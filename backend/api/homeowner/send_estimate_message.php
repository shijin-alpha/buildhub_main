<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    header('Access-Control-Max-Age: 86400'); 
    exit; 
}

require_once __DIR__ . '/../../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $homeownerId = isset($input['homeowner_id']) ? (int)$input['homeowner_id'] : 0;
    $estimateId = isset($input['estimate_id']) ? (int)$input['estimate_id'] : 0;
    $contractorId = isset($input['contractor_id']) ? (int)$input['contractor_id'] : 0;
    $message = isset($input['message']) ? trim($input['message']) : '';
    $projectTitle = isset($input['project_title']) ? trim($input['project_title']) : 'Untitled Project';

    if ($homeownerId <= 0 || $estimateId <= 0 || $contractorId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    // Ensure contractor_inbox table exists with correct structure
    $db->exec("CREATE TABLE IF NOT EXISTS contractor_inbox (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        homeowner_id INT NOT NULL,
        estimate_id INT NULL,
        type ENUM('layout_request', 'construction_start', 'estimate_response', 'estimate_message', 'general') DEFAULT 'estimate_message',
        title VARCHAR(255) NOT NULL,
        message TEXT NULL,
        payload LONGTEXT NULL,
        status ENUM('unread', 'read', 'acknowledged') DEFAULT 'unread',
        acknowledged_at DATETIME NULL,
        due_date DATE NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(contractor_id), INDEX(homeowner_id), INDEX(estimate_id)
    )");
    
    // Ensure contractor_send_estimates table has required columns
    try { $db->exec("ALTER TABLE contractor_send_estimates ADD COLUMN homeowner_feedback TEXT NULL"); } catch (Throwable $e) {}
    try { $db->exec("ALTER TABLE contractor_send_estimates ADD COLUMN homeowner_action_at DATETIME NULL"); } catch (Throwable $e) {}

    // Validate ownership: estimate -> send -> homeowner
    $q = $db->prepare("
        SELECT e.id, e.materials, e.cost_breakdown, e.total_cost, e.timeline, e.notes, e.structured,
               s.layout_id, s.design_id, s.message as original_message
        FROM contractor_send_estimates e 
        INNER JOIN contractor_layout_sends s ON s.id = e.send_id 
        WHERE e.id = :eid AND s.homeowner_id = :hid AND s.contractor_id = :cid
    ");
    $q->bindValue(':eid', $estimateId, PDO::PARAM_INT);
    $q->bindValue(':hid', $homeownerId, PDO::PARAM_INT);
    $q->bindValue(':cid', $contractorId, PDO::PARAM_INT);
    $q->execute();
    $estimateData = $q->fetch(PDO::FETCH_ASSOC);
    
    if (!$estimateData) {
        echo json_encode(['success' => false, 'message' => 'Estimate not found or access denied']);
        exit;
    }

    // Get homeowner details - only select existing columns
    $homeownerQuery = $db->prepare("
        SELECT id, first_name, last_name, email, phone, address, city, state, zip_code
        FROM users 
        WHERE id = :hid
    ");
    $homeownerQuery->bindValue(':hid', $homeownerId, PDO::PARAM_INT);
    $homeownerQuery->execute();
    $homeownerData = $homeownerQuery->fetch(PDO::FETCH_ASSOC);

    // Get layout details if available
    $layoutData = null;
    if ($estimateData['layout_id']) {
        $layoutQuery = $db->prepare("
            SELECT id, title, description, image_url, created_at
            FROM layout_library 
            WHERE id = :lid
        ");
        $layoutQuery->bindValue(':lid', $estimateData['layout_id'], PDO::PARAM_INT);
        $layoutQuery->execute();
        $layoutData = $layoutQuery->fetch(PDO::FETCH_ASSOC);
    }

    // Prepare payload with all relevant information
    $payload = [
        'estimate_details' => [
            'id' => $estimateData['id'],
            'materials' => $estimateData['materials'],
            'cost_breakdown' => $estimateData['cost_breakdown'],
            'total_cost' => $estimateData['total_cost'],
            'timeline' => $estimateData['timeline'],
            'notes' => $estimateData['notes'],
            'structured' => $estimateData['structured'] ? json_decode($estimateData['structured'], true) : null
        ],
        'homeowner_details' => $homeownerData,
        'layout_details' => $layoutData,
        'project_title' => $projectTitle,
        'original_message' => $estimateData['original_message']
    ];

    // Insert message into contractor inbox
    $insertStmt = $db->prepare("
        INSERT INTO contractor_inbox (
            contractor_id, 
            homeowner_id, 
            estimate_id,
            type,
            title,
            message,
            payload,
            status,
            created_at
        ) VALUES (
            :contractor_id,
            :homeowner_id,
            :estimate_id,
            'estimate_message',
            :title,
            :message,
            :payload,
            'unread',
            NOW()
        )
    ");

    $insertStmt->bindValue(':contractor_id', $contractorId, PDO::PARAM_INT);
    $insertStmt->bindValue(':homeowner_id', $homeownerId, PDO::PARAM_INT);
    $insertStmt->bindValue(':estimate_id', $estimateId, PDO::PARAM_INT);
    $insertStmt->bindValue(':title', "Estimate Approved: {$projectTitle}", PDO::PARAM_STR);
    $insertStmt->bindValue(':message', $message, PDO::PARAM_STR);
    $insertStmt->bindValue(':payload', json_encode($payload), PDO::PARAM_STR);
    $insertStmt->execute();

    // Update estimate status to 'approved_with_message'
    $updateStmt = $db->prepare("
        UPDATE contractor_send_estimates 
        SET status = 'approved_with_message', 
            homeowner_feedback = :feedback,
            homeowner_action_at = NOW()
        WHERE id = :estimate_id
    ");
    $updateStmt->bindValue(':feedback', $message, PDO::PARAM_STR);
    $updateStmt->bindValue(':estimate_id', $estimateId, PDO::PARAM_INT);
    $updateStmt->execute();

    // Create notification for homeowner
    $notificationStmt = $db->prepare("
        INSERT INTO notifications (user_id, type, title, message, related_id) 
        VALUES (?, 'message_sent', 'Message Sent to Contractor', ?, ?)
    ");
    $notificationStmt->execute([
        $homeownerId, 
        "Your message has been sent to the contractor for project: {$projectTitle}",
        $estimateId
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Message sent to contractor successfully',
        'inbox_id' => $db->lastInsertId()
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
