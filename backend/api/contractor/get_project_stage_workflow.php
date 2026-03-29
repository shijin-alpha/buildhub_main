<?php
/**
 * Get stage workflow for a specific project
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

if (!isset($_GET['project_id']) || empty($_GET['project_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Project ID is required']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $project_id = (int)$_GET['project_id'];
    $contractor_id = $_SESSION['user_id'];
    
    // Verify contractor has access to this project
    $access_check = $db->prepare("SELECT id FROM construction_projects WHERE id = ? AND contractor_id = ?");
    $access_check->execute([$project_id, $contractor_id]);
    
    if (!$access_check->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this project']);
        exit;
    }
    
    // Get stage workflow for the project
    $workflow_query = "SELECT 
                         csw.id,
                         csw.stage_name,
                         csw.stage_order,
                         csw.contractor_status,
                         csw.contractor_submitted_at,
                         csw.inspection_status,
                         csw.inspection_approved_at,
                         csw.inspector_id,
                         csw.homeowner_visible,
                         csw.stage_completion_percentage,
                         csw.created_at,
                         csw.updated_at,
                         u_inspector.first_name as inspector_first_name,
                         u_inspector.last_name as inspector_last_name,
                         csp.typical_percentage,
                         csp.description as stage_description,
                         (SELECT COUNT(*) FROM contractor_stage_submissions 
                          WHERE project_id = csw.project_id AND stage_name = csw.stage_name 
                          AND contractor_id = csw.contractor_id) as submission_count,
                         (SELECT MAX(submitted_at) FROM contractor_stage_submissions 
                          WHERE project_id = csw.project_id AND stage_name = csw.stage_name 
                          AND contractor_id = csw.contractor_id) as last_submission_date
                       FROM construction_stage_workflow csw
                       LEFT JOIN users u_inspector ON csw.inspector_id = u_inspector.id
                       LEFT JOIN construction_stage_payments csp ON csw.stage_name = csp.stage_name
                       WHERE csw.project_id = ? AND csw.contractor_id = ?
                       ORDER BY csw.stage_order ASC";
    
    $workflow_stmt = $db->prepare($workflow_query);
    $workflow_stmt->execute([$project_id, $contractor_id]);
    $stages = $workflow_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent submissions for each stage
    foreach ($stages as &$stage) {
        $submissions_query = "SELECT 
                                css.id,
                                css.submission_type,
                                css.work_description,
                                css.completion_percentage,
                                css.submitted_at,
                                css.status,
                                css.admin_notes
                              FROM contractor_stage_submissions css
                              WHERE css.project_id = ? AND css.stage_name = ? AND css.contractor_id = ?
                              ORDER BY css.submitted_at DESC
                              LIMIT 3";
        
        $submissions_stmt = $db->prepare($submissions_query);
        $submissions_stmt->execute([$project_id, $stage['stage_name'], $contractor_id]);
        $stage['recent_submissions'] = $submissions_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get inspection details if available
        if ($stage['inspection_status'] !== 'pending') {
            $inspection_query = "SELECT 
                                   sia.id,
                                   sia.inspection_date,
                                   sia.inspection_type,
                                   sia.approval_status,
                                   sia.quality_score,
                                   sia.safety_compliance,
                                   sia.inspection_notes,
                                   sia.defects_found,
                                   sia.recommendations,
                                   sia.required_corrections,
                                   sia.reinspection_required,
                                   sia.reinspection_date,
                                   sia.approved_at
                                 FROM stage_inspection_approvals sia
                                 WHERE sia.project_id = ? AND sia.stage_workflow_id = ?
                                 ORDER BY sia.inspection_date DESC
                                 LIMIT 1";
            
            $inspection_stmt = $db->prepare($inspection_query);
            $inspection_stmt->execute([$project_id, $stage['id']]);
            $stage['inspection_details'] = $inspection_stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    // Get project information
    $project_query = "SELECT 
                        cp.id,
                        cp.project_name,
                        cp.project_description,
                        cp.project_location,
                        cp.status,
                        cp.current_stage,
                        cp.completion_percentage,
                        cp.start_date,
                        cp.expected_completion_date,
                        u_homeowner.first_name as homeowner_first_name,
                        u_homeowner.last_name as homeowner_last_name,
                        u_homeowner.email as homeowner_email
                      FROM construction_projects cp
                      JOIN users u_homeowner ON cp.homeowner_id = u_homeowner.id
                      WHERE cp.id = ?";
    
    $project_stmt = $db->prepare($project_query);
    $project_stmt->execute([$project_id]);
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate workflow statistics
    $total_stages = count($stages);
    $completed_stages = count(array_filter($stages, function($s) { return $s['contractor_status'] === 'completed'; }));
    $pending_inspection = count(array_filter($stages, function($s) { return $s['contractor_status'] === 'submitted_for_inspection'; }));
    $in_progress = count(array_filter($stages, function($s) { return $s['contractor_status'] === 'in_progress'; }));
    $not_started = count(array_filter($stages, function($s) { return $s['contractor_status'] === 'not_started'; }));
    
    // Find current stage (first non-completed stage)
    $current_stage_index = 0;
    foreach ($stages as $index => $stage) {
        if ($stage['contractor_status'] !== 'completed') {
            $current_stage_index = $index;
            break;
        }
    }
    
    $workflow_stats = [
        'total_stages' => $total_stages,
        'completed_stages' => $completed_stages,
        'pending_inspection' => $pending_inspection,
        'in_progress' => $in_progress,
        'not_started' => $not_started,
        'current_stage_index' => $current_stage_index,
        'overall_completion' => $total_stages > 0 ? round(($completed_stages / $total_stages) * 100, 2) : 0
    ];
    
    echo json_encode([
        'success' => true,
        'project' => $project,
        'stages' => $stages,
        'workflow_stats' => $workflow_stats
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_project_stage_workflow.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in get_project_stage_workflow.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>