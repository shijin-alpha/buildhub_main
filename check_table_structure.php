<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== Layout Requests Table Structure ===\n";
    $stmt = $pdo->query('DESCRIBE layout_requests');
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\n=== Construction Projects Table Structure ===\n";
    $stmt = $pdo->query('DESCRIBE construction_projects');
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>