-- ============================================================================
-- AI Self-Evaluation Framework - Stored Procedures & Triggers
-- ============================================================================

DELIMITER $$

-- ============================================================================
-- Procedure: Save AI Prediction
-- ============================================================================
-- Purpose: Store AI prediction results when project is confirmed
-- Called by: API after homeowner confirms project submission
-- ============================================================================

DROP PROCEDURE IF EXISTS save_ai_prediction$$
CREATE PROCEDURE save_ai_prediction(
  IN p_project_id INT,
  IN p_cost_risk_level VARCHAR(10),
  IN p_cost_probability DECIMAL(5,4),
  IN p_time_risk_level VARCHAR(10),
  IN p_time_probability DECIMAL(5,4),
  IN p_model_version VARCHAR(50)
)
BEGIN
  DECLARE v_already_locked INT DEFAULT 0;
  
  -- Check if predictions are already locked
  SELECT predictions_locked INTO v_already_locked
  FROM construction_projects
  WHERE id = p_project_id;
  
  IF v_already_locked = 1 THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Predictions are locked and cannot be modified';
  END IF;
  
  -- Save predictions
  UPDATE construction_projects
  SET 
    predicted_cost_risk_level = p_cost_risk_level,
    predicted_cost_probability = p_cost_probability,
    predicted_time_risk_level = p_time_risk_level,
    predicted_time_probability = p_time_probability,
    prediction_generated_at = NOW(),
    model_version = p_model_version
  WHERE id = p_project_id;
  
  -- Log the event
  INSERT INTO ai_prediction_audit (project_id, event_type, event_data)
  VALUES (
    p_project_id,
    'prediction_saved',
    JSON_OBJECT(
      'cost_risk', p_cost_risk_level,
      'cost_prob', p_cost_probability,
      'time_risk', p_time_risk_level,
      'time_prob', p_time_probability,
      'model_version', p_model_version
    )
  );
END$$

-- ============================================================================
-- Procedure: Calculate Actual Cost Overrun
-- ============================================================================
-- Purpose: Calculate actual cost overrun percentage from payments
-- Called by: Evaluation trigger or manually
-- ============================================================================

DROP PROCEDURE IF EXISTS calculate_actual_cost_overrun$$
CREATE PROCEDURE calculate_actual_cost_overrun(IN p_project_id INT)
BEGIN
  DECLARE v_original_estimate DECIMAL(15,2);
  DECLARE v_total_stage_payments DECIMAL(15,2);
  DECLARE v_total_custom_payments DECIMAL(15,2);
  DECLARE v_total_cost DECIMAL(15,2);
  DECLARE v_overrun_percentage DECIMAL(10,2);
  
  -- Get original estimate
  SELECT estimated_cost INTO v_original_estimate
  FROM construction_projects
  WHERE id = p_project_id;
  
  -- Calculate total stage payments
  SELECT COALESCE(SUM(amount), 0) INTO v_total_stage_payments
  FROM stage_payment_requests
  WHERE project_id = p_project_id
  AND status IN ('paid', 'pending', 'approved');
  
  -- Calculate total custom payments
  SELECT COALESCE(SUM(amount), 0) INTO v_total_custom_payments
  FROM custom_payment_requests
  WHERE project_id = p_project_id
  AND status IN ('paid', 'pending', 'approved');
  
  -- Calculate total cost and overrun
  SET v_total_cost = v_total_stage_payments + v_total_custom_payments;
  
  IF v_original_estimate > 0 THEN
    SET v_overrun_percentage = 
      ((v_total_cost - v_original_estimate) / v_original_estimate) * 100;
    
    UPDATE construction_projects
    SET actual_cost_overrun_percentage = v_overrun_percentage
    WHERE id = p_project_id;
  END IF;
END$$

-- ============================================================================
-- Procedure: Classify Ground Truth
-- ============================================================================
-- Purpose: Determine if actual outcome is "Overrun" or "No_Overrun"
-- Called by: Evaluation procedure
-- ============================================================================

