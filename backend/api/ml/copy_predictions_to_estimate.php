<?php
/**
 * Copy AI Predictions from Layout Request to Contractor Estimate (PDO)
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

if (!isset($input['estimate_id']) || !isset($input['layout_request_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: estimate_id and layout_request_id']);
    exit();
}

$estimate_id       = intval($input['estimate_id']);
$layout_request_id = intval($input['layout_request_id']);

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Get predictions from layout_requests using ai_prediction_date
    $stmt = $pdo->prepare(
        "SELECT predicted_cost_risk_level,
                predicted_cost_probability,
                predicted_time_risk_level,
                predicted_time_probability,
                ai_prediction_date,
                model_version,
                prediction_features,
                prediction_explanation
         FROM layout_requests
         WHERE id = ?"
    );
    $stmt->execute([$layout_request_id]);
    $predictions = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$predictions) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Layout request not found']);
        exit();
    }

    if ($predictions['predicted_cost_risk_level'] === null &&
        $predictions['predicted_time_risk_level'] === null) {
        echo json_encode([
            'status'              => 'success',
            'data'                => ['predictions_copied' => false],
            'message'             => 'No predictions to copy — layout request has no predictions',
        ]);
        exit();
    }

    // Copy predictions to contractor_send_estimates using ai_prediction_date
    $stmt = $pdo->prepare(
        "UPDATE contractor_send_estimates
         SET predicted_cost_risk_level  = :cl,
             predicted_cost_probability = :cp,
             predicted_time_risk_level  = :tl,
             predicted_time_probability = :tp,
             ai_prediction_date         = :apd,
             model_version              = :mv,
             prediction_features        = :pf,
             prediction_explanation     = :pe,
             layout_request_id          = :lrid
         WHERE id = :eid"
    );
    $stmt->execute([
        ':cl'   => $predictions['predicted_cost_risk_level'],
        ':cp'   => $predictions['predicted_cost_probability'],
        ':tl'   => $predictions['predicted_time_risk_level'],
        ':tp'   => $predictions['predicted_time_probability'],
        ':apd'  => $predictions['ai_prediction_date'],
        ':mv'   => $predictions['model_version'],
        ':pf'   => $predictions['prediction_features'],
        ':pe'   => $predictions['prediction_explanation'],
        ':lrid' => $layout_request_id,
        ':eid'  => $estimate_id,
    ]);

    echo json_encode([
        'status'  => 'success',
        'data'    => [
            'estimate_id'        => $estimate_id,
            'layout_request_id'  => $layout_request_id,
            'cost_risk_level'    => $predictions['predicted_cost_risk_level'],
            'time_risk_level'    => $predictions['predicted_time_risk_level'],
            'model_version'      => $predictions['model_version'],
            'predictions_copied' => true,
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
