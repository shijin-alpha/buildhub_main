<?php
$db = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
echo "=== inspection_reports schema ===\n";
$stmt = $db->query('DESCRIBE inspection_reports');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
