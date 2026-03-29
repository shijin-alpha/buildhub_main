<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    
    $issue_id = $_GET['issue_id'] ?? null;
    
    if (!$issue_id) {
        echo json_encode(['success' => false, 'message' => 'Issue ID is required']);
        exit;
    }
    
    // Get the issue details
    $stmt = $pdo->prepare("
        SELECT si.*, u.first_name, u.last_name, u.email, u.role 
        FROM support_issues si 
        JOIN users u ON si.user_id = u.id 
        WHERE si.id = ?
    ");
    $stmt->execute([$issue_id]);
    $issue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$issue) {
        echo json_encode(['success' => false, 'message' => 'Issue not found']);
        exit;
    }
    
    // Get replies for this issue
    $repliesStmt = $pdo->prepare("
        SELECT * FROM support_replies 
        WHERE issue_id = ? 
        ORDER BY created_at ASC
    ");
    $repliesStmt->execute([$issue_id]);
    $replies = $repliesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'issue' => $issue,
        'replies' => $replies
    ]);
    
} catch (Exception $e) {
    error_log("Admin get_support_thread error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>