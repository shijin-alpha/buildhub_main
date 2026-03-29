<?php
/**
 * Apply ML Prediction Lifecycle Fixes
 * 
 * This script applies all database schema changes and verifies the fixes
 * Run this file to fix the ML prediction storage and evaluation system
 */

require_once 'backend/config/database.php';

echo "=" . str_repeat("=", 79) . "\n";
echo "ML PREDICTION LIFECYCLE FIX - EXECUTION SCRIPT\n";
echo "=" . str_repeat("=", 79) . "\n\n";

$database = new Database();
$conn = $database->getConnection();

// Check if connection is mysqli or PDO
$is_mysqli = ($conn instanceof mysqli);
$is_pdo = ($conn instanceof PDO);

$fixes_applied = 0;
$fixes_failed = 0;

// ============================================================================
// Step 1: Add prediction columns to layout_requests
// ============================================================================
echo "Step 1: Adding prediction columns to layout_requests table...\n";

$sql_file = 'backend/database/schema_fixes/01_layout_requests_predictions.sql';
if (file_exists($sql_file)) {
    $sql = file_get_contents($sql_file);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        try {
            if ($is_mysqli) {
                $conn->query($statement);
            } else {
                $conn->exec($statement);
            }
            echo "  ✓ Executed statement\n";
            $fixes_applied++;
        } catch (Exception $e) {
            // Check if error is "Duplicate column" which means already applied
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "  ℹ Already applied\n";
            } else {
                echo "  ✗ Error: " . $e->getMessage() . "\n";
                $fixes_failed++;
            }
        }
    }
} else {
    echo "  ✗ SQL file not found: $sql_file\n";
    $fixes_failed++;
}

// ============================================================================
// Step 2: Add prediction columns to contractor_send_estimates
// ============================================================================
echo "\nStep 2: Adding prediction columns to contractor_send_estimates table...\n";

$sql_file = 'backend/database/schema_fixes/02_contractor_estimates_predictions.sql';
if (file_exists($sql_file)) {
    $sql = file_get_contents($sql_file);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        try {
            $conn->query($statement);
            echo "  ✓ Executed statement\n";
            $fixes_applied++;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'Duplicate key') !== false) {
                echo "  ℹ Already applied\n";
            } else {
                echo "  ✗ Error: " . $e->getMessage() . "\n";
                $fixes_failed++;
            }
        }
    }
} else {
    echo "  ✗ SQL file not found: $sql_file\n";
    $fixes_failed++;
}

// ============================================================================
// Step 3: Update construction_projects for 3-class evaluation
// ============================================================================
echo "\nStep 3: Updating construction_projects for 3-class evaluation...\n";

$sql_file = 'backend/database/schema_fixes/03_update_construction_projects_evaluation.sql';
if (file_exists($sql_file)) {
    $sql = file_get_contents($sql_file);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        try {
            $conn->query($statement);
            echo "  ✓ Executed statement\n";
            $fixes_applied++;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "  ℹ Already applied\n";
            } else {
                echo "  ✗ Error: " . $e->getMessage() . "\n";
                $fixes_failed++;
            }
        }
    }
} else {
    echo "  ✗ SQL file not found: $sql_file\n";
    $fixes_failed++;
}

// ============================================================================
// Step 4: Install 3-class evaluation procedures
// ============================================================================
echo "\nStep 4: Installing 3-class evaluation procedures...\n";

$sql_file = 'backend/database/procedures/evaluate_project_3class.sql';
if (file_exists($sql_file)) {
    $sql = file_get_contents($sql_file);
    
    try {
        if ($is_mysqli) {
            // Execute the entire file (procedures use DELIMITER)
            $conn->multi_query($sql);
            
            // Clear all results
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            
            echo "  ✓ Procedures installed successfully\n";
            $fixes_applied++;
        } else {
            // PDO doesn't support multi_query with DELIMITER
            // Parse and execute procedures individually
            echo "  ℹ PDO detected - installing procedures individually...\n";
            
            // Remove DELIMITER commands and split by procedure
            $sql_clean = preg_replace('/DELIMITER\s+\$\s*/i', '', $sql);
            $sql_clean = preg_replace('/DELIMITER\s+;\s*/i', '', $sql_clean);
            
            // Split by $ delimiter (procedure separator)
            $procedures = explode('$', $sql_clean);
            
            $proc_count = 0;
            foreach ($procedures as $proc) {
                $proc = trim($proc);
                if (empty($proc) || strpos($proc, '--') === 0) continue;
                
                // Skip comment-only blocks
                if (preg_match('/^--.*$/m', $proc) && !preg_match('/CREATE\s+PROCEDURE/i', $proc)) {
                    continue;
                }
                
                try {
                    $conn->exec($proc);
                    $proc_count++;
                    echo "  ✓ Installed procedure #$proc_count\n";
                } catch (Exception $e) {
                    // Check if it's a "procedure already exists" error
                    if (strpos($e->getMessage(), 'already exists') !== false) {
                        echo "  ℹ Procedure #$proc_count already exists\n";
                    } else {
                        echo "  ⚠ Warning installing procedure #$proc_count: " . $e->getMessage() . "\n";
                    }
                }
            }
            
            if ($proc_count > 0) {
                echo "  ✓ Processed $proc_count procedures\n";
                $fixes_applied++;
            } else {
                echo "  ℹ No procedures found to install\n";
                echo "  You can manually run: mysql -u root -p buildhub < backend/database/procedures/evaluate_project_3class.sql\n";
            }
        }
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        echo "  Manual installation command:\n";
        echo "  mysql -u root -p buildhub < backend/database/procedures/evaluate_project_3class.sql\n";
        $fixes_failed++;
    }
} else {
    echo "  ✗ SQL file not found: $sql_file\n";
    $fixes_failed++;
}

