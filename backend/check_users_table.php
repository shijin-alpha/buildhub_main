<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Users table structure:\n";
    $stmt = $db->query('DESCRIBE users');
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\nSample users:\n";
    $stmt = $db->query('SELECT id, first_name, last_name, email FROM users LIMIT 5');
    while($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Name: {$row['first_name']} {$row['last_name']}, Email: {$row['email']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>