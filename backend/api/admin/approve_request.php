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
    
    // Check admin authentication
    $isAdmin = false;
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        $isAdmin = true;
    } elseif (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $isAdmin = true;
    }

    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;
    $action = isset($input['action']) ? strtolower(trim($input['action'])) : '';
    $admin_notes = isset($input['admin_notes']) ? trim($input['admin_notes']) : '';

    if ($request_id <= 0 || !in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
        exit;
    }

    // Verify request exists and is pending
    $checkStmt = $db->prepare("SELECT id, user_id, homeowner_id, status FROM layout_requests WHERE id = :id AND status = 'pending'");
    $checkStmt->execute([':id' => $request_id]);
    $request = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
        exit;
    }

    // Update request status
    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $updateStmt = $db->prepare("UPDATE layout_requests SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $updateResult = $updateStmt->execute([':status' => $newStatus, ':id' => $request_id]);

    if (!$updateResult) {
        echo json_encode(['success' => false, 'message' => 'Failed to update request status']);
        exit;
    }

    // Log admin action
    $logStmt = $db->prepare("INSERT INTO admin_logs (action, user_id, details, created_at) VALUES ('request_approval', :admin_id, :details, CURRENT_TIMESTAMP)");
    $adminId = $_SESSION['user_id'] ?? 0;
    $logDetails = json_encode([
        'request_id' => $request_id,
        'homeowner_id' => $request['homeowner_id'],
        'action' => $action,
        'old_status' => 'pending',
        'new_status' => $newStatus,
        'admin_notes' => $admin_notes
    ]);
    $logStmt->execute([':admin_id' => $adminId, ':details' => $logDetails]);

    // Create notification for homeowner
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(user_id)
        )");

        $notificationTitle = $action === 'approve' ? 'Request Approved' : 'Request Rejected';
        $notificationMessage = $action === 'approve' 
            ? 'Your house design request has been approved and is now available to architects.'
            : 'Your house design request has been rejected. ' . ($admin_notes ? 'Reason: ' . $admin_notes : '');

        $notifyStmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (:user_id, 'request_status', :title, :message, CURRENT_TIMESTAMP)");
        $notifyStmt->execute([
            ':user_id' => $request['homeowner_id'],
            ':title' => $notificationTitle,
            ':message' => $notificationMessage
        ]);
    } catch (Exception $e) {
        // Notification creation failed, but don't fail the main operation
        error_log("Failed to create notification: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => "Request {$action}d successfully",
        'request_id' => $request_id,
        'new_status' => $newStatus
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing request: ' . $e->getMessage()
    ]);
}
?>