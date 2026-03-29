<?php
require_once 'backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Database Tables ===\n";
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach($tables as $table) {
    echo "- $table\n";
}

echo "\n=== Looking for project-related tables ===\n";
foreach($tables as $table) {
    if (stripos($table, 'project') !== false || stripos($table, 'progress') !== false || stripos($table, 'daily') !== false) {
        echo "\nTable: $table\n";
        $stmt = $db->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    }
}
