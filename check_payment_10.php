<?php
require_once __DIR__ . '/backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check payment 10
echo "<h2>Looking for Payment ID 10 for Homeowner 28:</h2>";
$stmt = $db->prepare('
    SELECT id, homeowner_id, contractor_id, status, verification_status, requested_amount, stage_name 
    FROM stage_payment_requests 
    WHERE id = 10
');
$stmt->execute();
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if ($payment) {
    echo "<pre>FOUND:\n" . json_encode($payment, JSON_PRETTY_PRINT) . "</pre>";
    echo "Belongs to homeowner: " . $payment['homeowner_id'] . "<br>";
    echo "Matches session homeowner (28)? " . ($payment['homeowner_id'] == 28 ? "YES ✅" : "NO ❌") . "<br>";
} else {
    echo "<p>❌ Payment NOT FOUND</p>";
}

// Check all payments for homeowner 28
echo "<h2>All Payments for Homeowner 28:</h2>";
$stmt2 = $db->prepare('
    SELECT id, homeowner_id, contractor_id, status, verification_status, requested_amount, stage_name 
    FROM stage_payment_requests 
    WHERE homeowner_id = 28
');
$stmt2->execute();
$payments = $stmt2->fetchAll(PDO::FETCH_ASSOC);

if (!empty($payments)) {
    echo "<pre>" . json_encode($payments, JSON_PRETTY_PRINT) . "</pre>";
    echo "IDs available for upload: " . implode(", ", array_column($payments, 'id')) . "<br>";
} else {
    echo "<p>❌ No payments found for homeowner 28</p>";
}

// Check if payment 10 exists for ANY homeowner
echo "<h2>Payment 10 Details (All Homeowners):</h2>";
$stmt3 = $db->prepare('
    SELECT id, homeowner_id, contractor_id, status, verification_status 
    FROM stage_payment_requests 
    WHERE id = 10
');
$stmt3->execute();
$p10 = $stmt3->fetch(PDO::FETCH_ASSOC);
if ($p10) {
    echo "<pre>" . json_encode($p10, JSON_PRETTY_PRINT) . "</pre>";
    echo "Payment 10 belongs to homeowner: " . $p10['homeowner_id'] . "<br>";
} else {
    echo "Payment 10 does not exist in database<br>";
}

// List all available payment IDs
echo "<h2>All Available Payment IDs:</h2>";
$stmt4 = $db->query('SELECT DISTINCT id, homeowner_id FROM stage_payment_requests ORDER BY id LIMIT 20');
$allPayments = $stmt4->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>" . json_encode($allPayments, JSON_PRETTY_PRINT) . "</pre>";

?>
