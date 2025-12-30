<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $orderId = $input['razorpay_order_id'] ?? '';
    $paymentId = $input['razorpay_payment_id'] ?? '';
    $signature = $input['razorpay_signature'] ?? '';

    $config = require __DIR__ . '/../../config/razorpay.php';
    $keyId = $config['key_id'] ?? '';
    $keySecret = $config['key_secret'] ?? '';
    if (!$keyId || !$keySecret) {
        echo json_encode(['success' => false, 'message' => 'Razorpay not configured']);
        exit;
    }

    $api = new Api($keyId, $keySecret);

    // Verify signature
    $attributes = [
        'razorpay_order_id' => $orderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature' => $signature,
    ];
    $api->utility->verifyPaymentSignature($attributes);

    // Mark payment as completed in your DB here using notes or context from initiate step
    echo json_encode(['success' => true]);
} catch (SignatureVerificationError $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}








