<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->query('SELECT COUNT(*) FROM worker_types');
    echo 'Worker types count: ' . $stmt->fetchColumn() . "\n";
    
    $stmt = $db->query('SELECT id, type_name FROM worker_types LIMIT 10');
    echo "Sample worker types:\n";
    while($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Type: {$row['type_name']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>