<?php
/**
 * Add technical_details column to house_plans table
 * This migration adds support for comprehensive technical specifications
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    echo "Starting migration: Adding technical_details column to house_plans table...\n";

    // Check if column already exists
    $checkStmt = $db->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'house_plans' 
        AND COLUMN_NAME = 'technical_details'
    ");
    $checkStmt->execute();
    $columnExists = $checkStmt->fetch();

    if ($columnExists) {
        echo "✅ Column 'technical_details' already exists in house_plans table.\n";
    } else {
        // Add the technical_details column
        $alterStmt = $db->prepare("
            ALTER TABLE house_plans 
            ADD COLUMN technical_details JSON NULL 
            COMMENT 'Stores comprehensive technical specifications including construction details, materials, MEP systems, etc.'
            AFTER plan_data
        ");
        
        if ($alterStmt->execute()) {
            echo "✅ Successfully added 'technical_details' column to house_plans table.\n";
        } else {
            throw new Exception('Failed to add technical_details column: ' . implode(', ', $alterStmt->errorInfo()));
        }
    }

    // Verify the table structure
    echo "\nCurrent house_plans table structure:\n";
    $describeStmt = $db->query("DESCRIBE house_plans");
    $columns = $describeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        $nullable = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $column['Default'] ? "DEFAULT '{$column['Default']}'" : '';
        echo "- {$column['Field']}: {$column['Type']} {$nullable} {$default}\n";
    }

    echo "\n✅ Migration completed successfully!\n";
    echo "The house_plans table now supports technical_details storage.\n";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>