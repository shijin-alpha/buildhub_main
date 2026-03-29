<?php
/**
 * JWT Refresh Token Endpoint
 * Handles access token refresh using refresh tokens
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

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['refresh_token'])) {
        throw new Exception('Refresh token is required');
    }
    
    $refreshToken = $input['refresh_token'];
    
    // Initialize JWT manager
    $database = new Database();
    $jwtManager = new JWTManager($database);
    
    // Refresh the access token
    $tokens = $jwtManager->refreshAccessToken($refreshToken);
    
    // Log successful token refresh
    $conn = $database->getConnection();
    $payload = $jwtManager->validateRefreshToken($refreshToken);
    
    $stmt = $conn->prepare("
        INSERT INTO auth_audit_log (user_id, action, endpoint, ip_address, user_agent, success, created_at) 
        VALUES (?, 'token_refresh', ?, ?, ?, TRUE, NOW())
    ");
    $stmt->execute([
        $payload['user_id'],
        $_SERVER['REQUEST_URI'] ?? '/api/auth/jwt_refresh.php',
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Token refreshed successfully',
        'tokens' => $tokens
    ]);
    
} catch (Exception $e) {
    // Log failed refresh attempt
    if (isset($database)) {
        $conn = $database->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO auth_audit_log (user_id, action, endpoint, ip_address, user_agent, success, error_message, created_at) 
            VALUES (NULL, 'token_refresh_failed', ?, ?, ?, FALSE, ?, NOW())
        ");
        $stmt->execute([
            $_SERVER['REQUEST_URI'] ?? '/api/auth/jwt_refresh.php',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $e->getMessage()
        ]);
    }
    
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'TOKEN_REFRESH_FAILED'
    ]);
}
?>