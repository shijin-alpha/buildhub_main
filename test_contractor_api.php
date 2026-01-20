<?php
// Simple test script for the contractor house plans API
session_start();

// Set up a test session (you'll need to adjust these values)
$_SESSION['user_id'] = 1; // Replace with actual architect ID
$_SESSION['role'] = 'architect';

echo "<h1>Testing Contractor House Plans API</h1>";

echo "<h2>Session Info:</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "<br><br>";

echo "<h2>API Test:</h2>";

// Make a request to the API
$url = 'http://localhost/buildhub/backend/api/architect/get_contractor_house_plans.php';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Content-Type: application/json',
            'Cookie: ' . session_name() . '=' . session_id()
        ]
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Failed to make API request<br>";
    echo "Error: " . error_get_last()['message'] . "<br>";
} else {
    echo "✅ API request successful<br>";
    echo "<h3>Response:</h3>";
    
    $data = json_decode($response, true);
    
    if ($data) {
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        
        if ($data['success']) {
            echo "<h3>Summary:</h3>";
            echo "Total Plans: " . $data['summary']['total_plans'] . "<br>";
            echo "Total Contractors: " . $data['summary']['total_contractors'] . "<br>";
            echo "Active Estimates: " . $data['summary']['active_estimates'] . "<br>";
            echo "Completed Estimates: " . $data['summary']['completed_estimates'] . "<br>";
        } else {
            echo "<h3>Error:</h3>";
            echo "Message: " . $data['message'] . "<br>";
            if (isset($data['debug'])) {
                echo "Debug: " . $data['debug'] . "<br>";
            }
        }
    } else {
        echo "❌ Failed to parse JSON response<br>";
        echo "Raw response: " . htmlspecialchars($response) . "<br>";
    }
}

echo "<br><h2>Direct API Call:</h2>";
echo "<a href='/buildhub/backend/api/architect/get_contractor_house_plans.php' target='_blank'>Click here to test API directly</a><br>";

echo "<br><h2>Debug Script:</h2>";
echo "<a href='/buildhub/backend/debug_contractor_house_plans.php' target='_blank'>Click here to run debug script</a><br>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #333; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>