DROP PROCEDURE IF EXISTS classify_ground_truth$$
CREATE PROCEDURE classify_ground_truth(IN p_project_id INT)
BEGIN
  DECLARE v_cost_overrun DECIMAL(10,2);
  DECLARE v_time_overrun DECIMAL(10,2);
  DECLARE v_cost_threshold DECIMAL(5,2);
  DECLARE v_time_threshold DECIMAL(5,2);
  
  -- Get thresholds from config
  SELECT CAST(config_value AS DECIMAL(5,2)) INTO v_cost_threshold
  FROM ai_evaluation_config
  WHERE config_key = 'cost_overrun_threshold';
  
  SELECT CAST(config_value AS DECIMAL(5,2)) INTO v_time_threshold
  FROM ai_evaluation_config
  WHERE config_key = 'time_overrun_threshold';
  
  -- Get actual overruns
  SELECT 
    actual_cost_overrun_percentage,
    actual_time_overrun_percentage
  INTO v_cost_overrun, v_time_overrun
  FROM construction_projects
  WHERE id = p_project_id;
  
  -- Classify cost ground truth
  UPDATE construction_projects
  SET cost_ground_truth_label = 
    CASE 
      WHEN v_cost_overrun IS NULL THEN NULL
      WHEN v_cost_overrun > v_cost_threshold THEN 'Overrun'
      ELSE 'No_Overrun'
    END
  WHERE id = p_project_id;
  
  -- Classify time ground truth
  UPDATE construction_projects
  SET time_ground_truth_label = 
    CASE 
      WHEN v_time_overrun IS NULL THEN NULL
      WHEN v_time_overrun > v_time_threshold THEN 'Overrun'
      ELSE 'No_Overrun'
    END
  WHERE id = p_project_id;
END$$

-- ============================================================================
-- Procedure: Classify Prediction (Confusion Matrix)
-- ============================================================================
-- Purpose: Classify prediction as TP, FP, TN, or FN
-- Logic:
--   - Predicted High/Medium = Positive prediction
--   - Predicted Low = Negative prediction
--   - Ground Truth "Overrun" = Positive outcome
--   - Ground Truth "No_Overrun" = Negative outcome
-- ============================================================================

