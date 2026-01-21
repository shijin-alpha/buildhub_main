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
    $contractor_id = $_SESSION['user_id'] ?? $_GET['contractor_id'] ?? null;
    $project_id = $_GET['project_id'] ?? null;
    
    if (!$contractor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Contractor not authenticated'
        ]);
        exit;
    }
    
    if (!$project_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Project ID is required'
        ]);
        exit;
    }
    
    // Get payment requests for the specific project and contractor
    $query = "
        SELECT 
            spr.*,
            u_homeowner.first_name as homeowner_first_name,
            u_homeowner.last_name as homeowner_last_name,
            u_homeowner.email as homeowner_email,
            
            -- Time calculations
            DATEDIFF(NOW(), spr.request_date) as days_since_request,
            
            CASE 
                WHEN spr.status = 'pending' AND DATEDIFF(NOW(), spr.request_date) > 7 THEN TRUE
                ELSE FALSE
            END as is_overdue
            
        FROM stage_payment_requests spr
        LEFT JOIN users u_homeowner ON spr.homeowner_id = u_homeowner.id
        WHERE spr.contractor_id = :contractor_id 
        AND spr.project_id = :project_id
        ORDER BY spr.request_date DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':contractor_id' => $contractor_id,
        ':project_id' => $project_id
    ]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get paid stages for filtering
    $paidStages = [];
    foreach ($requests as $request) {
        if ($request['status'] === 'paid') {
            $paidStages[] = $request['stage_name'];
        }
    }
    
    // Format the response data
    foreach ($requests as &$request) {
        $request['requested_amount'] = (float)$request['requested_amount'];
        $request['completion_percentage'] = (float)$request['completion_percentage'];
        $request['total_project_cost'] = (float)$request['total_project_cost'];
        $request['is_overdue'] = (bool)$request['is_overdue'];
        
        // Format dates
        $request['request_date_formatted'] = date('M j, Y g:i A', strtotime($request['request_date']));
        if ($request['response_date']) {
            $request['response_date_formatted'] = date('M j, Y g:i A', strtotime($request['response_date']));
        }
        
        // Format payment date
        if ($request['payment_date']) {
            $request['payment_date_formatted'] = date('M j, Y', strtotime($request['payment_date']));
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'requests' => $requests,
            'paid_stages' => $paidStages,
            'project_id' => $project_id,
            'contractor_id' => $contractor_id
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get project payment requests error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving payment requests: ' . $e->getMessage()
    ]);
}
?>