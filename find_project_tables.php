<?php
/**
 * Find Project Tables
 * Discover the correct table structure for projects
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Finding Project Tables\n\n";
    
    // 1. List all tables
    echo "1. All tables in database:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "   - $table\n";
    }
    echo "\n";
    
    // 2. Look for tables that might contain project information
    echo "2. Tables that might contain project info:\n";
    $projectTables = array_filter($tables, function($table) {
        return strpos(strtolower($table), 'project') !== false || 
               strpos(strtolower($table), 'send') !== false ||
               strpos(strtolower($table), 'layout') !== false ||
               strpos(strtolower($table), 'homeowner') !== false;
    });
    
    foreach ($projectTables as $table) {
        echo "   - $table\n";
        
        // Show structure
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            if (strpos(strtolower($column['Field']), 'name') !== false || 
                strpos(strtolower($column['Field']), 'id') !== false ||
                strpos(strtolower($column['Field']), 'homeowner') !== false) {
                echo "     * {$column['Field']} ({$column['Type']})\n";
            }
        }
        echo "\n";
    }
    
    // 3. Check contractor_send_estimates data with accepted status
    echo "3. Looking for accepted estimates for contractor 29:\n";
    $stmt = $pdo->query("
        SELECT id, send_id, contractor_id, status, total_cost, created_at
        FROM contractor_send_estimates 
        WHERE contractor_id = 29 
        ORDER BY created_at DESC
    ");
    $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($estimates)) {
        echo "   No estimates found for contractor 29\n";
    } else {
        foreach ($estimates as $est) {
            echo "   - ID: {$est['id']}, Send ID: {$est['send_id']}, Status: {$est['status']}, Cost: ₹" . number_format($est['total_cost']) . "\n";
        }
    }
    echo "\n";
    
    // 4. Check if there are any accepted estimates
    echo "4. All accepted estimates in system:\n";
    $stmt = $pdo->query("
        SELECT id, send_id, contractor_id, status, total_cost
        FROM contractor_send_estimates 
        WHERE status = 'accepted'
        LIMIT 10
    ");
    $accepted = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accepted)) {
        echo "   No accepted estimates found in the system\n";
        
        // Check what statuses exist
        echo "\n   Available statuses:\n";
        $stmt = $pdo->query("SELECT DISTINCT status FROM contractor_send_estimates");
        $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($statuses as $status) {
            echo "     - $status\n";
        }
    } else {
        foreach ($accepted as $est) {
            echo "   - ID: {$est['id']}, Send ID: {$est['send_id']}, Contractor: {$est['contractor_id']}, Cost: ₹" . number_format($est['total_cost']) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>