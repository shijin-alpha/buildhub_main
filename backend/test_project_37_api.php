<?php
// Test the API response for project 37
$contractor_id = 29;
$url = "http://localhost/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id=$contractor_id";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if ($data && $data['success']) {
    echo "=== API RESPONSE FOR CONTRACTOR 29 ===\n\n";
    echo "Total Projects: " . $data['data']['total_projects'] . "\n\n";
    
    foreach ($data['data']['projects'] as $project) {
        if ($project['id'] == 37) {
            echo "PROJECT ID 37 FOUND!\n";
            echo "==================\n";
            echo "Project Name: " . $project['project_name'] . "\n";
            echo "Homeowner: " . $project['homeowner_name'] . "\n";
            echo "Email: " . $project['homeowner_email'] . "\n";
            echo "Phone: " . ($project['homeowner_phone'] ?? 'N/A') . "\n";
            echo "Location: " . ($project['location'] ?? 'N/A') . "\n";
            echo "Plot Size: " . ($project['plot_size'] ?? 'N/A') . "\n";
            echo "Built-up Area: " . ($project['built_up_area'] ?? 'N/A') . "\n";
            echo "Floors: " . ($project['floors'] ?? 'N/A') . "\n";
            echo "Estimate Cost: ₹" . number_format($project['estimate_cost'], 2) . "\n";
            echo "Timeline: " . $project['timeline'] . "\n";
            echo "Status: " . $project['status'] . "\n";
            echo "\nStructured Data Available: " . (isset($project['structured_data']) ? 'YES' : 'NO') . "\n";
            
            if (isset($project['structured_data'])) {
                echo "\nStructured Data Keys:\n";
                foreach (array_keys($project['structured_data']) as $key) {
                    echo "  - $key\n";
                }
            }
            break;
        }
    }
} else {
    echo "API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
}
?>
