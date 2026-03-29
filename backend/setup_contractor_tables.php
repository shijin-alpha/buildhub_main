<?php
header('Content-Type: text/html; charset=utf-8');

require_once 'config/database.php';

echo "<h1>Setting Up Contractor House Plans Tables</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>1. Creating contractor_engagements table</h2>";
    
    // Create contractor_engagements table
    $createEngagementsTable = "
        CREATE TABLE IF NOT EXISTS contractor_engagements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            homeowner_id INT NOT NULL,
            contractor_id INT NOT NULL,
            layout_request_id INT NULL,
            house_plan_id INT NULL,
            engagement_type VARCHAR(50) DEFAULT 'estimate_request',
            status VARCHAR(50) DEFAULT 'pending',
            message TEXT,
            project_details JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_homeowner (homeowner_id),
            INDEX idx_contractor (contractor_id),
            INDEX idx_layout_request (layout_request_id),
            INDEX idx_house_plan (house_plan_id),
            INDEX idx_status (status),
            
            FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (layout_request_id) REFERENCES layout_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (house_plan_id) REFERENCES house_plans(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->exec($createEngagementsTable);
    echo "✅ contractor_engagements table created successfully<br>";
    
    echo "<h2>2. Checking contractor_layout_sends table</h2>";
    
    // Check if contractor_layout_sends exists
    $stmt = $db->query("SHOW TABLES LIKE 'contractor_layout_sends'");
    if ($stmt->rowCount() === 0) {
        echo "Creating contractor_layout_sends table...<br>";
        
        $createSendsTable = "
            CREATE TABLE IF NOT EXISTS contractor_layout_sends (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contractor_id INT NOT NULL,
                homeowner_id INT NOT NULL,
                layout_id INT NULL,
                design_id INT NULL,
                house_plan_id INT NULL,
                message TEXT,
                payload JSON,
                acknowledged_at DATETIME NULL,
                due_date DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                INDEX idx_contractor (contractor_id),
                INDEX idx_homeowner (homeowner_id),
                INDEX idx_layout (layout_id),
                INDEX idx_design (design_id),
                INDEX idx_house_plan (house_plan_id),
                INDEX idx_acknowledged (acknowledged_at),
                
                FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (house_plan_id) REFERENCES house_plans(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($createSendsTable);
        echo "✅ contractor_layout_sends table created successfully<br>";
    } else {
        echo "✅ contractor_layout_sends table already exists<br>";
        
        // Check if house_plan_id column exists
        $columns = $db->query("SHOW COLUMNS FROM contractor_layout_sends")->fetchAll(PDO::FETCH_COLUMN, 0);
        if (!in_array('house_plan_id', $columns)) {
            echo "Adding house_plan_id column to contractor_layout_sends...<br>";
            $db->exec("ALTER TABLE contractor_layout_sends ADD COLUMN house_plan_id INT NULL AFTER design_id");
            $db->exec("ALTER TABLE contractor_layout_sends ADD INDEX idx_house_plan (house_plan_id)");
            echo "✅ house_plan_id column added successfully<br>";
        } else {
            echo "✅ house_plan_id column already exists<br>";
        }
    }
    
    echo "<h2>3. Checking contractor_send_estimates table</h2>";
    
    // Check if contractor_send_estimates exists
    $stmt = $db->query("SHOW TABLES LIKE 'contractor_send_estimates'");
    if ($stmt->rowCount() === 0) {
        echo "Creating contractor_send_estimates table...<br>";
        
        $createEstimatesTable = "
            CREATE TABLE IF NOT EXISTS contractor_send_estimates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                contractor_id INT NOT NULL,
                send_id INT NULL,
                engagement_id INT NULL,
                status VARCHAR(50) DEFAULT 'pending',
                total_amount DECIMAL(15,2) NULL,
                estimate_details JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                accepted_at DATETIME NULL,
                
                INDEX idx_contractor (contractor_id),
                INDEX idx_send (send_id),
                INDEX idx_engagement (engagement_id),
                INDEX idx_status (status),
                
                FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (send_id) REFERENCES contractor_layout_sends(id) ON DELETE CASCADE,
                FOREIGN KEY (engagement_id) REFERENCES contractor_engagements(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($createEstimatesTable);
        echo "✅ contractor_send_estimates table created successfully<br>";
    } else {
        echo "✅ contractor_send_estimates table already exists<br>";
    }
    
    echo "<h2>4. Creating sample test data</h2>";
    
    // Check if we have any house plans to work with
    $stmt = $db->query("SELECT COUNT(*) as count FROM house_plans");
    $housePlansCount = $stmt->fetch()['count'];
    
    if ($housePlansCount == 0) {
        echo "⚠️ No house plans found. Creating a sample house plan...<br>";
        
        // Create a sample house plan
        $createSamplePlan = "
            INSERT INTO house_plans 
            (architect_id, plan_name, plot_width, plot_height, total_area, status, plan_data, created_at) 
            VALUES 
            (1, 'Sample Modern House', 40, 60, 2400, 'approved', 
             '{\"rooms\": [{\"id\": 1, \"name\": \"Living Room\", \"width\": 20, \"height\": 15}]}', 
             NOW())
        ";
        
        try {
            $db->exec($createSamplePlan);
            echo "✅ Sample house plan created<br>";
        } catch (Exception $e) {
            echo "⚠️ Could not create sample house plan: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✅ Found $housePlansCount existing house plans<br>";
    }
    
    // Check if we have users to work with
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role IN ('contractor', 'homeowner')");
    $usersCount = $stmt->fetch()['count'];
    
    if ($usersCount < 2) {
        echo "⚠️ Need at least 2 users (contractor and homeowner) for test data<br>";
        echo "Please ensure you have users with roles 'contractor' and 'homeowner' in your users table<br>";
    } else {
        echo "✅ Found $usersCount users for test data<br>";
        
        // Create sample contractor engagement
        $stmt = $db->query("SELECT id FROM house_plans LIMIT 1");
        $housePlan = $stmt->fetch();
        
        $stmt = $db->query("SELECT id FROM users WHERE role = 'homeowner' LIMIT 1");
        $homeowner = $stmt->fetch();
        
        $stmt = $db->query("SELECT id FROM users WHERE role = 'contractor' LIMIT 1");
        $contractor = $stmt->fetch();
        
        if ($housePlan && $homeowner && $contractor) {
            // Check if sample data already exists
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM contractor_engagements WHERE house_plan_id = ?");
            $stmt->execute([$housePlan['id']]);
            $existingEngagements = $stmt->fetch()['count'];
            
            if ($existingEngagements == 0) {
                echo "Creating sample contractor engagement...<br>";
                
                $createSampleEngagement = "
                    INSERT INTO contractor_engagements 
                    (homeowner_id, contractor_id, house_plan_id, engagement_type, status, message, created_at) 
                    VALUES 
                    (?, ?, ?, 'estimate_request', 'pending', 'Please provide construction estimate for this house plan', NOW())
                ";
                
                $stmt = $db->prepare($createSampleEngagement);
                $stmt->execute([$homeowner['id'], $contractor['id'], $housePlan['id']]);
                echo "✅ Sample contractor engagement created<br>";
                
                // Create sample layout send
                echo "Creating sample layout send...<br>";
                
                $createSampleSend = "
                    INSERT INTO contractor_layout_sends 
                    (contractor_id, homeowner_id, house_plan_id, message, created_at) 
                    VALUES 
                    (?, ?, ?, 'House plan sent for construction estimate', NOW())
                ";
                
                $stmt = $db->prepare($createSampleSend);
                $stmt->execute([$contractor['id'], $homeowner['id'], $housePlan['id']]);
                $sendId = $db->lastInsertId();
                echo "✅ Sample layout send created<br>";
                
                // Create sample estimate
                echo "Creating sample estimate...<br>";
                
                $createSampleEstimate = "
                    INSERT INTO contractor_send_estimates 
                    (contractor_id, send_id, status, total_amount, created_at) 
                    VALUES 
                    (?, ?, 'pending', 5500000.00, NOW())
                ";
                
                $stmt = $db->prepare($createSampleEstimate);
                $stmt->execute([$contractor['id'], $sendId]);
                echo "✅ Sample estimate created<br>";
            } else {
                echo "✅ Sample data already exists ($existingEngagements engagements found)<br>";
            }
        } else {
            echo "⚠️ Could not create sample data - missing required records<br>";
        }
    }
    
    echo "<h2>5. Verification</h2>";
    
    // Verify tables exist
    $tables = ['contractor_engagements', 'contractor_layout_sends', 'contractor_send_estimates'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "✅ $table: $count records<br>";
        } else {
            echo "❌ $table: Table missing<br>";
        }
    }
    
    echo "<h2>6. Test API</h2>";
    echo "<p>Now you can test the contractor house plans API:</p>";
    echo "<ul>";
    echo "<li><a href='/buildhub/backend/api/architect/get_contractor_house_plans.php' target='_blank'>Test API directly</a></li>";
    echo "<li><a href='/buildhub/backend/debug_contractor_house_plans.php' target='_blank'>Run debug script</a></li>";
    echo "<li><a href='/test_contractor_api.php' target='_blank'>Test with session handling</a></li>";
    echo "</ul>";
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ Setup Complete!</h3>";
    echo "<p style='color: #155724; margin-bottom: 0;'>All required tables have been created. You can now use the contractor house plans feature in the architect dashboard.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Setup Failed</h3>";
    echo "<p style='color: #721c24;'>Error: " . $e->getMessage() . "</p>";
    echo "<p style='color: #721c24;'>File: " . $e->getFile() . "</p>";
    echo "<p style='color: #721c24;'>Line: " . $e->getLine() . "</p>";
    echo "</div>";
    
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure your database user has CREATE and ALTER privileges</li>";
    echo "<li>Ensure the users, house_plans, and layout_requests tables exist</li>";
    echo "<li>Check that your database connection is working</li>";
    echo "</ul>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background: #f8f9fa;
}
h1, h2, h3 { 
    color: #333; 
}
h1 {
    background: #007bff;
    color: white;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}
h2 {
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
    margin-top: 30px;
}
ul {
    background: white;
    padding: 15px;
    border-radius: 5px;
    border: 1px solid #dee2e6;
}
li {
    margin-bottom: 5px;
}
a {
    color: #007bff;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>