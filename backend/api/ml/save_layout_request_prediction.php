<?php
/**
 * Save AI Prediction to Layout Request (PDO)
 * Primary prediction storage endpoint — called during homeowner request stage.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use POST.']);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

$required = ['layout_request_id', 'cost_risk_level', 'cost_probability', 'time_risk_level', 'time_probability'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
        exit();
    }
}

$layout_request_id = intval($input['layout_request_id']);
$cost_risk_level   = $input['cost_risk_level'];
$cost_probability  = floatval($input['cost_probability']);
$time_risk_level   = $input['time_risk_level'];
$time_probability  = floatval($input['time_probability']);
$model_version     = $input['model_version'] ?? 'v1.0.0';
$features          = isset($input['features'])    ? json_encode($input['features'])    : null;
$explanation       = isset($input['explanation']) ? json_encode($input['explanation']) : null;

$valid = ['Low', 'Medium', 'High'];
if (!in_array($cost_risk_level, $valid) || !in_array($time_risk_level, $valid)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid risk level. Must be Low, Medium, or High.']);
    exit();
}

if ($cost_probability < 0 || $cost_probability > 1 || $time_probability < 0 || $time_probability > 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Probabilities must be between 0 and 1.']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Verify layout request exists
    $stmt = $pdo->prepare("SELECT id FROM layout_requests WHERE id = ?");
    $stmt->execute([$layout_request_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Layout request not found']);
        exit();
    }

    // Save predictions using ai_prediction_date
    $stmt = $pdo->prepare(
        "UPDATE layout_requests
         SET predicted_cost_risk_level  = :cl,
             predicted_cost_probability = :cp,
             predicted_time_risk_level  = :tl,
             predicted_time_probability = :tp,
             ai_prediction_date         = NOW(),
             model_version              = :mv,
             prediction_features        = :pf,
             prediction_explanation     = :pe
         WHERE id = :id"
    );
    $stmt->execute([
        ':cl' => $cost_risk_level,
        ':cp' => $cost_probability,
        ':tl' => $time_risk_level,
        ':tp' => $time_probability,
        ':mv' => $model_version,
        ':pf' => $features,
        ':pe' => $explanation,
        ':id' => $layout_request_id,
    ]);

    echo json_encode([
        'status' => 'success',
        'data'   => [
            'layout_request_id' => $layout_request_id,
            'cost_risk_level'   => $cost_risk_level,
            'cost_probability'  => $cost_probability,
            'time_risk_level'   => $time_risk_level,
            'time_probability'  => $time_probability,
            'model_version'     => $model_version,
            'saved_at'          => date('Y-m-d H:i:s'),
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
