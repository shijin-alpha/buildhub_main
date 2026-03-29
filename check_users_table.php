<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Users table structure:\n";
    $stmt = $db->prepare('DESCRIBE users');
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\nSample users:\n";
    $stmt = $db->prepare('SELECT id, first_name, last_name, email, role FROM users LIMIT 5');
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($users as $user) {
        echo "ID: " . $user['id'] . ", Name: " . $user['first_name'] . " " . $user['last_name'] . ", Role: " . ($user['role'] ?? 'N/A') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
</content>