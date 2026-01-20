<?php
$url = "http://localhost/buildhub/backend/api/homeowner/get_progress_updates.php?homeowner_id=28&limit=5";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response) {
    echo "API Response:\n";
    echo "=============\n";
    
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        echo "✅ Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        echo "📊 Progress Updates: " . count($data['data']['progress_updates']) . "\n";
        echo "🏗️ Projects: " . count($data['data']['projects']) . "\n";
        echo "📸 Geo Photos: " . count($data['data']['geo_photos']) . "\n";
        
        if (count($data['data']['progress_updates']) > 0) {
            $update = $data['data']['progress_updates'][0];
            echo "\n📋 First Update Details:\n";
            echo "- ID: " . $update['id'] . "\n";
            echo "- Project ID: " . $update['project_id'] . "\n";
            echo "- Contractor: " . $update['contractor_name'] . "\n";
            echo "- Stage: " . $update['construction_stage'] . "\n";
            echo "- Date: " . $update['update_date'] . "\n";
            echo "- Progress: " . $update['cumulative_completion_percentage'] . "%\n";
            echo "- Working Hours: " . $update['working_hours'] . "\n";
            echo "- Weather: " . $update['weather_condition'] . "\n";
            echo "- Photos: " . count($update['photos']) . "\n";
            echo "- Worker Types: " . ($update['worker_types'] ?: 'None') . "\n";
            echo "- Total Workers: " . ($update['total_workers'] ?: 0) . "\n";
            
            echo "\n🔍 Full Update Data Structure:\n";
            foreach ($update as $key => $value) {
                if (is_array($value)) {
                    echo "- $key: [array with " . count($value) . " items]\n";
                } else {
                    $displayValue = is_string($value) && strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                    echo "- $key: " . ($displayValue ?: 'null') . "\n";
                }
            }
        }
    } else {
        echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        echo "Raw response: " . $response . "\n";
    }
} else {
    echo "❌ Failed to get response from API\n";
}
?>
</content>