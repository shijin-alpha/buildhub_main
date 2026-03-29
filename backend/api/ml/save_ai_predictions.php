<?php
/**
 * Save AI Predictions API
 * Stores AI risk predictions on the construction_projects table (PDO)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
session_start();

// Auth check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

$user_id  = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Input validation
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['project_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required field: project_id']);
    exit();
}

$project_id       = intval($input['project_id']);
$cost_risk_level  = $input['cost_risk_level']  ?? null;
$cost_probability = isset($input['cost_probability'])  ? floatval($input['cost_probability'])  : null;
$time_risk_level  = $input['time_risk_level']  ?? null;
$time_probability = isset($input['time_probability'])  ? floatval($input['time_probability'])  : null;
$model_version    = $input['model_version']    ?? 'v1.0.0';

$valid_risk_levels = ['Low', 'Medium', 'High'];
if ($cost_risk_level && !in_array($cost_risk_level, $valid_risk_levels)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid cost_risk_level']);
    exit();
}
if ($time_risk_level && !in_array($time_risk_level, $valid_risk_levels)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid time_risk_level']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Verify project exists and check ownership / lock status
    $stmt = $conn->prepare(
        "SELECT id, homeowner_id, status, predictions_locked, predicted_cost_risk_level
         FROM construction_projects WHERE id = ?"
    );
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Project not found']);
        exit();
    }

    // Only homeowner who owns the project or admin can save predictions
    if ($user_role !== 'admin' && intval($project['homeowner_id']) !== intval($user_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit();
    }

    // Predictions locked once work has started
    if ($project['predictions_locked']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error'   => 'Predictions are locked — work has already begun on this project'
        ]);
        exit();
    }

    $has_existing = !empty($project['predicted_cost_risk_level']);

    // Save predictions directly to construction_projects columns
    $update = $conn->prepare(
        "UPDATE construction_projects SET
            predicted_cost_risk_level  = :cost_level,
            predicted_cost_probability = :cost_prob,
            predicted_time_risk_level  = :time_level,
            predicted_time_probability = :time_prob,
            ai_model_version           = :model_ver,
            ai_prediction_date         = NOW()
         WHERE id = :project_id"
    );
    $update->execute([
        ':cost_level'  => $cost_risk_level,
        ':cost_prob'   => $cost_probability,
        ':time_level'  => $time_risk_level,
        ':time_prob'   => $time_probability,
        ':model_ver'   => $model_version,
        ':project_id'  => $project_id,
    ]);

    if ($update->rowCount() === 0) {
        throw new Exception('No rows updated — project may not exist');
    }

    // Audit log (best-effort — skip if table doesn't exist)
    try {
        $audit = $conn->prepare(
            "INSERT INTO ai_prediction_audit_log
                (project_id, user_id, action, cost_risk_level, time_risk_level, model_version, created_at)
             VALUES (?, ?, 'prediction_saved', ?, ?, ?, NOW())"
        );
        $audit->execute([$project_id, $user_id, $cost_risk_level, $time_risk_level, $model_version]);
    } catch (Exception $e) {
        // Audit table may not exist — non-fatal
    }

    echo json_encode([
        'success' => true,
        'message' => 'AI predictions saved successfully',
        'data'    => [
            'project_id'          => $project_id,
            'cost_risk_level'     => $cost_risk_level,
            'cost_probability'    => $cost_probability,
            'time_risk_level'     => $time_risk_level,
            'time_probability'    => $time_probability,
            'model_version'       => $model_version,
            'prediction_saved_at' => date('Y-m-d H:i:s'),
            'predictions_locked'  => false,
            'was_updated'         => $has_existing,
        ],
        'info' => [
            'immutable'  => 'Predictions will be locked when project starts',
            'evaluation' => 'Predictions will be evaluated automatically when project completes',
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
