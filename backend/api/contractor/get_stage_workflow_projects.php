<?php
/**
 * Get contractor's projects with stage workflow information
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in and is a contractor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['contractor_id']) || empty($_GET['contractor_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Contractor ID is required']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $contractor_id = (int)$_GET['contractor_id'];
    
    // Verify contractor access
    if ($_SESSION['user_id'] != $contractor_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Get contractor's projects with stage workflow information
    $projects_query = "SELECT 
                         cp.id,
                         cp.project_name,
                         cp.project_description,
                         cp.project_location,
                         cp.status as project_status,
                         cp.current_stage,
                         cp.completion_percentage,
                         cp.start_date,
                         cp.expected_completion_date,
                         cp.created_at,
                         u_homeowner.first_name as homeowner_first_name,
                         u_homeowner.last_name as homeowner_last_name,
                         u_homeowner.email as homeowner_email,
                         COUNT(csw.id) as total_stages,
                         COUNT(CASE WHEN csw.contractor_status = 'completed' THEN 1 END) as completed_stages,
                         COUNT(CASE WHEN csw.contractor_status = 'submitted_for_inspection' THEN 1 END) as pending_inspection,
                         COUNT(CASE WHEN csw.contractor_status = 'in_progress' THEN 1 END) as in_progress_stages,
                         MIN(CASE WHEN csw.contractor_status = 'not_started' THEN csw.stage_order END) as next_stage_order,
                         (SELECT stage_name FROM construction_stage_workflow 
                          WHERE project_id = cp.id AND contractor_id = cp.contractor_id 
                          AND stage_order = MIN(CASE WHEN csw.contractor_status = 'not_started' THEN csw.stage_order END)
                          LIMIT 1) as next_stage_name
                       FROM construction_projects cp
                       JOIN users u_homeowner ON cp.homeowner_id = u_homeowner.id
                       LEFT JOIN construction_stage_workflow csw ON cp.id = csw.project_id AND csw.contractor_id = cp.contractor_id
                       WHERE cp.contractor_id = ? AND cp.status IN ('created', 'in_progress')
                       GROUP BY cp.id, cp.project_name, cp.project_description, cp.project_location, 
                                cp.status, cp.current_stage, cp.completion_percentage, cp.start_date, 
                                cp.expected_completion_date, cp.created_at, u_homeowner.first_name, 
                                u_homeowner.last_name, u_homeowner.email
                       ORDER BY cp.created_at DESC";
    
    $projects_stmt = $db->prepare($projects_query);
    $projects_stmt->execute([$contractor_id]);
    $projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize stage workflow for projects that don't have it
    foreach ($projects as $project) {
        $check_workflow = $db->prepare("SELECT COUNT(*) FROM construction_stage_workflow WHERE project_id = ? AND contractor_id = ?");
        $check_workflow->execute([$project['id'], $contractor_id]);
        $workflow_exists = $check_workflow->fetchColumn();
        
        if ($workflow_exists == 0) {
            // Initialize stage workflow for this project
            $init_workflow = $db->prepare("
                INSERT INTO construction_stage_workflow (project_id, stage_name, stage_order, contractor_id, contractor_status)
                SELECT ?, stage_name, stage_order, ?, 'not_started'
                FROM construction_stage_payments
                ORDER BY stage_order
            ");
            $init_workflow->execute([$project['id'], $contractor_id]);
        }
    }
    
    // Get updated project data after initialization
    $projects_stmt->execute([$contractor_id]);
    $projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent submissions for each project
    foreach ($projects as &$project) {
        $submissions_query = "SELECT 
                                css.id,
                                css.stage_name,
                                css.submission_type,
                                css.completion_percentage,
                                css.submitted_at,
                                css.status
                              FROM contractor_stage_submissions css
                              WHERE css.project_id = ? AND css.contractor_id = ?
                              ORDER BY css.submitted_at DESC
                              LIMIT 5";
        
        $submissions_stmt = $db->prepare($submissions_query);
        $submissions_stmt->execute([$project['id'], $contractor_id]);
        $project['recent_submissions'] = $submissions_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate overall completion percentage based on stage workflow
        $completion_query = "SELECT AVG(stage_completion_percentage) as avg_completion
                           FROM construction_stage_workflow
                           WHERE project_id = ? AND contractor_id = ?";
        $completion_stmt = $db->prepare($completion_query);
        $completion_stmt->execute([$project['id'], $contractor_id]);
        $completion_result = $completion_stmt->fetch(PDO::FETCH_ASSOC);
        $project['workflow_completion_percentage'] = round($completion_result['avg_completion'] ?? 0, 2);
    }
    
    // Get contractor statistics
    $stats_query = "SELECT 
                      COUNT(DISTINCT cp.id) as total_projects,
                      COUNT(DISTINCT CASE WHEN cp.status = 'in_progress' THEN cp.id END) as active_projects,
                      COUNT(DISTINCT CASE WHEN cp.status = 'completed' THEN cp.id END) as completed_projects,
                      COUNT(CASE WHEN csw.contractor_status = 'submitted_for_inspection' THEN 1 END) as pending_inspections,
                      COUNT(CASE WHEN csw.contractor_status = 'in_progress' THEN 1 END) as active_stages
                    FROM construction_projects cp
                    LEFT JOIN construction_stage_workflow csw ON cp.id = csw.project_id AND csw.contractor_id = cp.contractor_id
                    WHERE cp.contractor_id = ?";
    
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([$contractor_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'projects' => $projects,
        'stats' => $stats
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_stage_workflow_projects.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in get_stage_workflow_projects.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>