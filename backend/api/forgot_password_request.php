<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../config/db.php';
require_once '../utils/send_mail.php';
require_once '../config/email_config.php';

$response = ['success' => false, 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $email = trim($data['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Enter a valid email address.';
        echo json_encode($response); exit;
    }

    // Find user
    $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // For security: return success even if user not found
    if (!$user) {
        $response['success'] = true;
        $response['message'] = 'If an account exists, an OTP has been sent.';
        echo json_encode($response); exit;
    }

    // Create numeric OTP (store hash)
    $tokenPlain = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT); // 6-digit OTP
    $tokenHash = hash('sha256', $tokenPlain);
    $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    // Ensure table exists (optional safety)
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      email VARCHAR(255) NOT NULL,
      token_hash VARCHAR(255) NOT NULL,
      expires_at DATETIME NOT NULL,
      used TINYINT(1) DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (email), INDEX (user_id), INDEX (expires_at)
    )");

    // Invalidate old tokens for this user/email
    $pdo->prepare('UPDATE password_resets SET used = 1 WHERE email = ?')->execute([$email]);

    // Insert token
    $ins = $pdo->prepare('INSERT INTO password_resets (user_id, email, token_hash, expires_at) VALUES (?, ?, ?, ?)');
    $ins->execute([$user['id'], $email, $tokenHash, $expires]);



    // Email content: OTP only, no links (styled HTML)
    $subject = 'Your BuildHub password reset OTP';
    $message = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<title>Password Reset OTP</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f5f7fb;padding:24px;">
    <tr><td align="center">
      <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:560px;background:#ffffff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,0.06);overflow:hidden;">
        <tr>
          <td style="background:#0ea5e9;padding:20px 24px;color:#ffffff;font-size:18px;font-weight:700;">
            BuildHub
          </td>
        </tr>
        <tr>
          <td style="padding:28px 24px 8px 24px;">
            <h1 style="margin:0 0 12px 0;font-size:22px;color:#111827;">Password reset request</h1>
            <p style="margin:0 0 16px 0;line-height:1.6;">Use the One‑Time Password (OTP) below to reset your BuildHub account password.</p>
            <div style="margin:16px 0 20px 0;text-align:center;">
              <div style="display:inline-block;padding:14px 24px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;font-family:&quot;SFMono-Regular&quot;,Consolas,&quot;Liberation Mono&quot;,Menlo,monospace;font-size:26px;letter-spacing:6px;color:#111827;font-weight:700;">
                ' . htmlspecialchars($tokenPlain) . '
              </div>
            </div>
            <p style="margin:0 0 10px 0;line-height:1.6;">This OTP will expire in 1 hour.</p>
            <p style="margin:0 0 0 0;line-height:1.6;color:#6b7280;font-size:12px;">If you did not request this, you can safely ignore this email.</p>
          </td>
        </tr>
        <tr>
          <td style="padding:18px 24px;border-top:1px solid #f3f4f6;color:#6b7280;font-size:12px;text-align:center;">
            © ' . date('Y') . ' BuildHub
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';

    // Send email
    $sent = sendMail($email, $subject, $message);

    if (!$sent) {
        $response['message'] = 'Failed to send reset email. Please try again later.';
        echo json_encode($response); exit;
    }

    $response['success'] = true;
    $response['message'] = 'If an account exists, an OTP has been sent.';
    echo json_encode($response);

} catch (Exception $e) {
    $response['message'] = 'Server error. Please try again.';
    echo json_encode($response);
}