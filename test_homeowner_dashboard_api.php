<?php
// Test the homeowner dashboard API
session_start();

// Set test homeowner session (user ID 28 based on the database data)
$_SESSION['user_id'] = 28;
$_SESSION['user_role'] = 'homeowner';
$_SESSION['user_name'] = 'SHIJIN THOMAS MCA2024-2026';

echo "<h2>Testing Homeowner Dashboard API</h2>";
echo "<p>Testing with homeowner ID: " . $_SESSION['user_id'] . "</p>";

// Make API call
$url = 'http://localhost/backend/api/homeowner/get_dashboard_data.php';

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
    echo "<p style='color: red;'>Error: Could not fetch data from API</p>";
    exit;
}

$data = json_decode($response, true);

echo "<h3>API Response:</h3>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
echo json_encode($data, JSON_PRETTY_PRINT);
echo "</pre>";

if ($data && $data['success']) {
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li>Total Projects: " . $data['data']['overview']['total_projects'] . "</li>";
    echo "<li>Projects in Progress: " . $data['data']['overview']['projects_in_progress'] . "</li>";
    echo "<li>Total Project Value: ₹" . number_format($data['data']['overview']['total_project_value']) . "</li>";
    echo "<li>Average Completion: " . $data['data']['overview']['average_completion'] . "%</li>";
    echo "<li>Total Budget: ₹" . number_format($data['data']['budget_summary']['total_budget']) . "</li>";
    echo "<li>Total Paid: ₹" . number_format($data['data']['budget_summary']['total_paid']) . "</li>";
    echo "<li>Total Pending: ₹" . number_format($data['data']['budget_summary']['total_pending']) . "</li>";
    echo "<li>Budget Utilization: " . $data['data']['budget_summary']['utilization_percentage'] . "%</li>";
    echo "</ul>";
    
    if (!empty($data['data']['projects'])) {
        echo "<h3>Projects:</h3>";
        foreach ($data['data']['projects'] as $project) {
            echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>" . htmlspecialchars($project['name']) . "</h4>";
            echo "<p><strong>Status:</strong> " . $project['progress']['status'] . "</p>";
            echo "<p><strong>Current Stage:</strong> " . $project['progress']['current_stage'] . "</p>";
            echo "<p><strong>Completion:</strong> " . $project['progress']['completion_percentage'] . "%</p>";
            echo "<p><strong>Total Cost:</strong> ₹" . number_format($project['budget']['total_cost']) . "</p>";
            echo "<p><strong>Paid Amount:</strong> ₹" . number_format($project['budget']['paid_amount']) . "</p>";
            echo "<p><strong>Pending Amount:</strong> ₹" . number_format($project['budget']['pending_amount']) . "</p>";
            echo "<p><strong>Contractor:</strong> " . ($project['contractor']['name'] ?: 'Not assigned') . "</p>";
            echo "</div>";
        }
    }
    
    if (!empty($data['data']['recent_activity']['payments'])) {
        echo "<h3>Recent Payment Activity:</h3>";
        foreach ($data['data']['recent_activity']['payments'] as $payment) {
            echo "<div style='border-left: 3px solid #007bff; padding: 10px; margin: 5px 0; background: #f8f9fa;'>";
            echo "<strong>" . $payment['stage_name'] . "</strong> - " . $payment['project_name'];
            echo "<br>Amount: ₹" . number_format($payment['amount']) . " | Status: " . strtoupper($payment['status']);
            echo "<br>Contractor: " . $payment['contractor_name'] . " | " . $payment['days_ago'] . " days ago";
            echo "</div>";
        }
    }
} else {
    echo "<p style='color: red;'>API Error: " . ($data['message'] ?? 'Unknown error') . "</p>";
}
?>