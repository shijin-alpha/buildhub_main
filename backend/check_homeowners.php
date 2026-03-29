<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->query('SELECT id, first_name, last_name, role FROM users WHERE role = "homeowner" LIMIT 5');
    echo "Available homeowners:\n";
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['first_name']} {$row['last_name']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>