<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Database connection
require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['notification_ids']) && is_array($input['notification_ids'])) {
        // Mark specific notifications as read
        // We need to handle both tables, so we'll check the source
        if (isset($input['source']) && is_array($input['source'])) {
            // Handle notifications with source information
            foreach ($input['notification_ids'] as $index => $notificationId) {
                $source = $input['source'][$index] ?? 'general';
                
                if ($source === 'contractor_acknowledgment') {
                    // Update homeowner_notifications table
                    $stmt = $pdo->prepare("
                        UPDATE homeowner_notifications 
                        SET status = 'read' 
                        WHERE homeowner_id = ? AND id = ?
                    ");
                    $stmt->execute([$user_id, $notificationId]);
                } else {
                    // Update general notifications table
                    $stmt = $pdo->prepare("
                        UPDATE notifications 
                        SET is_read = TRUE 
                        WHERE user_id = ? AND id = ?
                    ");
                    $stmt->execute([$user_id, $notificationId]);
                }
            }
        } else {
            // Fallback: try to update both tables
            $placeholders = str_repeat('?,', count($input['notification_ids']) - 1) . '?';
            
            // Update general notifications
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE user_id = ? AND id IN ($placeholders)
            ");
            $params = array_merge([$user_id], $input['notification_ids']);
            $stmt->execute($params);
            
            // Update homeowner notifications
            $stmt2 = $pdo->prepare("
                UPDATE homeowner_notifications 
                SET status = 'read' 
                WHERE homeowner_id = ? AND id IN ($placeholders)
            ");
            $stmt2->execute($params);
        }
    } elseif (isset($input['type'])) {
        // Mark all notifications of a specific type as read in both tables
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE user_id = ? AND type = ?
        ");
        $stmt->execute([$user_id, $input['type']]);
        
        $stmt2 = $pdo->prepare("
            UPDATE homeowner_notifications 
            SET status = 'read' 
            WHERE homeowner_id = ? AND type = ?
        ");
        $stmt2->execute([$user_id, $input['type']]);
    } else {
        // Mark all notifications as read in both tables
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        
        $stmt2 = $pdo->prepare("
            UPDATE homeowner_notifications 
            SET status = 'read' 
            WHERE homeowner_id = ?
        ");
        $stmt2->execute([$user_id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Notifications marked as read'
    ]);
    
} catch (Exception $e) {
    error_log("Mark notifications read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>