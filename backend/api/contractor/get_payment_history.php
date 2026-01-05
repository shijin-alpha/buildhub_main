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
    
    // Get payment requests for this project
    $payment_query = "
        SELECT 
            spr.*,
            cs.stage_name,
            cs.typical_percentage,
            lr.homeowner_name,
            lr.total_cost as project_budget,
            CASE 
                WHEN spr.response_date IS NOT NULL THEN spr.status
                ELSE 'pending'
            END as current_status
        FROM stage_payment_requests spr
        JOIN construction_stages cs ON spr.stage_id = cs.id
        JOIN layout_requests lr ON spr.project_id = lr.id
        WHERE spr.project_id = :project_id 
        AND spr.contractor_id = :contractor_id
        ORDER BY spr.request_date DESC
    ";
    
    $payment_stmt = $db->prepare($payment_query);
    $payment_stmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $payment_requests = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the payment requests
    $formatted_requests = [];
    foreach ($payment_requests as $request) {
        $formatted_requests[] = [
            'id' => $request['id'],
            'stage_name' => $request['stage_name'],
            'requested_amount' => (float)$request['requested_amount'],
            'approved_amount' => $request['approved_amount'] ? (float)$request['approved_amount'] : null,
            'completion_percentage' => (float)$request['completion_percentage'],
            'work_description' => $request['work_description'],
            'contractor_notes' => $request['contractor_notes'],
            'homeowner_notes' => $request['homeowner_notes'],
            'status' => $request['current_status'],
            'request_date' => $request['request_date'],
            'response_date' => $request['response_date'],
            'typical_percentage' => (float)$request['typical_percentage'],
            'project_budget' => (float)$request['project_budget'],
            'homeowner_name' => $request['homeowner_name']
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
    error_log("Get payment history error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving payment history: ' . $e->getMessage()
    ]);
}
?>