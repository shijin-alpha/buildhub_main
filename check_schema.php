<?php
$db = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');

echo "=== construction_projects schema ===\n";
$stmt = $db->query('DESCRIBE construction_projects');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== construction_phases schema ===\n";
$stmt = $db->query('DESCRIBE construction_phases');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== Sample construction_projects data ===\n";
$stmt = $db->query('SELECT * FROM construction_projects LIMIT 1');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    print_r(array_keys($row));
}
