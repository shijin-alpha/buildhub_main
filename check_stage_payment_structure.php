<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== stage_payment_requests table structure ===\n";
    $stmt = $pdo->query('DESCRIBE stage_payment_requests');
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>