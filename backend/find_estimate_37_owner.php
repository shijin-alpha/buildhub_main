<?php
$pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
$stmt = $pdo->query("
    SELECT 
        cse.id, 
        cse.contractor_id, 
        cse.status, 
        cse.total_cost, 
        CONCAT(u.first_name, ' ', u.last_name) as contractor_name
    FROM contractor_send_estimates cse
    LEFT JOIN users u ON u.id = cse.contractor_id
    WHERE cse.id = 37
");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Estimate ID 37:\n";
echo "  Contractor ID: {$row['contractor_id']}\n";
echo "  Contractor Name: {$row['contractor_name']}\n";
echo "  Status: {$row['status']}\n";
echo "  Total Cost: ₹" . number_format($row['total_cost'], 2) . "\n";
