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
    
    $razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
    $razorpay_order_id = $input['razorpay_order_id'] ?? '';
    $razorpay_signature = $input['razorpay_signature'] ?? '';
    $payment_id = isset($input['payment_id']) ? (int)$input['payment_id'] : 0;

    if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature)) {
        echo json_encode(['success' => false, 'message' => 'Payment verification data is incomplete']);
        exit;
    }

    // Get payment record
    $paymentStmt = $db->prepare("
        SELECT * FROM technical_details_payments 
        WHERE id = :payment_id AND homeowner_id = :homeowner_id AND razorpay_order_id = :order_id
    ");
    $paymentStmt->execute([
        ':payment_id' => $payment_id,
        ':homeowner_id' => $homeowner_id,
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
        // Update payment status to failed
        $updateStmt = $db->prepare("
            UPDATE technical_details_payments 
            SET payment_status = 'failed', updated_at = CURRENT_TIMESTAMP
            WHERE id = :payment_id
        ");
        $updateStmt->execute([':payment_id' => $payment_id]);
        
        echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
        exit;
    }

    // Update payment status to completed
    $updateStmt = $db->prepare("
        UPDATE technical_details_payments 
        SET payment_status = 'completed', 
            razorpay_payment_id = :payment_id,
            razorpay_signature = :signature,
            payment_method = 'razorpay',
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
    ");
    
    $updateStmt->execute([
        ':id' => $payment_id,
        ':payment_id' => $razorpay_payment_id,
        ':signature' => $razorpay_signature
    ]);

    // Get house plan details for notification
    $planStmt = $db->prepare("
        SELECT hp.plan_name, hp.architect_id 
        FROM house_plans hp
        WHERE hp.id = :plan_id
    ");
    $planStmt->execute([':plan_id' => $payment['house_plan_id']]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

    // Create notification for successful payment
    $notificationStmt = $db->prepare("
        INSERT INTO notifications (user_id, type, title, message, related_id, created_at)
        VALUES (:user_id, 'payment_success', 'Technical Details Unlocked', :message, :plan_id, CURRENT_TIMESTAMP)
    ");
    
    $message = sprintf(
        'Payment of ₹%.2f successful! Technical details for "%s" are now unlocked and available for viewing.',
        $payment['amount'],
        $plan['plan_name'] ?? 'House Plan'
    );
    
    $notificationStmt->execute([
        ':user_id' => $homeowner_id,
        ':message' => $message,
        ':plan_id' => $payment['house_plan_id']
    ]);

    // Notify architect about the payment
    if ($plan['architect_id']) {
        $architectNotificationStmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_id, created_at)
            VALUES (:user_id, 'technical_details_purchased', 'Technical Details Purchased', :message, :plan_id, CURRENT_TIMESTAMP)
        ");
        
        $architectMessage = sprintf(
            'Homeowner has purchased technical details for "%s" (₹%.2f). They now have full access to your technical specifications.',
            $plan['plan_name'] ?? 'House Plan',
            $payment['amount']
        );
        
        $architectNotificationStmt->execute([
            ':user_id' => $plan['architect_id'],
            ':message' => $architectMessage,
            ':plan_id' => $payment['house_plan_id']
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully',
        'payment_id' => $payment_id,
        'amount' => $payment['amount'],
        'house_plan_id' => $payment['house_plan_id']
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>