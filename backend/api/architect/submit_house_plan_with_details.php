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
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input: ' . json_last_error_msg()]);
        exit;
    }

    $plan_id = isset($input['plan_id']) ? (int)$input['plan_id'] : 0;
    $technical_details = $input['technical_details'] ?? [];
    $plan_data = $input['plan_data'] ?? [];

    if ($plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Plan ID is required']);
        exit;
    }

    if (empty($technical_details)) {
        echo json_encode(['success' => false, 'message' => 'Technical details are required']);
        exit;
    }

    // Verify plan belongs to this architect and get details
    $planStmt = $db->prepare("
        SELECT hp.*, lr.homeowner_id, lr.id as layout_request_id
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
        // Extract unlock_price from technical_details
        $unlock_price = isset($technical_details['unlock_price']) ? (float)$technical_details['unlock_price'] : 8000.00;
        
        // Update plan with technical details and set status to submitted
        $updateStmt = $db->prepare("
            UPDATE house_plans 
            SET status = 'submitted', 
                plan_data = :plan_data,
                technical_details = :technical_details,
                unlock_price = :unlock_price,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        
        $updateSuccess = $updateStmt->execute([
            ':id' => $plan_id,
            ':plan_data' => json_encode($plan_data),
            ':technical_details' => json_encode($technical_details),
            ':unlock_price' => $unlock_price
        ]);

        if (!$updateSuccess) {
            throw new Exception('Failed to update plan with technical details');
        }

        // Create review entry for homeowner if linked to a layout request
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
                INSERT INTO notifications (user_id, type, title, message, related_id, created_at)
                VALUES (:user_id, 'house_plan_submitted', 'New House Plan with Technical Details', 
                        :message, :plan_id, CURRENT_TIMESTAMP)
            ");
            
            $message = sprintf(
                'Your architect has submitted a complete house plan "%s" with technical specifications. The plan includes %d rooms covering %.0f sq ft with estimated cost of %s. Please review and provide feedback.',
                $plan['plan_name'],
                count($plan_data['rooms'] ?? []),
                $plan_data['total_construction_area'] ?? 0,
                $technical_details['construction_cost'] ?? 'TBD'
            );
            
            $notificationStmt->execute([
                ':user_id' => $plan['homeowner_id'],
                ':message' => $message,
                ':plan_id' => $plan_id
            ]);

            // Send inbox message for better visibility
            $inboxStmt = $db->prepare("
                INSERT INTO inbox_messages (recipient_id, sender_id, message_type, title, message, metadata, priority, created_at)
                VALUES (:recipient_id, :sender_id, 'plan_submitted', :title, :message, :metadata, 'high', CURRENT_TIMESTAMP)
            ");
            
            $metadata = json_encode([
                'plan_id' => $plan_id,
                'plan_name' => $plan['plan_name'],
                'total_rooms' => count($plan_data['rooms'] ?? []),
                'total_area' => $plan_data['total_construction_area'] ?? 0,
                'estimated_cost' => $technical_details['construction_cost'] ?? 'TBD',
                'construction_duration' => $technical_details['construction_duration'] ?? 'TBD',
                'architect_id' => $architect_id
            ]);
            
            $inboxStmt->execute([
                ':recipient_id' => $plan['homeowner_id'],
                ':sender_id' => $architect_id,
                ':title' => 'House Plan Ready for Review - ' . $plan['plan_name'],
                ':message' => $message,
                ':metadata' => $metadata
            ]);
        }

        $db->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'House plan with technical details submitted successfully',
            'plan_id' => $plan_id,
            'homeowner_id' => $plan['homeowner_id'],
            'layout_request_id' => $plan['layout_request_id']
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>