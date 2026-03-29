<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Applying international payment schema updates...\n";
    
    // Read and execute SQL file
    $sql = file_get_contents('database/add_international_payment_support.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        try {
            $db->exec($statement);
            $successCount++;
            echo "✓ Executed statement successfully\n";
        } catch (PDOException $e) {
            $errorCount++;
            echo "✗ Error executing statement: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "\nSchema update completed:\n";
    echo "- Successful statements: $successCount\n";
    echo "- Failed statements: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "\n✅ All international payment schema updates applied successfully!\n";
    } else {
        echo "\n⚠️ Some statements failed. Please check the errors above.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
}
?>