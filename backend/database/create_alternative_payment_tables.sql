-- Alternative Payment Methods Tables
-- For payments outside of Razorpay (bank transfer, UPI, cash, cheque)

-- Alternative payment methods table
CREATE TABLE IF NOT EXISTS alternative_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_type ENUM('technical_details', 'stage_payment') NOT NULL,
    reference_id INT NOT NULL, -- house_plan_id or payment_request_id
    homeowner_id INT NOT NULL,
    contractor_id INT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'INR',
    payment_method ENUM('bank_transfer', 'upi', 'cash', 'cheque', 'other') NOT NULL,
    payment_status ENUM('initiated', 'pending_verification', 'verified', 'completed', 'failed', 'cancelled') DEFAULT 'initiated',
    
    -- Payment details
    transaction_reference VARCHAR(255) NULL, -- Bank ref, UPI ID, cheque number
    payment_date DATE NULL,
    verification_required BOOLEAN DEFAULT TRUE,
    verification_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    verified_by INT NULL, -- Admin/contractor who verified
    verified_at TIMESTAMP NULL,
    
    -- Instructions and notes
    payment_instructions JSON NULL,
    homeowner_notes TEXT NULL,
    contractor_notes TEXT NULL,
    admin_notes TEXT NULL,
    
    -- File attachments
    receipt_file_path VARCHAR(500) NULL,
    proof_file_path VARCHAR(500) NULL,
    additional_files JSON NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_payment_type (payment_type),
    INDEX idx_reference_id (reference_id),
    INDEX idx_homeowner_id (homeowner_id),
    INDEX idx_contractor_id (contractor_id),
    INDEX idx_payment_method (payment_method),
    INDEX idx_payment_status (payment_status),
    INDEX idx_verification_status (verification_status),
    INDEX idx_payment_date (payment_date),
    INDEX idx_created_at (created_at)
);

-- Bank details for contractors
CREATE TABLE IF NOT EXISTS contractor_bank_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    ifsc_code VARCHAR(20) NOT NULL,
    bank_name VARCHAR(255) NOT NULL,
    branch_name VARCHAR(255) NULL,
    account_type ENUM('savings', 'current') DEFAULT 'savings',
    
    -- UPI details
    upi_id VARCHAR(100) NULL,
    upi_verified BOOLEAN DEFAULT FALSE,
    
    -- Verification status
    is_verified BOOLEAN DEFAULT FALSE,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    
    -- Additional details
    pan_number VARCHAR(20) NULL,
    gstin VARCHAR(20) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_contractor (contractor_id),
    INDEX idx_contractor_id (contractor_id),
    INDEX idx_account_number (account_number),
    INDEX idx_ifsc_code (ifsc_code),
    INDEX idx_upi_id (upi_id),
    INDEX idx_is_verified (is_verified)
);

-- Payment verification logs
CREATE TABLE IF NOT EXISTS payment_verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    verifier_id INT NOT NULL,
    verifier_type ENUM('homeowner', 'contractor', 'admin') NOT NULL,
    action ENUM('submitted', 'approved', 'rejected', 'requested_info') NOT NULL,
    comments TEXT NULL,
    attached_files JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (payment_id) REFERENCES alternative_payments(id) ON DELETE CASCADE,
    INDEX idx_payment_id (payment_id),
    INDEX idx_verifier (verifier_id, verifier_type),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Payment notifications for alternative methods
CREATE TABLE IF NOT EXISTS alternative_payment_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    recipient_id INT NOT NULL,
    recipient_type ENUM('homeowner', 'contractor', 'admin') NOT NULL,
    notification_type ENUM('payment_initiated', 'verification_required', 'payment_verified', 'payment_completed', 'payment_failed') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (payment_id) REFERENCES alternative_payments(id) ON DELETE CASCADE,
    INDEX idx_payment_id (payment_id),
    INDEX idx_recipient (recipient_id, recipient_type),
    INDEX idx_notification_type (notification_type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Insert sample contractor bank details
INSERT INTO contractor_bank_details 
(contractor_id, account_name, account_number, ifsc_code, bank_name, branch_name, upi_id, is_verified) 
VALUES 
(1, 'ABC Construction Pvt Ltd', '1234567890123456', 'SBIN0001234', 'State Bank of India', 'Main Branch', 'abcconstruction@paytm', TRUE),
(2, 'XYZ Builders', '9876543210987654', 'HDFC0002345', 'HDFC Bank', 'Commercial Branch', 'xyzbuilders@phonepe', TRUE)
ON DUPLICATE KEY UPDATE id=id;

-- Insert sample alternative payment
INSERT INTO alternative_payments 
(payment_type, reference_id, homeowner_id, contractor_id, amount, payment_method, payment_status) 
VALUES 
('stage_payment', 1, 1, 1, 1000000.00, 'bank_transfer', 'initiated')
ON DUPLICATE KEY UPDATE id=id;