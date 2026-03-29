<?php
// Test ML Analytics API
$url = 'http://localhost/buildhub/backend/api/ml/get_project_analytics.php?project_id=1';
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "ML Analytics API Test\n";
echo "=====================\n\n";

if ($data && isset($data['success'])) {
    if ($data['success']) {
        echo "✅ API Working!\n\n";
        echo "Project: " . $data['data']['project']['name'] . "\n";
        echo "Budget: ₹" . number_format($data['data']['project']['budget']) . "\n";
        
        if (isset($data['data']['prediction'])) {
            echo "\nPrediction:\n";
            echo "  Cost Risk: " . $data['data']['prediction']['cost_risk_level'] . " (" . 
                 round($data['data']['prediction']['cost_risk_probability'] * 100, 1) . "%)\n";
            echo "  Time Risk: " . $data['data']['prediction']['time_risk_level'] . " (" . 
                 round($data['data']['prediction']['time_risk_probability'] * 100, 1) . "%)\n";
        }
        
        echo "\n✅ All systems operational!\n";
    } else {
        echo "❌ API Error: " . $data['message'] . "\n";
    }
} else {
    echo "❌ Invalid response:\n";
    echo substr($response, 0, 500) . "\n";
}
?>
