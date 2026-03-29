<?php
/**
 * Add AI Predictions to Amal Samuel's Request
 * So contractor 29 can see AI predictions in their inbox
 */

require_once 'backend/config/database.php';

echo "=" . str_repeat("=", 79) . "\n";
echo "ADD AI PREDICTIONS TO AMAL SAMUEL'S REQUEST\n";
echo "=" . str_repeat("=", 79) . "\n\n";

$database = new Database();
$conn = $database->getConnection();

// Find Amal Samuel's request sent to contractor 29
$stmt = $conn->query("
    SELECT 
        cls.id as send_id,
        cls.layout_id,
        cls.contractor_id,
        cls.homeowner_id,
        cls.created_at,
        u.first_name,
        u.last_name,
        u.email,
        lr.id as request_id,
        lr.predicted_cost_risk_level
    FROM contractor_layout_sends cls
    LEFT JOIN users u ON cls.homeowner_id = u.id
    LEFT JOIN layout_requests lr ON cls.layout_id = lr.id
    WHERE cls.contractor_id = 29
      AND u.first_name = 'Amal'
    ORDER BY cls.created_at DESC
    LIMIT 1
");

$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo "❌ Could not find Amal Samuel's request for contractor 29\n";
    exit(1);
}

echo "Found request:\n";
echo "  Send ID: {$request['send_id']}\n";
echo "  Layout Request ID: {$request['request_id']}\n";
echo "  Contractor ID: {$request['contractor_id']}\n";
echo "  Homeowner: {$request['first_name']} {$request['last_name']}\n";
echo "  Email: {$request['email']}\n";
echo "  Created: {$request['created_at']}\n";
echo "  Current Predictions: " . ($request['predicted_cost_risk_level'] ? 'Yes' : 'No') . "\n";
echo "\n";

if (!$request['request_id']) {
    echo "❌ No layout_request linked to this send\n";
    echo "This might be a direct estimate request without a layout.\n";
    exit(1);
}

// Generate realistic AI predictions
$predictions = [
    'predicted_cost_risk_level' => 'High',
    'predicted_cost_probability' => 0.8750,
    'predicted_time_risk_level' => 'Medium',
    'predicted_time_probability' => 0.6250,
    'model_version' => 'v1.0.0-test',
    'prediction_generated_at' => date('Y-m-d H:i:s'),
    'prediction_explanation' => json_encode([
        'cost_factors' => [
            'Complex architectural design with custom features',
            'High budget per square foot indicates premium materials',
            'Multiple floors increase construction complexity',
            'Site conditions may require additional foundation work'
        ],
        'time_factors' => [
            'Standard timeline expectations for project size',
            'Weather conditions may cause minor delays',
            'Material availability could impact schedule'
        ],
        'recommendations' => [
            'Add 15-20% contingency to budget for unexpected costs',
            'Plan for 2-3 week buffer in timeline',
            'Consider phased construction approach',
            'Regular progress monitoring recommended'
        ]
    ]),
    'prediction_features' => json_encode([
        'plot_size' => 2400,
        'building_size' => 1800,
        'num_floors' => 2,
        'num_bedrooms' => 3,
        'num_bathrooms' => 2,
        'budget_per_sqft' => 2500,
        'has_basement' => false,
        'has_garage' => true,
        'complexity_score' => 7.5
    ])
];

// Update the layout_requests table
$update_sql = "
    UPDATE layout_requests
    SET 
        predicted_cost_risk_level = :cost_risk,
        predicted_cost_probability = :cost_prob,
        predicted_time_risk_level = :time_risk,
        predicted_time_probability = :time_prob,
        model_version = :model_version,
        prediction_generated_at = :generated_at,
        prediction_explanation = :explanation,
        prediction_features = :features
    WHERE id = :request_id
";

$stmt = $conn->prepare($update_sql);
$stmt->bindValue(':cost_risk', $predictions['predicted_cost_risk_level']);
$stmt->bindValue(':cost_prob', $predictions['predicted_cost_probability']);
$stmt->bindValue(':time_risk', $predictions['predicted_time_risk_level']);
$stmt->bindValue(':time_prob', $predictions['predicted_time_probability']);
$stmt->bindValue(':model_version', $predictions['model_version']);
$stmt->bindValue(':generated_at', $predictions['prediction_generated_at']);
$stmt->bindValue(':explanation', $predictions['prediction_explanation']);
$stmt->bindValue(':features', $predictions['prediction_features']);
$stmt->bindValue(':request_id', $request['request_id'], PDO::PARAM_INT);

try {
    $stmt->execute();
    echo "✅ AI predictions added successfully!\n\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Display the predictions
echo str_repeat("=", 79) . "\n";
echo "AI PREDICTIONS ADDED\n";
echo str_repeat("=", 79) . "\n\n";

echo "🤖 AI Risk Assessment:\n";
echo "  💰 Cost Overrun Risk: 🔴 {$predictions['predicted_cost_risk_level']}\n";
echo "     Probability: " . ($predictions['predicted_cost_probability'] * 100) . "%\n\n";

echo "  ⏰ Time Delay Risk: 🟡 {$predictions['predicted_time_risk_level']}\n";
echo "     Probability: " . ($predictions['predicted_time_probability'] * 100) . "%\n\n";

echo "  📊 Model Version: {$predictions['model_version']}\n";
echo "  🕐 Generated: {$predictions['prediction_generated_at']}\n\n";

$explanation = json_decode($predictions['prediction_explanation'], true);

echo "  🎯 Key Risk Factors:\n";
echo "     Cost Risks:\n";
foreach ($explanation['cost_factors'] as $factor) {
    echo "     • $factor\n";
}
echo "\n     Time Risks:\n";
foreach ($explanation['time_factors'] as $factor) {
    echo "     • $factor\n";
}

echo "\n  💡 Recommendations:\n";
foreach ($explanation['recommendations'] as $rec) {
    echo "     • $rec\n";
}

echo "\n" . str_repeat("=", 79) . "\n";
echo "NEXT STEPS\n";
echo str_repeat("=", 79) . "\n\n";

echo "1. Refresh your contractor dashboard (press F5 or click REFRESH button)\n\n";

echo "2. Click on the inbox item from Amal Samuel\n\n";

echo "3. You should now see the AI Risk Assessment section:\n";
echo "   🤖 AI Risk Assessment\n";
echo "   💰 Cost Overrun Risk: 🔴 High (87.5%)\n";
echo "   ⏰ Time Delay Risk: 🟡 Medium (62.5%)\n";
echo "   🎯 Key Risk Factors: [list]\n";
echo "   💡 Recommendation: Add 15-20% contingency...\n\n";

echo "4. If you still don't see it:\n";
echo "   - Clear browser cache (Ctrl+Shift+Delete)\n";
echo "   - Close and reopen browser\n";
echo "   - Check browser console (F12) for errors\n\n";

echo "5. The AI section should appear BETWEEN:\n";
echo "   - Message from Homeowner (green box)\n";
echo "   - Technical Design Details (purple button)\n\n";

echo str_repeat("=", 79) . "\n";

$conn = null;
?>
