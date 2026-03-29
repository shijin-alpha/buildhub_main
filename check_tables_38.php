<?php
$db = new PDO('sqlite:buildhub.db');
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
echo "Available tables:\n";
foreach($tables as $t) {
    echo "- $t\n";
}

// Check for project 38
echo "\nSearching for project 38...\n";
foreach(['projects', 'real_projects', 'estimates'] as $table) {
    if (in_array($table, $tables)) {
        $stmt = $db->query("SELECT * FROM $table WHERE id = 38 OR homeowner_id = 28 LIMIT 5");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($results) {
            echo "\nFound in table '$table':\n";
            print_r($results);
        }
    }
}
