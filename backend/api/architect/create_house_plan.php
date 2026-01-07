<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    session_start();
    $architect_id = $_SESSION['user_id'] ?? null;

    if (!$architect_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated. Please log in again.']);
        exit;
    }

    // Verify user is architect
    $userStmt = $db->prepare("SELECT role FROM users WHERE id = :id");
    $userStmt->execute([':id' => $architect_id]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'architect') {
        echo json_encode(['success' => false, 'message' => 'Access denied. User is not an architect.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input: ' . json_last_error_msg()]);
        exit;
    }
    
    $plan_name = trim($input['plan_name'] ?? '');
    $layout_request_id = isset($input['layout_request_id']) ? (int)$input['layout_request_id'] : null;
    $plot_width = floatval($input['plot_width'] ?? 0);
    $plot_height = floatval($input['plot_height'] ?? 0);
    $plan_data = $input['plan_data'] ?? [];
    $notes = trim($input['notes'] ?? '');

    if (empty($plan_name) || $plot_width <= 0 || $plot_height <= 0) {
        echo json_encode(['success' => false, 'message' => 'Plan name, plot width and height are required and must be greater than 0']);
        exit;
    }

    // Validate plan_data structure
    if (!isset($plan_data['rooms']) || !is_array($plan_data['rooms'])) {
        echo json_encode(['success' => false, 'message' => 'Plan data must contain a rooms array']);
        exit;
    }

    // Calculate total area from plan_data
    $total_area = 0;
    $construction_area = 0;
    
    if (isset($plan_data['rooms']) && is_array($plan_data['rooms'])) {
        foreach ($plan_data['rooms'] as $room) {
            // Handle both old format (width/height) and new format (layout_width/layout_height)
            $room_width = 0;
            $room_height = 0;
            
            if (isset($room['layout_width']) && isset($room['layout_height'])) {
                $room_width = floatval($room['layout_width']);
                $room_height = floatval($room['layout_height']);
            } elseif (isset($room['width']) && isset($room['height'])) {
                $room_width = floatval($room['width']);
                $room_height = floatval($room['height']);
            }
            
            $total_area += $room_width * $room_height;
            
            // Calculate construction area
            $actual_width = 0;
            $actual_height = 0;
            
            if (isset($room['actual_width']) && isset($room['actual_height'])) {
                $actual_width = floatval($room['actual_width']);
                $actual_height = floatval($room['actual_height']);
            } elseif ($room_width > 0 && $room_height > 0) {
                // Use scale ratio if available
                $scale_ratio = isset($plan_data['scale_ratio']) ? floatval($plan_data['scale_ratio']) : 1.2;
                $actual_width = $room_width * $scale_ratio;
                $actual_height = $room_height * $scale_ratio;
            }
            
            $construction_area += $actual_width * $actual_height;
        }
    }
    
    // Use construction area if available, otherwise use layout area
    $final_area = $construction_area > 0 ? $construction_area : $total_area;

    // If linked to a request, verify architect has access
    if ($layout_request_id) {
        $accessStmt = $db->prepare("
            SELECT 1 FROM layout_request_assignments 
            WHERE layout_request_id = :lrid AND architect_id = :aid AND status = 'accepted'
        ");
        $accessStmt->execute([':lrid' => $layout_request_id, ':aid' => $architect_id]);
        
        if (!$accessStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'No access to this layout request']);
            exit;
        }
    }

    // Create house plan
    $stmt = $db->prepare("
        INSERT INTO house_plans (architect_id, layout_request_id, plan_name, plot_width, plot_height, plan_data, total_area, notes)
        VALUES (:architect_id, :layout_request_id, :plan_name, :plot_width, :plot_height, :plan_data, :total_area, :notes)
    ");

    $success = $stmt->execute([
        ':architect_id' => $architect_id,
        ':layout_request_id' => $layout_request_id,
        ':plan_name' => $plan_name,
        ':plot_width' => $plot_width,
        ':plot_height' => $plot_height,
        ':plan_data' => json_encode($plan_data),
        ':total_area' => $final_area,
        ':notes' => $notes
    ]);

    if ($success) {
        $plan_id = $db->lastInsertId();
        
        // Get homeowner_id if this plan is linked to a layout request
        $homeowner_id = null;
        if ($layout_request_id) {
            $homeownerStmt = $db->prepare("SELECT homeowner_id FROM layout_requests WHERE id = :id");
            $homeownerStmt->execute([':id' => $layout_request_id]);
            $homeowner_result = $homeownerStmt->fetch(PDO::FETCH_ASSOC);
            if ($homeowner_result) {
                $homeowner_id = $homeowner_result['homeowner_id'];
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'House plan created successfully',
            'plan_id' => $plan_id,
            'homeowner_id' => $homeowner_id,
            'layout_request_id' => $layout_request_id
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        echo json_encode(['success' => false, 'message' => 'Failed to create house plan: ' . $errorInfo[2]]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>