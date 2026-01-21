<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

try {
    // Get project ID from query parameters
    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
    
    if (!$project_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Project ID is required'
        ]);
        exit();
    }
    
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all progress updates for the project
    $query = "
        SELECT 
            dpu.id,
            dpu.project_id,
            dpu.contractor_id,
            dpu.homeowner_id,
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
            CONCAT(u.first_name, ' ', u.last_name) as contractor_name
        FROM daily_progress_updates dpu
        LEFT JOIN users u ON dpu.contractor_id = u.id
        WHERE dpu.project_id = :project_id
        ORDER BY dpu.update_date ASC, dpu.created_at ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $progress_updates = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Parse photos if they exist
        $photos = [];
        if (!empty($row['progress_photos'])) {
            $photos_data = json_decode($row['progress_photos'], true);
            if (is_array($photos_data)) {
                $photos = $photos_data;
            }
        }
        
        $progress_updates[] = [
            'id' => intval($row['id']),
            'project_id' => intval($row['project_id']),
            'contractor_id' => intval($row['contractor_id']),
            'homeowner_id' => intval($row['homeowner_id']),
            'update_date' => $row['update_date'],
            'construction_stage' => $row['construction_stage'],
            'work_done_today' => $row['work_done_today'],
            'incremental_completion_percentage' => floatval($row['incremental_completion_percentage']),
            'cumulative_completion_percentage' => floatval($row['cumulative_completion_percentage']),
            'working_hours' => floatval($row['working_hours']),
            'weather_condition' => $row['weather_condition'],
            'site_issues' => $row['site_issues'],
            'photos' => $photos,
            'latitude' => $row['latitude'] ? floatval($row['latitude']) : null,
            'longitude' => $row['longitude'] ? floatval($row['longitude']) : null,
            'location_verified' => boolval($row['location_verified']),
            'contractor_name' => $row['contractor_name'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    
    // Get project basic info
    $project_query = "
        SELECT 
            cp.id,
            cp.project_name,
            cp.homeowner_id,
            cp.contractor_id,
            cp.estimate_id,
            cp.status as project_status,
            cp.created_at,
            cp.updated_at,
            CONCAT(h.first_name, ' ', h.last_name) as homeowner_name,
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name
        FROM construction_projects cp
        LEFT JOIN users h ON cp.homeowner_id = h.id
        LEFT JOIN users c ON cp.contractor_id = c.id
        WHERE cp.id = ? OR cp.estimate_id = ?
        ORDER BY cp.created_at DESC
        LIMIT 1
    ";
    
    $project_stmt = $db->prepare($project_query);
    $project_stmt->execute([$project_id, $project_id]);
    
    $project_info = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project_info) {
        echo json_encode([
            'success' => false,
            'message' => 'Project not found'
        ]);
        exit();
    }
    
    // Calculate stage-wise statistics
    $stage_stats = [];
    $total_progress = 0;
    $latest_progress = 0;
    
    foreach ($progress_updates as $update) {
        $stage = $update['construction_stage'];
        
        if (!isset($stage_stats[$stage])) {
            $stage_stats[$stage] = [
                'stage_name' => $stage,
                'total_incremental' => 0,
                'days_worked' => 0,
                'total_hours' => 0,
                'first_date' => $update['update_date'],
                'last_date' => $update['update_date'],
                'updates_count' => 0
            ];
        }
        
        $stage_stats[$stage]['total_incremental'] += $update['incremental_completion_percentage'];
        $stage_stats[$stage]['days_worked']++;
        $stage_stats[$stage]['total_hours'] += $update['working_hours'];
        $stage_stats[$stage]['updates_count']++;
        $stage_stats[$stage]['last_date'] = $update['update_date'];
        
        $latest_progress = max($latest_progress, $update['cumulative_completion_percentage']);
    }
    
    // Calculate project timeline statistics
    $timeline_stats = [
        'total_updates' => count($progress_updates),
        'total_working_days' => count(array_unique(array_column($progress_updates, 'update_date'))),
        'total_working_hours' => array_sum(array_column($progress_updates, 'working_hours')),
        'latest_progress' => $latest_progress,
        'stages_worked' => count($stage_stats),
        'average_daily_progress' => 0
    ];
    
    if ($timeline_stats['total_working_days'] > 0) {
        $timeline_stats['average_daily_progress'] = $latest_progress / $timeline_stats['total_working_days'];
    }
    
    // Get the most recent cumulative progress from database
    $latest_progress_query = "
        SELECT 
            cumulative_completion_percentage,
            update_date,
            construction_stage
        FROM daily_progress_updates 
        WHERE project_id = :project_id 
        ORDER BY update_date DESC, created_at DESC 
        LIMIT 1
    ";
    
    $latest_stmt = $db->prepare($latest_progress_query);
    $latest_stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $latest_stmt->execute();
    
    $latest_progress_data = $latest_stmt->fetch(PDO::FETCH_ASSOC);
    $current_cumulative_progress = $latest_progress_data ? 
        floatval($latest_progress_data['cumulative_completion_percentage']) : 0;
    
    // Update timeline stats with actual database progress
    $timeline_stats['current_cumulative_progress'] = $current_cumulative_progress;
    $timeline_stats['latest_stage'] = $latest_progress_data ? $latest_progress_data['construction_stage'] : null;
    $timeline_stats['latest_update_date'] = $latest_progress_data ? $latest_progress_data['update_date'] : null;
    
    // Estimate completion date based on current progress rate
    $estimated_completion = null;
    if ($timeline_stats['average_daily_progress'] > 0 && $latest_progress < 100) {
        $remaining_progress = 100 - $latest_progress;
        $estimated_days = ceil($remaining_progress / $timeline_stats['average_daily_progress']);
        $estimated_completion = date('Y-m-d', strtotime("+{$estimated_days} days"));
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'project_info' => [
                'id' => intval($project_info['id']),
                'project_name' => $project_info['project_name'],
                'homeowner_id' => intval($project_info['homeowner_id']),
                'contractor_id' => intval($project_info['contractor_id']),
                'estimate_id' => intval($project_info['estimate_id']),
                'project_status' => $project_info['project_status'],
                'homeowner_name' => $project_info['homeowner_name'],
                'contractor_name' => $project_info['contractor_name'],
                'created_at' => $project_info['created_at'],
                'updated_at' => $project_info['updated_at']
            ],
            'timeline_stats' => $timeline_stats,
            'stage_stats' => array_values($stage_stats),
            'estimated_completion' => $estimated_completion
        ],
        'progress_updates' => $progress_updates,
        'message' => 'Project progress data retrieved successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_project_progress.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving project progress data',
        'error' => $e->getMessage()
    ]);
}
?>