<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    $project_id = $input['project_id'] ?? null;
    $contractor_id = $input['contractor_id'] ?? null;
    
    if (!$project_id || !$contractor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Project ID and Contractor ID are required'
        ]);
        exit;
    }
    
    // Check project access using the same logic as submit_payment_request.php
    $projectCheckQuery = "
        SELECT 
            'construction_project' as source, cp.homeowner_id, cp.total_cost, cp.project_name, cp.status
        FROM construction_projects cp
        WHERE cp.id = :project_id 
        AND cp.contractor_id = :contractor_id 
        AND cp.status IN ('created', 'in_progress')
        
        UNION ALL
        
        SELECT 
            'contractor_estimate' as source, ce.homeowner_id, ce.total_cost, ce.project_name, ce.status
        FROM contractor_estimates ce
        WHERE ce.id = :project_id 
        AND ce.contractor_id = :contractor_id 
        AND ce.status = 'accepted'
        
        UNION ALL
        
        SELECT 
            'contractor_send_estimate' as source, cls.homeowner_id, cse.total_cost, 
            CONCAT('Project for ', COALESCE(u.first_name, 'Homeowner')) as project_name, cse.status
        FROM contractor_send_estimates cse
        INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        LEFT JOIN users u ON u.id = cls.homeowner_id
        WHERE cse.id = :project_id 
        AND cse.contractor_id = :contractor_id 
        AND cse.status IN ('accepted', 'project_created')
    ";
    
    $projectCheckStmt = $db->prepare($projectCheckQuery);
    $projectCheckStmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $projectResults = $projectCheckStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Also check what projects exist for this contractor
    $allProjectsQuery = "
        SELECT 'construction_project' as source, id, project_name, status, contractor_id, homeowner_id
        FROM construction_projects 
        WHERE contractor_id = :contractor_id
        
        UNION ALL
        
        SELECT 'contractor_estimate' as source, id, project_name, status, contractor_id, homeowner_id
        FROM contractor_estimates 
        WHERE contractor_id = :contractor_id
        
        UNION ALL
        
        SELECT 'contractor_send_estimate' as source, cse.id, 
               CONCAT('Project for ', COALESCE(u.first_name, 'Homeowner')) as project_name, 
               cse.status, cse.contractor_id, cls.homeowner_id
        FROM contractor_send_estimates cse
        INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        LEFT JOIN users u ON u.id = cls.homeowner_id
        WHERE cse.contractor_id = :contractor_id
    ";
    
    $allProjectsStmt = $db->prepare($allProjectsQuery);
    $allProjectsStmt->execute([':contractor_id' => $contractor_id]);
    $allProjects = $allProjectsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check existing payment requests for this project
    $existingRequestsQuery = "
        SELECT 'stage_payment_requests' as source, stage_name, status, requested_amount, created_at
        FROM stage_payment_requests 
        WHERE project_id = :project_id 
        AND contractor_id = :contractor_id
        
        UNION ALL
        
        SELECT 'enhanced_stage_payment_requests' as source, stage_name, status, requested_amount, created_at
        FROM enhanced_stage_payment_requests 
        WHERE project_id = :project_id 
        AND contractor_id = :contractor_id
        
        UNION ALL
        
        SELECT 'project_stage_payment_requests' as source, stage_name, status, requested_amount, created_at
        FROM project_stage_payment_requests 
        WHERE project_id = :project_id 
        AND contractor_id = :contractor_id
    ";
    
    $existingRequestsStmt = $db->prepare($existingRequestsQuery);
    $existingRequestsStmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    $existingRequests = $existingRequestsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'project_access_check' => [
                'project_id' => $project_id,
                'contractor_id' => $contractor_id,
                'matching_projects' => $projectResults,
                'has_access' => !empty($projectResults),
                'access_source' => !empty($projectResults) ? $projectResults[0]['source'] : null
            ],
            'all_contractor_projects' => $allProjects,
            'existing_payment_requests' => $existingRequests,
            'session_info' => [
                'session_user_id' => $_SESSION['user_id'] ?? null,
                'session_user_type' => $_SESSION['user_type'] ?? null
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Debug project access error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error checking project access: ' . $e->getMessage()
    ]);
}
?>