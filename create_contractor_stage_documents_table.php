<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Creating contractor_stage_documents table...\n";
    
    // Create the table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS `contractor_stage_documents` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `project_id` int(11) NOT NULL,
      `contractor_id` int(11) NOT NULL,
      `stage_name` varchar(100) NOT NULL,
      `document_type` enum('receipt', 'bill', 'invoice', 'material_certificate', 'quality_report', 'safety_certificate', 'permit', 'inspection_report', 'other') NOT NULL,
      `document_category` enum('stage_specific', 'project_wide') DEFAULT 'stage_specific',
      `file_path` varchar(500) NOT NULL,
      `original_filename` varchar(255) NOT NULL,
      `file_size` int(11) NOT NULL,
      `mime_type` varchar(100) NOT NULL,
      `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
      `uploaded_by` int(11) NOT NULL,
      `description` text DEFAULT NULL,
      `verification_status` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
      `verified_by` int(11) DEFAULT NULL,
      `verified_at` timestamp NULL DEFAULT NULL,
      `verification_notes` text DEFAULT NULL,
      `is_mandatory` tinyint(1) DEFAULT 0,
      `related_payment_id` int(11) DEFAULT NULL,
      `metadata` json DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `idx_project_contractor` (`project_id`, `contractor_id`),
      KEY `idx_stage_name` (`stage_name`),
      KEY `idx_document_type` (`document_type`),
      KEY `idx_verification_status` (`verification_status`),
      KEY `idx_related_payment` (`related_payment_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $pdo->exec($createTableSQL);
    echo "✓ contractor_stage_documents table created successfully\n";
    
    // Verify the table
    $verify = $pdo->query("SHOW TABLES LIKE 'contractor_stage_documents'");
    if ($verify->rowCount() > 0) {
        echo "✓ Table exists in database\n";
        
        // Show table structure
        echo "\nTable structure:\n";
        $structure = $pdo->query("DESCRIBE contractor_stage_documents");
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }
    } else {
        echo "✗ Table creation failed\n";
    }
    
    echo "\n=== Testing Upload API Dependencies ===\n";
    
    // Check if all required tables exist
    $requiredTables = [
        'contractor_stage_documents',
        'stage_document_requirements',
        'construction_projects',
        'users'
    ];
    
    foreach ($requiredTables as $table) {
        $check = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($check->rowCount() > 0) {
            echo "✓ $table exists\n";
        } else {
            echo "✗ $table missing\n";
        }
    }
    
    echo "\n=== Table Creation Complete ===\n";
    echo "Contractor document upload should now work without database errors.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>