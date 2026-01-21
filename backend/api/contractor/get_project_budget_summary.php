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
    
    if (!$contractor_id || !$project_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Contractor ID and Project ID are required'
        ]);
        exit;
    }
    
    // Get original estimate amount
    $estimateQuery = "
        SELECT 
            cse.total_cost as original_estimate,
            cp.project_name,
            u.first_name as homeowner_first_name,
            u.last_name as homeowner_last_name
        FROM contractor_send_estimates cse
        LEFT JOIN construction_projects cp ON cse.id = cp.estimate_id
        LEFT JOIN users u ON cp.homeowner_id = u.id
        WHERE cse.id = :project_id 
        AND cse.contractor_id = :contractor_id 
        AND cse.status IN ('accepted', 'project_created')
    ";
    
    $estimateStmt = $db->prepare($estimateQuery);
    $estimateStmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $project = $estimateStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode([
            'success' => false,
            'message' => 'Project not found or access denied'
        ]);
        exit;
    }
    
    // Get stage payment requests total
    $stagePaymentsQuery = "
        SELECT 
            COUNT(*) as total_stage_requests,
            COALESCE(SUM(requested_amount), 0) as total_stage_amount,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN requested_amount END), 0) as paid_stage_amount,
            COALESCE(SUM(CASE WHEN status IN ('pending', 'approved') THEN requested_amount END), 0) as pending_stage_amount
        FROM stage_payment_requests 
        WHERE project_id = :project_id AND contractor_id = :contractor_id
    ";
    
    $stageStmt = $db->prepare($stagePaymentsQuery);
    $stageStmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $stagePayments = $stageStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get custom payment requests total
    $customPaymentsQuery = "
        SELECT 
            COUNT(*) as total_custom_requests,
            COALESCE(SUM(requested_amount), 0) as total_custom_amount,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN requested_amount END), 0) as paid_custom_amount,
            COALESCE(SUM(CASE WHEN status IN ('pending', 'approved') THEN requested_amount END), 0) as pending_custom_amount
        FROM custom_payment_requests 
        WHERE project_id = :project_id AND contractor_id = :contractor_id
    ";
    
    $customStmt = $db->prepare($customPaymentsQuery);
    $customStmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $customPayments = $customStmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $originalEstimate = (float)$project['original_estimate'];
    $totalStageAmount = (float)$stagePayments['total_stage_amount'];
    $totalCustomAmount = (float)$customPayments['total_custom_amount'];
    $totalProjectCost = $totalStageAmount + $totalCustomAmount;
    $budgetDifference = $totalProjectCost - $originalEstimate;
    $budgetOverrun = $budgetDifference > 0 ? $budgetDifference : 0;
    $budgetUnderrun = $budgetDifference < 0 ? abs($budgetDifference) : 0;
    
    $paidStageAmount = (float)$stagePayments['paid_stage_amount'];
    $paidCustomAmount = (float)$customPayments['paid_custom_amount'];
    $totalPaidAmount = $paidStageAmount + $paidCustomAmount;
    
    $pendingStageAmount = (float)$stagePayments['pending_stage_amount'];
    $pendingCustomAmount = (float)$customPayments['pending_custom_amount'];
    $totalPendingAmount = $pendingStageAmount + $pendingCustomAmount;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'project_info' => [
                'project_id' => $project_id,
                'project_name' => $project['project_name'],
                'homeowner_name' => $project['homeowner_first_name'] . ' ' . $project['homeowner_last_name']
            ],
            'budget_summary' => [
                'original_estimate' => $originalEstimate,
                'total_project_cost' => $totalProjectCost,
                'budget_difference' => $budgetDifference,
                'budget_overrun' => $budgetOverrun,
                'budget_underrun' => $budgetUnderrun,
                'overrun_percentage' => $originalEstimate > 0 ? ($budgetOverrun / $originalEstimate) * 100 : 0
            ],
            'payment_breakdown' => [
                'stage_payments' => [
                    'total_requests' => (int)$stagePayments['total_stage_requests'],
                    'total_amount' => $totalStageAmount,
                    'paid_amount' => $paidStageAmount,
                    'pending_amount' => $pendingStageAmount
                ],
                'custom_payments' => [
                    'total_requests' => (int)$customPayments['total_custom_requests'],
                    'total_amount' => $totalCustomAmount,
                    'paid_amount' => $paidCustomAmount,
                    'pending_amount' => $pendingCustomAmount
                ],
                'totals' => [
                    'total_paid' => $totalPaidAmount,
                    'total_pending' => $totalPendingAmount,
                    'remaining_budget' => max(0, $originalEstimate - $totalPaidAmount)
                ]
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get project budget summary error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving project budget summary: ' . $e->getMessage()
    ]);
}
?>