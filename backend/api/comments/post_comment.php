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
    $user_id = $_SESSION['user_id'] ?? null; // architect or homeowner

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    $design_id = $payload['design_id'] ?? null;
    $message = trim($payload['message'] ?? '');

    if (!$design_id || $message === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }

    // Ensure comments table exists
    $db->exec("CREATE TABLE IF NOT EXISTS design_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        design_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Basic access check: user must be either the architect or the homeowner on this design/request
    $check = $db->prepare("SELECT d.id
                            FROM designs d
                            LEFT JOIN layout_requests lr ON lr.id = d.layout_request_id
                            WHERE d.id = :id AND (d.architect_id = :uid1 OR d.homeowner_id = :uid2 OR lr.homeowner_id = :uid3)");
    $check->bindParam(':id', $design_id, PDO::PARAM_INT);
    $check->bindParam(':uid1', $user_id, PDO::PARAM_INT);
    $check->bindParam(':uid2', $user_id, PDO::PARAM_INT);
    $check->bindParam(':uid3', $user_id, PDO::PARAM_INT);
    $check->execute();

    if (!$check->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO design_comments (design_id, user_id, message) VALUES (:did, :uid, :msg)");
    $stmt->bindParam(':did', $design_id, PDO::PARAM_INT);
    $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':msg', $message);
    $stmt->execute();

    echo json_encode(['success' => true, 'comment_id' => $db->lastInsertId()]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error posting comment: ' . $e->getMessage()]);
}