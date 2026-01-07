<?php
/**
 * Test Razorpay configuration
 */

require_once 'config/razorpay_config.php';

echo "=== Testing Razorpay Configuration ===\n\n";

$key_id = getRazorpayKeyId();
$key_secret = getRazorpayKeySecret();

echo "Key ID: $key_id\n";
echo "Key Secret: " . (strlen($key_secret) > 0 ? str_repeat('*', strlen($key_secret)) : 'NOT SET') . "\n\n";

// Check if keys are still placeholders
$is_placeholder_key = ($key_id === 'rzp_test_1234567890abcd');
$is_placeholder_secret = ($key_secret === 'your_test_key_secret_here');

if ($is_placeholder_key || $is_placeholder_secret) {
    echo "❌ CONFIGURATION ISSUE DETECTED\n\n";
    
    if ($is_placeholder_key) {
        echo "- Key ID is still using placeholder value\n";
    }
    if ($is_placeholder_secret) {
        echo "- Key Secret is still using placeholder value\n";
    }
    
    echo "\nTo fix the 401 Unauthorized error:\n";
    echo "1. Get your Razorpay test keys from: https://dashboard.razorpay.com/app/keys\n";
    echo "2. Update backend/config/razorpay_config.php with your actual keys\n";
    echo "3. Run this test again to verify\n\n";
    
} else {
    echo "✅ Configuration looks good!\n\n";
    
    // Test key format
    if (strpos($key_id, 'rzp_test_') === 0) {
        echo "✅ Using test keys (good for development)\n";
    } elseif (strpos($key_id, 'rzp_live_') === 0) {
        echo "⚠️  Using live keys (make sure this is production)\n";
    } else {
        echo "❌ Key ID format doesn't match Razorpay pattern\n";
    }
}

// Test signature verification function
echo "\n=== Testing Signature Verification ===\n";

$test_order_id = 'order_test123';
$test_payment_id = 'pay_test456';
$test_signature = hash_hmac('sha256', $test_order_id . '|' . $test_payment_id, $key_secret);

echo "Test Order ID: $test_order_id\n";
echo "Test Payment ID: $test_payment_id\n";
echo "Generated Signature: $test_signature\n";

$verification_result = verifyRazorpaySignature($test_order_id, $test_payment_id, $test_signature);
echo "Signature Verification: " . ($verification_result ? '✅ PASS' : '❌ FAIL') . "\n\n";

// Test order creation
echo "=== Testing Order Creation ===\n";

$test_amount = 800000; // ₹8000 in paise
$test_order = createRazorpayOrder($test_amount, 'INR', 'test_receipt_123');

echo "Test Order:\n";
echo "- ID: " . $test_order['id'] . "\n";
echo "- Amount: " . $test_order['amount'] . " paise (₹" . ($test_order['amount']/100) . ")\n";
echo "- Currency: " . $test_order['currency'] . "\n";
echo "- Receipt: " . $test_order['receipt'] . "\n";
echo "- Status: " . $test_order['status'] . "\n\n";

echo "=== Next Steps ===\n";
if ($is_placeholder_key || $is_placeholder_secret) {
    echo "1. Update your Razorpay keys in backend/config/razorpay_config.php\n";
    echo "2. Run this test again: php backend/test_razorpay_config.php\n";
    echo "3. Test the payment flow in your application\n";
} else {
    echo "1. Test the payment flow in your application\n";
    echo "2. Use Razorpay test cards for testing: https://razorpay.com/docs/payments/payments/test-card-details/\n";
    echo "3. Monitor payments in Razorpay Dashboard: https://dashboard.razorpay.com/\n";
}

echo "\n=== Test Cards for Development ===\n";
echo "Card Number: 4111 1111 1111 1111\n";
echo "Expiry: Any future date\n";
echo "CVV: Any 3 digits\n";
echo "Name: Any name\n";
?>