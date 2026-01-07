<?php
/**
 * Setup script to configure Razorpay keys
 * 
 * This script helps you set up your Razorpay test keys.
 * Get your keys from: https://dashboard.razorpay.com/app/keys
 */

echo "=== Razorpay Configuration Setup ===\n\n";

echo "To fix the Razorpay 401 Unauthorized error, you need to:\n\n";

echo "1. Sign up for a Razorpay account at: https://razorpay.com/\n";
echo "2. Go to Dashboard > Settings > API Keys: https://dashboard.razorpay.com/app/keys\n";
echo "3. Generate Test API Keys (for development)\n";
echo "4. Copy your Key ID and Key Secret\n\n";

echo "5. Update the file: backend/config/razorpay_config.php\n";
echo "   Replace these lines:\n";
echo "   define('RAZORPAY_KEY_ID', 'rzp_test_1234567890abcd');\n";
echo "   define('RAZORPAY_KEY_SECRET', 'your_test_key_secret_here');\n\n";

echo "   With your actual keys:\n";
echo "   define('RAZORPAY_KEY_ID', 'rzp_test_YOUR_ACTUAL_KEY_ID');\n";
echo "   define('RAZORPAY_KEY_SECRET', 'YOUR_ACTUAL_KEY_SECRET');\n\n";

echo "Example of valid Razorpay test keys:\n";
echo "Key ID: rzp_test_1DP5mmOlF5G5ag (starts with rzp_test_)\n";
echo "Key Secret: thisissecret (random string)\n\n";

echo "6. For production, replace 'rzp_test_' with 'rzp_live_' keys\n\n";

echo "Current configuration status:\n";

// Check if config file exists
$config_file = __DIR__ . '/config/razorpay_config.php';
if (file_exists($config_file)) {
    echo "✅ Config file exists: $config_file\n";
    
    // Include and check keys
    require_once $config_file;
    
    $key_id = getRazorpayKeyId();
    $key_secret = getRazorpayKeySecret();
    
    if ($key_id === 'rzp_test_1234567890abcd') {
        echo "❌ Using placeholder Key ID: $key_id\n";
        echo "   You need to replace this with your actual Razorpay Key ID\n";
    } else {
        echo "✅ Key ID configured: $key_id\n";
    }
    
    if ($key_secret === 'your_test_key_secret_here') {
        echo "❌ Using placeholder Key Secret\n";
        echo "   You need to replace this with your actual Razorpay Key Secret\n";
    } else {
        echo "✅ Key Secret configured (hidden for security)\n";
    }
    
} else {
    echo "❌ Config file not found: $config_file\n";
}

echo "\n=== Quick Test ===\n";
echo "After updating your keys, test the payment system:\n";
echo "1. Open: tests/demos/technical_details_payment_complete_test.html\n";
echo "2. Click 'Load Received Designs'\n";
echo "3. Try to initiate a payment\n";
echo "4. The Razorpay checkout should open without 401 errors\n\n";

echo "=== Security Notes ===\n";
echo "- Never commit your actual Razorpay keys to version control\n";
echo "- Use test keys for development (rzp_test_)\n";
echo "- Use live keys only in production (rzp_live_)\n";
echo "- Keep your Key Secret secure and never expose it in frontend code\n\n";

echo "Need help? Check Razorpay documentation:\n";
echo "- Integration Guide: https://razorpay.com/docs/payments/payment-gateway/web-integration/\n";
echo "- Test Cards: https://razorpay.com/docs/payments/payments/test-card-details/\n";
?>