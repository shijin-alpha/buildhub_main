<?php
/**
 * Get Project Risk Trend (PDO)
 * Returns ordered prediction history for sparkline chart.
 * Falls back to constructing history from construction_projects if no history table exists.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once __DIR__ . '/../../config/database.php';

$project_id = intval($_GET['project_id'] ?? 0);
if (!$project_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'project_id is required']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Check if project_prediction_history table exists
    $chk = $pdo->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'project_prediction_history'"
    );
    $chk->execute();
    $historyTableExists = (int)$chk->fetchColumn() > 0;

    $trend = [];

    if ($historyTableExists) {
        $stmt = $pdo->prepare(
            "SELECT recorded_at as label_date,
                    predicted_cost_risk_level,
                    predicted_time_risk_level,
                    predicted_cost_probability,
                    predicted_time_probability
             FROM project_prediction_history
             WHERE project_id = ?
             ORDER BY recorded_at ASC
             LIMIT 20"
        );
        $stmt->execute([$project_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $costLevel = ['low' => 0, 'medium' => 1, 'high' => 2][strtolower($row['predicted_cost_risk_level'] ?? 'low')] ?? 0;
            $timeLevel = ['low' => 0, 'medium' => 1, 'high' => 2][strtolower($row['predicted_time_risk_level'] ?? 'low')] ?? 0;
            $score = (int)round((($costLevel * 0.55 + $timeLevel * 0.45) / 2) * 100);
            $trend[] = [
                'score' => $score,
                'label' => date('M d', strtotime($row['label_date'])),
            ];
        }
    }

    // Fallback: build a synthetic 2-point trend from the project's current prediction
    // and the evaluation_completed_at date if available
    if (count($trend) < 2) {
        $stmt = $pdo->prepare(
            "SELECT predicted_cost_risk_level, predicted_time_risk_level,
                    ai_prediction_date, evaluation_completed_at,
                    cost_prediction_correct, time_prediction_correct
             FROM construction_projects WHERE id = ?"
        );
        $stmt->execute([$project_id]);
        $proj = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($proj && $proj['predicted_cost_risk_level']) {
            $costLevel = ['low' => 0, 'medium' => 1, 'high' => 2][strtolower($proj['predicted_cost_risk_level'])] ?? 0;
            $timeLevel = ['low' => 0, 'medium' => 1, 'high' => 2][strtolower($proj['predicted_time_risk_level'] ?? 'low')] ?? 0;
            $score = (int)round((($costLevel * 0.55 + $timeLevel * 0.45) / 2) * 100);

            $predDate = $proj['ai_prediction_date'] ?? date('Y-m-d');
            $trend[] = ['score' => $score, 'label' => date('M d', strtotime($predDate))];

            // Second point: if evaluated, show outcome score (0 = both correct, higher = errors)
            if ($proj['evaluation_completed_at']) {
                $outcomeScore = (int)((!$proj['cost_prediction_correct'] ? 40 : 0) + (!$proj['time_prediction_correct'] ? 30 : 0));
                $trend[] = ['score' => $outcomeScore, 'label' => date('M d', strtotime($proj['evaluation_completed_at']))];
            } else {
                // Duplicate with slight variation so sparkline has 2 points
                $trend[] = ['score' => max(0, $score - 5), 'label' => date('M d')];
            }
        }
    }

    echo json_encode(['status' => 'success', 'data' => $trend]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
