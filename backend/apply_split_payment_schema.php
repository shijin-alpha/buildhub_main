<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Applying split payment schema...\n";
    
    // Read and execute SQL file
    $sql = file_get_contents('database/create_split_payment_tables.sql');
    
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
    
    echo "\nSplit payment schema setup completed:\n";
    echo "- Successful statements: $successCount\n";
    echo "- Failed statements: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "\n✅ Split payment system ready!\n";
        echo "\nFeatures enabled:\n";
        echo "- Automatic payment splitting for amounts > ₹5,00,000\n";
        echo "- Sequential payment processing\n";
        echo "- Progress tracking and notifications\n";
        echo "- Support for up to 5 splits per payment\n";
    } else {
        echo "\n⚠️ Some statements failed. Please check the errors above.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
}
?>