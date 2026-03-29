<?php
/**
 * Add unlock_price column to house_plans table
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "Adding unlock_price column to house_plans table...\n";

    // Add unlock_price column
    $alterQuery = "ALTER TABLE house_plans ADD COLUMN IF NOT EXISTS unlock_price DECIMAL(10,2) DEFAULT 8000.00";
    $db->exec($alterQuery);
    
    echo "✅ Successfully added unlock_price column\n";

    // Verify the column was added
    $checkQuery = "SHOW COLUMNS FROM house_plans LIKE 'unlock_price'";
    $result = $db->query($checkQuery);
    
    if ($result && $result->rowCount() > 0) {
        echo "✅ Column verification successful\n";
        
        // Show the column details
        $column = $result->fetch(PDO::FETCH_ASSOC);
        echo "Column details:\n";
        echo "- Field: " . $column['Field'] . "\n";
        echo "- Type: " . $column['Type'] . "\n";
        echo "- Default: " . $column['Default'] . "\n";
    } else {
        echo "❌ Column verification failed\n";
    }

    // Update existing records to have default unlock price
    $updateQuery = "UPDATE house_plans SET unlock_price = 8000.00 WHERE unlock_price IS NULL";
    $updateResult = $db->exec($updateQuery);
    echo "✅ Updated $updateResult existing records with default unlock price\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>