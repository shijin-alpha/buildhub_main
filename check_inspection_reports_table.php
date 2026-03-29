<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== INSPECTION REPORTS TABLE STRUCTURE ===\n";
    $result = $db->query('DESCRIBE inspection_reports');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\n=== SAMPLE INSPECTION REPORTS DATA ===\n";
    $result = $db->query('SELECT * FROM inspection_reports ORDER BY created_at DESC LIMIT 5');
    $reports = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($reports)) {
        echo "No inspection reports found in database.\n";
    } else {
        foreach ($reports as $report) {
            echo "ID: " . $report['id'] . " | Project: " . $report['project_id'] . " | Date: " . $report['inspection_date'] . " | Status: " . $report['overall_status'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>