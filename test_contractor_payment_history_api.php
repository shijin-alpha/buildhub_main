<?php
// Test the contractor payment history API directly
echo "=== TESTING CONTRACTOR PAYMENT HISTORY API ===\n";

// Start session and simulate contractor login
session_start();
$_SESSION['user_id'] = 1; // Contractor ID 1
$_SESSION['user_role'] = 'contractor';
$_SESSION['logged_in'] = true;

echo "Session established for contractor ID: 1\n";

// Simulate the API call
$_GET['project_id'] = 2; // Project ID 2

echo "Testing API call for project ID: 2\n\n";

// Capture output
ob_start();

// Include the API file
include 'backend/api/contractor/get_payment_history.php';

// Get the output
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n";

// Parse the JSON response
$response = json_decode($output, true);

if ($response && $response['success']) {
    echo "\n=== PARSED RESPONSE ===\n";
    echo "Total payment requests: " . count($response['data']['payment_requests']) . "\n";
    
    echo "\nPayment requests found:\n";
    foreach ($response['data']['payment_requests'] as $request) {
        echo "  Payment ID: {$request['id']}\n";
        echo "    Stage: {$request['stage_name']}\n";
        echo "    Amount: ₹{$request['requested_amount']}\n";
        echo "    Status: {$request['status']}\n";
        echo "    Receipt Files: " . ($request['receipt_file_path'] ? 'YES (' . count($request['receipt_file_path']) . ' files)' : 'NO') . "\n";
        echo "    Verification Status: " . ($request['verification_status'] ?: 'NULL') . "\n";
        echo "    Transaction Reference: " . ($request['transaction_reference'] ?: 'NULL') . "\n";
        echo "    Payment Date: " . ($request['payment_date'] ?: 'NULL') . "\n";
        echo "    Payment Method: " . ($request['payment_method'] ?: 'NULL') . "\n";
        echo "  ---\n";
    }
    
    if (count($response['data']['payment_requests']) > 0) {
        echo "\n✅ Payment ID 16 should be visible in contractor dashboard!\n";
    } else {
        echo "\n❌ No payment requests found for contractor\n";
    }
} else {
    echo "\nAPI call failed or returned no data\n";
    if ($response) {
        echo "Error: " . ($response['message'] ?? 'Unknown error') . "\n";
    }
}
?>