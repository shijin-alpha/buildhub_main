<?php
// Test the fixed API
$contractor_id = 29;

// Simulate the API call
$_GET['contractor_id'] = $contractor_id;

// Capture output
ob_start();
include 'backend/api/contractor/get_contractor_projects.php';
$output = ob_get_clean();

// Parse JSON
$data = json_decode($output, true);

if ($data && $data['success']) {
    echo "=== API RESPONSE TEST ===\n\n";
    echo "Total Projects: " . $data['data']['total_projects'] . "\n\n";
    
    foreach ($data['data']['projects'] as $project) {
        echo "Project ID: {$project['id']}\n";
        echo "Name: {$project['project_name']}\n";
        echo "Cost: ₹" . number_format($project['estimate_cost'] ?? 0, 2) . "\n";
        echo "Daily Updates: {$project['daily_updates_count']}\n";
        echo "Weekly Summaries: {$project['weekly_summaries_count']}\n";
        echo "Monthly Reports: {$project['monthly_reports_count']}\n";
        echo "Source: {$project['source']}\n";
        echo "---\n\n";
    }
} else {
    echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    echo "Raw output:\n";
    echo $output;
}
