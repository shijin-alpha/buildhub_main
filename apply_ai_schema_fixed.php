<?php
/**
 * Apply AI Schema - Fixed Version
 * Handles DELIMITER statements properly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== APPLYING AI SYSTEM DATABASE SCHEMA (FIXED) ===\n\n";

try {
    $conn = new mysqli('localhost', 'root', '', 'buildhub');
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "✅ Database connection successful\n\n";
    
    // Step 1: Add columns to contractor_send_estimates
    echo "--- STEP 1: Adding prediction columns to contractor_send_estimates ---\n";
    
    $columns = [
        "ADD COLUMN predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted cost overrun risk level'",
        "ADD COLUMN predicted_cost_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted cost risk (0-1)'",
        "ADD COLUMN predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted time delay risk level'",
        "ADD COLUMN predicted_time_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted time risk (0-1)'",
        "ADD COLUMN prediction_generated_at TIMESTAMP NULL COMMENT 'When AI prediction was made'",
        "ADD COLUMN model_version VARCHAR(50) NULL COMMENT 'ML model version used for prediction'"
    ];
    
    foreach ($columns as $column) {
        $sql = "ALTER TABLE contractor_send_estimates $column";
        $conn->query($sql); // Ignore errors for existing columns
    }
    echo "✅ Checked contractor_send_estimates columns\n";
    
    // Step 2: Add columns to construction_projects
    echo "\n--- STEP 2: Adding prediction columns to construction_projects ---\n";
    
    $project_columns = [
        "ADD COLUMN predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted cost overrun risk level'",
        "ADD COLUMN predicted_cost_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted cost risk (0-1)'",
        "ADD COLUMN predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted time delay risk level'",
        "ADD COLUMN predicted_time_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted time risk (0-1)'",
        "ADD COLUMN prediction_generated_at TIMESTAMP NULL COMMENT 'When AI prediction was made'",
        "ADD COLUMN model_version VARCHAR(50) NULL COMMENT 'ML model version used for prediction'",
        "ADD COLUMN actual_cost_overrun_percentage DECIMAL(10,2) NULL COMMENT 'Actual cost overrun percentage'",
        "ADD COLUMN cost_ground_truth_label ENUM('Overrun', 'No_Overrun') NULL COMMENT 'Actual cost outcome based on threshold'",
        "ADD COLUMN time_ground_truth_label ENUM('Overrun', 'No_Overrun') NULL COMMENT 'Actual time outcome based on threshold'",
        "ADD COLUMN cost_prediction_classification ENUM('TP', 'FP', 'TN', 'FN') NULL COMMENT 'Cost prediction confusion matrix class'",
        "ADD COLUMN time_prediction_classification ENUM('TP', 'FP', 'TN', 'FN') NULL COMMENT 'Time prediction confusion matrix class'",
        "ADD COLUMN cost_prediction_correct TINYINT(1) NULL COMMENT '1 if cost prediction was correct, 0 if wrong'",
        "ADD COLUMN time_prediction_correct TINYINT(1) NULL COMMENT '1 if time prediction was correct, 0 if wrong'",
        "ADD COLUMN evaluation_completed_at TIMESTAMP NULL COMMENT 'When evaluation was performed'",
        "ADD COLUMN predictions_locked TINYINT(1) DEFAULT 0 COMMENT 'Prevent modification after work begins'"
    ];
    
    foreach ($project_columns as $column) {
        $sql = "ALTER TABLE construction_projects $column";
        if ($conn->query($sql)) {
            echo "✅ Added column\n";
        } else {
            if (strpos($conn->error, 'Duplicate column') !== false) {
                echo "⚠️ Column already exists\n";
            } else {
                echo "❌ Error: " . $conn->error . "\n";
            }
        }
    }
    
    // Step 3: Create ai_evaluation_config table
    echo "\n--- STEP 3: Creating ai_evaluation_config table ---\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS ai_evaluation_config (
      id INT PRIMARY KEY AUTO_INCREMENT,
      config_key VARCHAR(100) UNIQUE NOT NULL,
      config_value VARCHAR(255) NOT NULL,
      description TEXT,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      updated_by INT,
      FOREIGN KEY (updated_by) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql)) {
        echo "✅ ai_evaluation_config table created\n";
    } else {
        echo "⚠️ " . $conn->error . "\n";
    }
    
    // Insert default config
    $sql = "INSERT INTO ai_evaluation_config (config_key, config_value, description) VALUES
    ('cost_overrun_threshold', '5.0', 'Threshold percentage to classify cost overrun (default: 5%)'),
    ('time_overrun_threshold', '5.0', 'Threshold percentage to classify time overrun (default: 5%)'),
    ('high_risk_threshold', '0.70', 'Probability threshold to classify as High risk (default: 70%)'),
    ('medium_risk_threshold', '0.40', 'Probability threshold to classify as Medium risk (default: 40%)'),
    ('current_model_version', 'v1.0.0', 'Current ML model version identifier'),
    ('auto_evaluation_enabled', '1', 'Enable automatic evaluation on project completion (1=yes, 0=no)')
    ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)";
    
    if ($conn->query($sql)) {
        echo "✅ Default configuration inserted\n";
    } else {
        echo "⚠️ " . $conn->error . "\n";
    }
    
    // Step 4: Create ai_prediction_audit table
    echo "\n--- STEP 4: Creating ai_prediction_audit table ---\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS ai_prediction_audit (
      id INT PRIMARY KEY AUTO_INCREMENT,
      project_id INT NOT NULL,
      event_type ENUM('prediction_saved', 'prediction_locked', 'evaluation_completed') NOT NULL,
      event_data JSON,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (project_id) REFERENCES construction_projects(id) ON DELETE CASCADE,
      INDEX idx_project_event (project_id, event_type),
      INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql)) {
        echo "✅ ai_prediction_audit table created\n";
    } else {
        echo "⚠️ " . $conn->error . "\n";
    }
    
    echo "\n--- STEP 5: Creating indexes ---\n";
    
    $indexes = [
        "CREATE INDEX idx_prediction_evaluation ON construction_projects(status, evaluation_completed_at)",
        "CREATE INDEX idx_cost_classification ON construction_projects(cost_prediction_classification)",
        "CREATE INDEX idx_time_classification ON construction_projects(time_prediction_classification)",
        "CREATE INDEX idx_prediction_correctness ON construction_projects(cost_prediction_correct, time_prediction_correct)",
        "CREATE INDEX idx_estimate_predictions ON contractor_send_estimates(predicted_cost_risk_level, predicted_time_risk_level)"
    ];
    
    foreach ($indexes as $index_sql) {
        if ($conn->query($index_sql)) {
            echo "✅ Index created\n";
        } else {
            if (strpos($conn->error, 'Duplicate key') !== false) {
                echo "⚠️ Index already exists\n";
            } else {
                echo "❌ Error: " . $conn->error . "\n";
            }
        }
    }
    
    echo "\n=== SCHEMA APPLICATION COMPLETE ===\n";
    echo "Note: Triggers and procedures need to be created separately via MySQL command line\n";
    echo "Run: mysql -u root buildhub < backend/database/ai_self_evaluation_schema.sql\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
