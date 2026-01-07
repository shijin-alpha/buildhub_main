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
    if (!is_array($input)) { $input = $_POST ?? []; }

    $house_plan_id = isset($input['house_plan_id']) ? (int)$input['house_plan_id'] : 0;
    if ($house_plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'house_plan_id is required']);
        exit;
    }

    // Verify the house plan belongs to this homeowner (via layout_request)
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

    // Begin transaction
    $db->beginTransaction();

    try {
        // Delete related records first
        
        // Delete house plan reviews
        $db->prepare('DELETE FROM house_plan_reviews WHERE house_plan_id = :hpid')
           ->execute([':hpid' => $house_plan_id]);

        // Delete technical details payments
        $db->prepare('DELETE FROM technical_details_payments WHERE house_plan_id = :hpid')
           ->execute([':hpid' => $house_plan_id]);

        // Delete notifications related to this house plan
        $db->prepare('DELETE FROM notifications WHERE related_id = :hpid AND type LIKE "%house_plan%"')
           ->execute([':hpid' => $house_plan_id]);

        // Delete inbox messages related to this house plan
        $db->prepare('DELETE FROM inbox_messages WHERE metadata LIKE :metadata')
           ->execute([':metadata' => '%"plan_id":' . $house_plan_id . '%']);

        // Delete uploaded files from disk
        $technical_details = json_decode($row['technical_details'], true);
        if ($technical_details) {
            $uploadDir = '../../uploads/house_plans/';
            
            // Delete layout image
            if (isset($technical_details['layout_image']['stored'])) {
                $filepath = $uploadDir . $technical_details['layout_image']['stored'];
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
            }
            
            // Delete other files
            $fileTypes = ['elevation_images', 'section_drawings', 'renders_3d'];
            foreach ($fileTypes as $fileType) {
                if (isset($technical_details[$fileType]) && is_array($technical_details[$fileType])) {
                    foreach ($technical_details[$fileType] as $file) {
                        if (isset($file['stored'])) {
                            $filepath = $uploadDir . $file['stored'];
                            if (file_exists($filepath)) {
                                @unlink($filepath);
                            }
                        }
                    }
                }
            }
        }

        // Finally, delete the house plan itself
        $del = $db->prepare('DELETE FROM house_plans WHERE id = :hpid');
        $del->execute([':hpid' => $house_plan_id]);

        $db->commit();

        echo json_encode(['success' => true, 'message' => 'House plan deleted successfully']);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error deleting house plan: ' . $e->getMessage()]);
}
?>