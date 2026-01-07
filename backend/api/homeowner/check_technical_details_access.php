<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;

    if (!$homeowner_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $house_plan_id = isset($_GET['house_plan_id']) ? (int)$_GET['house_plan_id'] : 0;

    if ($house_plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'House plan ID is required']);
        exit;
    }

    // Check if technical details are unlocked
    $paymentStmt = $db->prepare("
        SELECT tdp.*, hp.unlock_price, hp.plan_name
        FROM technical_details_payments tdp
        RIGHT JOIN house_plans hp ON tdp.house_plan_id = hp.id
        LEFT JOIN layout_requests lr ON hp.layout_request_id = lr.id
        WHERE hp.id = :plan_id 
        AND lr.user_id = :homeowner_id
        AND (tdp.homeowner_id = :homeowner_id2 OR tdp.homeowner_id IS NULL)
        AND hp.status IN ('submitted', 'approved', 'rejected')
    ");
    
    $paymentStmt->execute([
        ':plan_id' => $house_plan_id,
        ':homeowner_id' => $homeowner_id,
        ':homeowner_id2' => $homeowner_id
    ]);
    
    $result = $paymentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'House plan not found or access denied']);
        exit;
    }

    $is_unlocked = false;
    $payment_status = null;
    $unlock_price = $result['unlock_price'] ?? 8000.00;
    
    if ($result['payment_status'] === 'completed') {
        $is_unlocked = true;
        $payment_status = 'completed';
    } elseif ($result['payment_status'] === 'pending') {
        $payment_status = 'pending';
    }

    echo json_encode([
        'success' => true,
        'is_unlocked' => $is_unlocked,
        'payment_status' => $payment_status,
        'unlock_price' => $unlock_price,
        'plan_name' => $result['plan_name'],
        'house_plan_id' => $house_plan_id
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>