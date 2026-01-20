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
    
    $payment_type = $input['payment_type'] ?? 'stage_payment'; // 'stage_payment' or 'technical_details'
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
    
    // Calculate payment splits
    $splitCalculation = calculatePaymentSplits($total_amount);
    
    if (!$splitCalculation['can_split'] && $splitCalculation['total_splits'] > 1) {
        echo json_encode([
            'success' => false,
            'message' => $splitCalculation['message'],
            'requires_split' => true,
            'total_amount' => $total_amount,
            'max_single_amount' => getMaxPaymentAmount()
        ]);
        exit;
    }
    
    $splits = $splitCalculation['splits'];
    $totalSplits = count($splits);
    
    // Validate splits
    $validation = validateSplitPayment($total_amount, $splits);
    if (!$validation['valid']) {
        echo json_encode([
            'success' => false,
            'message' => 'Split payment validation failed',
            'errors' => $validation['errors']
        ]);
        exit;
    }
    
    // Get additional details based on payment type
    $contractor_id = null;
    $description = '';
    
    if ($payment_type === 'technical_details') {
        // Get house plan details
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
        
        $description = "Split payment for technical details: " . ($plan['plan_name'] ?? 'House Plan');
        
    } else {
        // Get stage payment request details
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
        $description = "Split payment for stage: " . ($request['stage_name'] ?? 'Construction Stage');
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Create split payment group
        $groupStmt = $db->prepare("
            INSERT INTO split_payment_groups (
                payment_type, reference_id, homeowner_id, contractor_id, 
                total_amount, currency, country_code, total_splits, description
            ) VALUES (
                :payment_type, :reference_id, :homeowner_id, :contractor_id,
                :total_amount, :currency, :country_code, :total_splits, :description
            )
        ");
        
        $groupStmt->execute([
            ':payment_type' => $payment_type,
            ':reference_id' => $reference_id,
            ':homeowner_id' => $homeowner_id,
            ':contractor_id' => $contractor_id,
            ':total_amount' => $total_amount,
            ':currency' => $currency,
            ':country_code' => $country_code,
            ':total_splits' => $totalSplits,
            ':description' => $description
        ]);
        
        $splitGroupId = $db->lastInsertId();
        
        // Create individual split transactions
        $splitTransactions = [];
        foreach ($splits as $split) {
            $transactionStmt = $db->prepare("
                INSERT INTO split_payment_transactions (
                    split_group_id, sequence_number, amount, currency, description
                ) VALUES (
                    :split_group_id, :sequence_number, :amount, :currency, :description
                )
            ");
            
            $transactionStmt->execute([
                ':split_group_id' => $splitGroupId,
                ':sequence_number' => $split['sequence'],
                ':amount' => $split['amount'],
                ':currency' => $currency,
                ':description' => $split['description']
            ]);
            
            $splitTransactions[] = [
                'id' => $db->lastInsertId(),
                'sequence' => $split['sequence'],
                'amount' => $split['amount'],
                'description' => $split['description']
            ];
        }
        
        // Create initial progress record
        $progressStmt = $db->prepare("
            INSERT INTO split_payment_progress (
                split_group_id, progress_percentage, completed_splits, total_splits,
                completed_amount, total_amount, next_payment_amount
            ) VALUES (
                :split_group_id, 0.00, 0, :total_splits, 0.00, :total_amount, :next_amount
            )
        ");
        
        $progressStmt->execute([
            ':split_group_id' => $splitGroupId,
            ':total_splits' => $totalSplits,
            ':total_amount' => $total_amount,
            ':next_amount' => $splits[0]['amount']
        ]);
        
        // Create notification for split payment created
        $notificationStmt = $db->prepare("
            INSERT INTO split_payment_notifications (
                split_group_id, recipient_id, recipient_type, notification_type, title, message
            ) VALUES (
                :split_group_id, :recipient_id, 'homeowner', 'split_created', :title, :message
            )
        ");
        
        $notificationTitle = "Split Payment Created";
        $notificationMessage = "Your payment of ₹" . number_format($total_amount, 2) . 
                              " has been split into {$totalSplits} transactions. " .
                              "First payment: ₹" . number_format($splits[0]['amount'], 2);
        
        $notificationStmt->execute([
            ':split_group_id' => $splitGroupId,
            ':recipient_id' => $homeowner_id,
            ':title' => $notificationTitle,
            ':message' => $notificationMessage
        ]);
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Split payment created successfully',
            'data' => [
                'split_group_id' => $splitGroupId,
                'payment_type' => $payment_type,
                'reference_id' => $reference_id,
                'total_amount' => $total_amount,
                'currency' => $currency,
                'total_splits' => $totalSplits,
                'splits' => $splitTransactions,
                'first_payment' => [
                    'amount' => $splits[0]['amount'],
                    'sequence' => 1,
                    'description' => $splits[0]['description']
                ],
                'description' => $description,
                'next_step' => 'Process first payment of ₹' . number_format($splits[0]['amount'], 2)
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Split payment initiation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>