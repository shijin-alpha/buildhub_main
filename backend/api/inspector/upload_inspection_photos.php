<?php
/**
 * Upload inspection photos and documents
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in and is a site inspector
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'site_inspector') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $inspector_id = $_SESSION['user_id'];
    
    // Validate required parameters
    if (!isset($_POST['inspection_report_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Inspection report ID is required']);
        exit;
    }
    
    $inspection_report_id = $_POST['inspection_report_id'];
    
    // Verify inspector owns this inspection report
    $verify_query = "SELECT ir.project_id FROM inspection_reports ir 
                     WHERE ir.id = ? AND ir.inspector_id = ?";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute([$inspection_report_id, $inspector_id]);
    $inspection = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspection) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this inspection report']);
        exit;
    }
    
    // Check if files were uploaded
    if (!isset($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No files uploaded']);
        exit;
    }
    
    // Create upload directory if it doesn't exist
    $upload_dir = '../../uploads/inspection_photos/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $uploaded_files = [];
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
    $max_file_size = 10 * 1024 * 1024; // 10MB
    
    $db->beginTransaction();
    
    // Process each uploaded file
    for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
        if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $file_name = $_FILES['photos']['name'][$i];
        $file_tmp = $_FILES['photos']['tmp_name'][$i];
        $file_size = $_FILES['photos']['size'][$i];
        $file_type = $_FILES['photos']['type'][$i];
        
        // Validate file type
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Invalid file type for: $file_name");
        }
        
        // Validate file size
        if ($file_size > $max_file_size) {
            throw new Exception("File too large: $file_name");
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_filename = uniqid('inspection_' . $inspection_report_id . '_') . '.' . $file_extension;
        $file_path = $upload_dir . $unique_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file_tmp, $file_path)) {
            throw new Exception("Failed to upload file: $file_name");
        }
        
        // Get optional metadata
        $caption = $_POST['captions'][$i] ?? '';
        $photo_type = $_POST['photo_types'][$i] ?? 'progress';
        $latitude = !empty($_POST['latitude'][$i]) ? floatval($_POST['latitude'][$i]) : null;
        $longitude = !empty($_POST['longitude'][$i]) ? floatval($_POST['longitude'][$i]) : null;
        $location_accuracy = !empty($_POST['location_accuracy'][$i]) ? floatval($_POST['location_accuracy'][$i]) : null;
        
        // Insert file record into database
        $file_query = "INSERT INTO inspection_photos 
                       (inspection_report_id, file_path, file_name, file_size, mime_type, 
                        caption, photo_type, latitude, longitude, location_accuracy)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $file_stmt = $db->prepare($file_query);
        $file_stmt->execute([
            $inspection_report_id,
            'uploads/inspection_photos/' . $unique_filename,
            $file_name,
            $file_size,
            $file_type,
            $caption,
            $photo_type,
            $latitude,
            $longitude,
            $location_accuracy
        ]);
        
        $uploaded_files[] = [
            'id' => $db->lastInsertId(),
            'file_name' => $file_name,
            'file_path' => 'uploads/inspection_photos/' . $unique_filename,
            'caption' => $caption,
            'photo_type' => $photo_type
        ];
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => count($uploaded_files) . ' files uploaded successfully',
        'uploaded_files' => $uploaded_files
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    // Clean up any uploaded files on error
    if (isset($uploaded_files)) {
        foreach ($uploaded_files as $file) {
            if (file_exists('../../' . $file['file_path'])) {
                unlink('../../' . $file['file_path']);
            }
        }
    }
    
    error_log("Error uploading inspection photos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>