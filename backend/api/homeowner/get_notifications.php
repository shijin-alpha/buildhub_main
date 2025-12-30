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
    
    // Get all notifications for the user
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unread count
    $unreadStmt = $pdo->prepare("
        SELECT COUNT(*) as unread_count 
        FROM notifications 
        WHERE user_id = ? AND is_read = FALSE
    ");
    $unreadStmt->execute([$user_id]);
    $unreadResult = $unreadStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get counts by type for badges
    $countsStmt = $pdo->prepare("
        SELECT 
            type,
            COUNT(*) as count,
            SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread_count
        FROM notifications 
        WHERE user_id = ? 
        GROUP BY type
    ");
    $countsStmt->execute([$user_id]);
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