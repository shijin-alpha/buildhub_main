<?php
require_once 'backend/config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "Database Tables:\n";
echo "================\n\n";

$result = $conn->query('SHOW TABLES');
$tables = $result->fetchAll(PDO::FETCH_COLUMN);

if (empty($tables)) {
    echo "No tables found in database!\n";
} else {
    foreach($tables as $table) {
        echo "- " . $table . "\n";
    }
}

echo "\n\nTotal tables: " . count($tables) . "\n";
?>
