<?php
/**
 * Get Construction Timeline Data for Contractor
 * Fetch construction progress timeline from daily_progress_updates table
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';

    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $contractor_id = $_SESSION['user_id'] ?? null;
    
    // For testing purposes, create a mock user session if none exists
    if (!$contractor_id) {
        $_SESSION['user_id'] = 29; // Use the contractor_id from the sample data
        $_SESSION['role'] = 'contractor';
        $contractor_id = 29;
    }
    
    // Get query parameters
    $project_id = $_GET['project_id'] ?? null;
    $homeowner_id = $_GET['homeowner_id'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    // Get all construction projects for this contractor using the same logic as main API
    $projects_stmt = $db->prepare("
        -- Get projects from construction_projects table
        SELECT DISTINCT
            cp.id,
            cp.project_name,
            cp.project_description,
            cp.expected_completion_date,
            cp.start_date,
            cp.status,
            cp.completion_percentage,
            cp.current_stage,
            cp.homeowner_id,
            cp.total_cost as estimate_cost,
            cp.budget_range,
            
            -- Try to get additional budget info from layout_requests if missing (use subqueries to avoid duplicates)
            (SELECT lr.budget_range FROM layout_requests lr WHERE lr.homeowner_id = cp.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_budget_range,
            (SELECT lr.plot_size FROM layout_requests lr WHERE lr.homeowner_id = cp.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_plot_size,
            (SELECT lr.location FROM layout_requests lr WHERE lr.homeowner_id = cp.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_location,
            (SELECT lr.preferred_style FROM layout_requests lr WHERE lr.homeowner_id = cp.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_preferred_style,
            
            'construction_project' as source
        FROM construction_projects cp
        WHERE cp.contractor_id = ? 
        AND cp.status IN ('created', 'in_progress')
        
        UNION ALL
        
        -- Get accepted estimates from contractor_estimates table
        SELECT DISTINCT
            ce.id,
            ce.project_name,
            ce.notes as project_description,
            NULL as expected_completion_date,
            NULL as start_date,
            'ready_for_construction' as status,
            0 as completion_percentage,
            'Planning' as current_stage,
            ce.homeowner_id,
            ce.total_cost as estimate_cost,
            NULL as budget_range,
            
            -- Try to get additional info from layout_requests
            (SELECT lr.budget_range FROM layout_requests lr WHERE lr.homeowner_id = ce.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_budget_range,
            (SELECT lr.plot_size FROM layout_requests lr WHERE lr.homeowner_id = ce.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_plot_size,
            (SELECT lr.location FROM layout_requests lr WHERE lr.homeowner_id = ce.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_location,
            (SELECT lr.preferred_style FROM layout_requests lr WHERE lr.homeowner_id = ce.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_preferred_style,
            
            'contractor_estimate' as source
        FROM contractor_estimates ce
        WHERE ce.contractor_id = ? 
        AND ce.status = 'accepted'
        
        UNION ALL
        
        -- Also get projects from contractor_send_estimates that are ready for construction
        SELECT DISTINCT
            cse.id,
            CASE 
                WHEN cse.structured IS NOT NULL AND JSON_EXTRACT(cse.structured, '$.project_name') IS NOT NULL 
                THEN JSON_UNQUOTE(JSON_EXTRACT(cse.structured, '$.project_name'))
                ELSE CONCAT('Project for ', COALESCE(u.first_name, 'Homeowner'))
            END as project_name,
            cse.notes as project_description,
            NULL as expected_completion_date,
            NULL as start_date,
            'ready_for_construction' as status,
            0 as completion_percentage,
            'Planning' as current_stage,
            cls.homeowner_id,
            cse.total_cost as estimate_cost,
            NULL as budget_range,
            
            -- Budget info from layout_requests
            (SELECT lr.budget_range FROM layout_requests lr WHERE lr.homeowner_id = cls.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_budget_range,
            (SELECT lr.plot_size FROM layout_requests lr WHERE lr.homeowner_id = cls.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_plot_size,
            (SELECT lr.location FROM layout_requests lr WHERE lr.homeowner_id = cls.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_location,
            (SELECT lr.preferred_style FROM layout_requests lr WHERE lr.homeowner_id = cls.homeowner_id AND lr.status = 'approved' ORDER BY lr.created_at DESC LIMIT 1) as lr_preferred_style,
            
            'contractor_send_estimate' as source
        FROM contractor_send_estimates cse
        LEFT JOIN contractor_layout_sends cls ON cls.id = cse.send_id
        LEFT JOIN users u ON u.id = cls.homeowner_id
        WHERE cse.contractor_id = ? 
        AND cse.status IN ('accepted', 'project_created')
        
        ORDER BY id DESC
    ");
    
    $projects_stmt->execute([$contractor_id, $contractor_id, $contractor_id]);
    $all_projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build project info array
    $project_info = [];
    $projects_data = [];
    
    foreach ($all_projects as $project) {
        $project_info[$project['id']] = [
            'project_name' => $project['project_name'],
            'project_description' => $project['project_description'],
            'expected_completion_date' => $project['expected_completion_date'],
            'start_date' => $project['start_date'],
            'status' => $project['status'],
            'completion_percentage' => $project['completion_percentage'],
            'current_stage' => $project['current_stage'],
            'homeowner_id' => $project['homeowner_id'],
            'estimate_cost' => $project['estimate_cost'] ? floatval($project['estimate_cost']) : null,
            'budget_range' => $project['lr_budget_range'] ?? $project['budget_range']
        ];
        
        // Initialize project data
        $projects_data[$project['id']] = [
            'project_id' => $project['id'],
            'homeowner_id' => $project['homeowner_id'],
            'updates_count' => 0,
            'latest_progress' => (float)$project['completion_percentage'],
            'latest_stage' => $project['current_stage'] ?: 'Planning',
            'latest_date' => $project['start_date'] ?: $project['expected_completion_date']
        ];
    }
    
    // Now get construction timeline data for projects that have updates
    $timeline_where = "WHERE contractor_id = ?";
    $timeline_params = [$contractor_id];
    
    if ($project_id) {
        $timeline_where .= " AND project_id = ?";
        $timeline_params[] = $project_id;
    }
    
    if ($homeowner_id) {
        $timeline_where .= " AND homeowner_id = ?";
        $timeline_params[] = $homeowner_id;
    }
    
    // Get construction timeline data
    $stmt = $db->prepare("
        SELECT 
            id,
            project_id,
            homeowner_id,
            update_date,
            construction_stage,
            work_done_today,
            incremental_completion_percentage,
            cumulative_completion_percentage,
            working_hours,
            weather_condition,
            site_issues,
            progress_photos,
            created_at
        FROM daily_progress_updates 
        $timeline_where
        ORDER BY update_date ASC, created_at ASC
        LIMIT ?
    ");
    
    $timeline_params[] = $limit;
    $stmt->execute($timeline_params);
    
    $timeline_data = [];
    $milestones = [];
    $current_stage = '';
    $stage_start_date = null;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Parse progress photos if they exist
        $photos = [];
        if (!empty($row['progress_photos'])) {
            $photos_data = json_decode($row['progress_photos'], true);
            if (is_array($photos_data)) {
                $photos = $photos_data;
            }
        }
        
        // Track stage changes for milestones
        if ($current_stage !== $row['construction_stage']) {
            if ($current_stage !== '') {
                // Add milestone for previous stage completion
                $milestones[] = [
                    'stage' => $current_stage,
                    'date' => $stage_start_date,
                    'type' => 'stage_start',
                    'progress' => $row['cumulative_completion_percentage'],
                    'project_id' => $row['project_id']
                ];
            }
            
            $current_stage = $row['construction_stage'];
            $stage_start_date = $row['update_date'];
        }
        
        // Update project data with actual progress updates
        if (isset($projects_data[$row['project_id']])) {
            $projects_data[$row['project_id']]['updates_count']++;
            $projects_data[$row['project_id']]['latest_progress'] = (float)$row['cumulative_completion_percentage'];
            $projects_data[$row['project_id']]['latest_stage'] = $row['construction_stage'];
            $projects_data[$row['project_id']]['latest_date'] = $row['update_date'];
        }
        
        $timeline_data[] = [
            'id' => (int)$row['id'],
            'project_id' => (int)$row['project_id'],
            'homeowner_id' => (int)$row['homeowner_id'],
            'date' => $row['update_date'],
            'stage' => $row['construction_stage'],
            'work_description' => $row['work_done_today'],
            'daily_progress' => (float)$row['incremental_completion_percentage'],
            'total_progress' => (float)$row['cumulative_completion_percentage'],
            'working_hours' => (float)$row['working_hours'],
            'weather' => $row['weather_condition'],
            'issues' => $row['site_issues'],
            'photos' => $photos,
            'created_at' => $row['created_at']
        ];
    }
    
    // Add final milestone if we have data
    if (!empty($timeline_data)) {
        $last_entry = end($timeline_data);
        $milestones[] = [
            'stage' => $last_entry['stage'],
            'date' => $last_entry['date'],
            'type' => 'current',
            'progress' => $last_entry['total_progress'],
            'project_id' => $last_entry['project_id']
        ];
    }
    
    // Calculate timeline statistics
    $stats = [
        'total_updates' => count($timeline_data),
        'total_projects' => count($projects_data),
        'current_progress' => !empty($timeline_data) ? end($timeline_data)['total_progress'] : 0,
        'total_stages' => count(array_unique(array_column($timeline_data, 'stage'))),
        'total_working_hours' => array_sum(array_column($timeline_data, 'working_hours')),
        'start_date' => !empty($timeline_data) ? $timeline_data[0]['date'] : null,
        'last_update' => !empty($timeline_data) ? end($timeline_data)['date'] : null,
        'projects_summary' => array_values($projects_data)
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'timeline' => $timeline_data,
            'milestones' => $milestones,
            'projects_info' => $project_info,
            'statistics' => $stats,
            'user_role' => 'contractor'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Contractor construction timeline error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve construction timeline',
        'error' => $e->getMessage()
    ]);
}