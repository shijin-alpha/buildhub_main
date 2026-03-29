<?php
// Test the receipt upload API with the correct payment ID

// Simulate session
session_start();
$_SESSION['user_id'] = 32; // Homeowner ID

// Simulate POST data
$_POST = [
    'payment_id' => '24', // Use the payment ID we just created
    'transaction_reference' => 'TEST123456789',
    'payment_date' => '2026-02-01',
    'payment_method' => 'bank_transfer',
    'notes' => 'Test receipt upload'
];

// Simulate file upload (we'll skip actual file for this test)
$_FILES = [
    'receipt_files' => [
        'name' => ['test_receipt.jpg'],
        'type' => ['image/jpeg'],
        'size' => [1024000], // 1MB
        'tmp_name' => ['/tmp/test'],
        'error' => [UPLOAD_ERR_OK]
    ]
];

echo "Testing receipt upload API with:\n";
echo "Payment ID: {$_POST['payment_id']}\n";
echo "Homeowner ID: {$_SESSION['user_id']}\n";
echo "Transaction Reference: {$_POST['transaction_reference']}\n";
echo "Payment Date: {$_POST['payment_date']}\n";
echo "\n";

// Test the validation part of the API
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $homeowner_id = $_SESSION['user_id'];
    $payment_id = $_POST['payment_id'];
    
    // Verify payment belongs to homeowner
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
        echo "✅ Payment validation successful!\n";
        echo "Payment belongs to homeowner {$homeowner_id}\n";
        echo "Payment amount: ₹{$payment['requested_amount']}\n";
        echo "Payment status: {$payment['status']}\n";
        echo "\nThe API should work correctly with these parameters.\n";
    } else {
        echo "❌ Payment validation failed!\n";
        echo "Payment ID {$payment_id} not found for homeowner {$homeowner_id}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>