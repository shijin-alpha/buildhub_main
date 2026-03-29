<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { 
    header('Access-Control-Allow-Origin: ' . $origin); 
    header('Vary: Origin'); 
} else { 
    header('Access-Control-Allow-Origin: http://localhost:3000'); 
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    header('Access-Control-Max-Age: 86400'); 
    exit; 
}

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

    // Get active construction projects with proper progress calculation
    $projectsQuery = "
        SELECT 
            cp.id,
            cp.project_name,
            cp.total_cost,
            cp.status,
            cp.current_stage,
            cp.completion_percentage,
            cp.start_date,
            cp.expected_completion_date,
            cp.project_location,
            cp.timeline,
            CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) as contractor_name,
            u_contractor.email as contractor_email,
            u_contractor.phone as contractor_phone,
            
            -- Calculate days remaining
            CASE 
                WHEN cp.expected_completion_date IS NOT NULL 
                THEN DATEDIFF(cp.expected_completion_date, CURDATE())
                ELSE NULL
            END as days_remaining,
            
            -- Calculate project duration
            CASE 
                WHEN cp.start_date IS NOT NULL AND cp.expected_completion_date IS NOT NULL
                THEN DATEDIFF(cp.expected_completion_date, cp.start_date)
                ELSE NULL
            END as total_duration_days,
            
            -- Get latest progress from daily_progress_updates
            COALESCE(
                (SELECT MAX(dpu.cumulative_completion_percentage) 
                 FROM daily_progress_updates dpu 
                 WHERE dpu.project_id = cp.id), 
                cp.completion_percentage, 0
            ) as actual_completion_percentage,
            
            -- Get current stage from latest daily update
            COALESCE(
                (SELECT dpu.construction_stage 
                 FROM daily_progress_updates dpu 
                 WHERE dpu.project_id = cp.id 
                 ORDER BY dpu.update_date DESC, dpu.created_at DESC 
                 LIMIT 1), 
                cp.current_stage
            ) as current_construction_stage,
            
            -- Count daily updates
            (SELECT COUNT(*) 
             FROM daily_progress_updates dpu 
             WHERE dpu.project_id = cp.id) as daily_updates_count,
            
            -- Count weekly summaries (assuming weekly reports are in a separate table or marked differently)
            0 as weekly_summaries_count,
            
            -- Count monthly reports
            0 as monthly_reports_count,
            
            -- Get last update date
            (SELECT MAX(dpu.update_date) 
             FROM daily_progress_updates dpu 
             WHERE dpu.project_id = cp.id) as last_update_date
            
        FROM construction_projects cp
        LEFT JOIN users u_contractor ON cp.contractor_id = u_contractor.id
        WHERE cp.homeowner_id = :homeowner_id 
        AND cp.status IN ('created', 'in_progress', 'completed')
        ORDER BY 
            CASE 
                WHEN cp.status = 'in_progress' THEN 1
                WHEN cp.status = 'created' THEN 2
                WHEN cp.status = 'completed' THEN 3
            END,
            cp.created_at DESC
    ";
    
    $stmt = $db->prepare($projectsQuery);
    $stmt->bindParam(':homeowner_id', $homeowner_id);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get payment summary for budget tracking using the correct project mapping
    $paymentSummaryQuery = "
        SELECT 
            spr.project_id,
            COUNT(spr.id) as total_requests,
            COUNT(CASE WHEN spr.status = 'pending' THEN 1 END) as pending_requests,
            COUNT(CASE WHEN spr.status = 'approved' THEN 1 END) as approved_requests,
            COUNT(CASE WHEN spr.status = 'paid' THEN 1 END) as paid_requests,
            COUNT(CASE WHEN spr.status = 'rejected' THEN 1 END) as rejected_requests,
            COALESCE(SUM(CASE WHEN spr.status = 'paid' THEN spr.requested_amount END), 0) as total_paid,
            COALESCE(SUM(CASE WHEN spr.status = 'pending' THEN spr.requested_amount END), 0) as total_pending,
            COALESCE(SUM(CASE WHEN spr.status = 'approved' THEN spr.requested_amount END), 0) as total_approved,
            MAX(spr.total_project_cost) as project_budget,
            
            -- Recent payment activity
            MAX(CASE WHEN spr.status = 'paid' THEN spr.payment_date END) as last_payment_date,
            MAX(CASE WHEN spr.status = 'pending' THEN spr.request_date END) as latest_request_date
            
        FROM stage_payment_requests spr
        WHERE spr.homeowner_id = :homeowner_id
        GROUP BY spr.project_id
    ";
    
    $paymentStmt = $db->prepare($paymentSummaryQuery);
    $paymentStmt->bindParam(':homeowner_id', $homeowner_id);
    $paymentStmt->execute();
    $paymentSummaries = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create a map for easy lookup
    $paymentMap = [];
    foreach ($paymentSummaries as $payment) {
        $paymentMap[$payment['project_id']] = $payment;
    }

    // Get recent payment requests across all projects
    $recentPaymentsQuery = "
        SELECT 
            spr.id,
            spr.project_id,
            spr.stage_name,
            spr.requested_amount,
            spr.status,
            spr.request_date,
            spr.payment_date,
            cp.project_name,
            CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) as contractor_name
        FROM stage_payment_requests spr
        LEFT JOIN construction_projects cp ON spr.project_id = cp.id
        LEFT JOIN users u_contractor ON spr.contractor_id = u_contractor.id
        WHERE spr.homeowner_id = :homeowner_id
        ORDER BY spr.request_date DESC
        LIMIT 5
    ";
    
    $recentStmt = $db->prepare($recentPaymentsQuery);
    $recentStmt->bindParam(':homeowner_id', $homeowner_id);
    $recentStmt->execute();
    $recentPayments = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get overall statistics
    $overallStatsQuery = "
        SELECT 
            COUNT(DISTINCT cp.id) as total_active_projects,
            COALESCE(SUM(cp.total_cost), 0) as total_project_value,
            COALESCE(AVG(
                COALESCE(
                    (SELECT MAX(dpu.cumulative_completion_percentage) 
                     FROM daily_progress_updates dpu 
                     WHERE dpu.project_id = cp.id), 
                    cp.completion_percentage, 0
                )
            ), 0) as average_completion,
            COUNT(CASE WHEN cp.status = 'in_progress' THEN 1 END) as projects_in_progress,
            COUNT(CASE WHEN cp.status = 'created' THEN 1 END) as projects_created,
            COUNT(CASE WHEN cp.status = 'completed' THEN 1 END) as projects_completed
        FROM construction_projects cp
        WHERE cp.homeowner_id = :homeowner_id 
        AND cp.status IN ('created', 'in_progress', 'completed')
    ";
    
    $statsStmt = $db->prepare($overallStatsQuery);
    $statsStmt->bindParam(':homeowner_id', $homeowner_id);
    $statsStmt->execute();
    $overallStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Process projects data and merge with payment information
    $processedProjects = [];
    foreach ($projects as $project) {
        $projectId = $project['id'];
        $paymentData = $paymentMap[$projectId] ?? null;
        
        // Use actual completion percentage from daily updates
        $actualProgress = (float)$project['actual_completion_percentage'];
        
        // Calculate budget utilization
        $budgetUtilized = 0;
        $budgetPending = 0;
        $budgetRemaining = 0;
        
        if ($paymentData && $project['total_cost'] > 0) {
            $budgetUtilized = ($paymentData['total_paid'] / $project['total_cost']) * 100;
            $budgetPending = ($paymentData['total_pending'] / $project['total_cost']) * 100;
            $budgetRemaining = 100 - $budgetUtilized - $budgetPending;
        }
        
        // Determine project health status
        $healthStatus = 'good';
        if ($actualProgress < 10 && $project['days_remaining'] < 30) {
            $healthStatus = 'at_risk';
        } elseif ($paymentData && $paymentData['pending_requests'] > 3) {
            $healthStatus = 'attention_needed';
        } elseif ($project['days_remaining'] < 0) {
            $healthStatus = 'overdue';
        }
        
        $processedProjects[] = [
            'id' => $project['id'],
            'name' => $project['project_name'],
            'location' => $project['project_location'],
            'contractor' => [
                'name' => $project['contractor_name'],
                'email' => $project['contractor_email'],
                'phone' => $project['contractor_phone']
            ],
            'timeline' => [
                'start_date' => $project['start_date'],
                'expected_completion' => $project['expected_completion_date'],
                'days_remaining' => $project['days_remaining'],
                'total_duration' => $project['total_duration_days'],
                'timeline_text' => $project['timeline']
            ],
            'progress' => [
                'current_stage' => $project['current_construction_stage'],
                'completion_percentage' => $actualProgress,
                'status' => $project['status']
            ],
            'budget' => [
                'total_cost' => (float)$project['total_cost'],
                'paid_amount' => $paymentData ? (float)$paymentData['total_paid'] : 0,
                'pending_amount' => $paymentData ? (float)$paymentData['total_pending'] : 0,
                'approved_amount' => $paymentData ? (float)$paymentData['total_approved'] : 0,
                'budget_utilized_percentage' => round($budgetUtilized, 1),
                'budget_pending_percentage' => round($budgetPending, 1),
                'budget_remaining_percentage' => round($budgetRemaining, 1)
            ],
            'payments' => [
                'total_requests' => $paymentData ? (int)$paymentData['total_requests'] : 0,
                'pending_requests' => $paymentData ? (int)$paymentData['pending_requests'] : 0,
                'paid_requests' => $paymentData ? (int)$paymentData['paid_requests'] : 0,
                'last_payment_date' => $paymentData ? $paymentData['last_payment_date'] : null,
                'latest_request_date' => $paymentData ? $paymentData['latest_request_date'] : null
            ],
            'update_history' => [
                'daily_updates_count' => (int)$project['daily_updates_count'],
                'weekly_summaries_count' => (int)$project['weekly_summaries_count'],
                'monthly_reports_count' => (int)$project['monthly_reports_count'],
                'last_update_date' => $project['last_update_date']
            ],
            'health_status' => $healthStatus
        ];
    }

    // Format recent payments
    $formattedRecentPayments = [];
    foreach ($recentPayments as $payment) {
        $formattedRecentPayments[] = [
            'id' => $payment['id'],
            'project_id' => $payment['project_id'],
            'project_name' => $payment['project_name'],
            'stage_name' => $payment['stage_name'],
            'amount' => (float)$payment['requested_amount'],
            'status' => $payment['status'],
            'request_date' => $payment['request_date'],
            'payment_date' => $payment['payment_date'],
            'contractor_name' => $payment['contractor_name'],
            'days_ago' => floor((time() - strtotime($payment['request_date'])) / (60 * 60 * 24))
        ];
    }

    // Calculate overall budget summary
    $totalBudget = 0;
    $totalPaid = 0;
    $totalPending = 0;
    
    foreach ($processedProjects as $project) {
        $totalBudget += $project['budget']['total_cost'];
        $totalPaid += $project['budget']['paid_amount'];
        $totalPending += $project['budget']['pending_amount'];
    }

    $response = [
        'success' => true,
        'data' => [
            'overview' => [
                'total_projects' => (int)$overallStats['total_active_projects'],
                'projects_in_progress' => (int)$overallStats['projects_in_progress'],
                'projects_created' => (int)$overallStats['projects_created'],
                'projects_completed' => (int)$overallStats['projects_completed'],
                'total_project_value' => (float)$overallStats['total_project_value'],
                'average_completion' => round((float)$overallStats['average_completion'], 1)
            ],
            'projects' => $processedProjects,
            'budget_summary' => [
                'total_budget' => $totalBudget,
                'total_paid' => $totalPaid,
                'total_pending' => $totalPending,
                'total_remaining' => $totalBudget - $totalPaid - $totalPending,
                'utilization_percentage' => $totalBudget > 0 ? round(($totalPaid / $totalBudget) * 100, 1) : 0,
                'pending_percentage' => $totalBudget > 0 ? round(($totalPending / $totalBudget) * 100, 1) : 0
            ],
            'recent_activity' => [
                'payments' => $formattedRecentPayments
            ],
            'alerts' => [
                'overdue_projects' => array_filter($processedProjects, function($p) { 
                    return $p['health_status'] === 'overdue'; 
                }),
                'pending_payments' => array_sum(array_column($paymentSummaries, 'pending_requests')),
                'at_risk_projects' => array_filter($processedProjects, function($p) { 
                    return $p['health_status'] === 'at_risk'; 
                })
            ]
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving dashboard data: ' . $e->getMessage()
    ]);
}
?>