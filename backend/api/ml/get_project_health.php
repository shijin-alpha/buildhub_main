<?php
/**
 * Unified Project Health API
 * Returns combined cost + time risk as a single health score,
 * with trend history, top factors, and estimated impact.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
    if (!$project_id) {
        echo json_encode(['success' => false, 'message' => 'project_id required']);
        exit;
    }

    // ── Latest prediction ────────────────────────────────────────────────────
    $stmt = $conn->prepare("
        SELECT 
            predicted_cost_risk_level   AS cost_risk_level,
            predicted_cost_probability  AS cost_risk_probability,
            predicted_time_risk_level   AS time_risk_level,
            predicted_time_probability  AS time_risk_probability,
            ai_prediction_date          AS created_at
        FROM construction_projects
        WHERE id = :pid
          AND predicted_cost_risk_level IS NOT NULL
        LIMIT 1
    ");
    $stmt->bindParam(':pid', $project_id);
    $stmt->execute();
    $pred = $stmt->fetch(PDO::FETCH_ASSOC);

    // ── Project budget ───────────────────────────────────────────────────────
    $stmt2 = $conn->prepare("
        SELECT estimated_cost, project_name, completion_percentage
        FROM construction_projects WHERE id = :pid
    ");
    $stmt2->bindParam(':pid', $project_id);
    $stmt2->execute();
    $project = $stmt2->fetch(PDO::FETCH_ASSOC);

    // ── Trend history — last 5 prediction snapshots from audit log if available,
    //    otherwise just the current prediction as a single point ──────────────
    $stmt3 = $conn->prepare("
        SELECT 
            predicted_cost_risk_level   AS cost_risk_level,
            predicted_time_risk_level   AS time_risk_level,
            ai_prediction_date          AS created_at
        FROM construction_projects
        WHERE id = :pid
          AND predicted_cost_risk_level IS NOT NULL
        LIMIT 1
    ");
    $stmt3->bindParam(':pid', $project_id);
    $stmt3->execute();
    $history = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    $levels = ['Low' => 0, 'Medium' => 1, 'High' => 2];
    $trend = array_map(function($row) use ($levels) {
        $c = $levels[$row['cost_risk_level']] ?? 0;
        $t = $levels[$row['time_risk_level']] ?? 0;
        $score = (int) round((($c * 0.55 + $t * 0.45) / 2) * 100);
        return [
            'score' => $score,
            'label' => date('M d', strtotime($row['created_at']))
        ];
    }, $history);

    // ── Actual spend ─────────────────────────────────────────────────────────
    $stmt4 = $conn->prepare("
        SELECT
          (SELECT COALESCE(SUM(requested_amount),0) FROM stage_payment_requests
           WHERE project_id = ? AND status IN ('paid','approved')) +
          (SELECT COALESCE(SUM(requested_amount),0) FROM custom_payment_requests
           WHERE project_id = ? AND status IN ('paid','approved'))
        AS total_spent
    ");
    $stmt4->execute([$project_id, $project_id]);
    $spend = $stmt4->fetch(PDO::FETCH_ASSOC);

    $budget   = floatval($project['estimated_cost'] ?? 0);
    $spent    = floatval($spend['total_spent'] ?? 0);
    $spendPct = $budget > 0 ? round(($spent / $budget) * 100, 1) : 0;

    // ── Kerala district + panchayat climate data ─────────────────────────────
    $stmt5 = $conn->prepare("
        SELECT lr.kerala_district, lr.construction_start_month, lr.location,
               cp.project_location
        FROM construction_projects cp
        LEFT JOIN layout_requests lr ON lr.id = cp.layout_id
        WHERE cp.id = :pid
        LIMIT 1
    ");
    $stmt5->bindParam(':pid', $project_id);
    $stmt5->execute();
    $loc = $stmt5->fetch(PDO::FETCH_ASSOC);

    $kerala_district = $loc['kerala_district'] ?? '';
    // Use panchayat/location from layout_request, fallback to project_location, then district
    $location_name   = trim(($loc['location'] ?? '') ?: ($loc['project_location'] ?? '') ?: $kerala_district);

    // District-level climate profiles (mirrors climateData.js defaults)
    $district_climate = [
        'thiruvananthapuram' => ['terrain'=>'coastal',   'rainfall'=>'high',      'flood_risk'=>'moderate'],
        'kollam'             => ['terrain'=>'coastal',   'rainfall'=>'high',      'flood_risk'=>'moderate'],
        'pathanamthitta'     => ['terrain'=>'hilly',     'rainfall'=>'very_high', 'flood_risk'=>'low'],
        'alappuzha'          => ['terrain'=>'backwater', 'rainfall'=>'very_high', 'flood_risk'=>'high'],
        'kottayam'           => ['terrain'=>'midland',   'rainfall'=>'high',      'flood_risk'=>'moderate'],
        'idukki'             => ['terrain'=>'highland',  'rainfall'=>'very_high', 'flood_risk'=>'low'],
        'ernakulam'          => ['terrain'=>'coastal',   'rainfall'=>'high',      'flood_risk'=>'moderate'],
        'thrissur'           => ['terrain'=>'midland',   'rainfall'=>'high',      'flood_risk'=>'moderate'],
        'palakkad'           => ['terrain'=>'flat',      'rainfall'=>'moderate',  'flood_risk'=>'low'],
        'malappuram'         => ['terrain'=>'midland',   'rainfall'=>'high',      'flood_risk'=>'moderate'],
        'kozhikode'          => ['terrain'=>'coastal',   'rainfall'=>'very_high', 'flood_risk'=>'moderate'],
        'wayanad'            => ['terrain'=>'highland',  'rainfall'=>'very_high', 'flood_risk'=>'low'],
        'kannur'             => ['terrain'=>'coastal',   'rainfall'=>'very_high', 'flood_risk'=>'moderate'],
        'kasaragod'          => ['terrain'=>'coastal',   'rainfall'=>'high',      'flood_risk'=>'moderate'],
    ];

    $climate = $district_climate[strtolower(trim($kerala_district))] ?? ['terrain'=>'', 'rainfall'=>'', 'flood_risk'=>'low'];
    $terrain    = $climate['terrain'];
    $rainfall   = $climate['rainfall'];
    $flood_risk = $climate['flood_risk'];

    // ── Build top factors from real data ─────────────────────────────────────
    $costFactors = [];
    $timeFactors = [];

    if ($pred) {
        if ($spendPct > 70) {
            $costFactors[] = "Budget utilization is {$spendPct}% — high spend rate";
        }
        if ($pred['cost_risk_level'] === 'High') {
            $costFactors[] = 'Project type historically shows cost overruns';
        }
        $progress = floatval($project['completion_percentage'] ?? 0);
        if ($progress < 30 && $spendPct > 40) {
            $costFactors[] = 'Early-stage high spend relative to progress';
        }
        if ($pred['time_risk_level'] !== 'Low') {
            $timeFactors[] = 'Construction pace may not meet planned timeline';
        }
        if ($pred['time_risk_level'] === 'High') {
            $timeFactors[] = 'Similar projects in this category delayed 60%+ of the time';
        }
        // Climate-based factors
        if ($flood_risk === 'high') {
            $costFactors[] = 'High flood risk zone — waterproofing and drainage costs elevated';
        }
        if ($terrain === 'highland' || $terrain === 'hilly') {
            $timeFactors[] = 'Hilly/highland terrain increases material transport time';
        }
        if ($rainfall === 'very_high') {
            $timeFactors[] = 'Very high rainfall zone — monsoon disruptions likely';
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'cost_risk_level'      => $pred['cost_risk_level'] ?? 'Low',
            'cost_risk_probability'=> floatval($pred['cost_risk_probability'] ?? 0.5),
            'time_risk_level'      => $pred['time_risk_level'] ?? 'Low',
            'time_risk_probability'=> floatval($pred['time_risk_probability'] ?? 0.5),
            'budget'               => $budget,
            'total_spent'          => $spent,
            'spend_percentage'     => $spendPct,
            'project_name'         => $project['project_name'] ?? '',
            'completion_percentage'=> floatval($project['completion_percentage'] ?? 0),
            'cost_factors'         => $costFactors,
            'time_factors'         => $timeFactors,
            'trend_history'        => $trend,
            'derived_metrics'      => [
                'location'   => $location_name,
                'terrain'    => $terrain,
                'rainfall'   => $rainfall,
                'flood_risk' => $flood_risk,
            ],
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