DROP PROCEDURE IF EXISTS classify_prediction$$
CREATE PROCEDURE classify_prediction(IN p_project_id INT)
BEGIN
  DECLARE v_cost_pred VARCHAR(10);
  DECLARE v_time_pred VARCHAR(10);
  DECLARE v_cost_truth VARCHAR(20);
  DECLARE v_time_truth VARCHAR(20);
  DECLARE v_cost_class VARCHAR(2);
  DECLARE v_time_class VARCHAR(2);
  DECLARE v_cost_correct TINYINT(1);
  DECLARE v_time_correct TINYINT(1);
  
  -- Get prediction and ground truth
  SELECT 
    predicted_cost_risk_level,
    predicted_time_risk_level,
    cost_ground_truth_label,
    time_ground_truth_label
  INTO v_cost_pred, v_time_pred, v_cost_truth, v_time_truth
  FROM construction_projects
  WHERE id = p_project_id;
  
  -- Classify cost prediction
  IF v_cost_pred IS NOT NULL AND v_cost_truth IS NOT NULL THEN
    SET v_cost_class = CASE
      -- True Positive: Predicted High/Medium AND Actual Overrun
      WHEN v_cost_pred IN ('High', 'Medium') AND v_cost_truth = 'Overrun' THEN 'TP'
      -- False Positive: Predicted High/Medium BUT No Overrun
      WHEN v_cost_pred IN ('High', 'Medium') AND v_cost_truth = 'No_Overrun' THEN 'FP'
      -- True Negative: Predicted Low AND No Overrun
      WHEN v_cost_pred = 'Low' AND v_cost_truth = 'No_Overrun' THEN 'TN'
      -- False Negative: Predicted Low BUT Actual Overrun
      WHEN v_cost_pred = 'Low' AND v_cost_truth = 'Overrun' THEN 'FN'
      ELSE NULL
    END;
    
    SET v_cost_correct = CASE
      WHEN v_cost_class IN ('TP', 'TN') THEN 1
      WHEN v_cost_class IN ('FP', 'FN') THEN 0
      ELSE NULL
    END;
  END IF;
  
  -- Classify time prediction
  IF v_time_pred IS NOT NULL AND v_time_truth IS NOT NULL THEN
    SET v_time_class = CASE
      -- True Positive: Predicted High/Medium AND Actual Overrun
      WHEN v_time_pred IN ('High', 'Medium') AND v_time_truth = 'Overrun' THEN 'TP'
      -- False Positive: Predicted High/Medium BUT No Overrun
      WHEN v_time_pred IN ('High', 'Medium') AND v_time_truth = 'No_Overrun' THEN 'FP'
      -- True Negative: Predicted Low AND No Overrun
      WHEN v_time_pred = 'Low' AND v_time_truth = 'No_Overrun' THEN 'TN'
      -- False Negative: Predicted Low BUT Actual Overrun
      WHEN v_time_pred = 'Low' AND v_time_truth = 'Overrun' THEN 'FN'
      ELSE NULL
    END;
    
    SET v_time_correct = CASE
      WHEN v_time_class IN ('TP', 'TN') THEN 1
      WHEN v_time_class IN ('FP', 'FN') THEN 0
      ELSE NULL
    END;
  END IF;
  
  -- Update project with classifications
  UPDATE construction_projects
  SET 
    cost_prediction_classification = v_cost_class,
    time_prediction_classification = v_time_class,
    cost_prediction_correct = v_cost_correct,
    time_prediction_correct = v_time_correct
  WHERE id = p_project_id;
END$$

-- ============================================================================
-- Procedure: Evaluate Project
-- ============================================================================
-- Purpose: Complete evaluation workflow for a single project
-- Called by: Trigger on project completion or manually
-- ============================================================================

DROP PROCEDURE IF EXISTS evaluate_project$$
CREATE PROCEDURE evaluate_project(IN p_project_id INT)
BEGIN
  DECLARE v_has_predictions TINYINT(1) DEFAULT 0;
  DECLARE v_already_evaluated TINYINT(1) DEFAULT 0;
  
  -- Check if project has predictions
  SELECT COUNT(*) INTO v_has_predictions
  FROM construction_projects
  WHERE id = p_project_id
  AND predicted_cost_risk_level IS NOT NULL
  AND predicted_time_risk_level IS NOT NULL;
  
  -- Check if already evaluated
  SELECT COUNT(*) INTO v_already_evaluated
  FROM construction_projects
  WHERE id = p_project_id
  AND evaluation_completed_at IS NOT NULL;
  
  -- Only evaluate if has predictions and not already evaluated
  IF v_has_predictions = 1 AND v_already_evaluated = 0 THEN
    -- Step 1: Calculate actual cost overrun
    CALL calculate_actual_cost_overrun(p_project_id);
    
    -- Step 2: Classify ground truth
    CALL classify_ground_truth(p_project_id);
    
    -- Step 3: Classify predictions
    CALL classify_prediction(p_project_id);
    
    -- Step 4: Mark evaluation as complete
    UPDATE construction_projects
    SET evaluation_completed_at = NOW()
    WHERE id = p_project_id;
    
    -- Step 5: Log the event
    INSERT INTO ai_prediction_audit (project_id, event_type, event_data)
    SELECT 
      p_project_id,
      'evaluation_completed',
      JSON_OBJECT(
        'cost_classification', cost_prediction_classification,
        'time_classification', time_prediction_classification,
        'cost_correct', cost_prediction_correct,
        'time_correct', time_prediction_correct,
        'cost_overrun', actual_cost_overrun_percentage,
        'time_overrun', actual_time_overrun_percentage
      )
    FROM construction_projects
    WHERE id = p_project_id;
  END IF;
