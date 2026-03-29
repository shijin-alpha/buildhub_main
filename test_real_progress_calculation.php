<?php
/**
 * Test Real Progress Calculation
 * Tests the real progress calculation and displays results
 */

echo "🔧 Testing Real Progress Calculation...\n\n";

// Make HTTP request to the API
$url = 'http://localhost/buildhub/backend/api/admin/calculate_real_progress.php';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
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
    echo "✅ Progress calculation successful!\n\n";
    
    echo "📊 Updated Projects:\n";
    echo "==================\n";
    
    foreach ($result['updated_projects'] as $project) {
        echo "🏗️  Project: {$project['project_name']}\n";
        echo "   ID: {$project['project_id']}\n";
        echo "   Progress: {$project['old_progress']}% → {$project['new_progress']}%\n";
        echo "   Stage: {$project['old_stage']} → {$project['new_stage']}\n";
        echo "   Status: {$project['old_status']} → {$project['new_status']}\n";
        echo "   Completed Stages: " . count($project['completed_stages']) . "/{$project['total_stages']}\n";
        
        if (!empty($project['completed_stages'])) {
            echo "   Completed:\n";
            foreach ($project['completed_stages'] as $stage) {
                echo "     - {$stage['stage_name']} ({$stage['completion_percentage']}%) - {$stage['status']}\n";
            }
        }
        echo "\n";
    }
    
    echo "📈 Overall Statistics:\n";
    echo "====================\n";
    echo "Total Projects: {$result['statistics']['total_projects']}\n";
    echo "Average Progress: {$result['statistics']['average_progress']}%\n";
    echo "Active Projects: {$result['statistics']['active_projects']}\n";
    echo "Completed Projects: {$result['statistics']['completed_projects']}\n";
    echo "Started Projects: {$result['statistics']['started_projects']}\n";
    echo "\nCalculation Method: {$result['calculation_method']}\n";
    echo "Timestamp: {$result['timestamp']}\n";
    
} else {
    echo "❌ Progress calculation failed\n";
    echo "Error: {$result['message']}\n";
    if (isset($result['error'])) {
        echo "Details: {$result['error']}\n";
    }
}
?>