<?php
require_once __DIR__ . '/backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "=== daily_progress_updates Schema ===\n\n";
$stmt = $db->query('DESCRIBE daily_progress_updates');
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "{$col['Field']} ({$col['Type']})\n";
}

echo "\n=== Sample Data ===\n\n";
$stmt = $db->query('SELECT * FROM daily_progress_updates LIMIT 2');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
