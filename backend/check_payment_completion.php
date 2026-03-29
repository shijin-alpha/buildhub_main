<?php
/**
 * Check if payment was completed but not recorded properly
 */

require_once 'config/database.php';
require_once 'config/razorpay_config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $order_id = 'order_S3IrWKXRV403r4';
    
    echo "=== CHECKING PAYMENT COMPLETION ===\n\n";
    
    // Get transaction from database
    $stmt = $db->prepare("SELECT * FROM stage_payment_transactions WHERE razorpay_order_id = :order_id");
    $stmt->execute([':order_id' => $order_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Database Transaction:\n";
    echo "ID: " . $transaction['id'] . "\n";
    echo "Payment Request ID: " . $transaction['payment_request_id'] . "\n";
    echo "Amount: ₹" . number_format($transaction['amount'], 2) . "\n";
    echo "Order ID: " . $transaction['razorpay_order_id'] . "\n";
    echo "Payment ID: " . ($transaction['razorpay_payment_id'] ?: 'NULL') . "\n";
    echo "Status: " . $transaction['payment_status'] . "\n\n";
    
    // Fetch payments for this order from Razorpay
    $url = "https://api.razorpay.com/v1/orders/$order_id/payments";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET)
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        
        echo "Razorpay Payments:\n";
        if (empty($data['items'])) {
            echo "No payments found for this order\n\n";
        } else {
            foreach ($data['items'] as $payment) {
                echo "Payment ID: " . $payment['id'] . "\n";
                echo "Amount: ₹" . ($payment['amount'] / 100) . "\n";
                echo "Status: " . $payment['status'] . "\n";
                echo "Method: " . $payment['method'] . "\n";
                echo "Created: " . date('Y-m-d H:i:s', $payment['created_at']) . "\n\n";
                
                if ($payment['status'] === 'captured' || $payment['status'] === 'authorized') {
                    echo "✅ Payment was successful!\n";
                    echo "Payment ID: " . $payment['id'] . "\n\n";
                    
                    // Check if we need to update database
                    if ($transaction['payment_status'] !== 'completed') {
                        echo "⚠️ Database not updated! Need to sync payment status.\n";
                        echo "Should update:\n";
                        echo "- stage_payment_transactions.payment_status = 'completed'\n";
                        echo "- stage_payment_transactions.razorpay_payment_id = '" . $payment['id'] . "'\n";
                        echo "- stage_payment_requests.status = 'paid'\n";
                    }
                }
            }
        }
    } else {
        echo "Failed to fetch payments: HTTP $httpCode\n";
        echo "Response: $response\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
