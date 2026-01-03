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

    // Verify plan belongs to this architect and get details
    $planStmt = $db->prepare("
        SELECT hp.*, lr.user_id as homeowner_id 
        FROM house_plans hp
        LEFT JOIN layout_requests lr ON hp.layout_request_id = lr.id
        WHERE hp.id = :id AND hp.architect_id = :aid
    ");
    $planStmt->execute([':id' => $plan_id, ':aid' => $architect_id]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        echo json_encode(['success' => false, 'message' => 'Plan not found or access denied']);
        exit;
    }

    if ($plan['status'] === 'submitted') {
        echo json_encode(['success' => false, 'message' => 'Plan already submitted']);
        exit;
    }

    // Begin transaction
    $db->beginTransaction();

    try {
        // Update plan status to submitted
        $updateStmt = $db->prepare("UPDATE house_plans SET status = 'submitted' WHERE id = :id");
        $updateStmt->execute([':id' => $plan_id]);

        // If linked to a layout request, create review entry for homeowner
        if ($plan['layout_request_id'] && $plan['homeowner_id']) {
            $reviewStmt = $db->prepare("
                INSERT INTO house_plan_reviews (house_plan_id, homeowner_id, status)
                VALUES (:plan_id, :homeowner_id, 'pending')
                ON DUPLICATE KEY UPDATE status = 'pending', reviewed_at = CURRENT_TIMESTAMP
            ");
            $reviewStmt->execute([
                ':plan_id' => $plan_id,
                ':homeowner_id' => $plan['homeowner_id']
            ]);

            // Create notification for homeowner
            $notificationStmt = $db->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_id)
                VALUES (:user_id, 'house_plan_submitted', 'New House Plan Submitted', 
                        CONCAT('Architect has submitted a custom house plan: ', :plan_name), :plan_id)
            ");
            $notificationStmt->execute([
                ':user_id' => $plan['homeowner_id'],
                ':plan_name' => $plan['plan_name'],
                ':plan_id' => $plan_id
            ]);
        }

        $db->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'House plan submitted successfully',
            'plan_id' => $plan_id
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>