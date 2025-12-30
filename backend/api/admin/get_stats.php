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
    
    // Get system statistics
    $stats = [];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['totalUsers'] = $result['count'];
    
    // Active projects (you can adjust this query based on your project structure)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM layout_requests WHERE status != 'deleted'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['activeProjects'] = $result['count'];
    
    // Pending users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['pendingUsers'] = $result['count'];
    
    // Support issues
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM support_issues");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['supportIssues'] = $result['count'];
    
    // Open support issues
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM support_issues WHERE status = 'open'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['openSupportIssues'] = $result['count'];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Admin get_stats error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>