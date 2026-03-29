<?php
// Disable HTML error output for clean JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

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

try {
    require_once __DIR__ . '/../../config/db.php';
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Database connection failed.'];
    echo json_encode($response);
    exit();
}

$response = ['success' => false, 'message' => '', 'users' => []];

try {
    // Get all pending users (contractors and architects who are not verified)
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, role, license, portfolio, profile_image, created_at 
        FROM users 
        WHERE (role = 'contractor' OR role = 'architect') 
        AND is_verified = 0 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    $response['success'] = true;
    $response['users'] = $users;
    $response['message'] = 'Pending users retrieved successfully.';
    
} catch (PDOException $e) {
    error_log("Get pending users error: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
} catch (Exception $e) {
    error_log("Get pending users error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
?>