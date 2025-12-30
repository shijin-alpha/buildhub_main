<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { 
    header('Access-Control-Allow-Origin: ' . $origin); 
    header('Vary: Origin'); 
} else { 
    header('Access-Control-Allow-Origin: http://localhost:3000'); 
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    header('Access-Control-Max-Age: 86400'); 
    exit; 
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $estimate_id = isset($input['estimate_id']) ? (int)$input['estimate_id'] : 0;
    $contractor_id = isset($input['contractor_id']) ? (int)$input['contractor_id'] : 0;

    // Validation
    if ($estimate_id <= 0 || $contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing estimate_id or contractor_id']);
        exit;
    }

    // Verify the estimate belongs to this contractor
    $checkStmt = $db->prepare("
        SELECT id FROM contractor_send_estimates 
        WHERE id = :estimate_id AND contractor_id = :contractor_id
    ");
    $checkStmt->bindValue(':estimate_id', $estimate_id, PDO::PARAM_INT);
    $checkStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $checkStmt->execute();
    $estimate = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$estimate) {
        echo json_encode(['success' => false, 'message' => 'Estimate not found or unauthorized']);
        exit;
    }

    // Soft delete the estimate by setting status to 'deleted'
    $deleteStmt = $db->prepare("
        UPDATE contractor_send_estimates 
        SET status = 'deleted'
        WHERE id = :estimate_id AND contractor_id = :contractor_id
    ");
    $deleteStmt->bindValue(':estimate_id', $estimate_id, PDO::PARAM_INT);
    $deleteStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    
    if ($deleteStmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Estimate removed successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove estimate']);
    }

} catch (Exception $e) {
    error_log("Delete contractor estimate error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>