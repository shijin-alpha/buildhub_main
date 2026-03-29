<?php
/**
 * Apply AI Schema - Immediate Execution
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== APPLYING AI SYSTEM DATABASE SCHEMA ===\n\n";

$conn = new mysqli('localhost', 'root', '', 'buildhub');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "✅ Database connection successful\n\n";

// Step 1: Skip contractor_send_estimates (already has columns)
echo "--- STEP 1: contractor_send_estimates (already configured) ---\n";
echo "✅ Skipped\n";

// Step 2: Add columns to construction_projects
echo "\n--- STEP 2: Adding prediction columns to construction_projects ---\n";

$project_columns = [
    "predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL",
    "predicted_cost_probability DECIMAL(5,4) NULL",
    "predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL",
    "predicted_time_probability DECIMAL(5,4) NULL",
    "prediction_generated_at TIMESTAMP NULL",
    "model_version VARCHAR(50) NULL",
    "actual_cost_overrun_percentage DECIMAL(10,2) NULL",
    "cost_ground_truth_label ENUM('Overrun', 'No_Overrun') NULL",
    "time_ground_truth_label ENUM('Overrun', 'No_Overrun') NULL",
    "cost_prediction_classification ENUM('TP', 'FP', 'TN', 'FN') NULL",
    "time_prediction_classification ENUM('TP', 'FP', 'TN', 'FN') NULL",
    "cost_prediction_correct TINYINT(1) NULL",
    "time_prediction_correct TINYINT(1) NULL",
    "evaluation_completed_at TIMESTAMP NULL",
    "predictions_locked TINYINT(1) DEFAULT 0"
];

foreach ($project_columns as $column) {
    $sql = "ALTER TABLE construction_projects ADD COLUMN $column";
    $conn->query($sql); // Ignore duplicate errors
}
echo "✅ Columns added to construction_projects\n";

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$conn->query($sql);
echo "✅ ai_evaluation_config table created\n";

// Insert default config
$sql = "INSERT INTO ai_evaluation_config (config_key, config_value, description) VALUES
('cost_overrun_threshold', '5.0', 'Threshold percentage to classify cost overrun'),
('time_overrun_threshold', '5.0', 'Threshold percentage to classify time overrun'),
('current_model_version', 'v1.0.0', 'Current ML model version'),
('auto_evaluation_enabled', '1', 'Enable automatic evaluation')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)";

$conn->query($sql);
echo "✅ Default configuration inserted\n";

// Step 4: Create ai_prediction_audit table
echo "\n--- STEP 4: Creating ai_prediction_audit table ---\n";

$sql = "CREATE TABLE IF NOT EXISTS ai_prediction_audit (
  id INT PRIMARY KEY AUTO_INCREMENT,
  project_id INT NOT NULL,
  event_type ENUM('prediction_saved', 'prediction_locked', 'evaluation_completed') NOT NULL,
  event_data JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES construction_projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$conn->query($sql);
echo "✅ ai_prediction_audit table created\n";

echo "\n=== SCHEMA APPLICATION COMPLETE ===\n\n";

// Verify
echo "--- VERIFICATION ---\n";
$result = $conn->query("SHOW COLUMNS FROM construction_projects LIKE 'predicted_cost_risk_level'");
echo ($result->num_rows > 0 ? "✅" : "❌") . " Prediction columns in construction_projects\n";

$result = $conn->query("SHOW TABLES LIKE 'ai_evaluation_config'");
echo ($result->num_rows > 0 ? "✅" : "❌") . " ai_evaluation_config table\n";

$result = $conn->query("SHOW TABLES LIKE 'ai_prediction_audit'");
echo ($result->num_rows > 0 ? "✅" : "❌") . " ai_prediction_audit table\n";

echo "\n⚠️ NOTE: Triggers and procedures must be created via MySQL command line:\n";
echo "mysql -u root buildhub < backend/database/ai_self_evaluation_schema.sql\n";

$conn->close();
?>
