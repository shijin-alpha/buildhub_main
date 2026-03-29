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

    $architect_id = isset($_GET['architect_id']) ? (int)$_GET['architect_id'] : 0;
    if ($architect_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'architect_id is required']);
        exit;
    }

    // Ensure table exists
    $db->exec("CREATE TABLE IF NOT EXISTS architect_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        architect_id INT NOT NULL,
        homeowner_id INT NOT NULL,
        design_id INT NULL,
        rating TINYINT NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $sql = "SELECT r.*, u.first_name, u.last_name FROM architect_reviews r JOIN users u ON u.id = r.homeowner_id WHERE r.architect_id = :aid ORDER BY r.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':aid', $architect_id, PDO::PARAM_INT);
    $stmt->execute();

    $reviews = [];
    $total = 0; $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $reviews[] = [
            'id' => (int)$row['id'],
            'architect_id' => (int)$row['architect_id'],
            'homeowner_id' => (int)$row['homeowner_id'],
            'design_id' => isset($row['design_id']) ? (int)$row['design_id'] : null,
            'rating' => (int)$row['rating'],
            'comment' => $row['comment'],
            'author' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
            'created_at' => $row['created_at']
        ];
        $total += (int)$row['rating'];
        $count++;
    }
    $avg = $count > 0 ? round($total / $count, 2) : null;

    echo json_encode(['success' => true, 'reviews' => $reviews, 'avg_rating' => $avg, 'review_count' => $count]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching reviews: ' . $e->getMessage()]);
}