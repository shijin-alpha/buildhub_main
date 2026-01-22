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
    
    // Validate contractor has access to this project - check multiple sources
    $projectCheckQuery = "
        SELECT 
            'construction_project' as source, cp.homeowner_id, cp.total_cost
        FROM construction_projects cp
        WHERE cp.id = ? 
        AND cp.contractor_id = ? 
        AND cp.status IN ('created', 'in_progress')
        
        UNION ALL
        
        SELECT 
            'contractor_estimate' as source, ce.homeowner_id, ce.total_cost
        FROM contractor_estimates ce
        WHERE ce.id = ? 
        AND ce.contractor_id = ? 
        AND ce.status = 'accepted'
        
        UNION ALL
        
        SELECT 
            'contractor_send_estimate' as source, cls.homeowner_id, cse.total_cost
        FROM contractor_send_estimates cse
        INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        WHERE cse.id = ? 
        AND cse.contractor_id = ? 
        AND cse.status IN ('accepted', 'project_created')
        
        LIMIT 1
    ";
    
    $projectCheckStmt = $db->prepare($projectCheckQuery);
    $projectCheckStmt->execute([
        $input['project_id'], $contractor_id,  // construction_projects
        $input['project_id'], $contractor_id,  // contractor_estimates  
        $input['project_id'], $contractor_id   // contractor_send_estimates
    ]);
    
    $projectCheck = $projectCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$projectCheck) {
        // Enhanced error message with debugging info
        echo json_encode([
            'success' => false,
            'message' => 'You do not have access to this project or project is not accepted',
            'debug' => [
                'project_id' => $input['project_id'],
                'contractor_id' => $contractor_id,
                'error_type' => 'project_access_denied',
                'suggestion' => 'Make sure the project exists and is assigned to you, and that the estimate has been accepted by the homeowner'
            ]
        ]);
        exit;
    }
    
    // Use the homeowner_id from the project check if not provided in input
    if (!isset($input['homeowner_id']) || empty($input['homeowner_id'])) {
        $input['homeowner_id'] = $projectCheck['homeowner_id'];
    }
    
    // Use the total_cost from the project if total_project_cost is not provided or is 0
    if (!isset($input['total_project_cost']) || $input['total_project_cost'] <= 0) {
        $input['total_project_cost'] = $projectCheck['total_cost'] ?? 0;
    }
    
    // Check if payment request for this stage already exists and is not rejected
    // Check stage_payment_requests table first
    $existingCheckQuery = "
        SELECT 'stage_payment_requests' as source, status
        FROM stage_payment_requests 
        WHERE project_id = ? 
        AND contractor_id = ? 
        AND stage_name = ? 
        AND status IN ('pending', 'approved', 'paid')
        LIMIT 1
    ";
    
    $existingCheckStmt = $db->prepare($existingCheckQuery);
    $existingCheckStmt->execute([
        $input['project_id'], $contractor_id, $input['stage_name']
    ]);
    
    $existingCheck = $existingCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingCheck) {
        echo json_encode([
            'success' => false,
            'message' => 'A payment request for this stage already exists',
            'debug' => [
                'existing_status' => $existingCheck['status'],
                'source_table' => $existingCheck['source'],
                'stage_name' => $input['stage_name'],
                'error_type' => 'duplicate_request'
            ]
        ]);
        exit;
    }
    
    // Validate amounts
    if ($input['requested_amount'] <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Requested amount must be greater than 0',
            'debug' => [
                'requested_amount' => $input['requested_amount'],
                'error_type' => 'invalid_amount'
            ]
        ]);
        exit;
    }
    
    // Only validate against total project cost if we have a valid total cost
    if (isset($input['total_project_cost']) && $input['total_project_cost'] > 0) {
        if ($input['requested_amount'] > $input['total_project_cost']) {
            echo json_encode([
                'success' => false,
                'message' => 'Requested amount cannot exceed total project cost',
                'debug' => [
                    'requested_amount' => $input['requested_amount'],
                    'total_project_cost' => $input['total_project_cost'],
                    'error_type' => 'amount_exceeds_total'
                ]
            ]);
            exit;
        }
    }
    
    if ($input['completion_percentage'] < 0 || $input['completion_percentage'] > 100) {
        echo json_encode([
            'success' => false,
            'message' => 'Completion percentage must be between 0 and 100',
            'debug' => [
                'completion_percentage' => $input['completion_percentage'],
                'error_type' => 'invalid_percentage'
            ]
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
                'status' => 'pending',
                'project_source' => $projectCheck['source'] ?? 'unknown'
            ]
        ]);
    } else {
        // Get more detailed error information
        $errorInfo = $insertStmt->errorInfo();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit payment request',
            'debug' => [
                'sql_error' => $errorInfo[2] ?? 'Unknown database error',
                'error_code' => $errorInfo[1] ?? 'Unknown error code',
                'error_type' => 'database_insertion_failed'
            ]
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