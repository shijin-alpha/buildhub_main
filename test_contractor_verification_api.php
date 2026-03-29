<?php
// Test the new contractor verification API

echo "=== TESTING CONTRACTOR VERIFICATION API ===\n\n";

$contractor_id = 29; // The contractor for payment ID 15
$url = "http://localhost/buildhub/backend/api/contractor/get_pending_payment_verifications.php?contractor_id=" . $contractor_id;

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Content-Type: application/json',
        ]
    ]
]);

echo "API URL: $url\n\n";

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Failed to get response from API\n";
    exit;
}

$data = json_decode($response, true);

if (!$data) {
    echo "❌ Failed to decode JSON response\n";
    echo "Raw response: $response\n";
    exit;
}

echo "API Response Status: " . ($data['success'] ? 'Success' : 'Failed') . "\n";

if (isset($data['message'])) {
    echo "Message: {$data['message']}\n";
}

if (isset($data['data']['payments']) && !empty($data['data']['payments'])) {
    echo "\n✅ Found " . count($data['data']['payments']) . " pending payment verifications\n\n";
    
    foreach ($data['data']['payments'] as $index => $payment) {
        echo "=== Payment " . ($index + 1) . " ===\n";
        echo "ID: {$payment['id']}\n";
        echo "Project: {$payment['project_name']}\n";
        echo "Homeowner: {$payment['homeowner_name']}\n";
        echo "Stage: {$payment['stage_name']}\n";
        echo "Amount: {$payment['requested_amount_formatted']}\n";
        echo "Status: {$payment['status']}\n";
        echo "Verification Status: {$payment['verification_status']}\n";
        echo "Transaction Reference: {$payment['transaction_reference']}\n";
        echo "Payment Date: {$payment['payment_date_formatted']}\n";
        echo "Receipt Files: " . count($payment['receipt_files']) . "\n";
        echo "Days Since Upload: {$payment['days_since_upload']}\n";
        echo "\n";
    }
    
    if (isset($data['data']['summary'])) {
        $summary = $data['data']['summary'];
        echo "=== Summary ===\n";
        echo "Total Pending: {$summary['total_pending']}\n";
        echo "Total Amount: {$summary['total_amount_formatted']}\n";
        echo "Overdue Count: {$summary['overdue_count']}\n";
    }
    
} else {
    echo "No pending payment verifications found\n";
    if (isset($data['data'])) {
        echo "Data keys: " . implode(', ', array_keys($data['data'])) . "\n";
    }
}

echo "\n🎉 Contractor should now be able to see the uploaded receipt for verification!\n";
?>