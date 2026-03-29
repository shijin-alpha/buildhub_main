<?php
/**
 * Create Custom Payment Requests Table
 * Ensure the custom_payment_requests table exists in the database
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Creating Custom Payment Requests Table\n\n";
    
    // Create the custom_payment_requests table
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `custom_payment_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `project_id` int(11) NOT NULL,
            `contractor_id` int(11) NOT NULL,
            `homeowner_id` int(11) NOT NULL,
            `request_title` varchar(255) NOT NULL,
            `request_reason` text NOT NULL,
            `requested_amount` decimal(12,2) NOT NULL,
            `work_description` text NOT NULL,
            `urgency_level` enum('low','medium','high','urgent') DEFAULT 'medium',
            `category` varchar(100) DEFAULT NULL,
            `supporting_documents` text DEFAULT NULL,
            `contractor_notes` text DEFAULT NULL,
            `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
            `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
            `response_date` timestamp NULL DEFAULT NULL,
            `homeowner_notes` text DEFAULT NULL,
            `approved_amount` decimal(12,2) DEFAULT NULL,
            `rejection_reason` text DEFAULT NULL,
            `payment_date` date DEFAULT NULL,
            `transaction_reference` varchar(255) DEFAULT NULL,
            `receipt_file_path` text DEFAULT NULL,
            `payment_method` varchar(50) DEFAULT NULL,
            `verification_status` enum('pending','verified','rejected') DEFAULT NULL,
            `verified_by` int(11) DEFAULT NULL,
            `verified_at` timestamp NULL DEFAULT NULL,
            `verification_notes` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_project_contractor` (`project_id`,`contractor_id`),
            KEY `idx_status_date` (`status`,`request_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    
    $pdo->exec($createTableSQL);
    echo "✅ Custom payment requests table created successfully\n\n";
    
    // Check if table was created properly
    $stmt = $pdo->query("DESCRIBE custom_payment_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Table structure:\n";
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}\n";
    }
    echo "\n";
    
    // Check current data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM custom_payment_requests");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Current records: {$count['count']}\n\n";
    
    echo "✅ Custom payment requests system is ready!\n";
    echo "Contractors can now submit custom payment requests for additional work.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>