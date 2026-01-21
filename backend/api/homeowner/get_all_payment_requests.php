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
    $homeowner_id = $_SESSION['user_id'] ?? $_GET['homeowner_id'] ?? null;
    
    // For testing purposes, default to homeowner ID 28 if no authentication
    if (!$homeowner_id) {
        $homeowner_id = 28; // Default test homeowner
        error_log("Using default homeowner ID for testing: $homeowner_id");
    }
    
    // Simple unified query without complex filtering for now
    $query = "
        (SELECT 
            spr.id,
            spr.project_id,
            spr.contractor_id,
            spr.homeowner_id,
            spr.stage_name as request_title,
            spr.requested_amount,
            spr.completion_percentage,
            spr.work_description as request_description,
            spr.materials_used,
            spr.labor_count,
            spr.work_start_date,
            spr.work_end_date,
            spr.contractor_notes,
            spr.quality_check,
            spr.safety_compliance,
            spr.total_project_cost,
            spr.status,
            spr.request_date,
            spr.response_date,
            spr.homeowner_notes,
            spr.approved_amount,
            spr.rejection_reason,
            spr.created_at,
            spr.updated_at,
            spr.transaction_reference,
            spr.payment_date,
            spr.receipt_file_path,
            spr.payment_method,
            spr.verification_status,
            spr.verified_by,
            spr.verified_at,
            spr.verification_notes,
            'stage' as request_type,
            NULL as urgency_level,
            NULL as category,
            
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
            
            -- Time calculations
            DATEDIFF(NOW(), spr.request_date) as days_since_request,
            
            CASE 
                WHEN spr.status = 'pending' AND DATEDIFF(NOW(), spr.request_date) > 7 THEN TRUE
                ELSE FALSE
            END as is_overdue
            
        FROM stage_payment_requests spr
        LEFT JOIN users u_contractor ON spr.contractor_id = u_contractor.id
        LEFT JOIN users u_homeowner ON spr.homeowner_id = u_homeowner.id
        WHERE spr.homeowner_id = ?)
        
        UNION ALL
        
        (SELECT 
            cpr.id,
            cpr.project_id,
            cpr.contractor_id,
            cpr.homeowner_id,
            cpr.request_title,
            cpr.requested_amount,
            100 as completion_percentage,
            cpr.request_reason as request_description,
            NULL as materials_used,
            NULL as labor_count,
            NULL as work_start_date,
            NULL as work_end_date,
            cpr.contractor_notes,
            1 as quality_check,
            1 as safety_compliance,
            NULL as total_project_cost,
            cpr.status,
            cpr.request_date,
            cpr.response_date,
            cpr.homeowner_notes,
            cpr.approved_amount,
            cpr.rejection_reason,
            cpr.created_at,
            cpr.updated_at,
            cpr.transaction_reference,
            cpr.payment_date,
            cpr.receipt_file_path,
            cpr.payment_method,
            cpr.verification_status,
            cpr.verified_by,
            cpr.verified_at,
            cpr.verification_notes,
            'custom' as request_type,
            cpr.urgency_level,
            cpr.category,
            
            -- Contractor details
            u_contractor2.first_name as contractor_first_name,
            u_contractor2.last_name as contractor_last_name,
            u_contractor2.email as contractor_email,
            u_contractor2.phone as contractor_phone,
            
            -- Homeowner details
            u_homeowner2.first_name as homeowner_first_name,
            u_homeowner2.last_name as homeowner_last_name,
            u_homeowner2.email as homeowner_email,
            u_homeowner2.phone as homeowner_phone,
            
            -- Time calculations
            DATEDIFF(NOW(), cpr.request_date) as days_since_request,
            
            CASE 
                WHEN cpr.status = 'pending' AND DATEDIFF(NOW(), cpr.request_date) > 7 THEN TRUE
                ELSE FALSE
            END as is_overdue
            
        FROM custom_payment_requests cpr
        LEFT JOIN users u_contractor2 ON cpr.contractor_id = u_contractor2.id
        LEFT JOIN users u_homeowner2 ON cpr.homeowner_id = u_homeowner2.id
        WHERE cpr.homeowner_id = ?)
        
        ORDER BY request_date DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$homeowner_id, $homeowner_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Simple summary
    $summary = [
        'total_requests' => count($requests),
        'pending_requests' => count(array_filter($requests, fn($r) => $r['status'] === 'pending')),
        'approved_requests' => count(array_filter($requests, fn($r) => $r['status'] === 'approved')),
        'paid_requests' => count(array_filter($requests, fn($r) => $r['status'] === 'paid')),
        'rejected_requests' => count(array_filter($requests, fn($r) => $r['status'] === 'rejected')),
        'pending_amount' => array_sum(array_map(fn($r) => $r['status'] === 'pending' ? $r['requested_amount'] : 0, $requests)),
        'approved_amount' => array_sum(array_map(fn($r) => $r['status'] === 'approved' ? $r['requested_amount'] : 0, $requests)),
        'paid_amount' => array_sum(array_map(fn($r) => $r['status'] === 'paid' ? $r['requested_amount'] : 0, $requests))
    ];
    
    // Format the response data
    foreach ($requests as &$request) {
        $request['requested_amount'] = (float)$request['requested_amount'];
        $request['completion_percentage'] = (float)$request['completion_percentage'];
        $request['total_project_cost'] = (float)($request['total_project_cost'] ?? 0);
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
        
        // Add request type specific formatting
        if ($request['request_type'] === 'custom') {
            $request['urgency_badge'] = [
                'low' => ['color' => '#28a745', 'icon' => '🟢', 'label' => 'Low Priority'],
                'medium' => ['color' => '#ffc107', 'icon' => '🟡', 'label' => 'Medium Priority'],
                'high' => ['color' => '#fd7e14', 'icon' => '🟠', 'label' => 'High Priority'],
                'urgent' => ['color' => '#dc3545', 'icon' => '🔴', 'label' => 'Urgent']
            ][$request['urgency_level']] ?? ['color' => '#6c757d', 'icon' => '⚪', 'label' => 'Unknown'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'requests' => $requests,
            'summary' => $summary,
            'filters' => [
                'project_id' => null,
                'status' => 'all'
            ],
            'request_types' => [
                'stage' => array_filter($requests, fn($r) => $r['request_type'] === 'stage'),
                'custom' => array_filter($requests, fn($r) => $r['request_type'] === 'custom')
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get all payment requests error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving payment requests: ' . $e->getMessage()
    ]);
}
?>