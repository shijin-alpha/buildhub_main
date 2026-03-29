<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';

use Razorpay\Api\Api;

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $homeownerId = isset($input['homeowner_id']) ? (int)$input['homeowner_id'] : 0;
    $estimateId = isset($input['estimate_id']) ? (int)$input['estimate_id'] : 0;
    $amountRupees = 100; // fixed price to view estimate
    if ($homeownerId <= 0 || $estimateId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing identifiers']);
        exit;
    }

    $config = require __DIR__ . '/../../config/razorpay.php';
    $keyId = $config['key_id'] ?? '';
    $keySecret = $config['key_secret'] ?? '';
    if (!$keyId || !$keySecret) {
        echo json_encode(['success' => false, 'message' => 'Razorpay not configured']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Ensure payment table exists
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

    $api = new Api($keyId, $keySecret);
    $amountPaise = (int)($amountRupees * 100);
    $order = $api->order->create([
        'amount' => $amountPaise,
        'currency' => 'INR',
        'payment_capture' => 1,
        'notes' => [ 'homeowner_id' => (string)$homeownerId, 'estimate_id' => (string)$estimateId ]
    ]);

    // Persist pending record
    $ins = $db->prepare("INSERT INTO contractor_estimate_payments (homeowner_id, estimate_id, amount, currency, payment_status, razorpay_order_id) VALUES (:hid, :eid, :amt, 'INR', 'pending', :oid)");
    $ins->bindValue(':hid', $homeownerId, PDO::PARAM_INT);
    $ins->bindValue(':eid', $estimateId, PDO::PARAM_INT);
    $ins->bindValue(':amt', $amountRupees);
    $ins->bindValue(':oid', $order['id'] ?? null);
    $ins->execute();
    $paymentId = (int)$db->lastInsertId();

    echo json_encode([
        'success' => true,
        'amount' => $amountPaise,
        'amount_in_rupees' => $amountRupees,
        'currency' => 'INR',
        'razorpay_key_id' => $keyId,
        'razorpay_order_id' => $order['id'] ?? null,
        'payment_id' => $paymentId
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}




