<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? $_GET['homeowner_id'] ?? null;
    
    // For testing purposes, default to homeowner ID 28 if no authentication
    if (!$homeowner_id) {
        $homeowner_id = 28; // Default test homeowner
        error_log("Using default homeowner ID for testing: $homeowner_id");
    }
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    // Validate required fields
    $required_fields = ['request_id', 'action'];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: $field"
            ]);
            exit;
        }
    }
    
    $request_id = $input['request_id'];
    $action = $input['action']; // 'approve' or 'reject'
    $homeowner_notes = $input['homeowner_notes'] ?? '';
    $approved_amount = $input['approved_amount'] ?? null;
    
    // Validate action
    if (!in_array($action, ['approve', 'reject'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action. Must be "approve" or "reject"'
        ]);
        exit;
    }
    
    // Get the custom payment request
    $getRequestQuery = "
        SELECT * FROM custom_payment_requests 
        WHERE id = :request_id AND homeowner_id = :homeowner_id
    ";
    
    $getRequestStmt = $db->prepare($getRequestQuery);
    $getRequestStmt->execute([
        ':request_id' => $request_id,
        ':homeowner_id' => $homeowner_id
    ]);
    
    $request = $getRequestStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode([
            'success' => false,
            'message' => 'Custom payment request not found or access denied'
        ]);
        exit;
    }
    
    // Check if request is still pending
    if ($request['status'] !== 'pending') {
        echo json_encode([
            'success' => false,
            'message' => 'This request has already been responded to'
        ]);
        exit;
    }
    
    // Prepare update data
    $new_status = $action === 'approve' ? 'approved' : 'rejected';
    $update_data = [
        ':request_id' => $request_id,
        ':status' => $new_status,
        ':homeowner_notes' => $homeowner_notes,
        ':response_date' => date('Y-m-d H:i:s')
    ];
    
    // Handle approval
    if ($action === 'approve') {
        $approved_amount = $approved_amount ?? $request['requested_amount'];
        $update_data[':approved_amount'] = $approved_amount;
        $update_data[':rejection_reason'] = null;
        
        $updateQuery = "
            UPDATE custom_payment_requests 
            SET status = :status, 
                homeowner_notes = :homeowner_notes, 
                approved_amount = :approved_amount,
                response_date = :response_date,
                rejection_reason = :rejection_reason,
                updated_at = NOW()
            WHERE id = :request_id
        ";
    } else {
        // Handle rejection
        $rejection_reason = $input['rejection_reason'] ?? 'Request rejected by homeowner';
        $update_data[':rejection_reason'] = $rejection_reason;
        $update_data[':approved_amount'] = null;
        
        $updateQuery = "
            UPDATE custom_payment_requests 
            SET status = :status, 
                homeowner_notes = :homeowner_notes, 
                rejection_reason = :rejection_reason,
                response_date = :response_date,
                approved_amount = :approved_amount,
                updated_at = NOW()
            WHERE id = :request_id
        ";
    }
    
    $updateStmt = $db->prepare($updateQuery);
    $result = $updateStmt->execute($update_data);
    
    if ($result) {
        // Get contractor details for notification
        $contractorQuery = "SELECT first_name, last_name, email FROM users WHERE id = :contractor_id";
        $contractorStmt = $db->prepare($contractorQuery);
        $contractorStmt->execute([':contractor_id' => $request['contractor_id']]);
        $contractor = $contractorStmt->fetch(PDO::FETCH_ASSOC);
        
        $response_data = [
            'success' => true,
            'message' => $action === 'approve' 
                ? 'Custom payment request approved successfully' 
                : 'Custom payment request rejected',
            'data' => [
                'request_id' => $request_id,
                'action' => $action,
                'status' => $new_status,
                'request_title' => $request['request_title'],
                'requested_amount' => $request['requested_amount'],
                'approved_amount' => $approved_amount ?? null,
                'contractor_name' => $contractor ? $contractor['first_name'] . ' ' . $contractor['last_name'] : 'Contractor',
                'homeowner_notes' => $homeowner_notes,
                'rejection_reason' => $rejection_reason ?? null
            ]
        ];
        
        echo json_encode($response_data);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update custom payment request'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Respond to custom payment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error processing response: ' . $e->getMessage()
    ]);
}
?>