<?php
// Direct test of payment history without session dependency
header('Content-Type: application/json');

try {
    require_once 'backend/config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $contractor_id = 29; // Direct assignment instead of session
    $project_id = 37;
    
    // Test the project existence query
    $project_exists_query = "
        SELECT 
            CASE 
                WHEN cp.id IS NOT NULL THEN 'construction_projects'
                WHEN lr.id IS NOT NULL THEN 'layout_requests'
                ELSE NULL
            END as project_source,
            COALESCE(cp.id, lr.id) as project_id,
            COALESCE(cp.homeowner_id, lr.user_id) as homeowner_id
        FROM (SELECT ? as search_id) s
        LEFT JOIN construction_projects cp ON (cp.id = s.search_id OR cp.estimate_id = s.search_id)
        LEFT JOIN layout_requests lr ON lr.id = s.search_id
        WHERE cp.id IS NOT NULL OR lr.id IS NOT NULL
    ";
    
    $project_stmt = $db->prepare($project_exists_query);
    $project_stmt->execute([$project_id]);
    $project_result = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project_result) {
        echo json_encode([
            'success' => false,
            'message' => 'Project not found'
        ]);
        exit;
    }
    
    // Get payment requests for this project
    $payment_query = "
        SELECT 
            spr.*,
            u.first_name, u.last_name
        FROM stage_payment_requests spr
        LEFT JOIN users u ON spr.homeowner_id = u.id
        WHERE spr.project_id = ? 
        AND spr.contractor_id = ?
        ORDER BY spr.request_date DESC
    ";
    
    $payment_stmt = $db->prepare($payment_query);
    $payment_stmt->execute([$project_id, $contractor_id]);
    $payment_requests = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the payment requests
    $formatted_requests = [];
    foreach ($payment_requests as $request) {
        $homeowner_name = '';
        if ($request['first_name'] && $request['last_name']) {
            $homeowner_name = $request['first_name'] . ' ' . $request['last_name'];
        }
        
        $formatted_requests[] = [
            'id' => $request['id'],
            'stage_name' => $request['stage_name'],
            'requested_amount' => (float)$request['requested_amount'],
            'approved_amount' => $request['approved_amount'] ? (float)$request['approved_amount'] : null,
            'completion_percentage' => (float)$request['completion_percentage'],
            'work_description' => $request['work_description'],
            'status' => $request['status'],
            'request_date' => $request['request_date'],
            'response_date' => $request['response_date'],
            'homeowner_name' => $homeowner_name
        ];
    }
    
    // Calculate payment summary
    $total_requested = array_sum(array_column($formatted_requests, 'requested_amount'));
    $total_approved = 0;
    $total_paid = 0;
    $pending_count = 0;
    $approved_count = 0;
    $rejected_count = 0;
    
    foreach ($formatted_requests as $request) {
        if ($request['approved_amount']) {
            $total_approved += $request['approved_amount'];
        }
        
        switch ($request['status']) {
            case 'pending':
                $pending_count++;
                break;
            case 'approved':
                $approved_count++;
                break;
            case 'rejected':
                $rejected_count++;
                break;
            case 'paid':
                $total_paid += $request['approved_amount'] ?: $request['requested_amount'];
                break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'payment_requests' => $formatted_requests,
            'summary' => [
                'total_requests' => count($formatted_requests),
                'total_requested' => $total_requested,
                'total_approved' => $total_approved,
                'total_paid' => $total_paid,
                'pending_count' => $pending_count,
                'approved_count' => $approved_count,
                'rejected_count' => $rejected_count
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving payment history: ' . $e->getMessage()
    ]);
}
?>