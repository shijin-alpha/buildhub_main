<?php
/**
 * JWT Token Verification Endpoint
 * Verifies JWT token and returns user information
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../middleware/JWTAuthMiddleware.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Only GET method allowed');
    }
    
    // Initialize middleware
    $auth = new JWTAuthMiddleware();
    
    // Authenticate user
    $user = $auth->authenticate();
    if (!$user) {
        exit; // Authentication middleware handles the response
    }
    
    $payload = JWTAuthMiddleware::getJWTPayload();
    
    echo json_encode([
        'success' => true,
        'message' => 'Token is valid',
        'user' => [
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'admin_scope' => $user['admin_scope'],
            'is_verified' => (bool)$user['is_verified']
        ],
        'token_info' => [
            'issued_at' => $payload['iat'],
            'expires_at' => $payload['exp'],
            'token_id' => $payload['jti']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'TOKEN_VERIFICATION_FAILED'
    ]);
}
?>