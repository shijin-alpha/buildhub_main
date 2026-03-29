<?php
// Debug API responses for all real projects
echo "Debugging ML Analytics API Responses\n";
echo "=====================================\n\n";

$projects = [1, 3, 4, 37];

foreach ($projects as $project_id) {
    echo "Project #{$project_id}\n";
    echo str_repeat("-", 60) . "\n";
    
    $url = "http://localhost/buildhub/backend/api/ml/get_project_analytics.php?project_id={$project_id}";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        echo "✅ API Success\n\n";
        
        echo "Project Info:\n";
        echo "  Name: " . $data['data']['project']['name'] . "\n";
        echo "  Budget: ₹" . number_format($data['data']['project']['budget']) . "\n";
        echo "  Status: " . $data['data']['project']['status'] . "\n\n";
        
        echo "Prediction:\n";
        $pred = $data['data']['prediction'];
        echo "  Cost Risk: " . $pred['cost_risk_level'] . " (" . round($pred['cost_risk_probability'] * 100, 1) . "%)\n";
        echo "  Time Risk: " . $pred['time_risk_level'] . " (" . round($pred['time_risk_probability'] * 100, 1) . "%)\n";
        echo "  Probabilities: Low=" . round($pred['cost_risk_probabilities']['Low'] * 100, 1) . "%, ";
        echo "Medium=" . round($pred['cost_risk_probabilities']['Medium'] * 100, 1) . "%, ";
        echo "High=" . round($pred['cost_risk_probabilities']['High'] * 100, 1) . "%\n\n";
        
        echo "Cost Analysis:\n";
        $cost = $data['data']['cost_analysis'];
        echo "  Predicted Budget: ₹" . number_format($cost['predicted_budget']) . "\n";
        echo "  Actual Spent: ₹" . number_format($cost['actual_spent']) . "\n";
        echo "  Remaining: ₹" . number_format($cost['remaining']) . "\n";
        echo "  Spend %: " . round($cost['spend_percentage'], 1) . "%\n\n";
        
        echo "Time Analysis:\n";
        $time = $data['data']['time_analysis'];
        echo "  Current Progress: " . round($time['current_progress'], 1) . "%\n";
        echo "  Predicted Progress: " . round($time['predicted_progress'], 1) . "%\n";
        echo "  Days Elapsed: " . $time['days_elapsed'] . "\n";
        echo "  Timeline Points: " . count($time['timeline']) . "\n\n";
        
        echo "Model Performance:\n";
        if ($data['data']['model_performance']['cost_model']) {
            echo "  Cost Model Accuracy: " . $data['data']['model_performance']['cost_model']['accuracy'] . "%\n";
        }
        if ($data['data']['model_performance']['time_model']) {
            echo "  Time Model Accuracy: " . $data['data']['model_performance']['time_model']['accuracy'] . "%\n";
        }
        echo "  Overall Accuracy: " . round($data['data']['model_performance']['overall_accuracy'], 1) . "%\n\n";
        
        echo "Insights: " . count($data['data']['insights']) . " insights\n";
        foreach ($data['data']['insights'] as $insight) {
            echo "  - [{$insight['type']}] {$insight['title']}\n";
        }
        
    } else {
        echo "❌ Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
}

echo "✅ Debug complete!\n";
echo "\nIf all data looks correct above, the issue is browser caching.\n";
echo "Solution: Clear browser cache or use Ctrl+Shift+R (hard refresh)\n";
?>
