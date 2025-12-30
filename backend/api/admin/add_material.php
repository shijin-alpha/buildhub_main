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
    
    // Validate required fields
    $required = ['name', 'category', 'unit', 'price'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            $response['message'] = ucfirst($field) . " is required.";
            echo json_encode($response);
            exit;
        }
    }
    
    $name = trim($input['name']);
    $category = trim($input['category']);
    $unit = trim($input['unit']);
    $price = floatval($input['price']);
    $description = isset($input['description']) ? trim($input['description']) : '';
    
    // Validate price
    if ($price <= 0) {
        $response['message'] = 'Price must be greater than 0.';
        echo json_encode($response);
        exit;
    }
    
    // Check if material with same name and category already exists
    $stmt = $pdo->prepare("SELECT id FROM materials WHERE name = ? AND category = ?");
    $stmt->execute([$name, $category]);
    if ($stmt->fetch()) {
        $response['message'] = 'A material with this name already exists in the selected category.';
        echo json_encode($response);
        exit;
    }
    
    // Insert new material
    $stmt = $pdo->prepare("
        INSERT INTO materials (name, category, unit, price, description) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([$name, $category, $unit, $price, $description]);
    
    if ($result) {
        $response['success'] = true;
        $response['message'] = 'Material added successfully.';
        $response['material_id'] = $pdo->lastInsertId();
    } else {
        $response['message'] = 'Failed to add material.';
    }
    
} catch (PDOException $e) {
    error_log("Add material error: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
} catch (Exception $e) {
    error_log("Add material error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
?>