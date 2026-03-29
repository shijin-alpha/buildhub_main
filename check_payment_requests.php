<?php
require_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check payments for homeowner 28
echo "<h2>Payments for Homeowner 28:</h2>";
$stmt = $db->prepare('
    SELECT id, homeowner_id, contractor_id, status, verification_status, requested_amount, stage_name 
    FROM stage_payment_requests 
    WHERE homeowner_id = 28 
    LIMIT 15
');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>" . json_encode($results, JSON_PRETTY_PRINT) . "</pre>";

// Check all homeowners with payment requests
echo "<h2>Sample of All Payment Requests:</h2>";
$stmt2 = $db->prepare('
    SELECT DISTINCT homeowner_id FROM stage_payment_requests LIMIT 5
');
$stmt2->execute();
$homeowners = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "Homeowner IDs with payments: " . json_encode($homeowners) . "<br>";

// Get total payment counts
$stmt3 = $db->query('SELECT COUNT(*) as total FROM stage_payment_requests');
$total = $stmt3->fetch(PDO::FETCH_ASSOC);
echo "Total payment requests in database: " . $total['total'] . "<br>";

?>
