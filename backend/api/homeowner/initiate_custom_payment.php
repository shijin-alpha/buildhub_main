<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

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
require_once '../../config/payment_limits.php';

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
    $amount = isset($input['amount']) ? $input['amount'] : 0;
    
    // Log the received data for debugging
    error_log("Custom payment initiation - Request ID: $payment_request_id, Amount received: " . var_export($amount, true) . " (type: " . gettype($amount) . ")");
    
    // Validation
    if ($payment_request_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment request ID']);
        exit;
    }
    
    // Validate payment amount using the new limits configuration (₹20 lakh limit)
    $validation = validatePaymentAmount($amount);
    if (!$validation['valid']) {
        // Check if this should use split payment instead
        $maxAmount = getMaxPaymentAmount();
        if ($amount > $maxAmount) {
            echo json_encode([
                'success' => false, 
                'message' => "Payment amount ₹" . number_format($amount, 2) . " exceeds single payment limit of ₹" . number_format($maxAmount, 2) . ". Please use the split payment system for amounts above ₹20 lakhs.",
                'requires_split' => true,
                'max_single_amount' => $maxAmount,
                'suggested_action' => 'Use split payment system'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $validation['message']]);
        }
        exit;
    }
    
    // Get custom payment request details and verify ownership
    $requestCheck = $db->prepare("
        SELECT cpr.*, 
               u_contractor.first_name as contractor_first_name, 
               u_contractor.last_name as contractor_last_name,
               u_homeowner.first_name as homeowner_first_name, 
               u_homeowner.last_name as homeowner_last_name
        FROM custom_payment_requests cpr
        LEFT JOIN users u_contractor ON cpr.contractor_id = u_contractor.id
        LEFT JOIN users u_homeowner ON cpr.homeowner_id = u_homeowner.id
        WHERE cpr.id = :request_id AND cpr.homeowner_id = :homeowner_id 
        AND cpr.status = 'approved'
        LIMIT 1
    ");
    $requestCheck->bindValue(':request_id', $payment_request_id, PDO::PARAM_INT);
    $requestCheck->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $requestCheck->execute();
    $request = $requestCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Custom payment request not found or not eligible for payment']);
        exit;
    }
    
    // Cancel any pending alternative payments for this request
    try {
        $cancelAltStmt = $db->prepare("
            UPDATE alternative_payments 
            SET payment_status = 'cancelled',
                updated_at = NOW()
            WHERE reference_id = :request_id 
            AND payment_type = 'custom_payment'
            AND payment_status IN ('initiated', 'pending')
        ");
        $cancelAltStmt->execute([':request_id' => $payment_request_id]);
        
        if ($cancelAltStmt->rowCount() > 0) {
            error_log("Cancelled " . $cancelAltStmt->rowCount() . " pending alternative payments for custom request ID: $payment_request_id");
        }
    } catch (Exception $e) {
        // Non-critical error, continue with Razorpay payment
        error_log("Failed to cancel alternative payments: " . $e->getMessage());
    }
    
    // Flexible amount validation - allow any amount up to the approved amount
    $max_allowed_amount = (float)($request['approved_amount'] ?? $request['requested_amount']); // Use approved amount if available
    $input_amount = (float)$amount;
    
    // Handle common frontend issues
    // If amount is 100x larger, it might be in paise instead of rupees
    if ($input_amount > $max_allowed_amount * 50 && abs($input_amount - ($max_allowed_amount * 100)) <= 0.01) {
        $input_amount = $input_amount / 100; // Convert paise to rupees
    }
    
    // Remove any formatting issues (commas, etc.)
    if (is_string($amount)) {
        $input_amount = (float)str_replace(',', '', $amount);
    }
    
    // Validate amount is positive and within allowed range
    if ($input_amount <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => "Payment amount must be greater than zero"
        ]);
        exit;
    }
    
    if ($input_amount > $max_allowed_amount) {
        echo json_encode([
            'success' => false, 
            'message' => "Payment amount ₹" . number_format($input_amount, 2) . " exceeds maximum approved amount of ₹" . number_format($max_allowed_amount, 2) . " for this custom request"
        ]);
        exit;
    }
    
    // Use the input amount (user can pay any amount up to the approved limit)
    $amount = $input_amount;
    
    // Create custom payment transactions table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS custom_payment_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            payment_request_id INT NOT NULL,
            homeowner_id INT NOT NULL,
            contractor_id INT NOT NULL,
            amount DECIMAL(15,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'INR',
            razorpay_order_id VARCHAR(255) NULL,
            razorpay_payment_id VARCHAR(255) NULL,
            razorpay_signature VARCHAR(255) NULL,
            payment_status ENUM('created', 'pending', 'completed', 'failed', 'cancelled') DEFAULT 'created',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_payment_request_id (payment_request_id),
            INDEX idx_homeowner_id (homeowner_id),
            INDEX idx_contractor_id (contractor_id),
            INDEX idx_razorpay_order_id (razorpay_order_id),
            INDEX idx_payment_status (payment_status)
        )
    ");
    
    // Check if payment transaction already exists for this request
    $existingCheck = $db->prepare("
        SELECT id, razorpay_order_id, payment_status 
        FROM custom_payment_transactions 
        WHERE payment_request_id = :request_id AND payment_status IN ('created', 'pending')
        LIMIT 1
    ");
    $existingCheck->bindValue(':request_id', $payment_request_id, PDO::PARAM_INT);
    $existingCheck->execute();
    $existingTransaction = $existingCheck->fetch(PDO::FETCH_ASSOC);
    
    $razorpay_order_id = null;
    $transaction_id = null;
    
    if ($existingTransaction) {
        // Use existing transaction
        $razorpay_order_id = $existingTransaction['razorpay_order_id'];
        $transaction_id = $existingTransaction['id'];
    } else {
        // Create new Razorpay order
        $amount_in_paise = (int)($amount * 100); // Convert to paise
        $receipt = 'custom_payment_' . $payment_request_id . '_' . time();
        
        try {
            $razorpay_order = createRazorpayOrder($amount_in_paise, 'INR', $receipt);
            $razorpay_order_id = $razorpay_order['id'];
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create payment order: ' . $e->getMessage()
            ]);
            exit;
        }
        
        // Insert payment transaction record
        $insertStmt = $db->prepare("
            INSERT INTO custom_payment_transactions (
                payment_request_id, homeowner_id, contractor_id, amount, 
                razorpay_order_id, payment_status
            ) VALUES (
                :payment_request_id, :homeowner_id, :contractor_id, :amount,
                :razorpay_order_id, 'created'
            )
        ");
        
        $insertStmt->bindValue(':payment_request_id', $payment_request_id, PDO::PARAM_INT);
        $insertStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
        $insertStmt->bindValue(':contractor_id', $request['contractor_id'], PDO::PARAM_INT);
        $insertStmt->bindValue(':amount', $amount, PDO::PARAM_STR);
        $insertStmt->bindValue(':razorpay_order_id', $razorpay_order_id, PDO::PARAM_STR);
        
        if (!$insertStmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to create payment transaction']);
            exit;
        }
        
        $transaction_id = $db->lastInsertId();
    }
    
    // Return payment details for frontend
    echo json_encode([
        'success' => true,
        'message' => 'Custom payment order created successfully',
        'data' => [
            'transaction_id' => $transaction_id,
            'payment_request_id' => $payment_request_id,
            'razorpay_order_id' => $razorpay_order_id,
            'razorpay_key_id' => getRazorpayKeyId(),
            'amount' => $amount_in_paise, // Amount in paise for Razorpay
            'currency' => 'INR',
            'request_title' => $request['request_title'],
            'category' => $request['category'],
            'contractor_name' => $request['contractor_first_name'] . ' ' . $request['contractor_last_name'],
            'homeowner_name' => $request['homeowner_first_name'] . ' ' . $request['homeowner_last_name'],
            'payment_type' => 'custom'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Initiate custom payment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>