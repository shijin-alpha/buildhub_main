<?php
// Test script to debug profile API
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once '../../config/database.php';

echo "Testing profile API...\n";

try {
    // Test database connection
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    echo "Database connection: OK\n";
    
    // Test user_id parameter
    $user_id = $_GET['user_id'] ?? null;
    echo "User ID from GET: " . ($user_id ?? 'NULL') . "\n";
    
    if (!$user_id) {
        throw new Exception('User ID is required');
    }
    
    // Test if user exists
    $test_stmt = $db->prepare("SELECT id, first_name, last_name, role FROM users WHERE id = :user_id");
    $test_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $test_stmt->execute();
    
    $test_user = $test_stmt->fetch(PDO::FETCH_ASSOC);
    echo "User found: " . ($test_user ? 'YES' : 'NO') . "\n";
    
    if ($test_user) {
        echo "User data: " . json_encode($test_user) . "\n";
        
        // Check if user is homeowner
        if ($test_user['role'] !== 'homeowner') {
            echo "User role: " . $test_user['role'] . " (not homeowner)\n";
        } else {
            echo "User is homeowner: YES\n";
        }
    }
    
    // Test the actual query
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
    
    if (!$user) {
        throw new Exception('User not found or not a homeowner');
    }
    
    echo "Profile query successful\n";
    echo "User profile: " . json_encode($user) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "PHP Error: " . $e->getMessage() . "\n";
}
?>

