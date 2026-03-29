<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { 
    header('Access-Control-Allow-Origin: ' . $origin); 
    header('Vary: Origin'); 
} else { 
    header('Access-Control-Allow-Origin: http://localhost:3000'); 
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    header('Access-Control-Max-Age: 86400'); 
    exit; 
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get contractor ID and project ID from parameters
    $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    if ($contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing contractor_id']);
        exit;
    }

    // Build query based on parameters
    $whereClause = "WHERE dpu.contractor_id = :contractor_id";
    $params = [':contractor_id' => $contractor_id];

    if ($project_id > 0) {
        $whereClause .= " AND dpu.project_id = :project_id";
        $params[':project_id'] = $project_id;
    }

    // Get daily progress updates submitted by contractor with project details
    $stmt = $db->prepare("
        SELECT 
            dpu.*,
            u_homeowner.first_name as homeowner_first_name,
            u_homeowner.last_name as homeowner_last_name,
            u_homeowner.email as homeowner_email,
            u_homeowner.phone as homeowner_phone,
            COUNT(dlt.id) as labour_entries_count,
            GROUP_CONCAT(DISTINCT dlt.worker_type) as worker_types,
            SUM(dlt.worker_count) as total_workers,
            AVG(dlt.productivity_rating) as avg_productivity,
            -- Try to get project name from multiple sources
            COALESCE(
                cp.project_name,
                ce.project_name,
                CONCAT('Project #', dpu.project_id)
            ) as project_name
        FROM daily_progress_updates dpu
        LEFT JOIN users u_homeowner ON dpu.homeowner_id = u_homeowner.id
        LEFT JOIN daily_labour_tracking dlt ON dpu.id = dlt.daily_progress_id
        LEFT JOIN construction_projects cp ON dpu.project_id = cp.id
        LEFT JOIN contractor_estimates ce ON dpu.project_id = ce.id
        {$whereClause}
        GROUP BY dpu.id
        ORDER BY dpu.update_date DESC, dpu.created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $submitted_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process submitted reports and add additional data
    foreach ($submitted_reports as &$report) {
        // Decode photo paths
        $report['photos'] = json_decode($report['progress_photos'], true) ?: [];
        
        // Add full URLs for photos
        $report['photo_urls'] = array_map(function($photo) {
            return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/buildhub/backend' . $photo['path'];
        }, $report['photos']);

        // Format dates
        $report['update_date_formatted'] = date('M j, Y', strtotime($report['update_date']));
        $report['created_at_formatted'] = date('M j, Y g:i A', strtotime($report['created_at']));
        $report['updated_at_formatted'] = date('M j, Y g:i A', strtotime($report['updated_at']));

        // Add progress status based on completion percentage
        if ($report['cumulative_completion_percentage'] >= 100) {
            $report['status'] = 'Completed';
            $report['status_class'] = 'badge-success';
        } elseif ($report['cumulative_completion_percentage'] >= 75) {
            $report['status'] = 'Near Completion';
            $report['status_class'] = 'badge-info';
        } elseif ($report['cumulative_completion_percentage'] >= 25) {
            $report['status'] = 'In Progress';
            $report['status_class'] = 'badge-warning';
        } else {
            $report['status'] = 'Started';
            $report['status_class'] = 'badge-secondary';
        }

        // Add completion percentage class for progress bars
        if ($report['cumulative_completion_percentage'] >= 100) {
            $report['progress_class'] = 'progress-complete';
        } elseif ($report['cumulative_completion_percentage'] >= 75) {
            $report['progress_class'] = 'progress-high';
        } elseif ($report['cumulative_completion_percentage'] >= 50) {
            $report['progress_class'] = 'progress-medium';
        } elseif ($report['cumulative_completion_percentage'] >= 25) {
            $report['progress_class'] = 'progress-low';
        } else {
            $report['progress_class'] = 'progress-minimal';
        }

        // Add time ago
        $report['time_ago'] = timeAgo($report['created_at']);

        // Format homeowner name
        $report['homeowner_name'] = trim($report['homeowner_first_name'] . ' ' . $report['homeowner_last_name']);

        // Format worker types
        $report['worker_types_array'] = $report['worker_types'] ? explode(',', $report['worker_types']) : [];
        
        // Add location verification status
        $report['location_status'] = $report['location_verified'] ? 'Verified' : 'Not Verified';
        $report['location_class'] = $report['location_verified'] ? 'badge-success' : 'badge-warning';

        // Clean up sensitive data
        unset($report['progress_photos']);
    }

    // Get total count for pagination
    $countStmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM daily_progress_updates dpu 
        {$whereClause}
    ");
    
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get projects summary for contractor
    $projectsStmt = $db->prepare("
        SELECT 
            dpu.project_id,
            COALESCE(
                cp.project_name,
                ce.project_name,
                CONCAT('Project #', dpu.project_id)
            ) as project_name,
            u_homeowner.first_name as homeowner_first_name,
            u_homeowner.last_name as homeowner_last_name,
            COUNT(dpu.id) as total_updates,
            MAX(dpu.cumulative_completion_percentage) as latest_progress,
            MAX(dpu.update_date) as last_update_date,
            MAX(dpu.created_at) as last_update_time,
            COUNT(DISTINCT dpu.construction_stage) as stages_worked,
            AVG(dpu.incremental_completion_percentage) as avg_daily_progress
        FROM daily_progress_updates dpu
        LEFT JOIN users u_homeowner ON dpu.homeowner_id = u_homeowner.id
        LEFT JOIN construction_projects cp ON dpu.project_id = cp.id
        LEFT JOIN contractor_estimates ce ON dpu.project_id = ce.id
        WHERE dpu.contractor_id = :contractor_id
        GROUP BY dpu.project_id, dpu.homeowner_id
        ORDER BY last_update_time DESC
    ");
    
    $projectsStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $projectsStmt->execute();
    $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format project data
    foreach ($projects as &$project) {
        $project['last_update_formatted'] = $project['last_update_date'] 
            ? date('M j, Y', strtotime($project['last_update_date']))
            : 'No updates yet';
        $project['last_update_time_formatted'] = $project['last_update_time'] 
            ? date('M j, Y g:i A', strtotime($project['last_update_time']))
            : 'No updates yet';
        $project['latest_progress'] = $project['latest_progress'] ?: 0;
        $project['stages_worked'] = $project['stages_worked'] ?: 0;
        $project['homeowner_name'] = trim($project['homeowner_first_name'] . ' ' . $project['homeowner_last_name']);
        $project['avg_daily_progress'] = round($project['avg_daily_progress'] ?: 0, 2);
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'submitted_reports' => $submitted_reports,
            'projects' => $projects,
            'pagination' => [
                'total' => (int)$totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ],
            'summary' => [
                'total_reports' => (int)$totalCount,
                'total_projects' => count($projects)
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("Get contractor submitted reports error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred: ' . $e->getMessage()]);
}

// Helper function for time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}
?>