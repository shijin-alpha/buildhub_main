<?php
/**
 * Simple Receipt Upload API
 * Allows contractors to upload any receipt - no payment logic
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in and is a contractor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $contractor_id = $_SESSION['user_id'];
    
    // Extract form data
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $receipt_title = trim($_POST['receipt_title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        exit;
    }
    
    if (empty($receipt_title)) {
        echo json_encode(['success' => false, 'message' => 'Receipt title is required']);
        exit;
    }
    
    // Get homeowner_id from project
    $project_check = $db->prepare("
        SELECT homeowner_id 
        FROM contractor_send_estimates 
        WHERE id = ? AND contractor_id = ?
    ");
    $project_check->execute([$project_id, $contractor_id]);
    $project = $project_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this project']);
        exit;
    }
    
    $homeowner_id = $project['homeowner_id'];
    
    // Check if file was uploaded
    if (!isset($_FILES['receipt_file']) || $_FILES['receipt_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit;
    }
    
    $file = $_FILES['receipt_file'];
    
    // Validate file type and size
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF files are allowed']);
        exit;
    }
    
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 10MB']);
        exit;
    }
    
    // Create upload directory
    $upload_dir = "../../uploads/simple_receipts/{$project_id}/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $timestamp = time();
    $stored_filename = "receipt_{$timestamp}." . $file_extension;
    $file_path = $upload_dir . $stored_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        exit;
    }
    
    // Save to database
    $insert_stmt = $db->prepare("
        INSERT INTO simple_receipts 
        (project_id, contractor_id, homeowner_id, receipt_title, description, 
         file_path, original_filename, file_size, mime_type) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $relative_path = "uploads/simple_receipts/{$project_id}/{$stored_filename}";
    
    $insert_stmt->execute([
        $project_id,
        $contractor_id,
        $homeowner_id,
        $receipt_title,
        $description,
        $relative_path,
        $file['name'],
        $file['size'],
        $file['type']
    ]);
    
    $receipt_id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Receipt uploaded successfully',
        'data' => [
            'receipt_id' => $receipt_id,
            'file_path' => $relative_path,
            'original_filename' => $file['name'],
            'upload_date' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Simple receipt upload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>