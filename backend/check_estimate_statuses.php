<?php
$pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
$stmt = $pdo->query('SELECT DISTINCT status FROM contractor_send_estimates ORDER BY status');
$statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Available statuses in contractor_send_estimates:\n";
foreach ($statuses as $status) {
    echo "  - {$status}\n";
}

echo "\nRecords by status:\n";
$stmt = $pdo->query('SELECT status, COUNT(*) as count FROM contractor_send_estimates GROUP BY status');
$counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($counts as $row) {
    echo "  - {$row['status']}: {$row['count']} records\n";
}

echo "\nShijin Thomas estimates:\n";
$stmt = $pdo->query("
    SELECT 
        cse.id,
        cse.status,
        cse.total_cost,
        CONCAT(u.first_name, ' ', u.last_name) as contractor_name
    FROM contractor_send_estimates cse
    LEFT JOIN users u ON u.id = cse.contractor_id
    WHERE u.first_name = 'Shijin' AND u.last_name = 'Thomas'
");
$estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($estimates as $est) {
    echo "  - ID: {$est['id']}, Status: {$est['status']}, Cost: ₹" . number_format($est['total_cost'], 2) . "\n";
}
