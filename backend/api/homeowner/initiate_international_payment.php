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
require_once '../../config/international_payment_config.php';
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
    
    $payment_request_id = isset($input['payment_request_id']) ? (int)$input['payment_request_id'] : 0;
    $amount = isset($input['amount']) ? (float)$input['amount'] : 0;
    $currency = strtoupper($input['currency'] ?? 'INR');
    $country_code = strtoupper($input['country_code'] ?? 'IN');
    $payment_type = $input['payment_type'] ?? 'stage_payment'; // 'stage_payment' or 'technical_details'
    
    // Validate international payment
    $validation = validateInternationalPayment($amount, $currency, $country_code);
    if (!$validation['valid']) {
        echo json_encode([
            'success' => false,
            'message' => 'Payment validation failed',
            'errors' => $validation['errors']
        ]);
        exit;
    }
    
    // Convert amount to INR if needed (Razorpay primarily works with INR)
    $amount_inr = $currency === 'INR' ? $amount : convertCurrency($amount, $currency, 'INR');
    $amount_paise = (int)($amount_inr * 100);
    
    // Validate against payment limits
    $limit_validation = validatePaymentAmount($amount_inr);
    if (!$limit_validation['valid']) {
        echo json_encode(['success' => false, 'message' => $limit_validation['message']]);
        exit;
    }
    
    // Get payment request details based on type
    if ($payment_type === 'technical_details') {
        $house_plan_id = $payment_request_id;
        
        // Get house plan details
        $planStmt = $db->prepare("
            SELECT hp.*, lr.user_id as request_owner_id
            FROM house_plans hp
            LEFT JOIN layout_requests lr ON hp.layout_request_id = lr.id
            WHERE hp.id = :plan_id AND hp.status IN ('submitted', 'approved', 'rejected')
        ");
        $planStmt->execute([':plan_id' => $house_plan_id]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan || $plan['request_owner_id'] != $homeowner_id) {
            echo json_encode(['success' => false, 'message' => 'House plan not found or access denied']);
            exit;
        }
        
        $description = 'Unlock Technical Details for ' . $plan['plan_name'];
        $receipt = 'technical_details_' . $house_plan_id . '_' . $homeowner_id . '_' . time();
        
    } else {
        // Stage payment
        $requestCheck = $db->prepare("
            SELECT spr.*, 
                   u_contractor.first_name as contractor_first_name, 
                   u_contractor.last_name as contractor_last_name
            FROM stage_payment_requests spr
            LEFT JOIN users u_contractor ON spr.contractor_id = u_contractor.id
            WHERE spr.id = :request_id AND spr.homeowner_id = :homeowner_id 
            AND spr.status IN ('pending', 'approved')
            LIMIT 1
        ");
        $requestCheck->execute([
            ':request_id' => $payment_request_id,
            ':homeowner_id' => $homeowner_id
        ]);
        $request = $requestCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Payment request not found or not eligible']);
            exit;
        }
        
        $description = 'Stage Payment: ' . $request['stage_name'];
        $receipt = 'stage_payment_' . $payment_request_id . '_' . time();
    }
    
    // Create enhanced Razorpay order with international support
    $razorpay_order_data = [
        'amount' => $amount_paise,
        'currency' => 'INR', // Razorpay processes in INR
        'receipt' => $receipt,
        'payment_capture' => 1,
        'notes' => [
            'payment_type' => $payment_type,
            'original_currency' => $currency,
            'original_amount' => $amount,
            'country_code' => $country_code,
            'homeowner_id' => $homeowner_id,
            'international_payment' => $currency !== 'INR' ? 'true' : 'false'
        ]
    ];
    
    // Create Razorpay order
    try {
        $razorpay_order = createRazorpayOrder($amount_paise, 'INR', $receipt);
        $razorpay_order_id = $razorpay_order['id'];
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create payment order: ' . $e->getMessage()
        ]);
        exit;
    }
    
    // Store payment transaction
    if ($payment_type === 'technical_details') {
        // Handle technical details payment
        $existingStmt = $db->prepare("
            SELECT id FROM technical_details_payments 
            WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id
        ");
        $existingStmt->execute([
            ':plan_id' => $house_plan_id,
            ':homeowner_id' => $homeowner_id
        ]);
        
        if ($existingStmt->fetch()) {
            $updateStmt = $db->prepare("
                UPDATE technical_details_payments 
                SET amount = :amount, payment_status = 'pending', razorpay_order_id = :order_id, 
                    currency = :currency, country_code = :country_code, updated_at = CURRENT_TIMESTAMP
                WHERE house_plan_id = :plan_id AND homeowner_id = :homeowner_id
            ");
            $updateStmt->execute([
                ':plan_id' => $house_plan_id,
                ':homeowner_id' => $homeowner_id,
                ':amount' => $amount_inr,
                ':order_id' => $razorpay_order_id,
                ':currency' => $currency,
                ':country_code' => $country_code
            ]);
        } else {
            $insertStmt = $db->prepare("
                INSERT INTO technical_details_payments 
                (house_plan_id, homeowner_id, amount, payment_status, razorpay_order_id, 
                 currency, country_code, created_at)
                VALUES (:plan_id, :homeowner_id, :amount, 'pending', :order_id, 
                        :currency, :country_code, CURRENT_TIMESTAMP)
            ");
            $insertStmt->execute([
                ':plan_id' => $house_plan_id,
                ':homeowner_id' => $homeowner_id,
                ':amount' => $amount_inr,
                ':order_id' => $razorpay_order_id,
                ':currency' => $currency,
                ':country_code' => $country_code
            ]);
        }
        
        $payment_id = $db->lastInsertId() ?: $existingStmt->fetchColumn();
        
    } else {
        // Handle stage payment
        $insertStmt = $db->prepare("
            INSERT INTO stage_payment_transactions (
                payment_request_id, homeowner_id, contractor_id, amount, 
                razorpay_order_id, payment_status, currency, country_code
            ) VALUES (
                :payment_request_id, :homeowner_id, :contractor_id, :amount,
                :razorpay_order_id, 'created', :currency, :country_code
            )
        ");
        
        $insertStmt->execute([
            ':payment_request_id' => $payment_request_id,
            ':homeowner_id' => $homeowner_id,
            ':contractor_id' => $request['contractor_id'],
            ':amount' => $amount_inr,
            ':razorpay_order_id' => $razorpay_order_id,
            ':currency' => $currency,
            ':country_code' => $country_code
        ]);
        
        $payment_id = $db->lastInsertId();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'International payment order created successfully',
        'data' => [
            'payment_id' => $payment_id,
            'razorpay_order_id' => $razorpay_order_id,
            'razorpay_key_id' => getRazorpayKeyId(),
            'amount' => $amount_paise, // Amount in paise for Razorpay
            'currency' => 'INR',
            'original_amount' => $amount,
            'original_currency' => $currency,
            'country_code' => $country_code,
            'description' => $description,
            'supported_methods' => $validation['supported_methods'],
            'international_payment' => $currency !== 'INR'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("International payment initiation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>