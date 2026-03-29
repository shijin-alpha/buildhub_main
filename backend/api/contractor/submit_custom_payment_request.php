<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../utils/PaymentRequestValidator.php';

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
    
    // Add contractor_id to input for validation
    $input['contractor_id'] = $contractor_id;
    
    // Get project data for validation
    $project_data = [];
    if (isset($input['project_id'])) {
        $projectQuery = "
            SELECT cse.total_cost, cse.timeline, cls.homeowner_id
            FROM contractor_send_estimates cse
            INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
            WHERE cse.id = :project_id AND cse.contractor_id = :contractor_id
            LIMIT 1
        ";
        
        $projectStmt = $db->prepare($projectQuery);
        $projectStmt->execute([
            ':project_id' => $input['project_id'],
            ':contractor_id' => $contractor_id
        ]);
        
        $project_data = $projectStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        
        if (empty($project_data)) {
            echo json_encode([
                'success' => false,
                'message' => 'Project not found or contractor not assigned'
            ]);
            exit;
        }
        
        // Add homeowner_id to input
        $input['homeowner_id'] = $project_data['homeowner_id'];
    }
    
    // Comprehensive validation using PaymentRequestValidator
    $validation_result = PaymentRequestValidator::validateCustomPaymentRequest($input, $project_data);
    
    if (!$validation_result['is_valid']) {
        echo json_encode([
            'success' => false,
            'message' => 'Payment request validation failed',
            'validation_errors' => $validation_result['errors'],
            'validation_warnings' => $validation_result['warnings'],
            'validation_score' => $validation_result['validation_score'],
            'validation_summary' => PaymentRequestValidator::getValidationSummary($validation_result)
        ]);
        exit;
    }
    
    // Validate required fields (basic check already done in validator)
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
    
    // Validate contractor has access to this project (already done above)
    
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
            ],
            'validation_result' => [
                'score' => $validation_result['validation_score'],
                'warnings' => $validation_result['warnings'],
                'recommendations' => $validation_result['recommendations'] ?? [],
                'summary' => PaymentRequestValidator::getValidationSummary($validation_result)
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