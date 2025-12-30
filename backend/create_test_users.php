<?php
// Create test users for contractor and architect roles

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 Creating test users...\n\n";
    
    // Check if contractor user exists
    $checkContractor = $db->prepare("SELECT id FROM users WHERE email = 'contractor@gmail.com'");
    $checkContractor->execute();
    
    if ($checkContractor->rowCount() == 0) {
        // Create contractor user
        $contractorQuery = "INSERT INTO users (first_name, last_name, email, password, role, status, created_at) 
                           VALUES ('John', 'Builder', 'contractor@gmail.com', ?, 'contractor', 'approved', NOW())";
        $contractorStmt = $db->prepare($contractorQuery);
        $contractorPassword = password_hash('contractor123', PASSWORD_DEFAULT);
        $contractorStmt->execute([$contractorPassword]);
        echo "✅ Created contractor user: contractor@gmail.com (password: contractor123)\n";
    } else {
        echo "ℹ️  Contractor user already exists: contractor@gmail.com\n";
    }
    
    // Check if architect user exists
    $checkArchitect = $db->prepare("SELECT id FROM users WHERE email = 'architect@gmail.com'");
    $checkArchitect->execute();
    
    if ($checkArchitect->rowCount() == 0) {
        // Create architect user
        $architectQuery = "INSERT INTO users (first_name, last_name, email, password, role, status, created_at) 
                          VALUES ('Sarah', 'Designer', 'architect@gmail.com', ?, 'architect', 'approved', NOW())";
        $architectStmt = $db->prepare($architectQuery);
        $architectPassword = password_hash('architect123', PASSWORD_DEFAULT);
        $architectStmt->execute([$architectPassword]);
        echo "✅ Created architect user: architect@gmail.com (password: architect123)\n";
    } else {
        echo "ℹ️  Architect user already exists: architect@gmail.com\n";
    }
    
    // Check if homeowner user exists
    $checkHomeowner = $db->prepare("SELECT id FROM users WHERE email = 'homeowner@gmail.com'");
    $checkHomeowner->execute();
    
    if ($checkHomeowner->rowCount() == 0) {
        // Create homeowner user
        $homeownerQuery = "INSERT INTO users (first_name, last_name, email, password, role, status, created_at) 
                          VALUES ('Mike', 'Johnson', 'homeowner@gmail.com', ?, 'homeowner', 'approved', NOW())";
        $homeownerStmt = $db->prepare($homeownerQuery);
        $homeownerPassword = password_hash('homeowner123', PASSWORD_DEFAULT);
        $homeownerStmt->execute([$homeownerPassword]);
        echo "✅ Created homeowner user: homeowner@gmail.com (password: homeowner123)\n";
    } else {
        echo "ℹ️  Homeowner user already exists: homeowner@gmail.com\n";
    }
    
    echo "\n🎉 Test users setup completed!\n\n";
    
    echo "📋 Login Credentials:\n";
    echo "   👷‍♂️ Contractor: contractor@gmail.com / contractor123\n";
    echo "   🏛️ Architect: architect@gmail.com / architect123\n";
    echo "   🏠 Homeowner: homeowner@gmail.com / homeowner123\n";
    echo "   🔐 Admin: shijinthomas369@gmail.com / admin123\n\n";
    
    echo "🚀 You can now test all dashboard types!\n";
    
} catch (Exception $e) {
    echo "❌ Error creating test users: " . $e->getMessage() . "\n";
}
?>