END$$

-- ============================================================================
-- Procedure: Calculate Aggregate Metrics
-- ============================================================================
-- Purpose: Calculate accuracy, precision, recall, F1 for all completed projects
-- Called by: Admin dashboard or scheduled job
-- ============================================================================

DROP PROCEDURE IF EXISTS calculate_aggregate_metrics$$
CREATE PROCEDURE calculate_aggregate_metrics()
BEGIN
  DECLARE v_cost_tp, v_cost_fp, v_cost_tn, v_cost_fn INT;
  DECLARE v_time_tp, v_time_fp, v_time_tn, v_time_fn INT;
  DECLARE v_cost_accuracy, v_cost_precision, v_cost_recall, v_cost_f1 DECIMAL(5,4);
  DECLARE v_time_accuracy, v_time_precision, v_time_recall, v_time_f1 DECIMAL(5,4);
  DECLARE v_total_projects, v_evaluated_projects INT;
  DECLARE v_model_version VARCHAR(50);
  DECLARE v_cost_threshold, v_time_threshold DECIMAL(5,2);
  
  -- Get current model version and thresholds
  SELECT config_value INTO v_model_version
  FROM ai_evaluation_config WHERE config_key = 'current_model_version';
  
  SELECT CAST(config_value AS DECIMAL(5,2)) INTO v_cost_threshold
  FROM ai_evaluation_config WHERE config_key = 'cost_overrun_threshold';
  
  SELECT CAST(config_value AS DECIMAL(5,2)) INTO v_time_threshold
  FROM ai_evaluation_config WHERE config_key = 'time_overrun_threshold';
  
  -- Count total and evaluated projects
  SELECT COUNT(*) INTO v_total_projects
  FROM construction_projects
  WHERE status = 'completed';
  
  SELECT COUNT(*) INTO v_evaluated_projects
  FROM construction_projects
  WHERE status = 'completed'
  AND evaluation_completed_at IS NOT NULL;
  
  -- ========== COST METRICS ==========
  
  -- Count confusion matrix for cost
  SELECT 
    SUM(CASE WHEN cost_prediction_classification = 'TP' THEN 1 ELSE 0 END),
    SUM(CASE WHEN cost_prediction_classification = 'FP' THEN 1 ELSE 0 END),
    SUM(CASE WHEN cost_prediction_classification = 'TN' THEN 1 ELSE 0 END),
    SUM(CASE WHEN cost_prediction_classification = 'FN' THEN 1 ELSE 0 END)
  INTO v_cost_tp, v_cost_fp, v_cost_tn, v_cost_fn
  FROM construction_projects
  WHERE status = 'completed'
  AND evaluation_completed_at IS NOT NULL;
  
  -- Calculate cost metrics
  IF (v_cost_tp + v_cost_fp + v_cost_tn + v_cost_fn) > 0 THEN
    SET v_cost_accuracy = (v_cost_tp + v_cost_tn) / 
                          (v_cost_tp + v_cost_fp + v_cost_tn + v_cost_fn);
  END IF;
  
  IF (v_cost_tp + v_cost_fp) > 0 THEN
    SET v_cost_precision = v_cost_tp / (v_cost_tp + v_cost_fp);
  END IF;
  
  IF (v_cost_tp + v_cost_fn) > 0 THEN
    SET v_cost_recall = v_cost_tp / (v_cost_tp + v_cost_fn);
  END IF;
  
  IF v_cost_precision IS NOT NULL AND v_cost_recall IS NOT NULL 
     AND (v_cost_precision + v_cost_recall) > 0 THEN
    SET v_cost_f1 = 2 * (v_cost_precision * v_cost_recall) / 
                    (v_cost_precision + v_cost_recall);
  END IF;
  
  -- ========== TIME METRICS ==========
  
  -- Count confusion matrix for time
  SELECT 
    SUM(CASE WHEN time_prediction_classification = 'TP' THEN 1 ELSE 0 END),
    SUM(CASE WHEN time_prediction_classification = 'FP' THEN 1 ELSE 0 END),
    SUM(CASE WHEN time_prediction_classification = 'TN' THEN 1 ELSE 0 END),
    SUM(CASE WHEN time_prediction_classification = 'FN' THEN 1 ELSE 0 END)
  INTO v_time_tp, v_time_fp, v_time_tn, v_time_fn
  FROM construction_projects
  WHERE status = 'completed'
  AND evaluation_completed_at IS NOT NULL;
  
  -- Calculate time metrics
  IF (v_time_tp + v_time_fp + v_time_tn + v_time_fn) > 0 THEN
    SET v_time_accuracy = (v_time_tp + v_time_tn) / 
                          (v_time_tp + v_time_fp + v_time_tn + v_time_fn);
  END IF;
  
  IF (v_time_tp + v_time_fp) > 0 THEN
    SET v_time_precision = v_time_tp / (v_time_tp + v_time_fp);
  END IF;
  
  IF (v_time_tp + v_time_fn) > 0 THEN
    SET v_time_recall = v_time_tp / (v_time_tp + v_time_fn);
  END IF;
  
  IF v_time_precision IS NOT NULL AND v_time_recall IS NOT NULL 
     AND (v_time_precision + v_time_recall) > 0 THEN
    SET v_time_f1 = 2 * (v_time_precision * v_time_recall) / 
                    (v_time_precision + v_time_recall);
  END IF;
  
  -- ========== SAVE METRICS ==========
  
  -- Insert or update cost metrics
  INSERT INTO ai_evaluation_metrics (
    evaluation_date, metric_type, 
    true_positives, false_positives, true_negatives, false_negatives,
    accuracy, precision_score, recall_score, f1_score,
    total_projects, evaluated_projects,
    model_version, threshold_used
  ) VALUES (
    CURDATE(), 'cost',
    v_cost_tp, v_cost_fp, v_cost_tn, v_cost_fn,
    v_cost_accuracy, v_cost_precision, v_cost_recall, v_cost_f1,
    v_total_projects, v_evaluated_projects,
    v_model_version, v_cost_threshold
  )
  ON DUPLICATE KEY UPDATE
    true_positives = v_cost_tp,
    false_positives = v_cost_fp,
    true_negatives = v_cost_tn,
    false_negatives = v_cost_fn,
    accuracy = v_cost_accuracy,
    precision_score = v_cost_precision,
    recall_score = v_cost_recall,
    f1_score = v_cost_f1,
    total_projects = v_total_projects,
    evaluated_projects = v_evaluated_projects;
  
  -- Insert or update time metrics
  INSERT INTO ai_evaluation_metrics (
    evaluation_date, metric_type,
    true_positives, false_positives, true_negatives, false_negatives,
    accuracy, precision_score, recall_score, f1_score,
    total_projects, evaluated_projects,
    model_version, threshold_used
  ) VALUES (
    CURDATE(), 'time',
    v_time_tp, v_time_fp, v_time_tn, v_time_fn,
    v_time_accuracy, v_time_precision, v_time_recall, v_time_f1,
    v_total_projects, v_evaluated_projects,
    v_model_version, v_time_threshold
  )
  ON DUPLICATE KEY UPDATE
    true_positives = v_time_tp,
    false_positives = v_time_fp,
    true_negatives = v_time_tn,
    false_negatives = v_time_fn,
    accuracy = v_time_accuracy,
    precision_score = v_time_precision,
    recall_score = v_time_recall,
    f1_score = v_time_f1,
    total_projects = v_total_projects,
    evaluated_projects = v_evaluated_projects;
    
  -- Return results
  SELECT 
    'cost' as metric_type,
    v_cost_tp as TP, v_cost_fp as FP, v_cost_tn as TN, v_cost_fn as FN,
    v_cost_accuracy as accuracy,
    v_cost_precision as precision_val,
    v_cost_recall as recall_val,
    v_cost_f1 as f1_score
  UNION ALL
  SELECT 
    'time' as metric_type,
    v_time_tp as TP, v_time_fp as FP, v_time_tn as TN, v_time_fn as FN,
    v_time_accuracy as accuracy,
    v_time_precision as precision_val,
    v_time_recall as recall_val,
    v_time_f1 as f1_score;
