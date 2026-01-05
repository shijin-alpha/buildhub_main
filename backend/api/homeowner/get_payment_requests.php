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
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Homeowner not authenticated'
        ]);
        exit;
    }
    
    $project_id = $_GET['project_id'] ?? null;
    $status = $_GET['status'] ?? 'all';
    
    // Build query based on filters
    $whereClause = "WHERE ppr.homeowner_id = :homeowner_id";
    $params = [':homeowner_id' => $homeowner_id];
    
    if ($project_id) {
        $whereClause .= " AND ppr.project_id = :project_id";
        $params[':project_id'] = $project_id;
    }
    
    if ($status !== 'all') {
        $whereClause .= " AND ppr.status = :status";
        $params[':status'] = $status;
    }
    
    // Get payment requests with project and contractor details
    $query = "
        SELECT 
            ppr.*,
            cse.total_cost as project_total_cost,
            cse.homeowner_first_name,
            cse.homeowner_last_name,
            cse.contractor_first_name,
            cse.contractor_last_name,
            cse.plot_size,
            cse.budget_range,
            
            -- Contractor details
            u.email as contractor_email,
            u.phone as contractor_phone,
            
            -- Stage details
            csp.typical_percentage,
            csp.stage_order,
            csp.description as stage_description,
            
            -- Time calculations
            DATEDIFF(NOW(), ppr.request_date) as days_since_request,
            
            CASE 
                WHEN ppr.status = 'pending' AND DATEDIFF(NOW(), ppr.request_date) > 7 THEN TRUE
                ELSE FALSE
            END as is_overdue
            
        FROM project_stage_payment_requests ppr
        LEFT JOIN contractor_send_estimates cse ON ppr.project_id = cse.id
        LEFT JOIN users u ON ppr.contractor_id = u.id
        LEFT JOIN construction_stage_payments csp ON ppr.stage_name = csp.stage_name
        $whereClause
        ORDER BY ppr.request_date DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics
    $summaryQuery = "
        SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_requests,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_requests,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_requests,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN requested_amount END), 0) as pending_amount,
            COALESCE(SUM(CASE WHEN status = 'approved' THEN requested_amount END), 0) as approved_amount,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN requested_amount END), 0) as paid_amount,
            COUNT(CASE WHEN status = 'pending' AND DATEDIFF(NOW(), request_date) > 7 THEN 1 END) as overdue_requests
        FROM project_stage_payment_requests 
        $whereClause
    ";
    
    $summaryStmt = $db->prepare($summaryQuery);
    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get project-wise summary if no specific project filter
    $projectSummary = [];
    if (!$project_id) {
        $projectQuery = "
            SELECT 
                cse.id as project_id,
                cse.homeowner_first_name,
                cse.homeowner_last_name,
                cse.contractor_first_name,
                cse.contractor_last_name,
                cse.total_cost,
                cse.plot_size,
                cse.budget_range,
                COUNT(ppr.id) as total_requests,
                COUNT(CASE WHEN ppr.status = 'pending' THEN 1 END) as pending_requests,
                COALESCE(SUM(CASE WHEN ppr.status = 'paid' THEN ppr.requested_amount END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN ppr.status = 'pending' THEN ppr.requested_amount END), 0) as total_pending
            FROM contractor_send_estimates cse
            LEFT JOIN project_stage_payment_requests ppr ON cse.id = ppr.project_id
            WHERE cse.homeowner_id = :homeowner_id
            GROUP BY cse.id
            ORDER BY cse.id DESC
        ";
        
        $projectStmt = $db->prepare($projectQuery);
        $projectStmt->execute([':homeowner_id' => $homeowner_id]);
        $projectSummary = $projectStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Format the response data
    foreach ($requests as &$request) {
        $request['requested_amount'] = (float)$request['requested_amount'];
        $request['percentage_of_total'] = (float)$request['percentage_of_total'];
        $request['completion_percentage'] = (float)$request['completion_percentage'];
        $request['project_total_cost'] = (float)$request['project_total_cost'];
        $request['typical_percentage'] = (float)$request['typical_percentage'];
        $request['is_overdue'] = (bool)$request['is_overdue'];
        
        // Format dates
        $request['request_date_formatted'] = date('M j, Y g:i A', strtotime($request['request_date']));
        if ($request['homeowner_response_date']) {
            $request['homeowner_response_date_formatted'] = date('M j, Y g:i A', strtotime($request['homeowner_response_date']));
        }
        if ($request['payment_date']) {
            $request['payment_date_formatted'] = date('M j, Y g:i A', strtotime($request['payment_date']));
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'requests' => $requests,
            'summary' => $summary,
            'project_summary' => $projectSummary,
            'filters' => [
                'project_id' => $project_id,
                'status' => $status
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get payment requests error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving payment requests: ' . $e->getMessage()
    ]);
}
?>