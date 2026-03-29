<?php
/**
 * Get Detailed Project Progress for Site Inspector
 * Returns comprehensive progress data from daily_progress_updates table
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
    
    // Check if user is logged in as admin or inspector
    session_start();
    $isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    
    if (!$isAdmin) {
        // Use authorization middleware for non-admin users
        $auth = new AuthorizationMiddleware($db);
        $auth->requireAuth();
        $auth->requireCapability('view_assigned_projects');
        $currentUserId = $auth->getCurrentUser()['id'];
    } else {
        // Admin has full access - set up a mock user ID
        $currentUserId = 1;
    }
    
    $project_id = $_GET['project_id'] ?? null;
    
    if (!$project_id) {
        throw new Exception('Project ID is required');
    }
    
    $inspector_id = $currentUserId;
    
    // Verify inspector has access to this project (only for non-admin users)
    if (!$isAdmin) {
        $access_query = "
            SELECT COUNT(*) as has_access 
            FROM site_inspector_assignments sia 
            WHERE sia.inspector_id = ? AND sia.project_id = ? AND sia.status = 'active'
        ";
        $access_stmt = $db->prepare($access_query);
        $access_stmt->execute([$inspector_id, $project_id]);
        $access_result = $access_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($access_result['has_access'] == 0) {
            throw new Exception('Access denied to this project');
        }
    }
    // Admins have access to all projects
    
    // Get project basic information
    $project_query = "
        SELECT 
            cp.id,
            cp.project_name,
            cp.project_description,
            cp.status,
            cp.current_stage,
            cp.completion_percentage,
            cp.project_location,
            cp.start_date,
            cp.expected_completion_date,
            cp.total_cost,
            CONCAT(u_homeowner.first_name, ' ', u_homeowner.last_name) as homeowner_name,
            u_homeowner.email as homeowner_email,
            u_homeowner.phone as homeowner_phone,
            CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) as contractor_name,
            u_contractor.email as contractor_email,
            u_contractor.phone as contractor_phone
        FROM construction_projects cp
        LEFT JOIN users u_homeowner ON cp.homeowner_id = u_homeowner.id
        LEFT JOIN users u_contractor ON cp.contractor_id = u_contractor.id
        WHERE cp.id = ?
    ";
    
    $project_stmt = $db->prepare($project_query);
    $project_stmt->execute([$project_id]);
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        throw new Exception('Project not found');
    }
    
    // Get detailed daily progress updates
    $progress_query = "
        SELECT 
            dpu.id,
            dpu.update_date,
            dpu.construction_stage,
            dpu.work_done_today,
            dpu.incremental_completion_percentage,
            dpu.cumulative_completion_percentage,
            dpu.working_hours,
            dpu.weather_condition,
            dpu.site_issues,
            dpu.progress_photos,
            dpu.latitude,
            dpu.longitude,
            dpu.location_verified,
            dpu.created_at,
            dpu.updated_at,
            CONCAT(u.first_name, ' ', u.last_name) as contractor_name,
            u.email as contractor_email
        FROM daily_progress_updates dpu
        LEFT JOIN users u ON dpu.contractor_id = u.id
        WHERE dpu.project_id = ?
        ORDER BY dpu.update_date DESC, dpu.created_at DESC
    ";
    
    $progress_stmt = $db->prepare($progress_query);
    $progress_stmt->execute([$project_id]);
    $progress_updates = $progress_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format progress updates
    $formatted_updates = [];
    foreach ($progress_updates as $update) {
        $photos = [];
        if (!empty($update['progress_photos'])) {
            $decoded_photos = json_decode($update['progress_photos'], true);
            if (is_array($decoded_photos)) {
                $photos = $decoded_photos;
            }
        }
        
        $formatted_updates[] = [
            'id' => (int)$update['id'],
            'update_date' => $update['update_date'],
            'construction_stage' => $update['construction_stage'],
            'work_done_today' => $update['work_done_today'],
            'incremental_completion_percentage' => (float)$update['incremental_completion_percentage'],
            'cumulative_completion_percentage' => (float)$update['cumulative_completion_percentage'],
            'working_hours' => (float)$update['working_hours'],
            'weather_condition' => $update['weather_condition'],
            'site_issues' => $update['site_issues'],
            'progress_photos' => $photos,
            'location' => [
                'latitude' => $update['latitude'] ? (float)$update['latitude'] : null,
                'longitude' => $update['longitude'] ? (float)$update['longitude'] : null,
                'verified' => (bool)$update['location_verified']
            ],
            'contractor' => [
                'name' => $update['contractor_name'],
                'email' => $update['contractor_email']
            ],
            'timestamps' => [
                'created_at' => $update['created_at'],
                'updated_at' => $update['updated_at']
            ]
        ];
    }
    
    // Get progress statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_updates,
            COUNT(DISTINCT construction_stage) as stages_worked,
            AVG(incremental_completion_percentage) as avg_daily_progress,
            MAX(cumulative_completion_percentage) as current_completion,
            SUM(working_hours) as total_working_hours,
            COUNT(CASE WHEN site_issues IS NOT NULL AND site_issues != '' THEN 1 END) as updates_with_issues,
            MIN(update_date) as first_update_date,
            MAX(update_date) as last_update_date
        FROM daily_progress_updates 
        WHERE project_id = ?
    ";
    
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([$project_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get stage-wise progress breakdown
    $stage_query = "
        SELECT 
            construction_stage,
            COUNT(*) as update_count,
            SUM(incremental_completion_percentage) as total_stage_progress,
            AVG(working_hours) as avg_working_hours,
            MIN(update_date) as stage_start_date,
            MAX(update_date) as stage_last_update,
            COUNT(CASE WHEN site_issues IS NOT NULL AND site_issues != '' THEN 1 END) as issues_count
        FROM daily_progress_updates 
        WHERE project_id = ?
        GROUP BY construction_stage
        ORDER BY MIN(update_date) ASC
    ";
    
    $stage_stmt = $db->prepare($stage_query);
    $stage_stmt->execute([$project_id]);
    $stage_breakdown = $stage_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format stage breakdown
    $formatted_stages = [];
    foreach ($stage_breakdown as $stage) {
        $formatted_stages[] = [
            'stage_name' => $stage['construction_stage'],
            'update_count' => (int)$stage['update_count'],
            'total_progress' => (float)$stage['total_stage_progress'],
            'avg_working_hours' => (float)$stage['avg_working_hours'],
            'stage_start_date' => $stage['stage_start_date'],
            'stage_last_update' => $stage['stage_last_update'],
            'issues_count' => (int)$stage['issues_count']
        ];
    }
    
    // Get recent site issues
    $issues_query = "
        SELECT 
            update_date,
            construction_stage,
            site_issues,
            weather_condition,
            CONCAT(u.first_name, ' ', u.last_name) as contractor_name
        FROM daily_progress_updates dpu
        LEFT JOIN users u ON dpu.contractor_id = u.id
        WHERE dpu.project_id = ? 
        AND dpu.site_issues IS NOT NULL 
        AND dpu.site_issues != ''
        ORDER BY dpu.update_date DESC
        LIMIT 10
    ";
    
    $issues_stmt = $db->prepare($issues_query);
    $issues_stmt->execute([$project_id]);
    $recent_issues = $issues_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the action (only for authenticated users)
    if (!$isAdmin && isset($auth)) {
        $auth->logAction('view_project_progress_details', $project_id, 'project_progress', null, [
            'total_updates' => count($formatted_updates),
            'stages_count' => count($formatted_stages)
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'project' => [
            'id' => (int)$project['id'],
            'project_name' => $project['project_name'],
            'project_description' => $project['project_description'],
            'status' => $project['status'],
            'current_stage' => $project['current_stage'],
            'completion_percentage' => (float)$project['completion_percentage'],
            'project_location' => $project['project_location'],
            'start_date' => $project['start_date'],
            'expected_completion_date' => $project['expected_completion_date'],
            'total_cost' => $project['total_cost'] ? (float)$project['total_cost'] : null,
            'homeowner' => [
                'name' => $project['homeowner_name'],
                'email' => $project['homeowner_email'],
                'phone' => $project['homeowner_phone']
            ],
            'contractor' => [
                'name' => $project['contractor_name'],
                'email' => $project['contractor_email'],
                'phone' => $project['contractor_phone']
            ]
        ],
        'progress_updates' => $formatted_updates,
        'statistics' => [
            'total_updates' => (int)$stats['total_updates'],
            'stages_worked' => (int)$stats['stages_worked'],
            'avg_daily_progress' => round((float)$stats['avg_daily_progress'], 2),
            'current_completion' => (float)$stats['current_completion'],
            'total_working_hours' => (float)$stats['total_working_hours'],
            'updates_with_issues' => (int)$stats['updates_with_issues'],
            'first_update_date' => $stats['first_update_date'],
            'last_update_date' => $stats['last_update_date']
        ],
        'stage_breakdown' => $formatted_stages,
        'recent_issues' => $recent_issues
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching project progress details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>