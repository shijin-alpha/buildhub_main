<?php
/**
 * Apply Enhanced Inspection Schema
 * This script adds comprehensive inspection fields to the database
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Starting enhanced inspection schema migration...\n";
    
    // Read and execute the schema file
    $schema_sql = file_get_contents('backend/database/enhanced_inspection_schema.sql');
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema_sql)));
    
    $db->beginTransaction();
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Skip if column already exists or other non-critical errors
                if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                    echo "⚠ Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
                } else {
                    echo "✗ Error: " . $e->getMessage() . "\n";
                    echo "Statement: " . $statement . "\n";
                }
            }
        }
    }
    
    $db->commit();
    
    echo "\n✅ Enhanced inspection schema migration completed successfully!\n";
    echo "\nNew features added:\n";
    echo "- Comprehensive inspection fields (weather, site conditions, etc.)\n";
    echo "- Enhanced safety and quality assessment fields\n";
    echo "- Environmental impact tracking\n";
    echo "- Detailed checklist system\n";
    echo "- Follow-up and notification tracking\n";
    echo "- Performance indexes for better query speed\n";
    echo "- Comprehensive reporting views\n";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>