// ============================================================================
// Verification
// ============================================================================
echo "\n" . str_repeat("=", 80) . "\n";
echo "VERIFICATION\n";
echo str_repeat("=", 80) . "\n\n";

// Check layout_requests columns
echo "Checking layout_requests prediction columns:\n";
if ($is_mysqli) {
    $result = $conn->query("SHOW COLUMNS FROM layout_requests LIKE 'predicted%'");
    $count = $result->num_rows;
} else {
    $stmt = $conn->query("SHOW COLUMNS FROM layout_requests LIKE 'predicted%'");
    $count = $stmt->rowCount();
}
echo "  Found $count prediction columns\n";
if ($count >= 6) {
    echo "  ✓ layout_requests columns OK\n";
} else {
    echo "  ✗ Missing columns in layout_requests\n";
}

// Check contractor_send_estimates columns
echo "\nChecking contractor_send_estimates prediction columns:\n";
if ($is_mysqli) {
    $result = $conn->query("SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%'");
    $count = $result->num_rows;
} else {
    $stmt = $conn->query("SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%'");
    $count = $stmt->rowCount();
}
echo "  Found $count prediction columns\n";
if ($count >= 6) {
    echo "  ✓ contractor_send_estimates columns OK\n";
} else {
    echo "  ✗ Missing columns in contractor_send_estimates\n";
}

// Check construction_projects evaluation columns
echo "\nChecking construction_projects evaluation columns:\n";
if ($is_mysqli) {
    $result = $conn->query("SHOW COLUMNS FROM construction_projects WHERE Field LIKE '%threshold%'");
    $count = $result->num_rows;
} else {
    $stmt = $conn->query("SHOW COLUMNS FROM construction_projects WHERE Field LIKE '%threshold%'");
    $count = $stmt->rowCount();
}
echo "  Found $count threshold columns\n";
if ($count >= 4) {
    echo "  ✓ construction_projects threshold columns OK\n";
} else {
    echo "  ✗ Missing threshold columns in construction_projects\n";
}

// Check procedures
echo "\nChecking evaluation procedures:\n";
if ($is_mysqli) {
    $result = $conn->query("SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name LIKE '%3class%'");
    $count = $result->num_rows;
} else {
    $stmt = $conn->query("SHOW PROCEDURE STATUS WHERE Db = DATABASE() AND Name LIKE '%3class%'");
    $count = $stmt->rowCount();
}
echo "  Found $count 3-class procedures\n";
if ($count >= 3) {
    echo "  ✓ Evaluation procedures installed\n";
} else {
    echo "  ℹ Procedures need manual installation (see above)\n";
}

// ============================================================================
// Summary
// ============================================================================
echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 80) . "\n\n";

echo "Fixes Applied: $fixes_applied\n";
echo "Fixes Failed: $fixes_failed\n\n";

if ($fixes_failed == 0) {
    echo "✅ ALL FIXES APPLIED SUCCESSFULLY!\n\n";
    echo "Next Steps:\n";
    echo "1. Test prediction storage by submitting a new homeowner request\n";
    echo "2. Verify predictions are saved in layout_requests table\n";
    echo "3. Create contractor estimate and verify predictions are copied\n";
    echo "4. Complete a project and verify 3-class evaluation works\n";
    echo "5. Complete a project and verify evaluation metrics update correctly\n";
} else {
    echo "⚠️  SOME FIXES FAILED\n\n";
    echo "Please review the errors above and fix manually if needed.\n";
}

echo "\n" . str_repeat("=", 80) . "\n";

if ($is_mysqli) {
    $conn->close();
} else {
    $conn = null;
}
?>
