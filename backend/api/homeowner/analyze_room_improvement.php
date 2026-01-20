<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly, we'll handle them
ini_set('log_errors', 1);

// Prevent any output before JSON headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Clear any previous output
ob_clean();

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../utils/EnhancedRoomAnalyzer.php';

    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    // For testing purposes, create a mock user session if none exists
    if (!$homeowner_id) {
        $_SESSION['user_id'] = 999; // Mock homeowner ID for testing
        $_SESSION['role'] = 'homeowner';
        $homeowner_id = 999;
    }
    
    // Get form data
    $room_type = $_POST['room_type'] ?? '';
    $improvement_notes = $_POST['improvement_notes'] ?? '';
    
    // Validation
    if (empty($room_type)) {
        echo json_encode(['success' => false, 'message' => 'Room type is required']);
        exit;
    }
    
    if (!isset($_FILES['room_image']) || $_FILES['room_image']['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $error_code = $_FILES['room_image']['error'] ?? UPLOAD_ERR_NO_FILE;
        $error_message = $error_messages[$error_code] ?? 'Unknown upload error';
        
        echo json_encode(['success' => false, 'message' => 'Room image upload error: ' . $error_message]);
        exit;
    }
    
    $file = $_FILES['room_image'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG and PNG images are allowed']);
        exit;
    }
    
    // Validate file size (5MB limit)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image file size must be less than 5MB']);
        exit;
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = __DIR__ . '/../../uploads/room_improvements/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to create upload directory'
            ]);
            exit;
        }
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'room_' . $homeowner_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
    
    // Analyze the room image and generate improvement concept using enhanced AI
    $analysis_result = EnhancedRoomAnalyzer::analyzeRoom($room_type, $improvement_notes, $file_path);
    
    // Store the analysis in database
    $stmt = $db->prepare("
        INSERT INTO room_improvement_analyses 
        (homeowner_id, room_type, improvement_notes, image_path, analysis_result, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $analysis_json = json_encode($analysis_result);
    $stmt->execute([$homeowner_id, $room_type, $improvement_notes, $filename, $analysis_json]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Room analysis completed successfully',
        'analysis' => $analysis_result
    ]);
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_clean();
    
    error_log("Enhanced room improvement analysis error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during visual analysis. Please try again.',
        'debug' => [
            'error_type' => get_class($e),
            'error_message' => $e->getMessage(),
            'gd_available' => extension_loaded('gd')
        ]
    ]);
} catch (Error $e) {
    // Handle PHP fatal errors
    ob_clean();
    
    error_log("PHP Error in room improvement analysis: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred. Please try again.',
        'debug' => [
            'error_type' => 'PHP Error',
            'error_message' => $e->getMessage()
        ]
    ]);
}