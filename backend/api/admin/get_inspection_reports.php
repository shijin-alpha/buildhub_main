<?php
/**
 * Get All Inspection Reports for Admin
 * Returns all inspection reports across all projects with filtering options
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user is logged in as admin
    session_start();
    $isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    
    if (!$isAdmin) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    // Get filter parameters
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $inspector_id = isset($_GET['inspector_id']) ? (int)$_GET['inspector_id'] : null;
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    
    if ($project_id) {
        $where_conditions[] = "ir.project_id = ?";
        $params[] = $project_id;
    }
    
    if ($status) {
        $where_conditions[] = "ir.overall_status = ?";
        $params[] = $status;
    }
    
    if ($inspector_id) {
        $where_conditions[] = "ir.inspector_id = ?";
        $params[] = $inspector_id;
    }
    
    if ($date_from) {
        $where_conditions[] = "ir.inspection_date >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $where_conditions[] = "ir.inspection_date <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get inspection reports with project and inspector details
    $reportsQuery = "
        SELECT 
            ir.id,
            ir.project_id,
            ir.inspector_id,
            ir.inspection_date,
            ir.inspection_stage,
            ir.inspection_type,
            ir.overall_status,
            ir.quality_score,
            ir.safety_compliance,
            ir.notes,
            ir.recommendations,
            ir.issues_identified,
            ir.corrective_actions_required,
            ir.next_inspection_date,
            ir.weather_conditions,
            ir.temperature,
            ir.site_accessibility,
            ir.work_progress_since_last,
            ir.safety_equipment_available,
            ir.safety_violations_found,
            ir.structural_integrity,
            ir.workmanship_quality,
            ir.code_compliance,
            ir.follow_up_required,
            ir.contractor_present,
            ir.contractor_representative,
            ir.homeowner_notified,
            ir.created_at,
            ir.updated_at,
            -- Project details
            cp.project_name,
            cp.project_location,
            cp.status as project_status,
            cp.current_stage as project_current_stage,
            cp.completion_percentage as project_completion,
            -- Inspector details
            CONCAT(inspector.first_name, ' ', inspector.last_name) as inspector_name,
            inspector.email as inspector_email,
            inspector.phone as inspector_phone,
            -- Homeowner details
            CONCAT(homeowner.first_name, ' ', homeowner.last_name) as homeowner_name,
            homeowner.email as homeowner_email,
            -- Contractor details
            CONCAT(contractor.first_name, ' ', contractor.last_name) as contractor_name,
            contractor.email as contractor_email,
            -- Checklist items count
            (SELECT COUNT(*) FROM inspection_checklist_items ici WHERE ici.inspection_report_id = ir.id) as checklist_items_count,
            (SELECT COUNT(*) FROM inspection_checklist_items ici WHERE ici.inspection_report_id = ir.id AND ici.status = 'fail') as failed_items_count,
            -- Photos count
            (SELECT COUNT(*) FROM inspection_photos ip WHERE ip.inspection_report_id = ir.id) as photos_count
        FROM inspection_reports ir
        JOIN construction_projects cp ON ir.project_id = cp.id
        JOIN users inspector ON ir.inspector_id = inspector.id
        LEFT JOIN users homeowner ON cp.homeowner_id = homeowner.id
        LEFT JOIN users contractor ON cp.contractor_id = contractor.id
        $where_clause
        ORDER BY ir.inspection_date DESC, ir.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $reportsStmt = $db->prepare($reportsQuery);
    $reportsStmt->execute($params);
    $reports = $reportsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(*) as total
        FROM inspection_reports ir
        JOIN construction_projects cp ON ir.project_id = cp.id
        $where_clause
    ";
    
    $countParams = array_slice($params, 0, -2); // Remove limit and offset
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get summary statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total_reports,
            COUNT(CASE WHEN ir.overall_status = 'approved' THEN 1 END) as approved_count,
            COUNT(CASE WHEN ir.overall_status = 'rejected' THEN 1 END) as rejected_count,
            COUNT(CASE WHEN ir.overall_status = 'needs_attention' THEN 1 END) as needs_attention_count,
            COUNT(CASE WHEN ir.overall_status = 'pending' THEN 1 END) as pending_count,
            AVG(ir.quality_score) as avg_quality_score,
            COUNT(CASE WHEN ir.safety_violations_found != 'no' THEN 1 END) as safety_violations_count,
            COUNT(CASE WHEN ir.follow_up_required != 'no' THEN 1 END) as follow_up_required_count,
            COUNT(DISTINCT ir.project_id) as projects_with_reports,
            COUNT(DISTINCT ir.inspector_id) as active_inspectors
        FROM inspection_reports ir
        JOIN construction_projects cp ON ir.project_id = cp.id
        $where_clause
    ";
    
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute($countParams);
    $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Format the reports
    $formattedReports = [];
    foreach ($reports as $report) {
        $formattedReports[] = [
            'id' => (int)$report['id'],
            'project' => [
                'id' => (int)$report['project_id'],
                'name' => $report['project_name'],
                'location' => $report['project_location'],
                'status' => $report['project_status'],
                'current_stage' => $report['project_current_stage'],
                'completion_percentage' => (float)$report['project_completion']
            ],
            'inspector' => [
                'id' => (int)$report['inspector_id'],
                'name' => $report['inspector_name'],
                'email' => $report['inspector_email'],
                'phone' => $report['inspector_phone']
            ],
            'homeowner' => [
                'name' => $report['homeowner_name'],
                'email' => $report['homeowner_email']
            ],
            'contractor' => [
                'name' => $report['contractor_name'],
                'email' => $report['contractor_email']
            ],
            'inspection' => [
                'date' => $report['inspection_date'],
                'stage' => $report['inspection_stage'],
                'type' => $report['inspection_type'],
                'status' => $report['overall_status'],
                'quality_score' => $report['quality_score'] ? (float)$report['quality_score'] : null,
                'safety_compliance' => $report['safety_compliance'],
                'notes' => $report['notes'],
                'recommendations' => $report['recommendations'],
                'issues_identified' => $report['issues_identified'],
                'corrective_actions_required' => $report['corrective_actions_required'],
                'next_inspection_date' => $report['next_inspection_date']
            ],
            'site_conditions' => [
                'weather_conditions' => $report['weather_conditions'],
                'temperature' => $report['temperature'] ? (float)$report['temperature'] : null,
                'site_accessibility' => $report['site_accessibility'],
                'work_progress_since_last' => $report['work_progress_since_last']
            ],
            'safety_assessment' => [
                'safety_equipment_available' => $report['safety_equipment_available'],
                'safety_violations_found' => $report['safety_violations_found'],
                'structural_integrity' => $report['structural_integrity'],
                'workmanship_quality' => $report['workmanship_quality'],
                'code_compliance' => $report['code_compliance']
            ],
            'follow_up' => [
                'follow_up_required' => $report['follow_up_required'],
                'contractor_present' => $report['contractor_present'],
                'contractor_representative' => $report['contractor_representative'],
                'homeowner_notified' => $report['homeowner_notified']
            ],
            'counts' => [
                'checklist_items' => (int)$report['checklist_items_count'],
                'failed_items' => (int)$report['failed_items_count'],
                'photos' => (int)$report['photos_count']
            ],
            'timestamps' => [
                'created_at' => $report['created_at'],
                'updated_at' => $report['updated_at']
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'reports' => $formattedReports,
        'pagination' => [
            'total' => (int)$totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalCount
        ],
        'statistics' => [
            'total_reports' => (int)$statistics['total_reports'],
            'approved_count' => (int)$statistics['approved_count'],
            'rejected_count' => (int)$statistics['rejected_count'],
            'needs_attention_count' => (int)$statistics['needs_attention_count'],
            'pending_count' => (int)$statistics['pending_count'],
            'avg_quality_score' => $statistics['avg_quality_score'] ? round((float)$statistics['avg_quality_score'], 2) : null,
            'safety_violations_count' => (int)$statistics['safety_violations_count'],
            'follow_up_required_count' => (int)$statistics['follow_up_required_count'],
            'projects_with_reports' => (int)$statistics['projects_with_reports'],
            'active_inspectors' => (int)$statistics['active_inspectors']
        ],
        'filters_applied' => [
            'project_id' => $project_id,
            'status' => $status,
            'inspector_id' => $inspector_id,
            'date_from' => $date_from,
            'date_to' => $date_to
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching inspection reports for admin: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch inspection reports',
        'error' => $e->getMessage()
    ]);
}
?>