<?php
/**
 * Test Project Details API via HTTP
 */

echo "🌐 Testing Project Details API via HTTP...\n\n";

// Test the project details API endpoint
$apiUrl = 'http://localhost/buildhub/backend/api/inspector/get_project_details.php?project_id=3';

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
    
    $error = error_get_last();
    if ($error) {
        echo "Error details: {$error['message']}\n";
    }
    
} else {
    echo "✅ HTTP request successful\n";
    echo "Response length: " . strlen($response) . " characters\n";
    
    // Parse JSON response
    $data = json_decode($response, true);
    
    if ($data === null) {
        echo "❌ Invalid JSON response\n";
        echo "Raw response (first 500 chars): " . substr($response, 0, 500) . "\n";
    } else {
        echo "✅ Valid JSON response\n";
        
        if (isset($data['success']) && $data['success']) {
            echo "✅ API success status\n";
            
            $project = $data['project'];
            echo "\n📋 Project Details:\n";
            echo "   ID: {$project['project_id']}\n";
            echo "   Name: {$project['project_name']}\n";
            echo "   Status: {$project['project_status']}\n";
            echo "   Current Stage: {$project['current_stage']}\n";
            echo "   Stored Progress: {$project['completion_percentage']}%\n";
            echo "   Real Progress: {$project['real_completion_percentage']}%\n";
            echo "   Completed Stages: {$project['completed_stages']}/{$project['total_stages']}\n";
            
            echo "\n💰 Stage Payments: " . count($data['stage_payments']) . "\n";
            foreach ($data['stage_payments'] as $payment) {
                $status_icon = $payment['status'] === 'paid' ? '✅' : 
                              ($payment['status'] === 'approved' ? '🟡' : '⏳');
                echo "   {$status_icon} {$payment['stage_name']}: {$payment['completion_percentage']}% - ₹" . 
                     number_format($payment['requested_amount']) . " ({$payment['status']})\n";
            }
            
            echo "\n📊 Progress Calculation:\n";
            $calc = $data['progress_calculation'];
            echo "   Method: {$calc['method']}\n";
            echo "   Real Progress: {$calc['real_progress']}%\n";
            echo "   Stored Progress: {$calc['stored_progress']}%\n";
            echo "   Difference: {$calc['difference']}%\n";
            
        } else {
            echo "❌ API error status\n";
            if (isset($data['message'])) {
                echo "Error message: {$data['message']}\n";
            }
            if (isset($data['error'])) {
                echo "Error details: {$data['error']}\n";
            }
        }
    }
}

echo "\n✅ Project Details HTTP Test Complete!\n";
?>