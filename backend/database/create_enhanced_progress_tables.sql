-- Enhanced Construction Progress Monitoring System
-- This creates comprehensive tables for daily, weekly, and monthly progress tracking

-- Daily Progress Updates Table
CREATE TABLE IF NOT EXISTS daily_progress_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    contractor_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    update_date DATE NOT NULL,
    construction_stage ENUM('Foundation', 'Structure', 'Brickwork', 'Roofing', 'Electrical', 'Plumbing', 'Finishing', 'Other') NOT NULL,
    work_done_today TEXT NOT NULL,
    incremental_completion_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00 CHECK (incremental_completion_percentage >= 0 AND incremental_completion_percentage <= 100),
    cumulative_completion_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00 CHECK (cumulative_completion_percentage >= 0 AND cumulative_completion_percentage <= 100),
    working_hours DECIMAL(4,2) NOT NULL DEFAULT 8.00,
    weather_condition ENUM('Sunny', 'Cloudy', 'Rainy', 'Stormy', 'Foggy', 'Hot', 'Cold', 'Windy') NOT NULL,
    site_issues TEXT NULL,
    progress_photos JSON NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    location_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_project_date (project_id, update_date),
    INDEX idx_contractor_date (contractor_id, update_date),
    INDEX idx_stage (construction_stage),
    INDEX idx_date (update_date),
    
    -- Unique constraint to prevent duplicate daily updates
    UNIQUE KEY unique_daily_update (project_id, contractor_id, update_date),
    
    -- Foreign key constraints
    FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Daily Labour Tracking Table
CREATE TABLE IF NOT EXISTS daily_labour_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    daily_progress_id INT NOT NULL,
    worker_type ENUM('Mason', 'Helper', 'Electrician', 'Plumber', 'Carpenter', 'Painter', 'Supervisor', 'Welder', 'Crane Operator', 'Excavator Operator', 'Steel Fixer', 'Tile Worker', 'Plasterer', 'Roofer', 'Security Guard', 'Site Engineer', 'Quality Inspector', 'Safety Officer', 'Other') NOT NULL,
    worker_count INT NOT NULL DEFAULT 0,
    hours_worked DECIMAL(4,2) NOT NULL DEFAULT 8.00,
    overtime_hours DECIMAL(4,2) DEFAULT 0.00,
    absent_count INT DEFAULT 0,
    hourly_rate DECIMAL(8,2) DEFAULT 0.00,
    total_wages DECIMAL(10,2) DEFAULT 0.00,
    productivity_rating INT DEFAULT 5 CHECK (productivity_rating >= 1 AND productivity_rating <= 5),
    safety_compliance ENUM('excellent', 'good', 'average', 'poor', 'needs_improvement') DEFAULT 'good',
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_daily_progress (daily_progress_id),
    INDEX idx_worker_type (worker_type),
    INDEX idx_productivity (productivity_rating),
    INDEX idx_safety (safety_compliance),
    
    FOREIGN KEY (daily_progress_id) REFERENCES daily_progress_updates(id) ON DELETE CASCADE
);

-- Weekly Progress Summary Table
CREATE TABLE IF NOT EXISTS weekly_progress_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    contractor_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    week_start_date DATE NOT NULL,
    week_end_date DATE NOT NULL,
    stages_worked JSON NOT NULL, -- Array of stages worked during the week
    start_progress_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    end_progress_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    total_labour_used JSON NOT NULL, -- Object with worker types and total counts
    delays_and_reasons TEXT NULL,
    weekly_remarks TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_project_week (project_id, week_start_date),
    INDEX idx_contractor_week (contractor_id, week_start_date),
    INDEX idx_week_range (week_start_date, week_end_date),
    
    -- Unique constraint for weekly updates
    UNIQUE KEY unique_weekly_update (project_id, contractor_id, week_start_date),
    
    -- Foreign key constraints
    FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Monthly Progress Report Table
