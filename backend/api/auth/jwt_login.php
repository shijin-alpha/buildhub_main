<?php
/**
 * JWT Login Endpoint
 * Handles user authentication and JWT token generation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/JWTManager.php';

// Rate limiting
$rateLimitFile = __DIR__ . '/../../temp/login_rate_limit.json';
$maxAttempts = 5;
$windowMinutes = 15;

function checkRateLimit($email, $rateLimitFile, $maxAttempts, $windowMinutes) {
    if (!file_exists($rateLimitFile)) {
        file_put_contents($rateLimitFile, json_encode([]));
    }
    
    $rateLimits = json_decode(file_get_contents($rateLimitFile), true);
    $now = time();
    $windowStart = $now - ($windowMinutes * 60);
    
    // Clean old entries
    foreach ($rateLimits as $key => $data) {
        if ($data['timestamp'] < $windowStart) {
            unset($rateLimits[$key]);
        }
    }
    
    // Count attempts for this email
    $attempts = 0;
    foreach ($rateLimits as $data) {
        if ($data['email'] === $email && $data['timestamp'] >= $windowStart) {
            $attempts++;
        }
    }
    
    if ($attempts >= $maxAttempts) {
        return false;
    }
    
    // Log this attempt
    $rateLimits[] = [
        'email' => $email,
        'timestamp' => $now
    ];
    
    file_put_contents($rateLimitFile, json_encode($rateLimits));
    return true;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        throw new Exception('Email and password are required');
    }
    
    $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
    $password = $input['password'];
    
    if (!$email) {
        throw new Exception('Invalid email format');
    }
    
    // Check rate limiting
    if (!checkRateLimit($email, $rateLimitFile, $maxAttempts, $windowMinutes)) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => "Too many login attempts. Please try again in {$windowMinutes} minutes.",
            'code' => 'RATE_LIMIT_EXCEEDED'
        ]);
        exit;
    }
    
    // Database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get user
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email, password, role, status, admin_scope, is_verified, 
               login_attempts, locked_until
        FROM users 
        WHERE email = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Invalid email or password');
    }
    
    // Check if account is locked
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        throw new Exception('Account is temporarily locked. Please try again later.');
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Increment login attempts
        $attempts = $user['login_attempts'] + 1;
        $lockUntil = null;
        
        if ($attempts >= 5) {
            $lockUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        }
        
        $stmt = $conn->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?");
        $stmt->execute([$attempts, $lockUntil, $user['id']]);
        
        throw new Exception('Invalid email or password');
    }
    
    // Check user status
    if ($user['status'] !== 'approved') {
        throw new Exception('Account is not approved. Please contact administrator.');
    }
    
    // Reset login attempts on successful login
    $stmt = $conn->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Generate JWT tokens
    $jwtManager = new JWTManager($database);
    
    $additionalClaims = [];
    if ($user['admin_scope']) {
        $additionalClaims['admin_scope'] = $user['admin_scope'];
    }
    
    $tokens = $jwtManager->generateTokens(
        $user['id'],
        $user['role'],
        $user['email'],
        $additionalClaims
    );
    
    // Log successful login
    $stmt = $conn->prepare("
        INSERT INTO auth_audit_log (user_id, action, endpoint, ip_address, user_agent, success, created_at) 
        VALUES (?, 'login', ?, ?, ?, TRUE, NOW())
    ");
    $stmt->execute([
        $user['id'],
        $_SERVER['REQUEST_URI'] ?? '/api/auth/jwt_login.php',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'admin_scope' => $user['admin_scope'],
            'is_verified' => (bool)$user['is_verified']
        ],
        'tokens' => $tokens
    ]);
    
} catch (Exception $e) {
    // Log failed login attempt
    if (isset($email) && isset($database)) {
        $stmt = $conn->prepare("
            INSERT INTO auth_audit_log (user_id, action, endpoint, ip_address, user_agent, success, error_message, created_at) 
            VALUES (NULL, 'login_failed', ?, ?, ?, FALSE, ?, NOW())
        ");
        $stmt->execute([
            $_SERVER['REQUEST_URI'] ?? '/api/auth/jwt_login.php',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $e->getMessage()
        ]);
    }
    
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'LOGIN_FAILED'
    ]);
}
?>