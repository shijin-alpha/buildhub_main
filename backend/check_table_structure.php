<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();

echo "contractor_send_estimates columns:\n";
$stmt = $db->query('DESCRIBE contractor_send_estimates');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  " . $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
