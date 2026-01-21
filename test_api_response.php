<?php
session_start();

// Simulate architect session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'architect';

echo "=== Testing API Response ===\n\n";

// Test the get_concept_previews.php endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/buildhub/backend/api/architect/get_concept_previews.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "✓ API call successful\n";
        echo "Number of previews: " . count($data['previews']) . "\n";
        
        foreach ($data['previews'] as $preview) {
            echo "\nPreview ID: " . $preview['id'] . "\n";
            echo "Status: " . $preview['status'] . "\n";
            echo "Image URL: " . ($preview['image_url'] ?? 'NULL') . "\n";
            echo "Homeowner: " . ($preview['homeowner_name'] ?? 'Unknown') . "\n";
            echo "Description: " . substr($preview['original_description'], 0, 50) . "...\n";
            
            if ($preview['image_url']) {
                $imagePath = str_replace('/buildhub/', '', $preview['image_url']);
                $fullPath = __DIR__ . '/' . $imagePath;
                echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
                if (file_exists($fullPath)) {
                    echo "File size: " . filesize($fullPath) . " bytes\n";
                }
            }
        }
    } else {
        echo "✗ API call failed\n";
        if (isset($data['message'])) {
            echo "Message: " . $data['message'] . "\n";
        }
    }
} else {
    echo "✗ HTTP error: $httpCode\n";
}

echo "\n=== Test Complete ===\n";
?>