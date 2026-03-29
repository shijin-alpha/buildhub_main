-- ============================================================================
-- AI Self-Evaluation Framework - Database Schema
-- ============================================================================
-- Purpose: Enable automatic evaluation of AI prediction accuracy against
--          actual project outcomes using confusion matrix classification
-- Compatibility: 100% backward compatible - all fields nullable
-- ============================================================================

-- Add AI prediction storage fields to construction_projects table
ALTER TABLE construction_projects
ADD COLUMN predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted cost overrun risk level',
ADD COLUMN predicted_cost_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted cost risk (0-1)',
ADD COLUMN predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted time delay risk level',
ADD COLUMN predicted_time_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted time risk (0-1)',
ADD COLUMN prediction_generated_at TIMESTAMP NULL COMMENT 'When AI prediction was made',
ADD COLUMN model_version VARCHAR(50) NULL COMMENT 'ML model version used for prediction',

-- Add actual overrun percentage fields (if not already present)
ADD COLUMN actual_cost_overrun_percentage DECIMAL(10,2) NULL COMMENT 'Actual cost overrun percentage',
-- actual_time_overrun_percentage already exists from schedule tracking

-- Add ground truth classification fields
ADD COLUMN cost_ground_truth_label ENUM('Overrun', 'No_Overrun') NULL COMMENT 'Actual cost outcome based on threshold',
ADD COLUMN time_ground_truth_label ENUM('Overrun', 'No_Overrun') NULL COMMENT 'Actual time outcome based on threshold',

-- Add confusion matrix classification fields
ADD COLUMN cost_prediction_classification ENUM('TP', 'FP', 'TN', 'FN') NULL COMMENT 'Cost prediction confusion matrix class',
ADD COLUMN time_prediction_classification ENUM('TP', 'FP', 'TN', 'FN') NULL COMMENT 'Time prediction confusion matrix class',

-- Add correctness flags
ADD COLUMN cost_prediction_correct TINYINT(1) NULL COMMENT '1 if cost prediction was correct, 0 if wrong',
ADD COLUMN time_prediction_correct TINYINT(1) NULL COMMENT '1 if time prediction was correct, 0 if wrong',

-- Add evaluation metadata
ADD COLUMN evaluation_completed_at TIMESTAMP NULL COMMENT 'When evaluation was performed',
ADD COLUMN predictions_locked TINYINT(1) DEFAULT 0 COMMENT 'Prevent modification after work begins',

-- Add indexes for performance
ADD INDEX idx_prediction_evaluation (status, evaluation_completed_at),
ADD INDEX idx_cost_classification (cost_prediction_classification),
ADD INDEX idx_time_classification (time_prediction_classification),
ADD INDEX idx_prediction_correctness (cost_prediction_correct, time_prediction_correct);

-- ============================================================================
-- System Configuration Table
-- ============================================================================

