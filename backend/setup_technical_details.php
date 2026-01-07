<?php
// Setup script to add technical details support to house plans

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Setting up technical details support...\n";

    // Check if technical_details column exists
    $checkColumn = $db->query("SHOW COLUMNS FROM house_plans LIKE 'technical_details'");
    
    if ($checkColumn->rowCount() == 0) {
        echo "Adding technical_details column to house_plans table...\n";
        
        // Add the column
        $db->exec("ALTER TABLE house_plans ADD COLUMN technical_details JSON NULL AFTER plan_data");
        
        // Update existing plans
        $db->exec("UPDATE house_plans SET technical_details = '{}' WHERE technical_details IS NULL");
        
        echo "✓ Technical details column added successfully\n";
    } else {
        echo "✓ Technical details column already exists\n";
    }

    // Check if notifications table exists
    $checkNotifications = $db->query("SHOW TABLES LIKE 'notifications'");
    
    if ($checkNotifications->rowCount() == 0) {
        echo "Creating notifications table...\n";
        
        $db->exec("
            CREATE TABLE notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                related_id INT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_unread (user_id, is_read),
                INDEX idx_type (type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        echo "✓ Notifications table created successfully\n";
    } else {
        echo "✓ Notifications table already exists\n";
    }

    // Check if inbox_messages table exists
    $checkInbox = $db->query("SHOW TABLES LIKE 'inbox_messages'");
    
    if ($checkInbox->rowCount() == 0) {
        echo "Creating inbox_messages table...\n";
        
        $db->exec("
            CREATE TABLE inbox_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipient_id INT NOT NULL,
                sender_id INT NOT NULL,
                message_type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                metadata JSON NULL,
                priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                read_at TIMESTAMP NULL,
                FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_recipient_unread (recipient_id, is_read),
                INDEX idx_type (message_type),
                INDEX idx_priority (priority)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        echo "✓ Inbox messages table created successfully\n";
    } else {
        echo "✓ Inbox messages table already exists\n";
    }

    echo "\n=== Technical Details Setup Complete ===\n";
    echo "✓ Database schema updated\n";
    echo "✓ Technical details support enabled\n";
    echo "✓ Notification system ready\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>