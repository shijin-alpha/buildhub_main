<?php
/**
 * Verify if existing Razorpay order is still valid
 */

require_once 'config/razorpay_config.php';

$order_id = 'order_S3IrWKXRV403r4'; // From database

echo "=== VERIFYING RAZORPAY ORDER ===\n\n";
echo "Order ID: $order_id\n\n";

try {
    $url = "https://api.razorpay.com/v1/orders/$order_id";
    
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
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ cURL Error: $error\n";
        exit;
    }
    
    echo "HTTP Status: $httpCode\n\n";
    
    if ($httpCode === 200) {
        $orderData = json_decode($response, true);
        echo "✅ Order is valid!\n\n";
        echo "Order Details:\n";
        echo "ID: " . $orderData['id'] . "\n";
        echo "Amount: ₹" . ($orderData['amount'] / 100) . "\n";
        echo "Currency: " . $orderData['currency'] . "\n";
        echo "Status: " . $orderData['status'] . "\n";
        echo "Created: " . date('Y-m-d H:i:s', $orderData['created_at']) . "\n";
        
        if ($orderData['status'] === 'created') {
            echo "\n✅ Order can be used for payment!\n";
        } else {
            echo "\n⚠️ Order status is '" . $orderData['status'] . "' - may need new order\n";
        }
    } else {
        echo "❌ Order verification failed\n";
        echo "Response: $response\n";
        echo "\n⚠️ Need to create new Razorpay order\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
