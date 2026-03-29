<?php
/**
 * Apply Prediction Copy Trigger
 * 
 * This script:
 * 1. Adds prediction columns to contractor_send_estimates table
 * 2. Creates trigger to copy predictions from estimate to project
 * 3. Verifies the trigger was created successfully
 */

require_once __DIR__ . '/backend/config/database.php';

echo "🔧 Applying Prediction Copy Trigger...\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Step 1: Check if prediction columns exist in contractor_send_estimates
    echo "Step 1: Checking contractor_send_estimates table structure...\n";
    
    $check_columns = "SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted_cost_risk_level'";
    $result = $conn->query($check_columns);
    
    if ($result->num_rows === 0) {
        echo "  → Adding prediction columns to contractor_send_estimates...\n";
        
        $alter_query = "
            ALTER TABLE contractor_send_estimates
            ADD COLUMN predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL,
            ADD COLUMN predicted_cost_probability DECIMAL(5,4) NULL,
            ADD COLUMN predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL,
            ADD COLUMN predicted_time_probability DECIMAL(5,4) NULL,
            ADD COLUMN prediction_generated_at TIMESTAMP NULL,
            ADD COLUMN model_version VARCHAR(50) NULL
        ";
        
        if ($conn->query($alter_query)) {
            echo "  ✅ Prediction columns added successfully\n\n";
        } else {
            throw new Exception("Failed to add columns: " . $conn->error);
        }
    } else {
        echo "  ✅ Prediction columns already exist\n\n";
    }
    
    // Step 2: Read and execute trigger SQL
    echo "Step 2: Creating prediction copy trigger...\n";
    
    $sql_file = __DIR__ . '/backend/database/prediction_copy_trigger.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("Trigger SQL file not found: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    
    // Split by delimiter and execute each statement
    $statements = explode('$$', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Skip empty statements and comments
        if (empty($statement) || 
            strpos($statement, '--') === 0 || 
            strpos($statement, 'DELIMITER') === 0 ||
            strpos($statement, '/*') === 0) {
            continue;
        }
        
        // Execute statement
        if (!$conn->query($statement)) {
            // Ignore "trigger already exists" errors
            if (strpos($conn->error, 'already exists') === false) {
                throw new Exception("SQL Error: " . $conn->error . "\nStatement: " . substr($statement, 0, 200));
            }
        }
    }
    
    echo "  ✅ Trigger created successfully\n\n";
    
    // Step 3: Verify trigger exists
    echo "Step 3: Verifying trigger...\n";
    
    $verify_query = "
        SELECT 
            TRIGGER_NAME,
            EVENT_MANIPULATION,
            EVENT_OBJECT_TABLE,
            ACTION_TIMING
        FROM information_schema.TRIGGERS
        WHERE TRIGGER_SCHEMA = DATABASE()
          AND TRIGGER_NAME = 'copy_predictions_to_project'
    ";
    
    $result = $conn->query($verify_query);
    
    if ($result->num_rows > 0) {
        $trigger = $result->fetch_assoc();
        echo "  ✅ Trigger verified:\n";
        echo "     Name: {$trigger['TRIGGER_NAME']}\n";
        echo "     Event: {$trigger['ACTION_TIMING']} {$trigger['EVENT_MANIPULATION']}\n";
        echo "     Table: {$trigger['EVENT_OBJECT_TABLE']}\n\n";
    } else {
        throw new Exception("Trigger not found after creation");
    }
    
    // Step 4: Test the workflow
    echo "Step 4: Testing prediction workflow...\n";
    
    // Check if we have any estimates with predictions
    $test_query = "
        SELECT COUNT(*) as count
        FROM contractor_send_estimates
        WHERE predicted_cost_risk_level IS NOT NULL
    ";
    
    $result = $conn->query($test_query);
    $row = $result->fetch_assoc();
    
    echo "  → Estimates with predictions: {$row['count']}\n";
    
    // Check if we have any projects with predictions
    $test_query2 = "
        SELECT COUNT(*) as count
        FROM construction_projects
        WHERE predicted_cost_risk_level IS NOT NULL
    ";
    
    $result2 = $conn->query($test_query2);
    $row2 = $result2->fetch_assoc();
    
    echo "  → Projects with predictions: {$row2['count']}\n\n";
    
    echo "✅ PREDICTION COPY TRIGGER APPLIED SUCCESSFULLY!\n\n";
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "WORKFLOW NOW COMPLETE:\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "1. Homeowner fills form → Risk assessment runs\n";
    echo "2. RiskAssessmentPreview.jsx calls save_estimate_prediction.php\n";
    echo "3. Predictions saved to contractor_send_estimates table\n";
    echo "4. Homeowner submits → Project created with estimate_id\n";
    echo "5. Trigger fires → Copies predictions to construction_projects\n";
    echo "6. Project completes → Auto-evaluation runs\n";
    echo "7. AI learns from actual outcomes\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    echo "NEXT STEPS:\n";
    echo "1. Test the workflow by creating a new project request\n";
    echo "2. Verify predictions are saved to estimate\n";
    echo "3. Accept estimate and create project\n";
    echo "4. Verify predictions copied to project\n";
    echo "5. Complete project and verify evaluation runs\n\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
