<?php
// Test the fixed progress API
$project_id = 37;

echo "=== TESTING FIXED PROGRESS API FOR PROJECT 37 ===\n\n";

// Simulate the API call
$_GET['project_id'] = $project_id;

// Capture output
ob_start();
include 'backend/api/contractor/get_project_current_progress.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n\n";

// Parse and display formatted
$response = json_decode($output, true);
if ($response && $response['success']) {
    echo "=== FORMATTED PROGRESS DATA ===\n";
    $data = $response['data'];
    
    echo "Project ID: {$data['project_id']}\n";
    echo "Project Name: {$data['project_name']}\n";
    echo "Current Progress: {$data['current_progress']}%\n";
    echo "Latest Stage: {$data['latest_stage']}\n";
    echo "Latest Update Date: {$data['latest_update_date']}\n";
    echo "Project Budget: " . ($data['budget_formatted'] ?? 'N/A') . "\n";
    echo "Timeline: " . ($data['project_timeline'] ?? 'N/A') . "\n";
    echo "Homeowner: " . ($data['homeowner_name'] ?? 'N/A') . "\n";
    echo "Contractor: " . ($data['contractor_name'] ?? 'N/A') . "\n";
    echo "Total Updates: {$data['total_updates']}\n";
    echo "Has Updates: " . ($data['has_updates'] ? 'YES' : 'NO') . "\n";
    echo "Latest Work: {$data['latest_work_description']}\n";
    echo "Working Hours: {$data['latest_working_hours']}\n";
    echo "Weather: {$data['latest_weather']}\n";
    
} else {
    echo "API Error: " . ($response['message'] ?? 'Unknown error') . "\n";
    if (isset($response['error'])) {
        echo "Error Details: " . $response['error'] . "\n";
    }
}
?>