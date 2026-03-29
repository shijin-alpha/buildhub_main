<?php
// Suppress warnings to prevent JSON corruption
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { header('Access-Control-Allow-Origin: ' . $origin); header('Vary: Origin'); } else { header('Access-Control-Allow-Origin: http://localhost'); }
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); header('Access-Control-Max-Age: 86400'); exit; }

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $contractorId = isset($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : 0;
    if ($contractorId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing contractor_id']);
        exit;
    }

    // Ensure table exists
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS contractor_layout_sends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contractor_id INT NOT NULL,
            homeowner_id INT NULL,
            layout_id INT NULL,
            design_id INT NULL,
            message TEXT NULL,
            payload JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Throwable $e) {
        $db->exec("CREATE TABLE IF NOT EXISTS contractor_layout_sends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contractor_id INT NOT NULL,
            homeowner_id INT NULL,
            layout_id INT NULL,
            design_id INT NULL,
            message TEXT NULL,
            payload LONGTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // Ensure columns exist
    try {
        $cols = $db->query("SHOW COLUMNS FROM contractor_layout_sends")->fetchAll(PDO::FETCH_COLUMN, 0);
        if ($cols && !in_array('homeowner_id', $cols)) {
            $db->exec("ALTER TABLE contractor_layout_sends ADD COLUMN homeowner_id INT NULL AFTER contractor_id");
        }
        if ($cols && !in_array('payload', $cols)) {
            try { $db->exec("ALTER TABLE contractor_layout_sends ADD COLUMN payload JSON NULL AFTER message"); }
            catch (Throwable $e) { $db->exec("ALTER TABLE contractor_layout_sends ADD COLUMN payload LONGTEXT NULL AFTER message"); }
        }
    } catch (Throwable $e) {}

    // Ensure AI prediction columns exist on layout_requests (migration-safe)
    try {
        $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS ai_cost_risk_level VARCHAR(20) DEFAULT NULL");
        $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS ai_time_risk_level VARCHAR(20) DEFAULT NULL");
        $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS ai_risk_score DECIMAL(5,2) DEFAULT NULL");
        $db->exec("ALTER TABLE layout_requests ADD COLUMN IF NOT EXISTS ai_prediction_data LONGTEXT DEFAULT NULL");
    } catch (Throwable $e) {}

    // Join layout_requests by layout_id when set; otherwise fall back to most recent
    // non-deleted request for that homeowner — this covers old sends where layout_id is NULL.
    $stmt = $db->prepare("
        SELECT s.id, s.contractor_id, s.homeowner_id, s.layout_id, s.design_id, NULL as estimate_id,
               s.message, s.payload, s.created_at, s.acknowledged_at, s.due_date,
               CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS homeowner_name,
               u.email AS homeowner_email,
               'layout_request' as type,
               'New layout sent' as title,
               'unread' as status,
               COALESCE(lr_direct.ai_cost_risk_level, lr_fallback.ai_cost_risk_level) as ai_cost_risk_level,
               COALESCE(lr_direct.ai_time_risk_level, lr_fallback.ai_time_risk_level) as ai_time_risk_level,
               COALESCE(lr_direct.ai_risk_score,      lr_fallback.ai_risk_score)      as ai_risk_score,
               COALESCE(lr_direct.ai_prediction_data, lr_fallback.ai_prediction_data) as ai_prediction_data,
               COALESCE(lr_direct.plot_size,     lr_fallback.plot_size)     as lr_plot_size,
               COALESCE(lr_direct.building_size, lr_fallback.building_size) as lr_building_size,
               COALESCE(lr_direct.budget_range,  lr_fallback.budget_range)  as lr_budget_range,
               COALESCE(lr_direct.timeline,      lr_fallback.timeline)      as lr_timeline
        FROM contractor_layout_sends s
        LEFT JOIN users u ON u.id = s.homeowner_id
        LEFT JOIN layout_requests lr_direct
            ON s.layout_id IS NOT NULL AND lr_direct.id = s.layout_id
        LEFT JOIN layout_requests lr_fallback
            ON s.layout_id IS NULL
            AND lr_fallback.homeowner_id = s.homeowner_id
            AND lr_fallback.status NOT IN ('deleted','rejected')
            AND lr_fallback.id = (
                SELECT id FROM layout_requests
                WHERE homeowner_id = s.homeowner_id
                AND status NOT IN ('deleted','rejected')
                ORDER BY created_at DESC LIMIT 1
            )
        WHERE s.contractor_id = :cid1

        UNION ALL

        SELECT ci.id, ci.contractor_id, ci.homeowner_id, NULL as layout_id, NULL as design_id, ci.estimate_id,
               ci.message, NULL as payload, ci.created_at, ci.acknowledged_at, ci.due_date,
               CONCAT(COALESCE(u2.first_name,''), ' ', COALESCE(u2.last_name,'')) AS homeowner_name,
               u2.email AS homeowner_email,
               ci.type,
               ci.title,
               ci.status,
               NULL as ai_cost_risk_level,
               NULL as ai_time_risk_level,
               NULL as ai_risk_score,
               NULL as ai_prediction_data,
               NULL as lr_plot_size,
               NULL as lr_building_size,
               NULL as lr_budget_range,
               NULL as lr_timeline
        FROM contractor_inbox ci
        LEFT JOIN users u2 ON u2.id = ci.homeowner_id
        WHERE ci.contractor_id = :cid2

        ORDER BY created_at DESC
    ");
    $stmt->bindValue(':cid1', $contractorId, PDO::PARAM_INT);
    $stmt->bindValue(':cid2', $contractorId, PDO::PARAM_INT);
    $stmt->execute();

    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $payload = [];
        if (!empty($row['payload'])) {
            $decoded = json_decode($row['payload'], true);
            if (is_array($decoded)) $payload = $decoded;
        }

        // Extract technical details from payload if available
        $technical_details = null;
        if (isset($payload['technical_details']) && is_array($payload['technical_details'])) {
            $technical_details = $payload['technical_details'];
        } else if (isset($payload['forwarded_design']['technical_details']) && is_array($payload['forwarded_design']['technical_details'])) {
            $technical_details = $payload['forwarded_design']['technical_details'];
        }

        // Extract layout image URL for display
        $layout_image_url = null;
        if (isset($payload['layout_image_url'])) {
            $layout_image_url = $payload['layout_image_url'];
        } else if (isset($payload['technical_details']['layout_image']) && is_array($payload['technical_details']['layout_image'])) {
            $layoutImage = $payload['technical_details']['layout_image'];
            if (!empty($layoutImage['name']) && (!isset($layoutImage['uploaded']) || $layoutImage['uploaded'] === true)) {
                $storedName = $layoutImage['stored'] ?? $layoutImage['name'];
                $layout_image_url = '/buildhub/backend/uploads/house_plans/' . $storedName;
            }
        }

        // Extract plot size and building size — prefer payload, fall back to layout_requests row
        $plot_size    = $payload['plot_size']    ?? $row['lr_plot_size']    ?? null;
        $building_size = $payload['building_size'] ?? $row['lr_building_size'] ?? null;

        // Extract layout request details
        $layout_request_details = $payload['layout_request_details'] ?? null;

        $budget_range = null; $timeline = null; $num_floors = null;
        $orientation = null; $site_considerations = null; $material_preferences = null;
        $budget_allocation = null; $preferred_style = null;
        $requirements = null; $parsed_requirements = null;

        if ($layout_request_details && is_array($layout_request_details)) {
            $budget_range        = $layout_request_details['budget_range']        ?? null;
            $timeline            = $layout_request_details['timeline']            ?? null;
            $num_floors          = $layout_request_details['num_floors']          ?? null;
            $orientation         = $layout_request_details['orientation']         ?? null;
            $site_considerations = $layout_request_details['site_considerations'] ?? null;
            $material_preferences = $layout_request_details['material_preferences'] ?? null;
            $budget_allocation   = $layout_request_details['budget_allocation']   ?? null;
            $preferred_style     = $layout_request_details['preferred_style']     ?? null;
            $requirements        = $layout_request_details['requirements']        ?? null;
            $parsed_requirements = $layout_request_details['parsed_requirements'] ?? null;
        }
        // Fall back to layout_requests columns when payload has no details
        if (!$budget_range) $budget_range = $row['lr_budget_range'] ?? null;
        if (!$timeline)     $timeline     = $row['lr_timeline']     ?? null;

        // ---------------------------------------------------------------
        // Build ai_predictions — priority order:
        //   1. payload.ai_predictions  (new flow: saved at send time)
        //   2. layout_requests columns (ai_cost_risk_level etc.)
        // ---------------------------------------------------------------
        $ai_predictions = null;

        if (!empty($payload['ai_predictions']) && is_array($payload['ai_predictions'])) {
            // New flow — full prediction data already in payload
            $ai_predictions = $payload['ai_predictions'];
        } elseif (!empty($row['ai_cost_risk_level']) || !empty($row['ai_time_risk_level'])) {
            // Fallback — pulled from layout_requests via JOIN
            $pred_data = null;
            if (!empty($row['ai_prediction_data'])) {
                $pred_data = json_decode($row['ai_prediction_data'], true);
            }
            $ai_predictions = [
                'cost_risk_level'  => $row['ai_cost_risk_level']  ?? null,
                'time_risk_level'  => $row['ai_time_risk_level']  ?? null,
                'risk_score'       => !empty($row['ai_risk_score']) ? floatval($row['ai_risk_score']) : null,
                'prediction_data'  => $pred_data,
            ];
        }

        $items[] = [
            'id'                    => (int)$row['id'],
            'contractor_id'         => (int)$row['contractor_id'],
            'homeowner_id'          => is_null($row['homeowner_id']) ? null : (int)$row['homeowner_id'],
            'homeowner_name'        => $row['homeowner_name'] ?? null,
            'homeowner_email'       => $row['homeowner_email'] ?? null,
            'layout_id'             => is_null($row['layout_id']) ? null : (int)$row['layout_id'],
            'design_id'             => is_null($row['design_id']) ? null : (int)$row['design_id'],
            'estimate_id'           => is_null($row['estimate_id']) ? null : (int)$row['estimate_id'],
            'type'                  => $row['type'] ?? 'layout_request',
            'title'                 => $row['title'] ?? 'New layout sent',
            'message'               => $row['message'],
            'payload'               => $payload,
            'technical_details'     => $technical_details,
            'plot_size'             => $plot_size,
            'building_size'         => $building_size,
            'budget_range'          => $budget_range,
            'timeline'              => $timeline,
            'num_floors'            => $num_floors,
            'orientation'           => $orientation,
            'site_considerations'   => $site_considerations,
            'material_preferences'  => $material_preferences,
            'budget_allocation'     => $budget_allocation,
            'preferred_style'       => $preferred_style,
            'requirements'          => $requirements,
            'parsed_requirements'   => $parsed_requirements,
            'layout_request_details' => $layout_request_details,
            'layout_image_url'      => $layout_image_url,
            'created_at'            => $row['created_at'],
            'acknowledged_at'       => $row['acknowledged_at'] ?? null,
            'due_date'              => $row['due_date'] ?? null,
            'status'                => $row['status'] ?? 'unread',
            'ai_predictions'        => $ai_predictions,
        ];
    }

    echo json_encode(['success' => true, 'items' => $items]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching inbox: ' . $e->getMessage()]);
}
