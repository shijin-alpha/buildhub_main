<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Razorpay\Api\Api;

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $designId = (int)($input['design_id'] ?? 0);
    $amountOverride = (int)($input['amount_override'] ?? 0); // rupees
    if ($designId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid design id']);
        exit;
    }

    // Load Razorpay credentials
    $config = require __DIR__ . '/../../config/razorpay.php';
    $keyId = $config['key_id'] ?? '';
    $keySecret = $config['key_secret'] ?? '';
    if (!$keyId || !$keySecret) {
        echo json_encode(['success' => false, 'message' => 'Razorpay not configured']);
        exit;
    }

    $api = new Api($keyId, $keySecret);

    // Look up architect-set price from DB when not overridden
    $amountRupees = 0;
    if ($amountOverride > 0) {
        $amountRupees = (int)$amountOverride;
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->prepare("SELECT view_price FROM designs WHERE id = :id LIMIT 1");
            $stmt->bindValue(':id', $designId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $vp = isset($row['view_price']) ? (float)$row['view_price'] : 0.0;
            if ($vp > 0) {
                $amountRupees = (int)round($vp);
            }
        } catch (Throwable $e) {
            // fall back silently
        }
        if ($amountRupees <= 0) {
            $amountRupees = 8000; // fallback base price
        }
    }

    // Calculate amount in paise
    $amountPaise = $amountRupees * 100;

    // Create order
    $order = $api->order->create([
        'amount' => $amountPaise,
        'currency' => 'INR',
        'payment_capture' => 1,
        'notes' => [ 'design_id' => (string)$designId ]
    ]);

    // You may persist a payment record here and return its id
    echo json_encode([
        'success' => true,
        'amount' => $amountPaise,
        'amount_in_rupees' => $amountPaise / 100,
        'currency' => 'INR',
        'razorpay_key_id' => $keyId,
        'razorpay_order_id' => $order['id'] ?? null,
        'payment_id' => null,
        'design_title' => $input['design_title'] ?? ''
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}








