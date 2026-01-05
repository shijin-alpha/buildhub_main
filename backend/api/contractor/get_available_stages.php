<?php
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
    
    $project_id = $_GET['project_id'] ?? '';
    
    if (empty($project_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Project ID is required'
        ]);
        exit;
    }
    
    // Verify contractor has access to this project
    $access_query = "
        SELECT COUNT(*) as has_access 
        FROM layout_requests lr 
        WHERE lr.id = :project_id 
        AND lr.contractor_id = :contractor_id
    ";
    
    $access_stmt = $db->prepare($access_query);
    $access_stmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $access_result = $access_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$access_result || $access_result['has_access'] == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Access denied to this project'
        ]);
        exit;
    }
    
    // Get all available construction stages
    $stages_query = "
        SELECT 
            id,
            stage_name,
            typical_percentage,
            description,
            stage_order
        FROM construction_stages 
        ORDER BY stage_order ASC
    ";
    
    $stages_stmt = $db->prepare($stages_query);
    $stages_stmt->execute();
    $stages = $stages_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get existing payment requests for this project to show status
    $existing_query = "
        SELECT 
            cs.stage_name,
            spr.status,
            spr.requested_amount,
            spr.approved_amount,
            spr.request_date
        FROM stage_payment_requests spr
        JOIN construction_stages cs ON spr.stage_id = cs.id
        WHERE spr.project_id = :project_id 
        AND spr.contractor_id = :contractor_id
    ";
    
    $existing_stmt = $db->prepare($existing_query);
    $existing_stmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $existing_requests = $existing_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create a map of existing requests by stage name
    $existing_map = [];
    foreach ($existing_requests as $request) {
        $existing_map[$request['stage_name']] = $request;
    }
    
    // Format stages with existing request status
    $formatted_stages = [];
    foreach ($stages as $stage) {
        $existing_request = $existing_map[$stage['stage_name']] ?? null;
        
        $formatted_stages[] = [
            'id' => $stage['id'],
            'stage_name' => $stage['stage_name'],
            'typical_percentage' => (float)$stage['typical_percentage'],
            'description' => $stage['description'],
            'stage_order' => (int)$stage['stage_order'],
            'has_existing_request' => $existing_request !== null,
            'existing_status' => $existing_request ? $existing_request['status'] : null,
            'existing_amount' => $existing_request ? (float)$existing_request['requested_amount'] : null,
            'can_request' => $existing_request === null || $existing_request['status'] === 'rejected'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'stages' => $formatted_stages,
            'total_stages' => count($formatted_stages),
            'existing_requests_count' => count($existing_requests)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get available stages error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving available stages: ' . $e->getMessage()
    ]);
}
?>