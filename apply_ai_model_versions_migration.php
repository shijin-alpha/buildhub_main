<?php
/**
 * Apply AI Model Versions Schema Migration
 * Creates the ai_model_versions table for tracking ML model versions
 */

require_once 'backend/config/database.php';

echo "=== AI Model Versions Schema Migration ===\n\n";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Read SQL file
    $sqlFile = 'backend/database/ai_model_versions_schema.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    echo "Executing schema migration...\n";
    
    // Execute SQL
    $pdo->exec($sql);
    
    echo "✓ ai_model_versions table created successfully\n";
    
    // Verify table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'ai_model_versions'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table verification passed\n";
        
        // Show table structure
        echo "\nTable structure:\n";
        $stmt = $pdo->query("DESCRIBE ai_model_versions");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
        
        // Check if initial data was inserted
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM ai_model_versions");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\n✓ Initial model versions: {$result['count']}\n";
        
    } else {
        throw new Exception("Table verification failed");
    }
    
    echo "\n=== Migration completed successfully ===\n";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
