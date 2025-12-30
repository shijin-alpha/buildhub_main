<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../error.log');

header('Content-Type: application/json');
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once '../config/db.php';
} catch (Throwable $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.', 'error' => $e->getMessage()]);
    exit;
}

try {

$response = ['success' => false, 'message' => '', 'redirect' => ''];

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

// Check if data was parsed successfully
if ($data === null) {
    $response['message'] = "Invalid request data.";
    echo json_encode($response);
    exit;
}

// Google Sign-In: If "google" key is set, handle Google login
if (!empty($data['google']) && !empty($data['email'])) {
    // Determine schema capabilities
    $hasStatus = false;
    $hasDeletedAt = false;
    try {
        $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
        $hasStatus = $col && $col->rowCount() > 0;
        $col2 = $pdo->query("SHOW COLUMNS FROM users LIKE 'deleted_at'");
        $hasDeletedAt = $col2 && $col2->rowCount() > 0;
    } catch (Throwable $e) {}

    // Find user by email (include status/deleted_at if available)
    if ($hasStatus) {
        $select = "SELECT id, first_name, last_name, email, role, is_verified, status" . ($hasDeletedAt ? ", deleted_at" : "") . " FROM users WHERE email = ?";
    } else {
        $select = "SELECT id, first_name, last_name, email, role, is_verified" . ($hasDeletedAt ? ", deleted_at" : "") . " FROM users WHERE email = ?";
    }
    $stmt = $pdo->prepare($select);
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();

    if (!$user) {
        $response['message'] = "No account found for this Google email. Please register first.";
        echo json_encode($response);
        exit;
    }

    // Block suspended accounts
    if ($hasStatus && isset($user['status']) && $user['status'] === 'suspended') {
        $response['message'] = "Your account is suspended. Please contact support.";
        $response['redirect'] = 'login';
        echo json_encode($response);
        exit;
    }

    // Block soft-deleted accounts
    if ($hasDeletedAt && !empty($user['deleted_at'])) {
        $response['message'] = "This account has been deleted.";
        $response['redirect'] = 'login';
        echo json_encode($response);
        exit;
    }

    // Check verification for contractor/architect
    if (($user['role'] === 'contractor' || $user['role'] === 'architect') && !$user['is_verified']) {
        $response['message'] = "Your account is pending admin verification.";
        $response['redirect'] = 'login';
        echo json_encode($response);
        exit;
    }

    // Start PHP session and set user session
    // Ensure session cookie works across localhost paths
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/buildhub',
      'domain' => '',
      'secure' => false,
      'httponly' => true,
      'samesite' => 'Lax'
    ]);
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];

    $response['success'] = true;
    $response['message'] = "Login successful!";
    $response['user'] = [
        'id' => $user['id'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
    
    // Set redirect based on role
    switch ($user['role']) {
        case 'homeowner':
            $response['redirect'] = 'homeowner-dashboard';
            break;
        case 'contractor':
            $response['redirect'] = 'contractor-dashboard';
            break;
        case 'architect':
            $response['redirect'] = 'architect-dashboard';
            break;
        default:
            $response['redirect'] = 'login';
    }
    
    echo json_encode($response);
    exit;
}

// Normal email/password login
if (empty($data['email']) || empty($data['password'])) {
    $response['message'] = "Email and password are required.";
    echo json_encode($response);
    exit;
}

// Check for admin login first
$adminUsername = 'shijinthomas369@gmail.com'; // Admin email
$adminPassword = 'admin123'; // Admin password

if ($data['email'] === $adminUsername && $data['password'] === $adminPassword) {
    // Configure session for cross-origin requests
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Start session for admin
    session_start();
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
    
    $response['success'] = true;
    $response['message'] = "Admin login successful!";
    $response['redirect'] = 'admin-dashboard';
    echo json_encode($response);
    exit;
}

// Allow any valid email domain for regular users
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $response['message'] = "Invalid email address.";
    echo json_encode($response);
    exit;
}

// Determine schema capabilities
$hasStatus = false;
$hasDeletedAt = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'status'");
    $hasStatus = $col && $col->rowCount() > 0;
    $col2 = $pdo->query("SHOW COLUMNS FROM users LIKE 'deleted_at'");
    $hasDeletedAt = $col2 && $col2->rowCount() > 0;
} catch (Throwable $e) {}

// Find user (include status/deleted_at if available)
if ($hasStatus) {
    $select = "SELECT id, first_name, last_name, email, password, role, is_verified, status" . ($hasDeletedAt ? ", deleted_at" : "") . " FROM users WHERE email = ?";
} else {
    $select = "SELECT id, first_name, last_name, email, password, role, is_verified" . ($hasDeletedAt ? ", deleted_at" : "") . " FROM users WHERE email = ?";
}
$stmt = $pdo->prepare($select);
$stmt->execute([$data['email']]);
$user = $stmt->fetch();

if (!$user) {
    $response['message'] = "Invalid email or password.";
    echo json_encode($response);
    exit;
}

// Check password
if (!password_verify($data['password'], $user['password'])) {
    $response['message'] = "Invalid email or password.";
    echo json_encode($response);
    exit;
}

// Block suspended accounts
if ($hasStatus && isset($user['status']) && $user['status'] === 'suspended') {
    $response['message'] = "Your account is suspended. Please contact support.";
    $response['redirect'] = 'login';
    echo json_encode($response);
    exit;
}

// Block soft-deleted accounts
if ($hasDeletedAt && !empty($user['deleted_at'])) {
    $response['message'] = "This account has been deleted.";
    $response['redirect'] = 'login';
    echo json_encode($response);
    exit;
}

// Check verification for contractor/architect
if (($user['role'] === 'contractor' || $user['role'] === 'architect') && !$user['is_verified']) {
    $response['message'] = "Your account is pending admin verification.";
    $response['redirect'] = 'login';
    echo json_encode($response);
    exit;
}

// Start PHP session and set user session for regular login
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/buildhub',
  'domain' => '',
  'secure' => false,
  'httponly' => true,
  'samesite' => 'Lax'
]);
session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];

// Success: set redirect based on role
$response['success'] = true;
$response['message'] = "Login successful!";
$response['user'] = [
    'id' => $user['id'],
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'email' => $user['email'],
    'role' => $user['role']
];

// Set redirect based on role
switch ($user['role']) {
    case 'homeowner':
        $response['redirect'] = 'homeowner-dashboard';
        break;
    case 'contractor':
        $response['redirect'] = 'contractor-dashboard';
        break;
    case 'architect':
        $response['redirect'] = 'architect-dashboard';
        break;
    default:
        $response['redirect'] = 'login';
}

echo json_encode($response);

} catch (Throwable $e) {
    error_log("Login Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred during login.', 'error' => $e->getMessage()]);
}