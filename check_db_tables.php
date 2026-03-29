<?php
$db = new PDO('sqlite:buildhub.db');

echo "=== Database Tables ===\n";
$stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach($tables as $table) {
    echo "- $table\n";
}
