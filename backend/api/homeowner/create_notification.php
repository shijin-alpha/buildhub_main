<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    $user_id = $input['user_id'] ?? null;
    $type = $input['type'] ?? null;
    $title = $input['title'] ?? null;
    $message = $input['message'] ?? null;
    $related_id = $input['related_id'] ?? null;
    
    if (!$user_id || !$type || !$title || !$message) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
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
    
    // Insert notification
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message, related_id) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $type, $title, $message, $related_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Notification created successfully',
        'notification_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log("Create notification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>