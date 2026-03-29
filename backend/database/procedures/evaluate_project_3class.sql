-- ============================================================================
-- 3-Class Evaluation Procedures
-- ============================================================================
-- Purpose: Evaluate AI predictions using 3-class classification (Low/Medium/High)
-- Replaces binary evaluation with proper multi-class confusion matrix
-- Includes Kerala district and construction_start_month context weighting
-- ============================================================================

DELIMITER $

-- Drop existing procedures if they exist
DROP PROCEDURE IF EXISTS determine_ground_truth_3class$
DROP PROCEDURE IF EXISTS classify_predictions_3class$
DROP PROCEDURE IF EXISTS evaluate_project_predictions_3class$
DROP PROCEDURE IF EXISTS update_aggregated_metrics_3class$

-- ============================================================================
-- Procedure: Determine Ground Truth Labels (3-Class)
-- ============================================================================
CREATE PROCEDURE determine_ground_truth_3class(IN p_project_id INT)
BEGIN
  DECLARE v_cost_overrun DECIMAL(10,2);
  DECLARE v_time_overrun DECIMAL(10,2);
  DECLARE v_cost_medium_threshold DECIMAL(5,2);
  DECLARE v_cost_high_threshold DECIMAL(5,2);
  DECLARE v_time_medium_threshold DECIMAL(5,2);
  DECLARE v_time_high_threshold DECIMAL(5,2);
  DECLARE v_cost_label VARCHAR(10);
  DECLARE v_time_label VARCHAR(10);
  DECLARE v_kerala_district VARCHAR(50);
  DECLARE v_start_month TINYINT;
  DECLARE v_district_cost_adj DECIMAL(5,2) DEFAULT 0;
  DECLARE v_district_time_adj DECIMAL(5,2) DEFAULT 0;
  DECLARE v_monsoon_adj DECIMAL(5,2) DEFAULT 0;
  
  -- Get thresholds from config
  SELECT 
    CAST(MAX(CASE WHEN config_key = 'cost_medium_threshold' THEN config_value END) AS DECIMAL(5,2)),
    CAST(MAX(CASE WHEN config_key = 'cost_high_threshold' THEN config_value END) AS DECIMAL(5,2)),
    CAST(MAX(CASE WHEN config_key = 'time_medium_threshold' THEN config_value END) AS DECIMAL(5,2)),
    CAST(MAX(CASE WHEN config_key = 'time_high_threshold' THEN config_value END) AS DECIMAL(5,2))
  INTO 
    v_cost_medium_threshold,
    v_cost_high_threshold,
    v_time_medium_threshold,
    v_time_high_threshold
  FROM ai_evaluation_config
  WHERE config_key IN ('cost_medium_threshold', 'cost_high_threshold', 
                       'time_medium_threshold', 'time_high_threshold');
  
  -- Default thresholds if not configured
  IF v_cost_medium_threshold IS NULL THEN SET v_cost_medium_threshold = 5.0; END IF;
  IF v_cost_high_threshold IS NULL THEN SET v_cost_high_threshold = 15.0; END IF;
  IF v_time_medium_threshold IS NULL THEN SET v_time_medium_threshold = 5.0; END IF;
  IF v_time_high_threshold IS NULL THEN SET v_time_high_threshold = 15.0; END IF;
  
  -- Get actual overrun percentages + Kerala context from layout_requests
  SELECT 
    cp.actual_cost_overrun_percentage,
    cp.actual_time_overrun_percentage,
    lr.kerala_district,
    lr.construction_start_month
  INTO v_cost_overrun, v_time_overrun, v_kerala_district, v_start_month
  FROM construction_projects cp
  LEFT JOIN layout_requests lr ON lr.id = cp.layout_request_id
  WHERE cp.id = p_project_id;

  -- ── Kerala district threshold adjustments ──
  -- High-risk districts get slightly lower thresholds (easier to hit High)
  IF v_kerala_district IS NOT NULL THEN
    CASE LOWER(v_kerala_district)
      WHEN 'idukki'         THEN SET v_district_cost_adj = -2.0; SET v_district_time_adj = -3.0;
      WHEN 'wayanad'        THEN SET v_district_cost_adj = -1.5; SET v_district_time_adj = -2.0;
      WHEN 'alappuzha'      THEN SET v_district_cost_adj = -1.0; SET v_district_time_adj = -2.0;
      WHEN 'pathanamthitta' THEN SET v_district_cost_adj = -1.0; SET v_district_time_adj = -1.5;
      WHEN 'kottayam'       THEN SET v_district_cost_adj = -1.0; SET v_district_time_adj = -1.5;
      WHEN 'kasaragod'      THEN SET v_district_cost_adj = -0.5; SET v_district_time_adj = -1.0;
      WHEN 'ernakulam'      THEN SET v_district_cost_adj =  1.0; SET v_district_time_adj =  1.0;
      WHEN 'palakkad'       THEN SET v_district_cost_adj =  0.5; SET v_district_time_adj =  1.0;
      ELSE SET v_district_cost_adj = 0; SET v_district_time_adj = 0;
    END CASE;
  END IF;

  -- ── Monsoon exposure adjustment (SW monsoon Jun-Sep, NE monsoon Oct-Nov) ──
  IF v_start_month IS NOT NULL THEN
    -- Projects starting in peak monsoon months get lower time threshold
    IF v_start_month IN (6, 7, 8, 9) THEN
      SET v_monsoon_adj = -2.5;
    ELSEIF v_start_month IN (10, 11) THEN
      SET v_monsoon_adj = -1.5;
    ELSEIF v_start_month IN (5, 12) THEN
      SET v_monsoon_adj = -0.5;
    ELSE
      SET v_monsoon_adj = 0;
    END IF;
  END IF;

  -- Apply context adjustments to thresholds
  SET v_cost_medium_threshold = v_cost_medium_threshold + v_district_cost_adj;
  SET v_cost_high_threshold   = v_cost_high_threshold   + v_district_cost_adj;
  SET v_time_medium_threshold = v_time_medium_threshold + v_district_time_adj + v_monsoon_adj;
  SET v_time_high_threshold   = v_time_high_threshold   + v_district_time_adj + v_monsoon_adj;

  -- Clamp thresholds to sensible minimums
  IF v_cost_medium_threshold < 2.0 THEN SET v_cost_medium_threshold = 2.0; END IF;
  IF v_cost_high_threshold   < 8.0 THEN SET v_cost_high_threshold   = 8.0; END IF;
  IF v_time_medium_threshold < 2.0 THEN SET v_time_medium_threshold = 2.0; END IF;
  IF v_time_high_threshold   < 8.0 THEN SET v_time_high_threshold   = 8.0; END IF;
  
  -- Determine cost ground truth label (3-class)
  IF v_cost_overrun IS NOT NULL THEN
    IF v_cost_overrun >= v_cost_high_threshold THEN
      SET v_cost_label = 'High';
    ELSEIF v_cost_overrun >= v_cost_medium_threshold THEN
      SET v_cost_label = 'Medium';
    ELSE
      SET v_cost_label = 'Low';
    END IF;
  END IF;
  
  -- Determine time ground truth label (3-class)
  IF v_time_overrun IS NOT NULL THEN
    IF v_time_overrun >= v_time_high_threshold THEN
      SET v_time_label = 'High';
    ELSEIF v_time_overrun >= v_time_medium_threshold THEN
      SET v_time_label = 'Medium';
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
END$

