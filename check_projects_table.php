<?php
require_once 'backend/config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "Projects Table Structure:\n";
echo "========================\n\n";

$result = $conn->query('DESCRIBE projects');
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . " - Key: " . $row['Key'] . "\n";
}

echo "\n\nChecking primary key:\n";
$pk = $conn->query("SHOW KEYS FROM projects WHERE Key_name = 'PRIMARY'")->fetch(PDO::FETCH_ASSOC);
echo "Primary Key Column: " . ($pk['Column_name'] ?? 'NOT FOUND') . "\n";
?>
