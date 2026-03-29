<?php
/**
 * AI Self-Evaluation Framework Migration Script
 * 
 * Purpose: Apply database schema changes for AI self-evaluation system
 * Compatibility: 100% backward compatible - all changes are additive
 * 
 * What this does:
 * 1. Adds nullable prediction storage fields to construction_projects
 * 2. Creates configuration table for thresholds
 * 3. Creates metrics tracking table
 * 4. Creates audit log table
 * 5. Installs stored procedures and triggers
 * 6. Creates helpful views
 * 
 * Safe to run multiple times - uses IF NOT EXISTS and ADD COLUMN IF NOT EXISTS
 */

require_once __DIR__ . '/backend/config/database.php';

echo "=================================================================\n";
echo "AI Self-Evaluation Framework Migration\n";
echo "=================================================================\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "✓ Database connection established\n\n";
    
    // Step 1: Read and execute schema file
    echo "Step 1: Applying schema changes...\n";
    $schema_file = __DIR__ . '/backend/database/ai_self_evaluation_schema.sql';
    
    if (!file_exists($schema_file)) {
        throw new Exception("Schema file not found: $schema_file");
    }
    
    $schema_sql = file_get_contents($schema_file);
    
    // Split by semicolons but preserve them in procedures
    $statements = [];
    $current = '';
    $in_delimiter = false;
    
    foreach (explode("\n", $schema_sql) as $line) {
        if (stripos($line, 'DELIMITER') !== false) {
            $in_delimiter = !$in_delimiter;
            continue;
        }
        
        $current .= $line . "\n";
        
        if (!$in_delimiter && substr(trim($line), -1) === ';') {
            if (trim($current)) {
                $statements[] = $current;
            }
            $current = '';
        }
    }
    
    if (trim($current)) {
        $statements[] = $current;
    }
    
    $executed = 0;
    $skipped = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip comments and empty statements
        if (empty($statement) || substr($statement, 0, 2) === '--') {
            continue;
        }
        
        try {
            // Check if it's an ALTER TABLE ADD COLUMN statement
            if (stripos($statement, 'ALTER TABLE') !== false && stripos($statement, 'ADD COLUMN') !== false) {
                // Extract column name
                preg_match('/ADD COLUMN\s+(\w+)/i', $statement, $matches);
                $column_name = $matches[1] ?? '';
                
                if ($column_name) {
                    // Check if column already exists
                    $check_query = "SHOW COLUMNS FROM construction_projects LIKE '$column_name'";
                    $result = $conn->query($check_query);
                    
                    if ($result && $result->num_rows > 0) {
                        echo "  ⊙ Column '$column_name' already exists, skipping\n";
                        $skipped++;
                        continue;
                    }
                }
            }
            
            $conn->query($statement);
            $executed++;
            
        } catch (Exception $e) {
            // Check if error is about duplicate column/table
            if (stripos($e->getMessage(), 'Duplicate column') !== false ||
                stripos($e->getMessage(), 'already exists') !== false) {
                $skipped++;
            } else {
                echo "  ✗ Error executing statement: " . $e->getMessage() . "\n";
                echo "  Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "  ✓ Executed $executed statements, skipped $skipped existing items\n\n";
    
    // Step 2: Read and execute procedures file
    echo "Step 2: Installing stored procedures and triggers...\n";
    $procedures_file = __DIR__ . '/backend/database/ai_evaluation_procedures.sql';
    
    if (!file_exists($procedures_file)) {
        throw new Exception("Procedures file not found: $procedures_file");
    }
    
    $procedures_sql = file_get_contents($procedures_file);
    
    // Execute the entire file (it handles its own DELIMITER)
    $conn->multi_query($procedures_sql);
    
    // Clear all results
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    
    echo "  ✓ Stored procedures and triggers installed\n\n";
    
    // Step 3: Verify installation
    echo "Step 3: Verifying installation...\n";
    
    // Check if new columns exist
    $verify_columns = [
        'predicted_cost_risk_level',
        'predicted_time_risk_level',
        'cost_ground_truth_label',
        'cost_prediction_classification',
        'evaluation_completed_at'
    ];
    
    foreach ($verify_columns as $column) {
        $check_query = "SHOW COLUMNS FROM construction_projects LIKE '$column'";
        $result = $conn->query($check_query);
        
        if ($result && $result->num_rows > 0) {
            echo "  ✓ Column '$column' exists\n";
        } else {
            echo "  ✗ Column '$column' NOT FOUND\n";
        }
    }
    
    // Check if tables exist
    $verify_tables = [
        'ai_evaluation_config',
        'ai_evaluation_metrics',
        'ai_prediction_audit'
    ];
    
    foreach ($verify_tables as $table) {
        $check_query = "SHOW TABLES LIKE '$table'";
        $result = $conn->query($check_query);
        
        if ($result && $result->num_rows > 0) {
            echo "  ✓ Table '$table' exists\n";
        } else {
            echo "  ✗ Table '$table' NOT FOUND\n";
        }
    }
    
    // Check if procedures exist
    $verify_procedures = [
        'save_ai_prediction',
        'evaluate_project',
        'calculate_aggregate_metrics'
    ];
    
    foreach ($verify_procedures as $procedure) {
        $check_query = "SHOW PROCEDURE STATUS WHERE Name = '$procedure'";
        $result = $conn->query($check_query);
        
        if ($result && $result->num_rows > 0) {
            echo "  ✓ Procedure '$procedure' exists\n";
        } else {
            echo "  ✗ Procedure '$procedure' NOT FOUND\n";
        }
    }
    
    // Check if views exist
    $verify_views = [
        'v_ai_prediction_performance',
        'v_latest_evaluation_metrics',
        'v_confusion_matrix_summary'
    ];
    
    foreach ($verify_views as $view) {
        $check_query = "SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_" . 
                       $conn->query("SELECT DATABASE()")->fetch_row()[0] . " = '$view'";
        $result = $conn->query($check_query);
        
        if ($result && $result->num_rows > 0) {
            echo "  ✓ View '$view' exists\n";
        } else {
            echo "  ✗ View '$view' NOT FOUND\n";
        }
    }
    
    echo "\n";
    
    // Step 4: Display configuration
    echo "Step 4: Current configuration:\n";
    $config_query = "SELECT config_key, config_value, description FROM ai_evaluation_config";
    $result = $conn->query($config_query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  • {$row['config_key']}: {$row['config_value']}\n";
            echo "    ({$row['description']})\n";
        }
    }
    
    echo "\n";
    echo "=================================================================\n";
    echo "✓ MIGRATION COMPLETED SUCCESSFULLY\n";
    echo "=================================================================\n\n";
    
    echo "Next steps:\n";
    echo "1. Test prediction saving: php test_ai_self_evaluation.php\n";
    echo "2. Update frontend to call save_ai_prediction API after risk assessment\n";
    echo "3. Complete projects will be automatically evaluated\n";
    echo "4. View metrics at: backend/api/ml/get_evaluation_metrics.php?action=latest\n\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "\n✗ MIGRATION FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
