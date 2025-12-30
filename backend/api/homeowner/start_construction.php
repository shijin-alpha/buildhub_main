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
    $homeowner_id = isset($input['homeowner_id']) ? (int)$input['homeowner_id'] : 0;

    // Validation
    if ($estimate_id <= 0 || $homeowner_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing estimate_id or homeowner_id']);
        exit;
    }

    // Verify the estimate belongs to this homeowner and is in 'accepted' status
    $checkStmt = $db->prepare("
        SELECT cse.id, cse.contractor_id, cse.status, cls.homeowner_id,
               u.first_name, u.last_name, u.email
        FROM contractor_send_estimates cse
        INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        LEFT JOIN users u ON cse.contractor_id = u.id
        WHERE cse.id = :estimate_id 
        AND cls.homeowner_id = :homeowner_id
    ");
    $checkStmt->bindValue(':estimate_id', $estimate_id, PDO::PARAM_INT);
    $checkStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $checkStmt->execute();
    $estimate = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$estimate) {
        echo json_encode(['success' => false, 'message' => 'Estimate not found or unauthorized']);
        exit;
    }

    // Check if already in construction phase
    if ($estimate['status'] === 'construction_started' || $estimate['status'] === 'in_progress') {
        echo json_encode(['success' => false, 'message' => 'Construction has already been started for this project']);
        exit;
    }

    // Check if estimate is accepted
    if ($estimate['status'] !== 'accepted') {
        echo json_encode(['success' => false, 'message' => 'Estimate must be accepted before starting construction']);
        exit;
    }

    // Update estimate status to 'construction_started'
    $updateStmt = $db->prepare("
        UPDATE contractor_send_estimates 
        SET status = 'construction_started'
        WHERE id = :estimate_id
    ");
    $updateStmt->bindValue(':estimate_id', $estimate_id, PDO::PARAM_INT);
    
    if ($updateStmt->execute()) {
        // Create notification for contractor
        $contractor_id = $estimate['contractor_id'];
        $contractor_name = $estimate['first_name'] . ' ' . $estimate['last_name'];
        
        // Insert notification (if you have a notifications table)
        try {
            $notificationStmt = $db->prepare("
                INSERT INTO progress_notifications (
                    progress_update_id, homeowner_id, contractor_id, type, title, message, status
                ) VALUES (
                    :estimate_id, :homeowner_id, :contractor_id, 'progress_update', 
                    'Construction Started', 
                    'The homeowner has started construction. You can now submit progress updates.',
                    'unread'
                )
            ");
            $notificationStmt->bindValue(':estimate_id', $estimate_id, PDO::PARAM_INT);
            $notificationStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
            $notificationStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
            $notificationStmt->execute();
        } catch (Exception $e) {
            // Notification table might not exist, continue anyway
            error_log("Notification creation failed: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Construction started successfully',
            'data' => [
                'estimate_id' => $estimate_id,
                'status' => 'construction_started',
                'contractor_name' => $contractor_name,
                'contractor_email' => $estimate['email']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to start construction']);
    }

} catch (Exception $e) {
    error_log("Start construction error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>