CREATE TABLE IF NOT EXISTS ai_evaluation_config (
  id INT PRIMARY KEY AUTO_INCREMENT,
  config_key VARCHAR(100) UNIQUE NOT NULL,
  config_value VARCHAR(255) NOT NULL,
  description TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by INT,
  FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default configuration
INSERT INTO ai_evaluation_config (config_key, config_value, description) VALUES
('cost_overrun_threshold', '5.0', 'Threshold percentage to classify cost overrun (default: 5%)'),
('time_overrun_threshold', '5.0', 'Threshold percentage to classify time overrun (default: 5%)'),
('high_risk_threshold', '0.70', 'Probability threshold to classify as High risk (default: 70%)'),
('medium_risk_threshold', '0.40', 'Probability threshold to classify as Medium risk (default: 40%)'),
('current_model_version', 'v1.0.0', 'Current ML model version identifier'),
('auto_evaluation_enabled', '1', 'Enable automatic evaluation on project completion (1=yes, 0=no)')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

-- ============================================================================
-- AI Evaluation Metrics Table
-- ============================================================================

CREATE TABLE IF NOT EXISTS ai_evaluation_metrics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  evaluation_date DATE NOT NULL,
  metric_type ENUM('cost', 'time') NOT NULL,
  
  -- Confusion Matrix Counts
  true_positives INT DEFAULT 0,
  false_positives INT DEFAULT 0,
  true_negatives INT DEFAULT 0,
  false_negatives INT DEFAULT 0,
  
  -- Calculated Metrics
  accuracy DECIMAL(5,4) NULL,
  precision_score DECIMAL(5,4) NULL,
  recall_score DECIMAL(5,4) NULL,
  f1_score DECIMAL(5,4) NULL,
  
  -- Sample Size
  total_projects INT DEFAULT 0,
  evaluated_projects INT DEFAULT 0,
  
  -- Metadata
  model_version VARCHAR(50),
  threshold_used DECIMAL(5,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY unique_evaluation (evaluation_date, metric_type, model_version),
  INDEX idx_metric_type (metric_type),
  INDEX idx_evaluation_date (evaluation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- AI Prediction Audit Log
-- ============================================================================

CREATE TABLE IF NOT EXISTS ai_prediction_audit (
  id INT PRIMARY KEY AUTO_INCREMENT,
  project_id INT NOT NULL,
  event_type ENUM('prediction_saved', 'prediction_locked', 'evaluation_completed') NOT NULL,
  event_data JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (project_id) REFERENCES construction_projects(id) ON DELETE CASCADE,
  INDEX idx_project_event (project_id, event_type),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- STEP 2: Create system configuration table for thresholds
-- ============================================================================

CREATE TABLE IF NOT EXISTS ai_evaluation_config (
  id INT PRIMARY KEY AUTO_INCREMENT,
  config_key VARCHAR(100) UNIQUE NOT NULL,
  config_value VARCHAR(255) NOT NULL,
  description TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  updated_by INT,
  FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default configuration values
INSERT INTO ai_evaluation_config (config_key, config_value, description) VALUES
('cost_overrun_threshold', '5.0', 'Threshold percentage to classify cost overrun as High (default: 5%)'),
('time_overrun_threshold', '5.0', 'Threshold percentage to classify time overrun as High (default: 5%)'),
('model_version', 'v1.0.0', 'Current ML model version identifier'),
('evaluation_enabled', '1', 'Enable/disable automatic AI evaluation (1=enabled, 0=disabled)'),
('min_projects_for_metrics', '10', 'Minimum completed projects required for reliable metrics')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

-- ============================================================================
-- STEP 3: Create aggregated metrics table
-- ============================================================================

CREATE TABLE IF NOT EXISTS ai_evaluation_metrics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  metric_type ENUM('cost', 'time') NOT NULL,
  evaluation_date DATE NOT NULL,
  total_projects INT NOT NULL DEFAULT 0,
  
  -- Confusion Matrix Counts
  true_positives INT NOT NULL DEFAULT 0,
  false_positives INT NOT NULL DEFAULT 0,
  true_negatives INT NOT NULL DEFAULT 0,
  false_negatives INT NOT NULL DEFAULT 0,
  
  -- Performance Metrics
  accuracy DECIMAL(5,2) NULL COMMENT 'Overall accuracy percentage',
  precision_score DECIMAL(5,2) NULL COMMENT 'Precision for High risk predictions',
  recall_score DECIMAL(5,2) NULL COMMENT 'Recall for High risk predictions',
  f1_score DECIMAL(5,2) NULL COMMENT 'F1 score for High risk predictions',
  
  -- Additional Metrics
  specificity DECIMAL(5,2) NULL COMMENT 'True negative rate',
  false_positive_rate DECIMAL(5,2) NULL COMMENT 'False positive rate',
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY unique_metric_date (metric_type, evaluation_date),
  INDEX idx_metric_type (metric_type),
  INDEX idx_evaluation_date (evaluation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================================
-- STEP 4: Create audit trail for AI predictions
-- ============================================================================

CREATE TABLE IF NOT EXISTS ai_prediction_audit (
  id INT PRIMARY KEY AUTO_INCREMENT,
  project_id INT NOT NULL,
  action_type ENUM('prediction_saved', 'prediction_locked', 'evaluation_completed') NOT NULL,
  
  -- Snapshot of prediction data
  cost_risk_level VARCHAR(20),
  cost_probability DECIMAL(5,2),
  time_risk_level VARCHAR(20),
  time_probability DECIMAL(5,2),
  
  -- Evaluation results (if applicable)
  cost_classification VARCHAR(10),
  time_classification VARCHAR(10),
  
  model_version VARCHAR(50),
  performed_by INT,
  performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  notes TEXT,
  
  FOREIGN KEY (project_id) REFERENCES construction_projects(id) ON DELETE CASCADE,
  FOREIGN KEY (performed_by) REFERENCES users(id),
  INDEX idx_project_audit (project_id, performed_at),
  INDEX idx_action_type (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- STEP 5: Create stored procedures for evaluation logic
-- ============================================================================

DELIMITER $$

-- Procedure to get current threshold values
CREATE PROCEDURE get_evaluation_thresholds(
  OUT cost_threshold DECIMAL(5,2),
  OUT time_threshold DECIMAL(5,2)
)
BEGIN
  SELECT 
    CAST(MAX(CASE WHEN config_key = 'cost_overrun_threshold' THEN config_value END) AS DECIMAL(5,2)),
    CAST(MAX(CASE WHEN config_key = 'time_overrun_threshold' THEN config_value END) AS DECIMAL(5,2))
  INTO cost_threshold, time_threshold
  FROM ai_evaluation_config
  WHERE config_key IN ('cost_overrun_threshold', 'time_overrun_threshold');
  
  -- Default to 5% if not configured
  IF cost_threshold IS NULL THEN SET cost_threshold = 5.0; END IF;
  IF time_threshold IS NULL THEN SET time_threshold = 5.0; END IF;
END$$


-- Procedure to calculate actual cost overrun percentage
CREATE PROCEDURE calculate_actual_cost_overrun(IN p_project_id INT)
BEGIN
  DECLARE v_original_estimate DECIMAL(15,2);
  DECLARE v_total_stage_payments DECIMAL(15,2);
  DECLARE v_total_custom_payments DECIMAL(15,2);
  DECLARE v_total_cost DECIMAL(15,2);
  DECLARE v_cost_overrun_pct DECIMAL(10,2);
  
  -- Get original estimate
  SELECT estimated_cost INTO v_original_estimate
  FROM construction_projects
  WHERE id = p_project_id;
  
  -- Calculate total stage payments
  SELECT COALESCE(SUM(amount), 0) INTO v_total_stage_payments
  FROM stage_payment_requests
  WHERE project_id = p_project_id 
    AND status IN ('paid', 'pending');
  
  -- Calculate total custom payments
  SELECT COALESCE(SUM(amount), 0) INTO v_total_custom_payments
  FROM custom_payment_requests
  WHERE project_id = p_project_id 
    AND status IN ('approved', 'paid', 'pending');
  
  -- Calculate total cost and overrun percentage
  SET v_total_cost = v_total_stage_payments + v_total_custom_payments;
  
  IF v_original_estimate > 0 THEN
    SET v_cost_overrun_pct = ((v_total_cost - v_original_estimate) / v_original_estimate) * 100;
    
    -- Update the project record
    UPDATE construction_projects
    SET actual_cost_overrun_percentage = v_cost_overrun_pct
    WHERE id = p_project_id;
  END IF;
END$$

-- Procedure to determine ground truth labels based on thresholds
CREATE PROCEDURE determine_ground_truth_labels(IN p_project_id INT)
BEGIN
  DECLARE v_cost_overrun DECIMAL(10,2);
  DECLARE v_time_overrun DECIMAL(10,2);
  DECLARE v_cost_threshold DECIMAL(5,2);
  DECLARE v_time_threshold DECIMAL(5,2);
  DECLARE v_cost_label VARCHAR(10);
  DECLARE v_time_label VARCHAR(10);
  
  -- Get thresholds
  CALL get_evaluation_thresholds(v_cost_threshold, v_time_threshold);
  
  -- Get actual overrun percentages
  SELECT 
    actual_cost_overrun_percentage,
    actual_time_overrun_percentage
  INTO v_cost_overrun, v_time_overrun
  FROM construction_projects
  WHERE id = p_project_id;
  
  -- Determine cost ground truth label
  IF v_cost_overrun IS NOT NULL THEN
    IF v_cost_overrun >= v_cost_threshold THEN
      SET v_cost_label = 'High';
    ELSE
      SET v_cost_label = 'Low';
    END IF;
  END IF;
  
  -- Determine time ground truth label
  IF v_time_overrun IS NOT NULL THEN
    IF v_time_overrun >= v_time_threshold THEN
      SET v_time_label = 'High';
    ELSE
      SET v_time_label = 'Low';
    END IF;
  END IF;
  
  -- Update ground truth labels
  UPDATE construction_projects
  SET 
    cost_ground_truth_label = v_cost_label,
    time_ground_truth_label = v_time_label
  WHERE id = p_project_id;
END$$


-- Procedure to classify predictions using confusion matrix logic
CREATE PROCEDURE classify_predictions(IN p_project_id INT)
BEGIN
  DECLARE v_predicted_cost_risk VARCHAR(20);
  DECLARE v_predicted_time_risk VARCHAR(20);
  DECLARE v_cost_ground_truth VARCHAR(10);
  DECLARE v_time_ground_truth VARCHAR(10);
  DECLARE v_cost_classification VARCHAR(10);
  DECLARE v_time_classification VARCHAR(10);
  DECLARE v_cost_correct TINYINT(1);
  DECLARE v_time_correct TINYINT(1);
  
  -- Get prediction and ground truth data
  SELECT 
    predicted_cost_risk_level,
    predicted_time_risk_level,
    cost_ground_truth_label,
    time_ground_truth_label
  INTO 
    v_predicted_cost_risk,
    v_predicted_time_risk,
    v_cost_ground_truth,
    v_time_ground_truth
  FROM construction_projects
  WHERE id = p_project_id;
  
  -- Classify cost prediction
  IF v_predicted_cost_risk IS NOT NULL AND v_cost_ground_truth IS NOT NULL THEN
    -- Convert Medium to High for binary classification
    IF v_predicted_cost_risk = 'Medium' THEN
      SET v_predicted_cost_risk = 'High';
    END IF;
    
    IF v_predicted_cost_risk = 'High' AND v_cost_ground_truth = 'High' THEN
      SET v_cost_classification = 'TP';  -- True Positive
      SET v_cost_correct = 1;
    ELSEIF v_predicted_cost_risk = 'High' AND v_cost_ground_truth = 'Low' THEN
      SET v_cost_classification = 'FP';  -- False Positive
      SET v_cost_correct = 0;
    ELSEIF v_predicted_cost_risk = 'Low' AND v_cost_ground_truth = 'Low' THEN
      SET v_cost_classification = 'TN';  -- True Negative
      SET v_cost_correct = 1;
    ELSEIF v_predicted_cost_risk = 'Low' AND v_cost_ground_truth = 'High' THEN
      SET v_cost_classification = 'FN';  -- False Negative
      SET v_cost_correct = 0;
    END IF;
  END IF;
  
  -- Classify time prediction
  IF v_predicted_time_risk IS NOT NULL AND v_time_ground_truth IS NOT NULL THEN
    -- Convert Medium to High for binary classification
    IF v_predicted_time_risk = 'Medium' THEN
      SET v_predicted_time_risk = 'High';
    END IF;
    
    IF v_predicted_time_risk = 'High' AND v_time_ground_truth = 'High' THEN
      SET v_time_classification = 'TP';  -- True Positive
      SET v_time_correct = 1;
    ELSEIF v_predicted_time_risk = 'High' AND v_time_ground_truth = 'Low' THEN
      SET v_time_classification = 'FP';  -- False Positive
      SET v_time_correct = 0;
    ELSEIF v_predicted_time_risk = 'Low' AND v_time_ground_truth = 'Low' THEN
      SET v_time_classification = 'TN';  -- True Negative
      SET v_time_correct = 1;
    ELSEIF v_predicted_time_risk = 'Low' AND v_time_ground_truth = 'High' THEN
      SET v_time_classification = 'FN';  -- False Negative
      SET v_time_correct = 0;
    END IF;
  END IF;
  
  -- Update classification results
  UPDATE construction_projects
  SET 
    cost_prediction_classification = v_cost_classification,
    time_prediction_classification = v_time_classification,
    cost_prediction_correct = v_cost_correct,
    time_prediction_correct = v_time_correct,
    evaluation_completed_at = NOW()
  WHERE id = p_project_id;
  
  -- Log to audit trail
  INSERT INTO ai_prediction_audit (
    project_id,
    action_type,
    cost_classification,
    time_classification,
    performed_at
  ) VALUES (
    p_project_id,
    'evaluation_completed',
    v_cost_classification,
    v_time_classification,
    NOW()
  );
END$$


-- Master procedure to evaluate a completed project
CREATE PROCEDURE evaluate_project_predictions(IN p_project_id INT)
BEGIN
  DECLARE v_evaluation_enabled INT;
  DECLARE v_has_predictions TINYINT(1);
  DECLARE v_already_evaluated TINYINT(1);
  
  -- Check if evaluation is enabled
  SELECT CAST(config_value AS UNSIGNED) INTO v_evaluation_enabled
  FROM ai_evaluation_config
  WHERE config_key = 'evaluation_enabled';
  
  IF v_evaluation_enabled = 0 THEN
    LEAVE;
  END IF;
  
  -- Check if project has predictions
  SELECT 
    (predicted_cost_risk_level IS NOT NULL OR predicted_time_risk_level IS NOT NULL),
    (evaluation_completed_at IS NOT NULL)
  INTO v_has_predictions, v_already_evaluated
  FROM construction_projects
  WHERE id = p_project_id;
  
  -- Only evaluate if has predictions and not already evaluated
  IF v_has_predictions = 1 AND v_already_evaluated = 0 THEN
    -- Step 1: Calculate actual cost overrun
    CALL calculate_actual_cost_overrun(p_project_id);
    
    -- Step 2: Determine ground truth labels
    CALL determine_ground_truth_labels(p_project_id);
    
    -- Step 3: Classify predictions
    CALL classify_predictions(p_project_id);
    
    -- Step 4: Update aggregated metrics
    CALL update_aggregated_metrics();
  END IF;
END$$

-- Procedure to calculate and store aggregated metrics
CREATE PROCEDURE update_aggregated_metrics()
BEGIN
  DECLARE v_today DATE;
  SET v_today = CURDATE();
  
  -- Calculate cost metrics
  INSERT INTO ai_evaluation_metrics (
    metric_type,
    evaluation_date,
    total_projects,
    true_positives,
    false_positives,
    true_negatives,
    false_negatives,
    accuracy,
    precision_score,
    recall_score,
    f1_score,
    specificity,
    false_positive_rate
  )
  SELECT 
    'cost' as metric_type,
    v_today as evaluation_date,
    COUNT(*) as total_projects,
    SUM(CASE WHEN cost_prediction_classification = 'TP' THEN 1 ELSE 0 END) as true_positives,
    SUM(CASE WHEN cost_prediction_classification = 'FP' THEN 1 ELSE 0 END) as false_positives,
    SUM(CASE WHEN cost_prediction_classification = 'TN' THEN 1 ELSE 0 END) as true_negatives,
    SUM(CASE WHEN cost_prediction_classification = 'FN' THEN 1 ELSE 0 END) as false_negatives,
    -- Accuracy = (TP + TN) / Total
    ROUND((SUM(CASE WHEN cost_prediction_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as accuracy,
    -- Precision = TP / (TP + FP)
    ROUND((SUM(CASE WHEN cost_prediction_classification = 'TP' THEN 1 ELSE 0 END) / 
           NULLIF(SUM(CASE WHEN cost_prediction_classification IN ('TP', 'FP') THEN 1 ELSE 0 END), 0)) * 100, 2) as precision_score,
    -- Recall = TP / (TP + FN)
    ROUND((SUM(CASE WHEN cost_prediction_classification = 'TP' THEN 1 ELSE 0 END) / 
           NULLIF(SUM(CASE WHEN cost_prediction_classification IN ('TP', 'FN') THEN 1 ELSE 0 END), 0)) * 100, 2) as recall_score,
    -- F1 = 2 * (Precision * Recall) / (Precision + Recall)
    ROUND((2 * 
           (SUM(CASE WHEN cost_prediction_classification = 'TP' THEN 1 ELSE 0 END) / 
            NULLIF(SUM(CASE WHEN cost_prediction_classification IN ('TP', 'FP') THEN 1 ELSE 0 END), 0)) *
           (SUM(CASE WHEN cost_prediction_classification = 'TP' THEN 1 ELSE 0 END) / 
            NULLIF(SUM(CASE WHEN cost_prediction_classification IN ('TP', 'FN') THEN 1 ELSE 0 END), 0))) /
          NULLIF((SUM(CASE WHEN cost_prediction_classification = 'TP' THEN 1 ELSE 0 END) / 
                  NULLIF(SUM(CASE WHEN cost_prediction_classification IN ('TP', 'FP') THEN 1 ELSE 0 END), 0)) +
                 (SUM(CASE WHEN cost_prediction_classification = 'TP' THEN 1 ELSE 0 END) / 
                  NULLIF(SUM(CASE WHEN cost_prediction_classification IN ('TP', 'FN') THEN 1 ELSE 0 END), 0)), 0) * 100, 2) as f1_score,
    -- Specificity = TN / (TN + FP)
    ROUND((SUM(CASE WHEN cost_prediction_classification = 'TN' THEN 1 ELSE 0 END) / 
           NULLIF(SUM(CASE WHEN cost_prediction_classification IN ('TN', 'FP') THEN 1 ELSE 0 END), 0)) * 100, 2) as specificity,
    -- FPR = FP / (FP + TN)
    ROUND((SUM(CASE WHEN cost_prediction_classification = 'FP' THEN 1 ELSE 0 END) / 
           NULLIF(SUM(CASE WHEN cost_prediction_classification IN ('FP', 'TN') THEN 1 ELSE 0 END), 0)) * 100, 2) as false_positive_rate
  FROM construction_projects
  WHERE status = 'completed'
    AND evaluation_completed_at IS NOT NULL
    AND predicted_cost_risk_level IS NOT NULL
  ON DUPLICATE KEY UPDATE
    total_projects = VALUES(total_projects),
    true_positives = VALUES(true_positives),
    false_positives = VALUES(false_positives),
    true_negatives = VALUES(true_negatives),
    false_negatives = VALUES(false_negatives),
    accuracy = VALUES(accuracy),
    precision_score = VALUES(precision_score),
    recall_score = VALUES(recall_score),
    f1_score = VALUES(f1_score),
    specificity = VALUES(specificity),
    false_positive_rate = VALUES(false_positive_rate);
  
  -- Calculate time metrics
  INSERT INTO ai_evaluation_metrics (
    metric_type,
    evaluation_date,
    total_projects,
    true_positives,
    false_positives,
    true_negatives,
    false_negatives,
    accuracy,
    precision_score,
    recall_score,
    f1_score,
    specificity,
    false_positive_rate
  )
  SELECT 
    'time' as metric_type,
    v_today as evaluation_date,
    COUNT(*) as total_projects,
    SUM(CASE WHEN time_prediction_classification = 'TP' THEN 1 ELSE 0 END) as true_positives,
    SUM(CASE WHEN time_prediction_classification = 'FP' THEN 1 ELSE 0 END) as false_positives,
    SUM(CASE WHEN time_prediction_classification = 'TN' THEN 1 ELSE 0 END) as true_negatives,
    SUM(CASE WHEN time_prediction_classification = 'FN' THEN 1 ELSE 0 END) as false_negatives,
    ROUND((SUM(CASE WHEN time_prediction_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as accuracy,
    ROUND((SUM(CASE WHEN time_prediction_classification = 'TP' THEN 1 ELSE 0 END) / 
           NULLIF(SUM(CASE WHEN time_prediction_classification IN ('TP', 'FP') THEN 1 ELSE 0 END), 0)) * 100, 2) as precision_score,
    ROUND((SUM(CASE WHEN time_prediction_classification = 'TP' THEN 1 ELSE 0 END) / 
           NULLIF(SUM(CASE WHEN time_prediction_classification IN ('TP', 'FN') THEN 1 ELSE 0 END), 0)) * 100, 2) as recall_score,
    ROUND((2 * 
           (SUM(CASE WHEN time_prediction_classification = 'TP' THEN 1 ELSE 0 END) / 
            NULLIF(SUM(CASE WHEN time_prediction_classification IN ('TP', 'FP') THEN 1 ELSE 0 END), 0)) *
           (SUM(CASE WHEN time_prediction_classification = 'TP' THEN 1 ELSE 0 END) / 
            NULLIF(SUM(CASE WHEN time_prediction_classification IN ('TP', 'FN') THEN 1 ELSE 0 END), 0))) /
          NULLIF((SUM(CASE WHEN time_prediction_classification = 'TP' THEN 1 ELSE 0 END) / 
                  NULLIF(SUM(CASE WHEN time_prediction_classification IN ('TP', 'FP') THEN 1 ELSE 0 END), 0)) +
                 (SUM(CASE WHEN time_prediction_classification = 'TP' THEN 1 ELSE 0 END) / 
                  NULLIF(SUM(CASE WHEN time_prediction_classification IN ('TP', 'FN') THEN 1 ELSE 0 END), 0)), 0) * 100, 2) as f1_score,
    ROUND((SUM(CASE WHEN time_prediction_classification = 'TN' THEN 1 ELSE 0 END) / 
           NULLIF(SUM(CASE WHEN time_prediction_classification IN ('TN', 'FP') THEN 1 ELSE 0 END), 0)) * 100, 2) as specificity,
    ROUND((SUM(CASE WHEN time_prediction_classification = 'FP' THEN 1 ELSE 0 END) / 
           NULLIF(SUM(CASE WHEN time_prediction_classification IN ('FP', 'TN') THEN 1 ELSE 0 END), 0)) * 100, 2) as false_positive_rate
  FROM construction_projects
  WHERE status = 'completed'
    AND evaluation_completed_at IS NOT NULL
    AND predicted_time_risk_level IS NOT NULL
  ON DUPLICATE KEY UPDATE
    total_projects = VALUES(total_projects),
    true_positives = VALUES(true_positives),
    false_positives = VALUES(false_positives),
    true_negatives = VALUES(true_negatives),
    false_negatives = VALUES(false_negatives),
    accuracy = VALUES(accuracy),
    precision_score = VALUES(precision_score),
    recall_score = VALUES(recall_score),
    f1_score = VALUES(f1_score),
    specificity = VALUES(specificity),
    false_positive_rate = VALUES(false_positive_rate);
END$$


-- ============================================================================
-- STEP 6: Create triggers for automatic evaluation
-- ============================================================================

-- Trigger to lock predictions when project starts
CREATE TRIGGER lock_predictions_on_start
BEFORE UPDATE ON construction_projects
FOR EACH ROW
BEGIN
  -- Lock predictions when actual_start_date is set for the first time
  IF NEW.actual_start_date IS NOT NULL AND OLD.actual_start_date IS NULL THEN
    SET NEW.predictions_locked = 1;
  END IF;
  
  -- Prevent modification of predictions if locked
  IF OLD.predictions_locked = 1 THEN
    SET NEW.predicted_cost_risk_level = OLD.predicted_cost_risk_level;
    SET NEW.predicted_cost_probability = OLD.predicted_cost_probability;
    SET NEW.predicted_time_risk_level = OLD.predicted_time_risk_level;
    SET NEW.predicted_time_probability = OLD.predicted_time_probability;
    SET NEW.prediction_generated_at = OLD.prediction_generated_at;
    SET NEW.model_version = OLD.model_version;
  END IF;
END$$

-- Trigger to automatically evaluate when project completes
CREATE TRIGGER auto_evaluate_on_completion
AFTER UPDATE ON construction_projects
FOR EACH ROW
BEGIN
  -- Trigger evaluation when status changes to 'completed'
  IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
    CALL evaluate_project_predictions(NEW.id);
  END IF;
END$$

DELIMITER ;

-- ============================================================================
-- STEP 7: Create views for easy metric access
-- ============================================================================

-- View for latest AI performance metrics
CREATE OR REPLACE VIEW v_latest_ai_metrics AS
SELECT 
  m1.metric_type,
  m1.evaluation_date,
  m1.total_projects,
  m1.true_positives,
  m1.false_positives,
  m1.true_negatives,
  m1.false_negatives,
  m1.accuracy,
  m1.precision_score,
  m1.recall_score,
  m1.f1_score,
  m1.specificity,
  m1.false_positive_rate,
  m1.created_at
FROM ai_evaluation_metrics m1
INNER JOIN (
  SELECT metric_type, MAX(evaluation_date) as max_date
  FROM ai_evaluation_metrics
  GROUP BY metric_type
) m2 ON m1.metric_type = m2.metric_type AND m1.evaluation_date = m2.max_date;

-- View for project evaluation summary
CREATE OR REPLACE VIEW v_project_evaluation_summary AS
SELECT 
  cp.id as project_id,
  cp.project_name,
  cp.status,
  
  -- Predictions
  cp.predicted_cost_risk_level,
  cp.predicted_cost_probability,
  cp.predicted_time_risk_level,
  cp.predicted_time_probability,
  cp.prediction_generated_at,
  cp.model_version,
  
  -- Actuals
  cp.actual_cost_overrun_percentage,
  cp.actual_time_overrun_percentage,
  
  -- Ground Truth
  cp.cost_ground_truth_label,
  cp.time_ground_truth_label,
  
  -- Classification
  cp.cost_prediction_classification,
  cp.time_prediction_classification,
  cp.cost_prediction_correct,
  cp.time_prediction_correct,
  
  -- Metadata
  cp.predictions_locked,
  cp.evaluation_completed_at,
  
  -- Thresholds used
  (SELECT config_value FROM ai_evaluation_config WHERE config_key = 'cost_overrun_threshold') as cost_threshold,
  (SELECT config_value FROM ai_evaluation_config WHERE config_key = 'time_overrun_threshold') as time_threshold
FROM construction_projects cp
WHERE cp.predicted_cost_risk_level IS NOT NULL 
   OR cp.predicted_time_risk_level IS NOT NULL;

-- View for confusion matrix breakdown
CREATE OR REPLACE VIEW v_confusion_matrix_breakdown AS
SELECT 
  'cost' as metric_type,
  cost_prediction_classification as classification,
  COUNT(*) as count,
  ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM construction_projects 
                              WHERE evaluation_completed_at IS NOT NULL 
                              AND predicted_cost_risk_level IS NOT NULL)), 2) as percentage
FROM construction_projects
WHERE evaluation_completed_at IS NOT NULL
  AND predicted_cost_risk_level IS NOT NULL
GROUP BY cost_prediction_classification

UNION ALL

SELECT 
  'time' as metric_type,
  time_prediction_classification as classification,
  COUNT(*) as count,
  ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM construction_projects 
                              WHERE evaluation_completed_at IS NOT NULL 
                              AND predicted_time_risk_level IS NOT NULL)), 2) as percentage
FROM construction_projects
WHERE evaluation_completed_at IS NOT NULL
  AND predicted_time_risk_level IS NOT NULL
GROUP BY time_prediction_classification;


-- ============================================================================
-- STEP 8: Create helper functions
-- ============================================================================

DELIMITER $$

-- Function to check if project has sufficient data for evaluation
CREATE FUNCTION can_evaluate_project(p_project_id INT) 
RETURNS TINYINT(1)
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE v_has_predictions TINYINT(1);
  DECLARE v_is_completed TINYINT(1);
  DECLARE v_already_evaluated TINYINT(1);
  
  SELECT 
    (predicted_cost_risk_level IS NOT NULL OR predicted_time_risk_level IS NOT NULL),
    (status = 'completed'),
    (evaluation_completed_at IS NOT NULL)
  INTO v_has_predictions, v_is_completed, v_already_evaluated
  FROM construction_projects
  WHERE id = p_project_id;
  
  IF v_has_predictions = 1 AND v_is_completed = 1 AND v_already_evaluated = 0 THEN
    RETURN 1;
  ELSE
    RETURN 0;
  END IF;
END$$

-- Function to get current model version
CREATE FUNCTION get_current_model_version() 
RETURNS VARCHAR(50)
DETERMINISTIC
READS SQL DATA
BEGIN
  DECLARE v_version VARCHAR(50);
  
  SELECT config_value INTO v_version
  FROM ai_evaluation_config
  WHERE config_key = 'model_version'
  LIMIT 1;
  
  IF v_version IS NULL THEN
    SET v_version = 'v1.0.0';
  END IF;
  
  RETURN v_version;
END$$

DELIMITER ;

-- ============================================================================
-- STEP 9: Grant necessary permissions (adjust as needed)
-- ============================================================================

-- Grant execute permissions on stored procedures
-- GRANT EXECUTE ON PROCEDURE buildhub.evaluate_project_predictions TO 'buildhub_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE buildhub.calculate_actual_cost_overrun TO 'buildhub_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE buildhub.determine_ground_truth_labels TO 'buildhub_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE buildhub.classify_predictions TO 'buildhub_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE buildhub.update_aggregated_metrics TO 'buildhub_user'@'localhost';

-- ============================================================================
-- STEP 10: Verification queries
-- ============================================================================

-- Verify schema changes
SELECT 
  COLUMN_NAME, 
  DATA_TYPE, 
  IS_NULLABLE, 
  COLUMN_DEFAULT,
  COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'buildhub' 
  AND TABLE_NAME = 'construction_projects'
  AND COLUMN_NAME LIKE '%predict%' OR COLUMN_NAME LIKE '%ground_truth%' OR COLUMN_NAME LIKE '%evaluation%'
ORDER BY ORDINAL_POSITION;

-- Verify new tables created
SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'buildhub'
  AND TABLE_NAME IN ('ai_evaluation_config', 'ai_evaluation_metrics', 'ai_prediction_audit')
ORDER BY TABLE_NAME;

-- Verify stored procedures created
SELECT ROUTINE_NAME, ROUTINE_TYPE, CREATED
FROM INFORMATION_SCHEMA.ROUTINES
WHERE ROUTINE_SCHEMA = 'buildhub'
  AND ROUTINE_NAME LIKE '%evaluate%' OR ROUTINE_NAME LIKE '%ground_truth%' OR ROUTINE_NAME LIKE '%classify%'
ORDER BY ROUTINE_NAME;

-- Verify triggers created
SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING
FROM INFORMATION_SCHEMA.TRIGGERS
WHERE TRIGGER_SCHEMA = 'buildhub'
  AND (TRIGGER_NAME LIKE '%prediction%' OR TRIGGER_NAME LIKE '%evaluate%')
ORDER BY TRIGGER_NAME;

-- Verify views created
SELECT TABLE_NAME, VIEW_DEFINITION
FROM INFORMATION_SCHEMA.VIEWS
WHERE TABLE_SCHEMA = 'buildhub'
  AND TABLE_NAME LIKE 'v_%ai%' OR TABLE_NAME LIKE 'v_%evaluation%' OR TABLE_NAME LIKE 'v_%confusion%'
ORDER BY TABLE_NAME;

-- ============================================================================
-- Installation Complete
-- ============================================================================

SELECT 'AI Self-Evaluation Framework installed successfully!' as status,
       'All schema changes are backward compatible and nullable' as compatibility,
       'Automatic evaluation will trigger on project completion' as automation,
       'Use v_latest_ai_metrics view to see current performance' as usage;

