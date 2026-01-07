<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✓ MySQL connection successful\n";
        
        // Check if house_plans table exists
        $stmt = $db->query("SHOW TABLES LIKE 'house_plans'");
        if ($stmt->rowCount() > 0) {
            echo "✓ house_plans table exists\n";
            
            // Check for house plans with layout images
            $stmt = $db->query("SELECT id, plan_name, layout_image FROM house_plans WHERE layout_image IS NOT NULL LIMIT 5");
            $plans = $stmt->fetchAll();
            
            echo "House plans with images:\n";
            foreach ($plans as $plan) {
                echo "ID: {$plan['id']}, Name: {$plan['plan_name']}, Image: {$plan['layout_image']}\n";
            }
        } else {
            echo "✗ house_plans table does not exist\n";
        }
        
        // Check users table
        $stmt = $db->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "✓ users table exists\n";
            
            // Check for SHIJIN user
            $stmt = $db->prepare("SELECT id, name, email FROM users WHERE name LIKE ? OR email LIKE ?");
            $stmt->execute(['%SHIJIN%', '%shijin%']);
            $users = $stmt->fetchAll();
            
            echo "SHIJIN users:\n";
            foreach ($users as $user) {
                echo "ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}\n";
            }
        } else {
            echo "✗ users table does not exist\n";
        }
        
    } else {
        echo "✗ Failed to connect to MySQL\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>