-- Enhanced BuildHub Integrated Workflow Database Schema
-- This script creates tables to support the integrated workflow connecting
-- house plans, geo-photos, progress reports, and project management

-- 1. Enhanced layout_requests table with integration flags
ALTER TABLE layout_requests 
ADD COLUMN IF NOT EXISTS requires_house_plan BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS enable_progress_tracking BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS enable_geo_photos BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS house_plan_requirements JSON NULL,
ADD COLUMN IF NOT EXISTS workflow_status ENUM('pending', 'design_phase', 'approval_phase', 'construction_phase', 'completed') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS integration_features JSON NULL;

-- 2. Projects table for comprehensive project management
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    homeowner_id INT NOT NULL,
    layout_request_id INT NULL,
    architect_id INT NULL,
    contractor_id INT NULL,
    project_name VARCHAR(255) NOT NULL,
    project_description TEXT NULL,
    status ENUM('planning', 'design', 'approval', 'construction', 'completed', 'on_hold') DEFAULT 'planning',
    
    -- Feature enablement flags
    enable_house_plans BOOLEAN DEFAULT TRUE,
    enable_geo_photos BOOLEAN DEFAULT TRUE,
    enable_progress_reports BOOLEAN DEFAULT TRUE,
    
    -- Project details
    total_budget DECIMAL(15,2) NULL,
    allocated_budget DECIMAL(15,2) NULL,
    spent_budget DECIMAL(15,2) DEFAULT 0,
    start_date DATE NULL,
    expected_completion_date DATE NULL,
    actual_completion_date DATE NULL,
    
    -- Integration tracking
    house_plan_id INT NULL,
    active_house_plan_id INT NULL,
    total_geo_photos INT DEFAULT 0,
    total_progress_reports INT DEFAULT 0,
    
    -- Metadata
    project_metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (layout_request_id) REFERENCES layout_requests(id) ON DELETE SET NULL,
    FOREIGN KEY (architect_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (house_plan_id) REFERENCES house_plans(id) ON DELETE SET NULL,
    FOREIGN KEY (active_house_plan_id) REFERENCES house_plans(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_homeowner (homeowner_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, expected_completion_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Enhanced layout_request_assignments with workflow integration
ALTER TABLE layout_request_assignments 
ADD COLUMN IF NOT EXISTS project_id INT NULL,
ADD COLUMN IF NOT EXISTS workflow_instructions JSON NULL,
ADD COLUMN IF NOT EXISTS assigned_features JSON NULL,
ADD COLUMN IF NOT EXISTS completion_status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
ADD FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL;

-- 4. Project milestones for tracking workflow progress
CREATE TABLE IF NOT EXISTS project_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    milestone_name VARCHAR(255) NOT NULL,
    milestone_description TEXT NULL,
    phase ENUM('planning', 'design', 'approval', 'construction', 'completion') NOT NULL,
    order_sequence INT NOT NULL DEFAULT 1,
    
    -- Status tracking
    status ENUM('pending', 'in_progress', 'completed', 'skipped') DEFAULT 'pending',
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Dates
    planned_start_date DATE NULL,
    planned_end_date DATE NULL,
    actual_start_date DATE NULL,
    actual_end_date DATE NULL,
    
    -- Integration links
    house_plan_id INT NULL,
    progress_report_id INT NULL,
    geo_photo_count INT DEFAULT 0,
    
    -- Metadata
    milestone_metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (house_plan_id) REFERENCES house_plans(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_project_phase (project_id, phase),
    INDEX idx_status (status),
    INDEX idx_order (project_id, order_sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Enhanced house_plans table with project integration
ALTER TABLE house_plans 
ADD COLUMN IF NOT EXISTS project_id INT NULL,
ADD COLUMN IF NOT EXISTS milestone_id INT NULL,
ADD COLUMN IF NOT EXISTS workflow_stage ENUM('draft', 'review', 'approved', 'construction_ready') DEFAULT 'draft',
ADD COLUMN IF NOT EXISTS integration_data JSON NULL,
ADD FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL;

-- 6. Enhanced geo_photos table with project and milestone linking
ALTER TABLE geo_photos 
ADD COLUMN IF NOT EXISTS project_id INT NULL,
ADD COLUMN IF NOT EXISTS milestone_id INT NULL,
ADD COLUMN IF NOT EXISTS workflow_context ENUM('site_survey', 'foundation', 'structure', 'finishing', 'completion') NULL,
ADD FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
ADD FOREIGN KEY (milestone_id) REFERENCES project_milestones(id) ON DELETE SET NULL;

-- 7. Project progress reports linking all features
CREATE TABLE IF NOT EXISTS project_progress_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    milestone_id INT NULL,
    contractor_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    
    -- Report details
    report_title VARCHAR(255) NOT NULL,
    report_description TEXT NULL,
    work_completed TEXT NULL,
    work_planned TEXT NULL,
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Integration data
    house_plan_updates JSON NULL,
    geo_photo_ids JSON NULL,
    material_usage JSON NULL,
    labor_details JSON NULL,
    quality_checks JSON NULL,
    
    -- Status and dates
    report_status ENUM('draft', 'submitted', 'reviewed', 'approved') DEFAULT 'draft',
    submission_date TIMESTAMP NULL,
    review_date TIMESTAMP NULL,
    approval_date TIMESTAMP NULL,
    
    -- Metadata
    report_metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (milestone_id) REFERENCES project_milestones(id) ON DELETE SET NULL,
    FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_project_status (project_id, report_status),
    INDEX idx_contractor (contractor_id),
    INDEX idx_dates (submission_date, review_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Workflow notifications for integrated features
CREATE TABLE IF NOT EXISTS workflow_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    notification_type ENUM('house_plan_update', 'geo_photo_added', 'progress_report', 'milestone_completed', 'approval_required') NOT NULL,
    
    -- Notification content
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    action_required BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500) NULL,
    
    -- Integration references
    house_plan_id INT NULL,
    geo_photo_id INT NULL,
    progress_report_id INT NULL,
    milestone_id INT NULL,
    
    -- Status
    is_read BOOLEAN DEFAULT FALSE,
    is_archived BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    
    -- Metadata
    notification_metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (house_plan_id) REFERENCES house_plans(id) ON DELETE SET NULL,
    FOREIGN KEY (geo_photo_id) REFERENCES geo_photos(id) ON DELETE SET NULL,
    FOREIGN KEY (milestone_id) REFERENCES project_milestones(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_project_type (project_id, notification_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Feature integration tracking
CREATE TABLE IF NOT EXISTS feature_integrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    feature_name ENUM('house_plan_designer', 'geo_tagged_photos', 'progress_reports', 'milestone_tracking') NOT NULL,
    
    -- Integration status
    is_enabled BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT FALSE,
    configuration JSON NULL,
    
    -- Usage statistics
    total_usage_count INT DEFAULT 0,
    last_used_at TIMESTAMP NULL,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    
    -- Unique constraint
    UNIQUE KEY unique_project_feature (project_id, feature_name),
    
    -- Indexes
    INDEX idx_project_enabled (project_id, is_enabled),
    INDEX idx_feature_active (feature_name, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Create views for integrated workflow reporting
CREATE OR REPLACE VIEW project_overview AS
SELECT 
    p.id as project_id,
    p.project_name,
    p.status as project_status,
    p.homeowner_id,
    p.architect_id,
    p.contractor_id,
    lr.id as layout_request_id,
    lr.plot_size,
    lr.budget_range,
    lr.location,
    
    -- Feature counts
    COUNT(DISTINCT hp.id) as total_house_plans,
    COUNT(DISTINCT gp.id) as total_geo_photos,
    COUNT(DISTINCT ppr.id) as total_progress_reports,
    COUNT(DISTINCT pm.id) as total_milestones,
    
    -- Status counts
    SUM(CASE WHEN pm.status = 'completed' THEN 1 ELSE 0 END) as completed_milestones,
    SUM(CASE WHEN pm.status = 'in_progress' THEN 1 ELSE 0 END) as active_milestones,
    
    -- Progress calculation
    COALESCE(AVG(pm.progress_percentage), 0) as overall_progress,
    
    -- Dates
    p.start_date,
    p.expected_completion_date,
    p.created_at as project_created_at
    
FROM projects p
LEFT JOIN layout_requests lr ON p.layout_request_id = lr.id
LEFT JOIN house_plans hp ON p.id = hp.project_id
LEFT JOIN geo_photos gp ON p.id = gp.project_id
LEFT JOIN project_progress_reports ppr ON p.id = ppr.project_id
LEFT JOIN project_milestones pm ON p.id = pm.project_id
GROUP BY p.id;

-- 11. Insert default room templates for house plan designer integration
INSERT IGNORE INTO room_templates (name, icon, default_width, default_height, category, description) VALUES
('Master Bedroom', '🛏️', 12, 14, 'bedroom', 'Primary bedroom with attached bathroom'),
('Bedroom', '🛏️', 10, 12, 'bedroom', 'Standard bedroom'),
('Kitchen', '🍳', 8, 10, 'utility', 'Cooking and food preparation area'),
('Bathroom', '🛁', 6, 8, 'utility', 'Bathroom with standard fixtures'),
('Living Room', '🛋️', 14, 16, 'common', 'Main family gathering area'),
('Dining Room', '🍽️', 10, 12, 'common', 'Dining and meal area'),
('Study Room', '📚', 8, 10, 'special', 'Work and study space'),
('Pooja Room', '🙏', 6, 6, 'special', 'Prayer and meditation room'),
('Store Room', '📦', 6, 8, 'utility', 'Storage and utility space'),
('Parking', '🚗', 10, 20, 'utility', 'Vehicle parking space'),
('Balcony', '🏡', 6, 12, 'outdoor', 'Outdoor relaxation space'),
('Entrance', '🚪', 6, 8, 'common', 'Main entry foyer'),
('Garden', '🌿', 12, 16, 'outdoor', 'Landscaped outdoor area'),
('Utility Room', '🏠', 6, 8, 'utility', 'Laundry and utility space');

-- 12. Create indexes for performance optimization
CREATE INDEX IF NOT EXISTS idx_projects_workflow ON projects(status, enable_house_plans, enable_geo_photos, enable_progress_reports);
CREATE INDEX IF NOT EXISTS idx_milestones_workflow ON project_milestones(project_id, phase, status, order_sequence);
CREATE INDEX IF NOT EXISTS idx_notifications_workflow ON workflow_notifications(project_id, user_id, notification_type, is_read);
CREATE INDEX IF NOT EXISTS idx_house_plans_workflow ON house_plans(project_id, workflow_stage, status);
CREATE INDEX IF NOT EXISTS idx_geo_photos_workflow ON geo_photos(project_id, workflow_context, created_at);

-- 13. Create triggers for automatic workflow updates
DELIMITER //

-- Trigger to update project statistics when house plans are added
CREATE TRIGGER IF NOT EXISTS update_project_house_plans
AFTER INSERT ON house_plans
FOR EACH ROW
BEGIN
    IF NEW.project_id IS NOT NULL THEN
        UPDATE projects 
        SET active_house_plan_id = NEW.id,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = NEW.project_id;
    END IF;
END//

-- Trigger to update project statistics when geo photos are added
CREATE TRIGGER IF NOT EXISTS update_project_geo_photos
AFTER INSERT ON geo_photos
FOR EACH ROW
BEGIN
    IF NEW.project_id IS NOT NULL THEN
        UPDATE projects 
        SET total_geo_photos = total_geo_photos + 1,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = NEW.project_id;
    END IF;
END//

-- Trigger to update project statistics when progress reports are added
CREATE TRIGGER IF NOT EXISTS update_project_progress_reports
AFTER INSERT ON project_progress_reports
FOR EACH ROW
BEGIN
    UPDATE projects 
    SET total_progress_reports = total_progress_reports + 1,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.project_id;
END//

-- Trigger to update project status when milestones are completed
CREATE TRIGGER IF NOT EXISTS update_project_milestone_completion
AFTER UPDATE ON project_milestones
FOR EACH ROW
BEGIN
    DECLARE completed_count INT;
    DECLARE total_count INT;
    
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        -- Count completed milestones
        SELECT COUNT(*) INTO completed_count
        FROM project_milestones 
        WHERE project_id = NEW.project_id AND status = 'completed';
        
        -- Count total milestones
        SELECT COUNT(*) INTO total_count
        FROM project_milestones 
        WHERE project_id = NEW.project_id;
        
        -- Update project status if all milestones completed
        IF completed_count = total_count THEN
            UPDATE projects 
            SET status = 'completed',
                actual_completion_date = CURRENT_DATE,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = NEW.project_id;
        END IF;
    END IF;
END//

DELIMITER ;

-- 14. Insert sample workflow configurations
INSERT IGNORE INTO feature_integrations (project_id, feature_name, is_enabled, configuration) 
SELECT 
    p.id,
    'house_plan_designer',
    p.enable_house_plans,
    JSON_OBJECT(
        'auto_create_from_request', true,
        'require_approval', true,
        'enable_revisions', true
    )
FROM projects p
WHERE p.enable_house_plans = 1;

INSERT IGNORE INTO feature_integrations (project_id, feature_name, is_enabled, configuration)
SELECT 
    p.id,
    'geo_tagged_photos',
    p.enable_geo_photos,
    JSON_OBJECT(
        'auto_tag_location', true,
        'require_coordinates', true,
        'max_photos_per_report', 10
    )
FROM projects p
WHERE p.enable_geo_photos = 1;

INSERT IGNORE INTO feature_integrations (project_id, feature_name, is_enabled, configuration)
SELECT 
    p.id,
    'progress_reports',
    p.enable_progress_reports,
    JSON_OBJECT(
        'frequency', 'weekly',
        'require_photos', true,
        'auto_milestone_update', true
    )
FROM projects p
WHERE p.enable_progress_reports = 1;

-- Success message
SELECT 'Integrated workflow database schema created successfully!' as message;