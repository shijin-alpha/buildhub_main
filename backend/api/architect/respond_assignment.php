<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    session_start();
    $architect_id = $_SESSION['user_id'] ?? null;
    if (!$architect_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $assignment_id = isset($input['assignment_id']) ? (int)$input['assignment_id'] : 0;
    $action = isset($input['action']) ? strtolower(trim($input['action'])) : '';
    if ($assignment_id <= 0 || !in_array($action, ['accept', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    // Ensure table exists
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

    // Update status only if belongs to this architect
    $newStatus = $action === 'accept' ? 'accepted' : 'declined';
    $stmt = $db->prepare("UPDATE layout_request_assignments SET status = :st
                           WHERE id = :id AND architect_id = :aid");
    $ok = $stmt->execute([':st' => $newStatus, ':id' => $assignment_id, ':aid' => $architect_id]);

    if ($ok && $stmt->rowCount() > 0) {
        // If architect accepted, also mark the related layout request as approved so it appears to architects
        if ($newStatus === 'accepted') {
            // Get layout_request_id for this assignment
            $sel = $db->prepare("SELECT layout_request_id FROM layout_request_assignments WHERE id = :id AND architect_id = :aid");
            $sel->execute([':id' => $assignment_id, ':aid' => $architect_id]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            if (!empty($row['layout_request_id'])) {
                $lrid = (int)$row['layout_request_id'];
                // Update layout_requests status to approved
                $upd = $db->prepare("UPDATE layout_requests SET status = 'approved' WHERE id = :lrid");
                $upd->execute([':lrid' => $lrid]);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Updated', 'status' => $newStatus]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not found or no change']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}