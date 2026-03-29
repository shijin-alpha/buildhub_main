<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->query('DESCRIBE stage_payment_requests');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "stage_payment_requests columns:\n";
    foreach($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>