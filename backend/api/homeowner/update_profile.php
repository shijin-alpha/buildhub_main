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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $user_id = $input['user_id'] ?? null;
    $first_name = $input['first_name'] ?? null;
    $last_name = $input['last_name'] ?? null;
    $phone = $input['phone'] ?? null;
    
    if (!$user_id) {
        throw new Exception('User ID is required');
    }
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Validate required fields
    if (!$first_name || !$last_name) {
        throw new Exception('First name and last name are required');
    }
    
    // Check if user exists and is a homeowner
    $check_stmt = $db->prepare("SELECT id FROM users WHERE id = :user_id AND role = 'homeowner'");
    $check_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $check_stmt->execute();
    
    if (!$check_stmt->fetch()) {
        throw new Exception('User not found or not authorized');
    }
    
    // Update user profile
    $update_fields = [];
    $params = [':user_id' => $user_id];
    
    if ($first_name) {
        $update_fields[] = "first_name = :first_name";
        $params[':first_name'] = $first_name;
    }
    
    if ($last_name) {
        $update_fields[] = "last_name = :last_name";
        $params[':last_name'] = $last_name;
    }
    
    if ($phone) {
        $update_fields[] = "phone = :phone";
        $params[':phone'] = $phone;
    }
    
    $update_fields[] = "updated_at = NOW()";
    
    if (empty($update_fields)) {
        throw new Exception('No fields to update');
    }
    
    $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = :user_id";
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    // Get updated user data
    $get_stmt = $db->prepare("
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
        WHERE id = :user_id
    ");
    $get_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $get_stmt->execute();
    
    $updated_user = $get_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => $updated_user
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
