<?php
/**
 * Test Inspector APIs
 * Simple test script to verify all inspector APIs are working
 */

echo "🧪 Testing Inspector APIs\n";
echo "========================\n\n";

// Test URLs
$baseUrl = 'http://localhost/buildhub/backend/api/inspector/';
$testUrls = [
    'get_assigned_projects.php',
    'get_project_details.php?project_id=1',
    'get_project_details.php?project_id=2', 
    'get_project_details.php?project_id=3',
    'get_inspection_reports.php',
    'get_site_notes.php'
];

foreach ($testUrls as $url) {
    echo "Testing: $url\n";
    
    $fullUrl = $baseUrl . $url;
    $response = @file_get_contents($fullUrl);
    
    if ($response === false) {
        echo "❌ Failed to connect to $url\n";
    } else {
        $data = json_decode($response, true);
        if ($data && isset($data['success'])) {
            if ($data['success']) {
                echo "✅ $url - Success\n";
            } else {
                echo "⚠️  $url - API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "❌ $url - Invalid JSON response\n";
        }
    }
    echo "\n";
}

echo "🔍 Testing complete!\n";
echo "\nNote: Some APIs may show authentication errors, which is expected\n";
echo "when testing without proper session authentication.\n";
?>