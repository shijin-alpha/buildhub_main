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
    if (!is_array($input)) { 
        $input = $_POST ?? []; 
    }

    $house_plan_id = isset($input['house_plan_id']) ? (int)$input['house_plan_id'] : 0;
    if ($house_plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'house_plan_id is required']);
        exit;
    }

    // Ensure the house plan belongs to this homeowner via layout_request
    $ownSql = "SELECT hp.id, hp.technical_details, lr.homeowner_id
               FROM house_plans hp
               INNER JOIN layout_requests lr ON hp.layout_request_id = lr.id
               WHERE hp.id = :hpid AND lr.homeowner_id = :uid";
    
    $stmt = $db->prepare($ownSql);
    $stmt->execute([':hpid' => $house_plan_id, ':uid' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'House plan not found for this user']);
        exit;
    }

    // Begin transaction for cleanup
    $db->beginTransaction();

    try {
        // Delete related records first
        
        // Delete technical details payments
        $deletePayments = $db->prepare('DELETE FROM technical_details_payments WHERE house_plan_id = :hpid AND homeowner_id = :uid');
        $deletePayments->execute([':hpid' => $house_plan_id, ':uid' => $user_id]);
        
        // Delete house plan reviews
        $deleteReviews = $db->prepare('DELETE FROM house_plan_reviews WHERE house_plan_id = :hpid AND homeowner_id = :uid');
        $deleteReviews->execute([':hpid' => $house_plan_id, ':uid' => $user_id]);
        
        // Delete notifications related to this house plan
        $deleteNotifications = $db->prepare('DELETE FROM notifications WHERE user_id = :uid AND related_id = :hpid AND type LIKE "%house_plan%"');
        $deleteNotifications->execute([':uid' => $user_id, ':hpid' => $house_plan_id]);
        
        // Delete inbox messages related to this house plan
        $deleteInbox = $db->prepare('DELETE FROM inbox_messages WHERE recipient_id = :uid AND JSON_EXTRACT(metadata, "$.plan_id") = :hpid');
        $deleteInbox->execute([':uid' => $user_id, ':hpid' => $house_plan_id]);
        
        // Attempt to delete associated files from disk
        $technical_details = json_decode($row['technical_details'], true);
        if (is_array($technical_details)) {
            $upload_dir = __DIR__ . '/../../uploads/house_plans/';
            
            // Delete layout image if exists
            if (isset($technical_details['layout_image']['stored'])) {
                $layout_file = $upload_dir . $technical_details['layout_image']['stored'];
                if (file_exists($layout_file)) {
                    @unlink($layout_file);
                }
            }
            
            // Delete other technical detail files
            $file_types = ['elevation_images', 'section_drawings', 'renders_3d'];
            foreach ($file_types as $file_type) {
                if (isset($technical_details[$file_type]) && is_array($technical_details[$file_type])) {
                    foreach ($technical_details[$file_type] as $file) {
                        if (isset($file['stored'])) {
                            $file_path = $upload_dir . $file['stored'];
                            if (file_exists($file_path)) {
                                @unlink($file_path);
                            }
                        }
                    }
                }
            }
        }
        
        // Finally, delete the house plan record
        $deleteHousePlan = $db->prepare('DELETE FROM house_plans WHERE id = :hpid');
        $deleteHousePlan->execute([':hpid' => $house_plan_id]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'House plan deleted successfully'
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error deleting house plan: ' . $e->getMessage()
    ]);
}
?>