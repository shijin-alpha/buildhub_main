<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Checking technical_details_payments table...\n";
    
    $result = $db->query("SHOW TABLES LIKE 'technical_details_payments'");
    if ($result->rowCount() > 0) {
        echo "✅ Table exists\n";
        
        $desc = $db->query("DESCRIBE technical_details_payments");
        echo "\nTable structure:\n";
        while ($row = $desc->fetch()) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }
        
        // Test the query
        echo "\nTesting query...\n";
        $testSql = "SELECT COUNT(*) as count FROM technical_details_payments";
        $testResult = $db->query($testSql);
        $count = $testResult->fetch()['count'];
        echo "Records in table: $count\n";
        
    } else {
        echo "❌ Table does not exist\n";
        echo "Creating table...\n";
        
        // Create the table
        $createSql = file_get_contents(__DIR__ . '/database/create_technical_details_payments.sql');
        $statements = array_filter(array_map('trim', explode(';', $createSql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $db->exec($statement);
                echo "✅ Executed: " . substr($statement, 0, 50) . "...\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>