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
    $layout_request_id = isset($input['layout_request_id']) ? (int)$input['layout_request_id'] : 0;
    $architect_ids = isset($input['architect_ids']) ? $input['architect_ids'] : [];
    $message = isset($input['message']) ? trim($input['message']) : '';

    if ($layout_request_id <= 0 || empty($architect_ids)) {
        echo json_encode(['success' => false, 'message' => 'Layout request ID and architect IDs are required']);
        exit;
    }

    // Ensure architect_ids is an array
    if (!is_array($architect_ids)) {
        $architect_ids = [$architect_ids];
    }

    // Verify request exists, belongs to homeowner, and is approved
    $requestStmt = $db->prepare("SELECT id, status FROM layout_requests WHERE id = :id AND user_id = :homeowner_id");
    $requestStmt->execute([':id' => $layout_request_id, ':homeowner_id' => $homeowner_id]);
    $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found or access denied']);
        exit;
    }

    if ($request['status'] !== 'approved') {
        echo json_encode(['success' => false, 'message' => 'Request must be approved before assigning architects']);
        exit;
    }

    // Ensure assignment table exists
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
    )");

    $successCount = 0;
    $errors = [];

    foreach ($architect_ids as $architect_id) {
        $architect_id = (int)$architect_id;
        
        // Verify architect exists and is verified
        $archStmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE id = :id AND role = 'architect' AND is_verified = 1");
        $archStmt->execute([':id' => $architect_id]);
        $architect = $archStmt->fetch(PDO::FETCH_ASSOC);

        if (!$architect) {
            $errors[] = "Architect ID $architect_id not found or not verified";
            continue;
        }

        // Insert or update assignment
        $assignStmt = $db->prepare("INSERT INTO layout_request_assignments (layout_request_id, homeowner_id, architect_id, message, status) 
                                   VALUES (:layout_request_id, :homeowner_id, :architect_id, :message, 'sent')
                                   ON DUPLICATE KEY UPDATE 
                                   message = VALUES(message), 
                                   status = 'sent', 
                                   updated_at = CURRENT_TIMESTAMP");
        
        $assignResult = $assignStmt->execute([
            ':layout_request_id' => $layout_request_id,
            ':homeowner_id' => $homeowner_id,
            ':architect_id' => $architect_id,
            ':message' => $message
        ]);

        if ($assignResult) {
            $successCount++;

            // Create notification for architect
            try {
                $notifyStmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) 
                                          VALUES (:user_id, 'new_assignment', :title, :message, CURRENT_TIMESTAMP)");
                $notifyStmt->execute([
                    ':user_id' => $architect_id,
                    ':title' => 'New Design Request Assignment',
                    ':message' => "You have been assigned a new house design request. Please review and respond."
                ]);
            } catch (Exception $e) {
                // Notification failed, but don't fail the assignment
                error_log("Failed to create notification for architect $architect_id: " . $e->getMessage());
            }
        } else {
            $errors[] = "Failed to assign architect ID $architect_id";
        }
    }

    if ($successCount > 0) {
        echo json_encode([
            'success' => true,
            'message' => "Successfully assigned to $successCount architect(s)",
            'assigned_count' => $successCount,
            'errors' => $errors
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to assign any architects',
            'errors' => $errors
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error assigning architects: ' . $e->getMessage()
    ]);
}
?>