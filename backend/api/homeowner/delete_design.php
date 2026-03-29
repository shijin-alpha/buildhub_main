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
    $user_id = $_SESSION['user_id'] ?? null; // homeowner id

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = $_POST ?? []; }

    $design_id = isset($input['design_id']) ? (int)$input['design_id'] : 0;
    if ($design_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'design_id is required']);
        exit;
    }

    // Ensure the design belongs to this homeowner (either direct or via a layout_request)
    $ownSql = "SELECT d.id, d.design_files, d.homeowner_id, d.layout_request_id
               FROM designs d
               LEFT JOIN layout_requests lr ON lr.id = d.layout_request_id
               WHERE d.id = :did AND (d.homeowner_id = :uid1 OR lr.homeowner_id = :uid2)";
    $stmt = $db->prepare($ownSql);
    $stmt->execute([':did' => $design_id, ':uid1' => $user_id, ':uid2' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Design not found for this user']);
        exit;
    }

    // Delete DB record first
    $del = $db->prepare('DELETE FROM designs WHERE id = :did');
    $del->execute([':did' => $design_id]);

    // Attempt to delete files from disk
    $files = json_decode($row['design_files'], true);
    if (is_array($files)) {
        foreach ($files as $f) {
            if (!empty($f['stored'])) {
                $abs = realpath(__DIR__ . '/../../uploads/designs/' . $f['stored']);
                if ($abs && strpos($abs, realpath(__DIR__ . '/../../uploads/designs/')) === 0 && file_exists($abs)) {
                    @unlink($abs);
                }
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Design deleted']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error deleting design: ' . $e->getMessage()]);
}