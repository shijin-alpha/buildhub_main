<?php
// Ensure clean JSON output
ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all approved layout requests, including assignment counters for sidebar badges
    $query = "SELECT lr.*, u.first_name, u.last_name, u.email, u.phone, u.address, u.city, u.state, u.zip_code,
                     CONCAT(u.first_name, ' ', u.last_name) as homeowner_name,
                     CONCAT(u.first_name, ' ', u.last_name) as client_name,
                     COUNT(DISTINCT d.id) as design_count,
                     COALESCE(SUM(CASE WHEN a.status = 'accepted' THEN 1 ELSE 0 END), 0) as accepted_count,
                     COALESCE(SUM(CASE WHEN a.status = 'declined' THEN 1 ELSE 0 END), 0) as rejected_count,
                     COALESCE(SUM(CASE WHEN a.status = 'sent' THEN 1 ELSE 0 END), 0) as sent_count
              FROM layout_requests lr 
              JOIN users u ON lr.user_id = u.id 
              LEFT JOIN designs d ON lr.id = d.layout_request_id
              LEFT JOIN layout_request_assignments a ON a.layout_request_id = lr.id
              WHERE lr.status = 'approved'
              GROUP BY lr.id
              ORDER BY lr.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $requests = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $requests[] = [
            'id' => $row['id'],
            'homeowner_name' => $row['homeowner_name'],
            'client_name' => $row['client_name'],
            'plot_size' => $row['plot_size'],
            'budget_range' => $row['budget_range'],
            'requirements' => $row['requirements'],
            // decode structured requirements if JSON
            'requirements_parsed' => json_decode($row['requirements'], true),
            'plot_shape' => $row['plot_shape'],
            'topography' => $row['topography'],
            'development_laws' => $row['development_laws'],
            'family_needs' => $row['family_needs'],
            'rooms' => $row['rooms'],
            'aesthetic' => $row['aesthetic'],
            'location' => $row['location'] ?? 'Not specified',
            'timeline' => $row['timeline'],
            'layout_file' => $row['layout_file'],
            'created_at' => $row['created_at'],
            'design_count' => (int)$row['design_count'],
            'status' => $row['status'],
            'accepted_count' => isset($row['accepted_count']) ? (int)$row['accepted_count'] : 0,
            'rejected_count' => isset($row['rejected_count']) ? (int)$row['rejected_count'] : 0,
            'sent_count' => isset($row['sent_count']) ? (int)$row['sent_count'] : 0,
            
            // New detailed fields
            'num_floors' => $row['num_floors'],
            'preferred_style' => $row['preferred_style'],
            'orientation' => $row['orientation'],
            'site_considerations' => $row['site_considerations'],
            'material_preferences' => $row['material_preferences'],
            'budget_allocation' => $row['budget_allocation'],
            'site_images' => $row['site_images'],
            'reference_images' => $row['reference_images'],
            'room_images' => $row['room_images'],
            'floor_rooms' => $row['floor_rooms'],
            
            // Client contact information
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'address' => $row['address'],
            'city' => $row['city'],
            'state' => $row['state'],
            'zip_code' => $row['zip_code'],
        ];
    }
    
    // Clear any accidental output before emitting JSON
    $stray = ob_get_clean();
    if (!empty($stray)) {
        error_log('get_layout_requests stray output: ' . substr($stray, 0, 500));
    }
    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);
    exit;
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching layout requests: ' . $e->getMessage()
    ]);
    exit;
}
?>