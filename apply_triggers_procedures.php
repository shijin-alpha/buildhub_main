<?php
/**
 * Apply Triggers and Procedures - PowerShell Compatible
 * This script applies the SQL files that contain DELIMITER statements
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== APPLYING TRIGGERS AND PROCEDURES ===\n\n";

$conn = new mysqli('localhost', 'root', '', 'buildhub');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "✅ Database connection successful\n\n";

// Function to execute SQL file with DELIMITER handling
function executeSQLFile($conn, $filename) {
    echo "--- Processing: $filename ---\n";
    
    if (!file_exists($filename)) {
        echo "❌ File not found: $filename\n";
        return false;
    }
    
    $sql = file_get_contents($filename);
    
    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Split by DELIMITER
    $parts = preg_split('/DELIMITER\s+(\S+)/i', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
    
    $current_delimiter = ';';
    $statements = [];
    
    for ($i = 0; $i < count($parts); $i++) {
        if ($i % 2 == 1) {
            // This is a delimiter definition
            $current_delimiter = trim($parts[$i]);
        } else {
            // This is SQL content
            $content = trim($parts[$i]);
            if (empty($content)) continue;
            
            // Split by current delimiter
            $stmts = explode($current_delimiter, $content);
            foreach ($stmts as $stmt) {
                $stmt = trim($stmt);
                if (!empty($stmt)) {
                    $statements[] = $stmt;
                }
            }
        }
    }
    
    // Execute each statement
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        // Skip DELIMITER statements
        if (stripos($statement, 'DELIMITER') === 0) continue;
        
        try {
            if ($conn->query($statement)) {
                $success_count++;
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            // Ignore "already exists" errors
            if (stripos($error, 'already exists') !== false || 
                stripos($error, 'Duplicate') !== false ||
                stripos($error, 'Can\'t DROP') !== false) {
                // Silently skip
            } else {
                echo "❌ Error: " . $error . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
                $error_count++;
            }
        }
    }
    
    echo "✅ Executed $success_count statements";
    if ($error_count > 0) {
        echo " ($error_count errors)";
    }
    echo "\n\n";
    
    return true;
}

// Apply prediction storage fix
echo "=== STEP 1: PREDICTION STORAGE FIX ===\n";
executeSQLFile($conn, 'backend/database/prediction_storage_fix.sql');

// Apply AI self-evaluation schema
echo "=== STEP 2: AI SELF-EVALUATION SCHEMA ===\n";
executeSQLFile($conn, 'backend/database/ai_self_evaluation_schema.sql');

// Verify installation
echo "=== VERIFICATION ===\n\n";

// Check triggers
echo "--- Checking Triggers ---\n";
$result = $conn->query("SHOW TRIGGERS");
$triggers = [];
while ($row = $result->fetch_assoc()) {
    $triggers[] = $row['Trigger'];
    echo "✅ " . $row['Trigger'] . "\n";
}

$required_triggers = ['copy_predictions_to_project', 'lock_predictions_on_start', 'auto_evaluate_on_completion'];
foreach ($required_triggers as $trigger) {
    if (!in_array($trigger, $triggers)) {
        echo "❌ MISSING: $trigger\n";
    }
}

// Check procedures
echo "\n--- Checking Stored Procedures ---\n";
$result = $conn->query("SHOW PROCEDURE STATUS WHERE Db = 'buildhub'");
$procedures = [];
while ($row = $result->fetch_assoc()) {
    $procedures[] = $row['Name'];
    echo "✅ " . $row['Name'] . "\n";
}

$required_procedures = [
    'evaluate_project_predictions',
    'calculate_actual_cost_overrun',
    'determine_ground_truth_labels',
    'classify_predictions',
    'update_aggregated_metrics'
];
foreach ($required_procedures as $procedure) {
    if (!in_array($procedure, $procedures)) {
        echo "❌ MISSING: $procedure\n";
    }
}

// Check views
echo "\n--- Checking Views ---\n";
$result = $conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
$views = [];
while ($row = $result->fetch_array()) {
    $views[] = $row[0];
    echo "✅ " . $row[0] . "\n";
}

$required_views = ['v_latest_ai_metrics', 'v_project_evaluation_summary', 'v_confusion_matrix_breakdown'];
foreach ($required_views as $view) {
    if (!in_array($view, $views)) {
        echo "❌ MISSING: $view\n";
    }
}

echo "\n=== APPLICATION COMPLETE ===\n";

// Summary
$triggers_ok = count(array_intersect($required_triggers, $triggers)) == count($required_triggers);
$procedures_ok = count(array_intersect($required_procedures, $procedures)) == count($required_procedures);
$views_ok = count(array_intersect($required_views, $views)) == count($required_views);

echo "\nSummary:\n";
echo ($triggers_ok ? "✅" : "❌") . " Triggers: " . count(array_intersect($required_triggers, $triggers)) . "/" . count($required_triggers) . "\n";
echo ($procedures_ok ? "✅" : "❌") . " Procedures: " . count(array_intersect($required_procedures, $procedures)) . "/" . count($required_procedures) . "\n";
echo ($views_ok ? "✅" : "❌") . " Views: " . count(array_intersect($required_views, $views)) . "/" . count($required_views) . "\n";

if ($triggers_ok && $procedures_ok && $views_ok) {
    echo "\n🎉 SYSTEM IS NOW FULLY OPERATIONAL! 🎉\n";
    echo "\nNext steps:\n";
    echo "1. Run: php verify_system_database.php\n";
    echo "2. Test the complete workflow\n";
    echo "3. Check that predictions copy automatically\n";
    echo "4. Verify evaluation runs on project completion\n";
} else {
    echo "\n⚠️ Some components are still missing. Check errors above.\n";
}

$conn->close();
?>
