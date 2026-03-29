<?php
$db = new PDO('sqlite:buildhub.db');
$stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Available tables:\n";
foreach($tables as $table) {
    echo "- $table\n";
}
