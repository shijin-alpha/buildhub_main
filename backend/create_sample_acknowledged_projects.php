<?php
/**
 * Create Sample Acknowledged Projects with Estimates
 * This script creates sample data for testing the progress update system
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Creating Sample Acknowledged Projects with Estimates</h2>\n";
    
    // Ensure all required tables exist
    echo "<h3>Creating Required Tables...</h3>\n";
    
    // Create users table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(20),
        role ENUM('homeowner', 'contractor', 'architect', 'admin') NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create layout_requests table (using homeowner_id as per existing structure)
    $db->exec("CREATE TABLE IF NOT EXISTS layout_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        homeowner_id INT NOT NULL,
        plot_size VARCHAR(100) NOT NULL,
        budget_range VARCHAR(100) NOT NULL,
        requirements TEXT NOT NULL,
        preferred_style VARCHAR(100),
        location VARCHAR(255),
        timeline VARCHAR(100),
        status ENUM('pending','approved','rejected','active','accepted','declined','deleted') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        selected_layout_id INT NULL,
        layout_type ENUM('custom','library') NULL,
        layout_file VARCHAR(255) NULL,
        site_images TEXT NULL,
        reference_images TEXT NULL,
        room_images TEXT NULL,
        orientation VARCHAR(255) NULL,
        site_considerations TEXT NULL,
        material_preferences TEXT NULL,
        budget_allocation VARCHAR(255) NULL,
        floor_rooms TEXT NULL,
        num_floors VARCHAR(10) NULL,
        building_size VARCHAR(100) NULL
    )");
    
    // Create contractor_layout_sends table
    $db->exec("CREATE TABLE IF NOT EXISTS contractor_layout_sends (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        homeowner_id INT NOT NULL,
        layout_id INT NULL,
        design_id INT NULL,
        message TEXT NULL,
        payload LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        acknowledged_at DATETIME NULL,
        due_date DATE NULL,
        FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Create contractor_send_estimates table
    $db->exec("CREATE TABLE IF NOT EXISTS contractor_send_estimates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        send_id INT NOT NULL,
        contractor_id INT NOT NULL,
        materials TEXT NULL,
        cost_breakdown TEXT NULL,
        total_cost DECIMAL(15,2) NULL,
        timeline VARCHAR(255) NULL,
        notes TEXT NULL,
        structured LONGTEXT NULL,
        status VARCHAR(32) DEFAULT 'submitted',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (send_id) REFERENCES contractor_layout_sends(id) ON DELETE CASCADE
    )");
    
    // Create project_locations table
    $db->exec("CREATE TABLE IF NOT EXISTS project_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        address VARCHAR(500) NOT NULL,
        radius_meters INT DEFAULT 100,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_project_location (project_id)
    )");
    
    echo "✓ Tables created successfully\n<br>";
    
    // Insert sample users
    echo "<h3>Creating Sample Users...</h3>\n";
    
    // Sample homeowners
    $homeowners = [
        ['John', 'Smith', 'john.smith@email.com', '9876543210', 'homeowner123'],
        ['Sarah', 'Johnson', 'sarah.johnson@email.com', '9876543211', 'homeowner123'],
        ['Michael', 'Brown', 'michael.brown@email.com', '9876543212', 'homeowner123']
    ];
    
    // Sample contractors
    $contractors = [
        ['Rajesh', 'Kumar', 'rajesh.contractor@email.com', '9876543220', 'contractor123'],
        ['Amit', 'Sharma', 'amit.contractor@email.com', '9876543221', 'contractor123'],
        ['Priya', 'Patel', 'priya.contractor@email.com', '9876543222', 'contractor123']
    ];
    
    $userStmt = $db->prepare("INSERT IGNORE INTO users (first_name, last_name, email, phone, role, password) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($homeowners as $homeowner) {
        $userStmt->execute([$homeowner[0], $homeowner[1], $homeowner[2], $homeowner[3], 'homeowner', password_hash($homeowner[4], PASSWORD_DEFAULT)]);
        echo "✓ Created homeowner: {$homeowner[0]} {$homeowner[1]}\n<br>";
    }
    
    foreach ($contractors as $contractor) {
        $userStmt->execute([$contractor[0], $contractor[1], $contractor[2], $contractor[3], 'contractor', password_hash($contractor[4], PASSWORD_DEFAULT)]);
        echo "✓ Created contractor: {$contractor[0]} {$contractor[1]}\n<br>";
    }
    
    // Get user IDs
    $homeowner1 = $db->query("SELECT id FROM users WHERE email = 'john.smith@email.com'")->fetch()['id'];
    $homeowner2 = $db->query("SELECT id FROM users WHERE email = 'sarah.johnson@email.com'")->fetch()['id'];
    $homeowner3 = $db->query("SELECT id FROM users WHERE email = 'michael.brown@email.com'")->fetch()['id'];
    
    $contractor1 = $db->query("SELECT id FROM users WHERE email = 'rajesh.contractor@email.com'")->fetch()['id'];
    $contractor2 = $db->query("SELECT id FROM users WHERE email = 'amit.contractor@email.com'")->fetch()['id'];
    $contractor3 = $db->query("SELECT id FROM users WHERE email = 'priya.contractor@email.com'")->fetch()['id'];
    
    echo "<h3>Creating Sample Layout Requests...</h3>\n";
    
    // Sample layout requests (setting both user_id and homeowner_id for compatibility)
    $layoutRequests = [
        [$homeowner1, $homeowner1, '30x40 feet', '₹15-20 lakhs', 'Need a 3BHK house with modern kitchen and spacious living room. Should have good ventilation and natural light.', 'Modern', 'Bangalore, Karnataka', '6-8 months'],
        [$homeowner2, $homeowner2, '40x60 feet', '₹25-30 lakhs', 'Want a traditional style house with 4 bedrooms and garden space. Prefer vastu compliant design.', 'Traditional', 'Mumbai, Maharashtra', '8-10 months'],
        [$homeowner3, $homeowner3, '25x30 feet', '₹10-15 lakhs', 'Compact 2BHK house with efficient space utilization. Need parking for 1 car.', 'Compact', 'Delhi, India', '4-6 months']
    ];
    
    $layoutStmt = $db->prepare("INSERT INTO layout_requests (user_id, homeowner_id, plot_size, budget_range, requirements, preferred_style, location, timeline) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($layoutRequests as $request) {
        $layoutStmt->execute($request);
        echo "✓ Created layout request for homeowner ID {$request[1]}: {$request[2]} - {$request[3]}\n<br>";
    }
    
    // Get layout request IDs (using homeowner_id)
    $layout1 = $db->query("SELECT id FROM layout_requests WHERE homeowner_id = $homeowner1")->fetch()['id'];
    $layout2 = $db->query("SELECT id FROM layout_requests WHERE homeowner_id = $homeowner2")->fetch()['id'];
    $layout3 = $db->query("SELECT id FROM layout_requests WHERE homeowner_id = $homeowner3")->fetch()['id'];
    
    echo "<h3>Creating Contractor Layout Sends (Acknowledged Projects)...</h3>\n";
    
    // Create contractor layout sends (these represent projects sent to contractors)
    $layoutSends = [
        [$contractor1, $homeowner1, $layout1, null, 'Project for 3BHK modern house in Bangalore. Please provide detailed estimate.', date('Y-m-d H:i:s', strtotime('-5 days'))],
        [$contractor2, $homeowner2, $layout2, null, 'Traditional 4BHK house project in Mumbai. Vastu compliant design required.', date('Y-m-d H:i:s', strtotime('-3 days'))],
        [$contractor3, $homeowner3, $layout3, null, 'Compact 2BHK house project in Delhi. Space optimization is key.', date('Y-m-d H:i:s', strtotime('-7 days'))]
    ];
    
    $sendStmt = $db->prepare("INSERT INTO contractor_layout_sends (contractor_id, homeowner_id, layout_id, design_id, message, acknowledged_at) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($layoutSends as $send) {
        $sendStmt->execute($send);
        echo "✓ Created acknowledged project send for contractor ID {$send[0]} and homeowner ID {$send[1]}\n<br>";
    }
    
    // Get send IDs
    $send1 = $db->query("SELECT id FROM contractor_layout_sends WHERE contractor_id = $contractor1 AND homeowner_id = $homeowner1")->fetch()['id'];
    $send2 = $db->query("SELECT id FROM contractor_layout_sends WHERE contractor_id = $contractor2 AND homeowner_id = $homeowner2")->fetch()['id'];
    $send3 = $db->query("SELECT id FROM contractor_layout_sends WHERE contractor_id = $contractor3 AND homeowner_id = $homeowner3")->fetch()['id'];
    
    echo "<h3>Creating Contractor Estimates (Ready for Construction)...</h3>\n";
    
    // Create detailed estimates
    $estimates = [
        [
            $send1, $contractor1,
            'Cement: 200 bags, Steel: 2 tons, Bricks: 15000 pieces, Sand: 100 cubic feet, Aggregate: 150 cubic feet',
            'Foundation: ₹3,00,000, Structure: ₹5,00,000, Brickwork: ₹2,50,000, Roofing: ₹2,00,000, Electrical: ₹1,50,000, Plumbing: ₹1,00,000, Finishing: ₹2,00,000',
            1700000.00,
            '7 months',
            'Complete construction with all modern amenities. Includes electrical, plumbing, and basic finishing work.',
            'accepted'
        ],
        [
            $send2, $contractor2,
            'Cement: 300 bags, Steel: 3 tons, Bricks: 25000 pieces, Sand: 150 cubic feet, Aggregate: 200 cubic feet, Marble: 2000 sq ft',
            'Foundation: ₹4,50,000, Structure: ₹7,50,000, Brickwork: ₹4,00,000, Roofing: ₹3,00,000, Electrical: ₹2,50,000, Plumbing: ₹2,00,000, Finishing: ₹4,50,000',
            2800000.00,
            '9 months',
            'Traditional design with vastu compliance. Premium materials and finishes included.',
            'accepted'
        ],
        [
            $send3, $contractor3,
            'Cement: 120 bags, Steel: 1.5 tons, Bricks: 10000 pieces, Sand: 75 cubic feet, Aggregate: 100 cubic feet',
            'Foundation: ₹2,00,000, Structure: ₹3,50,000, Brickwork: ₹1,50,000, Roofing: ₹1,50,000, Electrical: ₹1,00,000, Plumbing: ₹75,000, Finishing: ₹1,75,000',
            1200000.00,
            '5 months',
            'Compact and efficient design with space optimization. All basic amenities included.',
            'accepted'
        ]
    ];
    
    $estimateStmt = $db->prepare("INSERT INTO contractor_send_estimates (send_id, contractor_id, materials, cost_breakdown, total_cost, timeline, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($estimates as $estimate) {
        $estimateStmt->execute($estimate);
        echo "✓ Created estimate for send ID {$estimate[0]}: ₹" . number_format($estimate[4], 2) . " - {$estimate[5]}\n<br>";
    }
    
    // Get estimate IDs (these are the actual project IDs)
    $project1 = $db->query("SELECT id FROM contractor_send_estimates WHERE send_id = $send1")->fetch()['id'];
    $project2 = $db->query("SELECT id FROM contractor_send_estimates WHERE send_id = $send2")->fetch()['id'];
    $project3 = $db->query("SELECT id FROM contractor_send_estimates WHERE send_id = $send3")->fetch()['id'];
    
    echo "<h3>Adding Project Locations for GPS Verification...</h3>\n";
    
    // Add project locations
    $locations = [
        [$project1, 12.9716, 77.5946, 'Bangalore, Karnataka - John Smith Project', 100],
        [$project2, 19.0760, 72.8777, 'Mumbai, Maharashtra - Sarah Johnson Project', 150],
        [$project3, 28.7041, 77.1025, 'Delhi, India - Michael Brown Project', 200]
    ];
    
    $locationStmt = $db->prepare("INSERT IGNORE INTO project_locations (project_id, latitude, longitude, address, radius_meters) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($locations as $location) {
        $locationStmt->execute($location);
        echo "✓ Added location for project {$location[0]}: {$location[3]}\n<br>";
    }
    
    echo "<h3>Sample Data Creation Complete!</h3>\n";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #c3e6cb;'>\n";
    echo "<h4 style='color: #155724; margin-top: 0;'>✅ Successfully Created:</h4>\n";
    echo "<ul style='color: #155724;'>\n";
    echo "<li><strong>3 Homeowners:</strong> John Smith, Sarah Johnson, Michael Brown</li>\n";
    echo "<li><strong>3 Contractors:</strong> Rajesh Kumar, Amit Sharma, Priya Patel</li>\n";
    echo "<li><strong>3 Layout Requests:</strong> Different plot sizes and budgets</li>\n";
    echo "<li><strong>3 Acknowledged Projects:</strong> Ready for progress updates</li>\n";
    echo "<li><strong>3 Detailed Estimates:</strong> With materials, costs, and timelines</li>\n";
    echo "<li><strong>3 Project Locations:</strong> GPS coordinates for verification</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<h4>🔑 Login Credentials:</h4>\n";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0; border: 1px solid #e9ecef;'>\n";
    echo "<strong>Contractors (use these to test progress updates):</strong><br>\n";
    echo "📧 rajesh.contractor@email.com | 🔑 contractor123<br>\n";
    echo "📧 amit.contractor@email.com | 🔑 contractor123<br>\n";
    echo "📧 priya.contractor@email.com | 🔑 contractor123<br><br>\n";
    
    echo "<strong>Homeowners (use these to view progress):</strong><br>\n";
    echo "📧 john.smith@email.com | 🔑 homeowner123<br>\n";
    echo "📧 sarah.johnson@email.com | 🔑 homeowner123<br>\n";
    echo "📧 michael.brown@email.com | 🔑 homeowner123<br>\n";
    echo "</div>\n";
    
    echo "<h4>🚀 Next Steps:</h4>\n";
    echo "<ol>\n";
    echo "<li>Login as any contractor using the credentials above</li>\n";
    echo "<li>Navigate to the 'Progress Updates' tab in the contractor dashboard</li>\n";
    echo "<li>You should now see acknowledged projects in the dropdown</li>\n";
    echo "<li>Select a project and start submitting progress updates</li>\n";
    echo "<li>Login as the corresponding homeowner to view the progress</li>\n";
    echo "</ol>\n";
    
    echo "<p style='color: #28a745; font-weight: bold;'>🎉 Sample acknowledged projects with estimates are now ready for progress tracking!</p>\n";
    
} catch (Exception $e) {
    echo "<h3>Error Creating Sample Data</h3>\n";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>