<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Searching for Project 38 or Homeowner 28 ===\n\n";
    
    // Check all possible tables
    $tables_to_check = ['projects', 'real_projects', 'estimates', 'project_estimates'];
    
    foreach ($tables_to_check as $table) {
        try {
            echo "Checking table: {$table}\n";
            
            // Try to find by ID 38
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = 38");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo "✓ Found project with ID 38 in {$table}:\n";
                print_r($result);
                echo "\n";
            }
            
            // Try to find by homeowner_id 28
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE homeowner_id = 28 LIMIT 5");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($results) {
                echo "✓ Found " . count($results) . " projects for homeowner 28 in {$table}:\n";
                foreach ($results as $row) {
                    echo "  - ID: {$row['id']}, Name: " . ($row['project_name'] ?? $row['client_name'] ?? 'N/A') . "\n";
                }
                echo "\n";
            }
            
        } catch (PDOException $e) {
            echo "  Table {$table} doesn't exist or error: " . $e->getMessage() . "\n\n";
        }
    }
    
    // List all tables
    echo "=== All Available Tables ===\n";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- {$table}\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
