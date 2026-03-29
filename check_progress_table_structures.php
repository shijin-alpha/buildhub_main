<?php
/**
 * Check actual structure of progress-related tables
 */

echo "🔍 Checking Progress Table Structures...\n\n";

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // List of progress-related tables to check
    $progress_tables = [
        'daily_progress_updates',
        'construction_progress_updates', 
        'monthly_progress_reports',
        'progress_reports',
        'weekly_progress_summaries'
    ];
    
    foreach ($progress_tables as $table) {
        echo "📋 Table: {$table}\n";
        echo str_repeat("=", 50) . "\n";
        
        try {
            $stmt = $db->query("DESCRIBE {$table}");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($columns)) {
                echo "❌ Table not found or empty\n\n";
                continue;
            }
            
            echo "Columns:\n";
            foreach ($columns as $column) {
                echo "  - {$column['Field']} ({$column['Type']}) " . 
                     ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . 
                     ($column['Default'] ? " DEFAULT {$column['Default']}" : '') . "\n";
            }
            
            // Try to get sample data
            echo "\nSample Data (first 3 rows):\n";
            try {
                $sampleStmt = $db->query("SELECT * FROM {$table} LIMIT 3");
                $sampleData = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($sampleData)) {
                    echo "  No data found\n";
                } else {
                    foreach ($sampleData as $row) {
                        echo "  Row: " . json_encode($row) . "\n";
                    }
                }
            } catch (Exception $e) {
                echo "  Error getting sample data: " . $e->getMessage() . "\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    // Also check if there are any other tables with 'progress' in the name
    echo "🔍 Searching for other progress-related tables:\n";
    echo str_repeat("=", 50) . "\n";
    
    $stmt = $db->query("SHOW TABLES LIKE '%progress%'");
    $progressTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($progressTables)) {
        echo "No tables found with 'progress' in the name\n";
    } else {
        echo "Found tables:\n";
        foreach ($progressTables as $table) {
            echo "  - {$table}\n";
        }
    }
    
    echo "\n";
    
    // Check for any tables with 'daily' in the name
    echo "🔍 Searching for daily-related tables:\n";
    echo str_repeat("=", 50) . "\n";
    
    $stmt = $db->query("SHOW TABLES LIKE '%daily%'");
    $dailyTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($dailyTables)) {
        echo "No tables found with 'daily' in the name\n";
    } else {
        echo "Found tables:\n";
        foreach ($dailyTables as $table) {
            echo "  - {$table}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Progress Table Structure Analysis Complete!\n";
?>