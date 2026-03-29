<?php
require_once 'backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

$contractorId = 51;

echo "Testing inbox for contractor_id = $contractorId\n";
echo str_repeat("=", 79) . "\n\n";

$stmt = $db->prepare("
    SELECT s.id, s.contractor_id, s.homeowner_id, s.layout_id,
           s.message, s.created_at,
           CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS homeowner_name,
           lr.predicted_cost_risk_level,
           lr.predicted_cost_probability,
           lr.predicted_time_risk_level,
           lr.predicted_time_probability,
           lr.prediction_explanation,
           lr.model_version
    FROM contractor_layout_sends s
    LEFT JOIN users u ON u.id = s.homeowner_id
    LEFT JOIN layout_requests lr ON s.layout_id = lr.id
    WHERE s.contractor_id = :cid
    ORDER BY s.created_at DESC
");

$stmt->bindValue(':cid', $contractorId, PDO::PARAM_INT);
$stmt->execute();

$items = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $items[] = $row;
}

echo "Found " . count($items) . " inbox items:\n\n";

foreach ($items as $i => $item) {
    echo ($i + 1) . ". From: {$item['homeowner_name']}\n";
    echo "   Layout ID: {$item['layout_id']}\n";
    echo "   Created: {$item['created_at']}\n";
    
    if ($item['predicted_cost_risk_level']) {
        echo "   🤖 AI PREDICTIONS FOUND!\n";
        echo "      💰 Cost Risk: {$item['predicted_cost_risk_level']} (" . ($item['predicted_cost_probability'] * 100) . "%)\n";
        echo "      ⏰ Time Risk: {$item['predicted_time_risk_level']} (" . ($item['predicted_time_probability'] * 100) . "%)\n";
        echo "      📊 Model: {$item['model_version']}\n";
        
        if ($item['prediction_explanation']) {
            $explanation = json_decode($item['prediction_explanation'], true);
            if ($explanation && isset($explanation['cost_factors'])) {
                echo "      🎯 Cost Factors:\n";
                foreach (array_slice($explanation['cost_factors'], 0, 2) as $factor) {
                    echo "         • $factor\n";
                }
            }
        }
    } else {
        echo "   ℹ️  No AI predictions\n";
    }
    
    echo "\n";
}

if (count($items) > 0 && $items[0]['predicted_cost_risk_level']) {
    echo str_repeat("=", 79) . "\n";
    echo "✅ SUCCESS! AI predictions are in the database!\n";
    echo str_repeat("=", 79) . "\n\n";
    
    echo "Now rebuild the frontend to see them:\n";
    echo "  cd frontend\n";
    echo "  npm run build\n\n";
    
    echo "Then login as contractor ID $contractorId and check inbox.\n";
}

$db = null;
?>
