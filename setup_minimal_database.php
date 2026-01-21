<?php
require_once 'backend/config/database.php';

try {
    $db = new PDO("sqlite:buildhub.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Setting up minimal database for concept generation testing...\n\n";
    
    // Create users table
    echo "Creating users table...\n";
    $db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT,
        last_name TEXT,
        email TEXT UNIQUE,
        phone TEXT,
        role TEXT CHECK (role IN ('homeowner', 'architect', 'contractor', 'admin')),
        status TEXT DEFAULT 'approved',
        city TEXT,
        specialization TEXT,
        experience_years INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create layout_requests table
    echo "Creating layout_requests table...\n";
    $db->exec("
    CREATE TABLE IF NOT EXISTS layout_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        homeowner_id INTEGER,
        plot_size TEXT,
        budget_range TEXT,
        location TEXT,
        timeline TEXT,
        num_floors TEXT,
        preferred_style TEXT,
        orientation TEXT,
        site_considerations TEXT,
        material_preferences TEXT,
        budget_allocation TEXT,
        site_images TEXT,
        reference_images TEXT,
        room_images TEXT,
        floor_rooms TEXT,
        requirements TEXT,
        status TEXT DEFAULT 'pending',
        layout_type TEXT DEFAULT 'custom',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert sample architect
    echo "Creating sample architect...\n";
    $db->exec("
    INSERT OR IGNORE INTO users (id, first_name, last_name, email, role, status) 
    VALUES (1, 'Test', 'Architect', 'architect@test.com', 'architect', 'approved')
    ");
    
    // Insert sample homeowner
    echo "Creating sample homeowner...\n";
    $db->exec("
    INSERT OR IGNORE INTO users (id, first_name, last_name, email, role, status) 
    VALUES (2, 'Test', 'Homeowner', 'homeowner@test.com', 'homeowner', 'approved')
    ");
    
    // Insert sample layout request
    echo "Creating sample layout request...\n";
    $db->exec("
    INSERT OR IGNORE INTO layout_requests (id, user_id, homeowner_id, plot_size, budget_range, location, requirements, status) 
    VALUES (1, 2, 2, '2000 sq ft', '10-15 lakhs', 'Test Location', 'Modern house with 3 bedrooms', 'approved')
    ");
    
    // Create uploads directories
    echo "Creating upload directories...\n";
    $dirs = [
        'uploads',
        'uploads/conceptual_images',
        'uploads/concept_previews',
        'uploads/room_improvements'
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "Created directory: $dir\n";
        } else {
            echo "Directory exists: $dir\n";
        }
    }
    
    echo "\n✓ Minimal database setup complete!\n";
    echo "✓ Sample architect ID: 1 (architect@test.com)\n";
    echo "✓ Sample homeowner ID: 2 (homeowner@test.com)\n";
    echo "✓ Sample layout request ID: 1\n";
    echo "✓ Upload directories created\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>