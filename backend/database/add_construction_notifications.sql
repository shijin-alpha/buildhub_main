-- Add construction_start notification type to contractor_inbox table
-- This migration adds support for construction start notifications

-- First, check if the contractor_inbox table exists and create it if it doesn't
CREATE TABLE IF NOT EXISTS contractor_inbox (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL,
    homeowner_id INT NOT NULL,
    estimate_id INT DEFAULT NULL,
    type ENUM('layout_request', 'construction_start', 'estimate_response', 'general') DEFAULT 'layout_request',
    title VARCHAR(255) NOT NULL,
    message TEXT,
    status ENUM('unread', 'read', 'acknowledged') DEFAULT 'unread',
    acknowledged_at TIMESTAMP NULL DEFAULT NULL,
    due_date DATE NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contractor_id (contractor_id),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
);

-- Update the type enum to include construction_start if it doesn't already exist
-- Note: This might fail if the table already exists with the old enum, but that's okay
ALTER TABLE contractor_inbox MODIFY COLUMN type ENUM('layout_request', 'construction_start', 'estimate_response', 'general') DEFAULT 'layout_request';

-- Add homeowner_name and homeowner_email columns if they don't exist
ALTER TABLE contractor_inbox ADD COLUMN IF NOT EXISTS homeowner_name VARCHAR(255) DEFAULT NULL;
ALTER TABLE contractor_inbox ADD COLUMN IF NOT EXISTS homeowner_email VARCHAR(255) DEFAULT NULL;

-- Add payload column for storing additional data
ALTER TABLE contractor_inbox ADD COLUMN IF NOT EXISTS payload JSON DEFAULT NULL;

