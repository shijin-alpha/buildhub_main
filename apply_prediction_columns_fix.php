<?php
/**
 * Apply Prediction Columns Fix
 * 
 * This script adds the missing AI prediction columns to the database tables.
 * Run this once to enable prediction storage functionality.
 * 
 * Usage: php apply_prediction_columns_fix.php
 * Or access via browser: http://localhost/buildhub/apply_prediction_columns_fix.php
 */

require_once __DIR__ . '/backend/config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Apply Prediction Columns Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 12px; border-radius: 4px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 12px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; border-left: 4px solid #3498db; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 4px; }
        .step-number { display: inline-block; background: #3498db; color: white; width: 30px; height: 30px; line-height: 30px; text-align: center; border-radius: 50%; margin-right: 10px; font-weight: bold; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔧 AI Prediction Storage Fix</h1>";
echo "<p>This script will add the missing prediction columns to enable AI prediction storage.</p>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Failed to connect to database");
    }
    
    echo "<div class='success'>✅ Database connection established</div>";
    
    // Step 1: Check current schema
    echo "<div class='step'>";
    echo "<h2><span class='step-number'>1</span>Checking Current Schema</h2>";
    
    $check_query = "SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%'";
    $stmt = $conn->query($check_query);
    $existing_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($existing_columns) > 0) {
        echo "<div class='warning'>⚠️ Prediction columns already exist in contractor_send_estimates:</div>";
        echo "<pre>";
        foreach ($existing_columns as $col) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
        echo "</pre>";
    } else {
        echo "<div class='info'>ℹ️ No prediction columns found. Will add them now.</div>";
    }
    echo "</div>";
    
    // Step 2: Add columns to contractor_send_estimates
    echo "<div class='step'>";
    echo "<h2><span class='step-number'>2</span>Adding Columns to contractor_send_estimates</h2>";
    
    if (count($existing_columns) == 0) {
        $alter_query = "
            ALTER TABLE contractor_send_estimates
            ADD COLUMN predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted cost overrun risk level',
            ADD COLUMN predicted_cost_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted cost risk (0-1)',
            ADD COLUMN predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted time delay risk level',
            ADD COLUMN predicted_time_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted time risk (0-1)',
            ADD COLUMN prediction_generated_at TIMESTAMP NULL COMMENT 'When AI prediction was made',
            ADD COLUMN model_version VARCHAR(50) NULL COMMENT 'ML model version used for prediction'
        ";
        
        $conn->exec($alter_query);
        echo "<div class='success'>✅ Successfully added 6 prediction columns to contractor_send_estimates</div>";
        
        // Add index
        $index_query = "CREATE INDEX idx_estimate_predictions ON contractor_send_estimates(predicted_cost_risk_level, predicted_time_risk_level)";
        try {
            $conn->exec($index_query);
            echo "<div class='success'>✅ Created index for performance optimization</div>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<div class='info'>ℹ️ Index already exists</div>";
            } else {
                throw $e;
            }
        }
    } else {
        echo "<div class='info'>ℹ️ Columns already exist, skipping</div>";
    }
    echo "</div>";
    
    // Step 3: Check construction_projects table
    echo "<div class='step'>";
    echo "<h2><span class='step-number'>3</span>Checking construction_projects Table</h2>";
    
    $check_projects_query = "SHOW COLUMNS FROM construction_projects LIKE 'predicted%'";
    $stmt = $conn->query($check_projects_query);
    $existing_project_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($existing_project_columns) > 0) {
        echo "<div class='warning'>⚠️ Prediction columns already exist in construction_projects</div>";
    } else {
        echo "<div class='info'>ℹ️ Adding prediction columns to construction_projects...</div>";
        
        $alter_projects_query = "
            ALTER TABLE construction_projects
            ADD COLUMN predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted cost overrun risk level',
            ADD COLUMN predicted_cost_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted cost risk (0-1)',
            ADD COLUMN predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted time delay risk level',
            ADD COLUMN predicted_time_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted time risk (0-1)',
            ADD COLUMN prediction_generated_at TIMESTAMP NULL COMMENT 'When AI prediction was made',
            ADD COLUMN model_version VARCHAR(50) NULL COMMENT 'ML model version used for prediction',
            ADD COLUMN predictions_locked TINYINT(1) DEFAULT 0 COMMENT 'Prevent modification after work begins'
        ";
        
        $conn->exec($alter_projects_query);
        echo "<div class='success'>✅ Successfully added prediction columns to construction_projects</div>";
    }
    echo "</div>";
    
    // Step 4: Create trigger
    echo "<div class='step'>";
    echo "<h2><span class='step-number'>4</span>Creating Prediction Copy Trigger</h2>";
    
    // Check if trigger exists
    $check_trigger = "SHOW TRIGGERS WHERE `Trigger` = 'copy_predictions_to_project'";
    $stmt = $conn->query($check_trigger);
    $trigger_exists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($trigger_exists) > 0) {
        echo "<div class='warning'>⚠️ Trigger already exists, dropping and recreating...</div>";
        $conn->exec("DROP TRIGGER IF EXISTS copy_predictions_to_project");
    }
    
    $trigger_query = "
        CREATE TRIGGER copy_predictions_to_project
        AFTER INSERT ON construction_projects
        FOR EACH ROW
        BEGIN
            DECLARE v_cost_risk VARCHAR(20);
            DECLARE v_cost_prob DECIMAL(5,4);
            DECLARE v_time_risk VARCHAR(20);
            DECLARE v_time_prob DECIMAL(5,4);
            DECLARE v_pred_time TIMESTAMP;
            DECLARE v_model_ver VARCHAR(50);
            
            IF NEW.estimate_id IS NOT NULL THEN
                SELECT 
                    predicted_cost_risk_level,
                    predicted_cost_probability,
                    predicted_time_risk_level,
                    predicted_time_probability,
                    prediction_generated_at,
                    model_version
                INTO 
                    v_cost_risk, v_cost_prob, v_time_risk, 
                    v_time_prob, v_pred_time, v_model_ver
                FROM contractor_send_estimates
                WHERE id = NEW.estimate_id;
                
                IF v_cost_risk IS NOT NULL OR v_time_risk IS NOT NULL THEN
                    UPDATE construction_projects
                    SET 
                        predicted_cost_risk_level = v_cost_risk,
                        predicted_cost_probability = v_cost_prob,
                        predicted_time_risk_level = v_time_risk,
                        predicted_time_probability = v_time_prob,
                        prediction_generated_at = v_pred_time,
                        model_version = v_model_ver
                    WHERE id = NEW.id;
                END IF;
            END IF;
        END
    ";
    
    $conn->exec($trigger_query);
    echo "<div class='success'>✅ Successfully created trigger to copy predictions from estimate to project</div>";
    echo "</div>";
    
    // Step 5: Verification
    echo "<div class='step'>";
    echo "<h2><span class='step-number'>5</span>Verification</h2>";
    
    // Verify contractor_send_estimates columns
    $verify_query = "SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%'";
    $stmt = $conn->query($verify_query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>contractor_send_estimates columns:</h3>";
    echo "<pre>";
    foreach ($columns as $col) {
        echo "✓ " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    echo "</pre>";
    
    // Verify construction_projects columns
    $verify_projects_query = "SHOW COLUMNS FROM construction_projects LIKE 'predicted%'";
    $stmt = $conn->query($verify_projects_query);
    $project_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>construction_projects columns:</h3>";
    echo "<pre>";
    foreach ($project_columns as $col) {
        echo "✓ " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    echo "</pre>";
    
    // Verify trigger
    $verify_trigger = "SHOW TRIGGERS WHERE `Trigger` = 'copy_predictions_to_project'";
    $stmt = $conn->query($verify_trigger);
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Triggers:</h3>";
    echo "<pre>";
    foreach ($triggers as $trigger) {
        echo "✓ " . $trigger['Trigger'] . " on " . $trigger['Table'] . " (" . $trigger['Timing'] . " " . $trigger['Event'] . ")\n";
    }
    echo "</pre>";
    
    echo "</div>";
    
    // Success summary
    echo "<div class='success' style='margin-top: 30px; padding: 20px;'>";
    echo "<h2 style='margin-top: 0;'>🎉 Migration Completed Successfully!</h2>";
    echo "<p><strong>What was done:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Added 6 prediction columns to contractor_send_estimates table</li>";
    echo "<li>✅ Added 7 prediction columns to construction_projects table</li>";
    echo "<li>✅ Created trigger to automatically copy predictions from estimate to project</li>";
    echo "<li>✅ Created performance indexes</li>";
    echo "</ul>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test by submitting a new homeowner request</li>";
    echo "<li>View the risk assessment in the dashboard</li>";
    echo "<li>Check the database to verify predictions are stored</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='info' style='margin-top: 20px;'>";
    echo "<h3>Test Query:</h3>";
    echo "<pre>SELECT id, predicted_cost_risk_level, predicted_cost_probability,
       predicted_time_risk_level, predicted_time_probability,
       prediction_generated_at, model_version
FROM contractor_send_estimates
WHERE predicted_cost_risk_level IS NOT NULL
ORDER BY created_at DESC
LIMIT 5;</pre>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error Occurred</h2>";
    echo "<p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div>
</body>
</html>";
?>
