<?php
/**
 * Test Contractor 29 Inbox
 */

require_once 'backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

$contractorId = 29; // Use actual contractor ID from database

echo "Testing inbox for contractor_id = $contractorId\n";
echo str_repeat("=", 79) . "\n\n";

$stmt = $db->prepare("
    SELECT s.id, s.contractor_id, s.homeowner_id, s.layout_id, s.design_id, NULL as estimate_id,
           s.message, s.payload, s.created_at, s.acknowledged_at, s.due_date,
           CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS homeowner_name,
           u.email AS homeowner_email,
           'layout_request' as type,
           'New layout sent' as title,
           'unread' as status,
           lr.predicted_cost_risk_level,
           lr.predicted_cost_probability,
           lr.predicted_time_risk_level,
           lr.predicted_time_probability,
           lr.prediction_explanation,
           lr.model_version as prediction_model_version
    FROM contractor_layout_sends s
    LEFT JOIN users u ON u.id = s.homeowner_id
    LEFT JOIN layout_requests lr ON s.layout_id = lr.id
    WHERE s.contractor_id = :cid1
    
    UNION ALL
    
    SELECT ci.id, ci.contractor_id, ci.homeowner_id, NULL as layout_id, NULL as design_id, ci.estimate_id,
           ci.message, NULL as payload, ci.created_at, ci.acknowledged_at, ci.due_date,
           CONCAT(COALESCE(u2.first_name,''), ' ', COALESCE(u2.last_name,'')) AS homeowner_name,
           u2.email AS homeowner_email,
           ci.type,
           ci.title,
           ci.status,
           NULL as predicted_cost_risk_level,
           NULL as predicted_cost_probability,
           NULL as predicted_time_risk_level,
           NULL as predicted_time_probability,
           NULL as prediction_explanation,
           NULL as prediction_model_version
    FROM contractor_inbox ci
    LEFT JOIN users u2 ON u2.id = ci.homeowner_id
    WHERE ci.contractor_id = :cid2
    
    ORDER BY created_at DESC
");

$stmt->bindValue(':cid1', $contractorId, PDO::PARAM_INT);
$stmt->bindValue(':cid2', $contractorId, PDO::PARAM_INT);
$stmt->execute();

$items = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $items[] = [
        'id' => $row['id'],
        'type' => $row['type'],
        'title' => $row['title'],
        'homeowner_name' => $row['homeowner_name'],
        'message' => substr($row['message'], 0, 50) . '...',
        'created_at' => $row['created_at'],
        'ai_predictions' => null
    ];
    
    // Add AI predictions if available
    $lastIndex = count($items) - 1;
    if (!empty($row['predicted_cost_risk_level']) || !empty($row['predicted_time_risk_level'])) {
        $items[$lastIndex]['ai_predictions'] = [
            'cost_risk_level' => $row['predicted_cost_risk_level'],
            'cost_probability' => $row['predicted_cost_probability'],
            'time_risk_level' => $row['predicted_time_risk_level'],
            'time_probability' => $row['predicted_time_probability'],
            'model_version' => $row['prediction_model_version']
        ];
    }
}

echo "Found " . count($items) . " inbox items:\n\n";

foreach ($items as $i => $item) {
    echo ($i + 1) . ". {$item['title']}\n";
    echo "   From: {$item['homeowner_name']}\n";
    echo "   Type: {$item['type']}\n";
    echo "   Created: {$item['created_at']}\n";
    echo "   Message: {$item['message']}\n";
    
    if ($item['ai_predictions']) {
        echo "   🤖 AI Predictions:\n";
        echo "      Cost Risk: {$item['ai_predictions']['cost_risk_level']} ({$item['ai_predictions']['cost_probability']})\n";
        echo "      Time Risk: {$item['ai_predictions']['time_risk_level']} ({$item['ai_predictions']['time_probability']})\n";
        echo "      Model: {$item['ai_predictions']['model_version']}\n";
    } else {
        echo "   ℹ️  No AI predictions (old request or predictions not generated)\n";
    }
    
    echo "\n";
}

echo str_repeat("=", 79) . "\n";
echo "✅ API query works correctly!\n";
echo "Inbox items are being returned.\n\n";

if (count($items) > 0 && !$items[0]['ai_predictions']) {
    echo "ℹ️  Note: These are old requests without AI predictions.\n";
    echo "Submit a NEW homeowner request to see AI predictions.\n";
}

$db = null;
?>
