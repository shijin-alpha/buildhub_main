-- Construction Progress Update Tables for BuildHub
-- This creates the necessary tables for tracking construction progress

-- Main construction progress updates table
CREATE TABLE IF NOT EXISTS construction_progress_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    contractor_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    stage_name ENUM('Foundation', 'Structure', 'Brickwork', 'Roofing', 'Electrical', 'Plumbing', 'Finishing', 'Other') NOT NULL,
    stage_status ENUM('Not Started', 'In Progress', 'Completed') NOT NULL,
    completion_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00 CHECK (completion_percentage >= 0 AND completion_percentage <= 100),
    remarks TEXT,
    delay_reason ENUM('Weather', 'Material Delay', 'Labor Shortage', 'Design Change', 'Client Request', 'Other') NULL,
    delay_description TEXT NULL,
    photo_paths JSON NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    location_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_project_id (project_id),
    INDEX idx_contractor_id (contractor_id),
    INDEX idx_homeowner_id (homeowner_id),
    INDEX idx_stage_name (stage_name),
    INDEX idx_created_at (created_at),
    
    -- Foreign key constraints (assuming these tables exist)
    FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Project locations table for geo-verification
CREATE TABLE IF NOT EXISTS project_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL UNIQUE,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    address TEXT,
    radius_meters INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_project_id (project_id)
);

-- Progress notifications table (extends existing notification system)
CREATE TABLE IF NOT EXISTS progress_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progress_update_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    contractor_id INT NOT NULL,
    type ENUM('progress_update', 'stage_completed', 'delay_reported') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    INDEX idx_homeowner_id (homeowner_id),
    INDEX idx_contractor_id (contractor_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (progress_update_id) REFERENCES construction_progress_updates(id) ON DELETE CASCADE,
    FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add progress_update type to existing contractor_inbox if it exists
-- This is a safe operation that won't fail if the table doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contractor_inbox') > 0,
    "ALTER TABLE contractor_inbox MODIFY COLUMN type ENUM('layout_request', 'construction_start', 'estimate_response', 'general', 'progress_update') DEFAULT 'layout_request'",
    "SELECT 'contractor_inbox table does not exist' as message"
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create indexes for better query performance
CREATE INDEX IF NOT EXISTS idx_progress_stage_status ON construction_progress_updates(stage_name, stage_status);
CREATE INDEX IF NOT EXISTS idx_progress_completion ON construction_progress_updates(completion_percentage);
CREATE INDEX IF NOT EXISTS idx_progress_location ON construction_progress_updates(latitude, longitude);

-- Insert sample project locations for testing (optional)
-- INSERT INTO project_locations (project_id, latitude, longitude, address) VALUES
-- (1, 12.9716, 77.5946, 'Bangalore, Karnataka'),
-- (2, 19.0760, 72.8777, 'Mumbai, Maharashtra'),
-- (3, 28.7041, 77.1025, 'New Delhi, Delhi');