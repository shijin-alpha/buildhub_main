<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->query('DESCRIBE layout_requests');
    echo "layout_requests table structure:\n";
    while ($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>