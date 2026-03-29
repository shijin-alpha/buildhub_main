<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->query('SELECT COUNT(*) as count FROM users WHERE role = "contractor"');
    $result = $stmt->fetch();
    echo "Contractors in database: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        $stmt = $db->query('SELECT id, first_name, last_name, email FROM users WHERE role = "contractor" LIMIT 3');
        echo "\nSample contractors:\n";
        while ($row = $stmt->fetch()) {
            echo "- ID: {$row['id']}, Name: {$row['first_name']} {$row['last_name']}, Email: {$row['email']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>