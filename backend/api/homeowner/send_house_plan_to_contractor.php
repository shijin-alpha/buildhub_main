<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;

    if (!$homeowner_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    $contractor_id = isset($input['contractor_id']) ? (int)$input['contractor_id'] : 0;
    $house_plan_data = isset($input['house_plan_data']) ? $input['house_plan_data'] : null;
    $message = isset($input['message']) ? trim($input['message']) : '';

    if ($contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing contractor ID']);
        exit;
    }

    if (!$house_plan_data) {
        echo json_encode(['success' => false, 'message' => 'Missing house plan data']);
        exit;
    }

    // Verify contractor exists and is active
    $checkStmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE id = :id AND role = 'contractor'");
    $checkStmt->execute([':id' => $contractor_id]);
    $contractor = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contractor) {
        echo json_encode(['success' => false, 'message' => 'Contractor not found']);
        exit;
    }

    // Ensure contractor_layout_sends table exists with required columns
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS contractor_layout_sends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contractor_id INT NOT NULL,
            homeowner_id INT NULL,
            layout_id INT NULL,
            design_id INT NULL,
            house_plan_id INT NULL,
            message TEXT NULL,
            payload LONGTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            acknowledged_at DATETIME NULL,
            due_date DATE NULL
        )");
    } catch (Exception $e) {
        // Table might already exist
    }

    // Ensure house_plan_id column exists
    try {
        $cols = $db->query("SHOW COLUMNS FROM contractor_layout_sends")->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!in_array('house_plan_id', $cols)) {
            $db->exec("ALTER TABLE contractor_layout_sends ADD COLUMN house_plan_id INT NULL AFTER design_id");
        }
    } catch (Exception $e) {
        // Column might already exist
    }

    // Extract layout images from technical details for contractor viewing
    $layout_images = [];
    $layout_image_url = null;
    
    if (isset($house_plan_data['technical_details']['layout_image']) && is_array($house_plan_data['technical_details']['layout_image'])) {
        $layoutImage = $house_plan_data['technical_details']['layout_image'];
        if (!empty($layoutImage['name']) && (!isset($layoutImage['uploaded']) || $layoutImage['uploaded'] === true)) {
            $storedName = $layoutImage['stored'] ?? $layoutImage['name'];
            $layout_image_url = '/buildhub/backend/uploads/house_plans/' . $storedName;
            $layout_images[] = [
                'original' => $layoutImage['name'],
                'stored' => $storedName,
                'url' => $layout_image_url,
                'path' => $layout_image_url,
                'type' => 'layout_image'
            ];
        }
    }
    
    // Prepare comprehensive payload for contractor
    $payload = [
        'type' => 'house_plan',
        'house_plan_id' => $house_plan_data['house_plan_id'] ?? null,
        'plan_name' => $house_plan_data['plan_name'] ?? '',
        'plot_dimensions' => $house_plan_data['plot_dimensions'] ?? '',
        'total_area' => $house_plan_data['total_area'] ?? 0,
        'technical_details' => $house_plan_data['technical_details'] ?? [],
        'plan_data' => $house_plan_data['plan_data'] ?? [],
        'architect_info' => $house_plan_data['architect_info'] ?? [],
        'layout_images' => $layout_images,
        'layout_image_url' => $layout_image_url, // Primary layout image for quick display
        'notes' => $house_plan_data['notes'] ?? '',
        'message' => $message,
        'sent_at' => date('Y-m-d H:i:s'),
        'homeowner_id' => $homeowner_id,
        // Add forwarded_design structure for compatibility with existing frontend
        'forwarded_design' => [
            'title' => $house_plan_data['plan_name'] ?? 'House Plan',
            'description' => $house_plan_data['notes'] ?? 'House plan with technical specifications',
            'technical_details' => $house_plan_data['technical_details'] ?? [],
            'files' => $layout_images
        ]
    ];

    // Insert the house plan send record
    $insertStmt = $db->prepare("
        INSERT INTO contractor_layout_sends 
        (contractor_id, homeowner_id, house_plan_id, message, payload, created_at) 
        VALUES (:contractor_id, :homeowner_id, :house_plan_id, :message, :payload, NOW())
    ");
    
    $insertStmt->execute([
        ':contractor_id' => $contractor_id,
        ':homeowner_id' => $homeowner_id,
        ':house_plan_id' => $house_plan_data['house_plan_id'] ?? null,
        ':message' => $message,
        ':payload' => json_encode($payload)
    ]);

    $send_id = $db->lastInsertId();

    // Create notification for contractor
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            related_id INT NULL,
            metadata LONGTEXT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $notificationStmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_id, metadata, created_at)
            VALUES (:user_id, 'house_plan_received', :title, :message, :related_id, :metadata, NOW())
        ");
        
        $title = 'New House Plan for Estimate';
        $notificationMessage = sprintf(
            'You have received a house plan "%s" from a homeowner. Plot: %s, Area: %s sq ft. Please review and provide your construction estimate.',
            $house_plan_data['plan_name'] ?? 'House Plan',
            $house_plan_data['plot_dimensions'] ?? 'N/A',
            number_format($house_plan_data['total_area'] ?? 0)
        );
        
        $metadata = json_encode([
            'send_id' => $send_id,
            'house_plan_id' => $house_plan_data['house_plan_id'] ?? null,
            'homeowner_id' => $homeowner_id,
            'plan_name' => $house_plan_data['plan_name'] ?? '',
            'total_area' => $house_plan_data['total_area'] ?? 0
        ]);
        
        $notificationStmt->execute([
            ':user_id' => $contractor_id,
            ':title' => $title,
            ':message' => $notificationMessage,
            ':related_id' => $send_id,
            ':metadata' => $metadata
        ]);
    } catch (Exception $e) {
        // Notification creation failed, but main operation succeeded
        error_log("Failed to create notification: " . $e->getMessage());
    }

    $contractor_name = trim(($contractor['first_name'] ?? '') . ' ' . ($contractor['last_name'] ?? ''));
    
    echo json_encode([
        'success' => true, 
        'message' => 'House plan sent successfully to contractor',
        'contractor_name' => $contractor_name ?: 'Contractor',
        'send_id' => $send_id
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>