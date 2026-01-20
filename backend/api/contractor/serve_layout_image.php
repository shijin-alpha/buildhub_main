<?php
// CORS headers for image serving
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { 
    header('Access-Control-Allow-Origin: ' . $origin); 
    header('Vary: Origin'); 
} else { 
    header('Access-Control-Allow-Origin: *'); 
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    header('Access-Control-Max-Age: 86400'); 
    exit; 
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get parameters
    $contractor_id = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    $image_name = isset($_GET['image']) ? $_GET['image'] : '';
    $download = isset($_GET['download']) && $_GET['download'] === '1';

    if ($contractor_id <= 0 || empty($image_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }

    // Sanitize image name to prevent directory traversal
    $image_name = basename($image_name);
    $upload_dir = '../../uploads/house_plans/';
    $file_path = $upload_dir . $image_name;

    // Check if file exists
    if (!file_exists($file_path)) {
        http_response_code(404);
        echo json_encode(['error' => 'Image not found']);
        exit;
    }

    // Verify contractor has access to this image by checking if it's in their inbox
    $checkStmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM contractor_layout_sends 
        WHERE contractor_id = :contractor_id 
        AND (payload LIKE :image_pattern1 OR payload LIKE :image_pattern2)
    ");
    
    $image_pattern1 = '%"stored":"' . $image_name . '"%';
    $image_pattern2 = '%"original":"' . $image_name . '"%';
    
    $checkStmt->execute([
        ':contractor_id' => $contractor_id,
        ':image_pattern1' => $image_pattern1,
        ':image_pattern2' => $image_pattern2
    ]);
    
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Get file info
    $file_size = filesize($file_path);
    $file_info = pathinfo($file_path);
    $mime_type = 'image/' . strtolower($file_info['extension']);
    
    // Set appropriate headers
    if ($download) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $image_name . '"');
    } else {
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: inline; filename="' . $image_name . '"');
    }
    
    header('Content-Length: ' . $file_size);
    header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

    // Output the file
    readfile($file_path);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>