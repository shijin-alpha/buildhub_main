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
    $database = new Database();
    $db = $database->getConnection();

    // Get parameters
    $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    if ($contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing contractor_id']);
        exit;
    }

    // Build query based on parameters
    $whereClause = "WHERE cpu.contractor_id = :contractor_id";
    $params = [':contractor_id' => $contractor_id];

    if ($project_id > 0) {
        $whereClause .= " AND cpu.project_id = :project_id";
        $params[':project_id'] = $project_id;
    }

    // Get progress updates with project details
    $stmt = $db->prepare("
        SELECT 
            cpu.*,
            cse.total_cost,
            cse.timeline,
            cse.materials,
            cse.structured,
            cls.homeowner_id as cse_homeowner_id,
            u_homeowner.first_name as homeowner_first_name,
            u_homeowner.last_name as homeowner_last_name,
            u_homeowner.email as homeowner_email
        FROM construction_progress_updates cpu
        LEFT JOIN contractor_send_estimates cse ON cpu.project_id = cse.id
        LEFT JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        LEFT JOIN users u_homeowner ON COALESCE(cpu.homeowner_id, cls.homeowner_id) = u_homeowner.id
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
        
        // Extract project details from structured JSON if available
        if (!empty($update['structured'])) {
            $structured = json_decode($update['structured'], true);
            if ($structured) {
                $update['plot_size'] = $structured['plot_size'] ?? null;
                $update['budget_range'] = $structured['budget_range'] ?? null;
                $update['project_requirements'] = $structured['requirements'] ?? null;
            }
        }

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

        // Clean up sensitive data
        unset($update['photo_paths']);
        unset($update['structured']); // Remove raw JSON from response
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

    // Get project summary if project_id is specified
    $project_summary = null;
    if ($project_id > 0) {
        $summaryStmt = $db->prepare("
            SELECT 
                cse.id,
                cse.total_cost,
                cse.timeline,
                cse.materials,
                cse.structured,
                cse.status as project_status,
                cls.homeowner_id,
                u_homeowner.first_name as homeowner_first_name,
                u_homeowner.last_name as homeowner_last_name,
                COUNT(cpu.id) as total_updates,
                MAX(cpu.completion_percentage) as latest_progress,
                MAX(cpu.created_at) as last_update
            FROM contractor_send_estimates cse
            LEFT JOIN contractor_layout_sends cls ON cse.send_id = cls.id
            LEFT JOIN users u_homeowner ON cls.homeowner_id = u_homeowner.id
            LEFT JOIN construction_progress_updates cpu ON cse.id = cpu.project_id
            WHERE cse.id = :project_id AND cse.contractor_id = :contractor_id
            GROUP BY cse.id
        ");
        
        $summaryStmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
        $summaryStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
        $summaryStmt->execute();
        $project_summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

        if ($project_summary) {
            // Extract project details from structured JSON
            if (!empty($project_summary['structured'])) {
                $structured = json_decode($project_summary['structured'], true);
                if ($structured) {
                    $project_summary['plot_size'] = $structured['plot_size'] ?? null;
                    $project_summary['budget_range'] = $structured['budget_range'] ?? null;
                    $project_summary['requirements'] = $structured['requirements'] ?? null;
                }
            }
            
            $project_summary['last_update_formatted'] = $project_summary['last_update'] 
                ? date('M j, Y g:i A', strtotime($project_summary['last_update']))
                : 'No updates yet';
                
            // Clean up
            unset($project_summary['structured']);
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