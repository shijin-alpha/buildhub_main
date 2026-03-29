<?php

/**
 * Create blockchain operation queue table
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creating blockchain operation queue table...\n";
    
    $sql = "
    CREATE TABLE IF NOT EXISTS blockchain_operation_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        method_name VARCHAR(100) NOT NULL,
        parameters JSON NOT NULL,
        status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
        attempts INT DEFAULT 0,
        max_attempts INT DEFAULT 3,
        error_message TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )";
    
    $db->exec($sql);
    echo "✅ blockchain_operation_queue table created successfully!\n";
    
    // Also ensure logs directory exists
    $logDir = dirname(__DIR__ . '/../../logs/blockchain.log');
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
        echo "✅ Created logs directory: $logDir\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error creating operation queue table: " . $e->getMessage() . "\n";
    exit(1);
}