<?php
/**
 * Test API Direct Access
 * Test the inspector APIs by making HTTP requests
 */

echo "🧪 Testing Inspector APIs via HTTP\n";
echo "==================================\n\n";

// Function to make HTTP request
function testAPI($url) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json',
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['success' => false, 'error' => 'Failed to connect'];
    }
    
    $data = json_decode($response, true);
    if ($data === null) {
        return ['success' => false, 'error' => 'Invalid JSON', 'raw' => $response];
    }
    
    return $data;
}

// Test URLs
$baseUrl = 'http://localhost/buildhub/backend/api/inspector/';
$tests = [
    'get_assigned_projects.php' => 'Get Assigned Projects',
    'get_project_details.php?project_id=1' => 'Get Project 1 Details',
    'get_inspection_reports.php' => 'Get Inspection Reports',
    'get_site_notes.php' => 'Get Site Notes'
];

foreach ($tests as $endpoint => $description) {
    echo "Testing: $description\n";
    echo "URL: $baseUrl$endpoint\n";
    
    $result = testAPI($baseUrl . $endpoint);
    
    if (isset($result['success'])) {
        if ($result['success']) {
            echo "✅ Success\n";
        } else {
            echo "⚠️  API Error: " . ($result['message'] ?? 'Unknown error') . "\n";
            if (isset($result['error_code'])) {
                echo "   Error Code: " . $result['error_code'] . "\n";
            }
        }
    } else {
        echo "❌ Failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        if (isset($result['raw'])) {
            echo "   Raw Response: " . substr($result['raw'], 0, 200) . "...\n";
        }
    }
    echo "\n";
}

echo "🔍 Test complete!\n";
?>