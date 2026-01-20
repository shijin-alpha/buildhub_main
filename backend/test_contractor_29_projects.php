<?php
$contractor_id = 29;
$api_url = "http://localhost/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id={$contractor_id}";

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "=== Contractor ID 29 (Shijin Thomas) Projects ===\n\n";

if ($data['success']) {
    $projects = $data['data']['projects'];
    echo "Total Projects: " . count($projects) . "\n\n";
    
    foreach ($projects as $project) {
        echo "Project ID: {$project['id']}\n";
        echo "  Name: {$project['project_name']}\n";
        echo "  Estimate Cost: " . ($project['estimate_cost'] ? "₹" . number_format($project['estimate_cost'], 2) : "Not set") . "\n";
        echo "  Homeowner: {$project['homeowner_name']}\n";
        echo "  Status: {$project['status']}\n";
        echo "  Source: {$project['source']}\n";
        echo "\n";
    }
    
    // Check if estimate ID 37 is included
    $found_37 = false;
    foreach ($projects as $project) {
        if ($project['id'] == 37 || $project['estimate_id'] == 37) {
            $found_37 = true;
            echo "✅ Estimate ID 37 IS included in the results!\n";
            break;
        }
    }
    
    if (!$found_37) {
        echo "❌ Estimate ID 37 is NOT in the results\n";
        echo "   This means the query is not including 'project_created' status\n";
    }
} else {
    echo "❌ API Error: {$data['message']}\n";
}
