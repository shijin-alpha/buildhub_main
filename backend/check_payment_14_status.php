<?php
/**
 * Check current status of Payment Request ID 14 (₹250)
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== PAYMENT REQUEST ID 14 STATUS ===\n\n";
    
    // Check payment request
    $stmt = $db->prepare("SELECT * FROM stage_payment_requests WHERE id = 14");
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Payment Request:\n";
    echo "ID: " . $request['id'] . "\n";
    echo "Stage: " . $request['stage_name'] . "\n";
    echo "Amount: ₹" . number_format($request['requested_amount'], 2) . "\n";
    echo "Status: " . $request['status'] . "\n";
    echo "Homeowner ID: " . $request['homeowner_id'] . "\n";
    echo "Contractor ID: " . $request['contractor_id'] . "\n\n";
    
    // Check alternative payments
    $altStmt = $db->prepare("
        SELECT * FROM alternative_payments 
        WHERE reference_id = 14 AND payment_type = 'stage_payment'
        ORDER BY created_at DESC
    ");
    $altStmt->execute();
    $altPayments = $altStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Alternative Payments:\n";
    if (empty($altPayments)) {
        echo "No alternative payments found\n\n";
    } else {
        foreach ($altPayments as $alt) {
            echo "ID: " . $alt['id'] . " | ";
            echo "Method: " . $alt['payment_method'] . " | ";
            echo "Status: " . $alt['payment_status'] . " | ";
            echo "Verification: " . $alt['verification_status'] . " | ";
            echo "Created: " . $alt['created_at'] . "\n";
        }
        echo "\n";
    }
    
    // Check Razorpay transactions
    $rzpStmt = $db->prepare("
        SELECT * FROM stage_payment_transactions 
        WHERE payment_request_id = 14
        ORDER BY created_at DESC
    ");
    $rzpStmt->execute();
    $rzpPayments = $rzpStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Razorpay Transactions:\n";
    if (empty($rzpPayments)) {
        echo "No Razorpay transactions found\n\n";
    } else {
        foreach ($rzpPayments as $rzp) {
            echo "ID: " . $rzp['id'] . " | ";
            echo "Amount: ₹" . number_format($rzp['amount'], 2) . " | ";
            echo "Order ID: " . $rzp['razorpay_order_id'] . " | ";
            echo "Status: " . $rzp['payment_status'] . " | ";
            echo "Created: " . $rzp['created_at'] . "\n";
        }
        echo "\n";
    }
    
    // Summary
    echo "=== SUMMARY ===\n";
    echo "Payment Request Status: " . $request['status'] . "\n";
    echo "Alternative Payments: " . count($altPayments) . "\n";
    echo "Razorpay Transactions: " . count($rzpPayments) . "\n";
    
    // Check for blocking payments
    $blockingAlt = array_filter($altPayments, function($p) {
        return in_array($p['payment_status'], ['initiated', 'pending']);
    });
    
    if (!empty($blockingAlt)) {
        echo "\n⚠️ WARNING: " . count($blockingAlt) . " alternative payment(s) in initiated/pending status may block Razorpay!\n";
        echo "These should be automatically cancelled when initiating Razorpay payment.\n";
    } else {
        echo "\n✅ No blocking alternative payments found. Razorpay should work!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
