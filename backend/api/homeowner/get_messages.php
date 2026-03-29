<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    
    // Create messages table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            from_user_id INT NOT NULL,
            to_user_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            message_type VARCHAR(50) DEFAULT 'general',
            related_id INT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createTableSQL);
    
    // Get all messages for the user (both sent and received)
    $stmt = $pdo->prepare("
        SELECT 
            m.*,
            sender.first_name as sender_first_name,
            sender.last_name as sender_last_name,
            sender.email as sender_email,
            sender.role as sender_role,
            receiver.first_name as receiver_first_name,
            receiver.last_name as receiver_last_name,
            receiver.email as receiver_email,
            receiver.role as receiver_role
        FROM messages m
        LEFT JOIN users sender ON m.from_user_id = sender.id
        LEFT JOIN users receiver ON m.to_user_id = receiver.id
        WHERE m.from_user_id = ? OR m.to_user_id = ?
        ORDER BY m.created_at DESC
        LIMIT 100
    ");
    $stmt->execute([$user_id, $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unread count
    $unreadStmt = $pdo->prepare("
        SELECT COUNT(*) as unread_count 
        FROM messages 
        WHERE to_user_id = ? AND is_read = FALSE
    ");
    $unreadStmt->execute([$user_id]);
    $unreadResult = $unreadStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get conversation threads (group by participants)
    $threadsStmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN m.from_user_id = ? THEN m.to_user_id 
                ELSE m.from_user_id 
            END as other_user_id,
            MAX(m.created_at) as last_message_time,
            COUNT(*) as message_count,
            SUM(CASE WHEN m.to_user_id = ? AND m.is_read = FALSE THEN 1 ELSE 0 END) as unread_count,
            u.first_name,
            u.last_name,
            u.email,
            u.role
        FROM messages m
        LEFT JOIN users u ON (
            CASE 
                WHEN m.from_user_id = ? THEN m.to_user_id = u.id
                ELSE m.from_user_id = u.id
            END
        )
        WHERE m.from_user_id = ? OR m.to_user_id = ?
        GROUP BY other_user_id
        ORDER BY last_message_time DESC
    ");
    $threadsStmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
    $threads = $threadsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'threads' => $threads,
        'unread_count' => $unreadResult['unread_count']
    ]);
    
} catch (Exception $e) {
    error_log("Get messages error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>