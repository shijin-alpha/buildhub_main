<?php
// This script creates the materials table - run once to set up the database

try {
    require_once __DIR__ . '/../../config/db.php';
    
    $sql = "CREATE TABLE IF NOT EXISTS materials (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        unit VARCHAR(50) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_category (category),
        INDEX idx_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql);
    
    echo "Materials table created successfully!";
    
} catch (PDOException $e) {
    echo "Error creating materials table: " . $e->getMessage();
}
?>