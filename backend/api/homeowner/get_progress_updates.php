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

    // Get parameters
    $homeowner_id = isset($_GET['homeowner_id']) ? (int)$_GET['homeowner_id'] : 0;
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    if ($homeowner_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing homeowner_id']);
        exit;
    }

    // Build query based on parameters
    $whereClause = "WHERE dpu.homeowner_id = :homeowner_id";
    $params = [':homeowner_id' => $homeowner_id];

    if ($project_id > 0) {
        $whereClause .= " AND dpu.project_id = :project_id";
        $params[':project_id'] = $project_id;
    }

    // Get daily progress updates with contractor details and labour tracking
    $stmt = $db->prepare("
        SELECT 
            dpu.*,
            u_contractor.first_name as contractor_first_name,
            u_contractor.last_name as contractor_last_name,
            u_contractor.email as contractor_email,
            u_contractor.phone as contractor_phone,
            u_contractor.company_name as contractor_company,
            COUNT(dlt.id) as labour_entries_count,
            GROUP_CONCAT(DISTINCT dlt.worker_type) as worker_types,
            SUM(dlt.worker_count) as total_workers,
            AVG(dlt.productivity_rating) as avg_productivity
        FROM daily_progress_updates dpu
        LEFT JOIN users u_contractor ON dpu.contractor_id = u_contractor.id
        LEFT JOIN daily_labour_tracking dlt ON dpu.id = dlt.daily_progress_id
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

    $progress_updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process progress updates and add additional data
    foreach ($progress_updates as &$update) {
        // Decode photo paths
        $update['photos'] = json_decode($update['progress_photos'], true) ?: [];
        
        // Add full URLs for photos
        $update['photo_urls'] = array_map(function($path) {
            return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/buildhub/backend' . $path['path'];
        }, $update['photos']);

        // Format dates
        $update['update_date_formatted'] = date('M j, Y', strtotime($update['update_date']));
        $update['created_at_formatted'] = date('M j, Y g:i A', strtotime($update['created_at']));
        $update['updated_at_formatted'] = date('M j, Y g:i A', strtotime($update['updated_at']));

        // Add progress status based on completion percentage
        if ($update['cumulative_completion_percentage'] >= 100) {
            $update['status'] = 'Completed';
            $update['status_class'] = 'badge-success';
        } elseif ($update['cumulative_completion_percentage'] >= 75) {
            $update['status'] = 'Near Completion';
            $update['status_class'] = 'badge-info';
        } elseif ($update['cumulative_completion_percentage'] >= 25) {
            $update['status'] = 'In Progress';
            $update['status_class'] = 'badge-warning';
        } else {
            $update['status'] = 'Started';
            $update['status_class'] = 'badge-secondary';
        }

        // Add completion percentage class for progress bars
        if ($update['cumulative_completion_percentage'] >= 100) {
            $update['progress_class'] = 'progress-complete';
        } elseif ($update['cumulative_completion_percentage'] >= 75) {
            $update['progress_class'] = 'progress-high';
        } elseif ($update['cumulative_completion_percentage'] >= 50) {
            $update['progress_class'] = 'progress-medium';
        } elseif ($update['cumulative_completion_percentage'] >= 25) {
            $update['progress_class'] = 'progress-low';
        } else {
            $update['progress_class'] = 'progress-minimal';
        }

        // Add time ago
        $update['time_ago'] = timeAgo($update['created_at']);

        // Format contractor name
        $update['contractor_name'] = trim($update['contractor_first_name'] . ' ' . $update['contractor_last_name']);

        // Format worker types
        $update['worker_types_array'] = $update['worker_types'] ? explode(',', $update['worker_types']) : [];
        
        // Add location verification status
        $update['location_status'] = $update['location_verified'] ? 'Verified' : 'Not Verified';
        $update['location_class'] = $update['location_verified'] ? 'badge-success' : 'badge-warning';

        // Clean up sensitive data
        unset($update['progress_photos']);
    }

    // Get geo photos for the homeowner's projects
    $geoPhotosStmt = $db->prepare("
        SELECT 
            gp.*,
            dpu.update_date,
            dpu.construction_stage
        FROM geo_photos gp
        LEFT JOIN daily_progress_updates dpu ON gp.project_id = dpu.project_id 
            AND DATE(gp.photo_timestamp) = dpu.update_date
        WHERE gp.homeowner_id = :homeowner_id
        ORDER BY gp.photo_timestamp DESC
        LIMIT 20
    ");
    $geoPhotosStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $geoPhotosStmt->execute();
    $geoPhotos = $geoPhotosStmt->fetchAll(PDO::FETCH_ASSOC);

    // Process geo photos
    foreach ($geoPhotos as &$photo) {
        $photo['photo_url'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/buildhub/backend' . str_replace('../../', '/', $photo['file_path']);
        $photo['location_data'] = json_decode($photo['location_data'], true) ?: [];
        $photo['timestamp_formatted'] = date('M j, Y g:i A', strtotime($photo['photo_timestamp']));
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

    // Get projects summary for homeowner
    $projectsStmt = $db->prepare("
        SELECT 
            dpu.project_id,
            u_contractor.first_name as contractor_first_name,
            u_contractor.last_name as contractor_last_name,
            u_contractor.company_name,
            COUNT(dpu.id) as total_updates,
            MAX(dpu.cumulative_completion_percentage) as latest_progress,
            MAX(dpu.update_date) as last_update_date,
            MAX(dpu.created_at) as last_update_time,
            COUNT(DISTINCT dpu.construction_stage) as stages_worked,
            AVG(dpu.incremental_completion_percentage) as avg_daily_progress
        FROM daily_progress_updates dpu
        LEFT JOIN users u_contractor ON dpu.contractor_id = u_contractor.id
        WHERE dpu.homeowner_id = :homeowner_id
        GROUP BY dpu.project_id, dpu.contractor_id
        ORDER BY last_update_time DESC
    ");
    
    $projectsStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
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
        $project['contractor_name'] = trim($project['contractor_first_name'] . ' ' . $project['contractor_last_name']);
        $project['avg_daily_progress'] = round($project['avg_daily_progress'] ?: 0, 2);
    }

    // Get unread notifications count
    $unreadStmt = $db->prepare("
        SELECT COUNT(*) as unread_count 
        FROM enhanced_progress_notifications 
        WHERE homeowner_id = :homeowner_id AND status = 'unread'
    ");
    $unreadStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $unreadStmt->execute();
    $unreadCount = $unreadStmt->fetch(PDO::FETCH_ASSOC)['unread_count'];

    echo json_encode([
        'success' => true,
        'data' => [
            'progress_updates' => $progress_updates,
            'geo_photos' => $geoPhotos,
            'projects' => $projects,
            'pagination' => [
                'total' => (int)$totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ],
            'unread_notifications' => (int)$unreadCount,
            'summary' => [
                'total_updates' => (int)$totalCount,
                'total_projects' => count($projects),
                'total_geo_photos' => count($geoPhotos)
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("Get homeowner progress updates error: " . $e->getMessage());
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