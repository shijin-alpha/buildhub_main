<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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
    
    $project_id = $_GET['project_id'] ?? 0;
    $stage_name = $_GET['stage_name'] ?? '';
    
    if (!$project_id || !$stage_name) {
        echo json_encode([
            'success' => false,
            'message' => 'Project ID and stage name are required'
        ]);
        exit;
    }
    
    // Verify contractor is assigned to this project
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
    
    // Get stage payment information
    $stageInfo = $db->prepare("
        SELECT * FROM construction_stage_payments 
        WHERE stage_name = :stage_name
    ");
    $stageInfo->bindValue(':stage_name', $stage_name, PDO::PARAM_STR);
    $stageInfo->execute();
    $stage = $stageInfo->fetch(PDO::FETCH_ASSOC);
    
    // Get existing payment requests for this stage
    $existingRequests = $db->prepare("
        SELECT * FROM project_stage_payment_requests 
        WHERE project_id = :project_id AND stage_name = :stage_name
        ORDER BY request_date DESC
    ");
    $existingRequests->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $existingRequests->bindValue(':stage_name', $stage_name, PDO::PARAM_STR);
    $existingRequests->execute();
    $requests = $existingRequests->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate suggested amount based on stage percentage
    $suggested_amount = 0;
    $suggested_percentage = 0;
    if ($stage) {
        $suggested_percentage = $stage['typical_percentage'];
        $suggested_amount = ($project['total_cost'] * $suggested_percentage) / 100;
    }
    
    // Get total paid amount for this project
    $paidAmount = $db->prepare("
        SELECT COALESCE(SUM(requested_amount), 0) as total_paid
        FROM project_stage_payment_requests 
        WHERE project_id = :project_id AND status = 'paid'
    ");
    $paidAmount->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $paidAmount->execute();
    $totalPaid = $paidAmount->fetchColumn();
    
    // Get pending amount for this project
    $pendingAmount = $db->prepare("
        SELECT COALESCE(SUM(requested_amount), 0) as total_pending
        FROM project_stage_payment_requests 
        WHERE project_id = :project_id AND status IN ('pending', 'approved')
    ");
    $pendingAmount->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $pendingAmount->execute();
    $totalPending = $pendingAmount->fetchColumn();
    
    // Calculate remaining amount
    $remaining_amount = $project['total_cost'] - $totalPaid - $totalPending;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'project_info' => [
                'project_id' => $project['id'],
                'total_cost' => $project['total_cost'],
                'homeowner_name' => $project['homeowner_first_name'] . ' ' . $project['homeowner_last_name'],
                'total_paid' => $totalPaid,
                'total_pending' => $totalPending,
                'remaining_amount' => $remaining_amount
            ],
            'stage_info' => $stage,
            'suggested_amount' => $suggested_amount,
            'suggested_percentage' => $suggested_percentage,
            'existing_requests' => $requests,
            'can_request' => !in_array('pending', array_column($requests, 'status')),
            'payment_summary' => [
                'total_project_cost' => $project['total_cost'],
                'total_paid' => $totalPaid,
                'total_pending' => $totalPending,
                'remaining_budget' => $remaining_amount,
                'paid_percentage' => ($totalPaid / $project['total_cost']) * 100,
                'pending_percentage' => ($totalPending / $project['total_cost']) * 100,
                'remaining_percentage' => ($remaining_amount / $project['total_cost']) * 100
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get stage payment info error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving payment information: ' . $e->getMessage()
    ]);
}
?>