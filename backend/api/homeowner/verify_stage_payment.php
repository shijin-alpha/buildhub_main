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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Homeowner not authenticated'
        ]);
        exit;
    }
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $payment_request_id = isset($input['payment_request_id']) ? (int)$input['payment_request_id'] : 0;
    $razorpay_order_id = trim($input['razorpay_order_id'] ?? '');
    $razorpay_payment_id = trim($input['razorpay_payment_id'] ?? '');
    $razorpay_signature = trim($input['razorpay_signature'] ?? '');
    
    // Validation
    if ($payment_request_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment request ID']);
        exit;
    }
    
    if (empty($razorpay_order_id) || empty($razorpay_payment_id) || empty($razorpay_signature)) {
        echo json_encode(['success' => false, 'message' => 'Missing Razorpay payment details']);
        exit;
    }
    
    // Get transaction details
    $transactionCheck = $db->prepare("
        SELECT spt.*, spr.stage_name, spr.contractor_id
        FROM stage_payment_transactions spt
        INNER JOIN stage_payment_requests spr ON spt.payment_request_id = spr.id
        WHERE spt.payment_request_id = :request_id 
        AND spt.homeowner_id = :homeowner_id 
        AND spt.razorpay_order_id = :order_id
        AND spt.payment_status IN ('created', 'pending')
        LIMIT 1
    ");
    $transactionCheck->bindValue(':request_id', $payment_request_id, PDO::PARAM_INT);
    $transactionCheck->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $transactionCheck->bindValue(':order_id', $razorpay_order_id, PDO::PARAM_STR);
    $transactionCheck->execute();
    $transaction = $transactionCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Payment transaction not found or already processed']);
        exit;
    }
    
    // Verify Razorpay signature
    $signature_valid = verifyRazorpaySignature($razorpay_order_id, $razorpay_payment_id, $razorpay_signature);
    
    if (!$signature_valid) {
        // Update transaction as failed
        $failStmt = $db->prepare("
            UPDATE stage_payment_transactions 
            SET payment_status = 'failed', 
                razorpay_payment_id = :payment_id,
                razorpay_signature = :signature,
                updated_at = NOW()
            WHERE id = :transaction_id
        ");
        $failStmt->bindValue(':payment_id', $razorpay_payment_id, PDO::PARAM_STR);
        $failStmt->bindValue(':signature', $razorpay_signature, PDO::PARAM_STR);
        $failStmt->bindValue(':transaction_id', $transaction['id'], PDO::PARAM_INT);
        $failStmt->execute();
        
        echo json_encode(['success' => false, 'message' => 'Payment signature verification failed']);
        exit;
    }
    
    // Begin transaction for atomic updates
    $db->beginTransaction();
    
    try {
        // Update payment transaction as completed
        $updateTransactionStmt = $db->prepare("
            UPDATE stage_payment_transactions 
            SET payment_status = 'completed',
                razorpay_payment_id = :payment_id,
                razorpay_signature = :signature,
                updated_at = NOW()
            WHERE id = :transaction_id
        ");
        $updateTransactionStmt->bindValue(':payment_id', $razorpay_payment_id, PDO::PARAM_STR);
        $updateTransactionStmt->bindValue(':signature', $razorpay_signature, PDO::PARAM_STR);
        $updateTransactionStmt->bindValue(':transaction_id', $transaction['id'], PDO::PARAM_INT);
        $updateTransactionStmt->execute();
        
        // Update payment request status to paid
        $updateRequestStmt = $db->prepare("
            UPDATE stage_payment_requests 
            SET status = 'paid',
                payment_date = NOW(),
                razorpay_payment_id = :payment_id,
                razorpay_order_id = :order_id
            WHERE id = :request_id
        ");
        $updateRequestStmt->bindValue(':payment_id', $razorpay_payment_id, PDO::PARAM_STR);
        $updateRequestStmt->bindValue(':order_id', $razorpay_order_id, PDO::PARAM_STR);
        $updateRequestStmt->bindValue(':request_id', $payment_request_id, PDO::PARAM_INT);
        $updateRequestStmt->execute();
        
        // Create notification for contractor
        $notificationStmt = $db->prepare("
            INSERT INTO payment_notifications (
                payment_request_id, recipient_id, recipient_type, notification_type, title, message
            ) VALUES (
                :payment_request_id, :recipient_id, 'contractor', 'payment_completed', :title, :message
            )
        ");
        
        $notification_title = "Payment Received: {$transaction['stage_name']} Stage";
        $notification_message = "Homeowner has completed payment of ₹" . number_format($transaction['amount'], 2) . 
                               " for {$transaction['stage_name']} stage. Payment ID: {$razorpay_payment_id}";
        
        $notificationStmt->bindValue(':payment_request_id', $payment_request_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':recipient_id', $transaction['contractor_id'], PDO::PARAM_INT);
        $notificationStmt->bindValue(':title', $notification_title, PDO::PARAM_STR);
        $notificationStmt->bindValue(':message', $notification_message, PDO::PARAM_STR);
        $notificationStmt->execute();
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment completed successfully',
            'data' => [
                'transaction_id' => $transaction['id'],
                'payment_request_id' => $payment_request_id,
                'razorpay_payment_id' => $razorpay_payment_id,
                'razorpay_order_id' => $razorpay_order_id,
                'amount' => $transaction['amount'],
                'stage_name' => $transaction['stage_name'],
                'payment_date' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Verify stage payment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>