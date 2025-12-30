<?php
// Disable HTML error output for clean JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

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
    // Decide input source: JSON or multipart (for file uploads)
    $ctype = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    $isMultipart = stripos($ctype, 'multipart/form-data') !== false;

    // Debug logging (simplified)
    error_log("Google Register - Content Type: " . $ctype);
    error_log("Google Register - POST data received: " . ($isMultipart ? 'multipart' : 'other'));
    error_log("Google Register - Raw POST data: " . print_r($_POST, true));

    if ($isMultipart) {
        // Expecting fields from FormData
        $input = [
            'firstName' => isset($_POST['firstName']) ? trim($_POST['firstName']) : null,
            'lastName'  => isset($_POST['lastName']) ? trim($_POST['lastName']) : null,
            'email'     => isset($_POST['email']) ? trim($_POST['email']) : null,
            'role'      => isset($_POST['role']) ? trim($_POST['role']) : null,
            // --- PATCH: Accept password from Google signup (random password from frontend) ---
            'password'  => isset($_POST['password']) ? trim($_POST['password']) : null,
        ];
    } else {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?: [];
        // --- PATCH: Accept password from Google signup (random password from frontend) ---
        if (!isset($input['password'])) $input['password'] = null;
        // Debug logging for non-multipart
        error_log("Google Register - Raw input length: " . strlen($raw));
    }

    // --- PATCH: Require password for DB consistency, but skip validation ---
    // Debug: log the parsed input data
    error_log("Google Register - Parsed input data: " . print_r($input, true));
    
    $required = ['firstName', 'lastName', 'email', 'role', 'password'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            error_log("Google Register - Missing field: $field (value: " . ($input[$field] ?? 'NULL') . ")");
            $response['message'] = "$field is required.";
            echo json_encode($response);
            exit;
        }
    }

    // Google OAuth can provide various email domains, not just Gmail
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Invalid email address.";
        echo json_encode($response);
        exit;
    }

    // If the role requires documents, ensure they are provided when multipart
    $role = strtolower($input['role']);
    $licensePath = null;
    $portfolioPath = null;

    function uploadFileGoogle($fileKey, $uploadDir) {
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

        return ['path' => str_replace(__DIR__ . '/../../', '', $filePath)];
    }

    if ($isMultipart) {
        if ($role === 'contractor') {
            $upload = uploadFileGoogle('license', __DIR__ . '/../../uploads/licenses/');
            if (isset($upload['error'])) {
                $response['message'] = $upload['error'];
                echo json_encode($response);
                exit;
            }
            $licensePath = $upload['path'];
        }
        if ($role === 'architect') {
            $upload = uploadFileGoogle('portfolio', __DIR__ . '/../../uploads/portfolios/');
            if (isset($upload['error'])) {
                $response['message'] = $upload['error'];
                echo json_encode($response);
                exit;
            }
            $portfolioPath = $upload['path'];
        }
    } else {
        // If role requires documents but request is not multipart, reject
        if ($role === 'contractor' || $role === 'architect') {
            $response['message'] = 'Document upload is required for ' . ucfirst($role) . ' role. Please upload the required documents.';
            echo json_encode($response);
            exit;
        }
    }

    // Check existing user
    $stmt = $pdo->prepare("SELECT id, is_verified, role FROM users WHERE email = ?");
    $stmt->execute([$input['email']]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['role'] !== 'homeowner' && !$user['is_verified']) {
            $response['message'] = "Your account is pending admin verification.";
            $response['redirect'] = 'login';
            echo json_encode($response);
            exit;
        }
        $response['success'] = true;
        $response['message'] = "Login successful!";
        $response['redirect'] = ($user['role'] === 'homeowner') ? 'homeowner-dashboard' : 'login';
        echo json_encode($response);
        exit;
    }

    $is_verified = ($role === 'homeowner') ? 1 : 0;

    // --- PATCH: Hash the password from Google signup for DB consistency ---
    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);

    // Insert supports optional license/portfolio columns like normal register
    try {
        // Debug logging before insertion
        error_log("Google Register - Inserting user with data: " . json_encode([
            'firstName' => $input['firstName'],
            'lastName' => $input['lastName'],
            'email' => $input['email'],
            'role' => $role,
            'is_verified' => $is_verified,
            'licensePath' => $licensePath,
            'portfolioPath' => $portfolioPath
        ]));

        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role, is_verified, license, portfolio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $input['firstName'],
            $input['lastName'],
            $input['email'],
            $hashedPassword,
            $role,
            $is_verified,
            $licensePath,
            $portfolioPath
        ]);

        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log("Google Register - SQL error: " . print_r($errorInfo, true));
            throw new Exception("Database insertion failed: " . $errorInfo[2]);
        }

        $userId = $pdo->lastInsertId();
        error_log("Google Register - User created successfully with ID: " . $userId);

    } catch (PDOException $e) {
        error_log("Google Register - Database error: " . $e->getMessage());
        $response['message'] = "Database error occurred. Please try again.";
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        error_log("Google Register - Registration error: " . $e->getMessage());
        $response['message'] = "Registration error: " . $e->getMessage();
        echo json_encode($response);
        exit;
    }

    // Set success response (temporarily disable email sending for debugging)
    if ($role !== 'homeowner') {
        $response['message'] = "Registration successful! Await admin verification. You'll receive an email once verified.";
        $response['redirect'] = 'login';
    } else {
        $response['message'] = "Registration successful! Redirecting to dashboard...";
        $response['redirect'] = 'homeowner-dashboard';
    }

    // TODO: Re-enable email notifications once registration is working
    // Email notifications would go here but are disabled for debugging

    // Start session and store user data for immediate login (homeowners only)
    if ($role === 'homeowner') {
        session_start();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $input['email'];
        $_SESSION['user_name'] = $input['firstName'] . ' ' . $input['lastName'];
        $_SESSION['user_role'] = $role;
        $_SESSION['is_verified'] = 1;
        error_log("Google Register - Session started for homeowner: " . $input['email']);
    }

    $response['success'] = true;
    $response['user_data'] = [
        'id' => $userId,
        'email' => $input['email'],
        'name' => $input['firstName'] . ' ' . $input['lastName'],
        'role' => $role,
        'is_verified' => $is_verified
    ];

    error_log("Google Register - Success response: " . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'An unexpected error occurred. Please try again.'];
    echo json_encode($response);
}

file_put_contents(__DIR__ . '/debug_google_register.log', date('c') . " | POST: " . print_r($_POST, true) . " | FILES: " . print_r($_FILES, true) . "\n", FILE_APPEND);
?>
