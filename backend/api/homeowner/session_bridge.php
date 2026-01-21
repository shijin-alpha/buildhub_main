<?php
/**
 * Session Bridge for Homeowner Authentication
 * This API establishes a proper session for the homeowner dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

try {
    // Set proper session configuration like the main login API
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/buildhub',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
    
    // For testing purposes, establish homeowner session
    // In production, this would be done through proper login
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 28;
        $_SESSION['role'] = 'homeowner';
        $_SESSION['first_name'] = 'Test';
        $_SESSION['last_name'] = 'Homeowner';
        $_SESSION['email'] = 'homeowner@test.com';
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Session established successfully',
        'user' => [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'],
            'first_name' => $_SESSION['first_name'],
            'last_name' => $_SESSION['last_name']
        ],
        'session_id' => session_id()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to establish session: ' . $e->getMessage()
    ]);
}
?>