<?php
/**
 * Apply AI Model Versions Schema
 * Creates the ai_model_versions table for tracking ML model versions
 */

require_once 'backend/config/database.php';

echo "==========================================================\n";
echo "AI MODEL VERSIONS SCHEMA MIGRATION\n";
echo "==========================================================\n\n";

try {
    // Read SQL file
    $sql_file = 'backend/database/ai_model_versions_schema.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "Executing " . count($statements) . " SQL statements...\n\n";
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) continue;
        
        echo "Statement " . ($index + 1) . ": ";
        
        if ($conn->query($statement)) {
            echo "✓ Success\n";
        } else {
            echo "✗ Failed: " . $conn->error . "\n";
        }
    }
    
    echo "\n==========================================================\n";
    echo "SCHEMA MIGRATION COMPLETE\n";
    echo "==========================================================\n\n";
    
    // Verify table creation
    $result = $conn->query("SHOW TABLES LIKE 'ai_model_versions'");
    
    if ($result->num_rows > 0) {
        echo "✓ Table 'ai_model_versions' created successfully\n\n";
        
        // Show table structure
        echo "Table Structure:\n";
        $structure = $conn->query("DESCRIBE ai_model_versions");
        
        while ($row = $structure->fetch_assoc()) {
            echo "  - {$row['Field']} ({$row['Type']})\n";
        }
        
        echo "\n";
        
        // Check initial data
        $count_result = $conn->query("SELECT COUNT(*) as count FROM ai_model_versions");
        $count_row = $count_result->fetch_assoc();
        
        echo "Initial records: {$count_row['count']}\n";
        
        if ($count_row['count'] > 0) {
            echo "\nInitial Model Versions:\n";
            $versions = $conn->query("SELECT * FROM ai_model_versions ORDER BY id");
            
            while ($version = $versions->fetch_assoc()) {
                echo "  - {$version['model_type']} {$version['model_version']}: ";
                echo "Accuracy = {$version['accuracy']}\n";
            }
        }
        
    } else {
        echo "⚠ Warning: Table 'ai_model_versions' not found after migration\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();

echo "\n==========================================================\n";
echo "You can now use the ML model versioning system!\n";
echo "==========================================================\n";
?>
