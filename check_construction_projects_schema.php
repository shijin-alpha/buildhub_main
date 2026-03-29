<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Construction Projects Table Schema ===\n\n";
    
    $stmt = $db->query("DESCRIBE construction_projects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo "{$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n=== Sample Projects ===\n\n";
    
    $stmt = $db->query("SELECT * FROM construction_projects ORDER BY id DESC LIMIT 3");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        print_r($project);
        echo "\n---\n\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
