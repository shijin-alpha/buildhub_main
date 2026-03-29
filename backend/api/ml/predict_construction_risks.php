<?php
/**
 * Construction Risk Prediction API
 * 
 * This endpoint provides real-time risk assessment for construction projects
 * using trained ML models for cost overrun and time delay risks.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    $required_fields = [
        'plot_size_sqft',
        'building_size_sqft', 
        'num_floors',
        'budget_amount',
        'num_bedrooms',
        'num_bathrooms'
    ];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || !is_numeric($input[$field])) {
            throw new Exception("Missing or invalid field: $field");
        }
    }
    
    // Call FastAPI ML service
    $ml_service_url = 'http://localhost:8000/predict';

    // ── Extract location/climate data from input (used for derived_metrics injection) ──
    $district_name_map = [
        'thiruvananthapuram'=>'thiruvananthapuram','kollam'=>'kollam','pathanamthitta'=>'pathanamthitta',
        'alappuzha'=>'alappuzha','kottayam'=>'kottayam','idukki'=>'idukki','ernakulam'=>'ernakulam',
        'thrissur'=>'thrissur','palakkad'=>'palakkad','malappuram'=>'malappuram','kozhikode'=>'kozhikode',
        'wayanad'=>'wayanad','kannur'=>'kannur','kasaragod'=>'kasaragod',
    ];
    $_district = strtolower(trim($input['kerala_district'] ?? $input['district'] ?? 'ernakulam'));
    $_district = $district_name_map[$_district] ?? 'ernakulam';

    $_climate_mods   = $input['climate_modifiers'] ?? [];
    $_terrain_label  = $_climate_mods['terrain_label'] ?? '';
    $_rainfall_label = $_climate_mods['rainfall_label'] ?? '';
    $_p_flood_mod    = floatval($_climate_mods['flood_mod'] ?? 0);
    $_location_name  = trim($input['location'] ?? '');

    // District-level fallbacks
    $_dist_climate = [
        'thiruvananthapuram'=>['terrain'=>'coastal',  'rainfall'=>'high',     'flood_risk'=>'moderate'],
        'kollam'            =>['terrain'=>'coastal',  'rainfall'=>'high',     'flood_risk'=>'moderate'],
        'pathanamthitta'    =>['terrain'=>'hilly',    'rainfall'=>'very_high','flood_risk'=>'low'],
        'alappuzha'         =>['terrain'=>'backwater','rainfall'=>'very_high','flood_risk'=>'high'],
        'kottayam'          =>['terrain'=>'midland',  'rainfall'=>'high',     'flood_risk'=>'moderate'],
        'idukki'            =>['terrain'=>'highland', 'rainfall'=>'very_high','flood_risk'=>'low'],
        'ernakulam'         =>['terrain'=>'coastal',  'rainfall'=>'high',     'flood_risk'=>'moderate'],
        'thrissur'          =>['terrain'=>'midland',  'rainfall'=>'high',     'flood_risk'=>'moderate'],
        'palakkad'          =>['terrain'=>'flat',     'rainfall'=>'moderate', 'flood_risk'=>'low'],
        'malappuram'        =>['terrain'=>'midland',  'rainfall'=>'high',     'flood_risk'=>'moderate'],
        'kozhikode'         =>['terrain'=>'coastal',  'rainfall'=>'very_high','flood_risk'=>'moderate'],
        'wayanad'           =>['terrain'=>'highland', 'rainfall'=>'very_high','flood_risk'=>'low'],
        'kannur'            =>['terrain'=>'coastal',  'rainfall'=>'very_high','flood_risk'=>'moderate'],
        'kasaragod'         =>['terrain'=>'coastal',  'rainfall'=>'high',     'flood_risk'=>'moderate'],
    ];
    $_dd = $_dist_climate[$_district] ?? ['terrain'=>'', 'rainfall'=>'moderate', 'flood_risk'=>'low'];
    if (empty($_terrain_label))  $_terrain_label  = $_dd['terrain'];
    if (empty($_rainfall_label)) $_rainfall_label = $_dd['rainfall'];
    if ($_p_flood_mod == 0 && empty($_climate_mods)) {
        $_p_flood_mod = $_dd['flood_risk'] === 'high' ? 12 : ($_dd['flood_risk'] === 'moderate' ? 6 : 0);
    }
    if (empty($_location_name))  $_location_name  = ucfirst($_district) . ' District';
    
    // Prepare cURL request
    $ch = curl_init($ml_service_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // If ML service is down, use PHP rule-based fallback
    if ($response === false || $http_code !== 200) {
        $fallback = php_fallback_prediction($input);
        echo json_encode([
            'success'   => true,
            'data'      => $fallback,
            'source'    => 'fallback',
            'warning'   => 'ML service unavailable — using rule-based estimate',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
    
    // Parse response
    $result = json_decode($response, true);
    if (!$result) {
        $fallback = php_fallback_prediction($input);
        echo json_encode([
            'success'   => true,
            'data'      => $fallback,
            'source'    => 'fallback',
            'warning'   => 'Invalid ML service response — using rule-based estimate',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
    
    // If ML service returned old format (no risk_reduction_suggestions),
    // generate them via the PHP engine so the frontend always gets suggestions.
    if (!isset($result['risk_reduction_suggestions'])) {
        $php_suggestions = php_generate_suggestions($input, $result);
        $result['risk_reduction_suggestions'] = $php_suggestions;
    }

    // Ensure final_risk is present (old format uses cost/time separately)
    if (!isset($result['final_risk'])) {
        $cost_lvl = $result['cost_overrun_risk']['risk_level'] ?? 'Low';
        $time_lvl = $result['time_delay_risk']['risk_level']  ?? 'Low';
        $result['final_risk'] = ($cost_lvl === 'High' || $time_lvl === 'High') ? 'High'
                              : (($cost_lvl === 'Medium' || $time_lvl === 'Medium') ? 'Medium' : 'Low');
    }

    // Always inject derived_metrics with location/climate data from PHP
    // (Python ML service doesn't return these — they come from the input)
    if (!isset($result['derived_metrics'])) {
        $result['derived_metrics'] = [];
    }
    // Merge location/climate fields so they're always present regardless of ML source
    $result['derived_metrics']['location']   = $_location_name;
    $result['derived_metrics']['terrain']    = $_terrain_label;
    $result['derived_metrics']['rainfall']   = $_rainfall_label;
    $result['derived_metrics']['flood_risk'] = $_p_flood_mod > 10 ? 'high' : ($_p_flood_mod > 5 ? 'moderate' : 'low');

    // Return successful prediction
    echo json_encode([
        'success'   => true,
        'data'      => $result,
        'source'    => 'ml_service',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Last-resort fallback on any exception
    if (isset($input) && is_array($input)) {
        $fallback = php_fallback_prediction($input);
        echo json_encode([
            'success'   => true,
            'data'      => $fallback,
            'source'    => 'fallback',
            'warning'   => 'ML service error — using rule-based estimate: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success'   => false,
            'error'     => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

/**
 * Generate context-aware risk reduction suggestions from an existing ML result.
 * Called when the ML service returns a valid prediction but without suggestions
 * (e.g. old service version still running).
 */
