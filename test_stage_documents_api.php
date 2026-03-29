<?php
// Test the stage documents API to see if it's working
$contractor_id = 32; // Using a known contractor ID

$url = "http://localhost/buildhub/backend/api/contractor/get_stage_documents.php?contractor_id=" . $contractor_id;

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Content-Type: application/json',
        ]
    ]
]);

echo "Testing Stage Documents API: $url\n\n";

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "Failed to get response from API\n";
    exit;
}

echo "Raw response:\n";
echo $response . "\n\n";

$data = json_decode($response, true);

if (!$data) {
    echo "Failed to decode JSON response\n";
    exit;
}

echo "Decoded response:\n";
echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";

if (isset($data['message'])) {
    echo "Message: " . $data['message'] . "\n";
}

if (isset($data['error'])) {
    echo "Error: " . $data['error'] . "\n";
}

if (isset($data['data'])) {
    echo "Data keys: " . implode(', ', array_keys($data['data'])) . "\n";
}
?>