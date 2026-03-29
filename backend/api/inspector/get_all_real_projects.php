<?php
/**
 * Get All Real Construction Projects for Site Inspector
 * Shows all actual projects from database like contractor dropdown
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all real construction projects with their details
    $query = "
        SELECT 
            cp.id,
            cp.project_name,
            cp.project_description,
            cp.status,
            cp.current_stage,
            cp.completion_percentage as stored_completion_percentage,
            cp.project_location,
            cp.homeowner_name,
            cp.homeowner_email,
            cp.start_date,
            cp.expected_completion_date,
            cp.total_cost,
            cp.timeline,
            cp.created_at,
            cp.contractor_id,
            CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) as contractor_name,
            u_contractor.email as contractor_email,
            u_contractor.phone as contractor_phone,
            -- Calculate real progress from daily progress updates (preferred) or stage payments (fallback)
            COALESCE((
                SELECT dpu.cumulative_completion_percentage
                FROM daily_progress_updates dpu 
                WHERE dpu.project_id = cp.id 
                ORDER BY dpu.update_date DESC, dpu.created_at DESC
                LIMIT 1
            ), (
                SELECT SUM(spr.completion_percentage) 
                FROM stage_payment_requests spr 
                WHERE spr.project_id = cp.id 
                AND spr.status IN ('paid', 'approved')
            ), 0) as real_completion_percentage,
            -- Get current stage from latest daily progress or latest completed payment
            COALESCE((
                SELECT dpu.construction_stage
                FROM daily_progress_updates dpu 
                WHERE dpu.project_id = cp.id 
                ORDER BY dpu.update_date DESC, dpu.created_at DESC
                LIMIT 1
            ), (
                SELECT spr.stage_name
                FROM stage_payment_requests spr 
                WHERE spr.project_id = cp.id 
                AND spr.status IN ('paid', 'approved')
                ORDER BY spr.request_date DESC
                LIMIT 1
            ), cp.current_stage) as actual_current_stage,
            -- Count of completed stages
            (SELECT COUNT(*) FROM stage_payment_requests spr 
             WHERE spr.project_id = cp.id AND spr.status IN ('paid', 'approved')) as completed_stages,
            -- Count of total stages
            (SELECT COUNT(*) FROM stage_payment_requests spr 
             WHERE spr.project_id = cp.id) as total_stages,
            -- Latest stage payment info
            (SELECT JSON_OBJECT(
                'stage_name', spr.stage_name,
                'amount', spr.requested_amount,
                'status', spr.status,
                'request_date', spr.request_date,
                'payment_date', spr.payment_date,
                'completion_percentage', spr.completion_percentage,
                'work_description', LEFT(spr.work_description, 200)
            ) FROM stage_payment_requests spr 
             WHERE spr.project_id = cp.id 
             ORDER BY spr.request_date DESC LIMIT 1) as latest_stage_payment,
            -- Latest daily progress info
            (SELECT JSON_OBJECT(
                'update_date', dpu.update_date,
                'construction_stage', dpu.construction_stage,
                'work_done_today', LEFT(dpu.work_done_today, 200),
                'incremental_completion_percentage', dpu.incremental_completion_percentage,
                'cumulative_completion_percentage', dpu.cumulative_completion_percentage,
                'working_hours', dpu.working_hours,
                'weather_condition', dpu.weather_condition,
                'created_at', dpu.created_at
            ) FROM daily_progress_updates dpu 
             WHERE dpu.project_id = cp.id 
             ORDER BY dpu.update_date DESC, dpu.created_at DESC LIMIT 1) as latest_daily_progress,
            -- Check if inspector is assigned
            (SELECT COUNT(*) FROM inspector_project_assignments ipa 
             WHERE ipa.project_id = cp.id AND ipa.status = 'active') as is_assigned_to_inspector,
            -- Get inspector assignment details if exists
            (SELECT JSON_OBJECT(
                'inspector_id', ipa.inspector_id,
                'assigned_at', ipa.assigned_at,
                'notes', ipa.notes
            ) FROM inspector_project_assignments ipa 
             WHERE ipa.project_id = cp.id AND ipa.status = 'active' LIMIT 1) as inspector_assignment
        FROM construction_projects cp
        LEFT JOIN users u_contractor ON cp.contractor_id = u_contractor.id
        ORDER BY cp.created_at DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the response data
    $formattedProjects = [];
    foreach ($projects as $project) {
        $realProgress = (float)$project['real_completion_percentage'];
        $realProgress = min($realProgress, 100); // Cap at 100%
        
        // Determine actual project status based on real progress
        $actualStatus = $project['status'];
        if ($realProgress > 0 && $actualStatus == 'created') {
            $actualStatus = 'in_progress';
        }
        if ($realProgress >= 100) {
            $actualStatus = 'completed';
        }
        
        $formattedProjects[] = [
            'id' => (int)$project['id'],
            'project_name' => $project['project_name'],
            'project_description' => $project['project_description'],
            'status' => $actualStatus,
            'stored_stage' => $project['current_stage'],
            'actual_current_stage' => $project['actual_current_stage'],
            'stored_completion_percentage' => (float)$project['stored_completion_percentage'],
            'real_completion_percentage' => $realProgress,
            'project_location' => $project['project_location'],
            'homeowner' => [
                'name' => $project['homeowner_name'],
                'email' => $project['homeowner_email']
            ],
            'contractor' => [
                'id' => $project['contractor_id'],
                'name' => $project['contractor_name'],
                'email' => $project['contractor_email'],
                'phone' => $project['contractor_phone']
            ],
            'dates' => [
                'start_date' => $project['start_date'],
                'expected_completion' => $project['expected_completion_date'],
                'created_at' => $project['created_at']
            ],
            'financial' => [
                'total_cost' => $project['total_cost'] ? (float)$project['total_cost'] : null,
                'timeline' => $project['timeline']
            ],
            'statistics' => [
                'completed_stages' => (int)$project['completed_stages'],
                'total_stages' => (int)$project['total_stages']
            ],
            'latest_stage_payment' => $project['latest_stage_payment'] ? json_decode($project['latest_stage_payment'], true) : null,
            'latest_daily_progress' => $project['latest_daily_progress'] ? json_decode($project['latest_daily_progress'], true) : null,
            'inspector_assignment' => [
                'is_assigned' => (int)$project['is_assigned_to_inspector'] > 0,
                'details' => $project['inspector_assignment'] ? json_decode($project['inspector_assignment'], true) : null
            ],
            'progress_calculation' => [
                'method' => 'daily_progress_based',
                'completed_stages' => (int)$project['completed_stages'],
                'total_stages' => (int)$project['total_stages'],
                'stage_completion_sum' => $realProgress,
                'progress_difference' => $realProgress - (float)$project['stored_completion_percentage'],
                'data_source' => $project['latest_daily_progress'] ? 'daily_progress_updates' : 'stage_payment_requests'
            ]
        ];
    }
    
    // Get all stage payments for detailed view
    $paymentsQuery = "
        SELECT 
            spr.project_id,
            spr.stage_name,
            spr.completion_percentage,
            spr.requested_amount,
            spr.status,
            spr.request_date,
            spr.payment_date,
            spr.work_description,
            cp.project_name
        FROM stage_payment_requests spr
        JOIN construction_projects cp ON spr.project_id = cp.id
        ORDER BY spr.project_id, spr.request_date
    ";
    
    $paymentsStmt = $db->prepare($paymentsQuery);
    $paymentsStmt->execute();
    $allPayments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group payments by project
    $paymentsByProject = [];
    foreach ($allPayments as $payment) {
        $paymentsByProject[$payment['project_id']][] = $payment;
    }
    
    // Get summary statistics
    $totalProjects = count($projects);
    $activeProjects = count(array_filter($projects, function($p) { return $p['status'] === 'in_progress'; }));
    $completedProjects = count(array_filter($projects, function($p) { return (float)$p['real_completion_percentage'] >= 100; }));
    $assignedProjects = count(array_filter($projects, function($p) { return (int)$p['is_assigned_to_inspector'] > 0; }));
    
    $avgRealProgress = 0;
    if ($totalProjects > 0) {
        $totalProgress = array_sum(array_map(function($p) { return (float)$p['real_completion_percentage']; }, $projects));
        $avgRealProgress = round($totalProgress / $totalProjects, 2);
    }
    
    echo json_encode([
        'success' => true,
        'projects' => $formattedProjects,
        'stage_payments_by_project' => $paymentsByProject,
        'statistics' => [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'assigned_projects' => $assignedProjects,
            'avg_real_completion' => $avgRealProgress
        ],
        'project_info' => [
            'data_source' => 'Real construction projects from database',
            'calculation_method' => 'Real progress calculated from daily progress updates (preferred) or paid/approved stage payment requests (fallback)',
            'progress_priority' => 'Daily progress updates > Stage payments > Stored values',
            'stage_percentages' => [
                'Site Preparation' => 5,
                'Foundation' => 20,
                'Structure' => 25,
                'Brickwork' => 15,
                'Roofing' => 10,
                'Electrical' => 8,
                'Plumbing' => 7,
                'Finishing' => 8,
                'Final Inspection' => 2
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching real projects: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch real projects',
        'error' => $e->getMessage()
    ]);
}
?>