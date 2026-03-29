<?php
$pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');

echo "Checking table collations:\n\n";

// Check stage_payment_requests table
$stmt = $pdo->query("SELECT COLUMN_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'buildhub' AND TABLE_NAME = 'stage_payment_requests' AND COLUMN_NAME IN ('stage_name', 'status')");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "stage_payment_requests table:\n";
foreach ($columns as $col) {
    echo "  {$col['COLUMN_NAME']}: {$col['COLLATION_NAME']}\n";
}

// Check custom_payment_requests table
$stmt = $pdo->query("SELECT COLUMN_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'buildhub' AND TABLE_NAME = 'custom_payment_requests' AND COLUMN_NAME IN ('request_title', 'status')");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\ncustom_payment_requests table:\n";
foreach ($columns as $col) {
    echo "  {$col['COLUMN_NAME']}: {$col['COLLATION_NAME']}\n";
}

// Check construction_stage_payments table
$stmt = $pdo->query("SELECT COLUMN_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'buildhub' AND TABLE_NAME = 'construction_stage_payments' AND COLUMN_NAME = 'stage_name'");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nconstruction_stage_payments table:\n";
foreach ($columns as $col) {
    echo "  {$col['COLUMN_NAME']}: {$col['COLLATION_NAME']}\n";
}
?>