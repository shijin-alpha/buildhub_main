<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// Database connection
require_once '../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if user is logged in and is admin
    $isAdmin = false;
    
    // Check for regular user admin session
    if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
        $isAdmin = true;
    }
    // Check for admin-specific session
    elseif (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $isAdmin = true;
    }
    
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    $issue_id = $input['issue_id'] ?? null;
    $message = trim($input['message'] ?? '');
    
    if (!$issue_id || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Issue ID and message are required']);
        exit;
    }
    
    // Create support_replies table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS support_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            issue_id INT NOT NULL,
            sender VARCHAR(20) NOT NULL DEFAULT 'admin',
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (issue_id) REFERENCES support_issues(id) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createTableSQL);
    
    // Insert the admin reply
    $stmt = $pdo->prepare("
        INSERT INTO support_replies (issue_id, sender, message) 
        VALUES (?, 'admin', ?)
    ");
    
    $stmt->execute([$issue_id, $message]);
    
    // Update the issue status and updated_at
    $updateStmt = $pdo->prepare("
        UPDATE support_issues 
        SET status = 'replied', updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $updateStmt->execute([$issue_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Reply sent successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Support admin_reply error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>