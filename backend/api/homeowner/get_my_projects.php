<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get homeowner ID from session
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }
    
    // Get all projects for this homeowner (approved layout requests with selected contractors)
    $query = "SELECT lr.*, d.design_title, d.architect_id,
                     CONCAT(arch.first_name, ' ', arch.last_name) as architect_name,
                     p.contractor_id, p.estimated_cost, p.timeline as contractor_timeline,
                     CONCAT(cont.first_name, ' ', cont.last_name) as contractor_name,
                     'in-progress' as status,
                     50 as progress,
                     lr.created_at as start_date
              FROM layout_requests lr
              JOIN designs d ON lr.id = d.layout_request_id AND d.status = 'approved'
              JOIN users arch ON d.architect_id = arch.id
              LEFT JOIN proposals p ON lr.id = p.layout_request_id AND p.status = 'accepted'
              LEFT JOIN users cont ON p.contractor_id = cont.id
              WHERE lr.user_id = :homeowner_id 
              AND lr.status = 'approved'
              ORDER BY lr.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':homeowner_id', $homeowner_id);
    $stmt->execute();
    
    $projects = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $projects[] = [
            'id' => $row['id'],
            'project_name' => $row['design_title'] ?? 'Construction Project',
            'plot_size' => $row['plot_size'],
            'budget_range' => $row['budget_range'],
            'architect_name' => $row['architect_name'],
            'contractor_name' => $row['contractor_name'] ?? 'Not assigned',
            'estimated_cost' => $row['estimated_cost'],
            'status' => $row['status'],
            'progress' => $row['progress'],
            'start_date' => $row['start_date'],
            'timeline' => $row['contractor_timeline'] ?? $row['timeline']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'projects' => $projects
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching projects: ' . $e->getMessage()
    ]);
}
?>