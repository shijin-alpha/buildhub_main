<?php
// Test script to debug house plan save issues
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config/database.php';

try {
    echo "=== House Plan Save Debug Test ===\n\n";
    
    // Test database connection
    $database = new Database();
    $db = $database->getConnection();
    echo "✓ Database connection successful\n";
    
    // Check if house_plans table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'house_plans'");
    if ($tableCheck->rowCount() > 0) {
        echo "✓ house_plans table exists\n";
        
        // Show table structure
        $structure = $db->query("DESCRIBE house_plans");
        echo "\nTable structure:\n";
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "  {$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
        }
    } else {
        echo "✗ house_plans table does not exist\n";
    }
    
    // Check session
    session_start();
    echo "\nSession info:\n";
    echo "  Session ID: " . session_id() . "\n";
    echo "  User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
    echo "  User Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
    
    // Check if we have any users with architect role
    $architectCheck = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'architect'");
    $architectCount = $architectCheck->fetch(PDO::FETCH_ASSOC);
    echo "\nArchitects in database: " . $architectCount['count'] . "\n";
    
    // Test JSON encoding
    $testData = [
        'rooms' => [
            [
                'id' => 1,
                'name' => 'Test Room',
                'layout_width' => 10,
                'layout_height' => 10,
                'x' => 50,
                'y' => 50
            ]
        ],
        'scale_ratio' => 1.2
    ];
    
    $jsonTest = json_encode($testData);
    if ($jsonTest !== false) {
        echo "✓ JSON encoding test successful\n";
    } else {
        echo "✗ JSON encoding failed: " . json_last_error_msg() . "\n";
    }
    
    // Test if we can insert a simple record (if user is authenticated)
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        
        // Check if user exists and is architect
        $userCheck = $db->prepare("SELECT role FROM users WHERE id = :id");
        $userCheck->execute([':id' => $userId]);
        $user = $userCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['role'] === 'architect') {
            echo "✓ User is authenticated architect\n";
            
            // Try to insert a test plan
            try {
                $testInsert = $db->prepare("
                    INSERT INTO house_plans (architect_id, plan_name, plot_width, plot_height, plan_data, total_area, notes)
                    VALUES (:architect_id, :plan_name, :plot_width, :plot_height, :plan_data, :total_area, :notes)
                ");
                
                $success = $testInsert->execute([
                    ':architect_id' => $userId,
                    ':plan_name' => 'Test Plan - ' . date('Y-m-d H:i:s'),
                    ':plot_width' => 100,
                    ':plot_height' => 100,
                    ':plan_data' => $jsonTest,
                    ':total_area' => 100,
                    ':notes' => 'Test plan for debugging'
                ]);
                
                if ($success) {
                    $testPlanId = $db->lastInsertId();
                    echo "✓ Test plan created successfully with ID: $testPlanId\n";
                    
                    // Clean up test plan
                    $cleanup = $db->prepare("DELETE FROM house_plans WHERE id = :id");
                    $cleanup->execute([':id' => $testPlanId]);
                    echo "✓ Test plan cleaned up\n";
                } else {
                    echo "✗ Failed to create test plan\n";
                }
            } catch (Exception $e) {
                echo "✗ Test plan creation error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✗ User is not an architect or doesn't exist\n";
        }
    } else {
        echo "✗ No user session found\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>