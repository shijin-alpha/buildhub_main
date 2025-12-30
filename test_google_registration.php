<?php
// Test Google registration with actual data
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTING GOOGLE REGISTRATION ===\n\n";

// Test data
$testData = [
    'firstName' => 'Google',
    'lastName' => 'TestUser',
    'email' => 'googletest@example.com',
    'role' => 'homeowner',
    'password' => 'randomGooglePass123!A1'
];

// Create FormData equivalent
$boundary = '----WebKitFormBoundary' . uniqid();
$data = '';

foreach ($testData as $name => $value) {
    $data .= "--$boundary\r\n";
    $data .= "Content-Disposition: form-data; name=\"$name\"\r\n\r\n";
    $data .= "$value\r\n";
}
$data .= "--$boundary--\r\n";

echo "1. Testing Google registration endpoint...\n";
echo "Data being sent:\n";
print_r($testData);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/buildhub/backend/api/google_register.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: multipart/form-data; boundary=$boundary",
    "Content-Length: " . strlen($data)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nHTTP Code: $httpCode\n";
echo "Response: $response\n";

// Check if user was created
try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute(['googletest@example.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "\n✓ User found in database:\n";
        echo "ID: {$user['id']}\n";
        echo "Name: {$user['first_name']} {$user['last_name']}\n";
        echo "Email: {$user['email']}\n";
        echo "Role: {$user['role']}\n";
        echo "Verified: {$user['is_verified']}\n";
        echo "Created: {$user['created_at']}\n";
        
        // Clean up
        $stmt = $pdo->prepare('DELETE FROM users WHERE email = ?');
        $stmt->execute(['googletest@example.com']);
        echo "\n✓ Test user cleaned up\n";
    } else {
        echo "\n✗ User NOT found in database\n";
    }
} catch (Exception $e) {
    echo "\nDatabase check error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>