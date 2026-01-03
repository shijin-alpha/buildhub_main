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
    
    $plan_id = isset($input['plan_id']) ? (int)$input['plan_id'] : 0;
    $status = $input['status'] ?? '';
    $feedback = trim($input['feedback'] ?? '');

    if ($plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Plan ID is required']);
        exit;
    }

    if (!in_array($status, ['approved', 'rejected', 'revision_requested'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Verify plan belongs to this homeowner's request
    $checkStmt = $db->prepare("
        SELECT hp.id, hp.architect_id, lr.user_id as homeowner_id 
        FROM house_plans hp
        INNER JOIN layout_requests lr ON hp.layout_request_id = lr.id
        WHERE hp.id = :plan_id AND lr.user_id = :homeowner_id
    ");
    $checkStmt->execute([':plan_id' => $plan_id, ':homeowner_id' => $homeowner_id]);
    $plan = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        echo json_encode(['success' => false, 'message' => 'Plan not found or access denied']);
        exit;
    }

    // Begin transaction
    $db->beginTransaction();

    try {
        // Insert or update review
        $reviewStmt = $db->prepare("
            INSERT INTO house_plan_reviews (house_plan_id, homeowner_id, status, feedback)
            VALUES (:plan_id, :homeowner_id, :status, :feedback)
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                feedback = VALUES(feedback), 
                reviewed_at = CURRENT_TIMESTAMP
        ");
        $reviewStmt->execute([
            ':plan_id' => $plan_id,
            ':homeowner_id' => $homeowner_id,
            ':status' => $status,
            ':feedback' => $feedback
        ]);

        // Update house plan status based on review
        $planStatus = $status === 'approved' ? 'approved' : 
                     ($status === 'rejected' ? 'rejected' : 'submitted');
        
        $updatePlanStmt = $db->prepare("UPDATE house_plans SET status = :status WHERE id = :plan_id");
        $updatePlanStmt->execute([':status' => $planStatus, ':plan_id' => $plan_id]);

        // Create notification for architect
        $notificationTitle = '';
        $notificationMessage = '';
        
        switch ($status) {
            case 'approved':
                $notificationTitle = 'House Plan Approved';
                $notificationMessage = 'Your house plan has been approved by the homeowner!';
                break;
            case 'rejected':
                $notificationTitle = 'House Plan Rejected';
                $notificationMessage = 'Your house plan has been rejected. Please check the feedback.';
                break;
            case 'revision_requested':
                $notificationTitle = 'House Plan Revision Requested';
                $notificationMessage = 'The homeowner has requested revisions to your house plan.';
                break;
        }

        $notificationStmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, related_id)
            VALUES (:user_id, 'house_plan_review', :title, :message, :plan_id)
        ");
        $notificationStmt->execute([
            ':user_id' => $plan['architect_id'],
            ':title' => $notificationTitle,
            ':message' => $notificationMessage,
            ':plan_id' => $plan_id
        ]);

        $db->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Review submitted successfully',
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