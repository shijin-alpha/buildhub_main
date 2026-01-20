<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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
    $user_id = $_SESSION['user_id'] ?? null;
    $user_role = $_SESSION['role'] ?? null;
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User authentication required']);
        exit;
    }
    
    // Ensure unified payments table exists
    $db->exec("CREATE TABLE IF NOT EXISTS unified_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        payer_id INT NOT NULL,
        payee_id INT NULL,
        payment_type ENUM('technical_details', 'estimate_fee', 'stage_payment', 'final_payment') NOT NULL,
        reference_type ENUM('house_plan', 'estimate', 'project_stage', 'contract') NOT NULL,
        reference_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'INR',
        payment_status ENUM('pending', 'initiated', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        razorpay_order_id VARCHAR(255) NULL,
        razorpay_payment_id VARCHAR(255) NULL,
        razorpay_signature VARCHAR(255) NULL,
        payment_method VARCHAR(50) NULL,
        description TEXT NULL,
        metadata JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_payer (payer_id),
        INDEX idx_payee (payee_id),
        INDEX idx_reference (reference_type, reference_id),
        INDEX idx_status (payment_status),
        INDEX idx_razorpay_order (razorpay_order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get payment history
        $payment_type = $_GET['payment_type'] ?? null;
        $reference_type = $_GET['reference_type'] ?? null;
        $reference_id = isset($_GET['reference_id']) ? (int)$_GET['reference_id'] : null;
        
        $query = "SELECT up.*, 
                         payer.first_name as payer_first_name, payer.last_name as payer_last_name,
                         payee.first_name as payee_first_name, payee.last_name as payee_last_name
                  FROM unified_payments up
                  JOIN users payer ON up.payer_id = payer.id
                  LEFT JOIN users payee ON up.payee_id = payee.id
                  WHERE (up.payer_id = :user_id OR up.payee_id = :user_id)";
        
        $params = [':user_id' => $user_id];
        
        if ($payment_type) {
            $query .= " AND up.payment_type = :payment_type";
            $params[':payment_type'] = $payment_type;
        }
        
        if ($reference_type && $reference_id) {
            $query .= " AND up.reference_type = :reference_type AND up.reference_id = :reference_id";
            $params[':reference_type'] = $reference_type;
            $params[':reference_id'] = $reference_id;
        }
        
        $query .= " ORDER BY up.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'payments' => $payments
        ]);
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = isset($input['action']) ? $input['action'] : '';
        
        switch ($action) {
            case 'initiate_payment':
                $payment_type = isset($input['payment_type']) ? $input['payment_type'] : '';
                $reference_type = isset($input['reference_type']) ? $input['reference_type'] : '';
                $reference_id = isset($input['reference_id']) ? (int)$input['reference_id'] : 0;
                $amount = isset($input['amount']) ? floatval($input['amount']) : 0;
                $payee_id = isset($input['payee_id']) ? (int)$input['payee_id'] : null;
                $description = isset($input['description']) ? trim($input['description']) : '';
                $metadata = isset($input['metadata']) ? $input['metadata'] : [];
                
                if (!$payment_type || !$reference_type || $reference_id <= 0 || $amount <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid payment parameters']);
                    exit;
                }
                
                // Create Razorpay order
                $order_data = [
                    'amount' => $amount * 100, // Convert to paise
                    'currency' => 'INR',
                    'receipt' => 'payment_' . time() . '_' . $user_id,
                    'notes' => [
                        'payment_type' => $payment_type,
                        'reference_type' => $reference_type,
                        'reference_id' => $reference_id,
                        'payer_id' => $user_id
                    ]
                ];
                
                $razorpay_order = createRazorpayOrder($order_data);
                
                if (!$razorpay_order) {
                    echo json_encode(['success' => false, 'message' => 'Failed to create payment order']);
                    exit;
                }
                
                // Insert payment record
                $paymentStmt = $db->prepare("INSERT INTO unified_payments 
                                           (payer_id, payee_id, payment_type, reference_type, reference_id, amount, 
                                            payment_status, razorpay_order_id, description, metadata) 
                                           VALUES (:payer_id, :payee_id, :payment_type, :reference_type, :reference_id, 
                                                   :amount, 'initiated', :order_id, :description, :metadata)");
                
                $result = $paymentStmt->execute([
                    ':payer_id' => $user_id,
                    ':payee_id' => $payee_id,
                    ':payment_type' => $payment_type,
                    ':reference_type' => $reference_type,
                    ':reference_id' => $reference_id,
                    ':amount' => $amount,
                    ':order_id' => $razorpay_order['id'],
                    ':description' => $description,
                    ':metadata' => json_encode($metadata)
                ]);
                
                if ($result) {
                    $payment_id = $db->lastInsertId();
                    
                    echo json_encode([
                        'success' => true,
                        'payment_id' => $payment_id,
                        'razorpay_order_id' => $razorpay_order['id'],
                        'amount' => $amount,
                        'currency' => 'INR'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create payment record']);
                }
                break;
                
            case 'verify_payment':
                $payment_id = isset($input['payment_id']) ? (int)$input['payment_id'] : 0;
                $razorpay_payment_id = isset($input['razorpay_payment_id']) ? $input['razorpay_payment_id'] : '';
                $razorpay_order_id = isset($input['razorpay_order_id']) ? $input['razorpay_order_id'] : '';
                $razorpay_signature = isset($input['razorpay_signature']) ? $input['razorpay_signature'] : '';
                
                if (!$payment_id || !$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature) {
                    echo json_encode(['success' => false, 'message' => 'Payment verification data incomplete']);
                    exit;
                }
                
                // Get payment record
                $paymentStmt = $db->prepare("SELECT * FROM unified_payments WHERE id = :id AND payer_id = :payer_id AND razorpay_order_id = :order_id");
                $paymentStmt->execute([
                    ':id' => $payment_id,
                    ':payer_id' => $user_id,
                    ':order_id' => $razorpay_order_id
                ]);
                $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$payment) {
                    echo json_encode(['success' => false, 'message' => 'Payment record not found']);
                    exit;
                }
                
                // Verify Razorpay signature
                $signature_valid = verifyRazorpaySignature($razorpay_order_id, $razorpay_payment_id, $razorpay_signature);
                
                if (!$signature_valid) {
                    echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
                    exit;
                }
                
                // Update payment record
                $updateStmt = $db->prepare("UPDATE unified_payments 
                                          SET payment_status = 'completed', 
                                              razorpay_payment_id = :payment_id,
                                              razorpay_signature = :signature,
                                              payment_method = 'razorpay',
                                              updated_at = CURRENT_TIMESTAMP
                                          WHERE id = :id");
                
                $updateResult = $updateStmt->execute([
                    ':id' => $payment_id,
                    ':payment_id' => $razorpay_payment_id,
                    ':signature' => $razorpay_signature
                ]);
                
                if ($updateResult) {
                    // Handle post-payment actions based on payment type
                    handlePostPaymentActions($db, $payment);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Payment verified successfully',
                        'payment_id' => $payment_id
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update payment status']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing payment: ' . $e->getMessage()
    ]);
}

function handlePostPaymentActions($db, $payment) {
    try {
        switch ($payment['payment_type']) {
            case 'technical_details':
                // Unlock technical details access
                if ($payment['reference_type'] === 'house_plan') {
                    $updateStmt = $db->prepare("UPDATE house_plans SET technical_details_unlocked = 1 WHERE id = :id");
                    $updateStmt->execute([':id' => $payment['reference_id']]);
                }
                break;
                
            case 'estimate_fee':
                // Mark estimate as paid
                if ($payment['reference_type'] === 'estimate') {
                    $updateStmt = $db->prepare("UPDATE contractor_engagements SET status = 'paid' WHERE id = :id");
                    $updateStmt->execute([':id' => $payment['reference_id']]);
                }
                break;
                
            case 'stage_payment':
                // Update project stage payment status
                // Implementation depends on your project management system
                break;
        }
        
        // Create notification for payee if exists
        if ($payment['payee_id']) {
            $notificationStmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) 
                                             VALUES (:user_id, 'payment_received', 'Payment Received', :message, CURRENT_TIMESTAMP)");
            
            $message = "You have received a payment of ₹{$payment['amount']} for {$payment['payment_type']}.";
            
            $notificationStmt->execute([
                ':user_id' => $payment['payee_id'],
                ':message' => $message
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Post-payment action error: " . $e->getMessage());
    }
}
?>