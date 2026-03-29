-- Migration: Remove materials_used column from daily_progress_updates table
-- This removes the materials_used field as it's no longer needed in the progress update form

-- Check if the column exists before trying to drop it
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'daily_progress_updates' 
     AND COLUMN_NAME = 'materials_used') > 0,
    "ALTER TABLE daily_progress_updates DROP COLUMN materials_used",
    "SELECT 'materials_used column does not exist' as message"
));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Log the migration
INSERT INTO migration_log (migration_name, executed_at, description) 
VALUES ('remove_materials_used_column', NOW(), 'Removed materials_used column from daily_progress_updates table')
ON DUPLICATE KEY UPDATE executed_at = NOW();