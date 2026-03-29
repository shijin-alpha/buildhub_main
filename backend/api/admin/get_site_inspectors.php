<?php
/**
 * Get list of available site inspectors (Admin function)
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

// Check if user is logged in and is admin
$isAdmin = false;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    $isAdmin = true;
} elseif (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $isAdmin = true;
}

if (!$isAdmin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all verified site inspectors
    $inspectors_query = "SELECT 
                           u.id,
                           u.first_name,
                           u.last_name,
                           u.email,
                           u.phone,
                           u.created_at,
                           u.updated_at,
                           COUNT(sia.id) as total_assignments,
                           COUNT(CASE WHEN sia.status = 'active' THEN 1 END) as active_assignments,
                           COUNT(CASE WHEN sia.status = 'completed' THEN 1 END) as completed_assignments,
                           MAX(sia.assigned_date) as last_assignment_date
                         FROM users u
                         LEFT JOIN site_inspector_assignments sia ON u.id = sia.inspector_id
                         WHERE u.role = 'site_inspector' AND u.is_verified = 1
                         GROUP BY u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at, u.updated_at
                         ORDER BY u.first_name ASC, u.last_name ASC";
    
    $inspectors_stmt = $db->prepare($inspectors_query);
    $inspectors_stmt->execute();
    $inspectors = $inspectors_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get projects that need inspector assignment
    $unassigned_projects_query = "SELECT 
                                    cp.id,
                                    cp.project_name,
                                    cp.status,
                                    cp.current_stage,
                                    cp.project_location,
                                    cp.created_at,
                                    CASE WHEN sia.id IS NULL THEN 1 ELSE 0 END as needs_inspector
                                  FROM construction_projects cp
                                  LEFT JOIN site_inspector_assignments sia ON cp.id = sia.project_id AND sia.status = 'active'
                                  WHERE cp.status IN ('created', 'in_progress')
                                  ORDER BY cp.created_at DESC";
    
    $projects_stmt = $db->prepare($unassigned_projects_query);
    $projects_stmt->execute();
    $projects = $projects_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get inspector assignments summary
    $assignments_query = "SELECT 
                            sia.id,
                            sia.inspector_id,
                            sia.project_id,
                            sia.assigned_date,
                            sia.status,
                            sia.notes,
                            u.first_name as inspector_first_name,
                            u.last_name as inspector_last_name,
                            cp.project_name,
                            cp.current_stage,
                            cp.status as project_status,
                            COUNT(ir.id) as inspection_count,
                            MAX(ir.inspection_date) as last_inspection_date
                          FROM site_inspector_assignments sia
                          JOIN users u ON sia.inspector_id = u.id
                          JOIN construction_projects cp ON sia.project_id = cp.id
                          LEFT JOIN inspection_reports ir ON sia.project_id = ir.project_id AND sia.inspector_id = ir.inspector_id
                          WHERE sia.status = 'active'
                          GROUP BY sia.id, sia.inspector_id, sia.project_id, sia.assigned_date, 
                                   sia.status, sia.notes, u.first_name, u.last_name, 
                                   cp.project_name, cp.current_stage, cp.status
                          ORDER BY sia.assigned_date DESC";
    
    $assignments_stmt = $db->prepare($assignments_query);
    $assignments_stmt->execute();
    $assignments = $assignments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $stats = [
        'total_inspectors' => count($inspectors),
        'active_inspectors' => count(array_filter($inspectors, function($i) { return $i['active_assignments'] > 0; })),
        'total_projects' => count($projects),
        'unassigned_projects' => count(array_filter($projects, function($p) { return $p['needs_inspector'] == 1; })),
        'active_assignments' => count($assignments),
        'total_inspections' => array_sum(array_column($assignments, 'inspection_count'))
    ];
    
    echo json_encode([
        'success' => true,
        'inspectors' => $inspectors,
        'projects' => $projects,
        'assignments' => $assignments,
        'stats' => $stats
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_site_inspectors.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in get_site_inspectors.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>