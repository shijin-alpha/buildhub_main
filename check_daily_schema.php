<?php
$db = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
echo "=== daily_progress_updates schema ===\n";
$stmt = $db->query('DESCRIBE daily_progress_updates');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
