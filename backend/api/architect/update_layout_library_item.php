<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    session_start();
    $architect_id = $_SESSION['user_id'] ?? null;
    if (!$architect_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    // Ensure table exists (columns used below)
    $db->exec("CREATE TABLE IF NOT EXISTS layout_library (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        layout_type VARCHAR(100) NOT NULL,
        bedrooms INT NOT NULL,
        bathrooms INT NOT NULL,
        area INT NOT NULL,
        description TEXT,
        image_url VARCHAR(500),
        design_file_url VARCHAR(500),
        price_range VARCHAR(100),
        architect_id INT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (architect_id) REFERENCES users(id)
    )");
    try { $db->exec("ALTER TABLE layout_library ADD COLUMN IF NOT EXISTS design_file_url VARCHAR(500) NULL AFTER image_url"); } catch (Exception $__) {}

    // Accept multipart/form-data or JSON
    $id = null;
    $fields = [];
    
    // Read raw input once (can only be read once)
    $rawInput = @file_get_contents('php://input');
    
    // Determine content type - check all possible locations
    $contentType = '';
    if (isset($_SERVER['CONTENT_TYPE'])) {
        $contentType = $_SERVER['CONTENT_TYPE'];
    } elseif (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
        $contentType = $_SERVER['HTTP_CONTENT_TYPE'];
    }
    $isJson = stripos($contentType, 'application/json') !== false;
    $isMultipart = stripos($contentType, 'multipart/form-data') !== false;
    
    // Debug logging (comment out in production)
    // error_log("Request received - Content-Type: $contentType");
    // error_log("Is JSON: " . ($isJson ? 'true' : 'false'));
    // error_log("Is Multipart: " . ($isMultipart ? 'true' : 'false'));
    // error_log("Raw input: " . substr($rawInput, 0, 200));
    
    // If JSON, read from raw input
    if ($isJson && !empty($rawInput)) {
        $input = json_decode($rawInput, true);
        // error_log("JSON decode result: " . print_r($input, true));
        if (is_array($input) && isset($input['id'])) {
            $id = (int)$input['id'];
            $fields = $input;
            // error_log("Parsed ID: $id");
        }
    }
    // If FormData or multipart, read from $_POST
    elseif ($isMultipart || !empty($_POST)) {
        if (!empty($_POST) && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $fields = $_POST;
        }
    }
    // Try $_GET as fallback
    elseif (!empty($_GET) && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $fields = $_GET;
    }
    // Last resort: try reading php://input anyway (for JSON without proper headers)
    if (empty($id) && !empty($rawInput)) {
        $input = json_decode($rawInput, true);
        if (is_array($input) && isset($input['id'])) {
            $id = (int)$input['id'];
            $fields = $input;
        }
    }

    if (!$id || $id <= 0) {
        // Enable debug logging
        error_log("Layout update failed - ID: " . var_export($id, true));
        error_log("Content-Type: " . ($contentType ?? 'not set'));
        error_log("Is JSON: " . ($isJson ? 'true' : 'false'));
        error_log("Is Multipart: " . ($isMultipart ? 'true' : 'false'));
        error_log("Raw input: " . $rawInput);
        error_log("POST: " . print_r($_POST, true));
        
        echo json_encode([
            'success' => false, 
            'message' => 'Missing or invalid layout id',
            'debug' => [
                'id' => $id,
                'content_type' => $contentType,
                'is_json' => $isJson,
                'raw_input' => $rawInput
            ]
        ]);
        exit;
    }

    // Ensure layout belongs to this architect
    $ownStmt = $db->prepare('SELECT * FROM layout_library WHERE id = :id AND architect_id = :aid');
    $ownStmt->execute([':id' => $id, ':aid' => $architect_id]);
    $existing = $ownStmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Layout not found or not owned by you']);
        exit;
    }

    $columns = [];
    $params = [':id' => $id];

    $map = [
        'title' => 'title',
        'layout_type' => 'layout_type',
        'bedrooms' => 'bedrooms',
        'bathrooms' => 'bathrooms',
        'area' => 'area',
        'description' => 'description',
        'price_range' => 'price_range',
        'view_price' => 'view_price',
        'status' => 'status'
    ];

    foreach ($map as $key => $col) {
        if (isset($fields[$key]) && $fields[$key] !== '') {
            $columns[] = "$col = :$key";
            // cast numbers
            if (in_array($key, ['bedrooms','bathrooms','area'])) {
                $params[":$key"] = (int)$fields[$key];
            } elseif ($key === 'view_price') {
                $params[":$key"] = (float)$fields[$key];
            } else {
                $params[":$key"] = $fields[$key];
            }
        }
    }

    // Handle optional image upload
    if (!empty($_FILES['image']['name'])) {
        $dir = __DIR__ . '/../../uploads/designs';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $safe = 'lib_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $safe;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save image']);
            exit;
        }
        $columns[] = 'image_url = :image_url';
        $params[':image_url'] = '/buildhub/backend/uploads/designs/' . $safe;
    }

    // Handle optional design file upload
    if (!empty($_FILES['design_file']['name'])) {
        $dir = __DIR__ . '/../../uploads/designs';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $ext = strtolower(pathinfo($_FILES['design_file']['name'], PATHINFO_EXTENSION));
        $safe = 'libfile_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $safe;
        if (!move_uploaded_file($_FILES['design_file']['tmp_name'], $dest)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save design file']);
            exit;
        }
        $columns[] = 'design_file_url = :design_file_url';
        $params[':design_file_url'] = '/buildhub/backend/uploads/designs/' . $safe;
    }

    if (empty($columns)) {
        echo json_encode(['success' => false, 'message' => 'No changes provided']);
        exit;
    }

    $sql = 'UPDATE layout_library SET ' . implode(', ', $columns) . ' WHERE id = :id';
    $stmt = $db->prepare($sql);
    $ok = $stmt->execute($params);

    echo json_encode(['success' => (bool)$ok]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}