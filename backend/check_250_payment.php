<?php
require_once 'config/database.php';

$db = (new Database())->getConnection();

echo "=== Checking for ₹250 Payment ===\n\n";

// Check stage_payment_requests
echo "1. Checking stage_payment_requests table:\n";
$stmt1 = $db->query("
    SELECT id, stage_name, requested_amount, approved_amount, status 
    FROM stage_payment_requests 
    WHERE requested_amount = 250 OR approved_amount = 250
    ORDER BY id DESC
");
$results1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

if (count($results1) > 0) {
    foreach ($results1 as $row) {
        echo "   Found in stage_payment_requests:\n";
        echo "   - ID: {$row['id']}\n";
        echo "   - Stage: {$row['stage_name']}\n";
        echo "   - Requested: ₹{$row['requested_amount']}\n";
        echo "   - Approved: ₹" . ($row['approved_amount'] ?: 'NULL') . "\n";
        echo "   - Status: {$row['status']}\n\n";
    }
} else {
    echo "   No ₹250 payments found\n\n";
}

// Check alternative_payments
echo "2. Checking alternative_payments table:\n";
try {
    $stmt2 = $db->query("
        SELECT id, payment_type, reference_id, amount, payment_method, payment_status, verification_status
        FROM alternative_payments 
        WHERE amount = 250
        ORDER BY id DESC
    ");
    $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results2) > 0) {
        foreach ($results2 as $row) {
            echo "   Found in alternative_payments:\n";
            echo "   - ID: {$row['id']}\n";
            echo "   - Type: {$row['payment_type']}\n";
            echo "   - Reference ID: {$row['reference_id']}\n";
            echo "   - Amount: ₹{$row['amount']}\n";
            echo "   - Method: {$row['payment_method']}\n";
            echo "   - Payment Status: {$row['payment_status']}\n";
            echo "   - Verification Status: {$row['verification_status']}\n\n";
        }
    } else {
        echo "   No ₹250 payments found\n\n";
    }
} catch (Exception $e) {
    echo "   Table doesn't exist or error: " . $e->getMessage() . "\n\n";
}

// Check all payment requests with small amounts
echo "3. Checking all payments under ₹1000:\n";
$stmt3 = $db->query("
    SELECT id, stage_name, requested_amount, approved_amount, status, homeowner_id, contractor_id
    FROM stage_payment_requests 
    WHERE requested_amount < 1000 OR approved_amount < 1000
    ORDER BY requested_amount ASC
    LIMIT 10
");
$results3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);

if (count($results3) > 0) {
    foreach ($results3 as $row) {
        echo "   - ID: {$row['id']}, Stage: {$row['stage_name']}, ";
        echo "Requested: ₹{$row['requested_amount']}, ";
        echo "Approved: ₹" . ($row['approved_amount'] ?: 'NULL') . ", ";
        echo "Status: {$row['status']}\n";
    }
} else {
    echo "   No small payments found\n";
}

echo "\n=== Check Complete ===\n";