END$$

-- ============================================================================
-- TRIGGERS
-- ============================================================================

-- ============================================================================
-- Trigger: Lock Predictions When Work Begins
-- ============================================================================
-- Purpose: Prevent modification of predictions after actual work starts
-- ============================================================================

DROP TRIGGER IF EXISTS lock_predictions_on_work_start$$
CREATE TRIGGER lock_predictions_on_work_start
BEFORE UPDATE ON construction_projects
FOR EACH ROW
BEGIN
  -- Lock predictions when actual_start_date is set for the first time
  IF NEW.actual_start_date IS NOT NULL 
     AND OLD.actual_start_date IS NULL 
     AND OLD.predicted_cost_risk_level IS NOT NULL THEN
    SET NEW.predictions_locked = 1;
    
    -- Log the lock event
    INSERT INTO ai_prediction_audit (project_id, event_type, event_data)
    VALUES (
      NEW.id,
      'prediction_locked',
      JSON_OBJECT('locked_at', NOW())
    );
  END IF;
  
  -- Prevent modification of locked predictions
  IF OLD.predictions_locked = 1 THEN
    SET NEW.predicted_cost_risk_level = OLD.predicted_cost_risk_level;
    SET NEW.predicted_cost_probability = OLD.predicted_cost_probability;
    SET NEW.predicted_time_risk_level = OLD.predicted_time_risk_level;
    SET NEW.predicted_time_probability = OLD.predicted_time_probability;
    SET NEW.prediction_generated_at = OLD.prediction_generated_at;
    SET NEW.model_version = OLD.model_version;
  END IF;
