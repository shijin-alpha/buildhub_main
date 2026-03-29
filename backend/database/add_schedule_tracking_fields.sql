-- ============================================================================
-- BACKWARD-COMPATIBLE SCHEDULE TRACKING ENHANCEMENT
-- ============================================================================
-- This migration adds planned vs actual schedule tracking to construction_projects
-- All fields are nullable to maintain backward compatibility with existing records
-- ============================================================================

-- Add schedule tracking fields to construction_projects table
ALTER TABLE `construction_projects`
ADD COLUMN `planned_start_date` DATE NULL DEFAULT NULL COMMENT 'Planned project start date (contractor-entered after approval)',
ADD COLUMN `planned_end_date` DATE NULL DEFAULT NULL COMMENT 'Planned project completion date (contractor-entered after approval)',
ADD COLUMN `actual_start_date` DATE NULL DEFAULT NULL COMMENT 'Actual project start date (auto-locks planned dates)',
ADD COLUMN `actual_end_date` DATE NULL DEFAULT NULL COMMENT 'Actual project completion date (triggers overrun calculation)',
ADD COLUMN `actual_time_overrun_percentage` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Calculated time overrun percentage',
ADD COLUMN `planned_dates_locked` TINYINT(1) DEFAULT 0 COMMENT 'Flag to prevent planned date modification after actual start';

-- Create index for efficient querying of schedule data
CREATE INDEX idx_schedule_tracking ON `construction_projects` (
    `planned_start_date`, 
    `planned_end_date`, 
    `actual_start_date`, 
    `actual_end_date`
);

-- Create index for overrun analysis
CREATE INDEX idx_time_overrun ON `construction_projects` (`actual_time_overrun_percentage`);

-- ============================================================================
-- AUDIT LOG TABLE FOR SCHEDULE CHANGES
-- ============================================================================
-- Track all schedule-related modifications for accountability
-- ============================================================================

CREATE TABLE IF NOT EXISTS `project_schedule_audit` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `changed_by_user_id` INT NOT NULL,
    `user_role` ENUM('contractor', 'homeowner', 'admin', 'system') NOT NULL,
    `field_changed` VARCHAR(50) NOT NULL,
    `old_value` DATE NULL,
    `new_value` DATE NULL,
    `change_reason` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `construction_projects`(`id`) ON DELETE CASCADE,
    INDEX idx_project_audit (`project_id`, `created_at`),
    INDEX idx_user_audit (`changed_by_user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Audit trail for all schedule tracking changes';

-- ============================================================================
-- VERIFICATION QUERY
-- ============================================================================
-- Run this to verify the migration was successful
-- ============================================================================

-- SELECT 
--     COLUMN_NAME, 
--     DATA_TYPE, 
--     IS_NULLABLE, 
--     COLUMN_DEFAULT, 
--     COLUMN_COMMENT
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = 'buildhub' 
--   AND TABLE_NAME = 'construction_projects'
--   AND COLUMN_NAME IN (
--       'planned_start_date', 
--       'planned_end_date', 
--       'actual_start_date', 
--       'actual_end_date', 
--       'actual_time_overrun_percentage',
--       'planned_dates_locked'
--   );
