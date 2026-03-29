<?php
/**
 * Get Inspector Assigned Projects with Real Progress
 * Returns projects with actual progress calculated from stage payment requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthorizationMiddleware.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new AuthorizationMiddleware($db);
    
    // Require inspector authentication
    $auth->requireAuth();
    $auth->requireCapability('view_assigned_projects');
    
    // Get filter parameters
    $status = $_GET['status'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $sortBy = $_GET['sortBy'] ?? 'assigned_at';
    $sortOrder = $_GET['sortOrder'] ?? 'desc';
    
    // Validate sort parameters
    $allowedSortFields = ['assigned_at', 'project_name', 'status', 'real_completion_percentage', 'expected_completion_date'];
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'assigned_at';
    }
    
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
    
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
            -- Get next expected stage
            CASE 
                WHEN (SELECT COUNT(*) FROM stage_payment_requests spr WHERE spr.project_id = cp.id AND spr.status IN ('paid', 'approved')) = 0 THEN 'Site Preparation'
                WHEN (SELECT spr.stage_name FROM stage_payment_requests spr WHERE spr.project_id = cp.id AND spr.status IN ('paid', 'approved') ORDER BY spr.request_date DESC LIMIT 1) = 'Site Preparation' THEN 'Foundation'
                WHEN (SELECT spr.stage_name FROM stage_payment_requests spr WHERE spr.project_id = cp.id AND spr.status IN ('paid', 'approved') ORDER BY spr.request_date DESC LIMIT 1) = 'Foundation' THEN 'Structure'
                WHEN (SELECT spr.stage_name FROM stage_payment_requests spr WHERE spr.project_id = cp.id AND spr.status IN ('paid', 'approved') ORDER BY spr.request_date DESC LIMIT 1) = 'Structure' THEN 'Brickwork'
                WHEN (SELECT spr.stage_name FROM stage_payment_requests spr WHERE spr.project_id = cp.id AND spr.status IN ('paid', 'approved') ORDER BY spr.request_date DESC LIMIT 1) = 'Brickwork' THEN 'Roofing'
                WHEN (SELECT spr.stage_name FROM stage_payment_requests spr WHERE spr.project_id = cp.id AND spr.status IN ('paid', 'approved') ORDER BY spr.request_date DESC LIMIT 1) = 'Roofing' THEN 'Electrical'
                WHEN (SELECT spr.stage_name FROM stage_payment_requests spr WHERE spr.project_id = cp.id AND spr.status IN ('paid', 'approved') ORDER BY spr.request_date DESC LIMIT 1) = 'Electrical' THEN 'Plumbing'
                WHEN (SELECT spr.stage_name FROM stage_payment_requests spr WHERE spr.project_id = cp.id AND spr.status IN ('paid', 'approved') ORDER BY spr.request_date DESC LIMIT 1) = 'Plumbing' THEN 'Finishing'
                WHEN (SELECT spr.stage_name FROM stage_payment_requests spr WHERE spr.project_id = cp.id AND spr.status IN ('paid', 'approved') ORDER BY spr.request_date DESC LIMIT 1) = 'Finishing' THEN 'Final Inspection'
                ELSE 'Completed'
            END as next_expected_stage,
            -- Count of inspection reports
            (SELECT COUNT(*) FROM site_inspection_reports sir 
             WHERE sir.project_id = cp.id AND sir.inspector_id = :inspector_id) as report_count,
            -- Count of site notes
            (SELECT COUNT(*) FROM site_notes sn 
             WHERE sn.project_id = cp.id AND sn.inspector_id = :inspector_id) as note_count,
            -- Latest inspection date
            (SELECT MAX(inspection_date) FROM site_inspection_reports sir 
             WHERE sir.project_id = cp.id AND sir.inspector_id = :inspector_id) as last_inspection_date,
            -- Count of unresolved issues
            (SELECT COUNT(*) FROM site_notes sn 
             WHERE sn.project_id = cp.id AND sn.inspector_id = :inspector_id 
             AND sn.note_type = 'issue' AND sn.is_resolved = 0) as unresolved_issues,
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
    ";
    
    $params = [':inspector_id' => $auth->getCurrentUser()['id']];
    
    // Add status filter
    if ($status !== 'all') {
        $query .= " AND cp.status = :status";
        $params[':status'] = $status;
    }
    
    // Add search filter
    if (!empty($search)) {
        $query .= " AND (
            cp.project_name LIKE :search1 OR 
            cp.project_location LIKE :search2 OR 
            cp.homeowner_name LIKE :search3 OR
            cp.current_stage LIKE :search4
        )";
        $searchTerm = '%' . $search . '%';
        $params[':search1'] = $searchTerm;
        $params[':search2'] = $searchTerm;
        $params[':search3'] = $searchTerm;
        $params[':search4'] = $searchTerm;
    }
    
    // Add sorting (handle real_completion_percentage specially)
    if ($sortBy === 'real_completion_percentage') {
        $query .= " ORDER BY real_completion_percentage " . $sortOrder;
    } else {
        $query .= " ORDER BY " . $sortBy . " " . $sortOrder;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
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
            'next_expected_stage' => $project['next_expected_stage'],
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
                'assigned_at' => $project['assigned_at'],
                'last_inspection' => $project['last_inspection_date']
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
                'report_count' => (int)$project['report_count'],
                'note_count' => (int)$project['note_count'],
                'unresolved_issues' => (int)$project['unresolved_issues'],
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
            ) >= 100 THEN 1 ELSE 0 END) as completed_projects,
            COUNT(DISTINCT sir.id) as total_reports,
            COUNT(DISTINCT sn.id) as total_notes
        FROM inspector_project_assignments ipa
        JOIN construction_projects cp ON ipa.project_id = cp.id
        LEFT JOIN site_inspection_reports sir ON sir.project_id = cp.id AND sir.inspector_id = ipa.inspector_id
        LEFT JOIN site_notes sn ON sn.project_id = cp.id AND sn.inspector_id = ipa.inspector_id
        WHERE ipa.inspector_id = :inspector_id
        AND ipa.status = 'active'
    ";
    
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute([':inspector_id' => $auth->getCurrentUser()['id']]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Log the action
    $auth->logAction('view_assigned_projects_real_progress', null, 'project_list', null, [
        'filter_status' => $status,
        'search_term' => $search,
        'project_count' => count($formattedProjects),
        'calculation_method' => 'stage_payment_based'
    ]);
    
    echo json_encode([
        'success' => true,
        'projects' => $formattedProjects,
        'statistics' => [
            'total_projects' => (int)$stats['total_projects'],
            'active_projects' => (int)$stats['active_projects'],
            'completed_projects' => (int)$stats['completed_projects'],
            'avg_real_completion' => round((float)$stats['avg_real_completion'], 2),
            'total_reports' => (int)$stats['total_reports'],
            'total_notes' => (int)$stats['total_notes']
        ],
        'filters' => [
            'status' => $status,
            'search' => $search,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder
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
        'message' => 'Failed to fetch projects with real progress'
    ]);
}
?>