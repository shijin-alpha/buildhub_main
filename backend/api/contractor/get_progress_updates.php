<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

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
    // Start session to get user data
    session_start();
    
    $database = new Database();
    $db = $database->getConnection();

    // Get contractor ID from session or parameter
    $contractor_id = 0;
    if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'contractor') {
        $contractor_id = (int)$_SESSION['user_id'];
    } else if (isset($_GET['contractor_id'])) {
        $contractor_id = (int)$_GET['contractor_id'];
    }
    
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    if ($contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing contractor_id or invalid session']);
        exit;
    }

    // Build query based on parameters
    $whereClause = "WHERE dpu.contractor_id = :contractor_id";
    $params = [':contractor_id' => $contractor_id];

    if ($project_id > 0) {
        $whereClause .= " AND dpu.project_id = :project_id";
        $params[':project_id'] = $project_id;
    }

    // Get progress updates with project details
    $stmt = $db->prepare("
        SELECT 
            dpu.*,
            u_homeowner.first_name as homeowner_first_name,
            u_homeowner.last_name as homeowner_last_name,
            u_homeowner.email as homeowner_email
        FROM daily_progress_updates dpu
        LEFT JOIN users u_homeowner ON dpu.homeowner_id = u_homeowner.id
        {$whereClause}
        ORDER BY dpu.created_at DESC
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
        $update['photos'] = json_decode($update['progress_photos'], true) ?: [];
        
        // Add full URLs for photos
        $update['photo_urls'] = array_map(function($path) {
            return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/buildhub/backend' . $path;
        }, $update['photos']);
        
        // Format dates
        $update['created_at_formatted'] = date('M j, Y g:i A', strtotime($update['created_at']));
        $update['updated_at_formatted'] = date('M j, Y g:i A', strtotime($update['updated_at']));

        // Add progress status badge class based on completion percentage
        $completion = (float)$update['cumulative_completion_percentage'];
        if ($completion == 0) {
            $update['status_class'] = 'badge-secondary';
            $update['stage_status'] = 'Not Started';
        } elseif ($completion < 100) {
            $update['status_class'] = 'badge-warning';
            $update['stage_status'] = 'In Progress';
        } else {
            $update['status_class'] = 'badge-success';
            $update['stage_status'] = 'Completed';
        }

        // Clean up sensitive data
        unset($update['progress_photos']); // Remove raw JSON from response
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

    // Get project summary if project_id is specified
    $project_summary = null;
    if ($project_id > 0) {
        $summaryStmt = $db->prepare("
            SELECT 
                dpu.project_id,
                COUNT(dpu.id) as total_updates,
                MAX(dpu.cumulative_completion_percentage) as latest_progress,
                MAX(dpu.created_at) as last_update,
                u_homeowner.first_name as homeowner_first_name,
                u_homeowner.last_name as homeowner_last_name
            FROM daily_progress_updates dpu
            LEFT JOIN users u_homeowner ON dpu.homeowner_id = u_homeowner.id
            WHERE dpu.project_id = :project_id AND dpu.contractor_id = :contractor_id
            GROUP BY dpu.project_id
        ");
        
        $summaryStmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
        $summaryStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
        $summaryStmt->execute();
        $project_summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

        if ($project_summary) {
            $project_summary['last_update_formatted'] = $project_summary['last_update'] 
                ? date('M j, Y g:i A', strtotime($project_summary['last_update']))
                : 'No updates yet';
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'progress_updates' => $progress_updates,
            'pagination' => [
                'total' => (int)$totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ],
            'project_summary' => $project_summary
        ]
    ]);

} catch (Exception $e) {
    error_log("Get progress updates error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>