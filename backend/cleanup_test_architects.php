<?php
// Clean up test architect users and keep only valid registered architects

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🧹 Cleaning up test architect users...\n\n";
    
    // First, let's see what architect users we have
    $checkArchitects = $db->prepare("SELECT id, first_name, last_name, email, status, created_at FROM users WHERE role = 'architect' ORDER BY created_at");
    $checkArchitects->execute();
    $architects = $checkArchitects->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Current architect users:\n";
    foreach ($architects as $architect) {
        echo "   ID: {$architect['id']} | {$architect['first_name']} {$architect['last_name']} | {$architect['email']} | Status: {$architect['status']} | Created: {$architect['created_at']}\n";
    }
    echo "\n";
    
    // Define test user patterns to identify test accounts
    $testPatterns = [
        'testarchitect@gmail.com',
        'architect@gmail.com',
        'test@',
        'demo@',
        'sample@',
        'example@'
    ];
    
    // Also identify users with test-like names
    $testNames = [
        'Test',
        'Demo',
        'Sample',
        'Example',
        'Dummy'
    ];
    
    $deletedCount = 0;
    $keptCount = 0;
    
    echo "🔍 Analyzing architect users...\n\n";
    
    foreach ($architects as $architect) {
        $shouldDelete = false;
        $reason = '';
        
        // Check email patterns
        foreach ($testPatterns as $pattern) {
            if (strpos($architect['email'], $pattern) !== false) {
                $shouldDelete = true;
                $reason = "Test email pattern: $pattern";
                break;
            }
        }
        
        // Check name patterns
        if (!$shouldDelete) {
            foreach ($testNames as $name) {
                if (strpos($architect['first_name'], $name) !== false || 
                    strpos($architect['last_name'], $name) !== false) {
                    $shouldDelete = true;
                    $reason = "Test name pattern: $name";
                    break;
                }
            }
        }
        
        // Check if user has no real profile data (very basic test accounts)
        if (!$shouldDelete) {
            $checkProfile = $db->prepare("SELECT company_name, phone, city, specialization FROM users WHERE id = ?");
            $checkProfile->execute([$architect['id']]);
            $profile = $checkProfile->fetch(PDO::FETCH_ASSOC);
            
            if (empty($profile['company_name']) && 
                empty($profile['phone']) && 
                empty($profile['city']) && 
                empty($profile['specialization'])) {
                $shouldDelete = true;
                $reason = "No profile data (likely test account)";
            }
        }
        
        if ($shouldDelete) {
            echo "❌ DELETING: {$architect['first_name']} {$architect['last_name']} ({$architect['email']}) - $reason\n";
            
            // Delete related data first (foreign key constraints)
            try {
                // Delete from architect_assignments (check if table exists)
                try {
                    $deleteAssignments = $db->prepare("DELETE FROM architect_assignments WHERE architect_id = ?");
                    $deleteAssignments->execute([$architect['id']]);
                } catch (Exception $e) {
                    // Table might not exist, continue
                }
                
                // Delete from reviews where this architect is the subject
                $deleteReviews = $db->prepare("DELETE FROM reviews WHERE architect_id = ?");
                $deleteReviews->execute([$architect['id']]);
                
                // Delete from layout_requests where this architect is assigned
                $updateRequests = $db->prepare("UPDATE layout_requests SET assigned_architect_id = NULL WHERE assigned_architect_id = ?");
                $updateRequests->execute([$architect['id']]);
                
                // Finally delete the user
                $deleteUser = $db->prepare("DELETE FROM users WHERE id = ?");
                $deleteUser->execute([$architect['id']]);
                
                $deletedCount++;
                echo "   ✅ Deleted successfully\n";
                
            } catch (Exception $e) {
                echo "   ⚠️  Error deleting: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✅ KEEPING: {$architect['first_name']} {$architect['last_name']} ({$architect['email']}) - Valid registered architect\n";
            $keptCount++;
        }
    }
    
    echo "\n📊 Cleanup Summary:\n";
    echo "   🗑️  Deleted: $deletedCount test architect(s)\n";
    echo "   ✅ Kept: $keptCount valid architect(s)\n\n";
    
    // Show remaining architects
    $remainingArchitects = $db->prepare("SELECT id, first_name, last_name, email, status, company_name FROM users WHERE role = 'architect' ORDER BY created_at");
    $remainingArchitects->execute();
    $remaining = $remainingArchitects->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($remaining) > 0) {
        echo "🏛️  Remaining valid architects:\n";
        foreach ($remaining as $architect) {
            echo "   ID: {$architect['id']} | {$architect['first_name']} {$architect['last_name']} | {$architect['email']} | Company: {$architect['company_name']} | Status: {$architect['status']}\n";
        }
    } else {
        echo "ℹ️  No architects remaining in the system.\n";
    }
    
    echo "\n🎉 Cleanup completed successfully!\n";
    echo "💡 Only valid, registered architects with proper profiles are now in the system.\n";
    
} catch (Exception $e) {
    echo "❌ Error during cleanup: " . $e->getMessage() . "\n";
}
?>
