<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    session_start();
    $architect_id = $_SESSION['user_id'] ?? null;

    if (!$architect_id) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    $plan_id = isset($input['plan_id']) ? (int)$input['plan_id'] : 0;
    $plan_name = trim($input['plan_name'] ?? '');
    $plot_width = floatval($input['plot_width'] ?? 0);
    $plot_height = floatval($input['plot_height'] ?? 0);
    $plan_data = $input['plan_data'] ?? [];
    $notes = trim($input['notes'] ?? '');
    $status = $input['status'] ?? 'draft';

    if ($plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Plan ID is required']);
        exit;
    }

    // Verify plan belongs to this architect
    $checkStmt = $db->prepare("SELECT id, status FROM house_plans WHERE id = :id AND architect_id = :aid");
    $checkStmt->execute([':id' => $plan_id, ':aid' => $architect_id]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Plan not found or access denied']);
        exit;
    }

    // Calculate total area from plan_data
    $total_area = 0;
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
        }
    }
    
    // Also calculate construction area if available
    $construction_area = 0;
    if (isset($plan_data['total_construction_area'])) {
        $construction_area = floatval($plan_data['total_construction_area']);
    } elseif (isset($plan_data['rooms']) && is_array($plan_data['rooms'])) {
        foreach ($plan_data['rooms'] as $room) {
            $actual_width = 0;
            $actual_height = 0;
            
            if (isset($room['actual_width']) && isset($room['actual_height'])) {
                $actual_width = floatval($room['actual_width']);
                $actual_height = floatval($room['actual_height']);
            } elseif (isset($room['layout_width']) && isset($room['layout_height'])) {
                // Use scale ratio if available
                $scale_ratio = isset($plan_data['scale_ratio']) ? floatval($plan_data['scale_ratio']) : 1.2;
                $actual_width = floatval($room['layout_width']) * $scale_ratio;
                $actual_height = floatval($room['layout_height']) * $scale_ratio;
            }
            
            $construction_area += $actual_width * $actual_height;
        }
    }

    // Update house plan
    $updateFields = [];
    $params = [':id' => $plan_id, ':aid' => $architect_id];

    if (!empty($plan_name)) {
        $updateFields[] = "plan_name = :plan_name";
        $params[':plan_name'] = $plan_name;
    }

    if ($plot_width > 0) {
        $updateFields[] = "plot_width = :plot_width";
        $params[':plot_width'] = $plot_width;
    }

    if ($plot_height > 0) {
        $updateFields[] = "plot_height = :plot_height";
        $params[':plot_height'] = $plot_height;
    }

    if (!empty($plan_data)) {
        $updateFields[] = "plan_data = :plan_data";
        $updateFields[] = "total_area = :total_area";
        $params[':plan_data'] = json_encode($plan_data);
        $params[':total_area'] = $construction_area > 0 ? $construction_area : $total_area;
    }

    if (!empty($notes)) {
        $updateFields[] = "notes = :notes";
        $params[':notes'] = $notes;
    }

    if (in_array($status, ['draft', 'submitted'])) {
        $updateFields[] = "status = :status";
        $params[':status'] = $status;
    }

    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }

    $sql = "UPDATE house_plans SET " . implode(', ', $updateFields) . " WHERE id = :id AND architect_id = :aid";
    $stmt = $db->prepare($sql);
    $success = $stmt->execute($params);

    if ($success && $stmt->rowCount() > 0) {
        // Get homeowner_id if this plan is linked to a layout request
        $homeowner_id = null;
        $layout_request_id = null;
        
        $planInfoStmt = $db->prepare("SELECT layout_request_id FROM house_plans WHERE id = :id");
        $planInfoStmt->execute([':id' => $plan_id]);
        $planInfo = $planInfoStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($planInfo && $planInfo['layout_request_id']) {
            $layout_request_id = $planInfo['layout_request_id'];
            $homeownerStmt = $db->prepare("SELECT homeowner_id FROM layout_requests WHERE id = :id");
            $homeownerStmt->execute([':id' => $layout_request_id]);
            $homeowner_result = $homeownerStmt->fetch(PDO::FETCH_ASSOC);
            if ($homeowner_result) {
                $homeowner_id = $homeowner_result['homeowner_id'];
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'House plan updated successfully',
            'plan_id' => $plan_id,
            'homeowner_id' => $homeowner_id,
            'layout_request_id' => $layout_request_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or plan not found']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>