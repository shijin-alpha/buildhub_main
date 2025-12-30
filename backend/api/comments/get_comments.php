<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

    $design_id = isset($_GET['design_id']) ? (int)$_GET['design_id'] : null;
    if (!$design_id) {
        echo json_encode(['success' => false, 'message' => 'design_id is required']);
        exit;
    }

    // Ensure table exists
    $db->exec("CREATE TABLE IF NOT EXISTS design_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        design_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Access guard (same as post)
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

    $sql = "SELECT c.*, u.first_name, u.last_name
            FROM design_comments c
            JOIN users u ON u.id = c.user_id
            WHERE c.design_id = :did
            ORDER BY c.created_at ASC";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':did', $design_id, PDO::PARAM_INT);
    $stmt->execute();

    $comments = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $comments[] = [
            'id' => (int)$row['id'],
            'design_id' => (int)$row['design_id'],
            'user_id' => (int)$row['user_id'],
            'message' => $row['message'],
            'author' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode(['success' => true, 'comments' => $comments]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching comments: ' . $e->getMessage()]);
}