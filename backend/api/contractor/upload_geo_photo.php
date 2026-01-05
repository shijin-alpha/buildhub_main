<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Database connection
require_once '../../config/database.php';
require_once '../../utils/notification_helper.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Check if user is logged in and is a contractor
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    $contractor_id = $_SESSION['user_id'];
    
    // Validate required fields
    if (!isset($_POST['project_id']) || !isset($_POST['contractor_id']) || !isset($_FILES['photo'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $project_id = intval($_POST['project_id']);
    $submitted_contractor_id = intval($_POST['contractor_id']);
    $location_data = $_POST['location_data'] ?? '{}';
    $timestamp = $_POST['timestamp'] ?? date('Y-m-d H:i:s');
    
    // Verify contractor matches session
    if ($contractor_id !== $submitted_contractor_id) {
        echo json_encode(['success' => false, 'message' => 'Contractor ID mismatch']);
        exit;
    }
    
    // Verify project exists and contractor is assigned
    $projectStmt = $pdo->prepare("
        SELECT p.*, h.id as homeowner_id, h.first_name, h.last_name, h.email
        FROM layout_requests p
        LEFT JOIN users h ON p.homeowner_id = h.id
        WHERE p.id = ? AND p.contractor_id = ? AND p.status = 'acknowledged'
    ");
    $projectStmt->execute([$project_id, $contractor_id]);
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found or not assigned to contractor']);
        exit;
    }
    
    // Handle file upload
    $photo = $_FILES['photo'];
    
    // Validate file
    if ($photo['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error: ' . $photo['error']]);
        exit;
    }
    
    // Check file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    $fileType = mime_content_type($photo['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, WebP, and GIF are allowed']);
        exit;
    }
    
    // Check file size (max 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($photo['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 10MB']);
        exit;
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../../uploads/geo_photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($photo['name'], PATHINFO_EXTENSION);
    $fileName = 'geo_photo_' . $project_id . '_' . $contractor_id . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($photo['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
        exit;
    }
    
    // Parse location data with enhanced validation
    $locationInfo = json_decode($location_data, true);
    if (!$locationInfo) {
        $locationInfo = [];
    }
    
    // Validate and extract location coordinates
    $latitude = null;
    $longitude = null;
    $placeName = null;
    $accuracy = null;
    
    if (isset($locationInfo['latitude']) && isset($locationInfo['longitude'])) {
        $latitude = floatval($locationInfo['latitude']);
        $longitude = floatval($locationInfo['longitude']);
        
        // Validate coordinate ranges
        if ($latitude >= -90 && $latitude <= 90 && $longitude >= -180 && $longitude <= 180) {
            $placeName = $locationInfo['placeName'] ?? null;
            $accuracy = isset($locationInfo['accuracy']) ? floatval($locationInfo['accuracy']) : null;
        } else {
            error_log("Invalid coordinates: lat={$latitude}, lng={$longitude}");
            $latitude = null;
            $longitude = null;
        }
    }
    
    // Log location capture for debugging
    if ($latitude && $longitude) {
        error_log("Valid location captured: {$latitude}, {$longitude} - {$placeName} (±{$accuracy}m)");
    } else {
        error_log("No valid location data in upload: " . json_encode($locationInfo));
    }
    
    // Create geo_photos table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS geo_photos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            contractor_id INT NOT NULL,
            homeowner_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            latitude DECIMAL(10, 8) NULL,
            longitude DECIMAL(11, 8) NULL,
            place_name TEXT NULL,
            location_accuracy DECIMAL(8, 2) NULL,
            location_data JSON NULL,
            photo_timestamp TIMESTAMP NULL,
            upload_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_sent_to_homeowner BOOLEAN DEFAULT TRUE,
            homeowner_viewed BOOLEAN DEFAULT FALSE,
            homeowner_viewed_at TIMESTAMP NULL,
            progress_update_id INT NULL,
            is_included_in_progress BOOLEAN DEFAULT FALSE,
            progress_association_date TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_project_contractor (project_id, contractor_id),
            INDEX idx_homeowner (homeowner_id),
            INDEX idx_upload_date (upload_timestamp),
            INDEX idx_location (latitude, longitude),
            INDEX idx_progress_update (progress_update_id),
            
            FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createTableSQL);
    
    // Insert photo record with enhanced location data
    $insertStmt = $pdo->prepare("
        INSERT INTO geo_photos (
            project_id, contractor_id, homeowner_id, filename, original_filename,
            file_path, file_size, mime_type, latitude, longitude, place_name,
            location_accuracy, location_data, photo_timestamp
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $insertStmt->execute([
        $project_id,
        $contractor_id,
        $project['homeowner_id'],
        $fileName,
        $photo['name'],
        $filePath,
        $photo['size'],
        $fileType,
        $latitude,
        $longitude,
        $placeName,
        $accuracy,
        $location_data,
        $timestamp
    ]);
    
    $photo_id = $pdo->lastInsertId();
    
    // Create enhanced notification for homeowner
    $notification_title = 'New Geo-Located Construction Photo';
    $contractor_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    
    if ($latitude && $longitude && $placeName) {
        $location_text = $placeName;
        $coordinates_text = "({$latitude}, {$longitude})";
    } else {
        $location_text = "construction site";
        $coordinates_text = "";
    }
    
    $notification_message = "📸 {$contractor_name} has sent you a new geo-located photo from {$location_text} {$coordinates_text}. Click to view the progress update with exact location details.";
    
    createNotification(
        $pdo,
        $project['homeowner_id'],
        'photo_received',
        $notification_title,
        $notification_message,
        $photo_id
    );
    
    // Get enhanced photo details for response
    $photoDetails = [
        'id' => $photo_id,
        'filename' => $fileName,
        'original_filename' => $photo['name'],
        'file_size' => $photo['size'],
        'mime_type' => $fileType,
        'location' => [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'place_name' => $placeName,
            'accuracy' => $accuracy,
            'has_coordinates' => ($latitude !== null && $longitude !== null),
            'full_data' => $locationInfo
        ],
        'upload_timestamp' => date('Y-m-d H:i:s'),
        'project_id' => $project_id,
        'homeowner_name' => $project['first_name'] . ' ' . $project['last_name']
    ];
    
    // Enhanced logging
    $location_summary = $latitude && $longitude ? 
        "GPS: {$latitude}, {$longitude} ({$placeName})" : 
        "No GPS coordinates";
    error_log("Geo photo uploaded successfully: Project {$project_id}, Photo {$photo_id}, Location: {$location_summary}");
    
    $success_message = $latitude && $longitude ? 
        "Photo uploaded successfully with GPS coordinates and sent to homeowner" :
        "Photo uploaded successfully (location data may be limited) and sent to homeowner";
    
    echo json_encode([
        'success' => true,
        'message' => $success_message,
        'data' => $photoDetails
    ]);
    
} catch (Exception $e) {
    error_log("Geo photo upload error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>