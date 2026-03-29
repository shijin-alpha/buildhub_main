<?php
// Test receipt upload after login
session_start();

echo "=== TESTING RECEIPT UPLOAD AFTER LOGIN ===\n\n";

// Check session
echo "Session Status:\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "\n";
echo "Name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n";
echo "Session ID: " . session_id() . "\n\n";

if (!isset($_SESSION['user_id'])) {
    echo "❌ No session found. Please run quick_login_homeowner.php first\n";
    exit;
}

// Test the API validation
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $homeowner_id = $_SESSION['user_id'];
    $payment_id = 13;
    
    echo "Testing payment validation:\n";
    echo "Homeowner ID: $homeowner_id\n";
    echo "Payment ID: $payment_id\n\n";
    
    // Same validation as the API
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
        echo "✅ SUCCESS! Payment validation passed\n";
        echo "Payment Details:\n";
        echo "- ID: {$payment['id']}\n";
        echo "- Amount: ₹{$payment['requested_amount']}\n";
        echo "- Status: {$payment['status']}\n";
        echo "- Stage: {$payment['stage_name']}\n";
        echo "- Homeowner: {$payment['homeowner_id']}\n";
        echo "- Contractor: {$payment['contractor_id']}\n\n";
        
        echo "🎉 RECEIPT UPLOAD SHOULD NOW WORK!\n";
        echo "Go to your browser and try uploading the receipt for Payment ID #13\n";
    } else {
        echo "❌ FAILED! Payment validation failed\n";
        
        // Debug info
        $debugStmt = $db->prepare("SELECT * FROM stage_payment_requests WHERE id = ?");
        $debugStmt->execute([$payment_id]);
        $debugPayment = $debugStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($debugPayment) {
            echo "Payment exists but belongs to homeowner: {$debugPayment['homeowner_id']}\n";
            echo "Expected homeowner: $homeowner_id\n";
        } else {
            echo "Payment ID $payment_id does not exist\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>