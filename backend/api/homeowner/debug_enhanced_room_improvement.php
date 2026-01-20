<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Debug information
$debug_info = [
    'php_version' => phpversion(),
    'gd_extension' => extension_loaded('gd'),
    'gd_info' => extension_loaded('gd') ? gd_info() : 'GD not loaded',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'files_received' => isset($_FILES) ? array_keys($_FILES) : 'no files',
    'post_data' => isset($_POST) ? array_keys($_POST) : 'no post data'
];

// If this is a POST request, try to process it
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check for file upload
        if (isset($_FILES['room_image'])) {
            $file = $_FILES['room_image'];
            $debug_info['file_info'] = [
                'name' => $file['name'],
                'type' => $file['type'],
                'size' => $file['size'],
                'error' => $file['error'],
                'tmp_name' => $file['tmp_name']
            ];
            
            // Try to get image info
            if ($file['error'] === UPLOAD_ERR_OK && file_exists($file['tmp_name'])) {
                $image_info = @getimagesize($file['tmp_name']);
                $debug_info['image_info'] = $image_info ? [
                    'width' => $image_info[0],
                    'height' => $image_info[1],
                    'type' => $image_info[2],
                    'mime' => $image_info['mime']
                ] : 'Failed to get image info';
                
                // Try to load the image
                if ($image_info) {
                    switch ($image_info[2]) {
                        case IMAGETYPE_JPEG:
                            $resource = @imagecreatefromjpeg($file['tmp_name']);
                            break;
                        case IMAGETYPE_PNG:
                            $resource = @imagecreatefrompng($file['tmp_name']);
                            break;
                        default:
                            $resource = false;
                    }
                    
                    $debug_info['image_load_test'] = $resource ? 'success' : 'failed';
                    
                    if ($resource) {
                        imagedestroy($resource);
                    }
                }
            }
        }
        
        // Test the enhanced analyzer
        if (isset($_POST['room_type']) && isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
            require_once __DIR__ . '/../../utils/EnhancedRoomAnalyzer.php';
            
            $debug_info['analyzer_test'] = 'attempting analysis';
            
            try {
                $analysis = EnhancedRoomAnalyzer::analyzeRoom(
                    $_POST['room_type'],
                    $_POST['improvement_notes'] ?? '',
                    $_FILES['room_image']['tmp_name']
                );
                
                $debug_info['analyzer_test'] = 'success';
                $debug_info['analysis_type'] = $analysis['analysis_metadata']['system_type'] ?? 'unknown';
                
            } catch (Exception $e) {
                $debug_info['analyzer_test'] = 'failed: ' . $e->getMessage();
            }
        }
        
    } catch (Exception $e) {
        $debug_info['processing_error'] = $e->getMessage();
    }
}

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>