<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

try {
    session_start();
    $architect_id = $_SESSION['user_id'] ?? null;

    if (!$architect_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    // Check if files were uploaded
    if (empty($_FILES)) {
        echo json_encode(['success' => false, 'message' => 'No files uploaded']);
        exit;
    }

    $plan_id = $_POST['plan_id'] ?? null;
    $file_type = $_POST['file_type'] ?? 'layout_image'; // layout_image, elevation_images, section_drawings, renders_3d

    if (!$plan_id) {
        echo json_encode(['success' => false, 'message' => 'Plan ID is required']);
        exit;
    }

    // Verify plan belongs to this architect
    $database = new Database();
    $db = $database->getConnection();
    
    $planStmt = $db->prepare("SELECT id FROM house_plans WHERE id = :id AND architect_id = :aid");
    $planStmt->execute([':id' => $plan_id, ':aid' => $architect_id]);
    
    if (!$planStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Plan not found or access denied']);
        exit;
    }

    // Create upload directory if it doesn't exist
    $uploadDir = '../../uploads/house_plans/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadedFiles = [];
    $errors = [];

    foreach ($_FILES as $fileKey => $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading {$file['name']}: " . $file['error'];
            continue;
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = "Invalid file type for {$file['name']}. Only images and PDF files are allowed.";
            continue;
        }

        // Validate file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = "File {$file['name']} is too large. Maximum size is 10MB.";
            continue;
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $plan_id . '_' . $file_type . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $uploadedFiles[] = [
                'original' => $file['name'],
                'stored' => $filename,
                'size' => $file['size'],
                'type' => $file['type'],
                'ext' => $extension,
                'uploaded' => true,
                'upload_time' => date('Y-m-d H:i:s')
            ];
        } else {
            $errors[] = "Failed to save file {$file['name']}";
        }
    }

    if (empty($uploadedFiles) && !empty($errors)) {
        echo json_encode(['success' => false, 'message' => 'No files uploaded successfully', 'errors' => $errors]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
        'files' => $uploadedFiles,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>