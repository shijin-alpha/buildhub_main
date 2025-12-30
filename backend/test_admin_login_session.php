<?php
session_start();

// Simulate admin login
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = 'admin';

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Admin session created',
    'session_id' => session_id(),
    'session_data' => $_SESSION
]);
?>