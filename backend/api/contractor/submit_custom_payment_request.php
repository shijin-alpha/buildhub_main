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
    $contractor_id = $_SESSION['user_id'] ?? null;
    
    if (!$contractor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Contractor not authenticated'
        ]);
        exit;
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
    $required_fields = [
        'project_id', 'homeowner_id', 'request_title', 'request_reason',
        'requested_amount', 'work_description', 'urgency_level'
    ];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: $field"
            ]);
            exit;
        }
    }
    
    // Validate contractor has access to this project
    $projectCheckQuery = "
        SELECT COUNT(*) as count 
        FROM contractor_send_estimates cse
        WHERE cse.id = :project_id 
        AND cse.contractor_id = :contractor_id 
        AND cse.status IN ('accepted', 'project_created')
    ";
    
    $projectCheckStmt = $db->prepare($projectCheckQuery);
    $projectCheckStmt->execute([
        ':project_id' => $input['project_id'],
        ':contractor_id' => $contractor_id
    ]);
    
    $projectCheck = $projectCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($projectCheck['count'] == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'You do not have access to this project or project is not accepted'
        ]);
        exit;
    }
    
    // Validate amounts
    if ($input['requested_amount'] <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Requested amount must be greater than 0'
        ]);
        exit;
    }
    
    // Insert custom payment request
    $insertQuery = "
        INSERT INTO custom_payment_requests (
            project_id, contractor_id, homeowner_id, request_title,
            request_reason, requested_amount, work_description, urgency_level,
            category, contractor_notes, status, request_date, created_at, updated_at
        ) VALUES (
            :project_id, :contractor_id, :homeowner_id, :request_title,
            :request_reason, :requested_amount, :work_description, :urgency_level,
            :category, :contractor_notes, 'pending', NOW(), NOW(), NOW()
        )
    ";
    
    $insertStmt = $db->prepare($insertQuery);
    $result = $insertStmt->execute([
        ':project_id' => $input['project_id'],
        ':contractor_id' => $contractor_id,
        ':homeowner_id' => $input['homeowner_id'],
        ':request_title' => $input['request_title'],
        ':request_reason' => $input['request_reason'],
        ':requested_amount' => $input['requested_amount'],
        ':work_description' => $input['work_description'],
        ':urgency_level' => $input['urgency_level'],
        ':category' => $input['category'] ?? null,
        ':contractor_notes' => $input['contractor_notes'] ?? ''
    ]);
    
    if ($result) {
        $custom_request_id = $db->lastInsertId();
        
        // Get homeowner details for notification
        $homeownerQuery = "SELECT first_name, last_name, email FROM users WHERE id = :homeowner_id";
        $homeownerStmt = $db->prepare($homeownerQuery);
        $homeownerStmt->execute([':homeowner_id' => $input['homeowner_id']]);
        $homeowner = $homeownerStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Custom payment request submitted successfully',
            'data' => [
                'custom_request_id' => $custom_request_id,
                'request_title' => $input['request_title'],
                'requested_amount' => $input['requested_amount'],
                'homeowner_name' => $homeowner ? $homeowner['first_name'] . ' ' . $homeowner['last_name'] : 'Homeowner',
                'status' => 'pending',
                'urgency_level' => $input['urgency_level']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit custom payment request'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Submit custom payment request error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting custom payment request: ' . $e->getMessage()
    ]);
}
?>