<?php
echo "=== TESTING APIs WITH CURL ===\n";

// Function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null, $cookies = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/buildhub' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['response' => $response, 'http_code' => $httpCode];
}

echo "1. Testing Homeowner Session Bridge...\n";
$result = makeRequest('/backend/api/homeowner/session_bridge.php');
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: {$result['response']}\n\n";

echo "2. Testing Homeowner Progress Updates...\n";
$result = makeRequest('/backend/api/homeowner/get_progress_updates.php');
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: {$result['response']}\n\n";

echo "3. Testing Contractor Login...\n";
$result = makeRequest('/backend/api/contractor/login_contractor_session.php', 'POST', ['contractor_id' => 29]);
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: {$result['response']}\n\n";

echo "4. Testing Contractor Progress Updates...\n";
$result = makeRequest('/backend/api/contractor/get_progress_updates.php');
echo "HTTP Code: {$result['http_code']}\n";
echo "Response: {$result['response']}\n\n";

// Clean up cookie file
if (file_exists('cookie.txt')) {
    unlink('cookie.txt');
}
?>