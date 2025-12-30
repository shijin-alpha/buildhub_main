<?php
// Create test users with documents for testing admin dashboard

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 Creating test users with documents...\n\n";
    
    // First, let's check if the users table has the required columns
    $result = $db->query("DESCRIBE users");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Available columns: " . implode(', ', $columns) . "\n\n";
    
    // Add missing columns if they don't exist
    $columnsToAdd = [
        'role' => "ALTER TABLE users ADD COLUMN role ENUM('homeowner', 'contractor', 'architect', 'admin') DEFAULT 'homeowner' AFTER email",
        'is_verified' => "ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT FALSE AFTER role",
        'license' => "ALTER TABLE users ADD COLUMN license VARCHAR(255) NULL AFTER is_verified",
        'portfolio' => "ALTER TABLE users ADD COLUMN portfolio VARCHAR(255) NULL AFTER license"
    ];
    
    foreach ($columnsToAdd as $column => $sql) {
        if (!in_array($column, $columns)) {
            try {
                $db->exec($sql);
                echo "✅ Added column: $column\n";
            } catch (Exception $e) {
                echo "⚠️  Could not add column $column: " . $e->getMessage() . "\n";
            }
        } else {
            echo "ℹ️  Column already exists: $column\n";
        }
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
        echo "✅ Created uploads directory\n";
    }
    
    // Create test documents
    $testLicenseContent = "This is a test contractor license document.\n\nLicense Number: TEST-123456\nIssued Date: " . date('Y-m-d') . "\nExpiry Date: " . date('Y-m-d', strtotime('+1 year')) . "\n\nThis is a sample document for testing purposes.";
    $testPortfolioContent = "This is a test architect portfolio document.\n\nPortfolio Contents:\n- Project 1: Modern House Design\n- Project 2: Commercial Building\n- Project 3: Residential Complex\n\nCreated on: " . date('Y-m-d H:i:s') . "\n\nThis is a sample document for testing purposes.";
    
    $licenseFile = $uploadsDir . '/test_contractor_license.txt';
    $portfolioFile = $uploadsDir . '/test_architect_portfolio.txt';
    
    file_put_contents($licenseFile, $testLicenseContent);
    file_put_contents($portfolioFile, $testPortfolioContent);
    
    echo "✅ Created test documents\n";
    
    // Create test contractor user
    $checkContractor = $db->prepare("SELECT id FROM users WHERE email = 'testcontractor@gmail.com'");
    $checkContractor->execute();
    
    if ($checkContractor->rowCount() == 0) {
        $contractorQuery = "INSERT INTO users (first_name, last_name, email, password, role, is_verified, license, created_at) 
                           VALUES ('John', 'Builder', 'testcontractor@gmail.com', ?, 'contractor', 0, 'uploads/test_contractor_license.txt', NOW())";
        $contractorStmt = $db->prepare($contractorQuery);
        $contractorPassword = password_hash('contractor123', PASSWORD_DEFAULT);
        $contractorStmt->execute([$contractorPassword]);
        echo "✅ Created test contractor user: testcontractor@gmail.com (password: contractor123)\n";
    } else {
        // Update existing contractor with document
        $updateContractor = $db->prepare("UPDATE users SET role = 'contractor', is_verified = 0, license = 'uploads/test_contractor_license.txt' WHERE email = 'testcontractor@gmail.com'");
        $updateContractor->execute();
        echo "✅ Updated existing contractor user with test document\n";
    }
    
    // Create test architect user
    $checkArchitect = $db->prepare("SELECT id FROM users WHERE email = 'testarchitect@gmail.com'");
    $checkArchitect->execute();
    
    if ($checkArchitect->rowCount() == 0) {
        $architectQuery = "INSERT INTO users (first_name, last_name, email, password, role, is_verified, portfolio, created_at) 
                          VALUES ('Sarah', 'Designer', 'testarchitect@gmail.com', ?, 'architect', 0, 'uploads/test_architect_portfolio.txt', NOW())";
        $architectStmt = $db->prepare($architectQuery);
        $architectPassword = password_hash('architect123', PASSWORD_DEFAULT);
        $architectStmt->execute([$architectPassword]);
        echo "✅ Created test architect user: testarchitect@gmail.com (password: architect123)\n";
    } else {
        // Update existing architect with document
        $updateArchitect = $db->prepare("UPDATE users SET role = 'architect', is_verified = 0, portfolio = 'uploads/test_architect_portfolio.txt' WHERE email = 'testarchitect@gmail.com'");
        $updateArchitect->execute();
        echo "✅ Updated existing architect user with test document\n";
    }
    
    echo "\n🎉 Test users with documents created successfully!\n\n";
    
    echo "📋 Test Login Credentials:\n";
    echo "   👷‍♂️ Contractor: testcontractor@gmail.com / contractor123\n";
    echo "   🏛️ Architect: testarchitect@gmail.com / architect123\n";
    echo "   🔐 Admin: shijinthomas369@gmail.com / admin123\n\n";
    
    echo "📄 Test Documents Created:\n";
    echo "   📋 License: uploads/test_contractor_license.txt\n";
    echo "   📁 Portfolio: uploads/test_architect_portfolio.txt\n\n";
    
    echo "🚀 You can now test document viewing and downloading in the admin dashboard!\n";
    
} catch (Exception $e) {
    echo "❌ Error creating test users: " . $e->getMessage() . "\n";
}
?>