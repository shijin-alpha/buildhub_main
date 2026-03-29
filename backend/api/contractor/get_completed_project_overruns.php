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
    
    // Get project overrun data
    $query = "
        SELECT 
            cp.id,
            cp.project_name,
            cp.estimated_cost as original_estimate,
            cp.planned_start_date,
            cp.planned_end_date,
            cp.actual_start_date,
            cp.actual_end_date,
            cp.actual_time_overrun_percentage,
            cp.status,
            cp.completion_percentage,
            DATEDIFF(cp.planned_end_date, cp.planned_start_date) as planned_duration_days,
            DATEDIFF(cp.actual_end_date, cp.actual_start_date) as actual_duration_days,
            DATEDIFF(cp.actual_end_date, cp.planned_end_date) as delay_days,
            
            -- Calculate cost overrun
            (
                SELECT COALESCE(SUM(requested_amount), 0)
                FROM stage_payment_requests
                WHERE project_id = cp.id AND status IN ('paid', 'approved', 'pending')
            ) as total_stage_payments,
            
            (
                SELECT COALESCE(SUM(requested_amount), 0)
                FROM custom_payment_requests
                WHERE project_id = cp.id AND status IN ('paid', 'approved', 'pending')
            ) as total_custom_payments
            
        FROM construction_projects cp
        WHERE cp.id = :project_id 
        AND cp.contractor_id = :contractor_id
        AND cp.status = 'completed'
        AND cp.completion_percentage >= 100
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode([
            'success' => false,
            'message' => 'Completed project not found or access denied'
        ]);
        exit;
    }
    
    // Calculate cost overrun
    $originalEstimate = (float)$project['original_estimate'];
    $totalStagePayments = (float)$project['total_stage_payments'];
    $totalCustomPayments = (float)$project['total_custom_payments'];
    $totalProjectCost = $totalStagePayments + $totalCustomPayments;
    $costDifference = $totalProjectCost - $originalEstimate;
    $costOverrunPercentage = $originalEstimate > 0 ? ($costDifference / $originalEstimate) * 100 : 0;
    
    // Time overrun data
    $timeOverrunPercentage = (float)$project['actual_time_overrun_percentage'];
    $plannedDuration = (int)$project['planned_duration_days'];
    $actualDuration = (int)$project['actual_duration_days'];
    $delayDays = (int)$project['delay_days'];
    
    // Determine status indicators
    $hasCostOverrun = $costDifference > 0;
    $hasTimeOverrun = $timeOverrunPercentage > 0;
    $hasCostUnderrun = $costDifference < 0;
    $hasTimeEarly = $timeOverrunPercentage < 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'project_info' => [
                'project_id' => $project['id'],
                'project_name' => $project['project_name'],
                'status' => $project['status'],
                'completion_percentage' => $project['completion_percentage']
            ],
            'cost_overrun' => [
                'original_estimate' => $originalEstimate,
                'total_stage_payments' => $totalStagePayments,
                'total_custom_payments' => $totalCustomPayments,
                'total_project_cost' => $totalProjectCost,
                'cost_difference' => $costDifference,
                'cost_overrun_percentage' => round($costOverrunPercentage, 2),
                'has_overrun' => $hasCostOverrun,
                'has_underrun' => $hasCostUnderrun,
                'status_indicator' => $hasCostOverrun ? 'overrun' : ($hasCostUnderrun ? 'underrun' : 'on_budget')
            ],
            'time_overrun' => [
                'planned_start_date' => $project['planned_start_date'],
                'planned_end_date' => $project['planned_end_date'],
                'actual_start_date' => $project['actual_start_date'],
                'actual_end_date' => $project['actual_end_date'],
                'planned_duration_days' => $plannedDuration,
                'actual_duration_days' => $actualDuration,
                'delay_days' => $delayDays,
                'time_overrun_percentage' => round($timeOverrunPercentage, 2),
                'has_overrun' => $hasTimeOverrun,
                'has_early_completion' => $hasTimeEarly,
                'status_indicator' => $hasTimeOverrun ? 'delayed' : ($hasTimeEarly ? 'early' : 'on_time')
            ],
            'overall_performance' => [
                'both_on_target' => !$hasCostOverrun && !$hasTimeOverrun,
                'cost_only_overrun' => $hasCostOverrun && !$hasTimeOverrun,
                'time_only_overrun' => !$hasCostOverrun && $hasTimeOverrun,
                'both_overrun' => $hasCostOverrun && $hasTimeOverrun,
                'performance_rating' => calculatePerformanceRating($costOverrunPercentage, $timeOverrunPercentage)
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get completed project overruns error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving project overrun data: ' . $e->getMessage()
    ]);
}

function calculatePerformanceRating($costOverrun, $timeOverrun) {
    // Excellent: Both under 5%
    if (abs($costOverrun) <= 5 && abs($timeOverrun) <= 5) {
        return 'excellent';
    }
    // Good: Both under 10%
    if (abs($costOverrun) <= 10 && abs($timeOverrun) <= 10) {
        return 'good';
    }
    // Fair: Both under 20%
    if (abs($costOverrun) <= 20 && abs($timeOverrun) <= 20) {
        return 'fair';
    }
    // Poor: Either exceeds 20%
    return 'poor';
}
?>
