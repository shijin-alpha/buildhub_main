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
    
    $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;
    $action = trim($input['action'] ?? ''); // 'approve' or 'reject'
    $homeowner_notes = trim($input['homeowner_notes'] ?? '');
    $rejection_reason = trim($input['rejection_reason'] ?? '');
    
    // Validation
    if ($request_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
        exit;
    }
    
    if (!in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid action. Must be approve or reject']);
        exit;
    }
    
    if ($action === 'reject' && empty($rejection_reason)) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
        exit;
    }
    
    // Get payment request details and verify ownership
    $requestCheck = $db->prepare("
        SELECT spr.*, 
               u_contractor.first_name as contractor_first_name, 
               u_contractor.last_name as contractor_last_name
        FROM stage_payment_requests spr
        LEFT JOIN users u_contractor ON spr.contractor_id = u_contractor.id
        WHERE spr.id = :request_id AND spr.homeowner_id = :homeowner_id AND spr.status = 'pending'
        LIMIT 1
    ");
    $requestCheck->bindValue(':request_id', $request_id, PDO::PARAM_INT);
    $requestCheck->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $requestCheck->execute();
    $request = $requestCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Payment request not found or already processed']);
        exit;
    }
    
    // Update payment request status
    $new_status = $action === 'approve' ? 'approved' : 'rejected';
    
    $updateStmt = $db->prepare("
        UPDATE stage_payment_requests 
        SET status = :status, 
            response_date = NOW(),
            homeowner_notes = :homeowner_notes,
            rejection_reason = :rejection_reason
        WHERE id = :request_id
    ");
    
    $updateStmt->bindValue(':status', $new_status, PDO::PARAM_STR);
    $updateStmt->bindValue(':homeowner_notes', $homeowner_notes, PDO::PARAM_STR);
    $updateStmt->bindValue(':rejection_reason', $action === 'reject' ? $rejection_reason : null, PDO::PARAM_STR);
    $updateStmt->bindValue(':request_id', $request_id, PDO::PARAM_INT);
    
    if ($updateStmt->execute()) {
        // Create notification for contractor
        $notification_type = $action === 'approve' ? 'request_approved' : 'request_rejected';
        $notification_title = $action === 'approve' 
            ? "Payment Approved: {$request['stage_name']} Stage"
            : "Payment Rejected: {$request['stage_name']} Stage";
        
        $notification_message = $action === 'approve'
            ? "Homeowner has approved your payment request of ₹" . number_format($request['requested_amount'], 2) . 
              " for {$request['stage_name']} stage. Payment will be processed soon."
            : "Homeowner has rejected your payment request of ₹" . number_format($request['requested_amount'], 2) . 
              " for {$request['stage_name']} stage. Reason: " . $rejection_reason;
        
        if ($homeowner_notes) {
            $notification_message .= " Notes: " . $homeowner_notes;
        }
        
        $notificationStmt = $db->prepare("
            INSERT INTO payment_notifications (
                payment_request_id, recipient_id, recipient_type, notification_type, title, message
            ) VALUES (
                :payment_request_id, :recipient_id, 'contractor', :notification_type, :title, :message
            )
        ");
        
        $notificationStmt->bindValue(':payment_request_id', $request_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':recipient_id', $request['contractor_id'], PDO::PARAM_INT);
        $notificationStmt->bindValue(':notification_type', $notification_type, PDO::PARAM_STR);
        $notificationStmt->bindValue(':title', $notification_title, PDO::PARAM_STR);
        $notificationStmt->bindValue(':message', $notification_message, PDO::PARAM_STR);
        $notificationStmt->execute();
        
        $response_message = $action === 'approve' 
            ? 'Payment request approved successfully'
            : 'Payment request rejected successfully';
        
        echo json_encode([
            'success' => true,
            'message' => $response_message,
            'data' => [
                'request_id' => $request_id,
                'new_status' => $new_status,
                'stage_name' => $request['stage_name'],
                'requested_amount' => $request['requested_amount'],
                'contractor_name' => $request['contractor_first_name'] . ' ' . $request['contractor_last_name'],
                'action' => $action
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update payment request']);
    }
    
} catch (Exception $e) {
    error_log("Respond payment request error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>