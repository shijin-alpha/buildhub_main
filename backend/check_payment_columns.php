<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();

echo "=== Stage Payment Requests Table Columns ===\n\n";

$stmt = $db->query('SHOW COLUMNS FROM stage_payment_requests');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}

echo "\n=== Sample Payment Record ===\n\n";

$sampleStmt = $db->query('SELECT * FROM stage_payment_requests WHERE id = 13 LIMIT 1');
$sample = $sampleStmt->fetch(PDO::FETCH_ASSOC);

if ($sample) {
    foreach ($sample as $key => $value) {
        if (strlen($value) > 100) {
            $value = substr($value, 0, 100) . '...';
        }
        echo "$key: " . ($value ?: 'NULL') . "\n";
    }
} else {
    echo "No payment found with ID 13\n";
}
