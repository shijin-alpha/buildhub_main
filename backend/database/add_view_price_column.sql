-- Add view_price column to designs table
-- This column stores the price that homeowners need to pay to view the layout

ALTER TABLE designs 
ADD COLUMN view_price DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Price for homeowners to view this layout';

-- Update existing records to have 0 price (free)
UPDATE designs SET view_price = 0.00 WHERE view_price IS NULL;





