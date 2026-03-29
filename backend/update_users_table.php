<?php
// Update users table structure for dashboard functionality

require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 Updating users table structure...\n\n";
    
    // Check current table structure
    $result = $db->query("DESCRIBE users");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Current columns: " . implode(', ', $columns) . "\n\n";
    
    // Add missing columns if they don't exist
    $columnsToAdd = [
        'role' => "ALTER TABLE users ADD COLUMN role ENUM('homeowner', 'contractor', 'architect', 'admin') DEFAULT 'homeowner' AFTER email",
        'status' => "ALTER TABLE users ADD COLUMN status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending' AFTER role",
        'phone' => "ALTER TABLE users ADD COLUMN phone VARCHAR(20) NULL AFTER last_name",
        'address' => "ALTER TABLE users ADD COLUMN address TEXT NULL AFTER phone",
        'city' => "ALTER TABLE users ADD COLUMN city VARCHAR(100) NULL AFTER address",
        'state' => "ALTER TABLE users ADD COLUMN state VARCHAR(50) NULL AFTER city",
        'zip_code' => "ALTER TABLE users ADD COLUMN zip_code VARCHAR(10) NULL AFTER state",
        'profile_image' => "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER zip_code",
        'bio' => "ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER profile_image",
        'specialization' => "ALTER TABLE users ADD COLUMN specialization VARCHAR(255) NULL AFTER bio",
        'experience_years' => "ALTER TABLE users ADD COLUMN experience_years INT NULL AFTER specialization",
        'license_number' => "ALTER TABLE users ADD COLUMN license_number VARCHAR(100) NULL AFTER experience_years",
        'company_name' => "ALTER TABLE users ADD COLUMN company_name VARCHAR(255) NULL AFTER license_number",
        'website' => "ALTER TABLE users ADD COLUMN website VARCHAR(255) NULL AFTER company_name",
        'updated_at' => "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
        'deleted_at' => "ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL AFTER updated_at"
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

    // Ensure status ENUM contains 'suspended'
    try {
        $statusEnumCheck = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
        if ($statusEnumCheck && $statusEnumCheck->rowCount() > 0) {
            $colInfo = $statusEnumCheck->fetch(PDO::FETCH_ASSOC);
            $type = $colInfo['Type'] ?? '';
            if (strpos($type, "'suspended'") === false) {
                $db->exec("ALTER TABLE users MODIFY COLUMN status ENUM('pending','approved','rejected','suspended') DEFAULT 'pending'");
                echo "✅ Updated status ENUM to include 'suspended'\n";
            } else {
                echo "ℹ️  status ENUM already includes 'suspended'\n";
            }
        }
    } catch (Exception $e) {
        echo "⚠️  Could not modify status ENUM: " . $e->getMessage() . "\n";
    }
    
    // Update existing admin user if exists
    $checkAdmin = $db->prepare("SELECT id FROM users WHERE email = 'shijinthomas369@gmail.com'");
    $checkAdmin->execute();
    
    if ($checkAdmin->rowCount() > 0) {
        $updateAdmin = $db->prepare("UPDATE users SET role = 'admin', status = 'approved' WHERE email = 'shijinthomas369@gmail.com'");
        $updateAdmin->execute();
        echo "✅ Updated admin user role and status\n";
    }
    
    echo "\n🎉 Users table structure updated successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error updating users table: " . $e->getMessage() . "\n";
}
?>