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
        'project_id', 'homeowner_id', 'stage_name', 'requested_amount',
        'work_description', 'completion_percentage', 'labor_count',
        'total_project_cost', 'quality_check', 'safety_compliance'
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
        AND cse.status = 'accepted'
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
    
    // Check if payment request for this stage already exists and is not rejected
    $existingCheckQuery = "
        SELECT COUNT(*) as count 
        FROM stage_payment_requests 
        WHERE project_id = :project_id 
        AND contractor_id = :contractor_id 
        AND stage_name = :stage_name 
        AND status IN ('pending', 'approved', 'paid')
    ";
    
    $existingCheckStmt = $db->prepare($existingCheckQuery);
    $existingCheckStmt->execute([
        ':project_id' => $input['project_id'],
        ':contractor_id' => $contractor_id,
        ':stage_name' => $input['stage_name']
    ]);
    
    $existingCheck = $existingCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingCheck['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'A payment request for this stage already exists'
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
    
    if ($input['requested_amount'] > $input['total_project_cost']) {
        echo json_encode([
            'success' => false,
            'message' => 'Requested amount cannot exceed total project cost'
        ]);
        exit;
    }
    
    if ($input['completion_percentage'] < 0 || $input['completion_percentage'] > 100) {
        echo json_encode([
            'success' => false,
            'message' => 'Completion percentage must be between 0 and 100'
        ]);
        exit;
    }
    
    // Insert payment request
    $insertQuery = "
        INSERT INTO stage_payment_requests (
            project_id, contractor_id, homeowner_id, stage_name, 
            requested_amount, completion_percentage, work_description,
            materials_used, labor_count, work_start_date, work_end_date,
            contractor_notes, quality_check, safety_compliance,
            total_project_cost, status, request_date, created_at, updated_at
        ) VALUES (
            :project_id, :contractor_id, :homeowner_id, :stage_name,
            :requested_amount, :completion_percentage, :work_description,
            :materials_used, :labor_count, :work_start_date, :work_end_date,
            :contractor_notes, :quality_check, :safety_compliance,
            :total_project_cost, 'pending', NOW(), NOW(), NOW()
        )
    ";
    
    $insertStmt = $db->prepare($insertQuery);
    $result = $insertStmt->execute([
        ':project_id' => $input['project_id'],
        ':contractor_id' => $contractor_id,
        ':homeowner_id' => $input['homeowner_id'],
        ':stage_name' => $input['stage_name'],
        ':requested_amount' => $input['requested_amount'],
        ':completion_percentage' => $input['completion_percentage'],
        ':work_description' => $input['work_description'],
        ':materials_used' => $input['materials_used'] ?? '',
        ':labor_count' => $input['labor_count'],
        ':work_start_date' => $input['work_start_date'] ?: null,
        ':work_end_date' => $input['work_end_date'] ?: null,
        ':contractor_notes' => $input['contractor_notes'] ?? '',
        ':quality_check' => $input['quality_check'] ? 1 : 0,
        ':safety_compliance' => $input['safety_compliance'] ? 1 : 0,
        ':total_project_cost' => $input['total_project_cost']
    ]);
    
    if ($result) {
        $payment_request_id = $db->lastInsertId();
        
        // Get homeowner details for notification
        $homeownerQuery = "SELECT first_name, last_name, email FROM users WHERE id = :homeowner_id";
        $homeownerStmt = $db->prepare($homeownerQuery);
        $homeownerStmt->execute([':homeowner_id' => $input['homeowner_id']]);
        $homeowner = $homeownerStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment request submitted successfully',
            'data' => [
                'payment_request_id' => $payment_request_id,
                'stage_name' => $input['stage_name'],
                'requested_amount' => $input['requested_amount'],
                'homeowner_name' => $homeowner ? $homeowner['first_name'] . ' ' . $homeowner['last_name'] : 'Homeowner',
                'status' => 'pending'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit payment request'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Submit payment request error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting payment request: ' . $e->getMessage()
    ]);
}
?>