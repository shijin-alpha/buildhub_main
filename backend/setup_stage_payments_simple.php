<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Setting up Stage Payment Management System...\n";
    
    // 1. Create construction_stage_payments table
    $db->exec("
        CREATE TABLE IF NOT EXISTS construction_stage_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            stage_name VARCHAR(100) NOT NULL,
            stage_order INT NOT NULL,
            typical_percentage DECIMAL(5,2) DEFAULT 0.00,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_stage_name (stage_name),
            INDEX idx_stage_order (stage_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created construction_stage_payments table\n";
    
    // 2. Create project_stage_payment_requests table
    $db->exec("
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
            progress_update_id INT NULL,
            payment_method VARCHAR(50) NULL,
            transaction_id VARCHAR(255) NULL,
            
            INDEX idx_project_stage (project_id, stage_name),
            INDEX idx_contractor_status (contractor_id, status),
            INDEX idx_homeowner_status (homeowner_id, status),
            INDEX idx_request_date (request_date),
            INDEX idx_status_date (status, request_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created project_stage_payment_requests table\n";
    
    // 3. Create project_payment_schedule table
    $db->exec("
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
            
            INDEX idx_project_schedule (project_id, stage_name),
            INDEX idx_due_date (due_date),
            INDEX idx_completion (is_completed, due_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created project_payment_schedule table\n";
    
    // 4. Create payment_notifications table
    $db->exec("
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
            
            INDEX idx_recipient_unread (recipient_id, is_read),
            INDEX idx_notification_type (notification_type, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Created payment_notifications table\n";
    
    // Insert default construction stage payment structure
    $stages = [
        ['Site Preparation', 1, 5.00, 'Initial site setup, clearing, and preparation work'],
        ['Foundation', 2, 20.00, 'Foundation excavation, concrete work, and structural base'],
        ['Structure', 3, 25.00, 'Main structural work including columns, beams, and slabs'],
        ['Brickwork', 4, 15.00, 'Wall construction and masonry work'],
        ['Roofing', 5, 10.00, 'Roof structure and covering installation'],
        ['Electrical', 6, 8.00, 'Electrical wiring and installations'],
        ['Plumbing', 7, 7.00, 'Plumbing installations and pipe work'],
        ['Finishing', 8, 8.00, 'Plastering, painting, and interior finishing'],
        ['Final Inspection', 9, 2.00, 'Quality checks, cleanup, and project handover']
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO construction_stage_payments (stage_name, stage_order, typical_percentage, description) VALUES (?, ?, ?, ?)");
    foreach ($stages as $stage) {
        $stmt->execute($stage);
    }
    echo "✓ Inserted construction stage payment structures\n";
    
    echo "Stage Payment Management System setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error setting up Stage Payment Management System: " . $e->getMessage() . "\n";
}
?>