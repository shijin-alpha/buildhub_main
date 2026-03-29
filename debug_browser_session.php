<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

session_start();

// Debug current session
$debug_info = [
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE,
    'headers' => getallheaders(),
    'timestamp' => date('Y-m-d H:i:s')
];

// If no session, establish one for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 28;
    $_SESSION['role'] = 'homeowner';
    $_SESSION['username'] = 'SHIJIN THOMAS MCA2024-2026';
    $_SESSION['email'] = 'shijinthomas2026@mca.ajce.in';
    $debug_info['session_established'] = true;
} else {
    $debug_info['session_established'] = false;
}

echo json_encode([
    'success' => true,
    'message' => 'Session debug info',
    'debug' => $debug_info,
    'is_homeowner' => isset($_SESSION['role']) && $_SESSION['role'] === 'homeowner',
    'user_id' => $_SESSION['user_id'] ?? null
]);
?>