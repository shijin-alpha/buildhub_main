-- ============================================================================
-- Add AI Prediction Columns to contractor_send_estimates Table
-- ============================================================================
-- Purpose: Store predictions when contractor creates estimate
-- Predictions are copied from layout_requests table
-- ============================================================================

ALTER TABLE contractor_send_estimates
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
  COMMENT 'Features used for prediction',
ADD COLUMN prediction_explanation JSON NULL
  COMMENT 'Top risk factors and explanations',
ADD COLUMN layout_request_id INT NULL 
  COMMENT 'Link to original layout request';

-- Add foreign key to link back to layout_request
ALTER TABLE contractor_send_estimates
ADD CONSTRAINT fk_estimate_layout_request
FOREIGN KEY (layout_request_id) REFERENCES layout_requests(id) 
ON DELETE SET NULL;

-- Add index for querying predictions
CREATE INDEX idx_estimate_predictions 
ON contractor_send_estimates(predicted_cost_risk_level, predicted_time_risk_level);

-- Verify columns added
SELECT 
  COLUMN_NAME, 
  DATA_TYPE, 
  IS_NULLABLE, 
  COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'contractor_send_estimates'
  AND COLUMN_NAME LIKE 'predict%'
ORDER BY ORDINAL_POSITION;