function php_generate_suggestions(array $input, array $ml_result): array {
    $building_size = floatval($input['building_size_sqft'] ?? 0);
    $budget        = floatval($input['budget_amount'] ?? 0);
    $num_floors    = intval($input['num_floors'] ?? 1);
    $planned_dur   = floatval($input['planned_duration_months'] ?? 0);
    $budget_sqft   = $building_size > 0 ? $budget / $building_size : 0;

    $CRITICAL = 500; $LOW_BM = 800; $STANDARD = 1500; $GOOD = 2500;
    $expected_dur = max(3.0, ($building_size / 400) + ($num_floors * 1.5));

    $cost_level  = $ml_result['cost_overrun_risk']['risk_level'] ?? 'Low';
    $time_level  = $ml_result['time_delay_risk']['risk_level']   ?? 'Low';
    $final_risk  = ($cost_level === 'High' || $time_level === 'High') ? 'High'
                 : (($cost_level === 'Medium' || $time_level === 'Medium') ? 'Medium' : 'Low');

    if ($final_risk === 'Low') return [];

    $rank = ['Low' => 0, 'Medium' => 1, 'High' => 2];
    $suggestions = [];

    // ── Budget suggestions: target next benchmark tier ──
    $budget_tiers = [[$LOW_BM, 'minimum viable'], [$STANDARD, 'standard construction'], [$GOOD, 'comfortable']];
    foreach ($budget_tiers as [$tier, $label]) {
        if ($budget_sqft < $tier) {
            $delta      = $tier - $budget_sqft;
            $new_budget = $tier * $building_size;
            // Simulate new cost level at this tier
            $ns = 0;
            if ($tier < $STANDARD)     $ns += 2;
            if ($building_size > 3000) $ns += 1;
            if ($num_floors > 2)       $ns += 1;
            if ($tier < $LOW_BM)       $ns += 1;
            $new_cost  = $ns >= 3 ? 'High' : ($ns >= 1 ? 'Medium' : 'Low');
            $new_final = ($new_cost === 'High' || $time_level === 'High') ? 'High'
                       : (($new_cost === 'Medium' || $time_level === 'Medium') ? 'Medium' : 'Low');
            if (($rank[$new_final] ?? 2) < ($rank[$final_risk] ?? 2)) {
                $suggestions[] = [
                    'suggestion'  => "Increase budget to ₹{$tier}/sqft ({$label} level) — add ₹" . round($delta) . "/sqft (total ₹" . number_format($new_budget, 0) . ")",
                    'new_risk'    => $new_final,
                    'new_score'   => null,
                    'score_delta' => null,
                ];
                break; // nearest tier only; combined suggestion below handles bigger jump
            }
        }
    }

    // ── Timeline suggestions ──
    if ($planned_dur > 0 && $planned_dur < $expected_dur) {
        $gap         = $expected_dur - $planned_dur;
        $half_target = round($planned_dur + $gap * 0.5, 1);

        // Simulate time level at half-gap
        $ts = 0;
        if ($building_size > 2500) $ts += 2;
        if ($num_floors > 2)       $ts += 1;
        if (!empty($input['topography']) && $input['topography'] !== 'flat') $ts += 1;
        if ($half_target < $expected_dur * 0.65) $ts += 2;
        $new_time_half  = $ts >= 3 ? 'High' : ($ts >= 1 ? 'Medium' : 'Low');
        $new_final_half = ($cost_level === 'High' || $new_time_half === 'High') ? 'High'
                        : (($cost_level === 'Medium' || $new_time_half === 'Medium') ? 'Medium' : 'Low');
        if (($rank[$new_final_half] ?? 2) < ($rank[$final_risk] ?? 2)) {
            $suggestions[] = [
                'suggestion'  => "Extend timeline to {$half_target} months (halfway to recommended " . round($expected_dur, 1) . " months)",
                'new_risk'    => $new_final_half,
                'new_score'   => null,
                'score_delta' => null,
            ];
        }

        // Full gap
        $ts2 = 0;
        if ($building_size > 2500) $ts2 += 2;
        if ($num_floors > 2)       $ts2 += 1;
        if (!empty($input['topography']) && $input['topography'] !== 'flat') $ts2 += 1;
        $new_time_full  = $ts2 >= 3 ? 'High' : ($ts2 >= 1 ? 'Medium' : 'Low');
        $new_final_full = ($cost_level === 'High' || $new_time_full === 'High') ? 'High'
                        : (($cost_level === 'Medium' || $new_time_full === 'Medium') ? 'Medium' : 'Low');
        if (($rank[$new_final_full] ?? 2) < ($rank[$final_risk] ?? 2)) {
            $suggestions[] = [
                'suggestion'  => "Extend timeline to " . round($expected_dur, 1) . " months (recommended duration for this project scope)",
                'new_risk'    => $new_final_full,
                'new_score'   => null,
                'score_delta' => null,
            ];
        }
    }

    // ── Combined: nearest budget tier + full timeline ──
    foreach ($budget_tiers as [$tier, $label]) {
        if ($budget_sqft < $tier && $planned_dur > 0 && $planned_dur < $expected_dur) {
            $ns2 = 0;
            if ($tier < $STANDARD)     $ns2 += 2;
            if ($building_size > 3000) $ns2 += 1;
            if ($num_floors > 2)       $ns2 += 1;
            if ($tier < $LOW_BM)       $ns2 += 1;
            $new_cost2 = $ns2 >= 3 ? 'High' : ($ns2 >= 1 ? 'Medium' : 'Low');
            $ts3 = 0;
            if ($building_size > 2500) $ts3 += 2;
            if ($num_floors > 2)       $ts3 += 1;
            if (!empty($input['topography']) && $input['topography'] !== 'flat') $ts3 += 1;
            $new_time3  = $ts3 >= 3 ? 'High' : ($ts3 >= 1 ? 'Medium' : 'Low');
            $new_final3 = ($new_cost2 === 'High' || $new_time3 === 'High') ? 'High'
                        : (($new_cost2 === 'Medium' || $new_time3 === 'Medium') ? 'Medium' : 'Low');
            if (($rank[$new_final3] ?? 2) < ($rank[$final_risk] ?? 2)) {
                $suggestions[] = [
                    'suggestion'  => "Increase budget to ₹{$tier}/sqft AND extend timeline to " . round($expected_dur, 1) . " months",
                    'new_risk'    => $new_final3,
                    'new_score'   => null,
                    'score_delta' => null,
                ];
            }
            break;
        }
    }

    // Deduplicate by new_risk, keep best 3
    $seen = []; $out = [];
    usort($suggestions, fn($a, $b) => ($rank[$a['new_risk']] ?? 2) - ($rank[$b['new_risk']] ?? 2));
    foreach ($suggestions as $s) {
        if (!isset($seen[$s['new_risk']])) {
            $seen[$s['new_risk']] = true;
            $out[] = $s;
        }
        if (count($out) === 3) break;
    }
    return $out;
}

