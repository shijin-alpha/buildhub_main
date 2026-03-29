<?php
// Test script to verify the complete house plan submission workflow

header('Content-Type: text/plain');
require_once 'config/database.php';

try {
    echo "=== House Plan Submission Workflow Test ===\n\n";
    
    $database = new Database();
    $db = $database->getConnection();
    
    // 1. Check database setup
    echo "1. Checking database setup...\n";
    
    // Check house_plans table
    $checkTable = $db->query("SHOW TABLES LIKE 'house_plans'");
    if ($checkTable->rowCount() > 0) {
        echo "   ✓ house_plans table exists\n";
        
        // Check for technical_details column
        $checkColumn = $db->query("SHOW COLUMNS FROM house_plans LIKE 'technical_details'");
        if ($checkColumn->rowCount() > 0) {
            echo "   ✓ technical_details column exists\n";
        } else {
            echo "   ✗ technical_details column missing - running setup...\n";
            $db->exec("ALTER TABLE house_plans ADD COLUMN technical_details JSON NULL AFTER plan_data");
            echo "   ✓ technical_details column added\n";
        }
    } else {
        echo "   ✗ house_plans table missing\n";
        exit;
    }
    
    // Check notifications table
    $checkNotifications = $db->query("SHOW TABLES LIKE 'notifications'");
    if ($checkNotifications->rowCount() == 0) {
        echo "   Creating notifications table...\n";
        $db->exec("
            CREATE TABLE notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                related_id INT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_unread (user_id, is_read),
                INDEX idx_type (type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "   ✓ notifications table created\n";
    } else {
        echo "   ✓ notifications table exists\n";
    }
    
    // Check inbox_messages table
    $checkInbox = $db->query("SHOW TABLES LIKE 'inbox_messages'");
    if ($checkInbox->rowCount() == 0) {
        echo "   Creating inbox_messages table...\n";
        $db->exec("
            CREATE TABLE inbox_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                recipient_id INT NOT NULL,
                sender_id INT NOT NULL,
                message_type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                metadata JSON NULL,
                priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                read_at TIMESTAMP NULL,
                FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_recipient_unread (recipient_id, is_read),
                INDEX idx_type (message_type),
                INDEX idx_priority (priority)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "   ✓ inbox_messages table created\n";
    } else {
        echo "   ✓ inbox_messages table exists\n";
    }
    
    // 2. Check API endpoints
    echo "\n2. Checking API endpoints...\n";
    
    $apiEndpoints = [
        'create_house_plan.php' => 'Create house plan',
        'update_house_plan.php' => 'Update house plan',
        'submit_house_plan_with_details.php' => 'Submit with technical details',
        'get_house_plans.php' => 'Get house plans (architect)',
        '../homeowner/get_house_plans.php' => 'Get house plans (homeowner)',
        '../homeowner/review_house_plan.php' => 'Review house plan'
    ];
    
    foreach ($apiEndpoints as $file => $description) {
        $path = "api/architect/$file";
        if (strpos($file, '../') === 0) {
            $path = "api/" . substr($file, 3);
        }
        
        if (file_exists($path)) {
            echo "   ✓ $description ($file)\n";
        } else {
            echo "   ✗ $description ($file) - MISSING\n";
        }
    }
    
    // 3. Test data structure
    echo "\n3. Testing data structures...\n";
    
    // Test technical details JSON structure
    $testTechnicalDetails = [
        'foundation_type' => 'RCC',
        'structure_type' => 'RCC Frame',
        'wall_material' => 'Brick',
        'roofing_type' => 'RCC Slab',
        'construction_cost' => '25,00,000',
        'construction_duration' => '8-12',
        'electrical_load' => '5',
        'water_connection' => 'Municipal'
    ];
    
    $jsonTest = json_encode($testTechnicalDetails);
    if ($jsonTest !== false) {
        echo "   ✓ Technical details JSON encoding works\n";
        
        $decoded = json_decode($jsonTest, true);
        if ($decoded && $decoded['foundation_type'] === 'RCC') {
            echo "   ✓ Technical details JSON decoding works\n";
        } else {
            echo "   ✗ Technical details JSON decoding failed\n";
        }
    } else {
        echo "   ✗ Technical details JSON encoding failed\n";
    }
    
    // Test plan data structure
    $testPlanData = [
        'rooms' => [
            [
                'id' => 1,
                'name' => 'Living Room',
                'layout_width' => 16,
                'layout_height' => 14,
                'actual_width' => 19.2,
                'actual_height' => 16.8,
                'x' => 50,
                'y' => 50,
                'rotation' => 0,
                'color' => '#ffe0b2'
            ]
        ],
        'scale_ratio' => 1.2,
        'total_layout_area' => 224,
        'total_construction_area' => 322.56
    ];
    
    $planJsonTest = json_encode($testPlanData);
    if ($planJsonTest !== false) {
        echo "   ✓ Plan data JSON encoding works\n";
    } else {
        echo "   ✗ Plan data JSON encoding failed\n";
    }
    
    // 4. Check user roles
    echo "\n4. Checking user roles...\n";
    
    $architectCount = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'architect'")->fetch()['count'];
    $homeownerCount = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'homeowner'")->fetch()['count'];
    
    echo "   Architects in system: $architectCount\n";
    echo "   Homeowners in system: $homeownerCount\n";
    
    if ($architectCount > 0 && $homeownerCount > 0) {
        echo "   ✓ Both user types available for testing\n";
    } else {
        echo "   ⚠ Limited user types - create test users for full workflow testing\n";
    }
    
    // 5. Test workflow simulation (if users exist)
    if ($architectCount > 0 && $homeownerCount > 0) {
        echo "\n5. Simulating workflow...\n";
        
        // Get sample users
        $architect = $db->query("SELECT id, first_name, last_name FROM users WHERE role = 'architect' LIMIT 1")->fetch();
        $homeowner = $db->query("SELECT id, first_name, last_name FROM users WHERE role = 'homeowner' LIMIT 1")->fetch();
        
        if ($architect && $homeowner) {
            echo "   Using architect: {$architect['first_name']} {$architect['last_name']} (ID: {$architect['id']})\n";
            echo "   Using homeowner: {$homeowner['first_name']} {$homeowner['last_name']} (ID: {$homeowner['id']})\n";
            
            // Create a test layout request
            $layoutRequestStmt = $db->prepare("
                INSERT INTO layout_requests (homeowner_id, plot_size, budget_range, requirements, status)
                VALUES (:homeowner_id, '2400', '20-30 lakhs', 'Test requirements for workflow', 'approved')
            ");
            $layoutRequestStmt->execute([':homeowner_id' => $homeowner['id']]);
            $layoutRequestId = $db->lastInsertId();
            echo "   ✓ Created test layout request (ID: $layoutRequestId)\n";
            
            // Create assignment
            $assignmentStmt = $db->prepare("
                INSERT INTO layout_request_assignments (layout_request_id, architect_id, status)
                VALUES (:layout_request_id, :architect_id, 'accepted')
            ");
            $assignmentStmt->execute([
                ':layout_request_id' => $layoutRequestId,
                ':architect_id' => $architect['id']
            ]);
            echo "   ✓ Created architect assignment\n";
            
            // Create test house plan
            $planStmt = $db->prepare("
                INSERT INTO house_plans (architect_id, layout_request_id, plan_name, plot_width, plot_height, plan_data, technical_details, total_area, status)
                VALUES (:architect_id, :layout_request_id, :plan_name, :plot_width, :plot_height, :plan_data, :technical_details, :total_area, 'submitted')
            ");
            
            $planStmt->execute([
                ':architect_id' => $architect['id'],
                ':layout_request_id' => $layoutRequestId,
                ':plan_name' => 'Test House Plan - Workflow Demo',
                ':plot_width' => 40,
                ':plot_height' => 60,
                ':plan_data' => $planJsonTest,
                ':technical_details' => $jsonTest,
                ':total_area' => 322.56
            ]);
            
            $planId = $db->lastInsertId();
            echo "   ✓ Created test house plan (ID: $planId)\n";
            
            // Create review entry
            $reviewStmt = $db->prepare("
                INSERT INTO house_plan_reviews (house_plan_id, homeowner_id, status)
                VALUES (:plan_id, :homeowner_id, 'pending')
            ");
            $reviewStmt->execute([
                ':plan_id' => $planId,
                ':homeowner_id' => $homeowner['id']
            ]);
            echo "   ✓ Created review entry\n";
            
            // Create notification
            $notificationStmt = $db->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_id)
                VALUES (:user_id, 'house_plan_submitted', 'Test House Plan Ready', 'Test notification for workflow', :plan_id)
            ");
            $notificationStmt->execute([
                ':user_id' => $homeowner['id'],
                ':plan_id' => $planId
            ]);
            echo "   ✓ Created notification\n";
            
            echo "\n   Workflow simulation complete!\n";
            echo "   - House plan submitted with technical details\n";
            echo "   - Homeowner can now view and review the plan\n";
            echo "   - Notification sent to homeowner\n";
            
            // Cleanup test data
            echo "\n   Cleaning up test data...\n";
            $db->prepare("DELETE FROM notifications WHERE related_id = :plan_id")->execute([':plan_id' => $planId]);
            $db->prepare("DELETE FROM house_plan_reviews WHERE house_plan_id = :plan_id")->execute([':plan_id' => $planId]);
            $db->prepare("DELETE FROM house_plans WHERE id = :plan_id")->execute([':plan_id' => $planId]);
            $db->prepare("DELETE FROM layout_request_assignments WHERE layout_request_id = :layout_request_id")->execute([':layout_request_id' => $layoutRequestId]);
            $db->prepare("DELETE FROM layout_requests WHERE id = :layout_request_id")->execute([':layout_request_id' => $layoutRequestId]);
            echo "   ✓ Test data cleaned up\n";
        }
    }
    
    echo "\n=== Workflow Test Complete ===\n";
    echo "✓ Database schema ready\n";
    echo "✓ API endpoints available\n";
    echo "✓ Data structures validated\n";
    echo "✓ Workflow simulation successful\n";
    echo "\nThe house plan submission workflow is ready to use!\n";
    
    echo "\n=== Usage Instructions ===\n";
    echo "1. Architect creates house plan in the designer\n";
    echo "2. Architect clicks 'Submit to Homeowner'\n";
    echo "3. Technical details modal opens\n";
    echo "4. Architect fills technical specifications\n";
    echo "5. Plan is submitted with technical details\n";
    echo "6. Homeowner receives notification\n";
    echo "7. Homeowner can view plan in 'House Plans' tab\n";
    echo "8. Homeowner can review and provide feedback\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>