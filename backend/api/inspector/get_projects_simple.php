<?php
/**
 * Get Inspector Projects with Real Progress (Simplified)
 * Returns projects with actual progress calculated from stage payment requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // For testing, use inspector ID 1001
    $inspectorId = 1001;
    
    // Build query for assigned projects with real progress calculation
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
            ipa.assigned_at,
            ipa.notes as assignment_notes,
            ipa.status as assignment_status,
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name,
            c.email as contractor_email,
            c.phone as contractor_phone,
            -- Calculate real progress from stage payments
            COALESCE((
                SELECT SUM(spr.completion_percentage) 
                FROM stage_payment_requests spr 
                WHERE spr.project_id = cp.id 
                AND spr.status IN ('paid', 'approved')
            ), 0) as real_completion_percentage,
            -- Get current stage from latest completed payment
            COALESCE((
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
                'completion_percentage', spr.completion_percentage
            ) FROM stage_payment_requests spr 
             WHERE spr.project_id = cp.id 
             ORDER BY spr.request_date DESC LIMIT 1) as latest_stage_payment
        FROM inspector_project_assignments ipa
        JOIN construction_projects cp ON ipa.project_id = cp.id
        LEFT JOIN users c ON cp.contractor_id = c.id
        WHERE ipa.inspector_id = :inspector_id
        AND ipa.status = 'active'
        ORDER BY ipa.assigned_at DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':inspector_id' => $inspectorId]);
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
                'name' => $project['contractor_name'],
                'email' => $project['contractor_email'],
                'phone' => $project['contractor_phone']
            ],
            'dates' => [
                'start_date' => $project['start_date'],
                'expected_completion' => $project['expected_completion_date'],
                'assigned_at' => $project['assigned_at']
            ],
            'financial' => [
                'total_cost' => $project['total_cost'] ? (float)$project['total_cost'] : null,
                'timeline' => $project['timeline']
            ],
            'assignment' => [
                'notes' => $project['assignment_notes'],
                'status' => $project['assignment_status']
            ],
            'statistics' => [
                'completed_stages' => (int)$project['completed_stages'],
                'total_stages' => (int)$project['total_stages']
            ],
            'latest_stage_payment' => $project['latest_stage_payment'] ? json_decode($project['latest_stage_payment'], true) : null,
            'progress_calculation' => [
                'method' => 'stage_payment_based',
                'completed_stages' => (int)$project['completed_stages'],
                'total_stages' => (int)$project['total_stages'],
                'stage_completion_sum' => $realProgress
            ]
        ];
    }
    
    // Get summary statistics with real progress
    $statsQuery = "
        SELECT 
            COUNT(*) as total_projects,
            AVG(COALESCE((
                SELECT SUM(spr.completion_percentage) 
                FROM stage_payment_requests spr 
                WHERE spr.project_id = cp.id 
                AND spr.status IN ('paid', 'approved')
            ), 0)) as avg_real_completion,
            SUM(CASE WHEN cp.status = 'in_progress' OR (
                SELECT COUNT(*) FROM stage_payment_requests spr 
                WHERE spr.project_id = cp.id AND spr.status IN ('paid', 'approved')
            ) > 0 THEN 1 ELSE 0 END) as active_projects,
            SUM(CASE WHEN (
                SELECT SUM(spr.completion_percentage) 
                FROM stage_payment_requests spr 
                WHERE spr.project_id = cp.id 
                AND spr.status IN ('paid', 'approved')
            ) >= 100 THEN 1 ELSE 0 END) as completed_projects
        FROM inspector_project_assignments ipa
        JOIN construction_projects cp ON ipa.project_id = cp.id
        WHERE ipa.inspector_id = :inspector_id
        AND ipa.status = 'active'
    ";
    
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute([':inspector_id' => $inspectorId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'projects' => $formattedProjects,
        'statistics' => [
            'total_projects' => (int)$stats['total_projects'],
            'active_projects' => (int)$stats['active_projects'],
            'completed_projects' => (int)$stats['completed_projects'],
            'avg_real_completion' => round((float)$stats['avg_real_completion'], 2)
        ],
        'progress_info' => [
            'calculation_method' => 'Real progress calculated from paid/approved stage payment requests',
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
    error_log("Error fetching projects with real progress: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch projects with real progress',
        'error' => $e->getMessage()
    ]);
}
?>