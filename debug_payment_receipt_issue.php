<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== Payment Receipt Upload Debug ===\n\n";
    
    // Check if there are any stage_payment_requests
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM stage_payment_requests");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total stage_payment_requests: {$count['count']}\n\n";
    
    if ($count['count'] > 0) {
        // Show sample payment requests
        $stmt = $pdo->query("SELECT id, homeowner_id, contractor_id, requested_amount, status, created_at FROM stage_payment_requests ORDER BY id DESC LIMIT 5");
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Recent payment requests:\n";
        foreach ($payments as $payment) {
            echo "ID: {$payment['id']}, Homeowner: {$payment['homeowner_id']}, Contractor: {$payment['contractor_id']}, Amount: {$payment['requested_amount']}, Status: {$payment['status']}\n";
        }
        echo "\n";
    }
    
    // Check users table for homeowner ID 32
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, role FROM users WHERE id = 32");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "User ID 32 details:\n";
        echo "Name: {$user['first_name']} {$user['last_name']}\n";
        echo "Email: {$user['email']}\n";
        echo "Role: {$user['role']}\n\n";
        
        // Check payments for this homeowner
        $stmt = $pdo->prepare("SELECT id, contractor_id, requested_amount, status FROM stage_payment_requests WHERE homeowner_id = 32");
        $stmt->execute();
        $homeowner_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Payments for homeowner ID 32:\n";
        if (empty($homeowner_payments)) {
            echo "No payments found for homeowner ID 32\n";
        } else {
            foreach ($homeowner_payments as $payment) {
                echo "Payment ID: {$payment['id']}, Contractor: {$payment['contractor_id']}, Amount: {$payment['requested_amount']}, Status: {$payment['status']}\n";
            }
        }
    } else {
        echo "User ID 32 not found\n";
    }
    
    echo "\n=== Checking Alternative Payment Tables ===\n";
    
    // Check custom_payment_requests table
    $stmt = $pdo->query("SHOW TABLES LIKE 'custom_payment_requests'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM custom_payment_requests");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "custom_payment_requests table exists with {$count['count']} records\n";
        
        if ($count['count'] > 0) {
            $stmt = $pdo->query("SELECT id, homeowner_id, amount, status FROM custom_payment_requests LIMIT 5");
            $custom_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Sample custom payment requests:\n";
            foreach ($custom_payments as $payment) {
                echo "ID: {$payment['id']}, Homeowner: {$payment['homeowner_id']}, Amount: {$payment['amount']}, Status: {$payment['status']}\n";
            }
        }
    } else {
        echo "custom_payment_requests table does not exist\n";
    }
    
    // Check payment_requests table
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_requests'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM payment_requests");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "payment_requests table exists with {$count['count']} records\n";
        
        if ($count['count'] > 0) {
            $stmt = $pdo->query("SELECT id, homeowner_id, amount, status FROM payment_requests WHERE homeowner_id = 32 LIMIT 5");
            $payment_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Payment requests for homeowner 32:\n";
            foreach ($payment_requests as $payment) {
                echo "ID: {$payment['id']}, Homeowner: {$payment['homeowner_id']}, Amount: {$payment['amount']}, Status: {$payment['status']}\n";
            }
        }
    } else {
        echo "payment_requests table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>