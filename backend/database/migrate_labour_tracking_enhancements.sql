-- Migration script to enhance labour tracking with new fields
-- Run this script to add new columns to existing daily_labour_tracking table

-- Add new columns to daily_labour_tracking table
ALTER TABLE daily_labour_tracking 
ADD COLUMN IF NOT EXISTS hourly_rate DECIMAL(8,2) DEFAULT 0.00 AFTER absent_count,
ADD COLUMN IF NOT EXISTS total_wages DECIMAL(10,2) DEFAULT 0.00 AFTER hourly_rate,
ADD COLUMN IF NOT EXISTS productivity_rating INT DEFAULT 5 CHECK (productivity_rating >= 1 AND productivity_rating <= 5) AFTER total_wages,
ADD COLUMN IF NOT EXISTS safety_compliance ENUM('excellent', 'good', 'average', 'poor', 'needs_improvement') DEFAULT 'good' AFTER productivity_rating;

-- Update worker_type enum to include new worker types
ALTER TABLE daily_labour_tracking 
MODIFY COLUMN worker_type ENUM(
    'Mason', 'Helper', 'Electrician', 'Plumber', 'Carpenter', 'Painter', 
    'Supervisor', 'Welder', 'Crane Operator', 'Excavator Operator', 
    'Steel Fixer', 'Tile Worker', 'Plasterer', 'Roofer', 'Security Guard',
    'Site Engineer', 'Quality Inspector', 'Safety Officer', 'Other'
) NOT NULL;

-- Add indexes for new columns
CREATE INDEX IF NOT EXISTS idx_labour_productivity ON daily_labour_tracking(productivity_rating);
CREATE INDEX IF NOT EXISTS idx_labour_safety ON daily_labour_tracking(safety_compliance);
CREATE INDEX IF NOT EXISTS idx_labour_wages ON daily_labour_tracking(total_wages);

-- Update existing records with default values
UPDATE daily_labour_tracking 
SET 
    hourly_rate = CASE 
        WHEN worker_type = 'Mason' THEN 500.00
        WHEN worker_type = 'Electrician' THEN 600.00
        WHEN worker_type = 'Plumber' THEN 550.00
        WHEN worker_type = 'Carpenter' THEN 450.00
        WHEN worker_type = 'Painter' THEN 400.00
        WHEN worker_type = 'Supervisor' THEN 800.00
        WHEN worker_type = 'Site Engineer' THEN 1000.00
        WHEN worker_type = 'Safety Officer' THEN 700.00
        ELSE 300.00
    END,
    productivity_rating = 5,
    safety_compliance = 'good'
WHERE hourly_rate IS NULL OR hourly_rate = 0;

-- Calculate total wages for existing records
UPDATE daily_labour_tracking 
SET total_wages = worker_count * ((hours_worked * hourly_rate) + (overtime_hours * hourly_rate * 1.5))
WHERE total_wages IS NULL OR total_wages = 0;

-- Verify the migration
SELECT 
    'Migration completed successfully' as status,
    COUNT(*) as total_records,
    COUNT(CASE WHEN hourly_rate > 0 THEN 1 END) as records_with_hourly_rate,
    COUNT(CASE WHEN total_wages > 0 THEN 1 END) as records_with_total_wages,
    COUNT(CASE WHEN productivity_rating IS NOT NULL THEN 1 END) as records_with_productivity,
    COUNT(CASE WHEN safety_compliance IS NOT NULL THEN 1 END) as records_with_safety
FROM daily_labour_tracking;