<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';
require_once '../../config/razorpay_config.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;

    if (!$homeowner_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $house_plan_id = isset($input['house_plan_id']) ? (int)$input['house_plan_id'] : 0;

    if ($house_plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'House plan ID is required']);
        exit;
    }

    // Get house plan details and verify access
    $planStmt = $db->prepare("
        SELECT hp.*, lr.user_id as request_owner_id
        FROM house_plans hp
        LEFT JOIN layout_requests lr ON hp.layout_request_id = lr.id
        WHERE hp.id = :plan_id AND hp.status IN ('submitted', 'approved', 'rejected')
    ");
    $planStmt->execute([':plan_id' => $house_plan_id]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        echo json_encode(['success' => false, 'message' => 'House plan not found or not accessible']);
        exit;
    }

    // Verify homeowner has access to this plan
    if ($plan['request_owner_id'] != $homeowner_id) {
        echo json_encode(['success' => false, 'message' => 'Access denied to this house plan']);
        exit;
    }

    // Check if already paid
    $paymentStmt = $db->prepare("
        SELECT * FROM technical_details_payments 
        WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id AND payment_status = 'completed'
    ");
    $paymentStmt->execute([
        ':plan_id' => $house_plan_id,
        ':homeowner_id' => $homeowner_id
    ]);
    
    if ($paymentStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Technical details already unlocked']);
        exit;
    }

    $amount = $plan['unlock_price'] ?? 8000.00;
    $amount_paise = $amount * 100; // Convert to paise for Razorpay

    // Create real Razorpay order
    $receipt = 'technical_details_' . $house_plan_id . '_' . $homeowner_id . '_' . time();
    $razorpay_order = createRazorpayOrder($amount_paise, 'INR', $receipt);
    $razorpay_order_id = $razorpay_order['id'];
    
    // Check if payment record exists and update/insert accordingly
    $existingStmt = $db->prepare("
        SELECT id FROM technical_details_payments 
        WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id
    ");
    $existingStmt->execute([
        ':plan_id' => $house_plan_id,
        ':homeowner_id' => $homeowner_id
    ]);
    
    if ($existingStmt->fetch()) {
        // Update existing record
        $updateStmt = $db->prepare("
            UPDATE technical_details_payments 
            SET amount = :amount, payment_status = 'pending', razorpay_order_id = :order_id, updated_at = CURRENT_TIMESTAMP
            WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id
        ");
        $updateStmt->execute([
            ':plan_id' => $house_plan_id,
            ':homeowner_id' => $homeowner_id,
            ':amount' => $amount,
            ':order_id' => $razorpay_order_id
        ]);
        
        // Get the existing payment ID
        $idStmt = $db->prepare("SELECT id FROM technical_details_payments WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id");
        $idStmt->execute([':plan_id' => $house_plan_id, ':homeowner_id' => $homeowner_id]);
        $payment_id = $idStmt->fetchColumn();
    } else {
        // Insert new record
        $insertStmt = $db->prepare("
            INSERT INTO technical_details_payments 
            (house_plan_id, homeowner_id, amount, payment_status, razorpay_order_id, created_at)
            VALUES (:plan_id, :homeowner_id, :amount, 'pending', :order_id, CURRENT_TIMESTAMP)
        ");
        
        $insertStmt->execute([
            ':plan_id' => $house_plan_id,
            ':homeowner_id' => $homeowner_id,
            ':amount' => $amount,
            ':order_id' => $razorpay_order_id
        ]);

        $payment_id = $db->lastInsertId();
    }

    echo json_encode([
        'success' => true,
        'payment_id' => $payment_id,
        'razorpay_order_id' => $razorpay_order_id,
        'amount' => $amount_paise,
        'currency' => 'INR',
        'razorpay_key_id' => getRazorpayKeyId(),
        'plan_name' => $plan['plan_name'],
        'description' => 'Unlock Technical Details for ' . $plan['plan_name']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>