<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Checking house plans with layout images:\n";
    $stmt = $db->query("SELECT id, plan_name, layout_image, technical_details FROM house_plans");
    
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}\n";
        echo "Name: {$row['plan_name']}\n";
        echo "Layout Image: " . ($row['layout_image'] ?: 'None') . "\n";
        echo "Technical Details: " . (strlen($row['technical_details'] ?: '') > 50 ? substr($row['technical_details'], 0, 50) . '...' : ($row['technical_details'] ?: 'None')) . "\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>