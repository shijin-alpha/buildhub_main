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
    
    // Direct database connection (more reliable)
    $host = 'localhost';
    $dbname = 'buildhub';
    $username = 'root';
    $password = '';
    
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the latest cumulative progress for the project
    $query = "
        SELECT 
            cumulative_completion_percentage,
            incremental_completion_percentage,
            update_date,
            construction_stage,
            work_done_today,
            working_hours,
            weather_condition,
            CONCAT(u.first_name, ' ', u.last_name) as contractor_name
        FROM daily_progress_updates dpu
        LEFT JOIN users u ON dpu.contractor_id = u.id
        WHERE dpu.project_id = ?
        ORDER BY dpu.update_date DESC, dpu.created_at DESC 
        LIMIT 1
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$project_id]);
    $latest_update = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$latest_update) {
        // No progress updates found - project at 0%
        echo json_encode([
            'success' => true,
            'data' => [
                'project_id' => $project_id,
                'current_progress' => 0,
                'latest_stage' => null,
                'latest_update_date' => null,
                'has_updates' => false,
                'message' => 'No progress updates found for this project'
            ]
        ]);
        exit();
    }
    
    // Get project basic info with budget from estimate
    $project_query = "
        SELECT 
            cp.id,
            cp.project_name,
            cp.homeowner_id,
            cp.contractor_id,
            cp.estimate_id,
            CONCAT(h.first_name, ' ', h.last_name) as homeowner_name,
            CONCAT(c.first_name, ' ', c.last_name) as contractor_name,
            cse.total_cost as project_budget,
            cse.timeline as project_timeline,
            lr.budget_range as original_budget_range
        FROM construction_projects cp
        LEFT JOIN users h ON cp.homeowner_id = h.id
        LEFT JOIN users c ON cp.contractor_id = c.id
        LEFT JOIN contractor_send_estimates cse ON cp.estimate_id = cse.id
        LEFT JOIN layout_requests lr ON cse.send_id = lr.id
        WHERE cp.id = ? OR cp.estimate_id = ?
        ORDER BY cp.created_at DESC
        LIMIT 1
    ";
    
    $project_stmt = $db->prepare($project_query);
    $project_stmt->execute([$project_id, $project_id]);
    $project_info = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get total number of updates for this project
    $count_query = "SELECT COUNT(*) as total_updates FROM daily_progress_updates WHERE project_id = ?";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute([$project_id]);
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'project_id' => $project_id,
            'project_name' => $project_info ? $project_info['project_name'] : 'Unknown Project',
            'project_budget' => $project_info && $project_info['project_budget'] ? floatval($project_info['project_budget']) : null,
            'budget_formatted' => $project_info && $project_info['project_budget'] ? 
                '₹' . number_format($project_info['project_budget'], 0, '.', ',') : null,
            'original_budget_range' => $project_info ? $project_info['original_budget_range'] : null,
            'project_timeline' => $project_info ? $project_info['project_timeline'] : null,
            'homeowner_name' => $project_info ? $project_info['homeowner_name'] : null,
            'contractor_name' => $project_info ? $project_info['contractor_name'] : null,
            'current_progress' => floatval($latest_update['cumulative_completion_percentage']),
            'latest_stage' => $latest_update['construction_stage'],
            'latest_update_date' => $latest_update['update_date'],
            'latest_work_description' => $latest_update['work_done_today'],
            'latest_working_hours' => floatval($latest_update['working_hours']),
            'latest_weather' => $latest_update['weather_condition'],
            'total_updates' => intval($count_result['total_updates']),
            'has_updates' => true,
            'last_updated_by' => $latest_update['contractor_name']
        ],
        'message' => 'Current project progress retrieved successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_project_current_progress.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving project progress',
        'error' => $e->getMessage()
    ]);
}
?>