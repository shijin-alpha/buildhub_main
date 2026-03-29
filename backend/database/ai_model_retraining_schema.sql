-- ============================================================================
-- AI Model Retraining Log Table
-- ============================================================================
-- Purpose: Track all model retraining events with metrics and status
-- ============================================================================

CREATE TABLE IF NOT EXISTS ai_model_retraining_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Trigger information
    trigger_type ENUM('manual', 'scheduled', 'automatic') NOT NULL COMMENT 'How retraining was triggered',
    triggered_by INT DEFAULT NULL COMMENT 'User ID if manually triggered',
    triggered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When retraining started',
    
    -- Dataset information
    dataset_rows INT NOT NULL COMMENT 'Number of rows in training dataset',
    dataset_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to dataset file',
    
    -- Model version information
    previous_version VARCHAR(20) DEFAULT NULL COMMENT 'Previous model version',
    new_version VARCHAR(20) NOT NULL COMMENT 'New model version created',
    
    -- Performance metrics
    cost_accuracy DECIMAL(5,4) DEFAULT NULL COMMENT 'Cost model accuracy',
    cost_precision DECIMAL(5,4) DEFAULT NULL COMMENT 'Cost model precision',
    cost_recall DECIMAL(5,4) DEFAULT NULL COMMENT 'Cost model recall',
    cost_f1_score DECIMAL(5,4) DEFAULT NULL COMMENT 'Cost model F1 score',
    
    time_accuracy DECIMAL(5,4) DEFAULT NULL COMMENT 'Time model accuracy',
    time_precision DECIMAL(5,4) DEFAULT NULL COMMENT 'Time model precision',
    time_recall DECIMAL(5,4) DEFAULT NULL COMMENT 'Time model recall',
    time_f1_score DECIMAL(5,4) DEFAULT NULL COMMENT 'Time model F1 score',
    
    -- Status tracking
    status ENUM('started', 'dataset_generated', 'training', 'completed', 'failed') NOT NULL DEFAULT 'started',
    error_message TEXT DEFAULT NULL COMMENT 'Error details if failed',
    completed_at TIMESTAMP NULL DEFAULT NULL COMMENT 'When retraining completed',
    duration_seconds INT DEFAULT NULL COMMENT 'Total retraining duration',
    
    -- Additional metadata
    notes TEXT DEFAULT NULL COMMENT 'Additional notes or configuration',
    
    -- Indexes
    INDEX idx_trigger_type (trigger_type),
    INDEX idx_triggered_at (triggered_at),
    INDEX idx_status (status),
    INDEX idx_new_version (new_version),
    
    -- Foreign key
    FOREIGN KEY (triggered_by) REFERENCES users(id) ON DELETE SET NULL
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Log of all ML model retraining events';

-- ============================================================================
-- View: Latest Retraining Status
-- ============================================================================

CREATE OR REPLACE VIEW v_latest_retraining_status AS
SELECT 
    id,
    trigger_type,
    triggered_at,
    dataset_rows,
    new_version,
    cost_accuracy,
    time_accuracy,
    status,
    completed_at,
    duration_seconds,
    CASE 
        WHEN status = 'completed' THEN 'Success'
        WHEN status = 'failed' THEN 'Failed'
        ELSE 'In Progress'
    END as status_label
FROM ai_model_retraining_log
ORDER BY triggered_at DESC
LIMIT 10;

-- ============================================================================
-- Stored Procedure: Check Retraining Eligibility
-- ============================================================================

DELIMITER $$

CREATE PROCEDURE check_retraining_eligibility(
    OUT eligible BOOLEAN,
    OUT evaluated_count INT,
    OUT message VARCHAR(255)
)
BEGIN
    -- Count evaluated projects
    SELECT COUNT(*) INTO evaluated_count
    FROM construction_projects
    WHERE evaluation_completed_at IS NOT NULL
      AND actual_cost_overrun_percentage IS NOT NULL
      AND actual_time_overrun_percentage IS NOT NULL;
    
    -- Check if minimum threshold met
    IF evaluated_count >= 30 THEN
        SET eligible = TRUE;
        SET message = CONCAT('Eligible for retraining with ', evaluated_count, ' evaluated projects');
    ELSE
        SET eligible = FALSE;
        SET message = CONCAT('Insufficient data: ', evaluated_count, ' projects (minimum 30 required)');
    END IF;
END$$

-- ============================================================================
-- Stored Procedure: Get Retraining Statistics
-- ============================================================================

CREATE PROCEDURE get_retraining_statistics()
BEGIN
    SELECT 
        COUNT(*) as total_retrainings,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_retrainings,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_retrainings,
        AVG(CASE WHEN status = 'completed' THEN duration_seconds END) as avg_duration_seconds,
        MAX(triggered_at) as last_retraining_date,
        MAX(CASE WHEN status = 'completed' THEN new_version END) as latest_version,
        AVG(CASE WHEN status = 'completed' THEN cost_accuracy END) as avg_cost_accuracy,
        AVG(CASE WHEN status = 'completed' THEN time_accuracy END) as avg_time_accuracy
    FROM ai_model_retraining_log;
END$$

DELIMITER ;

-- ============================================================================
-- Sample Queries
-- ============================================================================

-- View all retraining events
-- SELECT * FROM ai_model_retraining_log ORDER BY triggered_at DESC;

-- Check latest retraining status
-- SELECT * FROM v_latest_retraining_status;

-- Get retraining statistics
-- CALL get_retraining_statistics();

-- Check if eligible for retraining
-- CALL check_retraining_eligibility(@eligible, @count, @message);
-- SELECT @eligible, @count, @message;
