<?php
// Disable error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

try {
    $user_id = $_GET['user_id'] ?? null;
    
    // Debug logging
    error_log("Profile API called with user_id: " . ($user_id ?? 'NULL'));
    
    if (!$user_id) {
        throw new Exception('User ID is required');
    }
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    error_log("Database connection OK, querying user: " . $user_id);
    
    $stmt = $db->prepare("
        SELECT 
            id,
            first_name,
            last_name,
            email,
            phone,
            role,
            is_verified,
            created_at,
            updated_at
        FROM users 
        WHERE id = :user_id AND role = 'homeowner'
    ");
    
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Query result: " . ($user ? 'User found' : 'User not found'));
    if ($user) {
        error_log("User role: " . ($user['role'] ?? 'NULL'));
    }
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Remove sensitive data
    unset($user['password']);
    
    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>
