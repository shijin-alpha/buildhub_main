<?php
require_once 'backend/config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query('DESCRIBE inspection_reports');
echo "inspection_reports columns:\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
