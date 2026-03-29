<?php
/**
 * JWT Logout Endpoint
 * Handles token blacklisting for secure logout
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
require_once __DIR__ . '/../../middleware/JWTAuthMiddleware.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Initialize middleware and JWT manager
    $auth = new JWTAuthMiddleware();
    $database = new Database();
    $jwtManager = new JWTManager($database);
    
    // Authenticate user
    $user = $auth->authenticate();
    if (!$user) {
        exit; // Authentication middleware handles the response
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $logoutAll = isset($input['logout_all']) && $input['logout_all'] === true;
    
    if ($logoutAll) {
        // Logout from all devices
        $jwtManager->blacklistAllUserTokens($user['id']);
        $message = 'Logged out from all devices successfully';
        $action = 'logout_all';
    } else {
        // Logout from current device only
        $payload = JWTAuthMiddleware::getJWTPayload();
        $jwtManager->blacklistToken($payload['jti']);
        $message = 'Logged out successfully';
        $action = 'logout';
    }
    
    // Log logout
    $conn = $database->getConnection();
    $stmt = $conn->prepare("
        INSERT INTO auth_audit_log (user_id, action, endpoint, ip_address, user_agent, success, created_at) 
        VALUES (?, ?, ?, ?, ?, TRUE, NOW())
    ");
    $stmt->execute([
        $user['id'],
        $action,
        $_SERVER['REQUEST_URI'] ?? '/api/auth/jwt_logout.php',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'LOGOUT_FAILED'
    ]);
}
?>