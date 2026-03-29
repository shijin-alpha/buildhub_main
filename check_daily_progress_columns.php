<?php
$db = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
$stmt = $db->query('DESCRIBE daily_progress_updates');
echo "Columns in daily_progress_updates table:\n\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
