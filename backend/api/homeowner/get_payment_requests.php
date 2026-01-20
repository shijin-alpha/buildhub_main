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
    $whereClause = "WHERE homeowner_id = :homeowner_id";
    $params = [':homeowner_id' => $homeowner_id];
    
    if ($project_id) {
        $whereClause .= " AND project_id = :project_id";
        $params[':project_id'] = $project_id;
    }
    
    if ($status !== 'all') {
        $whereClause .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    // Get payment requests with project and contractor details
    $query = "
        SELECT 
            spr.*,
            spr.total_project_cost as project_total_cost,
            
            -- Contractor details
            u_contractor.first_name as contractor_first_name,
            u_contractor.last_name as contractor_last_name,
            u_contractor.email as contractor_email,
            u_contractor.phone as contractor_phone,
            
            -- Homeowner details
            u_homeowner.first_name as homeowner_first_name,
            u_homeowner.last_name as homeowner_last_name,
            u_homeowner.email as homeowner_email,
            u_homeowner.phone as homeowner_phone,
            
            -- Stage details
            csp.typical_percentage,
            csp.stage_order,
            csp.description as stage_description,
            
            -- Time calculations
            DATEDIFF(NOW(), spr.request_date) as days_since_request,
            
            CASE 
                WHEN spr.status = 'pending' AND DATEDIFF(NOW(), spr.request_date) > 7 THEN TRUE
                ELSE FALSE
            END as is_overdue
            
        FROM stage_payment_requests spr
        LEFT JOIN users u_contractor ON spr.contractor_id = u_contractor.id
        LEFT JOIN users u_homeowner ON spr.homeowner_id = u_homeowner.id
        LEFT JOIN construction_stage_payments csp ON spr.stage_name COLLATE utf8mb4_unicode_ci = csp.stage_name
        $whereClause
        ORDER BY spr.request_date DESC
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
        FROM stage_payment_requests 
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
                spr.project_id,
                u_contractor.first_name as contractor_first_name,
                u_contractor.last_name as contractor_last_name,
                u_homeowner.first_name as homeowner_first_name,
                u_homeowner.last_name as homeowner_last_name,
                MAX(spr.total_project_cost) as total_cost,
                COUNT(spr.id) as total_requests,
                COUNT(CASE WHEN spr.status = 'pending' THEN 1 END) as pending_requests,
                COALESCE(SUM(CASE WHEN spr.status = 'paid' THEN spr.requested_amount END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN spr.status = 'pending' THEN spr.requested_amount END), 0) as total_pending
            FROM stage_payment_requests spr
            LEFT JOIN users u_contractor ON spr.contractor_id = u_contractor.id
            LEFT JOIN users u_homeowner ON spr.homeowner_id = u_homeowner.id
            WHERE spr.homeowner_id = :homeowner_id
            GROUP BY spr.project_id, spr.contractor_id, spr.homeowner_id
            ORDER BY spr.project_id DESC
        ";
        
        $projectStmt = $db->prepare($projectQuery);
        $projectStmt->execute([':homeowner_id' => $homeowner_id]);
        $projectSummary = $projectStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Format the response data
    foreach ($requests as &$request) {
        $request['requested_amount'] = (float)$request['requested_amount'];
        $request['completion_percentage'] = (float)$request['completion_percentage'];
        $request['total_project_cost'] = (float)$request['total_project_cost'];
        $request['typical_percentage'] = (float)$request['typical_percentage'];
        $request['is_overdue'] = (bool)$request['is_overdue'];
        
        // Add percentage_of_total calculation
        if ($request['total_project_cost'] > 0) {
            $request['percentage_of_total'] = ($request['requested_amount'] / $request['total_project_cost']) * 100;
        } else {
            $request['percentage_of_total'] = 0;
        }
        
        // Format dates
        $request['request_date_formatted'] = date('M j, Y g:i A', strtotime($request['request_date']));
        if ($request['response_date']) {
            $request['homeowner_response_date_formatted'] = date('M j, Y g:i A', strtotime($request['response_date']));
        }
        
        // Format receipt information
        if ($request['receipt_file_path']) {
            $request['receipt_files'] = json_decode($request['receipt_file_path'], true);
        } else {
            $request['receipt_files'] = null;
        }
        
        // Format payment date
        if ($request['payment_date']) {
            $request['payment_date_formatted'] = date('M j, Y', strtotime($request['payment_date']));
        }
        
        // Format verification timestamp
        if ($request['verified_at']) {
            $request['verified_at_formatted'] = date('M j, Y g:i A', strtotime($request['verified_at']));
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