<?php
/**
 * Update Contractor Inbox API to Include AI Predictions
 * 
 * This script modifies the get_inbox.php API to include AI predictions
 * from the layout_requests table so contractors can see risk assessments
 */

echo "=" . str_repeat("=", 79) . "\n";
echo "CONTRACTOR INBOX API - AI PREDICTIONS UPDATE\n";
echo "=" . str_repeat("=", 79) . "\n\n";

$api_file = 'backend/api/contractor/get_inbox.php';

if (!file_exists($api_file)) {
    echo "❌ Error: API file not found: $api_file\n";
    echo "Please ensure you're running this from the project root directory.\n";
    exit(1);
}

echo "📄 Reading current API file...\n";
$content = file_get_contents($api_file);

// Check if already updated
if (strpos($content, 'predicted_cost_risk_level') !== false) {
    echo "✅ API already includes AI predictions!\n";
    echo "No changes needed.\n";
    exit(0);
}

echo "🔧 Adding AI prediction fields to query...\n";

// Find the SELECT statement and add prediction fields
$select_pattern = '/(SELECT\s+cli\.\*,?\s*)/is';
$select_replacement = '$1
    lr.predicted_cost_risk_level,
    lr.predicted_cost_probability,
    lr.predicted_time_risk_level,
    lr.predicted_time_probability,
    lr.prediction_explanation,
    lr.model_version as prediction_model_version,
    ';

$content = preg_replace($select_pattern, $select_replacement, $content, 1);

// Find the FROM/JOIN section and ensure layout_requests is joined
// Look for existing layout_requests join or add it
if (strpos($content, 'LEFT JOIN layout_requests') === false) {
    echo "🔧 Adding layout_requests JOIN...\n";
    
    // Find the FROM contractor_inbox line and add join after it
    $from_pattern = '/(FROM\s+contractor_inbox\s+cli)/is';
    $from_replacement = '$1
    LEFT JOIN contractor_layout_sends cls ON cli.id = cls.id
    LEFT JOIN layout_requests lr ON cls.layout_id = lr.id';
    
    $content = preg_replace($from_pattern, $from_replacement, $content, 1);
}

// Add AI predictions to the response building
// Find where items are being built and add prediction data
$item_pattern = '/(\\$item\s*=\s*\[[\s\S]*?\];)/';

if (preg_match($item_pattern, $content, $matches)) {
    echo "🔧 Adding AI predictions to response...\n";
    
    $prediction_code = "
    
    // Add AI predictions if available
    \$item['ai_predictions'] = null;
    if (!empty(\$row['predicted_cost_risk_level']) || !empty(\$row['predicted_time_risk_level'])) {
        \$item['ai_predictions'] = [
            'cost_risk_level' => \$row['predicted_cost_risk_level'],
            'cost_probability' => !empty(\$row['predicted_cost_probability']) ? floatval(\$row['predicted_cost_probability']) : null,
            'time_risk_level' => \$row['predicted_time_risk_level'],
            'time_probability' => !empty(\$row['predicted_time_probability']) ? floatval(\$row['predicted_time_probability']) : null,
            'model_version' => \$row['prediction_model_version'],
            'explanation' => !empty(\$row['prediction_explanation']) ? json_decode(\$row['prediction_explanation'], true) : null
        ];
    }";
    
    // Insert after the $item array definition
    $content = preg_replace($item_pattern, '$1' . $prediction_code, $content, 1);
}

// Write the updated content
echo "💾 Writing updated API file...\n";
file_put_contents($api_file, $content);

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ SUCCESS! Contractor inbox API updated with AI predictions\n";
echo str_repeat("=", 80) . "\n\n";

echo "Next Steps:\n";
echo "1. Update ContractorDashboard.jsx to display AI predictions\n";
echo "2. See CONTRACTOR_AI_DISPLAY_INTEGRATION.md for frontend code\n";
echo "3. Test by submitting a homeowner request with AI predictions\n";
echo "4. Login as contractor and check inbox\n\n";

echo "The API will now return AI predictions in this format:\n";
echo "{\n";
echo "  \"ai_predictions\": {\n";
echo "    \"cost_risk_level\": \"High\",\n";
echo "    \"cost_probability\": 0.9550,\n";
echo "    \"time_risk_level\": \"Low\",\n";
echo "    \"time_probability\": 0.1520,\n";
echo "    \"model_version\": \"v1.0.0\",\n";
echo "    \"explanation\": {...}\n";
echo "  }\n";
echo "}\n\n";

?>
