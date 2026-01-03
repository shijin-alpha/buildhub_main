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
    $architect_id = $_SESSION['user_id'] ?? null;

    if (!$architect_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $plan_id = isset($input['plan_id']) ? (int)$input['plan_id'] : 0;

    if ($plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Plan ID is required']);
        exit;
    }

    // Verify plan belongs to this architect and is in draft status
    $checkStmt = $db->prepare("SELECT id, status FROM house_plans WHERE id = :id AND architect_id = :aid");
    $checkStmt->execute([':id' => $plan_id, ':aid' => $architect_id]);
    $plan = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        echo json_encode(['success' => false, 'message' => 'Plan not found or access denied']);
        exit;
    }

    if ($plan['status'] !== 'draft') {
        echo json_encode(['success' => false, 'message' => 'Only draft plans can be deleted']);
        exit;
    }

    // Delete the plan (reviews will be deleted automatically due to foreign key constraint)
    $deleteStmt = $db->prepare("DELETE FROM house_plans WHERE id = :id AND architect_id = :aid");
    $success = $deleteStmt->execute([':id' => $plan_id, ':aid' => $architect_id]);

    if ($success && $deleteStmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'House plan deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete plan']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>