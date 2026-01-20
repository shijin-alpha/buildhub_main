<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/database.php';
require_once '../../config/razorpay_config.php';
require_once '../../config/split_payment_config.php';
require_once '../../config/payment_limits.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $payment_type = $input['payment_type'] ?? 'stage_payment';
    $reference_id = isset($input['reference_id']) ? (int)$input['reference_id'] : 0;
    $total_amount = isset($input['amount']) ? (float)$input['amount'] : 0;
    $currency = strtoupper($input['currency'] ?? 'INR');
    $country_code = strtoupper($input['country_code'] ?? 'IN');
    
    // Validation
    if ($reference_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid reference ID']);
        exit;
    }
    
    if ($total_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment amount']);
        exit;
    }
    
    // Get maximum single payment amount
    $maxSingleAmount = getMaxPaymentAmount();
    
    // Check if payment needs to be split
    $needsSplit = $total_amount > $maxSingleAmount;
    
    if ($needsSplit) {
        // Use split payment system
        echo json_encode([
            'success' => true,
            'payment_method' => 'split',
            'message' => 'Payment will be processed using split payment system',
            'data' => [
                'total_amount' => $total_amount,
                'max_single_amount' => $maxSingleAmount,
                'requires_split' => true,
                'estimated_splits' => ceil($total_amount / $maxSingleAmount),
                'redirect_to' => 'split_payment',
                'split_info' => calculatePaymentSplits($total_amount)
            ]
        ]);
        exit;
    }
    
    // Process as single payment
    $amount_paise = (int)($total_amount * 100);
    $receipt = "{$payment_type}_{$reference_id}_" . time();
    
    // Get additional details based on payment type
    $description = '';
    $contractor_id = null;
    
    if ($payment_type === 'technical_details') {
        $planStmt = $db->prepare("
            SELECT hp.*, lr.user_id as request_owner_id
            FROM house_plans hp
            LEFT JOIN layout_requests lr ON hp.layout_request_id = lr.id
            WHERE hp.id = :plan_id
        ");
        $planStmt->execute([':plan_id' => $reference_id]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan || $plan['request_owner_id'] != $homeowner_id) {
            echo json_encode(['success' => false, 'message' => 'House plan not found or access denied']);
            exit;
        }
        
        $description = 'Technical Details: ' . ($plan['plan_name'] ?? 'House Plan');
        
    } else {
        $requestStmt = $db->prepare("
            SELECT spr.*, u.first_name, u.last_name
            FROM stage_payment_requests spr
            LEFT JOIN users u ON spr.contractor_id = u.id
            WHERE spr.id = :request_id AND spr.homeowner_id = :homeowner_id
        ");
        $requestStmt->execute([
            ':request_id' => $reference_id,
            ':homeowner_id' => $homeowner_id
        ]);
        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Payment request not found or access denied']);
            exit;
        }
        
        $contractor_id = $request['contractor_id'];
        $description = 'Stage Payment: ' . ($request['stage_name'] ?? 'Construction Stage');
    }
    
    // Create Razorpay order for single payment
    try {
        $razorpay_order = createRazorpayOrder($amount_paise, $currency, $receipt);
        $razorpay_order_id = $razorpay_order['id'];
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create payment order: ' . $e->getMessage()
        ]);
        exit;
    }
    
    // Store payment record based on type
    if ($payment_type === 'technical_details') {
        $existingStmt = $db->prepare("
            SELECT id FROM technical_details_payments 
            WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id
        ");
        $existingStmt->execute([
            ':plan_id' => $reference_id,
            ':homeowner_id' => $homeowner_id
        ]);
        
        if ($existingStmt->fetch()) {
            $updateStmt = $db->prepare("
                UPDATE technical_details_payments 
                SET amount = :amount, payment_status = 'pending', razorpay_order_id = :order_id, updated_at = CURRENT_TIMESTAMP
                WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id
            ");
            $updateStmt->execute([
                ':plan_id' => $reference_id,
                ':homeowner_id' => $homeowner_id,
                ':amount' => $total_amount,
                ':order_id' => $razorpay_order_id
            ]);
        } else {
            $insertStmt = $db->prepare("
                INSERT INTO technical_details_payments 
                (house_plan_id, homeowner_id, amount, payment_status, razorpay_order_id, created_at)
                VALUES (:plan_id, :homeowner_id, :amount, 'pending', :order_id, CURRENT_TIMESTAMP)
            ");
            $insertStmt->execute([
                ':plan_id' => $reference_id,
                ':homeowner_id' => $homeowner_id,
                ':amount' => $total_amount,
                ':order_id' => $razorpay_order_id
            ]);
        }
        
        $payment_id = $db->lastInsertId() ?: $existingStmt->fetchColumn();
        
    } else {
        $insertStmt = $db->prepare("
            INSERT INTO stage_payment_transactions (
                payment_request_id, homeowner_id, contractor_id, amount, 
                razorpay_order_id, payment_status
            ) VALUES (
                :payment_request_id, :homeowner_id, :contractor_id, :amount,
                :razorpay_order_id, 'created'
            )
        ");
        
        $insertStmt->execute([
            ':payment_request_id' => $reference_id,
            ':homeowner_id' => $homeowner_id,
            ':contractor_id' => $contractor_id,
            ':amount' => $total_amount,
            ':razorpay_order_id' => $razorpay_order_id
        ]);
        
        $payment_id = $db->lastInsertId();
    }
    
    echo json_encode([
        'success' => true,
        'payment_method' => 'single',
        'message' => 'Single payment order created successfully',
        'data' => [
            'payment_id' => $payment_id,
            'razorpay_order_id' => $razorpay_order_id,
            'razorpay_key_id' => getRazorpayKeyId(),
            'amount' => $amount_paise,
            'currency' => $currency,
            'description' => $description,
            'total_amount' => $total_amount,
            'requires_split' => false
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Smart payment initiation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>