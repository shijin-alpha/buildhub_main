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
    $contractor_id = $_SESSION['user_id'] ?? null;
    
    if (!$contractor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Contractor not authenticated'
        ]);
        exit;
    }
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    $project_id = isset($input['project_id']) ? (int)$input['project_id'] : 0;
    $stage_name = trim($input['stage_name'] ?? '');
    $requested_amount = isset($input['requested_amount']) ? (float)$input['requested_amount'] : 0;
    $work_description = trim($input['work_description'] ?? '');
    $completion_percentage = isset($input['completion_percentage']) ? (float)$input['completion_percentage'] : 0;
    $contractor_notes = trim($input['contractor_notes'] ?? '');
    $progress_update_id = isset($input['progress_update_id']) ? (int)$input['progress_update_id'] : null;
    
    // Validation
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        exit;
    }
    
    if (empty($stage_name)) {
        echo json_encode(['success' => false, 'message' => 'Stage name is required']);
        exit;
    }
    
    if ($requested_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Requested amount must be greater than 0']);
        exit;
    }
    
    if (empty($work_description)) {
        echo json_encode(['success' => false, 'message' => 'Work description is required']);
        exit;
    }
    
    if ($completion_percentage < 0 || $completion_percentage > 100) {
        echo json_encode(['success' => false, 'message' => 'Completion percentage must be between 0 and 100']);
        exit;
    }
    
    // Verify contractor is assigned to this project and get project details
    $projectCheck = $db->prepare("
        SELECT cse.id, cse.homeowner_id, cse.total_cost, cse.homeowner_first_name, cse.homeowner_last_name
        FROM contractor_send_estimates cse 
        WHERE cse.id = :project_id AND cse.contractor_id = :contractor_id
        LIMIT 1
    ");
    $projectCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $projectCheck->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $projectCheck->execute();
    $project = $projectCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found or contractor not assigned']);
        exit;
    }
    
    $homeowner_id = $project['homeowner_id'];
    $total_cost = $project['total_cost'];
    
    // Calculate percentage of total project cost
    $percentage_of_total = ($requested_amount / $total_cost) * 100;
    
    // Check if there's already a pending request for this stage
    $existingCheck = $db->prepare("
        SELECT id FROM project_stage_payment_requests 
        WHERE project_id = :project_id AND stage_name = :stage_name AND status = 'pending'
        LIMIT 1
    ");
    $existingCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $existingCheck->bindValue(':stage_name', $stage_name, PDO::PARAM_STR);
    $existingCheck->execute();
    
    if ($existingCheck->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'There is already a pending payment request for this stage']);
        exit;
    }
    
    // Get stage information for validation
    $stageCheck = $db->prepare("
        SELECT typical_percentage, description 
        FROM construction_stage_payments 
        WHERE stage_name = :stage_name
    ");
    $stageCheck->bindValue(':stage_name', $stage_name, PDO::PARAM_STR);
    $stageCheck->execute();
    $stageInfo = $stageCheck->fetch(PDO::FETCH_ASSOC);
    
    // Validate requested percentage against typical percentage (allow some flexibility)
    if ($stageInfo && $percentage_of_total > ($stageInfo['typical_percentage'] * 1.5)) {
        echo json_encode([
            'success' => false, 
            'message' => "Requested amount seems high for {$stage_name} stage. Typical: {$stageInfo['typical_percentage']}%, Requested: " . number_format($percentage_of_total, 2) . "%"
        ]);
        exit;
    }
    
    // Insert payment request
    $stmt = $db->prepare("
        INSERT INTO project_stage_payment_requests (
            project_id, contractor_id, homeowner_id, stage_name, 
            requested_amount, percentage_of_total, work_description, 
            completion_percentage, contractor_notes, progress_update_id
        ) VALUES (
            :project_id, :contractor_id, :homeowner_id, :stage_name,
            :requested_amount, :percentage_of_total, :work_description,
            :completion_percentage, :contractor_notes, :progress_update_id
        )
    ");
    
    $stmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $stmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $stmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $stmt->bindValue(':stage_name', $stage_name, PDO::PARAM_STR);
    $stmt->bindValue(':requested_amount', $requested_amount, PDO::PARAM_STR);
    $stmt->bindValue(':percentage_of_total', $percentage_of_total, PDO::PARAM_STR);
    $stmt->bindValue(':work_description', $work_description, PDO::PARAM_STR);
    $stmt->bindValue(':completion_percentage', $completion_percentage, PDO::PARAM_STR);
    $stmt->bindValue(':contractor_notes', $contractor_notes, PDO::PARAM_STR);
    $stmt->bindValue(':progress_update_id', $progress_update_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $payment_request_id = $db->lastInsertId();
        
        // Create notification for homeowner
        $notification_title = "Payment Request: {$stage_name} Stage";
        $notification_message = "Contractor has requested ₹" . number_format($requested_amount, 2) . 
                               " for {$stage_name} stage completion ({$completion_percentage}% complete). " .
                               "Work description: " . substr($work_description, 0, 100) . 
                               (strlen($work_description) > 100 ? '...' : '');
        
        $notificationStmt = $db->prepare("
            INSERT INTO payment_notifications (
                payment_request_id, recipient_id, recipient_type, notification_type, title, message
            ) VALUES (
                :payment_request_id, :recipient_id, 'homeowner', 'request_submitted', :title, :message
            )
        ");
        
        $notificationStmt->bindValue(':payment_request_id', $payment_request_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':recipient_id', $homeowner_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':title', $notification_title, PDO::PARAM_STR);
        $notificationStmt->bindValue(':message', $notification_message, PDO::PARAM_STR);
        $notificationStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment request submitted successfully',
            'data' => [
                'payment_request_id' => $payment_request_id,
                'requested_amount' => $requested_amount,
                'percentage_of_total' => round($percentage_of_total, 2),
                'stage_name' => $stage_name,
                'homeowner_name' => $project['homeowner_first_name'] . ' ' . $project['homeowner_last_name']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit payment request']);
    }
    
} catch (Exception $e) {
    error_log("Stage payment request error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>