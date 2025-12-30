<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// Check admin session
$isAdmin = false;
$sessionType = 'none';

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    $isAdmin = true;
    $sessionType = 'regular_admin';
} elseif (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $isAdmin = true;
    $sessionType = 'admin_login';
}

echo json_encode([
    'success' => true,
    'is_admin' => $isAdmin,
    'session_type' => $sessionType,
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>