<?php
// Test with a simulated architect session
session_start();

// Simulate an architect login
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get an architect user
    $architectStmt = $db->query("SELECT id FROM users WHERE role = 'architect' LIMIT 1");
    $architect = $architectStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$architect) {
        echo "No architect found\n";
        exit;
    }
    
    // Set session
    $_SESSION['user_id'] = $architect['id'];
    $_SESSION['role'] = 'architect';
    
    echo "Set session for architect ID: " . $architect['id'] . "\n";
    
    // Now test the endpoint
    $url = 'http://localhost/buildhub/backend/api/architect/generate_concept_preview.php';
    
    $data = [
        'layout_request_id' => 62,
        'concept_description' => 'A modern two-story villa with clean lines, large windows, flat roof, white exterior walls, and a contemporary entrance with glass doors.'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Cookie: PHPSESSID=' . session_id()
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
    echo "Body length: " . strlen($body) . "\n";
    echo "Body:\n$body\n";
    
    // Check character at position 78
    if (strlen($body) > 78) {
        echo "Character at position 78: '" . $body[78] . "' (ASCII: " . ord($body[78]) . ")\n";
    }
    
    // Try to decode JSON
    $decoded = json_decode($body);
    if ($decoded === null) {
        echo "JSON decode failed. Error: " . json_last_error_msg() . "\n";
        
        // Show first 100 characters with visible representation
        echo "First 100 chars: ";
        for ($i = 0; $i < min(100, strlen($body)); $i++) {
            $char = $body[$i];
            if (ctype_print($char)) {
                echo $char;
            } else {
                echo '[' . ord($char) . ']';
            }
        }
        echo "\n";
    } else {
        echo "JSON is valid!\n";
        print_r($decoded);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>