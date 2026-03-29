<?php
// Test the homeowner payment requests API

echo "Testing homeowner payment requests API...\n\n";

// Test the API endpoint that the frontend calls
$homeowner_id = 32;
$url = "http://localhost/buildhub/backend/api/homeowner/get_payment_requests.php?homeowner_id=" . $homeowner_id;

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
    echo "Failed to get response from API\n";
    exit;
}

$data = json_decode($response, true);

if (!$data) {
    echo "Failed to decode JSON response\n";
    echo "Raw response: $response\n";
    exit;
}

echo "API Response Status: " . ($data['success'] ? 'Success' : 'Failed') . "\n";

if (isset($data['message'])) {
    echo "Message: {$data['message']}\n";
}

if (isset($data['data']['payment_requests']) && !empty($data['data']['payment_requests'])) {
    echo "Number of payment requests: " . count($data['data']['payment_requests']) . "\n\n";
    
    foreach ($data['data']['payment_requests'] as $index => $request) {
        echo "=== Payment Request " . ($index + 1) . " ===\n";
        echo "ID: " . $request['id'] . "\n";
        echo "Homeowner ID: " . $request['homeowner_id'] . "\n";
        echo "Contractor ID: " . ($request['contractor_id'] ?? 'NULL') . "\n";
        echo "Amount: ₹" . number_format($request['requested_amount'], 2) . "\n";
        echo "Status: " . $request['status'] . "\n";
        echo "Stage: " . ($request['stage_name'] ?? 'N/A') . "\n";
        echo "Created: " . ($request['created_at'] ?? 'N/A') . "\n";
        echo "\n";
    }
} else {
    echo "No payment requests found\n";
    if (isset($data['data'])) {
        echo "Data keys: " . implode(', ', array_keys($data['data'])) . "\n";
    }
}

echo "\nFull response (first 1000 chars):\n";
echo substr($response, 0, 1000) . "\n";
?>