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
    $architect_id = $_SESSION['user_id'] ?? null;

    if (!$architect_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    // Ensure table exists (if homeowner used new flow)
    $db->exec("CREATE TABLE IF NOT EXISTS layout_request_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        layout_request_id INT NOT NULL,
        homeowner_id INT NOT NULL,
        architect_id INT NOT NULL,
        message TEXT NULL,
        status ENUM('sent','accepted','declined') DEFAULT 'sent',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_lr_arch (layout_request_id, architect_id),
        FOREIGN KEY (layout_request_id) REFERENCES layout_requests(id) ON DELETE CASCADE,
        FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (architect_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $query = "SELECT 
                a.id as assignment_id,
                a.status as assignment_status,
                a.created_at as assigned_at,
                a.message,
                lr.id as layout_request_id,
                lr.user_id as homeowner_id,
                lr.plot_size, lr.budget_range, lr.requirements, lr.location, lr.timeline,
                lr.preferred_style, lr.layout_type, lr.selected_layout_id, lr.layout_file,
                lr.site_images, lr.reference_images, lr.room_images,
                lr.orientation, lr.site_considerations, lr.material_preferences,
                lr.budget_allocation, lr.floor_rooms, lr.num_floors,
                lr.status as request_status, lr.created_at as request_created_at, lr.updated_at as request_updated_at,
                u.id as user_id, CONCAT(u.first_name, ' ', u.last_name) as homeowner_name, u.email as homeowner_email,
                ll.title as library_title, ll.image_url as library_image, ll.layout_type as library_layout_type, ll.design_file_url as library_file
              FROM layout_request_assignments a
              JOIN layout_requests lr ON lr.id = a.layout_request_id
              JOIN users u ON u.id = a.homeowner_id
              LEFT JOIN layout_library ll ON lr.selected_layout_id = ll.id
              WHERE a.architect_id = :aid
              ORDER BY a.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([':aid' => $architect_id]);

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Parse requirements once
        $requirements_parsed = json_decode($row['requirements'], true);
        
        // Try to get floor_rooms from dedicated column first, then from requirements JSON
        $floor_rooms = [];
        if ($row['floor_rooms']) {
            $floor_rooms = json_decode($row['floor_rooms'], true) ?: [];
        } elseif ($requirements_parsed && isset($requirements_parsed['floor_rooms'])) {
            $floor_rooms_value = $requirements_parsed['floor_rooms'];
            
            // Handle case where floor_rooms is stored as a JSON string (double-encoded)
            if (is_string($floor_rooms_value)) {
                $decoded = json_decode($floor_rooms_value, true);
                $floor_rooms = $decoded ?: [];
            } else {
                $floor_rooms = $floor_rooms_value;
            }
        }
        
        $items[] = [
            'assignment_id' => (int)$row['assignment_id'],
            'assignment_status' => $row['assignment_status'],
            'assigned_at' => $row['assigned_at'],
            'message' => $row['message'],
            'layout_request' => [
                'id' => (int)$row['layout_request_id'],
                'plot_size' => $row['plot_size'],
                'budget_range' => $row['budget_range'],
                'requirements' => $row['requirements'],
                'requirements_parsed' => $requirements_parsed,
                'location' => $row['location'],
                'timeline' => $row['timeline'],
                'preferred_style' => $row['preferred_style'] ?? null,
                'layout_type' => $row['layout_type'] ?? 'custom',
                'selected_layout_id' => $row['selected_layout_id'] ?? null,
                'layout_file' => $row['layout_file'] ?? null,
                // New comprehensive fields
                'site_images' => $row['site_images'] ? json_decode($row['site_images'], true) : [],
                'reference_images' => $row['reference_images'] ? json_decode($row['reference_images'], true) : [],
                'room_images' => $row['room_images'] ? json_decode($row['room_images'], true) : [],
                'orientation' => $row['orientation'] ?? null,
                'site_considerations' => $row['site_considerations'] ?? null,
                'material_preferences' => $row['material_preferences'] ? json_decode($row['material_preferences'], true) : [],
                'budget_allocation' => $row['budget_allocation'] ?? null,
                'floor_rooms' => $floor_rooms,
                'num_floors' => $row['num_floors'] ? (int)$row['num_floors'] : null,
                'status' => $row['request_status'],
                'created_at' => $row['request_created_at'],
                'updated_at' => $row['request_updated_at'],
                'library' => [
                    'title' => $row['library_title'] ?? null,
                    'image_url' => $row['library_image'] ?? null,
                    'layout_type' => $row['library_layout_type'] ?? null,
                    'file_url' => $row['library_file'] ?? ($row['layout_file'] ?? null)
                ]
            ],
            'homeowner' => [
                'id' => (int)$row['user_id'],
                'name' => $row['homeowner_name'],
                'email' => $row['homeowner_email']
            ]
        ];
    }

    echo json_encode(['success' => true, 'assignments' => $items]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching assigned requests: ' . $e->getMessage()]);
}