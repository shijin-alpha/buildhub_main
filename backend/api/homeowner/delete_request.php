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
    
    // Get homeowner ID from session
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }
    
    // Get request data from POST body
    $input = json_decode(file_get_contents('php://input'), true);
    $layout_request_id = $input['layout_request_id'] ?? null;
    
    if (!$layout_request_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Layout request ID is required'
        ]);
        exit;
    }
    
    // Verify that the request belongs to this homeowner
    $checkQuery = "SELECT id FROM layout_requests WHERE id = :request_id AND user_id = :homeowner_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':request_id', $layout_request_id);
    $checkStmt->bindParam(':homeowner_id', $homeowner_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Request not found or access denied'
        ]);
        exit;
    }
    
    // Soft delete the request by setting status to 'deleted'
    $deleteQuery = "UPDATE layout_requests SET status = 'deleted', updated_at = CURRENT_TIMESTAMP WHERE id = :request_id AND user_id = :homeowner_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':request_id', $layout_request_id);
    $deleteStmt->bindParam(':homeowner_id', $homeowner_id);
    
    if ($deleteStmt->execute()) {
        // Also delete any related assignments
        $deleteAssignmentsQuery = "DELETE FROM layout_request_assignments WHERE layout_request_id = :request_id";
        $deleteAssignmentsStmt = $db->prepare($deleteAssignmentsQuery);
        $deleteAssignmentsStmt->bindParam(':request_id', $layout_request_id);
        $deleteAssignmentsStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Request deleted successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete request'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting request: ' . $e->getMessage()
    ]);
}
?>





















