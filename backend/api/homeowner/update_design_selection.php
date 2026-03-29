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

    $payload = json_decode(file_get_contents('php://input'), true);
    $design_id = $payload['design_id'] ?? null;
    $action = $payload['action'] ?? null; // 'shortlist' | 'remove-shortlist' | 'finalize'

    if (!$design_id || !in_array($action, ['shortlist','remove-shortlist','finalize'], true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }

    // Check ownership: design must belong to this homeowner either directly or via their request
    $check = $db->prepare("SELECT d.id, d.status, d.layout_request_id, d.homeowner_id
                            FROM designs d
                            LEFT JOIN layout_requests lr ON lr.id = d.layout_request_id
                            WHERE d.id = :id AND (d.homeowner_id = :uid1 OR lr.homeowner_id = :uid2)");
    $check->bindParam(':id', $design_id, PDO::PARAM_INT);
    $check->bindParam(':uid1', $user_id, PDO::PARAM_INT);
    $check->bindParam(':uid2', $user_id, PDO::PARAM_INT);
    $check->execute();
    $design = $check->fetch(PDO::FETCH_ASSOC);

    if (!$design) {
        echo json_encode(['success' => false, 'message' => 'Design not found or access denied']);
        exit;
    }

    $db->beginTransaction();

    if ($action === 'shortlist') {
        $stmt = $db->prepare("UPDATE designs SET status = 'shortlisted' WHERE id = :id");
        $stmt->bindParam(':id', $design_id, PDO::PARAM_INT);
        $stmt->execute();
    } elseif ($action === 'remove-shortlist') {
        $stmt = $db->prepare("UPDATE designs SET status = 'proposed' WHERE id = :id");
        $stmt->bindParam(':id', $design_id, PDO::PARAM_INT);
        $stmt->execute();
    } else { // finalize
        // Finalize this one
        $stmt = $db->prepare("UPDATE designs SET status = 'finalized' WHERE id = :id");
        $stmt->bindParam(':id', $design_id, PDO::PARAM_INT);
        $stmt->execute();

        // If linked to a request, un-finalize others of the same request; otherwise, same homeowner
        if (!empty($design['layout_request_id'])) {
            $stmt2 = $db->prepare("UPDATE designs SET status = CASE WHEN status = 'finalized' AND id <> :id THEN 'shortlisted' ELSE status END WHERE layout_request_id = :rid");
            $stmt2->bindParam(':id', $design_id, PDO::PARAM_INT);
            $stmt2->bindParam(':rid', $design['layout_request_id'], PDO::PARAM_INT);
            $stmt2->execute();
        } else {
            $stmt2 = $db->prepare("UPDATE designs SET status = CASE WHEN status = 'finalized' AND id <> :id THEN 'shortlisted' ELSE status END WHERE homeowner_id = :hid");
            $stmt2->bindParam(':id', $design_id, PDO::PARAM_INT);
            $stmt2->bindParam(':hid', $user_id, PDO::PARAM_INT);
            $stmt2->execute();
        }
    }

    $db->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error updating selection: ' . $e->getMessage()]);
}