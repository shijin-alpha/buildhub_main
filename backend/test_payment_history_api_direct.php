<?php
// Direct test of the payment history API fix
session_start();
$_SESSION['user_id'] = 29; // Set contractor ID

// Set the project ID
$_GET['project_id'] = '1';

// Capture the API output
ob_start();

// Include the API file directly
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
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
    
    // First, check if stage_payment_requests table exists
    $table_check = $db->query("SHOW TABLES LIKE 'stage_payment_requests'");
    
    if ($table_check->rowCount() == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Payment requests table does not exist'
        ]);
        exit;
    }
    
    // Check if project exists in construction_projects table first, then layout_requests
    $project_exists_query = "
        SELECT 
            CASE 
                WHEN cp.id IS NOT NULL THEN 'construction_projects'
                WHEN lr.id IS NOT NULL THEN 'layout_requests'
                ELSE NULL
            END as project_source,
            COALESCE(cp.id, lr.id) as project_id,
            COALESCE(cp.homeowner_id, lr.user_id) as homeowner_id
        FROM (SELECT :project_id as search_id) s
        LEFT JOIN construction_projects cp ON cp.id = s.search_id
        LEFT JOIN layout_requests lr ON lr.id = s.search_id
        WHERE cp.id IS NOT NULL OR lr.id IS NOT NULL
    ";
    
    $project_stmt = $db->prepare($project_exists_query);
    $project_stmt->execute([':project_id' => $project_id]);
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
            'materials_used' => $request['materials_used'],
            'labor_count' => (int)$request['labor_count'],
            'work_start_date' => $request['work_start_date'],
            'work_end_date' => $request['work_end_date'],
            'contractor_notes' => $request['contractor_notes'],
            'homeowner_notes' => $request['homeowner_notes'],
            'quality_check' => (bool)$request['quality_check'],
            'safety_compliance' => (bool)$request['safety_compliance'],
            'status' => $request['status'],
            'request_date' => $request['request_date'],
            'response_date' => $request['response_date'],
            'project_budget' => null,
            'homeowner_name' => $homeowner_name,
            // Receipt information
            'transaction_reference' => $request['transaction_reference'] ?? null,
            'payment_date' => $request['payment_date'] ?? null,
            'payment_method' => $request['payment_method'] ?? null,
            'receipt_file_path' => $request['receipt_file_path'] ? json_decode($request['receipt_file_path'], true) : null,
            'verification_status' => $request['verification_status'] ?? null,
            'verified_by' => $request['verified_by'] ?? null,
            'verified_at' => $request['verified_at'] ?? null,
            'verification_notes' => $request['verification_notes'] ?? null
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
            ],
            'project_info' => [
                'project_source' => $project_result['project_source'],
                'project_id' => $project_result['project_id'],
                'homeowner_id' => $project_result['homeowner_id']
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving payment history: ' . $e->getMessage()
    ]);
}

$output = ob_get_clean();

echo "=== PAYMENT HISTORY API TEST RESULTS ===\n";
echo $output . "\n";
echo "=== END TEST RESULTS ===\n";
?>