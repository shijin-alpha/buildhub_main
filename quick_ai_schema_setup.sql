-- Quick AI Self-Evaluation Schema Setup
-- Run this in phpMyAdmin or MySQL command line

USE buildhub;

-- Add AI prediction columns to construction_projects
ALTER TABLE construction_projects
ADD COLUMN IF NOT EXISTS predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL,
ADD COLUMN IF NOT EXISTS predicted_cost_probability DECIMAL(5,4) NULL,
ADD COLUMN IF NOT EXISTS predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL,
ADD COLUMN IF NOT EXISTS predicted_time_probability DECIMAL(5,4) NULL,
ADD COLUMN IF NOT EXISTS prediction_generated_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS model_version VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS actual_cost_overrun_percentage DECIMAL(10,2) NULL,
ADD COLUMN IF NOT EXISTS actual_time_overrun_percentage DECIMAL(10,2) NULL,
ADD COLUMN IF NOT EXISTS cost_ground_truth_label ENUM('Overrun', 'No_Overrun') NULL,
ADD COLUMN IF NOT EXISTS time_ground_truth_label ENUM('Overrun', 'No_Overrun') NULL,
ADD COLUMN IF NOT EXISTS cost_prediction_classification ENUM('TP', 'FP', 'TN', 'FN') NULL,
ADD COLUMN IF NOT EXISTS time_prediction_classification ENUM('TP', 'FP', 'TN', 'FN') NULL,
ADD COLUMN IF NOT EXISTS cost_prediction_correct TINYINT(1) NULL,
ADD COLUMN IF NOT EXISTS time_prediction_correct TINYINT(1) NULL,
ADD COLUMN IF NOT EXISTS evaluation_completed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS predictions_locked TINYINT(1) DEFAULT 0;

-- Create config table
CREATE TABLE IF NOT EXISTS ai_evaluation_config (
  id INT PRIMARY KEY AUTO_INCREMENT,
  config_key VARCHAR(100) UNIQUE NOT NULL,
  config_value VARCHAR(255) NOT NULL,
  description TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default config
INSERT IGNORE INTO ai_evaluation_config (config_key, config_value, description) VALUES
('cost_overrun_threshold', '5.0', 'Threshold percentage to classify cost overrun'),
('time_overrun_threshold', '5.0', 'Threshold percentage to classify time overrun'),
('current_model_version', 'v1.0.0', 'Current ML model version'),
('auto_evaluation_enabled', '1', 'Enable automatic evaluation');

-- Create metrics table
CREATE TABLE IF NOT EXISTS ai_evaluation_metrics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  evaluation_date DATE NOT NULL,
  metric_type ENUM('cost', 'time') NOT NULL,
  true_positives INT DEFAULT 0,
  false_positives INT DEFAULT 0,
  true_negatives INT DEFAULT 0,
  false_negatives INT DEFAULT 0,
  accuracy DECIMAL(5,2) NULL,
  precision_score DECIMAL(5,2) NULL,
  recall_score DECIMAL(5,2) NULL,
  f1_score DECIMAL(5,2) NULL,
  total_projects INT DEFAULT 0,
  evaluated_projects INT DEFAULT 0,
  model_version VARCHAR(50),
  threshold_used DECIMAL(5,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_evaluation (evaluation_date, metric_type, model_version)
);

-- Create audit table
CREATE TABLE IF NOT EXISTS ai_prediction_audit (
  id INT PRIMARY KEY AUTO_INCREMENT,
  project_id INT NOT NULL,
  action_type ENUM('prediction_saved', 'prediction_locked', 'evaluation_completed') NOT NULL,
  cost_risk_level VARCHAR(20),
  cost_probability DECIMAL(5,2),
  time_risk_level VARCHAR(20),
  time_probability DECIMAL(5,2),
  cost_classification VARCHAR(10),
  time_classification VARCHAR(10),
  model_version VARCHAR(50),
  performed_by INT,
  performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  notes TEXT
);

SELECT 'AI Schema Setup Complete!' as status;
