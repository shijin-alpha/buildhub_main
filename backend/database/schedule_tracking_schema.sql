-- ============================================================================
-- SCHEDULE TRACKING ENHANCEMENT SCHEMA
-- ============================================================================
-- Purpose: Add planned vs actual schedule tracking to construction_projects
-- Backward Compatible: All fields are nullable, existing records unaffected
-- ============================================================================

-- Add schedule tracking fields to construction_projects table
ALTER TABLE `construction_projects`
ADD COLUMN `planned_start_date` DATE NULL DEFAULT NULL COMMENT 'Contractor-entered planned start date (locked after actual_start_date)',
ADD COLUMN `planned_end_date` DATE NULL DEFAULT NULL COMMENT 'Contractor-entered planned completion date (locked after actual_start_date)',
ADD COLUMN `actual_start_date` DATE NULL DEFAULT NULL COMMENT 'Actual project start date (locks planned dates)',
ADD COLUMN `actual_end_date` DATE NULL DEFAULT NULL COMMENT 'Actual project completion date',
ADD COLUMN `actual_time_overrun_percentage` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Calculated: ((actual_duration - planned_duration) / planned_duration) * 100',
ADD COLUMN `schedule_locked` TINYINT(1) DEFAULT 0 COMMENT 'Flag indicating if planned dates are locked';

-- Create index for schedule queries
CREATE INDEX `idx_schedule_dates` ON `construction_projects` (
    `planned_start_date`, 
    `planned_end_date`, 
    `actual_start_date`, 
    `actual_end_date`
);

