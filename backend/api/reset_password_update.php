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
    $password = (string)($data['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$token || !$password) {
        $response['message'] = 'Invalid request.';
        echo json_encode($response); exit;
    }

    if (!preg_match('/^\d{6}$/', $token)) {
        $response['message'] = 'Invalid token format.';
        echo json_encode($response); exit;
    }

    // Enforce same password rules as register.php
    if (strlen($password) < 8 || preg_match('/\s/', $password)) {
        $response['message'] = 'Password must be at least 8 characters long and contain no spaces.';
        echo json_encode($response); exit;
    }
    if (!preg_match('/[A-Za-z]/', $password)) { $response['message'] = 'Password must include at least one letter.'; echo json_encode($response); exit; }
    if (!preg_match('/[0-9]/', $password)) { $response['message'] = 'Password must include at least one number.'; echo json_encode($response); exit; }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) { $response['message'] = 'Password must include at least one special character.'; echo json_encode($response); exit; }

    $tokenHash = hash('sha256', $token);

    // Validate token
    $stmt = $pdo->prepare('SELECT id, user_id, expires_at, used FROM password_resets WHERE email = ? AND token_hash = ? LIMIT 1');
    $stmt->execute([$email, $tokenHash]);
    $row = $stmt->fetch();
    if (!$row) { $response['message'] = 'Invalid or expired token.'; echo json_encode($response); exit; }
    if ((int)$row['used'] === 1) { $response['message'] = 'This link has already been used.'; echo json_encode($response); exit; }
    if (new DateTime() > new DateTime($row['expires_at'])) { $response['message'] = 'This link has expired.'; echo json_encode($response); exit; }

    // Update user password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    $upd = $pdo->prepare('UPDATE users SET password = ? WHERE id = ? AND email = ?');
    $upd->execute([$hash, $row['user_id'], $email]);

    // Mark token used
    $mark = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
    $mark->execute([$row['id']]);
    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Password updated successfully.';
    echo json_encode($response);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    $response['message'] = 'Server error. Please try again.';
    echo json_encode($response);
}