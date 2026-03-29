<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Creating stage_document_requirements table...\n";
    
    // Create the table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS `stage_document_requirements` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `project_id` int(11) DEFAULT NULL,
      `stage_name` varchar(100) NOT NULL,
      `document_type` enum('receipt', 'bill', 'invoice', 'material_certificate', 'quality_report', 'safety_certificate', 'permit', 'inspection_report', 'other') NOT NULL,
      `is_required` tinyint(1) DEFAULT 0,
      `description` text DEFAULT NULL,
      `accepted_formats` varchar(255) DEFAULT 'pdf,jpg,jpeg,png,doc,docx',
      `max_file_size` int(11) DEFAULT 10485760,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `idx_project_stage` (`project_id`, `stage_name`),
      KEY `idx_stage_document` (`stage_name`, `document_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $pdo->exec($createTableSQL);
    echo "✓ Table created successfully\n";
    
    // Check if data already exists
    $checkData = $pdo->query("SELECT COUNT(*) as count FROM stage_document_requirements");
    $count = $checkData->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "Inserting default document requirements...\n";
        
        // Insert default data
        $insertSQL = "
        INSERT INTO `stage_document_requirements` (`stage_name`, `document_type`, `is_required`, `description`, `accepted_formats`, `max_file_size`) VALUES
        ('Foundation', 'receipt', 1, 'Material purchase receipts for foundation work', 'pdf,jpg,jpeg,png', 5242880),
        ('Foundation', 'bill', 1, 'Labor and equipment bills for foundation', 'pdf,jpg,jpeg,png', 5242880),
        ('Foundation', 'material_certificate', 1, 'Cement and steel quality certificates', 'pdf,jpg,jpeg,png', 5242880),
        ('Foundation', 'safety_certificate', 0, 'Safety compliance certificates', 'pdf', 5242880),
        ('Structure', 'receipt', 1, 'Material receipts for structural work', 'pdf,jpg,jpeg,png', 5242880),
        ('Structure', 'bill', 1, 'Labor bills for structural work', 'pdf,jpg,jpeg,png', 5242880),
        ('Structure', 'material_certificate', 1, 'Steel and concrete quality certificates', 'pdf,jpg,jpeg,png', 5242880),
        ('Structure', 'inspection_report', 0, 'Structural inspection reports', 'pdf', 5242880),
        ('Brickwork', 'receipt', 1, 'Brick and mortar purchase receipts', 'pdf,jpg,jpeg,png', 5242880),
        ('Brickwork', 'bill', 1, 'Mason labor bills', 'pdf,jpg,jpeg,png', 5242880),
        ('Brickwork', 'material_certificate', 0, 'Brick quality certificates', 'pdf,jpg,jpeg,png', 5242880),
        ('Roofing', 'receipt', 1, 'Roofing material receipts', 'pdf,jpg,jpeg,png', 5242880),
        ('Roofing', 'bill', 1, 'Roofing labor bills', 'pdf,jpg,jpeg,png', 5242880),
        ('Roofing', 'material_certificate', 0, 'Roofing material quality certificates', 'pdf,jpg,jpeg,png', 5242880),
        ('Electrical', 'receipt', 1, 'Electrical material receipts', 'pdf,jpg,jpeg,png', 5242880),
        ('Electrical', 'bill', 1, 'Electrician labor bills', 'pdf,jpg,jpeg,png', 5242880),
        ('Electrical', 'safety_certificate', 1, 'Electrical safety certificates', 'pdf', 5242880),
        ('Electrical', 'inspection_report', 0, 'Electrical inspection reports', 'pdf', 5242880),
        ('Plumbing', 'receipt', 1, 'Plumbing material receipts', 'pdf,jpg,jpeg,png', 5242880),
        ('Plumbing', 'bill', 1, 'Plumber labor bills', 'pdf,jpg,jpeg,png', 5242880),
        ('Plumbing', 'inspection_report', 0, 'Plumbing inspection reports', 'pdf', 5242880),
        ('Finishing', 'receipt', 1, 'Finishing material receipts', 'pdf,jpg,jpeg,png', 5242880),
        ('Finishing', 'bill', 1, 'Finishing work labor bills', 'pdf,jpg,jpeg,png', 5242880),
        ('Finishing', 'quality_report', 0, 'Finishing quality reports', 'pdf', 5242880);
        ";
        
        $pdo->exec($insertSQL);
        echo "✓ Default document requirements inserted\n";
    } else {
        echo "✓ Document requirements already exist ($count records)\n";
    }
    
    // Verify the table
    $verify = $pdo->query("SELECT COUNT(*) as count FROM stage_document_requirements");
    $finalCount = $verify->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "\n=== Table Creation Complete ===\n";
    echo "Total document requirements: $finalCount\n";
    
    // Show some sample data
    echo "\nSample document requirements:\n";
    $sample = $pdo->query("SELECT stage_name, document_type, is_required, description FROM stage_document_requirements LIMIT 5");
    while ($row = $sample->fetch(PDO::FETCH_ASSOC)) {
        $required = $row['is_required'] ? 'Required' : 'Optional';
        echo "- {$row['stage_name']}: {$row['document_type']} ($required)\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>