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
    $adminUserId = null;
    
    // Check for regular user admin session
    if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
        $isAdmin = true;
        $adminUserId = $_SESSION['user_id'];
    }
    // Check for admin-specific session
    elseif (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $isAdmin = true;
        $adminUserId = $_SESSION['admin_username'] ?? 'admin';
    }
    
    // Debug: Log session info
    error_log("Admin API - Session data: " . json_encode($_SESSION));
    error_log("Admin API - Admin access granted for: " . $adminUserId);
    error_log("Admin API - Is Admin: " . ($isAdmin ? 'true' : 'false'));
    
    if (!$isAdmin) {
        error_log("Admin API - Access denied. Session data: " . json_encode($_SESSION));
        echo json_encode(['success' => false, 'message' => 'Admin access required', 'debug' => [
            'session_data' => $_SESSION,
            'has_user_id' => isset($_SESSION['user_id']),
            'has_role' => isset($_SESSION['role']),
            'has_admin_logged_in' => isset($_SESSION['admin_logged_in']),
            'admin_logged_in_value' => $_SESSION['admin_logged_in'] ?? null
        ]]);
        exit;
    }
    
    // Get all support issues with user information
    $stmt = $pdo->prepare("
        SELECT si.*, u.first_name, u.last_name, u.email, u.role,
               (SELECT COUNT(*) FROM support_replies sr WHERE sr.issue_id = si.id) as reply_count
        FROM support_issues si 
        JOIN users u ON si.user_id = u.id 
        ORDER BY si.updated_at DESC, si.created_at DESC
    ");
    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Admin API - Found " . count($issues) . " issues");
    
    echo json_encode([
        'success' => true,
        'issues' => $issues,
        'debug' => [
            'count' => count($issues),
            'admin_user' => $adminUserId,
            'session_type' => isset($_SESSION['user_id']) ? 'regular_admin' : 'admin_login'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Admin get_support_issues error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>