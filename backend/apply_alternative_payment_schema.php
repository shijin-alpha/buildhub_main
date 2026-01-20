<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Applying alternative payment schema...\n";
    
    // Read and execute SQL file
    $sql = file_get_contents('database/create_alternative_payment_tables.sql');
    
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
    
    echo "\nAlternative payment schema setup completed:\n";
    echo "- Successful statements: $successCount\n";
    echo "- Failed statements: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "\n✅ Alternative payment system ready!\n";
        echo "\nAvailable payment methods:\n";
        echo "- 🏦 Bank Transfer (NEFT/RTGS) - Up to ₹1 crore\n";
        echo "- 📱 UPI Payment - Up to ₹10 lakhs, instant\n";
        echo "- 💵 Cash Payment - Up to ₹2 lakhs\n";
        echo "- 📝 Cheque Payment - Up to ₹5 crores\n";
        echo "\nBypass Razorpay limits completely!\n";
    } else {
        echo "\n⚠️ Some statements failed. Please check the errors above.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
}
?>