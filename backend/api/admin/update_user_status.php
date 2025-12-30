<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['user_id']) || !isset($input['status'])) {
        echo json_encode(['success' => false, 'message' => 'User ID and status are required']);
        exit;
    }

    $userId = $input['user_id'];
    $newStatus = $input['status'];

    // Validate status
    $allowedStatuses = ['pending', 'approved', 'rejected', 'suspended'];
    if (!in_array($newStatus, $allowedStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Check if users.status column exists
    $colStmt = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
    $hasStatus = $colStmt && $colStmt->rowCount() > 0;

    // Fetch user (avoid selecting missing columns)
    $checkQuery = $hasStatus
        ? "SELECT id, first_name, last_name, email, role, status, is_verified FROM users WHERE id = :user_id"
        : "SELECT id, first_name, last_name, email, role, is_verified FROM users WHERE id = :user_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $userId);
    $checkStmt->execute();
    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Use transaction to ensure data consistency
    $db->beginTransaction();
    
    try {
        // Perform update depending on schema
        if ($hasStatus) {
            $updateQuery = "UPDATE users SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':status', $newStatus);
            $updateStmt->bindParam(':user_id', $userId);
        } else {
            // Fallback: map statuses to is_verified where status column is absent
            // approved -> 1, others -> 0 (suspended/pending/rejected not distinguished in minimal schema)
            $isVerified = ($newStatus === 'approved') ? 1 : 0;
            $updateQuery = "UPDATE users SET is_verified = :is_verified, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':is_verified', $isVerified, PDO::PARAM_INT);
            $updateStmt->bindParam(':user_id', $userId);
        }

        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update user status");
        }
        
        // Verify the update was successful
        $verifyQuery = $hasStatus
            ? "SELECT status, is_verified FROM users WHERE id = :user_id"
            : "SELECT is_verified FROM users WHERE id = :user_id";
        $verifyStmt = $db->prepare($verifyQuery);
        $verifyStmt->bindParam(':user_id', $userId);
        $verifyStmt->execute();
        $verifyUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$verifyUser) {
            throw new Exception("User not found after update");
        }
        
        if ($hasStatus && $verifyUser['status'] !== $newStatus) {
            throw new Exception("Status update verification failed");
        }
        
        if (!$hasStatus) {
            $expectedVerified = ($newStatus === 'approved') ? 1 : 0;
            if ($verifyUser['is_verified'] != $expectedVerified) {
                throw new Exception("Verification status update verification failed");
            }
        }
        
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    if (true) { // Always true since we're in the try block
        // Create admin_logs if needed and record
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS admin_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                action VARCHAR(100) NOT NULL,
                user_id INT NOT NULL,
                details TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )");
            $logStmt = $db->prepare("INSERT INTO admin_logs (action, user_id, details, created_at) VALUES ('status_change', :user_id, :details, CURRENT_TIMESTAMP)");
            $logStmt->bindParam(':user_id', $userId);
            $prevStatus = $hasStatus ? ($user['status'] ?? (($user['is_verified'] ?? 0) ? 'approved' : 'pending')) : (($user['is_verified'] ?? 0) ? 'approved' : 'pending');
            $logDetails = json_encode([
                'old_status' => $prevStatus,
                'new_status' => $newStatus,
                'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                'user_email' => $user['email'],
                'user_role' => $user['role'],
                'schema_has_status' => $hasStatus
            ]);
            $logStmt->bindParam(':details', $logDetails);
            $logStmt->execute();
        } catch (Exception $logError) {
            error_log("Failed to log admin action: " . $logError->getMessage());
        }

        $statusMessages = [
            'approved' => 'User has been approved successfully',
            'rejected' => 'User has been rejected',
            'suspended' => 'User has been suspended',
            'pending' => 'User status has been set to pending'
        ];

        echo json_encode([
            'success' => true,
            'message' => $statusMessages[$newStatus] ?? 'User status updated successfully',
            'user' => [
                'id' => $user['id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'old_status' => $prevStatus,
                'new_status' => $newStatus
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating user status: ' . $e->getMessage()
    ]);
}
?>