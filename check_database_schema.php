<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Stage Payment Requests columns:\n";
    $stmt = $db->query('DESCRIBE stage_payment_requests');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }

    echo "\nLayout Requests columns:\n";
    $stmt = $db->query('DESCRIBE layout_requests');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }

    echo "\nUsers columns:\n";
    $stmt = $db->query('DESCRIBE users');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}