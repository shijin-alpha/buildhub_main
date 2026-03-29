<?php
/**
 * Get inspection history for site inspector
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in and is a site inspector
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'site_inspector') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $inspector_id = $_SESSION['user_id'];
    
    // Get filter parameters
    $project_id = $_GET['project_id'] ?? null;
    $status_filter = $_GET['status'] ?? 'all';
    $date_from = $_GET['date_from'] ?? null;
    $date_to = $_GET['date_to'] ?? null;
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    
    // Build query conditions
    $conditions = ["ir.inspector_id = ?"];
    $params = [$inspector_id];
    
    if ($project_id) {
        $conditions[] = "ir.project_id = ?";
        $params[] = $project_id;
    }
    
    if ($status_filter !== 'all') {
        $conditions[] = "ir.overall_status = ?";
        $params[] = $status_filter;
    }
    
    if ($date_from) {
        $conditions[] = "ir.inspection_date >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $conditions[] = "ir.inspection_date <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = implode(' AND ', $conditions);
    
    // Get inspection reports with project details
    $query = "SELECT 
                ir.*,
                cp.project_name,
                cp.project_location,
                cp.current_stage as project_current_stage,
                u_homeowner.first_name as homeowner_first_name,
                u_homeowner.last_name as homeowner_last_name,
                u_contractor.first_name as contractor_first_name,
                u_contractor.last_name as contractor_last_name,
                (SELECT COUNT(*) FROM inspection_photos ip WHERE ip.inspection_report_id = ir.id) as photo_count,
                (SELECT COUNT(*) FROM inspection_checklist_items ici WHERE ici.inspection_report_id = ir.id) as checklist_count
              FROM inspection_reports ir
              JOIN construction_projects cp ON ir.project_id = cp.id
              JOIN users u_homeowner ON cp.homeowner_id = u_homeowner.id
              JOIN users u_contractor ON cp.contractor_id = u_contractor.id
              WHERE $where_clause
              ORDER BY ir.inspection_date DESC, ir.created_at DESC
              LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) 
                    FROM inspection_reports ir
                    JOIN construction_projects cp ON ir.project_id = cp.id
                    WHERE $where_clause";
    
    $count_params = array_slice($params, 0, -2); // Remove limit and offset
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute($count_params);
    $total_count = $count_stmt->fetchColumn();
    
    // Get summary statistics
    $stats_query = "SELECT 
                      COUNT(*) as total_inspections,
                      SUM(CASE WHEN ir.overall_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                      SUM(CASE WHEN ir.overall_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                      SUM(CASE WHEN ir.overall_status = 'needs_attention' THEN 1 ELSE 0 END) as attention_count,
                      SUM(CASE WHEN ir.overall_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                      AVG(ir.quality_score) as avg_quality_score,
                      SUM(CASE WHEN DATE(ir.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as this_week_count,
                      SUM(CASE WHEN DATE(ir.created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as this_month_count
                    FROM inspection_reports ir
                    JOIN construction_projects cp ON ir.project_id = cp.id
                    JOIN site_inspector_assignments sia ON cp.id = sia.project_id
                    WHERE sia.inspector_id = ? AND sia.status = 'active'";
    
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([$inspector_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format the response
    foreach ($inspections as &$inspection) {
        $inspection['homeowner_name'] = $inspection['homeowner_first_name'] . ' ' . $inspection['homeowner_last_name'];
        $inspection['contractor_name'] = $inspection['contractor_first_name'] . ' ' . $inspection['contractor_last_name'];
        unset($inspection['homeowner_first_name'], $inspection['homeowner_last_name']);
        unset($inspection['contractor_first_name'], $inspection['contractor_last_name']);
    }
    
    echo json_encode([
        'success' => true,
        'inspections' => $inspections,
        'total_count' => intval($total_count),
        'stats' => $stats,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_count
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching inspection history: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch inspection history'
    ]);
}
?>