-- Create audit log table for schedule changes
CREATE TABLE IF NOT EXISTS `schedule_change_audit` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `changed_by_user_id` INT(11) NOT NULL,
  `changed_by_role` ENUM('contractor', 'admin') NOT NULL,
  `field_changed` VARCHAR(50) NOT NULL,
  `old_value` DATE NULL,
  `new_value` DATE NULL,
  `change_reason` TEXT NULL,
  `changed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_project_audit` (`project_id`),
  INDEX `idx_change_date` (`changed_at`),
  FOREIGN KEY (`project_id`) REFERENCES `construction_projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Audit trail for schedule date changes';

-- ============================================================================
-- STORED PROCEDURE: Calculate and Update Time Overrun
-- ============================================================================
-- Automatically calculates time overrun when actual_end_date is set
-- Only executes if all required dates exist
-- ============================================================================

DELIMITER $$

CREATE PROCEDURE `calculate_time_overrun`(IN p_project_id INT)
BEGIN
    DECLARE v_planned_start DATE;
    DECLARE v_planned_end DATE;
    DECLARE v_actual_start DATE;
    DECLARE v_actual_end DATE;
    DECLARE v_planned_duration INT;
    DECLARE v_actual_duration INT;
    DECLARE v_overrun_percentage DECIMAL(10,2);
    
    -- Get all date values
    SELECT 
        planned_start_date,
        planned_end_date,
        actual_start_date,
        actual_end_date
    INTO 
        v_planned_start,
        v_planned_end,
        v_actual_start,
        v_actual_end
    FROM construction_projects
    WHERE id = p_project_id;
    
    -- Only calculate if all required dates exist
    IF v_planned_start IS NOT NULL 
       AND v_planned_end IS NOT NULL 
       AND v_actual_start IS NOT NULL 
       AND v_actual_end IS NOT NULL THEN
        
        -- Calculate durations in days
        SET v_planned_duration = DATEDIFF(v_planned_end, v_planned_start);
        SET v_actual_duration = DATEDIFF(v_actual_end, v_actual_start);
        
        -- Prevent division by zero
        IF v_planned_duration > 0 THEN
            -- Calculate overrun percentage
            SET v_overrun_percentage = ((v_actual_duration - v_planned_duration) / v_planned_duration) * 100;
            
            -- Update the project record
            UPDATE construction_projects
            SET actual_time_overrun_percentage = v_overrun_percentage
            WHERE id = p_project_id;
        END IF;
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- TRIGGER: Lock Planned Dates When Actual Start Date is Set
-- ============================================================================
-- Prevents modification of planned dates after actual work begins
-- ============================================================================

DELIMITER $$

CREATE TRIGGER `lock_planned_dates_on_actual_start`
BEFORE UPDATE ON `construction_projects`
FOR EACH ROW
BEGIN
    -- If actual_start_date is being set for the first time
    IF NEW.actual_start_date IS NOT NULL AND OLD.actual_start_date IS NULL THEN
        SET NEW.schedule_locked = 1;
    END IF;
    
    -- Prevent changes to planned dates if schedule is locked
    IF OLD.schedule_locked = 1 THEN
        IF NEW.planned_start_date != OLD.planned_start_date OR 
           (NEW.planned_start_date IS NULL AND OLD.planned_start_date IS NOT NULL) OR
           (NEW.planned_start_date IS NOT NULL AND OLD.planned_start_date IS NULL) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot modify planned_start_date after actual work has begun';
        END IF;
        
        IF NEW.planned_end_date != OLD.planned_end_date OR 
           (NEW.planned_end_date IS NULL AND OLD.planned_end_date IS NOT NULL) OR
           (NEW.planned_end_date IS NOT NULL AND OLD.planned_end_date IS NULL) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Cannot modify planned_end_date after actual work has begun';
        END IF;
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- TRIGGER: Auto-Calculate Time Overrun on Completion
-- ============================================================================
-- Automatically calculates overrun when actual_end_date is set
-- ============================================================================

DELIMITER $$

CREATE TRIGGER `auto_calculate_overrun_on_completion`
AFTER UPDATE ON `construction_projects`
FOR EACH ROW
BEGIN
    -- If actual_end_date is being set and status is completed
    IF NEW.actual_end_date IS NOT NULL 
       AND (OLD.actual_end_date IS NULL OR OLD.actual_end_date != NEW.actual_end_date)
       AND NEW.status = 'completed' THEN
        CALL calculate_time_overrun(NEW.id);
    END IF;
END$$

DELIMITER ;

-- ============================================================================
-- VIEW: Project Schedule Summary
-- ============================================================================
-- Provides easy access to schedule metrics for all projects
-- ============================================================================

CREATE OR REPLACE VIEW `project_schedule_summary` AS
SELECT 
    cp.id AS project_id,
    cp.project_name,
    cp.contractor_id,
    cp.homeowner_id,
    cp.status,
    cp.planned_start_date,
    cp.planned_end_date,
    cp.actual_start_date,
    cp.actual_end_date,
    cp.schedule_locked,
    -- Calculate planned duration in days
    CASE 
        WHEN cp.planned_start_date IS NOT NULL AND cp.planned_end_date IS NOT NULL
        THEN DATEDIFF(cp.planned_end_date, cp.planned_start_date)
        ELSE NULL
    END AS planned_duration_days,
    -- Calculate actual duration in days
    CASE 
        WHEN cp.actual_start_date IS NOT NULL AND cp.actual_end_date IS NOT NULL
        THEN DATEDIFF(cp.actual_end_date, cp.actual_start_date)
        ELSE NULL
    END AS actual_duration_days,
    -- Calculate delay in days
    CASE 
        WHEN cp.planned_end_date IS NOT NULL AND cp.actual_end_date IS NOT NULL
        THEN DATEDIFF(cp.actual_end_date, cp.planned_end_date)
        WHEN cp.planned_end_date IS NOT NULL AND cp.actual_end_date IS NULL AND cp.status != 'completed'
        THEN DATEDIFF(CURDATE(), cp.planned_end_date)
        ELSE NULL
    END AS delay_days,
    cp.actual_time_overrun_percentage,
    -- Schedule status indicator
    CASE
        WHEN cp.actual_end_date IS NOT NULL AND cp.status = 'completed' THEN 'Completed'
        WHEN cp.planned_end_date IS NOT NULL AND CURDATE() > cp.planned_end_date AND cp.status != 'completed' THEN 'Delayed'
        WHEN cp.actual_start_date IS NOT NULL THEN 'In Progress'
        WHEN cp.planned_start_date IS NOT NULL THEN 'Scheduled'
        ELSE 'Not Scheduled'
    END AS schedule_status,
    cp.created_at,
    cp.updated_at
FROM construction_projects cp;

-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Check if columns were added successfully
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'construction_projects'
  AND COLUMN_NAME IN (
      'planned_start_date', 
      'planned_end_date', 
      'actual_start_date', 
      'actual_end_date', 
      'actual_time_overrun_percentage',
      'schedule_locked'
  );

-- ============================================================================
-- NOTES
-- ============================================================================
-- 1. All new fields are nullable - existing records remain valid
-- 2. Planned dates can only be set by contractors after project approval
-- 3. Planned dates lock automatically when actual_start_date is recorded
-- 4. Time overrun calculates automatically when project completes
-- 5. Homeowners see planned dates in read-only mode
-- 6. Audit trail tracks all schedule changes
-- 7. View provides easy access to schedule metrics
-- ============================================================================
