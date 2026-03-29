<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../config/db.php';

$response = ['success' => false, 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $email = trim($data['email'] ?? '');
    $token = trim($data['token'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$token) {
        $response['message'] = 'Invalid request.';
        echo json_encode($response); exit;
    }

    $tokenHash = hash('sha256', $token);

    // Lookup token
    $stmt = $pdo->prepare('SELECT id, user_id, expires_at, used FROM password_resets WHERE email = ? AND token_hash = ? LIMIT 1');
    $stmt->execute([$email, $tokenHash]);
    $row = $stmt->fetch();

    if (!$row) {
        $response['message'] = 'Invalid or expired token.';
        echo json_encode($response); exit;
    }

    if ((int)$row['used'] === 1) {
        $response['message'] = 'This link has already been used.';
        echo json_encode($response); exit;
    }

    if (new DateTime() > new DateTime($row['expires_at'])) {
        $response['message'] = 'This OTP has expired.';
        echo json_encode($response); exit;
    }

    $response['success'] = true;
    $response['message'] = 'Token valid.';
    echo json_encode($response);

} catch (Exception $e) {
    $response['message'] = 'Server error.';
    echo json_encode($response);
}