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

    // Get contractor_id from query parameter
    $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    
    if ($contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing contractor_id']);
        exit;
    }

    // Get all projects for this contractor
    $stmt = $db->prepare("
        SELECT 
            cp.*,
            -- Progress information
            COALESCE(
                (SELECT AVG(completion_percentage) 
                 FROM construction_progress_updates cpu 
                 WHERE cpu.project_id = cp.id), 
                0
            ) as avg_progress,
            
            -- Latest progress update
            (SELECT created_at 
             FROM construction_progress_updates cpu 
             WHERE cpu.project_id = cp.id 
             ORDER BY created_at DESC 
             LIMIT 1
            ) as last_progress_update,
            
            -- Count of progress updates
            (SELECT COUNT(*) 
             FROM construction_progress_updates cpu 
             WHERE cpu.project_id = cp.id
            ) as total_progress_updates,
            
            -- Latest stage
            (SELECT stage_name 
             FROM construction_progress_updates cpu 
             WHERE cpu.project_id = cp.id 
             ORDER BY created_at DESC 
             LIMIT 1
            ) as current_stage_name
            
        FROM construction_projects cp
        WHERE cp.contractor_id = :contractor_id
        ORDER BY cp.created_at DESC
    ");

    $stmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process each project
    foreach ($projects as &$project) {
        // Format dates
        $project['created_date_formatted'] = date('M j, Y', strtotime($project['created_at']));
        $project['start_date_formatted'] = $project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : 'Not started';
        $project['expected_completion_formatted'] = $project['expected_completion_date'] ? date('M j, Y', strtotime($project['expected_completion_date'])) : 'Not set';
        $project['last_update_formatted'] = $project['last_progress_update'] ? date('M j, Y g:i A', strtotime($project['last_progress_update'])) : 'No updates yet';

        // Parse structured data
        if (!empty($project['structured_data'])) {
            $project['structured'] = json_decode($project['structured_data'], true);
        } else {
            $project['structured'] = null;
        }

        // Parse layout images
        if (!empty($project['layout_images'])) {
            $project['layout_data'] = json_decode($project['layout_images'], true);
        } else {
            $project['layout_data'] = null;
        }

        // Calculate progress status
        $progress = (float)$project['avg_progress'];
        if ($progress == 0) {
            $project['progress_status'] = 'not_started';
            $project['progress_status_class'] = 'badge-secondary';
        } elseif ($progress < 25) {
            $project['progress_status'] = 'just_started';
            $project['progress_status_class'] = 'badge-info';
        } elseif ($progress < 50) {
            $project['progress_status'] = 'early_progress';
            $project['progress_status_class'] = 'badge-warning';
        } elseif ($progress < 75) {
            $project['progress_status'] = 'mid_progress';
            $project['progress_status_class'] = 'badge-primary';
        } elseif ($progress < 100) {
            $project['progress_status'] = 'near_completion';
            $project['progress_status_class'] = 'badge-success';
        } else {
            $project['progress_status'] = 'completed';
            $project['progress_status_class'] = 'badge-success';
        }

        // Create project summary for display
        $project['project_summary'] = [
            'name' => $project['project_name'],
            'homeowner' => $project['homeowner_name'],
            'location' => $project['project_location'] ?: 'Location not specified',
            'cost' => $project['total_cost'] ? '₹' . number_format($project['total_cost'], 2) : 'Cost not specified',
            'timeline' => $project['timeline'] ?: 'Timeline not specified',
            'progress' => number_format($project['avg_progress'], 1) . '%',
            'status' => ucfirst(str_replace('_', ' ', $project['status'])),
            'current_stage' => $project['current_stage_name'] ?: $project['current_stage'],
            'updates_count' => (int)$project['total_progress_updates'],
            'last_activity' => $project['last_update_formatted']
        ];

        // Add technical details summary
        if ($project['structured']) {
            $project['technical_summary'] = [
                'materials_cost' => isset($project['structured']['totals']['materials_total']) ? 
                    '₹' . number_format($project['structured']['totals']['materials_total'], 2) : 'Not specified',
                'labor_cost' => isset($project['structured']['totals']['labor_total']) ? 
                    '₹' . number_format($project['structured']['totals']['labor_total'], 2) : 'Not specified',
                'total_cost' => isset($project['structured']['totals']['grand_total']) ? 
                    '₹' . number_format($project['structured']['totals']['grand_total'], 2) : 
                    ($project['total_cost'] ? '₹' . number_format($project['total_cost'], 2) : 'Not specified')
            ];
        } else {
            $project['technical_summary'] = [
                'materials_cost' => 'Not specified',
                'labor_cost' => 'Not specified',
                'total_cost' => $project['total_cost'] ? '₹' . number_format($project['total_cost'], 2) : 'Not specified'
            ];
        }
    }

    // Get overall statistics
    $statsStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_projects,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_projects,
            COUNT(CASE WHEN status IN ('created', 'in_progress') THEN 1 END) as active_projects,
            COUNT(CASE WHEN status = 'created' THEN 1 END) as not_started_projects,
            AVG(completion_percentage) as avg_progress
        FROM construction_projects
        WHERE contractor_id = :contractor_id
    ");
    
    $statsStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'projects' => $projects,
            'statistics' => [
                'total_projects' => (int)$stats['total_projects'],
                'completed_projects' => (int)$stats['completed_projects'],
                'active_projects' => (int)$stats['active_projects'],
                'not_started_projects' => (int)$stats['not_started_projects'],
                'average_progress' => round((float)$stats['avg_progress'], 1)
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("Get contractor projects error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>