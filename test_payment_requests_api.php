<?php
// Test the payment requests API directly
echo "=== TESTING PAYMENT REQUESTS API ===\n";

// Start output buffering to capture the API response
ob_start();

// Include the API file
include 'backend/api/homeowner/get_all_payment_requests.php';

// Get the output
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n";

// Parse the JSON response
$response = json_decode($output, true);

if ($response && $response['success']) {
    echo "\n=== PARSED RESPONSE ===\n";
    echo "Total requests: " . count($response['data']['requests']) . "\n";
    echo "Homeowner ID used: " . ($response['data']['requests'][0]['homeowner_id'] ?? 'N/A') . "\n";
    
    echo "\nPayment IDs found:\n";
    foreach ($response['data']['requests'] as $request) {
        echo "ID: {$request['id']}, Amount: {$request['requested_amount']}, Status: {$request['status']}\n";
    }
} else {
    echo "\nAPI call failed or returned no data\n";
    if ($response) {
        echo "Error: " . ($response['message'] ?? 'Unknown error') . "\n";
    }
}
?>