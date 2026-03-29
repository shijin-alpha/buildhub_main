-- Stage Payment Management System
-- This creates tables for managing stage-based payment requests and approvals

-- 1. Construction stage payment structure
CREATE TABLE IF NOT EXISTS construction_stage_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stage_name VARCHAR(100) NOT NULL,
    stage_order INT NOT NULL,
    typical_percentage DECIMAL(5,2) DEFAULT 0.00,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_stage_name (stage_name),
    INDEX idx_stage_order (stage_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Project stage payment requests
CREATE TABLE IF NOT EXISTS project_stage_payment_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    contractor_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    stage_name VARCHAR(100) NOT NULL,
    requested_amount DECIMAL(12,2) NOT NULL,
    percentage_of_total DECIMAL(5,2) NOT NULL,
    work_description TEXT NOT NULL,
    completion_percentage DECIMAL(5,2) NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
    homeowner_response_date TIMESTAMP NULL,
    payment_date TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    contractor_notes TEXT NULL,
    homeowner_notes TEXT NULL,
    
    -- Progress update reference
    progress_update_id INT NULL,
    
    -- Payment tracking
    payment_method VARCHAR(50) NULL,
    transaction_id VARCHAR(255) NULL,
    
    FOREIGN KEY (project_id) REFERENCES contractor_send_estimates(id) ON DELETE CASCADE,
    FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (progress_update_id) REFERENCES construction_progress_updates(id) ON DELETE SET NULL,
    
    INDEX idx_project_stage (project_id, stage_name),
    INDEX idx_contractor_status (contractor_id, status),
    INDEX idx_homeowner_status (homeowner_id, status),
    INDEX idx_request_date (request_date),
    INDEX idx_status_date (status, request_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Payment milestones and schedules
CREATE TABLE IF NOT EXISTS project_payment_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    stage_name VARCHAR(100) NOT NULL,
    scheduled_percentage DECIMAL(5,2) NOT NULL,
    scheduled_amount DECIMAL(12,2) NOT NULL,
    due_date DATE NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES contractor_send_estimates(id) ON DELETE CASCADE,
    
    INDEX idx_project_schedule (project_id, stage_name),
    INDEX idx_due_date (due_date),
    INDEX idx_completion (is_completed, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Payment notifications and communications
CREATE TABLE IF NOT EXISTS payment_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_request_id INT NOT NULL,
    recipient_id INT NOT NULL,
    recipient_type ENUM('homeowner', 'contractor') NOT NULL,
    notification_type ENUM('request_submitted', 'request_approved', 'request_rejected', 'payment_completed', 'payment_overdue') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    FOREIGN KEY (payment_request_id) REFERENCES project_stage_payment_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_recipient_unread (recipient_id, is_read),
    INDEX idx_notification_type (notification_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Insert default construction stage payment structure
INSERT IGNORE INTO construction_stage_payments (stage_name, stage_order, typical_percentage, description) VALUES
('Site Preparation', 1, 5.00, 'Initial site setup, clearing, and preparation work'),
('Foundation', 2, 20.00, 'Foundation excavation, concrete work, and structural base'),
('Structure', 3, 25.00, 'Main structural work including columns, beams, and slabs'),
('Brickwork', 4, 15.00, 'Wall construction and masonry work'),
('Roofing', 5, 10.00, 'Roof structure and covering installation'),
('Electrical', 6, 8.00, 'Electrical wiring and installations'),
('Plumbing', 7, 7.00, 'Plumbing installations and pipe work'),
('Finishing', 8, 8.00, 'Plastering, painting, and interior finishing'),
('Final Inspection', 9, 2.00, 'Quality checks, cleanup, and project handover');

-- 6. Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_payment_requests_project_status ON project_stage_payment_requests(project_id, status, request_date);
CREATE INDEX IF NOT EXISTS idx_payment_schedule_project_completion ON project_payment_schedule(project_id, is_completed, due_date);
CREATE INDEX IF NOT EXISTS idx_payment_notifications_recipient_type ON payment_notifications(recipient_id, recipient_type, is_read);

-- 7. Create view for payment dashboard
CREATE OR REPLACE VIEW payment_dashboard_view AS
SELECT 
    ppr.id as request_id,
    ppr.project_id,
    ppr.contractor_id,
    ppr.homeowner_id,
    ppr.stage_name,
    ppr.requested_amount,
    ppr.percentage_of_total,
    ppr.completion_percentage,
    ppr.status,
    ppr.request_date,
    ppr.homeowner_response_date,
    ppr.payment_date,
    
    -- Project details
    cse.total_cost as project_total_cost,
    cse.homeowner_first_name,
    cse.homeowner_last_name,
    cse.contractor_first_name,
    cse.contractor_last_name,
    
    -- Stage details
    csp.typical_percentage,
    csp.stage_order,
    
    -- Payment calculations
    CASE 
        WHEN ppr.status = 'paid' THEN ppr.requested_amount
        ELSE 0
    END as paid_amount,
    
    CASE 
        WHEN ppr.status IN ('pending', 'approved') THEN ppr.requested_amount
        ELSE 0
    END as pending_amount,
    
    -- Time calculations
    DATEDIFF(NOW(), ppr.request_date) as days_since_request,
    
    CASE 
        WHEN ppr.status = 'pending' AND DATEDIFF(NOW(), ppr.request_date) > 7 THEN TRUE
        ELSE FALSE
    END as is_overdue

FROM project_stage_payment_requests ppr
LEFT JOIN contractor_send_estimates cse ON ppr.project_id = cse.id
LEFT JOIN construction_stage_payments csp ON ppr.stage_name = csp.stage_name
ORDER BY ppr.request_date DESC;

-- Success message
SELECT 'Stage Payment Management System created successfully!' as message;