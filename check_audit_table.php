<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Immutable Payment Audit Ledger columns:\n";
    $stmt = $db->query('DESCRIBE immutable_payment_audit_ledger');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }

    echo "\nSample data:\n";
    $stmt = $db->query('SELECT * FROM immutable_payment_audit_ledger LIMIT 2');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Block #{$row['block_number']}: Payment {$row['payment_id']}, Project {$row['project_id']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}