CREATE TABLE IF NOT EXISTS monthly_progress_report (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    contractor_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    report_month INT NOT NULL CHECK (report_month >= 1 AND report_month <= 12),
    report_year INT NOT NULL CHECK (report_year >= 2020 AND report_year <= 2050),
    planned_progress_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    actual_progress_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    milestones_achieved JSON NOT NULL, -- Array of achieved milestones
    labour_summary JSON NOT NULL, -- Summary of labour utilization
    material_summary JSON NOT NULL, -- Summary of materials used
    delay_explanation TEXT NULL,
    contractor_remarks TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_project_month (project_id, report_year, report_month),
    INDEX idx_contractor_month (contractor_id, report_year, report_month),
    INDEX idx_month_year (report_year, report_month),
    
    -- Unique constraint for monthly reports
    UNIQUE KEY unique_monthly_report (project_id, contractor_id, report_year, report_month),
    
    -- Foreign key constraints
    FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Progress Milestones Table (for tracking planned vs actual milestones)
CREATE TABLE IF NOT EXISTS progress_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    milestone_name VARCHAR(255) NOT NULL,
    milestone_stage ENUM('Foundation', 'Structure', 'Brickwork', 'Roofing', 'Electrical', 'Plumbing', 'Finishing', 'Other') NOT NULL,
    planned_completion_date DATE NOT NULL,
    actual_completion_date DATE NULL,
    planned_progress_percentage DECIMAL(5,2) NOT NULL,
    status ENUM('Pending', 'In Progress', 'Completed', 'Delayed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_project_milestone (project_id),
    INDEX idx_milestone_stage (milestone_stage),
    INDEX idx_planned_date (planned_completion_date),
    INDEX idx_status (status)
);

-- Enhanced Progress Notifications Table
CREATE TABLE IF NOT EXISTS enhanced_progress_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    contractor_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    notification_type ENUM('daily_update', 'weekly_summary', 'monthly_report', 'milestone_completed', 'delay_reported') NOT NULL,
    reference_id INT NOT NULL, -- ID of the related update/summary/report
    title VARCHAR(255) NOT NULL,
    message TEXT,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    INDEX idx_homeowner_status (homeowner_id, status),
    INDEX idx_contractor_type (contractor_id, notification_type),
    INDEX idx_project_notifications (project_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Progress Analytics View (for graph data)
CREATE OR REPLACE VIEW progress_analytics AS
SELECT 
    p.project_id,
    p.contractor_id,
    p.homeowner_id,
    DATE(p.update_date) as date,
    p.construction_stage,
    p.cumulative_completion_percentage,
    p.working_hours,
    p.weather_condition,
    -- Labour summary for the day
    (SELECT JSON_OBJECT(
        'total_workers', COALESCE(SUM(l.worker_count), 0),
        'total_hours', COALESCE(SUM(l.hours_worked), 0),
        'overtime_hours', COALESCE(SUM(l.overtime_hours), 0),
        'worker_breakdown', JSON_ARRAYAGG(
            JSON_OBJECT(
                'type', l.worker_type,
                'count', l.worker_count,
                'hours', l.hours_worked
            )
        )
    ) FROM daily_labour_tracking l WHERE l.daily_progress_id = p.id) as labour_data
FROM daily_progress_updates p
ORDER BY p.project_id, p.update_date;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_daily_progress_cumulative ON daily_progress_updates(cumulative_completion_percentage);
CREATE INDEX IF NOT EXISTS idx_daily_progress_stage_date ON daily_progress_updates(construction_stage, update_date);
CREATE INDEX IF NOT EXISTS idx_labour_tracking_type_date ON daily_labour_tracking(worker_type, created_at);

-- Insert sample milestones for new projects (can be customized)
INSERT IGNORE INTO progress_milestones (project_id, milestone_name, milestone_stage, planned_completion_date, planned_progress_percentage) VALUES
(1, 'Foundation Excavation Complete', 'Foundation', DATE_ADD(CURDATE(), INTERVAL 7 DAY), 10.00),
(1, 'Foundation Concrete Pour', 'Foundation', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 20.00),
(1, 'Ground Floor Structure', 'Structure', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 40.00),
(1, 'First Floor Structure', 'Structure', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 60.00),
(1, 'Roofing Complete', 'Roofing', DATE_ADD(CURDATE(), INTERVAL 60 DAY), 75.00),
(1, 'Electrical Rough-in', 'Electrical', DATE_ADD(CURDATE(), INTERVAL 75 DAY), 85.00),
(1, 'Plumbing Rough-in', 'Plumbing', DATE_ADD(CURDATE(), INTERVAL 80 DAY), 90.00),
(1, 'Final Finishing', 'Finishing', DATE_ADD(CURDATE(), INTERVAL 90 DAY), 100.00);

-- Add triggers to automatically update cumulative progress and milestones
DELIMITER //

CREATE TRIGGER IF NOT EXISTS update_milestone_status
AFTER INSERT ON daily_progress_updates
FOR EACH ROW
BEGIN
    -- Update milestone status based on progress
    UPDATE progress_milestones 
    SET status = CASE 
        WHEN NEW.cumulative_completion_percentage >= planned_progress_percentage THEN 'Completed'
        WHEN NEW.cumulative_completion_percentage >= (planned_progress_percentage - 10) THEN 'In Progress'
        ELSE status
    END,
    actual_completion_date = CASE 
        WHEN NEW.cumulative_completion_percentage >= planned_progress_percentage AND actual_completion_date IS NULL 
        THEN NEW.update_date
        ELSE actual_completion_date
    END
    WHERE project_id = NEW.project_id 
    AND milestone_stage = NEW.construction_stage;
END//

DELIMITER ;

-- Add sample weather conditions and worker types if needed
-- These are already defined in the ENUM constraints above