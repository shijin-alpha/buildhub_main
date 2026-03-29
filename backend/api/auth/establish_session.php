<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    session_start();
    
    // Auto-establish session for homeowner 32 (Amal Samuel)
    $_SESSION['user_id'] = 32;
    $_SESSION['user_role'] = 'homeowner';
    $_SESSION['user_name'] = 'Amal Samuel';
    $_SESSION['user_email'] = 'thomasshijin90@gmail.com';
    $_SESSION['logged_in'] = true;
    
    error_log("Session established for homeowner 32 via API");
    
    echo json_encode([
        'success' => true,
        'message' => 'Session established successfully',
        'data' => [
            'user_id' => $_SESSION['user_id'],
            'user_role' => $_SESSION['user_role'],
            'user_name' => $_SESSION['user_name'],
            'session_id' => session_id()
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Session establishment error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to establish session: ' . $e->getMessage()
    ]);
}
?>