<?php
/**
 * Add Test AI Predictions to Existing Layout Request
 * This allows you to see how AI predictions look in contractor dashboard
 * without submitting a new request
 */

require_once 'backend/config/database.php';

echo "=" . str_repeat("=", 79) . "\n";
echo "ADD TEST AI PREDICTIONS TO EXISTING REQUEST\n";
echo "=" . str_repeat("=", 79) . "\n\n";

$database = new Database();
$conn = $database->getConnection();

// Find the most recent layout request that's been sent to a contractor
$stmt = $conn->query("
    SELECT 
        cls.id as send_id,
        cls.layout_id,
        cls.contractor_id,
        cls.homeowner_id,
        lr.id as request_id,
        lr.predicted_cost_risk_level,
        u.first_name,
        u.last_name
    FROM contractor_layout_sends cls
    LEFT JOIN layout_requests lr ON cls.layout_id = lr.id
    LEFT JOIN users u ON cls.homeowner_id = u.id
    WHERE cls.layout_id IS NOT NULL
    ORDER BY cls.created_at DESC
    LIMIT 1
");

$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo "❌ No layout requests found in contractor inbox\n";
    echo "Please submit a homeowner request first.\n";
    exit(1);
}

echo "Found request:\n";
echo "  Layout Request ID: {$request['request_id']}\n";
echo "  Sent to Contractor ID: {$request['contractor_id']}\n";
echo "  From Homeowner: {$request['first_name']} {$request['last_name']}\n";
echo "  Current AI Predictions: " . ($request['predicted_cost_risk_level'] ? 'Yes' : 'No') . "\n";
echo "\n";

if ($request['predicted_cost_risk_level']) {
    echo "ℹ️  This request already has AI predictions.\n";
    echo "Updating with new test predictions...\n\n";
}

// Generate realistic test predictions
$test_predictions = [
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
$stmt->bindValue(':cost_risk', $test_predictions['predicted_cost_risk_level']);
$stmt->bindValue(':cost_prob', $test_predictions['predicted_cost_probability']);
$stmt->bindValue(':time_risk', $test_predictions['predicted_time_risk_level']);
$stmt->bindValue(':time_prob', $test_predictions['predicted_time_probability']);
$stmt->bindValue(':model_version', $test_predictions['model_version']);
$stmt->bindValue(':generated_at', $test_predictions['prediction_generated_at']);
$stmt->bindValue(':explanation', $test_predictions['prediction_explanation']);
$stmt->bindValue(':features', $test_predictions['prediction_features']);
$stmt->bindValue(':request_id', $request['request_id'], PDO::PARAM_INT);

try {
    $stmt->execute();
    echo "✅ Test AI predictions added successfully!\n\n";
} catch (Exception $e) {
    echo "❌ Error adding predictions: " . $e->getMessage() . "\n";
    exit(1);
}

// Display the predictions
echo str_repeat("=", 79) . "\n";
echo "TEST PREDICTIONS ADDED\n";
echo str_repeat("=", 79) . "\n\n";

echo "🤖 AI Risk Assessment:\n";
echo "  💰 Cost Overrun Risk: 🔴 {$test_predictions['predicted_cost_risk_level']}\n";
echo "     Probability: " . ($test_predictions['predicted_cost_probability'] * 100) . "%\n\n";

echo "  ⏰ Time Delay Risk: 🟡 {$test_predictions['predicted_time_risk_level']}\n";
echo "     Probability: " . ($test_predictions['predicted_time_probability'] * 100) . "%\n\n";

echo "  📊 Model Version: {$test_predictions['model_version']}\n";
echo "  🕐 Generated: {$test_predictions['prediction_generated_at']}\n\n";

$explanation = json_decode($test_predictions['prediction_explanation'], true);

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

echo "1. Rebuild frontend:\n";
echo "   cd frontend\n";
echo "   npm run build\n\n";

echo "2. Login as contractor (ID: {$request['contractor_id']})\n\n";

echo "3. Go to contractor dashboard inbox\n\n";

echo "4. You should now see the AI Risk Assessment section with:\n";
echo "   🤖 AI Risk Assessment\n";
echo "   💰 Cost Overrun Risk: 🔴 High (87.5%)\n";
echo "   ⏰ Time Delay Risk: 🟡 Medium (62.5%)\n";
echo "   🎯 Key Risk Factors: [list]\n";
echo "   💡 Recommendation: Add 15-20% contingency...\n\n";

echo "5. If you don't see it:\n";
echo "   - Clear browser cache (Ctrl+Shift+Delete)\n";
echo "   - Check browser console for errors (F12)\n";
echo "   - Verify frontend was rebuilt\n\n";

echo str_repeat("=", 79) . "\n";

$conn = null;
?>
