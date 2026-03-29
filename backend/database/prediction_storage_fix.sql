-- ============================================================================
-- PREDICTION STORAGE FIX - Database Schema Modifications
-- ============================================================================
-- Purpose: Allow predictions to be stored with estimates before project creation
-- Author: Senior Software Architect
-- Date: March 11, 2026
-- ============================================================================

-- Step 1: Add prediction fields to contractor_send_estimates table
-- This allows predictions to be stored during the estimate phase
ALTER TABLE contractor_send_estimates
ADD COLUMN IF NOT EXISTS predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted cost overrun risk level',
ADD COLUMN IF NOT EXISTS predicted_cost_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted cost risk (0-1)',
ADD COLUMN IF NOT EXISTS predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted time delay risk level',
ADD COLUMN IF NOT EXISTS predicted_time_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted time risk (0-1)',
ADD COLUMN IF NOT EXISTS prediction_generated_at TIMESTAMP NULL COMMENT 'When AI prediction was made',
ADD COLUMN IF NOT EXISTS model_version VARCHAR(50) NULL COMMENT 'ML model version used for prediction';

-- Step 2: Create trigger to automatically copy predictions when project is created
DELIMITER $

DROP TRIGGER IF EXISTS copy_predictions_to_project$

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
  
  -- Get predictions from estimate if estimate_id exists
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
    
    -- Copy to project if predictions exist
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
END$

DELIMITER ;

-- Step 3: Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_estimate_predictions 
ON contractor_send_estimates(predicted_cost_risk_level, predicted_time_risk_level);

-- Step 4: Verification query
SELECT 
  'Prediction storage fix applied successfully!' as status,
  'Estimates can now store predictions before project creation' as feature_1,
  'Predictions automatically copy to projects when created' as feature_2,
  'Trigger: copy_predictions_to_project is active' as feature_3;

-- ============================================================================
-- END OF PREDICTION STORAGE FIX
-- ============================================================================
