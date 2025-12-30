<?php
// Disable HTML error output for clean JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set up error handler to catch any PHP errors and return JSON
set_error_handler(function($severity, $message, $file, $line) {
    $response = ['success' => false, 'message' => 'Server error occurred. Please try again.'];
    echo json_encode($response);
    exit();
});

$response = ['success' => false, 'message' => ''];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['username']) || !isset($input['password'])) {
        $response['message'] = 'Username and password are required.';
        echo json_encode($response);
        exit;
    }
    
    $username = trim($input['username']);
    $password = trim($input['password']);
    
    // Simple admin credentials (in production, this should be in database with hashed password)
    $adminUsername = 'admin';
    $adminPassword = 'admin123'; // Change this to a secure password
    
    if ($username === $adminUsername && $password === $adminPassword) {
        // Configure session for cross-origin requests
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false, // Set to true if using HTTPS
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        // Start session
        session_start();
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        
        $response['success'] = true;
        $response['message'] = 'Admin login successful.';
        $response['redirect'] = 'admin-dashboard';
    } else {
        $response['message'] = 'Invalid admin credentials.';
    }
    
} catch (Exception $e) {
    error_log("Admin login error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
?>