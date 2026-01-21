<?php
// Test the updated contractor projects API
$contractor_id = 29; // Shijin Thomas contractor ID

echo "=== TESTING UPDATED CONTRACTOR PROJECTS API ===\n\n";

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
    echo "=== FORMATTED PROJECT DATA ===\n";
    foreach ($response['data']['projects'] as $project) {
        echo "Project ID: {$project['id']}\n";
        echo "Project Name: {$project['project_name']}\n";
        echo "Homeowner: {$project['homeowner_name']}\n";
        echo "Email: {$project['homeowner_email']}\n";
        echo "Phone: " . ($project['homeowner_phone'] ?? 'N/A') . "\n";
        echo "Estimate Cost: " . ($project['estimate_cost'] ? "₹" . number_format($project['estimate_cost']) : 'N/A') . "\n";
        echo "Location: " . ($project['location'] ?? 'N/A') . "\n";
        echo "Plot Size: " . ($project['plot_size'] ?? 'N/A') . "\n";
        echo "Built-up Area: " . ($project['built_up_area'] ?? 'N/A') . "\n";
        echo "Floors: " . ($project['floors'] ?? 'N/A') . "\n";
        echo "Timeline: " . ($project['timeline'] ?? 'N/A') . "\n";
        echo "Status: {$project['status']}\n";
        echo "Source: {$project['source']}\n";
        echo "Contractor: " . ($project['contractor_name'] ?? 'N/A') . "\n";
        echo "Contractor Email: " . ($project['contractor_email'] ?? 'N/A') . "\n";
        
        if (isset($project['structured_data'])) {
            echo "Has Structured Data: YES\n";
            $structured = $project['structured_data'];
            if (isset($structured['totals'])) {
                echo "  Materials: " . ($structured['totals']['materials'] ?? 'N/A') . "\n";
                echo "  Labor: " . ($structured['totals']['labor'] ?? 'N/A') . "\n";
                echo "  Utilities: " . ($structured['totals']['utilities'] ?? 'N/A') . "\n";
                echo "  Misc: " . ($structured['totals']['misc'] ?? 'N/A') . "\n";
                echo "  Grand Total: " . ($structured['totals']['grand'] ?? 'N/A') . "\n";
            }
        } else {
            echo "Has Structured Data: NO\n";
        }
        
        echo "Request Date: " . ($project['request_date_formatted'] ?? 'N/A') . "\n";
        echo "Estimate Date: " . ($project['estimate_date_formatted'] ?? 'N/A') . "\n";
        echo "Acknowledged Date: " . ($project['acknowledged_date_formatted'] ?? 'N/A') . "\n";
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
} else {
    echo "API Error: " . ($response['message'] ?? 'Unknown error') . "\n";
}
?>