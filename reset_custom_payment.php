<?php
$pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
$stmt = $pdo->prepare('UPDATE custom_payment_requests SET status = ?, response_date = NULL, homeowner_notes = NULL, approved_amount = NULL WHERE id = ?');
$result = $stmt->execute(['pending', 1]);
echo 'Reset custom payment request to pending status: ' . ($result ? 'Success' : 'Failed') . "\n";

// Verify the reset
$stmt = $pdo->prepare("SELECT * FROM custom_payment_requests WHERE id = ?");
$stmt->execute([1]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Current status: {$request['status']}\n";
?>