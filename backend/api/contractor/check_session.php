<?php
/**
 * Check contractor session
 */

header('Content-Type: application/json');
session_start();

$userType = $_SESSION['user_type'] ?? $_SESSION['role'] ?? null;

echo json_encode([
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? null,
    'user_type' => $_SESSION['user_type'] ?? null,
    'role' => $_SESSION['role'] ?? null,
    'detected_type' => $userType,
    'is_logged_in' => isset($_SESSION['user_id']),
    'is_contractor' => $userType === 'contractor',
    'all_session_data' => $_SESSION
]);
