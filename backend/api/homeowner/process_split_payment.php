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
    
    $split_group_id = isset($input['split_group_id']) ? (int)$input['split_group_id'] : 0;
    $sequence_number = isset($input['sequence_number']) ? (int)$input['sequence_number'] : 1;
    
    // Validation
    if ($split_group_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid split group ID']);
        exit;
    }
    
    // Get split payment group details
    $groupStmt = $db->prepare("
        SELECT spg.*, 
               u_homeowner.first_name as homeowner_first_name,
               u_homeowner.last_name as homeowner_last_name,
               u_contractor.first_name as contractor_first_name,
               u_contractor.last_name as contractor_last_name
        FROM split_payment_groups spg
        LEFT JOIN users u_homeowner ON spg.homeowner_id = u_homeowner.id
        LEFT JOIN users u_contractor ON spg.contractor_id = u_contractor.id
        WHERE spg.id = :group_id AND spg.homeowner_id = :homeowner_id
    ");
    $groupStmt->execute([
        ':group_id' => $split_group_id,
        ':homeowner_id' => $homeowner_id
    ]);
    $group = $groupStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$group) {
        echo json_encode(['success' => false, 'message' => 'Split payment group not found or access denied']);
        exit;
    }
    
    // Get specific split transaction
    $transactionStmt = $db->prepare("
        SELECT * FROM split_payment_transactions 
        WHERE split_group_id = :group_id AND sequence_number = :sequence
        AND payment_status IN ('created', 'pending')
    ");
    $transactionStmt->execute([
        ':group_id' => $split_group_id,
        ':sequence' => $sequence_number
    ]);
    $transaction = $transactionStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Split transaction not found or already processed']);
        exit;
    }
    
    // Check if this is the next expected payment
    $expectedSequence = $group['completed_splits'] + 1;
    if ($sequence_number != $expectedSequence) {
        echo json_encode([
            'success' => false,
            'message' => "Please complete payment {$expectedSequence} first before payment {$sequence_number}"
        ]);
        exit;
    }
    
    // Create Razorpay order for this split
    $amount_paise = (int)($transaction['amount'] * 100);
    $receipt = "split_{$split_group_id}_{$sequence_number}_" . time();
    
    try {
        $razorpay_order = createRazorpayOrder($amount_paise, $group['currency'], $receipt);
        $razorpay_order_id = $razorpay_order['id'];
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create payment order: ' . $e->getMessage()
        ]);
        exit;
    }
    
    // Update transaction with Razorpay order ID
    $updateStmt = $db->prepare("
        UPDATE split_payment_transactions 
        SET razorpay_order_id = :order_id, payment_status = 'pending', updated_at = NOW()
        WHERE id = :transaction_id
    ");
    $updateStmt->execute([
        ':order_id' => $razorpay_order_id,
        ':transaction_id' => $transaction['id']
    ]);
    
    // Prepare response data
    $paymentData = [
        'split_group_id' => $split_group_id,
        'transaction_id' => $transaction['id'],
        'sequence_number' => $sequence_number,
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_key_id' => getRazorpayKeyId(),
        'amount' => $amount_paise, // Amount in paise for Razorpay
        'currency' => $group['currency'],
        'description' => $transaction['description'],
        'customer_name' => $group['homeowner_first_name'] . ' ' . $group['homeowner_last_name'],
        'progress' => [
            'current_payment' => $sequence_number,
            'total_payments' => $group['total_splits'],
            'completed_amount' => $group['completed_amount'],
            'total_amount' => $group['total_amount'],
            'this_payment_amount' => $transaction['amount'],
            'remaining_amount' => $group['total_amount'] - $group['completed_amount'] - $transaction['amount']
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Split payment order created successfully',
        'data' => $paymentData
    ]);
    
} catch (Exception $e) {
    error_log("Split payment processing error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>