<?php
// Test the actual API endpoint
$contractor_id = 32; // Using a known contractor ID

$url = "http://localhost/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id=" . $contractor_id;

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Content-Type: application/json',
        ]
    ]
]);

echo "Testing API: $url\n\n";

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "Failed to get response from API\n";
    exit;
}

$data = json_decode($response, true);

if (!$data) {
    echo "Failed to decode JSON response\n";
    echo "Raw response: $response\n";
    exit;
}

echo "API Response Status: " . ($data['success'] ? 'Success' : 'Failed') . "\n";

if (isset($data['data']['projects']) && !empty($data['data']['projects'])) {
    echo "Number of projects: " . count($data['data']['projects']) . "\n\n";
    
    foreach ($data['data']['projects'] as $index => $project) {
        echo "=== Project " . ($index + 1) . " ===\n";
        echo "ID: " . $project['id'] . "\n";
        echo "Name: " . $project['project_name'] . "\n";
        echo "Daily Updates Count: " . ($project['daily_updates_count'] ?? 'NOT SET') . "\n";
        echo "Weekly Summaries Count: " . ($project['weekly_summaries_count'] ?? 'NOT SET') . "\n";
        echo "Monthly Reports Count: " . ($project['monthly_reports_count'] ?? 'NOT SET') . "\n";
        echo "Latest Update: " . ($project['latest_update_timestamp'] ?? 'NOT SET') . "\n";
        echo "Updated At: " . ($project['updated_at'] ?? 'NOT SET') . "\n";
        echo "\n";
    }
} else {
    echo "No projects found or projects array is empty\n";
    if (isset($data['message'])) {
        echo "Message: " . $data['message'] . "\n";
    }
    if (isset($data['data'])) {
        echo "Data structure keys: " . implode(', ', array_keys($data['data'])) . "\n";
    }
}

echo "Full response (first 1000 chars):\n";
echo substr($response, 0, 1000) . "\n";
?>