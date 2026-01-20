<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();

echo "construction_progress_updates columns:\n";
$stmt = $db->query('DESCRIBE construction_progress_updates');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  " . $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
