<?php
/**
 * Test Inspector Real Progress API
 * Tests the inspector API with real progress calculation
 */

echo "🔍 Testing Inspector Real Progress API...\n\n";

// Simulate inspector session
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_user_id'] = 1001; // Inspector user ID
$_SESSION['admin_email'] = 'inspector@buildhub.com';
$_SESSION['admin_scope'] = 'INSPECTOR';

// Make HTTP request to the API
$url = 'http://localhost/buildhub/backend/api/inspector/get_projects_with_real_progress.php';

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
    echo "❌ Failed to call API\n";
    exit(1);
}

$result = json_decode($response, true);

if (!$result) {
    echo "❌ Invalid JSON response\n";
    echo "Raw response: " . $response . "\n";
    exit(1);
}

if ($result['success']) {
    echo "✅ Inspector API successful!\n\n";
    
    echo "🏗️  Assigned Projects with Real Progress:\n";
    echo "========================================\n";
    
    foreach ($result['projects'] as $project) {
        echo "📋 Project: {$project['project_name']}\n";
        echo "   ID: {$project['id']}\n";
        echo "   Status: {$project['status']}\n";
        echo "   Stored Progress: {$project['stored_completion_percentage']}%\n";
        echo "   Real Progress: {$project['real_completion_percentage']}%\n";
        echo "   Stored Stage: {$project['stored_stage']}\n";
        echo "   Actual Stage: {$project['actual_current_stage']}\n";
        echo "   Next Expected: {$project['next_expected_stage']}\n";
        echo "   Location: {$project['project_location']}\n";
        echo "   Homeowner: {$project['homeowner']['name']}\n";
        echo "   Contractor: {$project['contractor']['name']}\n";
        echo "   Completed Stages: {$project['statistics']['completed_stages']}/{$project['statistics']['total_stages']}\n";
        
        if ($project['latest_stage_payment']) {
            $payment = $project['latest_stage_payment'];
            echo "   Latest Payment: {$payment['stage_name']} - {$payment['status']} ({$payment['completion_percentage']}%)\n";
        }
        
        echo "   Progress Method: {$project['progress_calculation']['method']}\n";
        echo "\n";
    }
    
    echo "📊 Inspector Statistics:\n";
    echo "======================\n";
    echo "Total Projects: {$result['statistics']['total_projects']}\n";
    echo "Active Projects: {$result['statistics']['active_projects']}\n";
    echo "Completed Projects: {$result['statistics']['completed_projects']}\n";
    echo "Average Real Progress: {$result['statistics']['avg_real_completion']}%\n";
    echo "Total Reports: {$result['statistics']['total_reports']}\n";
    echo "Total Notes: {$result['statistics']['total_notes']}\n";
    
    echo "\n🔧 Progress Calculation Info:\n";
    echo "============================\n";
    echo "{$result['progress_info']['calculation_method']}\n";
    echo "Stage Percentages:\n";
    foreach ($result['progress_info']['stage_percentages'] as $stage => $percentage) {
        echo "  - {$stage}: {$percentage}%\n";
    }
    
} else {
    echo "❌ Inspector API failed\n";
    echo "Error: {$result['message']}\n";
}
?>