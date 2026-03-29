<?php
$db = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
$stmt = $db->query('DESCRIBE project_stage_payment_requests');
echo "Columns in project_stage_payment_requests table:\n\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
