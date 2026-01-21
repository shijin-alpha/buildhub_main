<?php
header('Content-Type: application/json');
// Reflect origin for credentialed requests instead of '*'
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
} else {
    header('Access-Control-Allow-Origin: http://localhost');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    header('Access-Control-Max-Age: 86400');
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $contractor_id = isset($input['contractor_id']) ? (int)$input['contractor_id'] : 0;
    $layout_id = isset($input['layout_id']) ? (int)$input['layout_id'] : 0;
    $design_id = isset($input['design_id']) ? (int)$input['design_id'] : 0; // forwarded design bundle
    $homeowner_id = isset($input['homeowner_id']) ? (int)$input['homeowner_id'] : 0;
    $message = isset($input['contractor_message']) ? trim((string)$input['contractor_message']) : (isset($input['message']) ? trim((string)$input['message']) : '');
    $forwarded_design = isset($input['forwarded_design']) && is_array($input['forwarded_design']) ? $input['forwarded_design'] : null;
    $floor_details = isset($input['floor_details']) && is_array($input['floor_details']) ? $input['floor_details'] : null;
    $layout_image_url = isset($input['layout_image_url']) ? (string)$input['layout_image_url'] : '';
    $plot_size = isset($input['plot_size']) ? trim((string)$input['plot_size']) : '';
    $building_size = isset($input['building_size']) ? trim((string)$input['building_size']) : '';

    if ($contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing contractor_id']);
        exit;
    }

    // Verify contractor exists and is verified
    $hasStatus = false;
    try {
        $col = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
        $hasStatus = $col && $col->rowCount() > 0;
    } catch (Throwable $e) {}

    // Development-friendly verification: allow contractor role; prefer verified if present
    $checkQuery = $hasStatus
        ? "SELECT id FROM users WHERE id = :id AND role = 'contractor' AND (is_verified = 1 OR is_verified IS NULL) AND (status IS NULL OR status = '' OR status = 'approved' OR status = 'active')"
        : "SELECT id FROM users WHERE id = :id AND role = 'contractor'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindValue(':id', $contractor_id, PDO::PARAM_INT);
    $checkStmt->execute();
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Contractor not found or not approved']);
        exit;
    }

    // Basic record of the send action (create table if not exists)
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS contractor_layout_sends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contractor_id INT NOT NULL,
            layout_id INT NULL,
            design_id INT NULL,
            message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (Throwable $e) {
        // Fallback for DBs without JSON support
        $db->exec("CREATE TABLE IF NOT EXISTS contractor_layout_sends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contractor_id INT NOT NULL,
            layout_id INT NULL,
            design_id INT NULL,
            message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // Ensure expected columns exist (migration-safe)
    try {
        $cols = $db->query("SHOW COLUMNS FROM contractor_layout_sends")->fetchAll(PDO::FETCH_COLUMN, 0);
        if ($cols && !in_array('homeowner_id', $cols)) {
            $db->exec("ALTER TABLE contractor_layout_sends ADD COLUMN homeowner_id INT NULL AFTER contractor_id");
        }
        if ($cols && !in_array('payload', $cols)) {
            // Try JSON, fallback to LONGTEXT
            try { $db->exec("ALTER TABLE contractor_layout_sends ADD COLUMN payload JSON NULL AFTER message"); }
            catch (Throwable $e) { $db->exec("ALTER TABLE contractor_layout_sends ADD COLUMN payload LONGTEXT NULL AFTER message"); }
        }
    } catch (Throwable $e) {
        // ignore column ensure errors
    }

    // Get technical details and layout details from layout if available
    $technical_details = null;
    $layout_plot_size = null;
    $layout_building_size = null;
    $layout_request_details = null;
    
    if ($layout_id) {
        try {
            $techStmt = $db->prepare("SELECT technical_details, plot_size, building_size FROM layout_library WHERE id = :id");
            $techStmt->bindValue(':id', $layout_id, PDO::PARAM_INT);
            $techStmt->execute();
            $techRow = $techStmt->fetch(PDO::FETCH_ASSOC);
            if ($techRow) {
                if (!empty($techRow['technical_details'])) {
                    $technical_details = json_decode($techRow['technical_details'], true);
                }
                $layout_plot_size = $techRow['plot_size'] ?? null;
                $layout_building_size = $techRow['building_size'] ?? null;
            }
        } catch (Throwable $e) {
            error_log("Failed to get technical details: " . $e->getMessage());
            // Try without plot_size and building_size if they don't exist
            try {
                $techStmt = $db->prepare("SELECT technical_details FROM layout_library WHERE id = :id");
                $techStmt->bindValue(':id', $layout_id, PDO::PARAM_INT);
                $techStmt->execute();
                $techRow = $techStmt->fetch(PDO::FETCH_ASSOC);
                if ($techRow && !empty($techRow['technical_details'])) {
                    $technical_details = json_decode($techRow['technical_details'], true);
                }
            } catch (Throwable $e2) {
                error_log("Failed to get technical details: " . $e2->getMessage());
            }
        }
    }
    
    // Get layout request details if homeowner_id is available
    if ($homeowner_id) {
        try {
            $layoutRequestStmt = $db->prepare("
                SELECT 
                    plot_size, building_size, budget_range, location, timeline, 
                    num_floors, orientation, site_considerations, material_preferences,
                    budget_allocation, preferred_style, requirements
                FROM layout_requests 
                WHERE homeowner_id = :homeowner_id 
                AND status NOT IN ('deleted', 'rejected')
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $layoutRequestStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
            $layoutRequestStmt->execute();
            $layoutRequestRow = $layoutRequestStmt->fetch(PDO::FETCH_ASSOC);
            if ($layoutRequestRow) {
                $layout_request_details = $layoutRequestRow;
                // Parse requirements if it's JSON
                if (!empty($layoutRequestRow['requirements'])) {
                    try {
                        $parsed_requirements = json_decode($layoutRequestRow['requirements'], true);
                        if (is_array($parsed_requirements)) {
                            $layout_request_details['parsed_requirements'] = $parsed_requirements;
                        }
                    } catch (Throwable $e) {
                        // Keep original requirements if JSON parsing fails
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("Failed to get layout request details: " . $e->getMessage());
        }
    }
    
    // Also try to get technical details from forwarded design
    if (!$technical_details && $forwarded_design && isset($forwarded_design['technical_details'])) {
        $technical_details = $forwarded_design['technical_details'];
    }
    
    // Use layout request values first, then layout values, then passed values
    $final_plot_size = ($layout_request_details['plot_size'] ?? null) ?: $layout_plot_size ?: $plot_size;
    $final_building_size = ($layout_request_details['building_size'] ?? null) ?: $layout_building_size ?: $building_size;

    $payload = [
        'layout_id' => $layout_id ?: null,
        'design_id' => $design_id ?: null,
        'message' => $message ?: null,
        'forwarded_design' => $forwarded_design ?: null,
        'layout_image_url' => $layout_image_url ?: null,
        'floor_details' => $floor_details ?: null,
        'technical_details' => $technical_details,
        'plot_size' => $final_plot_size ?: null,
        'building_size' => $final_building_size ?: null,
        'layout_request_details' => $layout_request_details,
    ];

    $ins = $db->prepare("INSERT INTO contractor_layout_sends (contractor_id, homeowner_id, layout_id, design_id, message, payload) VALUES (:cid, :hid, :lid, :did, :msg, :payload)");
    $ins->bindValue(':cid', $contractor_id, PDO::PARAM_INT);
    $ins->bindValue(':hid', $homeowner_id ?: null, $homeowner_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $ins->bindValue(':lid', $layout_id ?: null, $layout_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $ins->bindValue(':did', $design_id ?: null, $design_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
    $ins->bindValue(':msg', $message ?: null, $message !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $ins->bindValue(':payload', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $ins->execute();

    // Get contractor display name
    $nameStmt = $db->prepare("SELECT CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) AS name FROM users WHERE id = :id");
    $nameStmt->bindValue(':id', $contractor_id, PDO::PARAM_INT);
    $nameStmt->execute();
    $name = ($row = $nameStmt->fetch(PDO::FETCH_ASSOC)) ? trim($row['name']) : 'Contractor';

    echo json_encode(['success' => true, 'message' => 'Layout sent to contractor', 'contractor_name' => $name]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error sending to contractor: ' . $e->getMessage()]);
}


