<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { header('Access-Control-Allow-Origin: ' . $origin); header('Vary: Origin'); } else { header('Access-Control-Allow-Origin: http://localhost'); }
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); header('Access-Control-Max-Age: 86400'); exit; }

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $contractorId = isset($input['contractor_id']) ? (int)$input['contractor_id'] : 0;
    if ($id <= 0 || $contractorId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing id or contractor_id']);
        exit;
    }

    // Ensure table exists (defensive)
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS contractor_layout_sends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contractor_id INT NOT NULL,
            homeowner_id INT NULL,
            layout_id INT NULL,
            design_id INT NULL,
            message TEXT NULL,
            payload JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Throwable $e) {
        $db->exec("CREATE TABLE IF NOT EXISTS contractor_layout_sends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contractor_id INT NOT NULL,
            homeowner_id INT NULL,
            layout_id INT NULL,
            design_id INT NULL,
            message TEXT NULL,
            payload LONGTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // First, try to delete from contractor_layout_sends table
    $del1 = $db->prepare("DELETE FROM contractor_layout_sends WHERE id = :id AND contractor_id = :cid");
    $del1->bindValue(':id', $id, PDO::PARAM_INT);
    $del1->bindValue(':cid', $contractorId, PDO::PARAM_INT);
    $del1->execute();
    $deleted1 = $del1->rowCount();

    // Also try to delete from contractor_inbox table (if it exists)
    $deleted2 = 0;
    try {
        $del2 = $db->prepare("DELETE FROM contractor_inbox WHERE id = :id AND contractor_id = :cid");
        $del2->bindValue(':id', $id, PDO::PARAM_INT);
        $del2->bindValue(':cid', $contractorId, PDO::PARAM_INT);
        $del2->execute();
        $deleted2 = $del2->rowCount();
    } catch (Exception $e) {
        // contractor_inbox table might not exist, that's okay
    }

    if ($deleted1 > 0 || $deleted2 > 0) {
        echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found or already deleted']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error deleting item: ' . $e->getMessage()]);
}















