<?php
/**
 * Add AI predictions to contractor_layout_sends payload
 * For requests without layout_id
 */

require_once 'backend/config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Find Amal Samuel's request
$stmt = $conn->query("
    SELECT id, payload
    FROM contractor_layout_sends
    WHERE contractor_id = 29
    ORDER BY created_at DESC
    LIMIT 1
");

$send = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$send) {
    echo "No request found\n";
    exit(1);
}

echo "Found request ID: {$send['id']}\n";

// Decode existing payload
$payload = json_decode($send['payload'], true) ?: [];

// Add AI predictions to payload
$payload['ai_predictions'] = [
    'cost_risk_level' => 'High',
    'cost_probability' => 0.8750,
    'time_risk_level' => 'Medium',
    'time_probability' => 0.6250,
    'model_version' => 'v1.0.0-test',
    'explanation' => [
        'cost_factors' => [
            'Complex architectural design with custom features',
            'High budget per square foot indicates premium materials',
            'Multiple floors increase construction complexity'
        ],
        'time_factors' => [
            'Standard timeline expectations for project size',
            'Weather conditions may cause minor delays'
        ]
    ]
];

// Update the record
$update = $conn->prepare("UPDATE contractor_layout_sends SET payload = :payload WHERE id = :id");
$update->execute([
    ':payload' => json_encode($payload),
    ':id' => $send['id']
]);

echo "✅ AI predictions added to payload!\n\n";
echo "Now refresh your contractor dashboard (F5)\n";
echo "You should see the AI Risk Assessment section!\n";
?>
