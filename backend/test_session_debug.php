<?php
session_start();

header('Content-Type: application/json');

// Debug session information
$sessionInfo = [
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? null,
    'role' => $_SESSION['role'] ?? null,
    'admin_logged_in' => $_SESSION['admin_logged_in'] ?? null,
    'admin_username' => $_SESSION['admin_username'] ?? null,
    'all_session_data' => $_SESSION,
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($sessionInfo, JSON_PRETTY_PRINT);
?>