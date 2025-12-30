<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
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
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    $subject = trim($input['subject'] ?? '');
    $category = trim($input['category'] ?? 'general');
    $message = trim($input['message'] ?? '');
    
    if (empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
        exit;
    }
    
    // Create support_issues table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS support_issues (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            category VARCHAR(50) DEFAULT 'general',
            message TEXT NOT NULL,
            status VARCHAR(20) DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createTableSQL);
    
    // Insert the support issue
    $stmt = $pdo->prepare("
        INSERT INTO support_issues (user_id, subject, category, message, status) 
        VALUES (?, ?, ?, ?, 'open')
    ");
    
    $stmt->execute([$user_id, $subject, $category, $message]);
    $issue_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Support issue created successfully',
        'issue_id' => $issue_id
    ]);
    
} catch (Exception $e) {
    error_log("Support create_issue error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>