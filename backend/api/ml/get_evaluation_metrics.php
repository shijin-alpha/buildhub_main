<?php
/**
 * Get AI Evaluation Metrics API (PDO)
 * Returns confusion matrix, accuracy, precision_score, recall_score, F1.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../../config/database.php';
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit();
}

if (!in_array($_SESSION['role'], ['admin', 'contractor'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Insufficient permissions']);
    exit();
}

$metric_type      = $_GET['metric_type']      ?? 'both';
$date_from        = $_GET['date_from']        ?? null;
$date_to          = $_GET['date_to']          ?? null;
$include_breakdown = isset($_GET['include_breakdown'])
    ? filter_var($_GET['include_breakdown'], FILTER_VALIDATE_BOOLEAN) : true;

if (!in_array($metric_type, ['cost', 'time', 'both'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid metric_type. Must be cost, time, or both']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// ── Guard: required objects ──────────────────────────────────────────────────
foreach (['ai_evaluation_metrics', 'ai_evaluation_config'] as $tbl) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $chk->execute([$tbl]);
    if ((int)$chk->fetchColumn() === 0) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Required table '$tbl' does not exist"]);
        exit();
    }
}

foreach (['v_latest_ai_metrics', 'v_project_evaluation_summary'] as $view) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $chk->execute([$view]);
    if ((int)$chk->fetchColumn() === 0) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Required view '$view' does not exist"]);
        exit();
    }
}

// ── Latest metrics from view ─────────────────────────────────────────────────
$sql    = "SELECT * FROM v_latest_ai_metrics WHERE 1=1";
$params = [];
if ($metric_type !== 'both') { $sql .= " AND metric_type = ?"; $params[] = $metric_type; }
if ($date_from)              { $sql .= " AND evaluation_date >= ?"; $params[] = $date_from; }
if ($date_to)                { $sql .= " AND evaluation_date <= ?"; $params[] = $date_to; }
$sql .= " ORDER BY metric_type, evaluation_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$metrics = [];
foreach ($rows as $row) {
    $metrics[] = [
        'metric_type'     => $row['metric_type'],
        'evaluation_date' => $row['evaluation_date'],
        'total_projects'  => intval($row['total_projects']),
        'confusion_matrix' => [
            'true_positives'  => intval($row['true_positives']),
            'false_positives' => intval($row['false_positives']),
            'true_negatives'  => intval($row['true_negatives']),
            'false_negatives' => intval($row['false_negatives']),
        ],
        'performance_metrics' => [
            'accuracy'           => floatval($row['accuracy']),
            'precision'          => floatval($row['precision_score']),   // correct column name
            'recall'             => floatval($row['recall_score']),      // correct column name
            'f1_score'           => floatval($row['f1_score']),
            'specificity'        => floatval($row['specificity']),
            'false_positive_rate'=> floatval($row['false_positive_rate']),
        ],
        'created_at' => $row['created_at'],
    ];
}

$response = [
    'status'   => 'success',
    'data'     => ['metrics' => $metrics],
    'metadata' => ['generated_at' => date('Y-m-d H:i:s'), 'metric_type' => $metric_type],
];

// ── Confusion matrix breakdown ───────────────────────────────────────────────
if ($include_breakdown) {
    $bsql    = "SELECT * FROM v_confusion_matrix_breakdown WHERE 1=1";
    $bparams = [];
    if ($metric_type !== 'both') { $bsql .= " AND metric_type = ?"; $bparams[] = $metric_type; }
    $bsql .= " ORDER BY metric_type, classification";

    $bstmt = $pdo->prepare($bsql);
    $bstmt->execute($bparams);
    $brows = $bstmt->fetchAll(PDO::FETCH_ASSOC);

    $breakdown = [];
    foreach ($brows as $row) {
        $breakdown[$row['metric_type']][] = [
            'classification' => $row['classification'],
            'count'          => intval($row['count']),
            'percentage'     => floatval($row['percentage']),
        ];
    }
    $response['data']['breakdown'] = $breakdown;
}

// ── Config ───────────────────────────────────────────────────────────────────
$cfgStmt = $pdo->query(
    "SELECT config_key, config_value, description FROM ai_evaluation_config
     WHERE config_key IN ('cost_overrun_threshold','time_overrun_threshold','model_version')"
);
$config = [];
foreach ($cfgStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $config[$row['config_key']] = ['value' => $row['config_value'], 'description' => $row['description']];
}
$response['data']['configuration'] = $config;

// ── Summary ──────────────────────────────────────────────────────────────────
$sumStmt = $pdo->query(
    "SELECT COUNT(*) as total_evaluated_projects,
            SUM(CASE WHEN cost_prediction_correct = 1 THEN 1 ELSE 0 END) as cost_correct_count,
            SUM(CASE WHEN time_prediction_correct = 1 THEN 1 ELSE 0 END) as time_correct_count,
            SUM(CASE WHEN cost_prediction_correct = 1 AND time_prediction_correct = 1 THEN 1 ELSE 0 END) as both_correct_count,
            COUNT(DISTINCT model_version) as model_versions_used,
            MIN(evaluation_completed_at) as first_evaluation,
            MAX(evaluation_completed_at) as latest_evaluation
     FROM construction_projects
     WHERE evaluation_completed_at IS NOT NULL"
);
$sum = $sumStmt->fetch(PDO::FETCH_ASSOC);
$response['data']['summary'] = [
    'total_evaluated_projects' => intval($sum['total_evaluated_projects']),
    'cost_predictions_correct' => intval($sum['cost_correct_count']),
    'time_predictions_correct' => intval($sum['time_correct_count']),
    'both_predictions_correct' => intval($sum['both_correct_count']),
    'model_versions_used'      => intval($sum['model_versions_used']),
    'first_evaluation_date'    => $sum['first_evaluation'],
    'latest_evaluation_date'   => $sum['latest_evaluation'],
];

// ── Recent evaluations ───────────────────────────────────────────────────────
$recStmt = $pdo->query(
    "SELECT id as project_id, project_name,
            predicted_cost_risk_level, cost_ground_truth_label, cost_prediction_classification,
            predicted_time_risk_level, time_ground_truth_label, time_prediction_classification,
            evaluation_completed_at
     FROM construction_projects
     WHERE evaluation_completed_at IS NOT NULL
     ORDER BY evaluation_completed_at DESC
     LIMIT 10"
);
$recent = [];
foreach ($recStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $recent[] = [
        'project_id'   => intval($row['project_id']),
        'project_name' => $row['project_name'],
        'cost'         => ['predicted' => $row['predicted_cost_risk_level'], 'actual' => $row['cost_ground_truth_label'], 'classification' => $row['cost_prediction_classification']],
        'time'         => ['predicted' => $row['predicted_time_risk_level'], 'actual' => $row['time_ground_truth_label'], 'classification' => $row['time_prediction_classification']],
        'evaluated_at' => $row['evaluation_completed_at'],
    ];
}
$response['data']['recent_evaluations'] = $recent;

// ── Interpretation ───────────────────────────────────────────────────────────
$interpretation = [];
foreach ($metrics as $metric) {
    $type = $metric['metric_type'];
    $perf = $metric['performance_metrics'];
    $f1   = $perf['f1_score'];
    $quality = $f1 >= 90 ? 'Excellent' : ($f1 >= 80 ? 'Good' : ($f1 >= 70 ? 'Fair' : 'Poor'));
    $recs = [];
    if ($perf['accuracy']  < 80) $recs[] = 'Model accuracy is below 80% — review prediction inputs';
    if ($perf['precision'] < 70) $recs[] = 'High false positive rate — model may be too conservative';
    if ($perf['recall']    < 70) $recs[] = 'High false negative rate — model may be missing risky projects';
    if ($f1 >= 90)               $recs[] = 'Model performance is excellent — continue monitoring';
    if (empty($recs))            $recs[] = 'Model performance is acceptable — continue monitoring';
    $interpretation[$type] = ['overall_quality' => $quality, 'f1_score' => $f1, 'recommendations' => $recs];
}
$response['data']['interpretation'] = $interpretation;

http_response_code(200);
echo json_encode($response, JSON_PRETTY_PRINT);
?>
