<?php
require 'backend/config/database.php';
$db = (new Database())->getConnection();
echo "=== stage_payment_requests Schema ===\n\n";
$stmt = $db->query('DESCRIBE stage_payment_requests');
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo $c['Field'] . ' (' . $c['Type'] . ')' . PHP_EOL;
}
