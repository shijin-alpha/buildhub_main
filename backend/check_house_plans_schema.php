<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "House plans table schema:\n";
    $stmt = $db->query("DESCRIBE house_plans");
    while ($row = $stmt->fetch()) {
        echo "- {$row['Field']} ({$row['Type']}) {$row['Null']} {$row['Key']} {$row['Default']}\n";
    }
    
    echo "\nChecking for house plans:\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM house_plans");
    $count = $stmt->fetch()['count'];
    echo "Total house plans: $count\n";
    
    if ($count > 0) {
        $stmt = $db->query("SELECT id, plan_name, architect_id, status FROM house_plans LIMIT 5");
        while ($row = $stmt->fetch()) {
            echo "ID: {$row['id']}, Name: {$row['plan_name']}, Architect: {$row['architect_id']}, Status: {$row['status']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>