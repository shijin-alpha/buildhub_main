<?php
/**
 * Get AI Evaluation Metrics API (PDO)
 * Supports: latest, history, project, config
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../../config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// ── Guard: required objects ──────────────────────────────────────────────────
$requiredTables = ['ai_evaluation_metrics', 'ai_evaluation_config'];
$requiredViews  = ['v_latest_ai_metrics', 'v_project_evaluation_summary'];

foreach ($requiredTables as $tbl) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $chk->execute([$tbl]);
    if ((int)$chk->fetchColumn() === 0) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Required table '$tbl' does not exist"]);
        exit();
    }
}

foreach ($requiredViews as $view) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $chk->execute([$view]);
    if ((int)$chk->fetchColumn() === 0) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Required view '$view' does not exist"]);
        exit();
    }
}

$type = $_GET['type'] ?? 'latest';

try {
    switch ($type) {

        case 'latest':
            $stmt = $pdo->query("SELECT * FROM v_latest_ai_metrics ORDER BY metric_type");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $metrics = [];
            foreach ($rows as $row) {
                $metrics[$row['metric_type']] = [
                    'evaluation_date' => $row['evaluation_date'],
                    'total_projects'  => intval($row['total_projects']),
                    'confusion_matrix' => [
                        'true_positives'  => intval($row['true_positives']),
                        'false_positives' => intval($row['false_positives']),
                        'true_negatives'  => intval($row['true_negatives']),
                        'false_negatives' => intval($row['false_negatives']),
                    ],
                    'performance' => [
                        'accuracy'           => round(floatval($row['accuracy']), 2),
                        'precision'          => round(floatval($row['precision_score']), 2),  // correct column
                        'recall'             => round(floatval($row['recall_score']), 2),     // correct column
                        'f1_score'           => round(floatval($row['f1_score']), 2),
                        'specificity'        => round(floatval($row['specificity']), 2),
                        'false_positive_rate'=> round(floatval($row['false_positive_rate']), 2),
                    ],
                    'created_at' => $row['created_at'],
                ];
            }
            echo json_encode(['status' => 'success', 'data' => $metrics, 'timestamp' => date('Y-m-d H:i:s')], JSON_PRETTY_PRINT);
            break;

        case 'history':
            $days = intval($_GET['days'] ?? 30);
            $stmt = $pdo->prepare(
                "SELECT * FROM ai_evaluation_metrics
                 WHERE evaluation_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                 ORDER BY evaluation_date DESC, metric_type"
            );
            $stmt->execute([$days]);
            $history = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $history[] = [
                    'date'           => $row['evaluation_date'],
                    'type'           => $row['metric_type'],
                    'total_projects' => intval($row['total_projects']),
                    'accuracy'       => round(floatval($row['accuracy']), 2),
                    'precision'      => round(floatval($row['precision_score']), 2),  // correct column
                    'recall'         => round(floatval($row['recall_score']), 2),     // correct column
                    'f1_score'       => round(floatval($row['f1_score']), 2),
                ];
            }
            echo json_encode(['status' => 'success', 'data' => $history, 'period_days' => $days, 'timestamp' => date('Y-m-d H:i:s')], JSON_PRETTY_PRINT);
            break;

        case 'project':
            $project_id = intval($_GET['project_id'] ?? 0);
            if (!$project_id) {
                throw new Exception('project_id required for type=project');
            }
            $stmt = $pdo->prepare("SELECT * FROM v_project_evaluation_summary WHERE project_id = ?");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$project) {
                echo json_encode(['status' => 'error', 'message' => 'No evaluation data found for this project']);
                exit();
            }
            echo json_encode([
                'status' => 'success',
                'data'   => [
                    'project_id'   => intval($project['project_id']),
                    'project_name' => $project['project_name'],
                    'status'       => $project['status'],
                    'predictions'  => [
                        'cost_risk'        => $project['predicted_cost_risk_level'],
                        'cost_probability' => floatval($project['predicted_cost_probability']),
                        'time_risk'        => $project['predicted_time_risk_level'],
                        'time_probability' => floatval($project['predicted_time_probability']),
                        'generated_at'     => $project['ai_prediction_date'] ?? null,
                        'model_version'    => $project['model_version'],
                    ],
                    'actuals' => [
                        'cost_overrun_pct'   => floatval($project['actual_cost_overrun_percentage']),
                        'time_overrun_pct'   => floatval($project['actual_time_overrun_percentage']),
                        'cost_ground_truth'  => $project['cost_ground_truth_label'],
                        'time_ground_truth'  => $project['time_ground_truth_label'],
                    ],
                    'evaluation' => [
                        'cost_classification' => $project['cost_prediction_classification'],
                        'cost_correct'        => (bool)$project['cost_prediction_correct'],
                        'time_classification' => $project['time_prediction_classification'],
                        'time_correct'        => (bool)$project['time_prediction_correct'],
                        'evaluated_at'        => $project['evaluation_completed_at'],
                    ],
                ],
                'timestamp' => date('Y-m-d H:i:s'),
            ], JSON_PRETTY_PRINT);
            break;

        case 'config':
            $stmt = $pdo->query("SELECT * FROM ai_evaluation_config ORDER BY config_key");
            $config = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $config[$row['config_key']] = [
                    'value'       => $row['config_value'],
                    'description' => $row['description'],
                    'updated_at'  => $row['updated_at'],
                ];
            }
            echo json_encode(['status' => 'success', 'data' => $config, 'timestamp' => date('Y-m-d H:i:s')], JSON_PRETTY_PRINT);
            break;

        default:
            throw new Exception('Invalid type parameter. Use: latest, history, project, or config');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'timestamp' => date('Y-m-d H:i:s')]);
}
?>
