<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Checking users table structure...\n";
    
    $result = $db->query('DESCRIBE users');
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\nChecking existing users...\n";
    $users = $db->query('SELECT id, first_name, last_name, email, role FROM users LIMIT 5');
    while ($user = $users->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}, Role: {$user['role']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>