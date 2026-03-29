<?php
/**
 * Trigger AI Evaluation API (PDO)
 * Admin-only: manually trigger evaluation for completed projects.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../../config/database.php';
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin role required to trigger evaluations']);
    exit();
}

$input      = json_decode(file_get_contents('php://input'), true);
$project_id = isset($input['project_id']) ? intval($input['project_id']) : null;
$force      = isset($input['force']) ? filter_var($input['force'], FILTER_VALIDATE_BOOLEAN) : false;

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// ── Guard: check stored procedure exists ────────────────────────────────────
$procCheck = $pdo->prepare(
    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.ROUTINES
     WHERE ROUTINE_SCHEMA = DATABASE()
       AND ROUTINE_TYPE   = 'PROCEDURE'
       AND ROUTINE_NAME   = 'evaluate_project_predictions'"
);
$procCheck->execute();
if ((int)$procCheck->fetchColumn() === 0) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Evaluation procedure not installed'
    ]);
    exit();
}

// ── Guard: required tables / views ──────────────────────────────────────────
$requiredTables = ['ai_evaluation_metrics', 'ai_evaluation_config'];
$requiredViews  = ['v_latest_ai_metrics', 'v_project_evaluation_summary'];

foreach ($requiredTables as $tbl) {
    $chk = $pdo->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );
    $chk->execute([$tbl]);
    if ((int)$chk->fetchColumn() === 0) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Required table '$tbl' does not exist"]);
        exit();
    }
}

foreach ($requiredViews as $view) {
    $chk = $pdo->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.VIEWS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );
    $chk->execute([$view]);
    if ((int)$chk->fetchColumn() === 0) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Required view '$view' does not exist"]);
        exit();
    }
}

// ── Evaluation ───────────────────────────────────────────────────────────────
$evaluated = [];
$skipped   = [];
$errors    = [];

if ($project_id) {
    $result = evaluateProject($pdo, $project_id, $force);
    if ($result['success']) { $evaluated[] = $result['data']; }
    else                    { $errors[]    = $result['error']; }
} else {
    $q = "SELECT id, project_name FROM construction_projects
          WHERE status = 'completed'
            AND (predicted_cost_risk_level IS NOT NULL OR predicted_time_risk_level IS NOT NULL)";
    if (!$force) { $q .= " AND evaluation_completed_at IS NULL"; }
    $q .= " ORDER BY id";

    $rows = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $r = evaluateProject($pdo, $row['id'], $force);
        if ($r['success']) { $evaluated[] = $r['data']; }
        else               { $skipped[]   = ['project_id' => $row['id'], 'project_name' => $row['project_name'], 'reason' => $r['error']]; }
    }
}

echo json_encode([
    'status' => 'success',
    'data'   => [
        'evaluated_count'    => count($evaluated),
        'skipped_count'      => count($skipped),
        'error_count'        => count($errors),
        'evaluated_projects' => $evaluated,
        'skipped_projects'   => $skipped,
        'errors'             => $errors,
    ],
    'metadata' => [
        'triggered_by' => $_SESSION['user_id'],
        'triggered_at' => date('Y-m-d H:i:s'),
        'force_mode'   => $force,
    ]
]);

// ── Helper ───────────────────────────────────────────────────────────────────
function evaluateProject(PDO $pdo, int $project_id, bool $force): array {
    $stmt = $pdo->prepare(
        "SELECT id, project_name, status,
                predicted_cost_risk_level, predicted_time_risk_level,
                evaluation_completed_at
         FROM construction_projects WHERE id = ?"
    );
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        return ['success' => false, 'error' => "Project $project_id not found"];
    }
    if (!$force && $project['evaluation_completed_at']) {
        return ['success' => false, 'error' => "Already evaluated at " . $project['evaluation_completed_at']];
    }
    if (!$project['predicted_cost_risk_level'] && !$project['predicted_time_risk_level']) {
        return ['success' => false, 'error' => "Project has no AI predictions to evaluate"];
    }
    if ($project['status'] !== 'completed') {
        return ['success' => false, 'error' => "Project is not completed (status: {$project['status']})"];
    }

    // Clear previous evaluation in force mode
    if ($force && $project['evaluation_completed_at']) {
        $pdo->prepare(
            "UPDATE construction_projects
             SET evaluation_completed_at = NULL,
                 cost_ground_truth_label = NULL, time_ground_truth_label = NULL,
                 cost_prediction_classification = NULL, time_prediction_classification = NULL,
                 cost_prediction_correct = NULL, time_prediction_correct = NULL
             WHERE id = ?"
        )->execute([$project_id]);
    }

    // Call stored procedure
    $stmt = $pdo->prepare("CALL evaluate_project_predictions(?)");
    if (!$stmt->execute([$project_id])) {
        return ['success' => false, 'error' => "Evaluation procedure failed for project $project_id"];
    }
    // Close any extra result sets PDO may buffer
    while ($stmt->nextRowset()) {}

    // Fetch results
    $stmt = $pdo->prepare(
        "SELECT id, project_name,
                predicted_cost_risk_level, cost_ground_truth_label,
                cost_prediction_classification, cost_prediction_correct,
                predicted_time_risk_level, time_ground_truth_label,
                time_prediction_classification, time_prediction_correct,
                actual_cost_overrun_percentage, actual_time_overrun_percentage,
                evaluation_completed_at
         FROM construction_projects WHERE id = ?"
    );
    $stmt->execute([$project_id]);
    $ev = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'success' => true,
        'data'    => [
            'project_id'   => intval($ev['id']),
            'project_name' => $ev['project_name'],
            'cost_evaluation' => [
                'predicted'        => $ev['predicted_cost_risk_level'],
                'actual'           => $ev['cost_ground_truth_label'],
                'classification'   => $ev['cost_prediction_classification'],
                'correct'          => (bool)$ev['cost_prediction_correct'],
                'actual_overrun_pct' => floatval($ev['actual_cost_overrun_percentage']),
            ],
            'time_evaluation' => [
                'predicted'        => $ev['predicted_time_risk_level'],
                'actual'           => $ev['time_ground_truth_label'],
                'classification'   => $ev['time_prediction_classification'],
                'correct'          => (bool)$ev['time_prediction_correct'],
                'actual_overrun_pct' => floatval($ev['actual_time_overrun_percentage']),
            ],
            'evaluated_at' => $ev['evaluation_completed_at'],
        ]
    ];
}
?>
