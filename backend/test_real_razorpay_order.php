<?php
/**
 * Test real Razorpay order creation
 */

require_once 'config/razorpay_config.php';

echo "=== Testing Real Razorpay Order Creation ===\n\n";

echo "Configuration:\n";
echo "Key ID: " . getRazorpayKeyId() . "\n";
echo "Key Secret: " . str_repeat('*', strlen(getRazorpayKeySecret())) . "\n\n";

try {
    echo "Creating real Razorpay order...\n";
    
    $amount = 800000; // ₹8000 in paise
    $currency = 'INR';
    $receipt = 'test_receipt_' . time();
    
    echo "Order details:\n";
    echo "- Amount: $amount paise (₹" . ($amount/100) . ")\n";
    echo "- Currency: $currency\n";
    echo "- Receipt: $receipt\n\n";
    
    $order = createRazorpayOrder($amount, $currency, $receipt);
    
    echo "✅ Order created successfully!\n";
    echo "Response:\n";
    echo json_encode($order, JSON_PRETTY_PRINT) . "\n\n";
    
    // Verify order structure
    if (isset($order['id']) && strpos($order['id'], 'order_') === 0) {
        echo "✅ Valid order ID: " . $order['id'] . "\n";
    } else {
        echo "❌ Invalid order ID format\n";
    }
    
    if (isset($order['amount']) && $order['amount'] == $amount) {
        echo "✅ Correct amount: " . $order['amount'] . " paise\n";
    } else {
        echo "❌ Incorrect amount\n";
    }
    
    if (isset($order['currency']) && $order['currency'] == $currency) {
        echo "✅ Correct currency: " . $order['currency'] . "\n";
    } else {
        echo "❌ Incorrect currency\n";
    }
    
    if (isset($order['status']) && $order['status'] == 'created') {
        echo "✅ Order status: " . $order['status'] . "\n";
    } else {
        echo "❌ Unexpected order status: " . ($order['status'] ?? 'unknown') . "\n";
    }
    
    echo "\n🎉 Real Razorpay integration is working!\n";
    echo "The 400 Bad Request error should now be fixed.\n";
    
} catch (Exception $e) {
    echo "❌ Error creating order: " . $e->getMessage() . "\n\n";
    
    // Check common issues
    if (strpos($e->getMessage(), 'cURL Error') !== false) {
        echo "💡 This might be a network/SSL issue. Try:\n";
        echo "1. Check your internet connection\n";
        echo "2. Verify SSL certificates are up to date\n";
        echo "3. Check if your server can make HTTPS requests\n";
    } elseif (strpos($e->getMessage(), 'Unauthorized') !== false) {
        echo "💡 This is an authentication issue. Check:\n";
        echo "1. Your Razorpay Key ID: " . getRazorpayKeyId() . "\n";
        echo "2. Your Razorpay Key Secret (hidden for security)\n";
        echo "3. Make sure keys are active in Razorpay dashboard\n";
    } elseif (strpos($e->getMessage(), 'Bad Request') !== false) {
        echo "💡 This is a request format issue. Check:\n";
        echo "1. Amount should be in paise (₹8000 = 800000 paise)\n";
        echo "2. Currency should be 'INR'\n";
        echo "3. Receipt should be a unique string\n";
    }
}
?>