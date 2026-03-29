<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== CHECKING PAYMENT ID 16 ===\n";
    
    // Check if payment ID 16 exists
    $stmt = $db->prepare('SELECT * FROM stage_payment_requests WHERE id = 16');
    $stmt->execute();
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "Payment ID 16 found:\n";
        echo "Homeowner ID: " . $payment['homeowner_id'] . "\n";
        echo "Contractor ID: " . $payment['contractor_id'] . "\n";
        echo "Project ID: " . $payment['project_id'] . "\n";
        echo "Amount: " . $payment['requested_amount'] . "\n";
        echo "Status: " . $payment['status'] . "\n";
        echo "Created: " . $payment['created_at'] . "\n";
        echo "Full details: " . json_encode($payment, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Payment ID 16 NOT FOUND\n";
        
        // Check what payment IDs exist
        $stmt = $db->prepare('SELECT id, homeowner_id, contractor_id, requested_amount, status FROM stage_payment_requests ORDER BY id DESC LIMIT 10');
        $stmt->execute();
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Recent payment IDs:\n";
        foreach ($payments as $p) {
            echo "ID: {$p['id']}, Homeowner: {$p['homeowner_id']}, Amount: {$p['requested_amount']}, Status: {$p['status']}\n";
        }
    }
    
    echo "\n=== CHECKING HOMEOWNER ID 32 ===\n";
    // Also check if homeowner ID 32 exists
    $stmt = $db->prepare('SELECT id, first_name, last_name, email, role FROM users WHERE id = 32');
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Homeowner ID 32 found: {$user['first_name']} {$user['last_name']} ({$user['email']}) - Role: {$user['role']}\n";
    } else {
        echo "Homeowner ID 32 NOT FOUND\n";
    }
    
    echo "\n=== CHECKING ALL PAYMENTS FOR HOMEOWNER 32 ===\n";
    $stmt = $db->prepare('SELECT id, project_id, requested_amount, status, created_at FROM stage_payment_requests WHERE homeowner_id = 32 ORDER BY id DESC');
    $stmt->execute();
    $userPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($userPayments) {
        echo "Payments for homeowner 32:\n";
        foreach ($userPayments as $p) {
            echo "Payment ID: {$p['id']}, Project: {$p['project_id']}, Amount: {$p['requested_amount']}, Status: {$p['status']}, Created: {$p['created_at']}\n";
        }
    } else {
        echo "No payments found for homeowner 32\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>