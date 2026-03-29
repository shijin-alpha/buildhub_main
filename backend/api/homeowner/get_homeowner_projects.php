<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    // Allow override for testing/admin purposes
    if (isset($_GET['homeowner_id']) && is_numeric($_GET['homeowner_id'])) {
        $homeowner_id = (int)$_GET['homeowner_id'];
    }
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }
    
    // Get homeowner's projects from multiple sources
    $projects = [];
    
    // 1. Get projects from construction_projects table
    $stmt = $db->prepare("
        SELECT 
            cp.id,
            cp.project_name,
            cp.project_location,
            cp.total_cost,
            cp.start_date,
            cp.expected_completion_date,
            cp.actual_completion_date,
            cp.status as project_status,
            cp.contractor_id,
            cp.created_at,
            CONCAT(u.first_name, ' ', u.last_name) as contractor_name,
            u.email as contractor_email,
            u.phone as contractor_phone,
            'construction_project' as project_type
        FROM construction_projects cp
        LEFT JOIN users u ON cp.contractor_id = u.id
        WHERE cp.homeowner_id = ?
        ORDER BY cp.created_at DESC
    ");
    $stmt->execute([$homeowner_id]);
    $construction_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Get projects from contractor_send_estimates table (estimates that became projects)
    $stmt = $db->prepare("
        SELECT 
            cse.id,
            JSON_UNQUOTE(JSON_EXTRACT(cse.structured, '$.project_name')) as project_name,
            JSON_UNQUOTE(JSON_EXTRACT(cse.structured, '$.project_address')) as project_location,
            cse.total_cost,
            cse.created_at as start_date,
            NULL as expected_completion_date,
            NULL as actual_completion_date,
            cse.status as project_status,
            cse.contractor_id,
            cse.created_at,
            CONCAT(u.first_name, ' ', u.last_name) as contractor_name,
            u.email as contractor_email,
            u.phone as contractor_phone,
            'estimate_project' as project_type
        FROM contractor_send_estimates cse
        LEFT JOIN users u ON cse.contractor_id = u.id
        WHERE cse.homeowner_id = ?
        AND cse.status IN ('approved', 'in_progress', 'completed')
        AND cse.id NOT IN (
            SELECT DISTINCT estimate_id 
            FROM construction_projects 
            WHERE estimate_id IS NOT NULL
        )
        ORDER BY cse.created_at DESC
    ");
    $stmt->execute([$homeowner_id]);
    $estimate_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine and format projects
    $all_projects = array_merge($construction_projects, $estimate_projects);
    
    // Format project data
    foreach ($all_projects as &$project) {
        // Format dates
        if ($project['start_date']) {
            $project['start_date_formatted'] = date('M j, Y', strtotime($project['start_date']));
        }
        if ($project['expected_completion_date']) {
            $project['expected_completion_date_formatted'] = date('M j, Y', strtotime($project['expected_completion_date']));
        }
        if ($project['actual_completion_date']) {
            $project['actual_completion_date_formatted'] = date('M j, Y', strtotime($project['actual_completion_date']));
        }
        
        // Format cost
        if ($project['total_cost']) {
            $project['total_cost_formatted'] = '₹' . number_format($project['total_cost'], 2);
        }
        
        // Set default project name if empty
        if (empty($project['project_name'])) {
            $project['project_name'] = 'Project ' . $project['id'];
        }
        
        // Set default location if empty
        if (empty($project['project_location'])) {
            $project['project_location'] = 'Location not specified';
        }
        
        // Add project status badge
        $status_badges = [
            'pending' => ['icon' => '⏳', 'text' => 'Pending', 'color' => '#ffc107'],
            'approved' => ['icon' => '✅', 'text' => 'Approved', 'color' => '#28a745'],
            'in_progress' => ['icon' => '🚧', 'text' => 'In Progress', 'color' => '#007bff'],
            'completed' => ['icon' => '🎉', 'text' => 'Completed', 'color' => '#28a745'],
            'cancelled' => ['icon' => '❌', 'text' => 'Cancelled', 'color' => '#dc3545'],
            'on_hold' => ['icon' => '⏸️', 'text' => 'On Hold', 'color' => '#6c757d']
        ];
        
        $project['status_badge'] = $status_badges[$project['project_status']] ?? 
            ['icon' => '❓', 'text' => ucfirst($project['project_status']), 'color' => '#6c757d'];
    }
    
    // Get document counts for each project
    foreach ($all_projects as &$project) {
        $doc_stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_documents,
                SUM(CASE WHEN verification_status = 'approved' THEN 1 ELSE 0 END) as approved_documents,
                SUM(CASE WHEN verification_status = 'pending' THEN 1 ELSE 0 END) as pending_documents,
                SUM(CASE WHEN verification_status = 'rejected' THEN 1 ELSE 0 END) as rejected_documents
            FROM contractor_stage_documents 
            WHERE project_id = ?
        ");
        $doc_stmt->execute([$project['id']]);
        $doc_stats = $doc_stmt->fetch(PDO::FETCH_ASSOC);
        
        $project['document_stats'] = [
            'total_documents' => (int)$doc_stats['total_documents'],
            'approved_documents' => (int)$doc_stats['approved_documents'],
            'pending_documents' => (int)$doc_stats['pending_documents'],
            'rejected_documents' => (int)$doc_stats['rejected_documents']
        ];
    }
    
    // Calculate summary statistics
    $total_projects = count($all_projects);
    $active_projects = count(array_filter($all_projects, function($p) {
        return in_array($p['project_status'], ['approved', 'in_progress']);
    }));
    $completed_projects = count(array_filter($all_projects, function($p) {
        return $p['project_status'] === 'completed';
    }));
    
    $total_documents = array_sum(array_column($all_projects, 'document_stats'));
    $total_documents = array_sum(array_column($total_documents, 'total_documents'));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'projects' => $all_projects,
            'statistics' => [
                'total_projects' => $total_projects,
                'active_projects' => $active_projects,
                'completed_projects' => $completed_projects,
                'total_documents' => $total_documents
            ]
        ],
        'message' => "Found {$total_projects} project(s) with {$total_documents} document(s)"
    ]);
    
} catch (Exception $e) {
    error_log("Homeowner Projects API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching projects: ' . $e->getMessage()
    ]);
}
?>