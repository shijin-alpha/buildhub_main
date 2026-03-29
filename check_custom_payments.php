<?php
$pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
echo "Custom payment requests:\n";
$stmt = $pdo->query('SELECT * FROM custom_payment_requests ORDER BY created_at DESC LIMIT 5');
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($requests as $req) {
    echo "ID: {$req['id']}, Title: {$req['request_title']}, Amount: ₹{$req['requested_amount']}, Status: {$req['status']}, Homeowner: {$req['homeowner_id']}\n";
}
?>