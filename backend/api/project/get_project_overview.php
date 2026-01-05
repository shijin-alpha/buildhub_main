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
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }
    
    $project_id = $_GET['project_id'] ?? null;
    
    if (!$project_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Project ID required'
        ]);
        exit;
    }
    
    // Get comprehensive project overview using the view we created
    $overview_query = "
        SELECT 
            po.*,
            u_homeowner.name as homeowner_name,
            u_homeowner.email as homeowner_email,
            u_architect.name as architect_name,
            u_architect.email as architect_email,
            u_contractor.name as contractor_name,
            u_contractor.email as contractor_email,
            lr.requirements,
            lr.house_plan_requirements,
            lr.workflow_status,
            lr.integration_features
        FROM project_overview po
        LEFT JOIN users u_homeowner ON po.homeowner_id = u_homeowner.id
        LEFT JOIN users u_architect ON po.architect_id = u_architect.id
        LEFT JOIN users u_contractor ON po.contractor_id = u_contractor.id
        LEFT JOIN layout_requests lr ON po.layout_request_id = lr.id
        WHERE po.project_id = :project_id
        AND (po.homeowner_id = :user_id OR po.architect_id = :user_id OR po.contractor_id = :user_id)
    ";
    
    $stmt = $db->prepare($overview_query);
    $stmt->execute([
        ':project_id' => $project_id,
        ':user_id' => $user_id
    ]);
    
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode([
            'success' => false,
            'message' => 'Project not found or access denied'
        ]);
        exit;
    }
    
    // Get detailed milestones
    $milestones_query = "
        SELECT 
            pm.*,
            COUNT(gp.id) as milestone_geo_photos,
            COUNT(ppr.id) as milestone_progress_reports
        FROM project_milestones pm
        LEFT JOIN geo_photos gp ON pm.id = gp.milestone_id
        LEFT JOIN project_progress_reports ppr ON pm.id = ppr.milestone_id
        WHERE pm.project_id = :project_id
        GROUP BY pm.id
        ORDER BY pm.order_sequence
    ";
    
    $milestones_stmt = $db->prepare($milestones_query);
    $milestones_stmt->execute([':project_id' => $project_id]);
    $milestones = $milestones_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent house plans
    $house_plans_query = "
        SELECT 
            hp.*,
            u.name as architect_name
        FROM house_plans hp
        LEFT JOIN users u ON hp.architect_id = u.id
        WHERE hp.project_id = :project_id
        ORDER BY hp.created_at DESC
        LIMIT 5
    ";
    
    $house_plans_stmt = $db->prepare($house_plans_query);
    $house_plans_stmt->execute([':project_id' => $project_id]);
    $house_plans = $house_plans_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent geo photos
    $geo_photos_query = "
        SELECT 
            gp.*,
            u.name as contractor_name,
            pm.milestone_name
        FROM geo_photos gp
        LEFT JOIN users u ON gp.contractor_id = u.id
        LEFT JOIN project_milestones pm ON gp.milestone_id = pm.id
        WHERE gp.project_id = :project_id
        ORDER BY gp.created_at DESC
        LIMIT 10
    ";
    
    $geo_photos_stmt = $db->prepare($geo_photos_query);
    $geo_photos_stmt->execute([':project_id' => $project_id]);
    $geo_photos = $geo_photos_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent progress reports
    $progress_reports_query = "
        SELECT 
            ppr.*,
            u.name as contractor_name,
            pm.milestone_name
        FROM project_progress_reports ppr
        LEFT JOIN users u ON ppr.contractor_id = u.id
        LEFT JOIN project_milestones pm ON ppr.milestone_id = pm.id
        WHERE ppr.project_id = :project_id
        ORDER BY ppr.created_at DESC
        LIMIT 5
    ";
    
    $progress_reports_stmt = $db->prepare($progress_reports_query);
    $progress_reports_stmt->execute([':project_id' => $project_id]);
    $progress_reports = $progress_reports_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get feature integration status
    $features_query = "
        SELECT 
            feature_name,
            is_enabled,
            is_active,
            configuration,
            total_usage_count,
            last_used_at
        FROM feature_integrations
        WHERE project_id = :project_id
    ";
    
    $features_stmt = $db->prepare($features_query);
    $features_stmt->execute([':project_id' => $project_id]);
    $features = $features_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent notifications
    $notifications_query = "
        SELECT 
            wn.*
        FROM workflow_notifications wn
        WHERE wn.project_id = :project_id
        AND wn.user_id = :user_id
        ORDER BY wn.created_at DESC
        LIMIT 10
    ";
    
    $notifications_stmt = $db->prepare($notifications_query);
    $notifications_stmt->execute([
        ':project_id' => $project_id,
        ':user_id' => $user_id
    ]);
    $notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate project statistics
    $stats = [
        'completion_percentage' => floatval($project['overall_progress']),
        'days_since_start' => $project['start_date'] ? 
            (new DateTime())->diff(new DateTime($project['start_date']))->days : 0,
        'days_to_completion' => $project['expected_completion_date'] ? 
            (new DateTime())->diff(new DateTime($project['expected_completion_date']))->days : null,
        'budget_utilization' => $project['total_budget'] > 0 ? 
            ($project['spent_budget'] / $project['total_budget']) * 100 : 0,
        'active_features' => count(array_filter($features, function($f) { return $f['is_active']; })),
        'total_interactions' => array_sum(array_column($features, 'total_usage_count'))
    ];
    
    // Prepare response
    $response = [
        'success' => true,
        'project' => [
            'id' => $project['project_id'],
            'name' => $project['project_name'],
            'status' => $project['project_status'],
            'plot_size' => $project['plot_size'],
            'budget_range' => $project['budget_range'],
            'location' => $project['location'],
            'start_date' => $project['start_date'],
            'expected_completion_date' => $project['expected_completion_date'],
            'actual_completion_date' => $project['actual_completion_date'],
            'created_at' => $project['project_created_at'],
            'overall_progress' => floatval($project['overall_progress']),
            'homeowner' => [
                'id' => $project['homeowner_id'],
                'name' => $project['homeowner_name'],
                'email' => $project['homeowner_email']
            ],
            'architect' => $project['architect_id'] ? [
                'id' => $project['architect_id'],
                'name' => $project['architect_name'],
                'email' => $project['architect_email']
            ] : null,
            'contractor' => $project['contractor_id'] ? [
                'id' => $project['contractor_id'],
                'name' => $project['contractor_name'],
                'email' => $project['contractor_email']
            ] : null
        ],
        'milestones' => array_map(function($milestone) {
            return [
                'id' => $milestone['id'],
                'name' => $milestone['milestone_name'],
                'description' => $milestone['milestone_description'],
                'phase' => $milestone['phase'],
                'status' => $milestone['status'],
                'progress_percentage' => floatval($milestone['progress_percentage']),
                'planned_start_date' => $milestone['planned_start_date'],
                'planned_end_date' => $milestone['planned_end_date'],
                'actual_start_date' => $milestone['actual_start_date'],
                'actual_end_date' => $milestone['actual_end_date'],
                'geo_photos_count' => intval($milestone['milestone_geo_photos']),
                'progress_reports_count' => intval($milestone['milestone_progress_reports'])
            ];
        }, $milestones),
        'house_plans' => array_map(function($plan) {
            return [
                'id' => $plan['id'],
                'title' => $plan['title'],
                'status' => $plan['status'],
                'workflow_stage' => $plan['workflow_stage'],
                'architect_name' => $plan['architect_name'],
                'created_at' => $plan['created_at'],
                'updated_at' => $plan['updated_at']
            ];
        }, $house_plans),
        'geo_photos' => array_map(function($photo) {
            return [
                'id' => $photo['id'],
                'filename' => $photo['filename'],
                'coordinates' => [
                    'latitude' => floatval($photo['latitude']),
                    'longitude' => floatval($photo['longitude'])
                ],
                'address' => $photo['address'],
                'contractor_name' => $photo['contractor_name'],
                'milestone_name' => $photo['milestone_name'],
                'workflow_context' => $photo['workflow_context'],
                'created_at' => $photo['created_at']
            ];
        }, $geo_photos),
        'progress_reports' => array_map(function($report) {
            return [
                'id' => $report['id'],
                'title' => $report['report_title'],
                'completion_percentage' => floatval($report['completion_percentage']),
                'status' => $report['report_status'],
                'contractor_name' => $report['contractor_name'],
                'milestone_name' => $report['milestone_name'],
                'submission_date' => $report['submission_date'],
                'created_at' => $report['created_at']
            ];
        }, $progress_reports),
        'features' => array_reduce($features, function($acc, $feature) {
            $acc[$feature['feature_name']] = [
                'enabled' => (bool)$feature['is_enabled'],
                'active' => (bool)$feature['is_active'],
                'usage_count' => intval($feature['total_usage_count']),
                'last_used' => $feature['last_used_at'],
                'configuration' => json_decode($feature['configuration'], true)
            ];
            return $acc;
        }, []),
        'notifications' => array_map(function($notification) {
            return [
                'id' => $notification['id'],
                'type' => $notification['notification_type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'action_required' => (bool)$notification['action_required'],
                'action_url' => $notification['action_url'],
                'is_read' => (bool)$notification['is_read'],
                'created_at' => $notification['created_at']
            ];
        }, $notifications),
        'statistics' => $stats,
        'integration_summary' => [
            'total_house_plans' => intval($project['total_house_plans']),
            'total_geo_photos' => intval($project['total_geo_photos']),
            'total_progress_reports' => intval($project['total_progress_reports']),
            'completed_milestones' => intval($project['completed_milestones']),
            'active_milestones' => intval($project['active_milestones']),
            'workflow_status' => $project['workflow_status'] ?? 'pending'
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Project overview error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving project overview: ' . $e->getMessage()
    ]);
}
?>