<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Available tables:\n";
    foreach($tables as $table) {
        echo "- $table\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>