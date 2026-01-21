<?php
session_start();

// Simulate architect session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'architect';

// Test the concept generation workflow
echo "=== Testing Concept Generation Workflow ===\n\n";

// Step 1: Generate a concept preview
echo "Step 1: Generating concept preview...\n";

$postData = json_encode([
    'layout_request_id' => 1,
    'concept_description' => 'A modern two-story house with clean lines, large windows, and white exterior walls'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/buildhub/backend/api/architect/generate_concept_preview.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    if ($result && isset($result['concept_id'])) {
        $conceptId = $result['concept_id'];
        echo "✓ Concept created with ID: $conceptId\n";
        
        // Step 2: Check concept status
        echo "\nStep 2: Checking concept status...\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/buildhub/backend/api/architect/get_concept_previews.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        echo "Concept previews response: $response\n";
        
        $previews = json_decode($response, true);
        if ($previews && isset($previews['previews'])) {
            foreach ($previews['previews'] as $preview) {
                if ($preview['id'] == $conceptId) {
                    echo "Found concept: Status = {$preview['status']}, Image URL = " . ($preview['image_url'] ?? 'NULL') . "\n";
                    break;
                }
            }
        }
        
        // Step 3: Wait and check again (simulate polling)
        echo "\nStep 3: Waiting 10 seconds and checking again...\n";
        sleep(10);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/buildhub/backend/api/architect/get_concept_previews.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $previews = json_decode($response, true);
        if ($previews && isset($previews['previews'])) {
            foreach ($previews['previews'] as $preview) {
                if ($preview['id'] == $conceptId) {
                    echo "Updated concept: Status = {$preview['status']}, Image URL = " . ($preview['image_url'] ?? 'NULL') . "\n";
                    
                    if ($preview['image_url']) {
                        $imagePath = str_replace('/buildhub/', '', $preview['image_url']);
                        $fullPath = __DIR__ . '/' . $imagePath;
                        echo "Image file exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
                        if (file_exists($fullPath)) {
                            echo "Image file size: " . filesize($fullPath) . " bytes\n";
                        }
                    }
                    break;
                }
            }
        }
    }
} else {
    echo "✗ Failed to generate concept\n";
}

echo "\n=== Test Complete ===\n";
?>