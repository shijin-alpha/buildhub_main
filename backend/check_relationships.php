<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();

echo "Checking project 37 relationships:\n\n";

// Check contractor_send_estimates
$stmt = $db->query("SELECT * FROM contractor_send_estimates WHERE id = 37");
$estimate = $stmt->fetch(PDO::FETCH_ASSOC);
echo "contractor_send_estimates (id=37):\n";
print_r($estimate);

// Check custom_requests
if ($estimate && $estimate['send_id']) {
    echo "\ncustom_requests (id=" . $estimate['send_id'] . "):\n";
    $stmt = $db->query("SELECT * FROM custom_requests WHERE id = " . $estimate['send_id']);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($request);
}
?>
