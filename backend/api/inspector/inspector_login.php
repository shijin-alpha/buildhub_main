<?php
/**
 * Site Inspector Login API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit;
    }
    
    $email = trim($input['email']);
    $password = trim($input['password']);
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password cannot be empty']);
        exit;
    }
    
    // Find inspector by email
    $query = "SELECT id, first_name, last_name, email, password, role, is_verified, status 
              FROM users 
              WHERE email = ? AND role = 'site_inspector'";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    $inspector = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspector) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    
    // Check if inspector is verified and active
    if (!$inspector['is_verified']) {
        echo json_encode(['success' => false, 'message' => 'Your account is not yet verified. Please contact the administrator.']);
        exit;
    }
    
    // Check account status if status column exists
    if (isset($inspector['status']) && $inspector['status'] !== 'approved') {
        $statusMessage = match($inspector['status']) {
            'pending' => 'Your account is pending approval.',
            'rejected' => 'Your account has been rejected. Please contact the administrator.',
            'suspended' => 'Your account has been suspended. Please contact the administrator.',
            default => 'Your account is not active. Please contact the administrator.'
        };
        echo json_encode(['success' => false, 'message' => $statusMessage]);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $inspector['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    
    // Configure session for cross-origin requests
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Start session and set inspector data
    session_start();
    $_SESSION['user_id'] = $inspector['id'];
    $_SESSION['role'] = 'site_inspector';
    $_SESSION['inspector_logged_in'] = true;
    $_SESSION['inspector_name'] = $inspector['first_name'] . ' ' . $inspector['last_name'];
    $_SESSION['inspector_email'] = $inspector['email'];
    
    // Get inspector's assignment statistics
    $stats_query = "SELECT 
                      COUNT(*) as total_assignments,
                      COUNT(CASE WHEN sia.status = 'active' THEN 1 END) as active_assignments,
                      COUNT(CASE WHEN sia.status = 'completed' THEN 1 END) as completed_assignments
                    FROM site_inspector_assignments sia
                    WHERE sia.inspector_id = ?";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([$inspector['id']]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Remove password from response
    unset($inspector['password']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'inspector' => $inspector,
        'stats' => $stats,
        'redirect' => '/site-inspection'
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in inspector_login.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in inspector_login.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>