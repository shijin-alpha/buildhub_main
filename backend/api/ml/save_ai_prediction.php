<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

foreach (['project_id','cost_risk_level','cost_probability','time_risk_level','time_probability'] as $f) {
    if (!isset($input[$f])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing: $f"]);
        exit();
    }
}

$project_id       = intval($input['project_id']);
$cost_risk_level  = $input['cost_risk_level'];
$cost_probability = floatval($input['cost_probability']);
$time_risk_level  = $input['time_risk_level'];
$time_probability = floatval($input['time_probability']);
$model_version    = $input['model_version'] ?? 'v1.0.0';

$valid = ['Low', 'Medium', 'High'];
if (!in_array($cost_risk_level, $valid) || !in_array($time_risk_level, $valid)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid risk level']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("SELECT id, predictions_locked FROM construction_projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Project not found']);
        exit();
    }

    if ($project['predictions_locked']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Predictions locked — work already started']);
        exit();
    }

    $upd = $conn->prepare(
        "UPDATE construction_projects SET
            predicted_cost_risk_level  = :cl,
            predicted_cost_probability = :cp,
            predicted_time_risk_level  = :tl,
            predicted_time_probability = :tp,
            ai_model_version           = :mv,
            ai_prediction_date         = NOW()
         WHERE id = :pid"
    );
    $upd->execute([':cl'=>$cost_risk_level,':cp'=>$cost_probability,
                   ':tl'=>$time_risk_level,':tp'=>$time_probability,
                   ':mv'=>$model_version,':pid'=>$project_id]);

    echo json_encode([
        'success' => true,
        'message' => 'AI prediction saved successfully',
        'data' => [
            'project_id'       => $project_id,
            'cost_risk_level'  => $cost_risk_level,
            'cost_probability' => $cost_probability,
            'time_risk_level'  => $time_risk_level,
            'time_probability' => $time_probability,
            'model_version'    => $model_version,
            'saved_at'         => date('Y-m-d H:i:s'),
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
