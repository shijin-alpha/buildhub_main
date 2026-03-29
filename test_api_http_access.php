<?php
/**
 * Test API HTTP Access
 * Verifies that the API endpoints are accessible via HTTP requests
 */

echo "🌐 Testing API HTTP Access...\n\n";

// Test the real progress API endpoint
$apiUrl = 'http://localhost/buildhub/backend/api/inspector/get_projects_simple.php';

echo "📡 Testing API URL: {$apiUrl}\n";

// Create HTTP context
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        'timeout' => 30
    ]
]);

// Make the HTTP request
$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    echo "❌ HTTP request failed\n";
    
    // Check if it's a network issue or server issue
    $error = error_get_last();
    if ($error) {
        echo "Error details: {$error['message']}\n";
    }
    
    // Try to check if the file exists
    $filePath = 'backend/api/inspector/get_projects_simple.php';
    if (file_exists($filePath)) {
        echo "✅ API file exists at: {$filePath}\n";
        
        // Try to execute directly
        echo "🔧 Testing direct execution...\n";
        ob_start();
        include $filePath;
        $directOutput = ob_get_clean();
        
        if (!empty($directOutput)) {
            echo "✅ Direct execution successful\n";
            echo "Response length: " . strlen($directOutput) . " characters\n";
            
            // Check if it's valid JSON
            $json = json_decode($directOutput, true);
            if ($json !== null) {
                echo "✅ Valid JSON response\n";
                if (isset($json['success']) && $json['success']) {
                    echo "✅ API returns success status\n";
                    echo "Projects found: " . count($json['projects']) . "\n";
                } else {
                    echo "❌ API returns error status\n";
                    if (isset($json['message'])) {
                        echo "Error message: {$json['message']}\n";
                    }
                }
            } else {
                echo "❌ Invalid JSON response\n";
                echo "Raw output (first 200 chars): " . substr($directOutput, 0, 200) . "\n";
            }
        } else {
            echo "❌ Direct execution produced no output\n";
        }
    } else {
        echo "❌ API file not found at: {$filePath}\n";
    }
    
} else {
    echo "✅ HTTP request successful\n";
    echo "Response length: " . strlen($response) . " characters\n";
    
    // Parse JSON response
    $data = json_decode($response, true);
    
    if ($data === null) {
        echo "❌ Invalid JSON response\n";
        echo "Raw response (first 200 chars): " . substr($response, 0, 200) . "\n";
    } else {
        echo "✅ Valid JSON response\n";
        
        if (isset($data['success']) && $data['success']) {
            echo "✅ API success status\n";
            echo "Projects returned: " . count($data['projects']) . "\n";
            echo "Statistics included: " . (isset($data['statistics']) ? 'Yes' : 'No') . "\n";
            
            // Show sample project data
            if (!empty($data['projects'])) {
                $project = $data['projects'][0];
                echo "\n📋 Sample Project Data:\n";
                echo "   ID: {$project['id']}\n";
                echo "   Name: {$project['project_name']}\n";
                echo "   Real Progress: {$project['real_completion_percentage']}%\n";
                echo "   Stored Progress: {$project['stored_completion_percentage']}%\n";
                echo "   Status: {$project['status']}\n";
            }
            
        } else {
            echo "❌ API error status\n";
            if (isset($data['message'])) {
                echo "Error message: {$data['message']}\n";
            }
        }
    }
}

echo "\n🔧 Server Environment Check:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Current Working Directory: " . getcwd() . "\n";

echo "\n✅ API HTTP Access Test Complete!\n";
?>