<?php
header('Content-Type: application/json');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/buildhub',
  'domain' => '',
  'secure' => false,
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();
if (!empty($_SESSION['user_id'])) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'role' => $_SESSION['role'] ?? null,
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? ''
        ]
    ]);
    exit;
}

echo json_encode(['authenticated' => false]);