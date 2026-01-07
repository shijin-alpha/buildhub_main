-- Add technical_details column to house_plans table
-- This column will store comprehensive technical specifications for house plans

ALTER TABLE house_plans 
ADD COLUMN technical_details JSON NULL 
COMMENT 'Stores comprehensive technical specifications including construction details, materials, MEP systems, etc.'
AFTER plan_data;

-- Update the table to ensure proper indexing
ALTER TABLE house_plans 
ADD INDEX idx_technical_details_status (status, technical_details(1));

-- Verify the column was added successfully
DESCRIBE house_plans;