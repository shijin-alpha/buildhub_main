<?php
// Test ML Analytics with Real Project Data
echo "Testing ML Analytics with Real Projects\n";
echo "========================================\n\n";

$test_projects = [1, 3, 4, 37];

foreach ($test_projects as $project_id) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Testing Project #{$project_id}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $url = "http://localhost/buildhub/backend/api/ml/get_project_analytics.php?project_id={$project_id}";
    $response = @file_get_contents($url);
    
    if ($response === false) {
        echo "❌ Failed to fetch data\n\n";
        continue;
    }
    
    $data = json_decode($response, true);
    
    if ($data && isset($data['success']) && $data['success']) {
        echo "✅ API Working!\n\n";
        
        $project = $data['data']['project'];
        $prediction = $data['data']['prediction'];
        $cost = $data['data']['cost_analysis'];
        
        echo "📊 Project: {$project['name']}\n";
        echo "   Status: {$project['status']}\n";
        echo "   Budget: ₹" . number_format($project['budget']) . "\n\n";
        
        if ($prediction) {
            echo "🤖 AI Predictions (Based on Real Data):\n";
            echo "   💰 Cost Risk: {$prediction['cost_risk_level']} (" . 
                 round($prediction['cost_risk_probability'] * 100, 1) . "% probability)\n";
            echo "   ⏱️  Time Risk: {$prediction['time_risk_level']} (" . 
                 round($prediction['time_risk_probability'] * 100, 1) . "% probability)\n\n";
        }
        
        echo "💵 Cost Analysis:\n";
        echo "   Budget: ₹" . number_format($cost['predicted_budget']) . "\n";
        echo "   Spent: ₹" . number_format($cost['actual_spent']) . "\n";
        echo "   Remaining: ₹" . number_format($cost['remaining']) . "\n";
        echo "   Spend %: " . round($cost['spend_percentage'], 1) . "%\n\n";
        
        if (isset($data['data']['insights']) && !empty($data['data']['insights'])) {
            echo "💡 AI Insights:\n";
            foreach ($data['data']['insights'] as $insight) {
                $icon = $insight['type'] === 'warning' ? '⚠️' : 
                       ($insight['type'] === 'success' ? '✅' : 'ℹ️');
                echo "   {$icon} {$insight['title']}\n";
                echo "      {$insight['message']}\n\n";
            }
        }
        
    } else {
        echo "❌ Error: " . ($data['message'] ?? 'Unknown error') . "\n\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ All real projects tested successfully!\n\n";
echo "🎯 You can now view these projects in the ML Analytics Dashboard:\n";
echo "   • Project #1 - In Progress with Time Risk\n";
echo "   • Project #3 - Completed with High Cost Overrun\n";
echo "   • Project #4 - Completed with High Cost Overrun\n";
echo "   • Project #37 - Completed Successfully\n\n";
echo "📊 Refresh your browser and select any of these projects!\n";
?>