-- ============================================================================
-- Procedure: Classify Predictions (3-Class)
-- ============================================================================
CREATE PROCEDURE classify_predictions_3class(IN p_project_id INT)
BEGIN
  DECLARE v_predicted_cost_risk VARCHAR(20);
  DECLARE v_predicted_time_risk VARCHAR(20);
  DECLARE v_cost_ground_truth VARCHAR(10);
  DECLARE v_time_ground_truth VARCHAR(10);
  DECLARE v_cost_correct TINYINT(1);
  DECLARE v_time_correct TINYINT(1);
  DECLARE v_kerala_district VARCHAR(50);
  DECLARE v_start_month TINYINT;
  
  -- Get prediction and ground truth data + Kerala context
  SELECT 
    cp.predicted_cost_risk_level,
    cp.predicted_time_risk_level,
    cp.cost_ground_truth_label,
    cp.time_ground_truth_label,
    lr.kerala_district,
    lr.construction_start_month
  INTO 
    v_predicted_cost_risk,
    v_predicted_time_risk,
    v_cost_ground_truth,
    v_time_ground_truth,
    v_kerala_district,
    v_start_month
  FROM construction_projects cp
  LEFT JOIN layout_requests lr ON lr.id = cp.layout_request_id
  WHERE cp.id = p_project_id;
  
  -- Classify cost prediction (exact match for 3-class)
  IF v_predicted_cost_risk IS NOT NULL AND v_cost_ground_truth IS NOT NULL THEN
    IF v_predicted_cost_risk = v_cost_ground_truth THEN
      SET v_cost_correct = 1;
    ELSE
      SET v_cost_correct = 0;
    END IF;
  END IF;
  
  -- Classify time prediction (exact match for 3-class)
  IF v_predicted_time_risk IS NOT NULL AND v_time_ground_truth IS NOT NULL THEN
    IF v_predicted_time_risk = v_time_ground_truth THEN
      SET v_time_correct = 1;
    ELSE
      SET v_time_correct = 0;
    END IF;
  END IF;
  
  -- Update classification results
  UPDATE construction_projects
  SET 
    cost_prediction_correct = v_cost_correct,
    time_prediction_correct = v_time_correct,
    evaluation_completed_at = NOW()
  WHERE id = p_project_id;
  
  -- Log to audit trail (includes Kerala context for traceability)
  INSERT INTO ai_prediction_audit (
    project_id,
    event_type,
    event_data,
    created_at
  ) VALUES (
    p_project_id,
    'evaluation_completed',
    JSON_OBJECT(
      'cost_predicted', v_predicted_cost_risk,
      'cost_actual', v_cost_ground_truth,
      'cost_correct', v_cost_correct,
      'time_predicted', v_predicted_time_risk,
      'time_actual', v_time_ground_truth,
      'time_correct', v_time_correct,
      'kerala_district', IFNULL(v_kerala_district, 'unknown'),
      'construction_start_month', IFNULL(v_start_month, 0)
    ),
    NOW()
  );
END$

DELIMITER ;
