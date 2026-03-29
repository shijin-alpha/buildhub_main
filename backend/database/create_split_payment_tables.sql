-- Split Payment System Tables
-- Handles large payments by splitting them into smaller transactions

-- Main split payment groups table
CREATE TABLE IF NOT EXISTS split_payment_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_type ENUM('technical_details', 'stage_payment') NOT NULL,
    reference_id INT NOT NULL, -- house_plan_id or payment_request_id
    homeowner_id INT NOT NULL,
    contractor_id INT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'INR',
    country_code VARCHAR(2) DEFAULT 'IN',
    total_splits INT NOT NULL,
    completed_splits INT DEFAULT 0,
    completed_amount DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('pending', 'partial', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    description TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_payment_type (payment_type),
    INDEX idx_reference_id (reference_id),
    INDEX idx_homeowner_id (homeowner_id),
    INDEX idx_contractor_id (contractor_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Individual split transactions table
CREATE TABLE IF NOT EXISTS split_payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    split_group_id INT NOT NULL,
    sequence_number INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'INR',
    razorpay_order_id VARCHAR(255) NULL,
    razorpay_payment_id VARCHAR(255) NULL,
    razorpay_signature VARCHAR(255) NULL,
    payment_status ENUM('created', 'pending', 'completed', 'failed', 'cancelled') DEFAULT 'created',
    failure_reason TEXT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (split_group_id) REFERENCES split_payment_groups(id) ON DELETE CASCADE,
    INDEX idx_split_group_id (split_group_id),
    INDEX idx_sequence_number (sequence_number),
    INDEX idx_razorpay_order_id (razorpay_order_id),
    INDEX idx_payment_status (payment_status),
    UNIQUE KEY unique_group_sequence (split_group_id, sequence_number)
);

-- Split payment notifications table
CREATE TABLE IF NOT EXISTS split_payment_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    split_group_id INT NOT NULL,
    split_transaction_id INT NULL,
    recipient_id INT NOT NULL,
    recipient_type ENUM('homeowner', 'contractor', 'admin') NOT NULL,
    notification_type ENUM('split_created', 'payment_completed', 'payment_failed', 'all_completed', 'payment_cancelled') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (split_group_id) REFERENCES split_payment_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (split_transaction_id) REFERENCES split_payment_transactions(id) ON DELETE SET NULL,
    INDEX idx_split_group_id (split_group_id),
    INDEX idx_recipient (recipient_id, recipient_type),
    INDEX idx_notification_type (notification_type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Split payment progress tracking
CREATE TABLE IF NOT EXISTS split_payment_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    split_group_id INT NOT NULL,
    progress_percentage DECIMAL(5,2) NOT NULL,
    completed_splits INT NOT NULL,
    total_splits INT NOT NULL,
    completed_amount DECIMAL(15,2) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    next_payment_amount DECIMAL(15,2) NULL,
    next_payment_due_date TIMESTAMP NULL,
    estimated_completion_date TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (split_group_id) REFERENCES split_payment_groups(id) ON DELETE CASCADE,
    INDEX idx_split_group_id (split_group_id),
    INDEX idx_progress_percentage (progress_percentage),
    INDEX idx_next_payment_due_date (next_payment_due_date)
);

-- Insert sample data for testing
INSERT INTO split_payment_groups 
(payment_type, reference_id, homeowner_id, contractor_id, total_amount, total_splits, description) 
VALUES 
('stage_payment', 1, 1, 2, 1000000.00, 2, 'Split payment for ₹10 lakh construction stage'),
('technical_details', 1, 1, NULL, 800000.00, 2, 'Split payment for ₹8 lakh technical details unlock')
ON DUPLICATE KEY UPDATE id=id;

-- Insert corresponding split transactions
INSERT INTO split_payment_transactions 
(split_group_id, sequence_number, amount, description) 
VALUES 
(1, 1, 500000.00, 'First payment of ₹5 lakh'),
(1, 2, 500000.00, 'Second payment of ₹5 lakh'),
(2, 1, 400000.00, 'First payment of ₹4 lakh'),
(2, 2, 400000.00, 'Second payment of ₹4 lakh')
ON DUPLICATE KEY UPDATE id=id;