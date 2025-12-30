<?php
// Disable HTML error output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Headers for file download
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once __DIR__ . '/../../config/db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

try {
    if (!isset($_GET['user_id']) || !isset($_GET['doc_type'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
        exit;
    }
    
    $userId = (int)$_GET['user_id'];
    $docType = $_GET['doc_type'];
    
    if (!in_array($docType, ['license', 'portfolio'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid document type.']);
        exit;
    }
    
    // Get user and document path
    $stmt = $pdo->prepare("SELECT first_name, last_name, role, $docType FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }
    
    $documentPath = $user[$docType];
    if (!$documentPath) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Document not found.']);
        exit;
    }
    
    // Construct full file path
    $fullPath = __DIR__ . '/../../../' . $documentPath;
    
    if (!file_exists($fullPath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'File not found on server.']);
        exit;
    }
    
    // Get file info
    $fileInfo = pathinfo($fullPath);
    $fileName = $user['first_name'] . '_' . $user['last_name'] . '_' . $docType . '.' . $fileInfo['extension'];
    
    // Determine MIME type based on file extension
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'txt' => 'text/plain',
        'rtf' => 'application/rtf',
        'odt' => 'application/vnd.oasis.opendocument.text'
    ];
    
    $extension = strtolower($fileInfo['extension']);
    $mimeType = isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
    
    // Set headers for file download
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // Output file
    readfile($fullPath);
    exit;
    
} catch (Exception $e) {
    error_log("Document download error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Download failed.']);
}
?>