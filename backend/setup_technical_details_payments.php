<?php
/**
 * Setup technical details payments table
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Setting up technical details payments table...\n";

    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/database/create_technical_details_payments.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
            echo "✅ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }

    echo "✅ Technical details payments table setup complete!\n";

    // Verify table exists
    $checkQuery = "SHOW TABLES LIKE 'technical_details_payments'";
    $result = $db->query($checkQuery);
    
    if ($result && $result->rowCount() > 0) {
        echo "✅ Table verification successful\n";
        
        // Show table structure
        $descQuery = "DESCRIBE technical_details_payments";
        $descResult = $db->query($descQuery);
        
        echo "\nTable structure:\n";
        while ($row = $descResult->fetch(PDO::FETCH_ASSOC)) {
            echo "- {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
        }
    } else {
        echo "❌ Table verification failed\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>