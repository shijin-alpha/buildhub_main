<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== TESTING RECEIPT UPLOAD FIX ===\n";
    
    // Test the payment lookup logic from the upload API
    $payment_id = 16;
    $homeowner_id = 32;
    
    echo "Testing payment lookup for Payment ID: $payment_id, Homeowner ID: $homeowner_id\n";
    
    // This is the same query used in the upload API
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
        echo "✅ Payment found successfully!\n";
        echo "  Payment ID: {$payment['id']}\n";
        echo "  Homeowner ID: {$payment['homeowner_id']}\n";
        echo "  Stage: {$payment['stage_name']}\n";
        echo "  Amount: ₹{$payment['requested_amount']}\n";
        echo "  Status: {$payment['status']}\n";
        echo "  Project ID: {$payment['project_id']}\n";
        echo "  Contractor ID: {$payment['contractor_id']}\n";
        
        echo "\n✅ Receipt upload should now work for payment ID 16!\n";
    } else {
        echo "❌ Payment still not found. Checking debug info...\n";
        
        // Debug information
        $debugStmt = $db->prepare("
            SELECT id, homeowner_id, contractor_id, requested_amount, status 
            FROM stage_payment_requests 
            WHERE id = :payment_id
        ");
        $debugStmt->execute([':payment_id' => $payment_id]);
        $debugPayment = $debugStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($debugPayment) {
            echo "Payment exists but belongs to homeowner {$debugPayment['homeowner_id']}, not $homeowner_id\n";
        } else {
            echo "Payment ID $payment_id does not exist at all\n";
        }
    }
    
    // Also test the session establishment
    echo "\n=== TESTING SESSION ESTABLISHMENT ===\n";
    session_start();
    
    // Simulate the session establishment logic
    if ($payment) {
        $_SESSION['user_id'] = $payment['homeowner_id'];
        $_SESSION['user_role'] = 'homeowner';
        $_SESSION['logged_in'] = true;
        
        echo "✅ Session established:\n";
        echo "  User ID: {$_SESSION['user_id']}\n";
        echo "  Role: {$_SESSION['user_role']}\n";
        echo "  Logged in: " . ($_SESSION['logged_in'] ? 'Yes' : 'No') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>