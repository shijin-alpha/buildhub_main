<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== CHECKING ESTIMATES TABLE STRUCTURE ===\n\n";
    
    $stmt = $db->query('DESCRIBE contractor_send_estimates');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "contractor_send_estimates columns:\n";
    foreach ($columns as $col) {
        echo "  {$col['Field']} - {$col['Type']}\n";
    }
    
    echo "\nSample data:\n";
    $stmt = $db->query('SELECT * FROM contractor_send_estimates LIMIT 3');
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($samples)) {
        echo "  No data found\n";
    } else {
        foreach ($samples as $sample) {
            echo "  ID: {$sample['id']}\n";
        }
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>