<?php
/**
 * Check Available Tables in Database
 */

require_once 'backend/config/database.php';

echo "=== Available Tables ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all tables
    $stmt = $db->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($tables) . " tables:\n\n";
    
    foreach ($tables as $table) {
        echo "📋 $table\n";
        
        // Get table info
        $info_stmt = $db->prepare("SELECT COUNT(*) as count FROM `$table`");
        $info_stmt->execute();
        $count = $info_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "   Records: $count\n";
        
        // Check if it's related to projects or construction
        if (stripos($table, 'project') !== false || 
            stripos($table, 'construction') !== false || 
            stripos($table, 'estimate') !== false ||
            stripos($table, 'progress') !== false) {
            
            echo "   🏗️ Construction-related table\n";
            
            // Show structure for construction-related tables
            $struct_stmt = $db->prepare("DESCRIBE `$table`");
            $struct_stmt->execute();
            $columns = $struct_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "   Columns: ";
            $col_names = array_column($columns, 'Field');
            echo implode(', ', $col_names) . "\n";
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}