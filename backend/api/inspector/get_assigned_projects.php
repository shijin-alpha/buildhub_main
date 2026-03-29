<?php
/**
 * Get Projects Assigned to Inspector
 * Returns list of construction projects assigned to the current inspector
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // For now, use a default inspector ID (1001) - in production this should come from session
    $inspector_id = $_GET['inspector_id'] ?? 1001;
    
    // Get assigned projects with detailed information
    $query = "SELECT 
                cp.id as project_id,
                cp.project_name,
                cp.project_description,
                cp.status as project_status,
                cp.current_stage,
                cp.completion_percentage,
                cp.project_location,
                cp.start_date,
                cp.expected_completion_date,
                cp.total_cost,
                sia.assigned_date,
                sia.notes as assignment_notes,
                sia.status as assignment_status,
                CONCAT(u_homeowner.first_name, ' ', u_homeowner.last_name) as homeowner_first_name,
                u_homeowner.last_name as homeowner_last_name,
                u_homeowner.email as homeowner_email,
                u_homeowner.phone as homeowner_phone,
                CONCAT(u_contractor.first_name, ' ', u_contractor.last_name) as contractor_first_name,
                u_contractor.last_name as contractor_last_name,
                u_contractor.email as contractor_email,
                u_contractor.phone as contractor_phone,
                (SELECT COUNT(*) FROM inspection_reports ir WHERE ir.project_id = cp.id AND ir.inspector_id = ?) as total_inspections,
                (SELECT COUNT(*) FROM inspection_reports ir WHERE ir.project_id = cp.id AND ir.inspector_id = ? AND ir.overall_status = 'pending') as pending_inspections,
                (SELECT MAX(ir.inspection_date) FROM inspection_reports ir WHERE ir.project_id = cp.id AND ir.inspector_id = ?) as last_inspection_date
              FROM site_inspector_assignments sia
              JOIN construction_projects cp ON sia.project_id = cp.id
              JOIN users u_homeowner ON cp.homeowner_id = u_homeowner.id
              JOIN users u_contractor ON cp.contractor_id = u_contractor.id
              WHERE sia.inspector_id = ? AND sia.status = 'active'
              ORDER BY sia.assigned_date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$inspector_id, $inspector_id, $inspector_id, $inspector_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics for inspector
    $stats_query = "SELECT 
                      COUNT(*) as total_assigned,
                      SUM(CASE WHEN cp.status = 'in_progress' THEN 1 ELSE 0 END) as active_projects,
                      SUM(CASE WHEN cp.status = 'completed' THEN 1 ELSE 0 END) as completed_projects,
                      (SELECT COUNT(*) FROM inspection_reports ir 
                       JOIN site_inspector_assignments sia2 ON ir.project_id = sia2.project_id 
                       WHERE sia2.inspector_id = ? AND ir.overall_status = 'pending') as pending_inspections_total,
                      (SELECT COUNT(*) FROM inspection_reports ir 
                       JOIN site_inspector_assignments sia3 ON ir.project_id = sia3.project_id 
                       WHERE sia3.inspector_id = ? AND DATE(ir.created_at) = CURDATE()) as today_inspections
                    FROM site_inspector_assignments sia
                    JOIN construction_projects cp ON sia.project_id = cp.id
                    WHERE sia.inspector_id = ? AND sia.status = 'active'";
    
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([$inspector_id, $inspector_id, $inspector_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'projects' => $projects,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching assigned projects: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch assigned projects'
    ]);
}
?>