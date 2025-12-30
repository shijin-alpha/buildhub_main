<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

session_start();

echo json_encode([
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? null,
    'role' => $_SESSION['role'] ?? null,
    'all_session_data' => $_SESSION,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>