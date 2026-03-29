<?php
// Disable HTML error output for clean JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set up error handler to catch any PHP errors and return JSON
set_error_handler(function($severity, $message, $file, $line) {
    $response = ['success' => false, 'message' => 'Server error occurred. Please try again.'];
    echo json_encode($response);
    exit();
});

try {
    require_once '../config/db.php';
    require_once '../utils/send_mail.php';
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Database connection failed.'];
    echo json_encode($response);
    exit();
}

$response = ['success' => false, 'message' => '', 'redirect' => null];

try {
    $data = array_map('trim', $_POST); // Remove extra spaces

// Required fields check
$required = ['firstName', 'lastName', 'email', 'password', 'role'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        $response['message'] = "$field is required.";
        echo json_encode($response);
        exit;
    }
}

// Email validation (allow any valid domain)
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $response['message'] = "Invalid email address.";
    echo json_encode($response);
    exit;
}

// --- PATCH: Allow Google signups with random passwords (skip strict password validation for Google) ---
$isGoogleSignup = false;
if (
    isset($_SERVER['HTTP_USER_AGENT']) &&
    strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'google') !== false
) {
    $isGoogleSignup = true;
}
// Or, better: check if password looks like a random string (e.g., ends with "!A1" as in frontend)
if (isset($data['password']) && preg_match('/[a-z0-9]{12}!A1$/i', $data['password'])) {
    $isGoogleSignup = true;
}

if (!$isGoogleSignup) {
    // Password validation for normal signups
    $pwd = $data['password'];
    if (strlen($pwd) < 8 || preg_match('/\s/', $pwd)) {
        $response['message'] = "Password must be at least 8 characters long and contain no spaces.";
        echo json_encode($response);
        exit;
    }
    if (!preg_match('/[A-Za-z]/', $pwd)) {
        $response['message'] = "Password must include at least one letter.";
        echo json_encode($response);
        exit;
    }
    if (!preg_match('/[0-9]/', $pwd)) {
        $response['message'] = "Password must include at least one number.";
        echo json_encode($response);
        exit;
    }
    if (!preg_match('/[^A-Za-z0-9]/', $pwd)) {
        $response['message'] = "Password must include at least one special character.";
        echo json_encode($response);
        exit;
    }
}
// For Google signups, skip password validation (random password is accepted)
// --------------------------------------------------------------------------------------

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$data['email']]);
if ($stmt->fetch()) {
    $response['message'] = "Email already registered.";
    echo json_encode($response);
    exit;
}

// Role-based file uploads
$role = strtolower($data['role']);
$licensePath = null;
$portfolioPath = null;

function uploadFile($fileKey, $uploadDir) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return ['error' => ucfirst($fileKey) . " file is required."];
    }
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $fileExt = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExtensions)) {
        return ['error' => "Invalid file type for $fileKey. Allowed: " . implode(', ', $allowedExtensions)];
    }

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $filePath = $uploadDir . uniqid() . '_' . basename($_FILES[$fileKey]['name']);
    if (!move_uploaded_file($_FILES[$fileKey]['tmp_name'], $filePath)) {
        return ['error' => "Failed to upload $fileKey."];
    }

    return ['path' => str_replace(__DIR__ . '/../..', '', $filePath)];
}

if ($role === 'contractor') {
    $upload = uploadFile('license', __DIR__ . '/../../uploads/licenses/');
    if (isset($upload['error'])) {
        $response['message'] = $upload['error'];
        echo json_encode($response);
        exit;
    }
    $licensePath = $upload['path'];
}

if ($role === 'architect') {
    $upload = uploadFile('portfolio', __DIR__ . '/../../uploads/portfolios/');
    if (isset($upload['error'])) {
        $response['message'] = $upload['error'];
        echo json_encode($response);
        exit;
    }
    $portfolioPath = $upload['path'];
}

// Save to database
$hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
$is_verified = ($role === 'homeowner') ? 1 : 0;

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, email, password, role, is_verified, license, portfolio)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $result = $stmt->execute([
        $data['firstName'],
        $data['lastName'],
        $data['email'],
        $hashedPassword,
        $role,
        $is_verified,
        $licensePath,
        $portfolioPath
    ]);
    
    if (!$result) {
        throw new Exception("Database insertion failed");
    }
    
} catch (PDOException $e) {
    $response['message'] = "Database error: " . $e->getMessage();
    echo json_encode($response);
    exit;
} catch (Exception $e) {
    $response['message'] = "Registration error: " . $e->getMessage();
    echo json_encode($response);
    exit;
}

// Set success response (temporarily disable email sending for debugging)
if ($role !== 'homeowner') {
    $response['message'] = "Registration successful! Await admin verification.";
    $response['redirect'] = 'login';
} else {
    $response['message'] = "Registration successful!";
    $response['redirect'] = 'homeowner-dashboard';
}

// TODO: Re-enable email notifications once registration is working
// Email notifications would go here but are disabled for debugging

    $response['success'] = true;
    echo json_encode($response);

} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred. Please try again.'];
    echo json_encode($response);
}
