<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating alternative payment tables...\n";
    
    // Alternative payments table
    $sql1 = "
    CREATE TABLE IF NOT EXISTS alternative_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        payment_type ENUM('technical_details', 'stage_payment') NOT NULL,
        reference_id INT NOT NULL,
        homeowner_id INT NOT NULL,
        contractor_id INT NULL,
        amount DECIMAL(15,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'INR',
        payment_method ENUM('bank_transfer', 'upi', 'cash', 'cheque', 'other') NOT NULL,
        payment_status ENUM('initiated', 'pending_verification', 'verified', 'completed', 'failed', 'cancelled') DEFAULT 'initiated',
        
        transaction_reference VARCHAR(255) NULL,
        payment_date DATE NULL,
        verification_required BOOLEAN DEFAULT TRUE,
        verification_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        verified_by INT NULL,
        verified_at TIMESTAMP NULL,
        
        payment_instructions JSON NULL,
        homeowner_notes TEXT NULL,
        contractor_notes TEXT NULL,
        admin_notes TEXT NULL,
        
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
        INDEX idx_payment_status (payment_status)
    )";
    
    $db->exec($sql1);
    echo "✓ Created alternative_payments table\n";
    
    // Contractor bank details table
    $sql2 = "
    CREATE TABLE IF NOT EXISTS contractor_bank_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        account_name VARCHAR(255) NOT NULL,
        account_number VARCHAR(50) NOT NULL,
        ifsc_code VARCHAR(20) NOT NULL,
        bank_name VARCHAR(255) NOT NULL,
        branch_name VARCHAR(255) NULL,
        account_type ENUM('savings', 'current') DEFAULT 'savings',
        
        upi_id VARCHAR(100) NULL,
        upi_verified BOOLEAN DEFAULT FALSE,
        
        is_verified BOOLEAN DEFAULT FALSE,
        verified_by INT NULL,
        verified_at TIMESTAMP NULL,
        
        pan_number VARCHAR(20) NULL,
        gstin VARCHAR(20) NULL,
        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        UNIQUE KEY unique_contractor (contractor_id),
        INDEX idx_contractor_id (contractor_id)
    )";
    
    $db->exec($sql2);
    echo "✓ Created contractor_bank_details table\n";
    
    // Alternative payment notifications table
    $sql3 = "
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
        
        INDEX idx_payment_id (payment_id),
        INDEX idx_recipient (recipient_id, recipient_type)
    )";
    
    $db->exec($sql3);
    echo "✓ Created alternative_payment_notifications table\n";
    
    // Insert sample contractor bank details
    $sql4 = "
    INSERT IGNORE INTO contractor_bank_details 
    (contractor_id, account_name, account_number, ifsc_code, bank_name, branch_name, upi_id, is_verified) 
    VALUES 
    (1, 'ABC Construction Pvt Ltd', '1234567890123456', 'SBIN0001234', 'State Bank of India', 'Main Branch', 'abcconstruction@paytm', TRUE),
    (2, 'XYZ Builders', '9876543210987654', 'HDFC0002345', 'HDFC Bank', 'Commercial Branch', 'xyzbuilders@phonepe', TRUE)
    ";
    
    $db->exec($sql4);
    echo "✓ Inserted sample contractor bank details\n";
    
    echo "\n✅ Alternative payment tables created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>