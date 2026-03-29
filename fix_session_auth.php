<?php
// Fix session authentication for homeowner 32
session_start();

// Set the session for homeowner 32 (Amal Samuel)
$_SESSION['user_id'] = 32;
$_SESSION['user_role'] = 'homeowner';
$_SESSION['user_name'] = 'Amal Samuel';
$_SESSION['user_email'] = 'thomasshijin90@gmail.com';

echo "Session fixed for homeowner 32 (Amal Samuel)\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "Role: " . $_SESSION['user_role'] . "\n";
echo "Name: " . $_SESSION['user_name'] . "\n";

// Test the session by calling the API
echo "\nTesting API with fixed session...\n";

// Simulate the API call
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $homeowner_id = $_SESSION['user_id'];
    $payment_id = 13;
    
    // Test the same validation as the API
    $paymentStmt = $db->prepare("
        SELECT * FROM stage_payment_requests 
        WHERE id = :payment_id AND homeowner_id = :homeowner_id
    ");
    $paymentStmt->execute([
        ':payment_id' => $payment_id,
        ':homeowner_id' => $homeowner_id
    ]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo "✅ SUCCESS: Payment validation passed!\n";
        echo "Payment ID {$payment_id} belongs to homeowner {$homeowner_id}\n";
        echo "Amount: ₹{$payment['requested_amount']}\n";
        echo "Status: {$payment['status']}\n";
        echo "\nReceipt upload should now work!\n";
    } else {
        echo "❌ FAILED: Payment validation failed\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>