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
    $homeowner_id = $_SESSION['user_id'] ?? 28; // Default for testing
    
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
    $razorpay_order_id = isset($input['razorpay_order_id']) ? $input['razorpay_order_id'] : '';
    $razorpay_payment_id = isset($input['razorpay_payment_id']) ? $input['razorpay_payment_id'] : '';
    $razorpay_signature = isset($input['razorpay_signature']) ? $input['razorpay_signature'] : '';
    
    // Validation
    if ($payment_request_id <= 0 || empty($razorpay_order_id) || empty($razorpay_payment_id) || empty($razorpay_signature)) {
        echo json_encode(['success' => false, 'message' => 'Missing required payment verification data']);
        exit;
    }
    
    // Get payment transaction
    $transactionStmt = $db->prepare("
        SELECT cpt.*, cpr.request_title, cpr.category, cpr.contractor_id
        FROM custom_payment_transactions cpt
        JOIN custom_payment_requests cpr ON cpt.payment_request_id = cpr.id
        WHERE cpt.payment_request_id = :request_id 
        AND cpt.homeowner_id = :homeowner_id 
        AND cpt.razorpay_order_id = :order_id
        AND cpt.payment_status IN ('created', 'pending')
        LIMIT 1
    ");
    $transactionStmt->execute([
        ':request_id' => $payment_request_id,
        ':homeowner_id' => $homeowner_id,
        ':order_id' => $razorpay_order_id
    ]);
    $transaction = $transactionStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        echo json_encode(['success' => false, 'message' => 'Payment transaction not found']);
        exit;
    }
    
    // Verify payment signature with Razorpay
    try {
        $isValidSignature = verifyRazorpaySignature(
            $razorpay_order_id,
            $razorpay_payment_id,
            $razorpay_signature
        );
        
        if (!$isValidSignature) {
            echo json_encode(['success' => false, 'message' => 'Invalid payment signature']);
            exit;
        }
    } catch (Exception $e) {
        error_log("Razorpay signature verification error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
        exit;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Update payment transaction
        $updateTransactionStmt = $db->prepare("
            UPDATE custom_payment_transactions 
            SET razorpay_payment_id = :payment_id,
                razorpay_signature = :signature,
                payment_status = 'completed',
                updated_at = NOW()
            WHERE id = :transaction_id
        ");
        $updateTransactionStmt->execute([
            ':payment_id' => $razorpay_payment_id,
            ':signature' => $razorpay_signature,
            ':transaction_id' => $transaction['id']
        ]);
        
        // Update custom payment request status
        $updateRequestStmt = $db->prepare("
            UPDATE custom_payment_requests 
            SET status = 'paid',
                payment_date = NOW(),
                payment_method = 'razorpay',
                transaction_reference = :payment_id,
                updated_at = NOW()
            WHERE id = :request_id
        ");
        $updateRequestStmt->execute([
            ':payment_id' => $razorpay_payment_id,
            ':request_id' => $payment_request_id
        ]);
        
        // Create notification for contractor
        try {
            $notificationStmt = $db->prepare("
                INSERT INTO notifications (
                    recipient_id, recipient_type, notification_type, title, message, 
                    reference_id, reference_type, created_at
                ) VALUES (
                    :contractor_id, 'contractor', 'payment_received', 
                    'Custom Payment Received', 
                    :message,
                    :request_id, 'custom_payment_request', NOW()
                )
            ");
            
            $notificationMessage = "You have received a payment of ₹" . number_format($transaction['amount'], 2) . 
                                 " for custom request: " . $transaction['request_title'] . 
                                 ". Transaction ID: " . $razorpay_payment_id;
            
            $notificationStmt->execute([
                ':contractor_id' => $transaction['contractor_id'],
                ':message' => $notificationMessage,
                ':request_id' => $payment_request_id
            ]);
        } catch (Exception $e) {
            // Non-critical error, continue
            error_log("Failed to create contractor notification: " . $e->getMessage());
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Custom payment verified and completed successfully',
            'data' => [
                'payment_request_id' => $payment_request_id,
                'transaction_id' => $transaction['id'],
                'razorpay_payment_id' => $razorpay_payment_id,
                'amount' => $transaction['amount'],
                'request_title' => $transaction['request_title'],
                'category' => $transaction['category'],
                'payment_date' => date('Y-m-d H:i:s'),
                'status' => 'paid'
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Verify custom payment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Payment verification failed: ' . $e->getMessage()
    ]);
}
?>