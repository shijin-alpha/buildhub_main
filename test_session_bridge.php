<?php
echo "Testing Session Bridge API:\n";

$response = file_get_contents('http://localhost/buildhub/backend/api/homeowner/session_bridge.php');
$data = json_decode($response, true);

echo "Response: $response\n";

if ($data) {
    echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "\n";
    echo "Message: " . $data['message'] . "\n";
    
    if ($data['success'] && isset($data['user'])) {
        echo "User ID: " . $data['user']['id'] . "\n";
        echo "User Role: " . $data['user']['role'] . "\n";
        echo "Session ID: " . $data['session_id'] . "\n";
    }
}
?>