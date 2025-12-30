-- Create progress_reports table for storing generated reports
CREATE TABLE IF NOT EXISTS progress_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    contractor_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    report_type ENUM('daily', 'weekly', 'monthly') NOT NULL,
    report_period_start DATE,
    report_period_end DATE,
    report_data TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('draft', 'sent', 'viewed', 'acknowledged') DEFAULT 'draft',
    homeowner_viewed_at TIMESTAMP NULL,
    homeowner_acknowledged_at TIMESTAMP NULL,
    
    INDEX idx_project_contractor (project_id, contractor_id),
    INDEX idx_homeowner (homeowner_id),
    INDEX idx_report_type (report_type),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status),
    INDEX idx_period_range (report_period_start, report_period_end),
    INDEX idx_project_type_period (project_id, report_type, report_period_start)
);