END$$

-- ============================================================================
-- Trigger: Auto-Evaluate on Project Completion
-- ============================================================================
-- Purpose: Automatically evaluate predictions when project is completed
-- ============================================================================

DROP TRIGGER IF EXISTS auto_evaluate_on_completion$$
CREATE TRIGGER auto_evaluate_on_completion
AFTER UPDATE ON construction_projects
FOR EACH ROW
BEGIN
  DECLARE v_auto_eval_enabled INT;
  
  -- Check if auto-evaluation is enabled
  SELECT CAST(config_value AS UNSIGNED) INTO v_auto_eval_enabled
  FROM ai_evaluation_config
  WHERE config_key = 'auto_evaluation_enabled';
  
  -- Trigger evaluation when status changes to 'completed'
  IF NEW.status = 'completed' 
     AND OLD.status != 'completed'
     AND v_auto_eval_enabled = 1 THEN
    CALL evaluate_project(NEW.id);
  END IF;
END$$

DELIMITER ;

-- ============================================================================
-- VIEWS FOR EASY QUERYING
-- ============================================================================

-- View: AI Prediction Performance Summary
CREATE OR REPLACE VIEW v_ai_prediction_performance AS
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
  
  -- Classifications
  cp.cost_prediction_classification,
  cp.time_prediction_classification,
  cp.cost_prediction_correct,
  cp.time_prediction_correct,
  
  -- Metadata
  cp.evaluation_completed_at,
  cp.predictions_locked,
  
  -- Calculated fields
  CASE 
    WHEN cp.cost_prediction_correct = 1 THEN 'Correct'
    WHEN cp.cost_prediction_correct = 0 THEN 'Incorrect'
    ELSE 'Not Evaluated'
  END as cost_prediction_status,
  
  CASE 
    WHEN cp.time_prediction_correct = 1 THEN 'Correct'
    WHEN cp.time_prediction_correct = 0 THEN 'Incorrect'
    ELSE 'Not Evaluated'
  END as time_prediction_status
  
