-- ============================================================================
-- Add AI Prediction Columns to layout_requests Table
-- ============================================================================
-- Purpose: Store AI predictions at the homeowner request stage
-- This is the PRIMARY storage location for predictions before estimate exists
-- ============================================================================

ALTER TABLE layout_requests
ADD COLUMN predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL 
  COMMENT 'AI predicted cost overrun risk level',
ADD COLUMN predicted_cost_probability DECIMAL(5,4) NULL 
  COMMENT 'Probability of predicted cost risk (0-1)',
ADD COLUMN predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL 
  COMMENT 'AI predicted time delay risk level',
ADD COLUMN predicted_time_probability DECIMAL(5,4) NULL 
  COMMENT 'Probability of predicted time risk (0-1)',
ADD COLUMN prediction_generated_at TIMESTAMP NULL 
  COMMENT 'When AI prediction was made',
ADD COLUMN model_version VARCHAR(50) NULL 
  COMMENT 'ML model version used for prediction',
ADD COLUMN prediction_features JSON NULL 
  COMMENT 'Features used for prediction (for retraining)',
ADD COLUMN prediction_explanation JSON NULL 
  COMMENT 'Top risk factors and explanations';

-- Add index for querying predictions
CREATE INDEX idx_layout_predictions 
ON layout_requests(predicted_cost_risk_level, predicted_time_risk_level);

-- Verify columns added
SELECT 
  COLUMN_NAME, 
  DATA_TYPE, 
  IS_NULLABLE, 
  COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'layout_requests'
  AND COLUMN_NAME LIKE 'predict%'
ORDER BY ORDINAL_POSITION;
