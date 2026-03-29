<?php
/**
 * Apply Complete AI Schema
 * This script applies all required database changes for the AI system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== APPLYING AI SYSTEM DATABASE SCHEMA ===\n\n";

try {
    $conn = new mysqli('localhost', 'root', '', 'buildhub');
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "✅ Database connection successful\n\n";
    
    // Step 1: Apply prediction storage fix (estimates)
    echo "--- STEP 1: Applying prediction storage fix ---\n";
    $sql_file = 'backend/database/prediction_storage_fix.sql';
    
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        
        // Execute multi-query
        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());
        }
        
        if ($conn->error) {
            echo "⚠️ Warning: " . $conn->error . "\n";
        } else {
            echo "✅ Prediction storage fix applied\n";
        }
    } else {
        echo "❌ File not found: $sql_file\n";
    }
    
    // Step 2: Apply AI self-evaluation schema
    echo "\n--- STEP 2: Applying AI self-evaluation schema ---\n";
    $sql_file = 'backend/database/ai_self_evaluation_schema.sql';
    
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        
        // Execute multi-query
        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());
        }
        
        if ($conn->error) {
            echo "⚠️ Warning: " . $conn->error . "\n";
        } else {
            echo "✅ AI self-evaluation schema applied\n";
        }
    } else {
        echo "❌ File not found: $sql_file\n";
    }
    
    // Verify installation
    echo "\n--- VERIFICATION ---\n";
    
    // Check columns
    $result = $conn->query("SHOW COLUMNS FROM construction_projects LIKE 'predicted_cost_risk_level'");
    if ($result->num_rows > 0) {
        echo "✅ Prediction columns added to construction_projects\n";
    } else {
        echo "❌ Prediction columns NOT added\n";
    }
    
    // Check triggers
    $result = $conn->query("SHOW TRIGGERS LIKE 'copy_predictions_to_project'");
    if ($result->num_rows > 0) {
        echo "✅ copy_predictions_to_project trigger created\n";
    } else {
        echo "❌ copy_predictions_to_project trigger NOT created\n";
    }
    
    $result = $conn->query("SHOW TRIGGERS LIKE 'auto_evaluate_on_completion'");
    if ($result->num_rows > 0) {
        echo "✅ auto_evaluate_on_completion trigger created\n";
    } else {
        echo "❌ auto_evaluate_on_completion trigger NOT created\n";
    }
    
    // Check procedures
    $result = $conn->query("SHOW PROCEDURE STATUS WHERE Db = 'buildhub' AND Name = 'evaluate_project_predictions'");
    if ($result->num_rows > 0) {
        echo "✅ evaluate_project_predictions procedure created\n";
    } else {
        echo "❌ evaluate_project_predictions procedure NOT created\n";
    }
    
    // Check tables
    $result = $conn->query("SHOW TABLES LIKE 'ai_evaluation_config'");
    if ($result->num_rows > 0) {
        echo "✅ ai_evaluation_config table created\n";
    } else {
        echo "❌ ai_evaluation_config table NOT created\n";
    }
    
    echo "\n=== SCHEMA APPLICATION COMPLETE ===\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
