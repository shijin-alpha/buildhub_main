<?php
// Test Overrun API
$url = 'http://localhost/buildhub/backend/api/contractor/get_completed_project_overruns.php?contractor_id=29&project_id=37';
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "Overrun API Test\n";
echo "================\n\n";

if ($data && isset($data['success'])) {
    if ($data['success']) {
        echo "✅ API Working!\n\n";
        echo "Project: " . $data['data']['project_info']['project_name'] . "\n";
        echo "Status: " . $data['data']['project_info']['status'] . "\n";
        echo "Completion: " . $data['data']['project_info']['completion_percentage'] . "%\n\n";
        
        echo "Cost Analysis:\n";
        echo "  Original: ₹" . number_format($data['data']['cost_overrun']['original_estimate']) . "\n";
        echo "  Actual: ₹" . number_format($data['data']['cost_overrun']['total_project_cost']) . "\n";
        echo "  Difference: ₹" . number_format($data['data']['cost_overrun']['cost_difference']) . "\n";
        echo "  Status: " . $data['data']['cost_overrun']['status_indicator'] . "\n\n";
        
        echo "✅ All systems operational!\n";
    } else {
        echo "❌ API Error: " . $data['message'] . "\n";
    }
} else {
    echo "❌ Invalid response:\n";
    echo substr($response, 0, 500) . "\n";
}
?>
