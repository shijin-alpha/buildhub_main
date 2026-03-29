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

    // Get plan details and homeowner info before deletion
    $planStmt = $db->prepare("
        SELECT hp.id, hp.plan_name, hp.status, hp.layout_request_id, hp.architect_id,
               lr.homeowner_id, u.first_name, u.last_name
        FROM house_plans hp
        LEFT JOIN layout_requests lr ON hp.layout_request_id = lr.id
        LEFT JOIN users u ON lr.homeowner_id = u.id
        WHERE hp.id = :id AND hp.architect_id = :aid
    ");
    $planStmt->execute([':id' => $plan_id, ':aid' => $architect_id]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        echo json_encode(['success' => false, 'message' => 'Plan not found or access denied']);
        exit;
    }

    if ($plan['status'] !== 'draft') {
        echo json_encode(['success' => false, 'message' => 'Only draft plans can be deleted']);
        exit;
    }

    // Get architect info for the message
    $architectStmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = :id");
    $architectStmt->execute([':id' => $architect_id]);
    $architect = $architectStmt->fetch(PDO::FETCH_ASSOC);

    // Delete the plan (reviews will be deleted automatically due to foreign key constraint)
    $deleteStmt = $db->prepare("DELETE FROM house_plans WHERE id = :id AND architect_id = :aid");
    $success = $deleteStmt->execute([':id' => $plan_id, ':aid' => $architect_id]);

    if ($success && $deleteStmt->rowCount() > 0) {
        // Send inbox message to homeowner if there's a homeowner associated
        if ($plan['homeowner_id']) {
            $architectName = trim(($architect['first_name'] ?? '') . ' ' . ($architect['last_name'] ?? ''));
            $homeownerName = trim(($plan['first_name'] ?? '') . ' ' . ($plan['last_name'] ?? ''));
            
            $messageTitle = 'House Plan Deleted';
            $messageContent = "Your architect {$architectName} has deleted the house plan \"{$plan['plan_name']}\". This may be due to design revisions or starting a new approach. Your architect will create a new plan for your review.";
            
            // Insert inbox message
            $inboxStmt = $db->prepare("
                INSERT INTO inbox_messages (
                    recipient_id, sender_id, message_type, title, message, 
                    metadata, priority, is_read, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
            ");
            
            $metadata = json_encode([
                'plan_id' => $plan_id,
                'plan_name' => $plan['plan_name'],
                'architect_id' => $architect_id,
                'architect_name' => $architectName,
                'action' => 'plan_deleted'
            ]);
            
            $inboxStmt->execute([
                $plan['homeowner_id'],
                $architect_id,
                'plan_deleted',
                $messageTitle,
                $messageContent,
                $metadata,
                'normal'
            ]);
            
            // Also create a notification for real-time updates
            $notificationStmt = $db->prepare("
                INSERT INTO notifications (
                    user_id, type, title, message, metadata, is_read, created_at
                ) VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            
            $notificationStmt->execute([
                $plan['homeowner_id'],
                'plan_deleted',
                $messageTitle,
                $messageContent,
                $metadata
            ]);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'House plan deleted successfully',
            'data' => [
                'plan_name' => $plan['plan_name'],
                'homeowner_notified' => !empty($plan['homeowner_id'])
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete plan']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>