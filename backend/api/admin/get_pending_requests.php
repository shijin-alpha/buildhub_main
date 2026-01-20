<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    session_start();
    
    // Check admin authentication
    $isAdmin = false;
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        $isAdmin = true;
    } elseif (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $isAdmin = true;
    }

    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    // Get all pending layout requests with homeowner details
    $query = "SELECT 
                lr.id,
                lr.user_id,
                lr.homeowner_id,
                lr.plot_size,
                lr.budget_range,
                lr.requirements,
                lr.location,
                lr.timeline,
                lr.preferred_style,
                lr.num_floors,
                lr.status,
                lr.created_at,
                lr.updated_at,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                CONCAT(u.first_name, ' ', u.last_name) as homeowner_name
              FROM layout_requests lr
              JOIN users u ON lr.user_id = u.id
              WHERE lr.status = 'pending'
              ORDER BY lr.created_at ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    $requests = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Parse requirements if JSON
        $requirements_parsed = json_decode($row['requirements'], true);
        
        $requests[] = [
            'id' => $row['id'],
            'homeowner_id' => $row['homeowner_id'],
            'homeowner_name' => $row['homeowner_name'],
            'homeowner_email' => $row['email'],
            'homeowner_phone' => $row['phone'],
            'plot_size' => $row['plot_size'],
            'budget_range' => $row['budget_range'],
            'location' => $row['location'],
            'timeline' => $row['timeline'],
            'preferred_style' => $row['preferred_style'],
            'num_floors' => $row['num_floors'],
            'requirements' => $row['requirements'],
            'requirements_parsed' => $requirements_parsed,
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'total_count' => count($requests)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching pending requests: ' . $e->getMessage()
    ]);
}
?>