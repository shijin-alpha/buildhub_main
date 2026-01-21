<?php
require_once 'backend/config/database.php';

try {
    $db = new PDO("sqlite:buildhub.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all tables
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Database tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // Check if concept_previews table exists
    if (in_array('concept_previews', $tables)) {
        echo "\nConcept previews table exists!\n";
        
        // Get table structure
        $stmt = $db->query("PRAGMA table_info(concept_previews)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Columns:\n";
        foreach ($columns as $column) {
            echo "- {$column['name']} ({$column['type']})\n";
        }
    } else {
        echo "\nConcept previews table does NOT exist!\n";
        echo "Need to create the table first.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>