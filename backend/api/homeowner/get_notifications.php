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
    
    // Create notifications table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            related_id INT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createTableSQL);
    
    // Create homeowner_notifications table if it doesn't exist
    $createHomeownerNotificationsSQL = "
        CREATE TABLE IF NOT EXISTS homeowner_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            homeowner_id INT NOT NULL,
            contractor_id INT NULL,
            type VARCHAR(50) DEFAULT 'acknowledgment',
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('unread', 'read') DEFAULT 'unread',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(homeowner_id), INDEX(status), INDEX(type)
        )
    ";
    $pdo->exec($createHomeownerNotificationsSQL);
    
    // Get all notifications for the user from both tables
    $stmt = $pdo->prepare("
        SELECT 
            id,
            type,
            title,
            message,
            related_id,
            CASE WHEN is_read = 1 THEN 1 ELSE 0 END as is_read,
            created_at,
            'general' as source
        FROM notifications 
        WHERE user_id = ? 
        
        UNION ALL
        
        SELECT 
            id,
            type,
            title,
            message,
            contractor_id as related_id,
            CASE WHEN status = 'read' THEN 1 ELSE 0 END as is_read,
            created_at,
            'contractor_acknowledgment' as source
        FROM homeowner_notifications 
        WHERE homeowner_id = ?
        
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$user_id, $user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unread count from both tables
    $unreadStmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE) +
            (SELECT COUNT(*) FROM homeowner_notifications WHERE homeowner_id = ? AND status = 'unread') 
            as unread_count
    ");
    $unreadStmt->execute([$user_id, $user_id]);
    $unreadResult = $unreadStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get counts by type for badges from both tables
    $countsStmt = $pdo->prepare("
        SELECT 
            type,
            COUNT(*) as count,
            SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread_count
        FROM notifications 
        WHERE user_id = ? 
        GROUP BY type
        
        UNION ALL
        
        SELECT 
            type,
            COUNT(*) as count,
            SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread_count
        FROM homeowner_notifications 
        WHERE homeowner_id = ? 
        GROUP BY type
    ");
    $countsStmt->execute([$user_id, $user_id]);
    $counts = $countsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format counts for easy access
    $badgeCounts = [];
    foreach ($counts as $count) {
        $badgeCounts[$count['type']] = [
            'total' => $count['count'],
            'unread' => $count['unread_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadResult['unread_count'],
        'badge_counts' => $badgeCounts
    ]);
    
} catch (Exception $e) {
    error_log("Get notifications error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>