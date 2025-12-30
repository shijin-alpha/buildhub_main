<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    session_start();
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }
    
    // Check if files were uploaded
    if (!isset($_FILES['room_images']) || empty($_FILES['room_images']['name'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No files uploaded'
        ]);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/room_images/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $uploaded_images = [];
    $files = $_FILES['room_images'];
    
    // Handle multiple files
    $file_count = is_array($files['name']) ? count($files['name']) : 1;
    
    for ($i = 0; $i < $file_count; $i++) {
        // Get file info (handle both single and multiple file uploads)
        $file_name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $file_tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $file_size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
        $file_error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        
        // Skip if there's an error or empty file
        if ($file_error !== UPLOAD_ERR_OK || empty($file_name)) {
            continue;
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $file_type = finfo_file($file_info, $file_tmp);
        finfo_close($file_info);
        
        if (!in_array($file_type, $allowed_types)) {
            continue; // Skip invalid file types
        }
        
        // Validate file size (max 10MB)
        if ($file_size > 10 * 1024 * 1024) {
            continue; // Skip files larger than 10MB
        }
        
        // Generate unique filename
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $unique_name = uniqid($user_id . '_') . '.' . $file_extension;
        $file_path = $upload_dir . $unique_name;
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $file_path)) {
            $uploaded_images[] = [
                'id' => uniqid(),
                'name' => $file_name,
                'size' => $file_size,
                'url' => '/buildhub/backend/uploads/room_images/' . $unique_name
            ];
        }
    }
    
    if (empty($uploaded_images)) {
        echo json_encode([
            'success' => false,
            'message' => 'No valid images were uploaded'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Images uploaded successfully',
            'images' => $uploaded_images
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error uploading images: ' . $e->getMessage()
    ]);
}
?>





















