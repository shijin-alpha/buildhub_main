<?php
/**
 * Get Construction Timeline Data
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
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    // For testing purposes, create a mock user session if none exists
    if (!$homeowner_id) {
        $_SESSION['user_id'] = 28; // Use the homeowner_id from the sample data
        $_SESSION['role'] = 'homeowner';
        $homeowner_id = 28;
    }
    
    // Get query parameters
    $project_id = $_GET['project_id'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    // Build query
    $where_clause = "WHERE homeowner_id = ?";
    $params = [$homeowner_id];
    
    if ($project_id) {
        $where_clause .= " AND project_id = ?";
        $params[] = $project_id;
    }
    
    // Get construction timeline data
    $stmt = $db->prepare("
        SELECT 
            id,
            project_id,
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
        $where_clause
        ORDER BY update_date ASC, created_at ASC
        LIMIT ?
    ");
    
    $params[] = $limit;
    $stmt->execute($params);
    
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
                    'progress' => $row['cumulative_completion_percentage']
                ];
            }
            
            $current_stage = $row['construction_stage'];
            $stage_start_date = $row['update_date'];
        }
        
        $timeline_data[] = [
            'id' => (int)$row['id'],
            'project_id' => (int)$row['project_id'],
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
            'progress' => $last_entry['total_progress']
        ];
    }
    
    // Get project information
    $project_info = null;
    if (!empty($timeline_data)) {
        $first_project_id = $timeline_data[0]['project_id'];
        
        $project_stmt = $db->prepare("
            SELECT 
                id,
                project_name,
                project_description as project_type,
                expected_completion_date as estimated_completion_date,
                start_date as actual_start_date,
                status,
                completion_percentage,
                current_stage
            FROM construction_projects 
            WHERE id = ? AND homeowner_id = ?
        ");
        $project_stmt->execute([$first_project_id, $homeowner_id]);
        $project_info = $project_stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Calculate timeline statistics
    $stats = [
        'total_updates' => count($timeline_data),
        'current_progress' => !empty($timeline_data) ? end($timeline_data)['total_progress'] : 0,
        'total_stages' => count(array_unique(array_column($timeline_data, 'stage'))),
        'total_working_hours' => array_sum(array_column($timeline_data, 'working_hours')),
        'start_date' => !empty($timeline_data) ? $timeline_data[0]['date'] : null,
        'last_update' => !empty($timeline_data) ? end($timeline_data)['date'] : null
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'timeline' => $timeline_data,
            'milestones' => $milestones,
            'project_info' => $project_info,
            'statistics' => $stats
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Construction timeline error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve construction timeline',
        'error' => $e->getMessage()
    ]);
}