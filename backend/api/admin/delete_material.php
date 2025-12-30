<?php
// Disable HTML error output for clean JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set up error handler to catch any PHP errors and return JSON
set_error_handler(function($severity, $message, $file, $line) {
    $response = ['success' => false, 'message' => 'Server error occurred. Please try again.'];
    echo json_encode($response);
    exit();
});

try {
    require_once __DIR__ . '/../../config/db.php';
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Database connection failed.'];
    echo json_encode($response);
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['material_id'])) {
        $response['message'] = 'Material ID is required.';
        echo json_encode($response);
        exit;
    }
    
    $materialId = (int)$input['material_id'];
    
    // Check if material exists
    $stmt = $pdo->prepare("SELECT name FROM materials WHERE id = ?");
    $stmt->execute([$materialId]);
    $material = $stmt->fetch();
    
    if (!$material) {
        $response['message'] = 'Material not found.';
        echo json_encode($response);
        exit;
    }
    
    // Delete material
    $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
    $result = $stmt->execute([$materialId]);
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Material deleted successfully.';
    } else {
        $response['message'] = 'Failed to delete material.';
    }
    
} catch (PDOException $e) {
    error_log("Delete material error: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
} catch (Exception $e) {
    error_log("Delete material error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
?>