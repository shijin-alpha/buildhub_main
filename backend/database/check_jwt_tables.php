<?php
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Checking existing tables...\n";
    
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Existing tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    echo "\nCreating JWT tables manually...\n";
    
    // Create jwt_tokens table
    $sql = "CREATE TABLE IF NOT EXISTS jwt_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        jti VARCHAR(255) NOT NULL UNIQUE,
        token_type ENUM('access', 'refresh') NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_jti (jti),
        INDEX idx_expires_at (expires_at)
    )";
    
    if ($conn->query($sql)) {
        echo "✓ jwt_tokens table created\n";
    } else {
        echo "✗ Failed to create jwt_tokens table\n";
    }
    
    // Create jwt_blacklist table
    $sql = "CREATE TABLE IF NOT EXISTS jwt_blacklist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        jti VARCHAR(255) NOT NULL UNIQUE,
        blacklisted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_jti (jti),
        INDEX idx_blacklisted_at (blacklisted_at)
    )";
    
    if ($conn->query($sql)) {
        echo "✓ jwt_blacklist table created\n";
    } else {
        echo "✗ Failed to create jwt_blacklist table\n";
    }
    
    // Create api_rate_limits table
    $sql = "CREATE TABLE IF NOT EXISTS api_rate_limits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        endpoint VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    )";
    
    if ($conn->query($sql)) {
        echo "✓ api_rate_limits table created\n";
    } else {
        echo "✗ Failed to create api_rate_limits table\n";
    }
    
    // Create auth_audit_log table
    $sql = "CREATE TABLE IF NOT EXISTS auth_audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        endpoint VARCHAR(255) NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        success BOOLEAN NOT NULL DEFAULT TRUE,
        error_message TEXT NULL,
        additional_data JSON NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at),
        INDEX idx_success (success)
    )";
    
    if ($conn->query($sql)) {
        echo "✓ auth_audit_log table created\n";
    } else {
        echo "✗ Failed to create auth_audit_log table\n";
    }
    
    echo "\nFinal table check:\n";
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $jwtTables = ['jwt_tokens', 'jwt_blacklist', 'api_rate_limits', 'auth_audit_log'];
    foreach ($jwtTables as $table) {
        if (in_array($table, $tables)) {
            echo "✓ $table exists\n";
        } else {
            echo "✗ $table missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>