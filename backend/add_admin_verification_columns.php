<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Adding admin verification columns to stage_payment_requests table...\n";
    
    // Add columns for admin verification
    $alterQueries = [
        "ALTER TABLE stage_payment_requests ADD COLUMN IF NOT EXISTS admin_verified BOOLEAN DEFAULT FALSE",
        "ALTER TABLE stage_payment_requests ADD COLUMN IF NOT EXISTS admin_verified_by VARCHAR(100) DEFAULT NULL", 
        "ALTER TABLE stage_payment_requests ADD COLUMN IF NOT EXISTS admin_verified_at TIMESTAMP NULL DEFAULT NULL",
        "ALTER TABLE stage_payment_requests ADD COLUMN IF NOT EXISTS admin_notes TEXT DEFAULT NULL"
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
    
    // Update verification_status enum to include admin statuses
    try {
        $updateEnumQuery = "
            ALTER TABLE stage_payment_requests 
            MODIFY COLUMN verification_status ENUM('pending', 'verified', 'rejected', 'admin_approved', 'admin_rejected') DEFAULT NULL
        ";
        $db->exec($updateEnumQuery);
        echo "✅ Updated verification_status enum to include admin statuses\n";
    } catch (Exception $e) {
        echo "ℹ️  Verification status enum might already be updated: " . $e->getMessage() . "\n";
    }
    
    // Create admin verification logs table if it doesn't exist
    $createAdminLogTable = "
        CREATE TABLE IF NOT EXISTS admin_payment_verification_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_request_id INT NOT NULL,
            admin_username VARCHAR(100) NOT NULL,
            action ENUM('admin_approved', 'admin_rejected', 'reviewed') NOT NULL,
            notes TEXT,
            previous_status VARCHAR(50),
            new_status VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (payment_request_id) REFERENCES stage_payment_requests(id) ON DELETE CASCADE
        )
    ";
    
    $db->exec($createAdminLogTable);
    echo "✅ Created admin_payment_verification_logs table\n";
    
    // Create admin notifications table for payment verifications
    $createAdminNotificationTable = "
        CREATE TABLE IF NOT EXISTS admin_payment_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_request_id INT NOT NULL,
            admin_username VARCHAR(100) NOT NULL,
            recipient_id INT NOT NULL,
            recipient_type ENUM('homeowner', 'contractor') NOT NULL,
            notification_type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (payment_request_id) REFERENCES stage_payment_requests(id) ON DELETE CASCADE
        )
    ";
    
    $db->exec($createAdminNotificationTable);
    echo "✅ Created admin_payment_notifications table\n";
    
    echo "\n🎉 Successfully added admin verification columns and tables!\n";
    echo "\nNew admin verification features:\n";
    echo "- admin_verified: Boolean flag for admin verification status\n";
    echo "- admin_verified_by: Admin username who verified the payment\n";
    echo "- admin_verified_at: Timestamp of admin verification\n";
    echo "- admin_notes: Admin notes for verification decision\n";
    echo "- verification_status: Updated to include 'admin_approved' and 'admin_rejected'\n";
    echo "- admin_payment_verification_logs: Audit trail for admin actions\n";
    echo "- admin_payment_notifications: Notifications sent by admin\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>