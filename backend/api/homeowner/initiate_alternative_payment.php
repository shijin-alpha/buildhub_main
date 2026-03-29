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
require_once '../../config/alternative_payment_config.php';

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
    $amount = isset($input['amount']) ? (float)$input['amount'] : 0;
    $payment_method = $input['payment_method'] ?? '';
    $homeowner_notes = $input['notes'] ?? '';
    
    // Validation
    if ($reference_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid reference ID']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment amount']);
        exit;
    }
    
    if (empty($payment_method)) {
        echo json_encode(['success' => false, 'message' => 'Payment method is required']);
        exit;
    }
    
    // Validate payment method
    $methodValidation = validatePaymentMethod($payment_method, $amount);
    if (!$methodValidation['valid']) {
        echo json_encode([
            'success' => false,
            'message' => $methodValidation['message']
        ]);
        exit;
    }
    
    // Get contractor details based on payment type
    $contractor_id = null;
    $description = '';
    
    if ($payment_type === 'technical_details') {
        $planStmt = $db->prepare("
            SELECT hp.*, lr.user_id as request_owner_id, hp.architect_id as contractor_id
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
        
        $contractor_id = $plan['contractor_id'];
        $description = 'Technical Details Payment: ' . ($plan['plan_name'] ?? 'House Plan');
        
    } else {
        // For stage payments, get from stage_payment_requests table
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
    
    // Generate payment instructions
    $instructions = generatePaymentInstructions($payment_method, $amount, $contractor_id, $reference_id);
    
    // Create alternative payment record
    $insertStmt = $db->prepare("
        INSERT INTO alternative_payments (
            payment_type, reference_id, homeowner_id, contractor_id, amount,
            payment_method, payment_status, payment_instructions, homeowner_notes
        ) VALUES (
            :payment_type, :reference_id, :homeowner_id, :contractor_id, :amount,
            :payment_method, 'initiated', :instructions, :notes
        )
    ");
    
    $insertStmt->execute([
        ':payment_type' => $payment_type,
        ':reference_id' => $reference_id,
        ':homeowner_id' => $homeowner_id,
        ':contractor_id' => $contractor_id,
        ':amount' => $amount,
        ':payment_method' => $payment_method,
        ':instructions' => json_encode($instructions),
        ':notes' => $homeowner_notes
    ]);
    
    $payment_id = $db->lastInsertId();
    
    // Get contractor bank details
    $bankDetails = null;
    if ($contractor_id) {
        $bankStmt = $db->prepare("
            SELECT * FROM contractor_bank_details 
            WHERE contractor_id = :contractor_id AND is_verified = TRUE
        ");
        $bankStmt->execute([':contractor_id' => $contractor_id]);
        $bankDetails = $bankStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Create notification for contractor
    if ($contractor_id) {
        $notificationStmt = $db->prepare("
            INSERT INTO alternative_payment_notifications (
                payment_id, recipient_id, recipient_type, notification_type, title, message
            ) VALUES (
                :payment_id, :recipient_id, 'contractor', 'payment_initiated', :title, :message
            )
        ");
        
        $methodDetails = $methodValidation['method_details'];
        $notificationTitle = "New {$methodDetails['name']} Payment";
        $notificationMessage = "Homeowner has initiated a {$methodDetails['name']} payment of ₹" . number_format($amount, 2) . 
                              " for {$description}. Please check payment details and provide verification once received.";
        
        $notificationStmt->execute([
            ':payment_id' => $payment_id,
            ':recipient_id' => $contractor_id,
            ':title' => $notificationTitle,
            ':message' => $notificationMessage
        ]);
    }
    
    // Create notification for homeowner
    $homeownerNotificationStmt = $db->prepare("
        INSERT INTO alternative_payment_notifications (
            payment_id, recipient_id, recipient_type, notification_type, title, message
        ) VALUES (
            :payment_id, :recipient_id, 'homeowner', 'payment_initiated', :title, :message
        )
    ");
    
    $homeownerTitle = "Payment Instructions Ready";
    $homeownerMessage = "Your {$methodValidation['method_details']['name']} payment of ₹" . number_format($amount, 2) . 
                       " has been set up. Please follow the instructions to complete the payment.";
    
    $homeownerNotificationStmt->execute([
        ':payment_id' => $payment_id,
        ':recipient_id' => $homeowner_id,
        ':title' => $homeownerTitle,
        ':message' => $homeownerMessage
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Alternative payment initiated successfully',
        'data' => [
            'payment_id' => $payment_id,
            'payment_method' => $payment_method,
            'method_details' => $methodValidation['method_details'],
            'amount' => $amount,
            'description' => $description,
            'instructions' => $instructions,
            'bank_details' => $bankDetails,
            'verification_required' => true,
            'next_steps' => [
                'Follow the payment instructions provided',
                'Complete the payment using your chosen method',
                'Upload payment proof/receipt for verification',
                'Wait for contractor/admin verification',
                'Payment will be marked as completed once verified'
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Alternative payment initiation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>