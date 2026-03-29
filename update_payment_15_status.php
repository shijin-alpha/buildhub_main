<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Update payment 15 status to approved
    $pdo->exec("UPDATE stage_payment_requests SET status = 'approved' WHERE id = 15");
    
    echo "✅ Updated payment 15 status to 'approved' for receipt upload\n";
    
    // Verify
    $check = $pdo->prepare("SELECT id, homeowner_id, status, requested_amount FROM stage_payment_requests WHERE id = 15");
    $check->execute();
    $payment = $check->fetch(PDO::FETCH_ASSOC);
    
    echo "Payment 15 details:\n";
    echo "ID: {$payment['id']}\n";
    echo "Homeowner: {$payment['homeowner_id']}\n";
    echo "Status: {$payment['status']}\n";
    echo "Amount: ₹{$payment['requested_amount']}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>