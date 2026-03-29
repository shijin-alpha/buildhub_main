<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== INSPECTION REPORTS TABLE STRUCTURE ===\n\n";
    
    $stmt = $db->query('DESCRIBE inspection_reports');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "inspection_reports columns:\n";
    foreach ($columns as $col) {
        echo "  {$col['Field']} - {$col['Type']}\n";
    }
    
    echo "\nTotal columns: " . count($columns) . "\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>