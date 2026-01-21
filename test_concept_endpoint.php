<?php
// Test the concept generation endpoint directly
$url = 'http://localhost/buildhub/backend/api/architect/generate_concept_preview.php';

$data = [
    'layout_request_id' => 62,  // Use an existing request ID
    'concept_description' => 'A modern two-story villa with clean lines, large windows, flat roof, white exterior walls, and a contemporary entrance with glass doors.'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

curl_close($ch);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "HTTP Code: $httpCode\n";
echo "Headers:\n$headers\n";
echo "Body length: " . strlen($body) . "\n";
echo "Body:\n$body\n";

// Check for JSON validity
echo "\nJSON Analysis:\n";
echo "==============\n";
for ($i = 0; $i < strlen($body); $i++) {
    $char = $body[$i];
    if ($i == 78) {
        echo "Position 78: '" . $char . "' (ASCII: " . ord($char) . ")\n";
    }
    if (!ctype_print($char) && $char !== "\n" && $char !== "\r" && $char !== "\t") {
        echo "Non-printable character at position $i: ASCII " . ord($char) . "\n";
    }
}

// Try to find where JSON ends
$decoded = json_decode($body);
if ($decoded === null) {
    echo "JSON decode failed. Error: " . json_last_error_msg() . "\n";
    
    // Try to find valid JSON portion
    for ($len = strlen($body); $len > 0; $len--) {
        $substr = substr($body, 0, $len);
        if (json_decode($substr) !== null) {
            echo "Valid JSON found up to position $len\n";
            echo "Valid JSON: $substr\n";
            echo "Extra content: " . substr($body, $len) . "\n";
            break;
        }
    }
} else {
    echo "JSON is valid!\n";
    print_r($decoded);
}
?>