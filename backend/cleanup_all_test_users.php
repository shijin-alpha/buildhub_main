<?php
// Comprehensive cleanup of all test users across all roles

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🧹 Comprehensive test user cleanup...\n\n";
    
    // Define test user patterns
    $testEmailPatterns = [
        'test@gmail.com',
        'test@test.com',
        'demo@gmail.com',
        'demo@demo.com',
        'sample@gmail.com',
        'example@gmail.com',
        'dummy@gmail.com',
        'fake@gmail.com',
        'temp@gmail.com',
        'temp@temp.com',
        'user@gmail.com',
        'user@user.com',
        'admin@gmail.com',
        'admin@admin.com',
        'contractor@gmail.com',
        'architect@gmail.com',
        'homeowner@gmail.com',
        'testcontractor@gmail.com',
        'testarchitect@gmail.com',
        'testhomeowner@gmail.com'
    ];
    
    $testNamePatterns = [
        'Test', 'Demo', 'Sample', 'Example', 'Dummy', 'Fake', 'Temp', 'User', 'Admin'
    ];
    
    $totalDeleted = 0;
    $totalKept = 0;
    
    // Get all users
    $allUsers = $db->prepare("SELECT id, first_name, last_name, email, role, status, created_at FROM users ORDER BY role, created_at");
    $allUsers->execute();
    $users = $allUsers->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Current users in system:\n";
    foreach ($users as $user) {
        echo "   ID: {$user['id']} | {$user['first_name']} {$user['last_name']} | {$user['email']} | Role: {$user['role']} | Status: {$user['status']}\n";
    }
    echo "\n";
    
    echo "🔍 Analyzing users for cleanup...\n\n";
    
    foreach ($users as $user) {
        $shouldDelete = false;
        $reason = '';
        
        // Skip the main admin account
        if ($user['email'] === 'shijinthomas369@gmail.com') {
            echo "✅ KEEPING: {$user['first_name']} {$user['last_name']} ({$user['email']}) - Main admin account\n";
            $totalKept++;
            continue;
        }
        
        // Check email patterns
        foreach ($testEmailPatterns as $pattern) {
            if (strpos($user['email'], $pattern) !== false) {
                $shouldDelete = true;
                $reason = "Test email pattern: $pattern";
                break;
            }
        }
        
        // Check name patterns
        if (!$shouldDelete) {
            foreach ($testNamePatterns as $name) {
                if (strpos($user['first_name'], $name) !== false || 
                    strpos($user['last_name'], $name) !== false) {
                    $shouldDelete = true;
                    $reason = "Test name pattern: $name";
                    break;
                }
            }
        }
        
        // Check for very basic test accounts (no profile data)
        if (!$shouldDelete && in_array($user['role'], ['contractor', 'architect'])) {
            $checkProfile = $db->prepare("SELECT company_name, phone, city, specialization FROM users WHERE id = ?");
            $checkProfile->execute([$user['id']]);
            $profile = $checkProfile->fetch(PDO::FETCH_ASSOC);
            
            if (empty($profile['company_name']) && 
                empty($profile['phone']) && 
                empty($profile['city'])) {
                $shouldDelete = true;
                $reason = "No profile data (likely test account)";
            }
        }
        
        if ($shouldDelete) {
            echo "❌ DELETING: {$user['first_name']} {$user['last_name']} ({$user['email']}) - $reason\n";
            
            try {
                // Delete related data first
                $userId = $user['id'];
                
                // Delete assignments
                $deleteAssignments = $db->prepare("DELETE FROM architect_assignments WHERE architect_id = ? OR homeowner_id = ?");
                $deleteAssignments->execute([$userId, $userId]);
                
                // Delete reviews
                $deleteReviews = $db->prepare("DELETE FROM reviews WHERE architect_id = ? OR homeowner_id = ?");
                $deleteReviews->execute([$userId, $userId]);
                
                // Update layout requests
                $updateRequests = $db->prepare("UPDATE layout_requests SET assigned_architect_id = NULL WHERE assigned_architect_id = ?");
                $updateRequests->execute([$userId]);
                
                // Delete the user
                $deleteUser = $db->prepare("DELETE FROM users WHERE id = ?");
                $deleteUser->execute([$userId]);
                
                $totalDeleted++;
                echo "   ✅ Deleted successfully\n";
                
            } catch (Exception $e) {
                echo "   ⚠️  Error deleting: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✅ KEEPING: {$user['first_name']} {$user['last_name']} ({$user['email']}) - Valid user\n";
            $totalKept++;
        }
    }
    
    echo "\n📊 Cleanup Summary:\n";
    echo "   🗑️  Total deleted: $totalDeleted user(s)\n";
    echo "   ✅ Total kept: $totalKept user(s)\n\n";
    
    // Show remaining users by role
    $remainingUsers = $db->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY role");
    $remainingUsers->execute();
    $remaining = $remainingUsers->fetchAll(PDO::FETCH_ASSOC);
    
    echo "👥 Remaining users by role:\n";
    foreach ($remaining as $role) {
        echo "   {$role['role']}: {$role['count']} user(s)\n";
    }
    
    // Show detailed remaining users
    $detailedUsers = $db->prepare("SELECT id, first_name, last_name, email, role, status, company_name FROM users ORDER BY role, first_name");
    $detailedUsers->execute();
    $detailed = $detailedUsers->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($detailed) > 0) {
        echo "\n🏛️  Remaining valid users:\n";
        foreach ($detailed as $user) {
            $company = $user['company_name'] ? " | Company: {$user['company_name']}" : "";
            echo "   ID: {$user['id']} | {$user['first_name']} {$user['last_name']} | {$user['email']} | Role: {$user['role']} | Status: {$user['status']}$company\n";
        }
    }
    
    echo "\n🎉 Cleanup completed successfully!\n";
    echo "💡 Only valid, registered users with proper profiles are now in the system.\n";
    
} catch (Exception $e) {
    echo "❌ Error during cleanup: " . $e->getMessage() . "\n";
}
?>
