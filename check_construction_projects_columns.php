<?php
require_once 'backend/config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo "Construction Projects Table Structure:\n";
echo "======================================\n\n";

$result = $conn->query('DESCRIBE construction_projects');
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