FROM construction_projects cp
WHERE cp.predicted_cost_risk_level IS NOT NULL
OR cp.predicted_time_risk_level IS NOT NULL;

-- View: Latest Evaluation Metrics
CREATE OR REPLACE VIEW v_latest_evaluation_metrics AS
SELECT 
  metric_type,
  true_positives as TP,
  false_positives as FP,
  true_negatives as TN,
  false_negatives as FN,
  ROUND(accuracy * 100, 2) as accuracy_pct,
  ROUND(precision_score * 100, 2) as precision_pct,
  ROUND(recall_score * 100, 2) as recall_pct,
  ROUND(f1_score * 100, 2) as f1_score_pct,
  total_projects,
  evaluated_projects,
  model_version,
  threshold_used,
  evaluation_date
FROM ai_evaluation_metrics
WHERE evaluation_date = (
  SELECT MAX(evaluation_date) 
  FROM ai_evaluation_metrics
);

-- View: Confusion Matrix Summary
CREATE OR REPLACE VIEW v_confusion_matrix_summary AS
SELECT 
  'Cost Predictions' as prediction_type,
  SUM(CASE WHEN cost_prediction_classification = 'TP' THEN 1 ELSE 0 END) as true_positives,
  SUM(CASE WHEN cost_prediction_classification = 'FP' THEN 1 ELSE 0 END) as false_positives,
  SUM(CASE WHEN cost_prediction_classification = 'TN' THEN 1 ELSE 0 END) as true_negatives,
  SUM(CASE WHEN cost_prediction_classification = 'FN' THEN 1 ELSE 0 END) as false_negatives,
  COUNT(*) as total_evaluated
FROM construction_projects
WHERE status = 'completed'
AND evaluation_completed_at IS NOT NULL

UNION ALL

SELECT 
  'Time Predictions' as prediction_type,
  SUM(CASE WHEN time_prediction_classification = 'TP' THEN 1 ELSE 0 END) as true_positives,
  SUM(CASE WHEN time_prediction_classification = 'FP' THEN 1 ELSE 0 END) as false_positives,
  SUM(CASE WHEN time_prediction_classification = 'TN' THEN 1 ELSE 0 END) as true_negatives,
  SUM(CASE WHEN time_prediction_classification = 'FN' THEN 1 ELSE 0 END) as false_negatives,
  COUNT(*) as total_evaluated
FROM construction_projects
WHERE status = 'completed'
AND evaluation_completed_at IS NOT NULL;

-- ============================================================================
-- SAMPLE QUERIES FOR TESTING
-- ============================================================================

-- Query 1: Check configuration
-- SELECT * FROM ai_evaluation_config;

-- Query 2: View all predictions with evaluation status
-- SELECT * FROM v_ai_prediction_performance ORDER BY project_id DESC LIMIT 10;

-- Query 3: View latest metrics
-- SELECT * FROM v_latest_evaluation_metrics;

-- Query 4: View confusion matrix
-- SELECT * FROM v_confusion_matrix_summary;

-- Query 5: Calculate metrics manually
-- CALL calculate_aggregate_metrics();

-- Query 6: Evaluate a specific project
-- CALL evaluate_project(1);

-- Query 7: Check prediction audit log
-- SELECT * FROM ai_prediction_audit ORDER BY created_at DESC LIMIT 20;

-- ============================================================================
-- END OF AI SELF-EVALUATION FRAMEWORK
-- ============================================================================
