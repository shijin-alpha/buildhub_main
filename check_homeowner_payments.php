<?php
require_once 'backend/config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check what payment requests exist for homeowner 28 vs 32
    echo "=== PAYMENT REQUESTS FOR HOMEOWNER 28 ===\n";
    $stmt = $db->prepare('SELECT id, requested_amount, status FROM stage_payment_requests WHERE homeowner_id = 28 ORDER BY id DESC LIMIT 5');
    $stmt->execute();
    $payments28 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($payments28 as $p) {
        echo "ID: {$p['id']}, Amount: {$p['requested_amount']}, Status: {$p['status']}\n";
    }
    
    echo "\n=== PAYMENT REQUESTS FOR HOMEOWNER 32 ===\n";
    $stmt = $db->prepare('SELECT id, requested_amount, status FROM stage_payment_requests WHERE homeowner_id = 32 ORDER BY id DESC LIMIT 5');
    $stmt->execute();
    $payments32 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($payments32 as $p) {
        echo "ID: {$p['id']}, Amount: {$p['requested_amount']}, Status: {$p['status']}\n";
    }
    
    // Check if payment ID 16 exists for homeowner 28
    echo "\n=== CHECKING PAYMENT ID 16 FOR HOMEOWNER 28 ===\n";
    $stmt = $db->prepare('SELECT * FROM stage_payment_requests WHERE id = 16 AND homeowner_id = 28');
    $stmt->execute();
    $payment16_28 = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($payment16_28) {
        echo "Payment ID 16 found for homeowner 28: Amount {$payment16_28['requested_amount']}, Status: {$payment16_28['status']}\n";
    } else {
        echo "Payment ID 16 NOT found for homeowner 28\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>