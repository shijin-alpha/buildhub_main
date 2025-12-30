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
    $whereClause = "WHERE cpu.homeowner_id = :homeowner_id";
    $params = [':homeowner_id' => $homeowner_id];

    if ($project_id > 0) {
        $whereClause .= " AND cpu.project_id = :project_id";
        $params[':project_id'] = $project_id;
    }

    // Get progress updates with contractor and project details
    $stmt = $db->prepare("
        SELECT 
            cpu.*,
            cse.total_cost,
            cse.timeline,
            cse.materials,
            u_contractor.first_name as contractor_first_name,
            u_contractor.last_name as contractor_last_name,
            u_contractor.email as contractor_email,
            u_contractor.phone as contractor_phone,
            lr.plot_size,
            lr.budget_range,
            lr.requirements as project_requirements
        FROM construction_progress_updates cpu
        LEFT JOIN contractor_send_estimates cse ON cpu.project_id = cse.id
        LEFT JOIN users u_contractor ON cpu.contractor_id = u_contractor.id
        LEFT JOIN layout_requests lr ON cse.layout_request_id = lr.id
        {$whereClause}
        ORDER BY cpu.created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $progress_updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process photo paths and add additional data
    foreach ($progress_updates as &$update) {
        // Decode photo paths
        $update['photos'] = json_decode($update['photo_paths'], true) ?: [];
        
        // Add full URLs for photos
        $update['photo_urls'] = array_map(function($path) {
            return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/buildhub/backend' . $path;
        }, $update['photos']);

        // Format dates
        $update['created_at_formatted'] = date('M j, Y g:i A', strtotime($update['created_at']));
        $update['updated_at_formatted'] = date('M j, Y g:i A', strtotime($update['updated_at']));

        // Add progress status badge class
        switch ($update['stage_status']) {
            case 'Not Started':
                $update['status_class'] = 'badge-secondary';
                break;
            case 'In Progress':
                $update['status_class'] = 'badge-warning';
                break;
            case 'Completed':
                $update['status_class'] = 'badge-success';
                break;
            default:
                $update['status_class'] = 'badge-secondary';
        }

        // Add completion percentage class
        if ($update['completion_percentage'] >= 100) {
            $update['progress_class'] = 'progress-complete';
        } elseif ($update['completion_percentage'] >= 75) {
            $update['progress_class'] = 'progress-high';
        } elseif ($update['completion_percentage'] >= 50) {
            $update['progress_class'] = 'progress-medium';
        } elseif ($update['completion_percentage'] >= 25) {
            $update['progress_class'] = 'progress-low';
        } else {
            $update['progress_class'] = 'progress-minimal';
        }

        // Add time ago
        $update['time_ago'] = timeAgo($update['created_at']);

        // Clean up sensitive data
        unset($update['photo_paths']);
    }

    // Get total count for pagination
    $countStmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM construction_progress_updates cpu 
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
            cse.id as project_id,
            cse.total_cost,
            cse.timeline,
            cse.status as project_status,
            u_contractor.first_name as contractor_first_name,
            u_contractor.last_name as contractor_last_name,
            lr.plot_size,
            lr.budget_range,
            COUNT(cpu.id) as total_updates,
            MAX(cpu.completion_percentage) as latest_progress,
            MAX(cpu.created_at) as last_update,
            SUM(CASE WHEN cpu.stage_status = 'Completed' THEN 1 ELSE 0 END) as completed_stages
        FROM contractor_send_estimates cse
        LEFT JOIN users u_contractor ON cse.contractor_id = u_contractor.id
        LEFT JOIN layout_requests lr ON cse.layout_request_id = lr.id
        LEFT JOIN construction_progress_updates cpu ON cse.id = cpu.project_id
        WHERE cse.homeowner_id = :homeowner_id
        GROUP BY cse.id
        ORDER BY cse.created_at DESC
    ");
    
    $projectsStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $projectsStmt->execute();
    $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format project data
    foreach ($projects as &$project) {
        $project['last_update_formatted'] = $project['last_update'] 
            ? date('M j, Y g:i A', strtotime($project['last_update']))
            : 'No updates yet';
        $project['latest_progress'] = $project['latest_progress'] ?: 0;
        $project['completed_stages'] = $project['completed_stages'] ?: 0;
    }

    // Get unread notifications count
    $unreadStmt = $db->prepare("
        SELECT COUNT(*) as unread_count 
        FROM progress_notifications 
        WHERE homeowner_id = :homeowner_id AND status = 'unread'
    ");
    $unreadStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $unreadStmt->execute();
    $unreadCount = $unreadStmt->fetch(PDO::FETCH_ASSOC)['unread_count'];

    echo json_encode([
        'success' => true,
        'data' => [
            'progress_updates' => $progress_updates,
            'projects' => $projects,
            'pagination' => [
                'total' => (int)$totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ],
            'unread_notifications' => (int)$unreadCount
        ]
    ]);

} catch (Exception $e) {
    error_log("Get homeowner progress updates error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
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