<?php
// Simple cleanup script to remove test users

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🧹 Simple test user cleanup...\n\n";
    
    // Define test email patterns
    $testEmails = [
        'test@gmail.com',
        'test@test.com',
        'demo@gmail.com',
        'demo@demo.com',
        'sample@gmail.com',
        'example@gmail.com',
        'dummy@gmail.com',
        'fake@gmail.com',
        'temp@gmail.com',
        'user@gmail.com',
        'admin@gmail.com',
        'contractor@gmail.com',
        'architect@gmail.com',
        'homeowner@gmail.com',
        'testcontractor@gmail.com',
        'testarchitect@gmail.com',
        'testhomeowner@gmail.com'
    ];
    
    // Get all users
    $allUsers = $db->prepare("SELECT id, first_name, last_name, email, role FROM users ORDER BY id");
    $allUsers->execute();
    $users = $allUsers->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Current users:\n";
    foreach ($users as $user) {
        echo "   ID: {$user['id']} | {$user['first_name']} {$user['last_name']} | {$user['email']} | Role: {$user['role']}\n";
    }
    echo "\n";
    
    $deletedCount = 0;
    $keptCount = 0;
    
    foreach ($users as $user) {
        $shouldDelete = false;
        
        // Skip main admin
        if ($user['email'] === 'shijinthomas369@gmail.com') {
            echo "✅ KEEPING: {$user['first_name']} {$user['last_name']} ({$user['email']}) - Main admin\n";
            $keptCount++;
            continue;
        }
        
        // Check if email matches test patterns
        foreach ($testEmails as $testEmail) {
            if ($user['email'] === $testEmail) {
                $shouldDelete = true;
                break;
            }
        }
        
        // Check for test-like names
        if (!$shouldDelete) {
            $testNames = ['Test', 'Demo', 'Sample', 'Example', 'Dummy', 'Fake', 'Temp', 'User'];
            foreach ($testNames as $testName) {
                if (strpos($user['first_name'], $testName) !== false || 
                    strpos($user['last_name'], $testName) !== false) {
                    $shouldDelete = true;
                    break;
                }
            }
        }
        
        if ($shouldDelete) {
            echo "❌ DELETING: {$user['first_name']} {$user['last_name']} ({$user['email']}) - Test user\n";
            
            try {
                $deleteUser = $db->prepare("DELETE FROM users WHERE id = ?");
                $deleteUser->execute([$user['id']]);
                $deletedCount++;
                echo "   ✅ Deleted successfully\n";
            } catch (Exception $e) {
                echo "   ⚠️  Error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✅ KEEPING: {$user['first_name']} {$user['last_name']} ({$user['email']}) - Valid user\n";
            $keptCount++;
        }
    }
    
    echo "\n📊 Cleanup Summary:\n";
    echo "   🗑️  Deleted: $deletedCount user(s)\n";
    echo "   ✅ Kept: $keptCount user(s)\n\n";
    
    // Show remaining users
    $remainingUsers = $db->prepare("SELECT id, first_name, last_name, email, role, status FROM users ORDER BY role, first_name");
    $remainingUsers->execute();
    $remaining = $remainingUsers->fetchAll(PDO::FETCH_ASSOC);
    
    echo "👥 Remaining users:\n";
    foreach ($remaining as $user) {
        echo "   ID: {$user['id']} | {$user['first_name']} {$user['last_name']} | {$user['email']} | Role: {$user['role']} | Status: {$user['status']}\n";
    }
    
    echo "\n🎉 Cleanup completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
