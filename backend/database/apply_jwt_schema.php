<?php
/**
 * Apply JWT Schema Updates
 * Run this script to add JWT-related tables to the database
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Applying JWT schema updates...\n";
    
    // Read and execute the JWT tables SQL
    $sql = file_get_contents(__DIR__ . '/jwt_tables.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        
        if (!$conn->query($statement)) {
            throw new Exception("Error executing statement: " . $conn->error);
        }
    }
    
    echo "JWT schema updates applied successfully!\n";
    
    // Verify tables were created
    $tables = ['jwt_tokens', 'jwt_blacklist', 'api_rate_limits', 'auth_audit_log'];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' created successfully\n";
        } else {
            echo "✗ Table '$table' was not created\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>