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
        AND cp.status IN ('created', 'in_progress', 'completed', 'on_hold')
        
        UNION ALL
        
        -- Get accepted estimates from contractor_estimates table
        -- (only if no construction_projects row exists for this estimate)
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
        AND NOT EXISTS (SELECT 1 FROM construction_projects cp2 WHERE cp2.estimate_id = ce.id)
        
        UNION ALL
        
        -- Also get projects from contractor_send_estimates that are ready for construction
        -- (only if no construction_projects row exists for this estimate)
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
        AND cse.status IN ('accepted', 'project_created', 'construction_started')
        AND NOT EXISTS (SELECT 1 FROM construction_projects cp2 WHERE cp2.estimate_id = cse.id)
        
        ORDER BY id DESC
    ");
    
    $projects_stmt->execute([$contractor_id, $contractor_id, $contractor_id]);
    $all_projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build project info array - get actual progress from daily_progress_updates in one query
    $project_info = [];
    $projects_data = [];

    // Fetch latest progress for all projects in one query
    // IMPORTANT: daily_progress_updates may store contractor_send_estimates.id as project_id
    // (not construction_projects.id), so we must search both
    $projectIds = array_map('intval', array_column($all_projects, 'id'));
    $estimateIds = array_filter(array_map('intval', array_column($all_projects, 'estimate_id')));

    // Map: estimate_id => construction_project_id (for remapping results)
    $estimateToProjectId = [];
    foreach ($all_projects as $p) {
        if (!empty($p['estimate_id'])) {
            $estimateToProjectId[(int)$p['estimate_id']] = (int)$p['id'];
        }
    }

    $allSearchIds = array_values(array_unique(array_merge($projectIds, $estimateIds)));
    $latestProgressMap = [];

    if (!empty($allSearchIds)) {
        $placeholders = implode(',', array_fill(0, count($allSearchIds), '?'));
        $progressStmt = $db->prepare("
            SELECT 
                project_id,
                MAX(cumulative_completion_percentage) as latest_progress,
                COUNT(*) as updates_count,
                (SELECT construction_stage FROM daily_progress_updates d2 
                 WHERE d2.project_id = d.project_id AND d2.contractor_id = ?
                 ORDER BY d2.update_date DESC, d2.created_at DESC LIMIT 1) as latest_stage
            FROM daily_progress_updates d
            WHERE project_id IN ($placeholders)
            AND contractor_id = ?
            GROUP BY project_id
        ");
        $params = array_merge([$contractor_id], $allSearchIds, [$contractor_id]);
        $progressStmt->execute($params);
        while ($row = $progressStmt->fetch(PDO::FETCH_ASSOC)) {
            $found_id = (int)$row['project_id'];
            $data = [
                'latest_progress' => (float)$row['latest_progress'],
                'updates_count'   => (int)$row['updates_count'],
                'latest_stage'    => $row['latest_stage']
            ];
            // Only remap to construction_projects.id if this found_id is NOT itself a cp.id
            // (i.e. it was stored as an estimate_id)
            if (!in_array($found_id, $projectIds) && isset($estimateToProjectId[$found_id])) {
                $latestProgressMap[$estimateToProjectId[$found_id]] = $data;
            } else {
                $latestProgressMap[$found_id] = $data;
            }
        }
    }

    foreach ($all_projects as $project) {
        $pid = $project['id'];
        $real_completion = isset($latestProgressMap[$pid])
            ? $latestProgressMap[$pid]['latest_progress']
            : (float)$project['completion_percentage'];
        $real_stage = isset($latestProgressMap[$pid]) && $latestProgressMap[$pid]['latest_stage']
            ? $latestProgressMap[$pid]['latest_stage']
            : ($project['current_stage'] ?: 'Planning');
        $real_updates = isset($latestProgressMap[$pid]) ? $latestProgressMap[$pid]['updates_count'] : 0;

        $project_info[$pid] = [
            'project_name'             => $project['project_name'],
            'project_description'      => $project['project_description'],
            'expected_completion_date' => $project['expected_completion_date'],
            'start_date'               => $project['start_date'],
            'status'                   => $project['status'],
            'completion_percentage'    => $real_completion,
            'current_stage'            => $real_stage,
            'homeowner_id'             => $project['homeowner_id'],
            'estimate_cost'            => $project['estimate_cost'] ? floatval($project['estimate_cost']) : null,
            'budget_range'             => $project['lr_budget_range'] ?? $project['budget_range']
        ];

        $projects_data[$pid] = [
            'project_id'      => $pid,
            'homeowner_id'    => $project['homeowner_id'],
            'updates_count'   => $real_updates,
            'latest_progress' => $real_completion,
            'latest_stage'    => $real_stage,
            'latest_date'     => $project['start_date'] ?: $project['expected_completion_date']
        ];
    }
    
    // Now get construction timeline data for projects that have updates
    // Must search by both construction_projects.id AND estimate_id since submit may use either
    $timeline_ids = $allSearchIds; // already contains both project IDs and estimate IDs
    $timeline_placeholders = implode(',', array_fill(0, count($timeline_ids), '?'));
    $timeline_where = "WHERE contractor_id = ? AND project_id IN ($timeline_placeholders)";
    $timeline_params = array_merge([$contractor_id], $timeline_ids);

    if ($project_id) {
        // If filtering by specific project, also include its estimate_id
        $extra_ids = [$project_id];
        foreach ($all_projects as $p) {
            if ((int)$p['id'] === (int)$project_id && !empty($p['estimate_id'])) {
                $extra_ids[] = (int)$p['estimate_id'];
            }
        }
        $ep = implode(',', array_fill(0, count($extra_ids), '?'));
        $timeline_where = "WHERE contractor_id = ? AND project_id IN ($ep)";
        $timeline_params = array_merge([$contractor_id], $extra_ids);
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
        
        // Remap project_id from estimate_id to construction_projects.id if needed
        // Only remap if this project_id is NOT itself a valid construction_projects.id
        $raw_pid = (int)$row['project_id'];
        $mapped_project_id = (!in_array($raw_pid, $projectIds) && isset($estimateToProjectId[$raw_pid]))
            ? $estimateToProjectId[$raw_pid]
            : $raw_pid;

        // Update project data with latest date from timeline entries
        if (isset($projects_data[$mapped_project_id])) {
            $projects_data[$mapped_project_id]['latest_date'] = $row['update_date'];
        }

        $timeline_data[] = [
            'id'               => (int)$row['id'],
            'project_id'       => $mapped_project_id,
            'homeowner_id'     => (int)$row['homeowner_id'],
            'date'             => $row['update_date'],
            'stage'            => $row['construction_stage'],
            'work_description' => $row['work_done_today'],
            'daily_progress'   => (float)$row['incremental_completion_percentage'],
            'total_progress'   => (float)$row['cumulative_completion_percentage'],
            'working_hours'    => (float)$row['working_hours'],
            'weather'          => $row['weather_condition'],
            'issues'           => $row['site_issues'],
            'photos'           => $photos,
            'created_at'       => $row['created_at']
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