<?php
/**
 * Sync Payment ID 14 with Razorpay status
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $payment_request_id = 14;
    $razorpay_payment_id = 'pay_S3IrgoUzLJL7Gm';
    $razorpay_order_id = 'order_S3IrWKXRV403r4';
    
    echo "=== SYNCING PAYMENT ID 14 ===\n\n";
    
    // Start transaction
    $db->beginTransaction();
    
    // Update stage_payment_transactions
    $stmt1 = $db->prepare("
        UPDATE stage_payment_transactions 
        SET payment_status = 'completed',
            razorpay_payment_id = :payment_id,
            updated_at = NOW()
        WHERE razorpay_order_id = :order_id
    ");
    $stmt1->execute([
        ':payment_id' => $razorpay_payment_id,
        ':order_id' => $razorpay_order_id
    ]);
    echo "✅ Updated stage_payment_transactions (rows: " . $stmt1->rowCount() . ")\n";
    
    // Update stage_payment_requests
    $stmt2 = $db->prepare("
        UPDATE stage_payment_requests 
        SET status = 'paid',
            updated_at = NOW()
        WHERE id = :request_id
    ");
    $stmt2->execute([':request_id' => $payment_request_id]);
    echo "✅ Updated stage_payment_requests (rows: " . $stmt2->rowCount() . ")\n";
    
    // Commit transaction
    $db->commit();
    
    echo "\n✅ Payment synced successfully!\n\n";
    
    // Verify updates
    $verify = $db->prepare("
        SELECT spr.id, spr.stage_name, spr.requested_amount, spr.status,
               spt.razorpay_payment_id, spt.payment_status
        FROM stage_payment_requests spr
        LEFT JOIN stage_payment_transactions spt ON spr.id = spt.payment_request_id
        WHERE spr.id = :request_id
    ");
    $verify->execute([':request_id' => $payment_request_id]);
    $result = $verify->fetch(PDO::FETCH_ASSOC);
    
    echo "Verification:\n";
    echo "Payment Request ID: " . $result['id'] . "\n";
    echo "Stage: " . $result['stage_name'] . "\n";
    echo "Amount: ₹" . number_format($result['requested_amount'], 2) . "\n";
    echo "Request Status: " . $result['status'] . "\n";
    echo "Transaction Status: " . $result['payment_status'] . "\n";
    echo "Razorpay Payment ID: " . $result['razorpay_payment_id'] . "\n";
    
    echo "\n✅ Payment ID 14 is now marked as PAID!\n";
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
