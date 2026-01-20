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
    
    $contractor_id = $_SESSION['user_id'];
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['homeowner_id']) || !isset($input['layout_title'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $homeowner_id = $input['homeowner_id'];
    $layout_title = $input['layout_title'];
    $due_date = $input['due_date'] ?? null;
    
    // Get contractor details
    $contractorStmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $contractorStmt->execute([$contractor_id]);
    $contractor = $contractorStmt->fetch(PDO::FETCH_ASSOC);
    $contractor_name = trim(($contractor['first_name'] ?? '') . ' ' . ($contractor['last_name'] ?? '')) ?: 'Contractor';
    
    // Create messages table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            from_user_id INT NOT NULL,
            to_user_id INT NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            message_type VARCHAR(50) DEFAULT 'acknowledgment',
            related_id INT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createTableSQL);
    
    // Create the acknowledgment message
    $subject = "Layout Request Acknowledged - {$layout_title}";
    $due_text = $due_date ? "Expected completion: " . date('F j, Y', strtotime($due_date)) : "Due date to be confirmed";
    $message_text = "Hello! I have acknowledged your layout request for '{$layout_title}' and will begin working on your estimate. {$due_text}. I'll keep you updated on the progress.";
    
    // Insert the message
    $stmt = $pdo->prepare("
        INSERT INTO messages (from_user_id, to_user_id, subject, message, message_type, created_at) 
        VALUES (?, ?, ?, ?, 'acknowledgment', NOW())
    ");
    $stmt->execute([$contractor_id, $homeowner_id, $subject, $message_text]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Acknowledgment message sent successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Send acknowledgment message error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>