<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $orderId = $input['razorpay_order_id'] ?? '';
    $paymentId = $input['razorpay_payment_id'] ?? '';
    $signature = $input['razorpay_signature'] ?? '';
    $paymentRowId = isset($input['payment_id']) ? (int)$input['payment_id'] : 0;

    if (!$orderId || !$paymentId || !$signature || $paymentRowId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing fields']);
        exit;
    }

    $config = require __DIR__ . '/../../config/razorpay.php';
    $keyId = $config['key_id'] ?? '';
    $keySecret = $config['key_secret'] ?? '';
    if (!$keyId || !$keySecret) {
        echo json_encode(['success' => false, 'message' => 'Razorpay not configured']);
        exit;
    }

    $api = new Api($keyId, $keySecret);
    $attributes = [
        'razorpay_order_id' => $orderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature' => $signature,
    ];
    $api->utility->verifyPaymentSignature($attributes);

    $database = new Database();
    $db = $database->getConnection();

    $db->exec("CREATE TABLE IF NOT EXISTS contractor_estimate_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        homeowner_id INT NOT NULL,
        estimate_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'INR',
        payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        razorpay_order_id VARCHAR(255),
        razorpay_payment_id VARCHAR(255),
        razorpay_signature VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX(homeowner_id), INDEX(estimate_id), INDEX(payment_status)
    )");

    $upd = $db->prepare("UPDATE contractor_estimate_payments SET payment_status='completed', razorpay_payment_id=:pid, razorpay_signature=:sig WHERE id=:id AND razorpay_order_id=:oid");
    $upd->bindValue(':pid', $paymentId);
    $upd->bindValue(':sig', $signature);
    $upd->bindValue(':id', $paymentRowId, PDO::PARAM_INT);
    $upd->bindValue(':oid', $orderId);
    $upd->execute();

    echo json_encode(['success' => true]);
} catch (SignatureVerificationError $e) {
    echo json_encode(['success' => false, 'message' => 'Signature verification failed: ' . $e->getMessage()]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}




