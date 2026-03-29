-- ============================================================================
-- PREDICTION COPY TRIGGER
-- ============================================================================
-- Purpose: Automatically copy AI predictions from estimate to project
-- When: After a new construction_projects record is inserted
-- Why: Predictions are generated during estimate phase (before project exists)
--      This trigger ensures predictions are preserved when project is created
-- ============================================================================

DELIMITER $$

-- Drop existing trigger if it exists
DROP TRIGGER IF EXISTS copy_predictions_to_project$$

-- Create trigger to copy predictions when project is created
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
    
    -- Only proceed if estimate_id is provided
    IF NEW.estimate_id IS NOT NULL THEN
        -- Get predictions from estimate
        SELECT 
            predicted_cost_risk_level,
            predicted_cost_probability,
            predicted_time_risk_level,
            predicted_time_probability,
            prediction_generated_at,
            model_version
        INTO 
            v_cost_risk, 
            v_cost_prob, 
            v_time_risk, 
            v_time_prob, 
            v_pred_time, 
            v_model_ver
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
END$$

DELIMITER ;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Check if trigger was created successfully
SELECT 
    TRIGGER_NAME,
    EVENT_MANIPULATION,
    EVENT_OBJECT_TABLE,
    ACTION_TIMING,
    ACTION_STATEMENT
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
  AND TRIGGER_NAME = 'copy_predictions_to_project';

-- ============================================================================
-- USAGE NOTES
-- ============================================================================
-- 
-- This trigger works in conjunction with:
-- 1. save_estimate_prediction.php - Saves predictions to estimates table
-- 2. RiskAssessmentPreview.jsx - Frontend component that calls the API
-- 3. HomeownerRequestWizard.jsx - Creates project with estimate_id
--
-- Workflow:
-- 1. Homeowner fills form → Risk assessment runs
-- 2. Predictions saved to contractor_send_estimates table
-- 3. Homeowner submits → Project created with estimate_id
-- 4. THIS TRIGGER fires → Copies predictions to construction_projects
-- 5. Predictions now available for evaluation when project completes
--
-- ============================================================================
