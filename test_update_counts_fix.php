<?php
// Test the updated contractor projects API with update counts
$contractor_id = 29; // Shijin Thomas contractor ID

echo "=== TESTING UPDATED CONTRACTOR PROJECTS API WITH UPDATE COUNTS ===\n\n";

// Simulate the API call
$_GET['contractor_id'] = $contractor_id;

// Capture output
ob_start();
include 'backend/api/contractor/get_contractor_projects.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n\n";

// Parse and display formatted
$response = json_decode($output, true);
if ($response && $response['success']) {
    echo "=== UPDATE COUNTS VERIFICATION ===\n";
    foreach ($response['data']['projects'] as $project) {
        echo "Project ID: {$project['id']}\n";
        echo "Project Name: {$project['project_name']}\n";
        echo "Estimate ID: " . ($project['estimate_id'] ?? 'N/A') . "\n";
        echo "Source: {$project['source']}\n";
        
        // Check update counts
        echo "UPDATE COUNTS:\n";
        echo "  Daily Updates: " . ($project['daily_updates_count'] ?? 'NOT SET') . "\n";
        echo "  Weekly Summaries: " . ($project['weekly_summaries_count'] ?? 'NOT SET') . "\n";
        echo "  Monthly Reports: " . ($project['monthly_reports_count'] ?? 'NOT SET') . "\n";
        echo "  Latest Update: " . ($project['latest_update_timestamp'] ?? 'N/A') . "\n";
        
        // Verify if this is project 37
        if ($project['id'] == 37 || $project['estimate_id'] == 37) {
            echo "  🎯 THIS IS PROJECT 37 - Should show 1 daily update!\n";
            if (($project['daily_updates_count'] ?? 0) > 0) {
                echo "  ✅ SUCCESS: Daily updates count is correct!\n";
            } else {
                echo "  ❌ ISSUE: Daily updates count is still 0\n";
            }
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
} else {
    echo "API Error: " . ($response['message'] ?? 'Unknown error') . "\n";
    if (isset($response['error'])) {
        echo "Error Details: " . $response['error'] . "\n";
    }
}
?>