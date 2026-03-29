-- ============================================================================
-- Update construction_projects Table for 3-Class Evaluation
-- ============================================================================
-- Purpose: Support proper 3-class evaluation (Low/Medium/High)
-- Replace binary evaluation with multi-class confusion matrix
-- ============================================================================

-- Modify ground truth labels to support 3 classes
ALTER TABLE construction_projects
MODIFY COLUMN cost_ground_truth_label ENUM('Low', 'Medium', 'High') NULL 
  COMMENT 'Actual cost outcome (3-class)',
MODIFY COLUMN time_ground_truth_label ENUM('Low', 'Medium', 'High') NULL 
  COMMENT 'Actual time outcome (3-class)';

-- Add thresholds for 3-class classification
ALTER TABLE construction_projects
ADD COLUMN cost_medium_threshold DECIMAL(5,2) DEFAULT 5.0 
  COMMENT 'Threshold for Medium cost overrun (default 5%)',
ADD COLUMN cost_high_threshold DECIMAL(5,2) DEFAULT 15.0 
  COMMENT 'Threshold for High cost overrun (default 15%)',
ADD COLUMN time_medium_threshold DECIMAL(5,2) DEFAULT 5.0 
  COMMENT 'Threshold for Medium time overrun (default 5%)',
ADD COLUMN time_high_threshold DECIMAL(5,2) DEFAULT 15.0 
  COMMENT 'Threshold for High time overrun (default 15%)';

-- Update evaluation config for 3-class thresholds
INSERT INTO ai_evaluation_config (config_key, config_value, description) VALUES
('cost_medium_threshold', '5.0', 'Threshold percentage for Medium cost overrun (default: 5%)'),
('cost_high_threshold', '15.0', 'Threshold percentage for High cost overrun (default: 15%)'),
('time_medium_threshold', '5.0', 'Threshold percentage for Medium time overrun (default: 5%)'),
('time_high_threshold', '15.0', 'Threshold percentage for High time overrun (default: 15%)'),
('min_projects_for_retraining', '150', 'Minimum completed projects required for model retraining')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

-- Verify changes
SELECT config_key, config_value, description
FROM ai_evaluation_config
WHERE config_key LIKE '%threshold%' OR config_key LIKE '%retraining%'
ORDER BY config_key;
