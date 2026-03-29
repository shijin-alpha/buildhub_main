<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Adding receipt-related columns to stage_payment_requests table...\n";
    
    // Add columns for receipt storage
    $alterQueries = [
        "ALTER TABLE stage_payment_requests ADD COLUMN IF NOT EXISTS transaction_reference VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE stage_payment_requests ADD COLUMN IF NOT EXISTS payment_date DATE DEFAULT NULL", 
        "ALTER TABLE stage_payment_requests ADD COLUMN IF NOT EXISTS receipt_file_path TEXT DEFAULT NULL",
        "ALTER TABLE stage_payment_requests ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE stage_payment_requests ADD COLUMN IF NOT EXISTS verification_status ENUM('pending', 'verified', 'rejected') DEFAULT NULL",
        "ALTER TABLE stage_payment_requests ADD COLUMN IF NOT EXISTS verified_by INT DEFAULT NULL",
        "ALTER TABLE stage_payment_requests ADD COLUMN IF NOT EXISTS verified_at TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE stage_payment_requests ADD COLUMN IF NOT EXISTS verification_notes TEXT DEFAULT NULL"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $db->exec($query);
            echo "✅ Executed: " . substr($query, 0, 80) . "...\n";
        } catch (Exception $e) {
            // Column might already exist, check if it's a duplicate column error
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "ℹ️  Column already exists: " . substr($query, 0, 80) . "...\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Create payment verification logs table if it doesn't exist
    $createVerificationTable = "
        CREATE TABLE IF NOT EXISTS stage_payment_verification_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_request_id INT NOT NULL,
            verifier_id INT NOT NULL,
            verifier_type ENUM('homeowner', 'contractor', 'admin') NOT NULL,
            action ENUM('submitted', 'verified', 'rejected', 'updated') NOT NULL,
            comments TEXT,
            attached_files JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (payment_request_id) REFERENCES stage_payment_requests(id) ON DELETE CASCADE
        )
    ";
    
    $db->exec($createVerificationTable);
    echo "✅ Created stage_payment_verification_logs table\n";
    
    // Create notifications table for stage payments if it doesn't exist
    $createNotificationTable = "
        CREATE TABLE IF NOT EXISTS stage_payment_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_request_id INT NOT NULL,
            recipient_id INT NOT NULL,
            recipient_type ENUM('homeowner', 'contractor', 'admin') NOT NULL,
            notification_type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (payment_request_id) REFERENCES stage_payment_requests(id) ON DELETE CASCADE
        )
    ";
    
    $db->exec($createNotificationTable);
    echo "✅ Created stage_payment_notifications table\n";
    
    echo "\n🎉 Successfully added receipt-related columns and tables!\n";
    echo "\nNew columns added to stage_payment_requests:\n";
    echo "- transaction_reference: Store payment reference number\n";
    echo "- payment_date: Date when payment was made\n";
    echo "- receipt_file_path: JSON array of uploaded receipt files\n";
    echo "- payment_method: Method used for payment (bank_transfer, upi, etc.)\n";
    echo "- verification_status: Status of receipt verification\n";
    echo "- verified_by: ID of user who verified the payment\n";
    echo "- verified_at: Timestamp of verification\n";
    echo "- verification_notes: Notes from verifier\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>