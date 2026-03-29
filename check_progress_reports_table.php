<?php
require_once 'backend/config/database.php';

try {
    // Check if progress_reports table exists
    $stmt = $db->query("SHOW TABLES LIKE 'progress_reports'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ progress_reports table exists\n\n";
        
        // Show table structure
        echo "Table structure:\n";
        $stmt = $db->query('DESCRIBE progress_reports');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
        
        // Show sample data
        echo "\nSample data:\n";
        $stmt = $db->query('SELECT id, contractor_id, project_id, report_type, status, created_at FROM progress_reports LIMIT 5');
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($data)) {
            echo "❌ No data found in progress_reports table\n";
        } else {
            foreach ($data as $row) {
                echo "ID: {$row['id']}, Contractor: {$row['contractor_id']}, Project: {$row['project_id']}, Type: {$row['report_type']}, Status: {$row['status']}, Created: {$row['created_at']}\n";
            }
        }
        
        // Count total reports
        $stmt = $db->query('SELECT COUNT(*) as total FROM progress_reports');
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "\nTotal reports in database: {$total}\n";
        
    } else {
        echo "❌ progress_reports table does not exist!\n";
        
        // Show all tables
        echo "\nAvailable tables:\n";
        $stmt = $db->query('SHOW TABLES');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            echo "- {$table}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>