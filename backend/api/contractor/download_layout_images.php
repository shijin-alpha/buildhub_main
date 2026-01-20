<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { 
    header('Access-Control-Allow-Origin: ' . $origin); 
    header('Vary: Origin'); 
} else { 
    header('Access-Control-Allow-Origin: http://localhost'); 
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    $contractor_id = isset($input['contractor_id']) ? (int)$input['contractor_id'] : 0;
    $inbox_item_id = isset($input['inbox_item_id']) ? (int)$input['inbox_item_id'] : 0;

    if ($contractor_id <= 0 || $inbox_item_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    // Verify contractor has access to this inbox item
    $checkStmt = $db->prepare("
        SELECT payload 
        FROM contractor_layout_sends 
        WHERE id = :inbox_id AND contractor_id = :contractor_id
    ");
    $checkStmt->execute([
        ':inbox_id' => $inbox_item_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $item = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Inbox item not found or access denied']);
        exit;
    }

    $payload = json_decode($item['payload'], true);
    if (!$payload || !isset($payload['layout_images']) || !is_array($payload['layout_images'])) {
        echo json_encode(['success' => false, 'message' => 'No layout images found']);
        exit;
    }

    $layout_images = $payload['layout_images'];
    $download_info = [];
    $upload_dir = '../../uploads/house_plans/';

    foreach ($layout_images as $index => $image) {
        $stored_name = $image['stored'] ?? $image['original'] ?? "layout_$index.png";
        $file_path = $upload_dir . $stored_name;
        
        if (file_exists($file_path)) {
            $download_info[] = [
                'name' => $image['original'] ?? $stored_name,
                'stored' => $stored_name,
                'url' => '/buildhub/backend/uploads/house_plans/' . $stored_name,
                'size' => filesize($file_path),
                'exists' => true
            ];
        } else {
            $download_info[] = [
                'name' => $image['original'] ?? $stored_name,
                'stored' => $stored_name,
                'url' => '/buildhub/backend/uploads/house_plans/' . $stored_name,
                'size' => 0,
                'exists' => false
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Layout images information retrieved',
        'images' => $download_info,
        'total_count' => count($download_info),
        'available_count' => count(array_filter($download_info, function($img) { return $img['exists']; }))
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>