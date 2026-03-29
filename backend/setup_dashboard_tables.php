<?php
// Setup dashboard tables

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 Setting up dashboard tables...\n\n";
    
    // Layout requests table
    $sql1 = "CREATE TABLE IF NOT EXISTS layout_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        homeowner_id INT NOT NULL,
        plot_size VARCHAR(100) NOT NULL,
        budget_range VARCHAR(100) NOT NULL,
        requirements TEXT NOT NULL,
        preferred_style VARCHAR(100),
        status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (homeowner_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $db->exec($sql1);
    echo "✅ Created layout_requests table\n";
    
    // Architect layouts table
    $sql2 = "CREATE TABLE IF NOT EXISTS architect_layouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        architect_id INT NOT NULL,
        layout_request_id INT NOT NULL,
        design_type ENUM('custom', 'template') NOT NULL,
        description TEXT NOT NULL,
        layout_file VARCHAR(255),
        template_id INT,
        notes TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (architect_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (layout_request_id) REFERENCES layout_requests(id) ON DELETE CASCADE
    )";
    
    $db->exec($sql2);
    echo "✅ Created architect_layouts table\n";
    
    // Contractor proposals table
    $sql3 = "CREATE TABLE IF NOT EXISTS contractor_proposals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT NOT NULL,
        layout_request_id INT NOT NULL,
        materials TEXT NOT NULL,
        cost_breakdown TEXT NOT NULL,
        total_cost DECIMAL(12,2) NOT NULL,
        timeline VARCHAR(100) NOT NULL,
        notes TEXT,
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (layout_request_id) REFERENCES layout_requests(id) ON DELETE CASCADE
    )";
    
    $db->exec($sql3);
    echo "✅ Created contractor_proposals table\n";
    
    // Layout templates table
    $sql4 = "CREATE TABLE IF NOT EXISTS layout_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        style VARCHAR(100),
        rooms INT,
        preview_image VARCHAR(255),
        template_file VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql4);
    echo "✅ Created layout_templates table\n";
    
    // Insert sample templates
    $checkTemplates = $db->query("SELECT COUNT(*) FROM layout_templates")->fetchColumn();
    if ($checkTemplates == 0) {
        $templates = [
            ['Modern Villa Template', 'Contemporary villa design with open spaces and large windows', 'Modern', 4],
            ['Traditional House Template', 'Classic house design with traditional architectural elements', 'Traditional', 3],
            ['Compact Home Template', 'Space-efficient design perfect for small plots', 'Compact', 2],
            ['Luxury Mansion Template', 'Grand mansion design with premium features', 'Luxury', 6],
            ['Eco-Friendly Home Template', 'Sustainable design with green building features', 'Eco-Friendly', 3]
        ];
        
        $stmt = $db->prepare("INSERT INTO layout_templates (name, description, style, rooms) VALUES (?, ?, ?, ?)");
        foreach ($templates as $template) {
            $stmt->execute($template);
        }
        echo "✅ Inserted sample layout templates\n";
    }
    
    // Insert sample layout requests (only if homeowner exists)
    $checkHomeowner = $db->query("SELECT id FROM users WHERE role = 'homeowner' LIMIT 1")->fetchColumn();
    if ($checkHomeowner) {
        $checkRequests = $db->query("SELECT COUNT(*) FROM layout_requests")->fetchColumn();
        if ($checkRequests == 0) {
            $requests = [
                [$checkHomeowner, '30x40 feet', '₹15-20 lakhs', 'Need a 3BHK house with modern kitchen and spacious living room', 'Modern'],
                [$checkHomeowner, '40x60 feet', '₹25-30 lakhs', 'Want a traditional style house with 4 bedrooms and garden space', 'Traditional'],
                [$checkHomeowner, '25x30 feet', '₹10-15 lakhs', 'Compact 2BHK house with efficient space utilization', 'Compact']
            ];
            
            $stmt = $db->prepare("INSERT INTO layout_requests (homeowner_id, plot_size, budget_range, requirements, preferred_style) VALUES (?, ?, ?, ?, ?)");
            foreach ($requests as $request) {
                $stmt->execute($request);
            }
            echo "✅ Inserted sample layout requests\n";
        }
    }
    
    // Create indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_layout_requests_homeowner ON layout_requests(homeowner_id)",
        "CREATE INDEX IF NOT EXISTS idx_layout_requests_status ON layout_requests(status)",
        "CREATE INDEX IF NOT EXISTS idx_architect_layouts_architect ON architect_layouts(architect_id)",
        "CREATE INDEX IF NOT EXISTS idx_architect_layouts_request ON architect_layouts(layout_request_id)",
        "CREATE INDEX IF NOT EXISTS idx_architect_layouts_status ON architect_layouts(status)",
        "CREATE INDEX IF NOT EXISTS idx_contractor_proposals_contractor ON contractor_proposals(contractor_id)",
        "CREATE INDEX IF NOT EXISTS idx_contractor_proposals_request ON contractor_proposals(layout_request_id)",
        "CREATE INDEX IF NOT EXISTS idx_contractor_proposals_status ON contractor_proposals(status)"
    ];
    
    foreach ($indexes as $index) {
        $db->exec($index);
    }
    echo "✅ Created database indexes\n";
    
    // Create upload directories
    $uploadDirs = [
        __DIR__ . '/uploads/layouts',
        __DIR__ . '/uploads/templates'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✅ Created directory: $dir\n";
        }
    }
    
    echo "\n🎉 Dashboard tables setup completed successfully!\n\n";
    
    echo "📋 Summary:\n";
    echo "   ✅ layout_requests - Store homeowner project requests\n";
    echo "   ✅ architect_layouts - Store architect design submissions\n";
    echo "   ✅ contractor_proposals - Store contractor material proposals\n";
    echo "   ✅ layout_templates - Library of design templates\n";
    echo "   ✅ Sample data inserted for testing\n";
    echo "   ✅ Upload directories created\n";
    echo "   ✅ Database indexes created for performance\n\n";
    
    echo "🚀 Ready to use contractor and architect dashboards!\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up tables: " . $e->getMessage() . "\n";
}
?>