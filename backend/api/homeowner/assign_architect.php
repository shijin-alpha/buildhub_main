<?php
header('Content-Type: application/json');
// Allow same-origin cookie sessions; adjust origin if needed
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Vary: Origin');
header('Access-Control-Allow-Credentials: true');
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

    // Parse JSON body or fallback to POST
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) { $input = $_POST ?? []; }

    // Accept various keys for compatibility
    $layout_request_id = 0;
    if (isset($input['layout_request_id'])) { $layout_request_id = (int)$input['layout_request_id']; }
    elseif (isset($input['request_id'])) { $layout_request_id = (int)$input['request_id']; }

    // Support both single and multiple architects
    $architect_ids = [];
    if (isset($input['architect_ids'])) {
        if (is_array($input['architect_ids'])) {
            $architect_ids = $input['architect_ids'];
        } elseif (is_string($input['architect_ids'])) {
            $architect_ids = preg_split('/[,\s]+/', trim($input['architect_ids']));
        }
    } elseif (isset($input['selected_architect_ids'])) {
        $architect_ids = is_array($input['selected_architect_ids']) ? $input['selected_architect_ids'] : [];
    } elseif (isset($input['architect_id'])) {
        $architect_ids = [(int)$input['architect_id']];
    }
    // Normalize to unique positive ints
    $architect_ids = array_values(array_unique(array_map('intval', $architect_ids)));
    $architect_ids = array_filter($architect_ids, function($v){ return $v > 0; });

    $message = $input['message'] ?? null;

    if ($layout_request_id <= 0 || count($architect_ids) === 0) {
        echo json_encode(['success' => false, 'message' => 'layout_request_id and at least one architect is required']);
        exit;
    }

    // Ensure layout_request belongs to this homeowner (by user_id)
    $ownStmt = $db->prepare("SELECT id FROM layout_requests WHERE id = :id AND user_id = :uid");
    $ownStmt->execute([':id' => $layout_request_id, ':uid' => $homeowner_id]);
    $own = $ownStmt->fetch(PDO::FETCH_ASSOC);

    if (!$own) {
        echo json_encode(['success' => false, 'message' => 'Request not found or not owned by user']);
        exit;
    }



    // Create linking table if not exists
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

    // Insert or update assignment(s)
    $insert = $db->prepare("INSERT INTO layout_request_assignments (layout_request_id, homeowner_id, architect_id, message)
                           VALUES (:lrid, :hid, :aid, :msg)
                           ON DUPLICATE KEY UPDATE message = VALUES(message), status = 'sent', updated_at = CURRENT_TIMESTAMP");

    $okAll = true;
    foreach ($architect_ids as $architect_id) {
        // Verify each architect exists and is approved (is_verified = 1)
        $archStmt = $db->prepare("SELECT id FROM users WHERE id = :aid AND role = 'architect' AND is_verified = 1");
        $archStmt->execute([':aid' => $architect_id]);
        if (!$archStmt->fetch(PDO::FETCH_ASSOC)) {
            $okAll = false; // skip invalid
            continue;
        }
        $ok = $insert->execute([
            ':lrid' => $layout_request_id,
            ':hid' => $homeowner_id,
            ':aid' => $architect_id,
            ':msg' => $message
        ]);
        if (!$ok) { $okAll = false; }
    }

    if ($okAll) {
        echo json_encode(['success' => true, 'message' => 'Request sent to architect(s)']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Some assignments may have failed']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}