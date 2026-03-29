<?php
// Disable HTML error output for clean JSON responses
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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

$response = ['success' => false, 'message' => '', 'materials' => []];

try {
    // Check if materials table exists, if not create it
    $stmt = $pdo->query("SHOW TABLES LIKE 'materials'");
    if ($stmt->rowCount() == 0) {
        // Create materials table
        $sql = "CREATE TABLE materials (
            id INT(11) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL,
            unit VARCHAR(50) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_category (category),
            INDEX idx_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $pdo->exec($sql);
    }
    
    // Get all materials
    $stmt = $pdo->prepare("SELECT * FROM materials ORDER BY category, name");
    $stmt->execute();
    $materials = $stmt->fetchAll();
    
    $response['success'] = true;
    $response['materials'] = $materials;
    $response['message'] = 'Materials retrieved successfully.';
    
} catch (PDOException $e) {
    error_log("Get materials error: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
} catch (Exception $e) {
    error_log("Get materials error: " . $e->getMessage());
    $response['message'] = 'An unexpected error occurred.';
}

echo json_encode($response);
?>