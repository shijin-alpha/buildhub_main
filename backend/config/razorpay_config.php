<?php
/**
 * Razorpay Configuration
 * 
 * For development/testing, you can use Razorpay test keys.
 * Get your keys from: https://dashboard.razorpay.com/app/keys
 */

// Demo mode for testing (set to false when you have real keys)
define('RAZORPAY_DEMO_MODE', false); // Disabled - using real keys now

// Razorpay Test Keys (your actual keys)
define('RAZORPAY_KEY_ID', 'rzp_test_RP6aD2gNdAuoRE'); // Your actual test key ID
define('RAZORPAY_KEY_SECRET', 'RyTIKYQ5yobfYgNaDrvErQKN'); // Your actual test key secret

// For production, use live keys
// define('RAZORPAY_KEY_ID', 'rzp_live_your_live_key');
// define('RAZORPAY_KEY_SECRET', 'your_live_key_secret');

/**
 * Get Razorpay Key ID
 */
function getRazorpayKeyId() {
    return RAZORPAY_KEY_ID;
}

/**
 * Get Razorpay Key Secret
 */
function getRazorpayKeySecret() {
    return RAZORPAY_KEY_SECRET;
}

/**
 * Verify Razorpay signature
 */
function verifyRazorpaySignature($razorpay_order_id, $razorpay_payment_id, $razorpay_signature) {
    // In demo mode, always return true for testing
    if (RAZORPAY_DEMO_MODE) {
        return true;
    }
    
    $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, RAZORPAY_KEY_SECRET);
    return hash_equals($generated_signature, $razorpay_signature);
}

/**
 * Create real Razorpay order using API
 */
function createRazorpayOrder($amount, $currency = 'INR', $receipt = null) {
    $url = 'https://api.razorpay.com/v1/orders';
    
    $data = [
        'amount' => $amount, // Amount in paise
        'currency' => $currency,
        'receipt' => $receipt ?: 'receipt_' . time(),
        'payment_capture' => 1 // Auto capture payment
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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
        throw new Exception('cURL Error: ' . $error);
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = isset($errorData['error']['description']) ? 
            $errorData['error']['description'] : 
            'HTTP Error: ' . $httpCode;
        throw new Exception('Razorpay API Error: ' . $errorMessage);
    }
    
    $orderData = json_decode($response, true);
    
    if (!$orderData || !isset($orderData['id'])) {
        throw new Exception('Invalid response from Razorpay API');
    }
    
    return $orderData;
}

/**
 * Check if we're in demo mode
 */
function isRazorpayDemoMode() {
    return RAZORPAY_DEMO_MODE;
}
?>