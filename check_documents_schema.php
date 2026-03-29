<?php
$db = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
echo "=== contractor_stage_documents schema ===\n";
$stmt = $db->query('DESCRIBE contractor_stage_documents');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
