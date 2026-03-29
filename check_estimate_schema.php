<?php
$db = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
echo "=== contractor_send_estimates schema ===\n";
$stmt = $db->query('DESCRIBE contractor_send_estimates');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