/**
 * Rule-based fallback prediction when ML service is unavailable.
 * Uses simple heuristics based on project inputs.
 * Also generates context-aware risk reduction suggestions.
 */
function php_fallback_prediction(array $input): array {
    $building_size  = floatval($input['building_size_sqft'] ?? 0);
    $budget         = floatval($input['budget_amount'] ?? 0);
    $num_floors     = intval($input['num_floors'] ?? 1);
    $planned_dur    = floatval($input['planned_duration_months'] ?? 0);
    $budget_sqft    = $building_size > 0 ? $budget / $building_size : 0;
    $start_month    = intval($input['construction_start_month'] ?? 1);
    $district       = strtolower(trim($input['kerala_district'] ?? 'ernakulam'));

    // Kerala benchmarks
    $CRITICAL = 500; $LOW_BM = 800; $STANDARD = 1500; $GOOD = 2500;
    $expected_dur = max(3.0, ($building_size / 400) + ($num_floors * 1.5));

    // District risk modifiers
    $district_cost_mod = [
        'thiruvananthapuram' => 0, 'kollam' => 0, 'pathanamthitta' => 8,
        'alappuzha' => 8, 'kottayam' => 8, 'idukki' => 15, 'ernakulam' => -5,
        'thrissur' => 0, 'palakkad' => 0, 'malappuram' => 0, 'kozhikode' => 0,
        'wayanad' => 12, 'kannur' => 0, 'kasaragod' => 5,
    ];
    $district_time_mod = [
        'thiruvananthapuram' => 0, 'kollam' => 0, 'pathanamthitta' => 8,
        'alappuzha' => 12, 'kottayam' => 8, 'idukki' => 18, 'ernakulam' => -5,
        'thrissur' => 0, 'palakkad' => -5, 'malappuram' => 0, 'kozhikode' => 0,
        'wayanad' => 12, 'kannur' => 0, 'kasaragod' => 5,
    ];
    $d_cost = $district_cost_mod[$district] ?? 0;
    $d_time = $district_time_mod[$district] ?? 0;

    // Panchayat-level climate modifiers (override district when available)
    $climate_mods    = $input['climate_modifiers'] ?? [];
    $p_cost_mod      = floatval($climate_mods['cost_mod']      ?? 0);
    $p_time_mod      = floatval($climate_mods['time_mod']      ?? 0);
    $p_flood_mod     = floatval($climate_mods['flood_mod']     ?? 0);
    $terrain_label   = $climate_mods['terrain_label']          ?? '';
    $rainfall_label  = $climate_mods['rainfall_label']         ?? '';
    $location_name   = trim($input['location']                 ?? '');
    $has_panchayat   = !empty($location_name) && !empty($climate_mods);

    // District-level climate fallbacks (used when no panchayat selected)
    $district_climate_defaults = [
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
    $dist_defaults = $district_climate_defaults[$district] ?? ['terrain'=>'', 'rainfall'=>'moderate', 'flood_risk'=>'low'];

    // Fill in blanks from district defaults when panchayat data is missing
    if (empty($terrain_label))  $terrain_label  = $dist_defaults['terrain'];
    if (empty($rainfall_label)) $rainfall_label = $dist_defaults['rainfall'];
    if (empty($p_flood_mod))    $p_flood_mod    = $dist_defaults['flood_risk'] === 'high' ? 12 : ($dist_defaults['flood_risk'] === 'moderate' ? 6 : 0);
    if (empty($location_name))  $location_name  = ucfirst($district) . ' District';

    // When panchayat data is available, use it instead of district-level modifiers
    if ($has_panchayat) {
        $d_cost = $p_cost_mod;
        $d_time = $p_time_mod;
    }

    // Rainfall multiplier for monsoon exposure
    $rainfall_mult = ['very_high' => 1.3, 'high' => 1.0, 'moderate' => 0.7, 'low' => 0.4];
    $r_mult = $has_panchayat ? ($rainfall_mult[$rainfall_label] ?? 1.0) : 1.0;

    // Monsoon exposure: SW monsoon Jun-Sep, NE monsoon Oct-Nov
    $sw_months = [6, 7, 8, 9];
    $ne_months = [10, 11];
    $monsoon_raw = 0.0;
    for ($i = 0; $i < 6; $i++) {
        $m = (($start_month - 1 + $i) % 12) + 1;
        if (in_array($m, $sw_months)) $monsoon_raw += 1.5;
        elseif (in_array($m, $ne_months)) $monsoon_raw += 0.8;
    }
    $monsoon_score = min(1.0, $monsoon_raw / 9.0);  // 0-1
    // Scale by panchayat rainfall intensity
    $monsoon_score = min(1.0, $monsoon_score * $r_mult);

    // ── Cost risk ──
    $cost_score = 0;
    if ($budget_sqft < $STANDARD) $cost_score += 2;
    if ($building_size > 3000)    $cost_score += 1;
    if ($num_floors > 2)          $cost_score += 1;
    if ($budget_sqft < $LOW_BM)   $cost_score += 1;
    $cost_score += intval($d_cost / 15);
    $cost_score += intval($monsoon_score * 2);   // up to +2 at full monsoon

    if ($cost_score >= 3)     { $cost_level = 'High';   $cost_prob = 0.80; }
    elseif ($cost_score >= 1) { $cost_level = 'Medium'; $cost_prob = 0.55; }
    else                      { $cost_level = 'Low';    $cost_prob = 0.20; }

    // ── Time risk ──
    $time_score = 0;
    if ($building_size > 2500) $time_score += 2;
    if ($num_floors > 2)       $time_score += 1;
    if (!empty($input['topography']) && $input['topography'] !== 'flat') $time_score += 1;
    if ($planned_dur > 0 && $planned_dur < $expected_dur * 0.65) $time_score += 2;
    // District + monsoon adjustments (monsoon is the biggest time risk in Kerala)
    $time_score += intval($d_time / 15);
    $time_score += intval($monsoon_score * 3);   // up to +3 at full monsoon (June start = 0.84 → +2)

    if ($time_score >= 3)     { $time_level = 'High';   $time_prob = 0.75; }
    elseif ($time_score >= 1) { $time_level = 'Medium'; $time_prob = 0.50; }
    else                      { $time_level = 'Low';    $time_prob = 0.20; }

    // ── Risk reduction suggestions (only for Medium / High) ──
    $suggestions = [];
    $final_risk  = ($cost_level === 'High' || $time_level === 'High') ? 'High'
                 : (($cost_level === 'Medium' || $time_level === 'Medium') ? 'Medium' : 'Low');

    // If monsoon exposure is significant, always treat as at least Medium
    // (even if budget/timeline look fine — monsoon is a real Kerala risk)
    if ($final_risk === 'Low' && $monsoon_score > 0.3) {
        $final_risk = 'Medium';
    }

    if ($final_risk !== 'Low') {
        // Budget suggestions — target the next benchmark tier
        $budget_tiers = [
            [$LOW_BM,    'minimum viable'],
            [$STANDARD,  'standard construction'],
            [$GOOD,      'comfortable'],
        ];
        $suggested_budget = false;
        foreach ($budget_tiers as [$tier, $label]) {
            if ($budget_sqft < $tier) {
                $delta      = $tier - $budget_sqft;
                $new_budget = $tier * $building_size;
                $new_cost_score = 0;
                if ($tier < $STANDARD) $new_cost_score += 2;
                if ($building_size > 3000) $new_cost_score += 1;
                if ($num_floors > 2)       $new_cost_score += 1;
                if ($tier < $LOW_BM)       $new_cost_score += 1;
                $new_cost = $new_cost_score >= 3 ? 'High' : ($new_cost_score >= 1 ? 'Medium' : 'Low');
                $new_final = ($new_cost === 'High' || $time_level === 'High') ? 'High'
                           : (($new_cost === 'Medium' || $time_level === 'Medium') ? 'Medium' : 'Low');
                if ($new_final < $final_risk || $new_cost !== $cost_level) {
                    $suggestions[] = [
                        'suggestion'  => "Increase budget to ₹{$tier}/sqft ({$label} level) — add ₹" . round($delta) . "/sqft (total budget ₹" . number_format($new_budget, 0) . ")",
                        'new_risk'    => $new_final,
                        'new_score'   => null,
                        'score_delta' => null,
                        'type'        => 'budget',
                    ];
                    if (!$suggested_budget) {
                        $suggested_budget = true;
                        break;
                    }
                }
            }
        }

        // Timeline suggestion
        if ($planned_dur > 0 && $planned_dur < $expected_dur) {
            $gap        = $expected_dur - $planned_dur;
            $half_target = round($planned_dur + $gap * 0.5, 1);
            $new_time_score = $time_score;
            if ($half_target >= $expected_dur * 0.65) $new_time_score = max(0, $new_time_score - 2);
            $new_time  = $new_time_score >= 3 ? 'High' : ($new_time_score >= 1 ? 'Medium' : 'Low');
            $new_final = ($cost_level === 'High' || $new_time === 'High') ? 'High'
                       : (($cost_level === 'Medium' || $new_time === 'Medium') ? 'Medium' : 'Low');
            if ($new_time !== $time_level) {
                $suggestions[] = [
                    'suggestion'  => "Extend timeline to {$half_target} months (halfway to recommended " . round($expected_dur, 1) . " months)",
                    'new_risk'    => $new_final,
                    'new_score'   => null,
                    'score_delta' => null,
                    'type'        => 'timeline',
                ];
            }

            $full_time_score = max(0, $time_score - 2);
            $new_time_full   = $full_time_score >= 3 ? 'High' : ($full_time_score >= 1 ? 'Medium' : 'Low');
            $new_final_full  = ($cost_level === 'High' || $new_time_full === 'High') ? 'High'
                             : (($cost_level === 'Medium' || $new_time_full === 'Medium') ? 'Medium' : 'Low');
            if ($new_time_full !== $time_level) {
                $suggestions[] = [
                    'suggestion'  => "Extend timeline to " . round($expected_dur, 1) . " months (recommended duration for this project scope)",
                    'new_risk'    => $new_final_full,
                    'new_score'   => null,
                    'score_delta' => null,
                    'type'        => 'timeline',
                ];
            }
        }

        // Monsoon-specific suggestion — always show when monsoon exposure is meaningful
        if ($monsoon_score > 0.15) {
            $month_names = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                            7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
            $dry_order = [12, 1, 2, 3, 4, 11, 5];
            $best_month = 12; $best_exp = $monsoon_score;
            foreach ($dry_order as $dm) {
                if ($dm === $start_month) continue;
                $raw2 = 0.0;
                for ($i = 0; $i < 6; $i++) {
                    $m2 = (($dm - 1 + $i) % 12) + 1;
                    if (in_array($m2, $sw_months)) $raw2 += 1.5;
                    elseif (in_array($m2, $ne_months)) $raw2 += 0.8;
                }
                $exp2 = min(1.0, $raw2 / 9.0);
                if ($exp2 < $best_exp) { $best_month = $dm; $best_exp = $exp2; break; }
            }
            $drop_pct = round(($monsoon_score - $best_exp) * 100);
            if ($drop_pct >= 1) {
                // Estimate new risk after switching to a dry month
                $new_monsoon_time = max(0, $time_score - intval($best_exp * 3));
                $new_time_m  = $new_monsoon_time >= 3 ? 'High' : ($new_monsoon_time >= 1 ? 'Medium' : 'Low');
                $new_final_m = ($cost_level === 'High' || $new_time_m === 'High') ? 'High'
                             : (($cost_level === 'Medium' || $new_time_m === 'Medium') ? 'Medium' : 'Low');
                $suggestions[] = [
                    'suggestion'  => "Start construction in " . $month_names[$best_month] . " instead of " . $month_names[$start_month] . " — reduces monsoon exposure by {$drop_pct}% (dry season start avoids SW/NE monsoon disruption)",
                    'new_risk'    => $new_final_m,
                    'new_score'   => null,
                    'score_delta' => $drop_pct,
                    'type'        => 'month',
                ];
            }
        }

        // Deduplicate: month gets its own slot, others dedup by new_risk level
        $seen = []; $seen_month = false; $filtered = [];
        usort($suggestions, fn($a, $b) => ($a['type'] === 'month' ? -1 : 1));  // month first
        foreach ($suggestions as $s) {
            if (($s['type'] ?? '') === 'month' && !$seen_month) {
                $seen_month = true;
                $filtered[] = $s;
            } elseif (($s['type'] ?? '') !== 'month' && !isset($seen[$s['new_risk']])) {
                $seen[$s['new_risk']] = true;
                $filtered[] = $s;
            }
            if (count($filtered) === 4) break;
        }
        $suggestions = $filtered;
    }

    // Monsoon suggestion for Low-risk projects (runs outside the Medium/High block)
    if ($final_risk === 'Low' && $monsoon_score > 0.15) {
        $month_names = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
                        7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
        $dry_order = [12, 1, 2, 3, 4, 11, 5];
        $best_month = 12; $best_exp = $monsoon_score;
        foreach ($dry_order as $dm) {
            if ($dm === $start_month) continue;
            $raw2 = 0.0;
            for ($i = 0; $i < 6; $i++) {
                $m2 = (($dm - 1 + $i) % 12) + 1;
                if (in_array($m2, $sw_months)) $raw2 += 1.5;
                elseif (in_array($m2, $ne_months)) $raw2 += 0.8;
            }
            $exp2 = min(1.0, $raw2 / 9.0);
            if ($exp2 < $best_exp) { $best_month = $dm; $best_exp = $exp2; break; }
        }
        $drop_pct = round(($monsoon_score - $best_exp) * 100);
        if ($drop_pct >= 1) {
            $suggestions[] = [
                'suggestion'  => "Start construction in " . $month_names[$best_month] . " instead of " . $month_names[$start_month] . " — reduces monsoon exposure by {$drop_pct}% (dry season start avoids SW/NE monsoon disruption)",
                'new_risk'    => 'Low',
                'new_score'   => null,
                'score_delta' => $drop_pct,
                'type'        => 'month',
            ];
        }
    }

    return [
        'cost_overrun_risk' => [
            'risk_level'    => $cost_level,
            'probabilities' => ['Low' => round(1 - $cost_prob, 2), 'Medium' => 0.15, 'High' => $cost_prob],
            'explanation'   => ['Rule-based estimate (ML service offline)'],
        ],
        'time_delay_risk' => [
            'risk_level'    => $time_level,
            'probabilities' => ['Low' => round(1 - $time_prob, 2), 'Medium' => 0.15, 'High' => $time_prob],
            'explanation'   => ['Rule-based estimate (ML service offline)'],
        ],
        'final_risk'                 => $final_risk,
        'risk_reduction_suggestions' => $suggestions,
        'derived_metrics' => [
            'budget_per_sqft'   => round($budget_sqft, 2),
            'expected_duration' => round($expected_dur, 1),
            'planned_duration'  => round($planned_dur, 1),
            'monsoon_exposure'  => round($monsoon_score, 2),
            'kerala_district'   => $district,
            'location'          => $location_name,
            'terrain'           => $terrain_label,
            'rainfall'          => $rainfall_label,
            'flood_risk'        => $p_flood_mod > 10 ? 'high' : ($p_flood_mod > 5 ? 'moderate' : 'low'),
        ],
    ];
}
?>