<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Adding layout_image column to house_plans table...\n";
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM house_plans LIKE 'layout_image'");
    if ($stmt->rowCount() > 0) {
        echo "✓ layout_image column already exists\n";
    } else {
        // Add the column
        $db->exec("ALTER TABLE house_plans ADD COLUMN layout_image TEXT NULL AFTER technical_details");
        echo "✓ Added layout_image column\n";
    }
    
    echo "\nUpdated house plans table schema:\n";
    $stmt = $db->query("DESCRIBE house_plans");
    while ($row = $stmt->fetch()) {
        echo "- {$row['Field']} ({$row['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>