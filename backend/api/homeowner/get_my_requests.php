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
    
    // Get all layout requests by this homeowner
    $query = "SELECT lr.*, 
                     ll.title as selected_layout_title,
                     ll.layout_type as selected_layout_type,
                     ll.image_url as selected_layout_image,
                     COUNT(DISTINCT d.id) as design_count,
                     COUNT(DISTINCT cp.id) as proposal_count,
                     SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END) as accepted_count,
                     SUM(CASE WHEN a.status = 'declined' THEN 1 ELSE 0 END) as rejected_count,
                     SUM(CASE WHEN a.status = 'sent' THEN 1 ELSE 0 END) as sent_count
              FROM layout_requests lr 
              LEFT JOIN layout_library ll ON lr.selected_layout_id = ll.id
              LEFT JOIN designs d ON lr.id = d.layout_request_id
              LEFT JOIN contractor_proposals cp ON lr.id = cp.layout_request_id
              LEFT JOIN layout_request_assignments a ON a.layout_request_id = lr.id
              WHERE lr.user_id = :homeowner_id 
                AND (lr.status IS NULL OR lr.status <> 'deleted')
                AND (lr.timeline IS NULL OR lr.timeline <> 'contractor-direct')
              GROUP BY lr.id
              ORDER BY lr.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':homeowner_id', $homeowner_id);
    $stmt->execute();
    
    $requests = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $requests[] = [
            'id' => $row['id'],
            'plot_size' => $row['plot_size'],
            'building_size' => $row['building_size'] ?? null,
            'budget_range' => $row['budget_range'],
            'requirements' => $row['requirements'],
            // decode structured requirements if JSON
            'requirements_parsed' => json_decode($row['requirements'], true),
            'plot_shape' => $row['plot_shape'] ?? null,
            'topography' => $row['topography'] ?? null,
            'development_laws' => $row['development_laws'] ?? null,
            'family_needs' => $row['family_needs'] ?? null,
            'rooms' => $row['rooms'] ?? null,
            'aesthetic' => $row['aesthetic'] ?? null,
            'location' => $row['location'] ?? null,
            'timeline' => $row['timeline'] ?? null,
            'layout_type' => $row['layout_type'],
            'selected_layout_id' => $row['selected_layout_id'],
            'selected_layout_title' => $row['selected_layout_title'],
            'selected_layout_type' => $row['selected_layout_type'],
            'selected_layout_image' => $row['selected_layout_image'],
            'status' => $row['status'] ?? 'pending',
            'design_count' => (int)$row['design_count'],
            'proposal_count' => (int)$row['proposal_count'],
            'sent_count' => isset($row['sent_count']) ? (int)$row['sent_count'] : 0,
            'accepted_count' => isset($row['accepted_count']) ? (int)$row['accepted_count'] : 0,
            'rejected_count' => isset($row['rejected_count']) ? (int)$row['rejected_count'] : 0,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching requests: ' . $e->getMessage()
    ]);
}
?>