<?php
header('Content-Type: application/json');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin) { 
    header('Access-Control-Allow-Origin: ' . $origin); 
    header('Vary: Origin'); 
} else { 
    header('Access-Control-Allow-Origin: http://localhost:3000'); 
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

    // Handle both multipart form data (with files) and JSON data
    $isMultipart = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false;
    
    if ($isMultipart) {
        // Extract form data
        $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
        $contractor_id = isset($_POST['contractor_id']) ? (int)$_POST['contractor_id'] : 0;
        $stage_name = trim($_POST['stage_name'] ?? '');
        $stage_status = trim($_POST['stage_status'] ?? '');
        $completion_percentage = isset($_POST['completion_percentage']) ? (float)$_POST['completion_percentage'] : 0;
        $remarks = trim($_POST['remarks'] ?? '');
        $delay_reason = !empty($_POST['delay_reason']) ? trim($_POST['delay_reason']) : null;
        $delay_description = !empty($_POST['delay_description']) ? trim($_POST['delay_description']) : null;
        $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    } else {
        // Handle JSON input
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $project_id = isset($input['project_id']) ? (int)$input['project_id'] : 0;
        $contractor_id = isset($input['contractor_id']) ? (int)$input['contractor_id'] : 0;
        $stage_name = trim($input['stage_name'] ?? '');
        $stage_status = trim($input['stage_status'] ?? '');
        $completion_percentage = isset($input['completion_percentage']) ? (float)$input['completion_percentage'] : 0;
        $remarks = trim($input['remarks'] ?? '');
        $delay_reason = !empty($input['delay_reason']) ? trim($input['delay_reason']) : null;
        $delay_description = !empty($input['delay_description']) ? trim($input['delay_description']) : null;
        $latitude = !empty($input['latitude']) ? (float)$input['latitude'] : null;
        $longitude = !empty($input['longitude']) ? (float)$input['longitude'] : null;
    }

    // Validation
    if ($project_id <= 0 || $contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing project_id or contractor_id']);
        exit;
    }

    $valid_stages = ['Foundation', 'Structure', 'Brickwork', 'Roofing', 'Electrical', 'Plumbing', 'Finishing', 'Other'];
    $valid_statuses = ['Not Started', 'In Progress', 'Completed'];
    $valid_delay_reasons = ['Weather', 'Material Delay', 'Labor Shortage', 'Design Change', 'Client Request', 'Other'];

    if (!in_array($stage_name, $valid_stages)) {
        echo json_encode(['success' => false, 'message' => 'Invalid stage name']);
        exit;
    }

    if (!in_array($stage_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid stage status']);
        exit;
    }

    if ($completion_percentage < 0 || $completion_percentage > 100) {
        echo json_encode(['success' => false, 'message' => 'Completion percentage must be between 0 and 100']);
        exit;
    }

    if ($delay_reason && !in_array($delay_reason, $valid_delay_reasons)) {
        echo json_encode(['success' => false, 'message' => 'Invalid delay reason']);
        exit;
    }

    // Verify contractor is assigned to this project
    $projectCheck = $db->prepare("
        SELECT cse.id, cse.homeowner_id, cse.contractor_id 
        FROM contractor_send_estimates cse 
        WHERE cse.id = :project_id AND cse.contractor_id = :contractor_id
        LIMIT 1
    ");
    $projectCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $projectCheck->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $projectCheck->execute();
    $project = $projectCheck->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found or contractor not assigned']);
        exit;
    }

    $homeowner_id = $project['homeowner_id'];

    // Check if completion percentage is decreasing (not allowed)
    $lastProgressCheck = $db->prepare("
        SELECT completion_percentage 
        FROM construction_progress_updates 
        WHERE project_id = :project_id AND contractor_id = :contractor_id 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $lastProgressCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $lastProgressCheck->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $lastProgressCheck->execute();
    $lastProgress = $lastProgressCheck->fetch(PDO::FETCH_ASSOC);

    if ($lastProgress && $completion_percentage < $lastProgress['completion_percentage']) {
        echo json_encode(['success' => false, 'message' => 'Progress percentage cannot decrease from previous update']);
        exit;
    }

    // Handle photo uploads
    $photo_paths = [];
    if ($isMultipart && isset($_FILES['photos'])) {
        $upload_dir = __DIR__ . '/../../uploads/progress_photos/' . $project_id . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        // Handle multiple files
        $files = $_FILES['photos'];
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $file_ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    
                    if (!in_array($file_ext, $allowed_extensions)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG allowed']);
                        exit;
                    }

                    if ($files['size'][$i] > $max_file_size) {
                        echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed']);
                        exit;
                    }

                    $filename = uniqid() . '_' . time() . '.' . $file_ext;
                    $file_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($files['tmp_name'][$i], $file_path)) {
                        $photo_paths[] = '/uploads/progress_photos/' . $project_id . '/' . $filename;
                    }
                }
            }
        } else {
            // Single file
            if ($files['error'] === UPLOAD_ERR_OK) {
                $file_ext = strtolower(pathinfo($files['name'], PATHINFO_EXTENSION));
                
                if (!in_array($file_ext, $allowed_extensions)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG allowed']);
                    exit;
                }

                if ($files['size'] > $max_file_size) {
                    echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed']);
                    exit;
                }

                $filename = uniqid() . '_' . time() . '.' . $file_ext;
                $file_path = $upload_dir . $filename;
                
                if (move_uploaded_file($files['tmp_name'], $file_path)) {
                    $photo_paths[] = '/uploads/progress_photos/' . $project_id . '/' . $filename;
                }
            }
        }
    }

    // Validate mandatory photo for completed stages
    if ($stage_status === 'Completed' && empty($photo_paths)) {
        echo json_encode(['success' => false, 'message' => 'At least one photo is required for completed stages']);
        exit;
    }

    // Verify location if project location is set
    $location_verified = false;
    if ($latitude && $longitude) {
        $locationCheck = $db->prepare("
            SELECT latitude, longitude, radius_meters 
            FROM project_locations 
            WHERE project_id = :project_id
        ");
        $locationCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
        $locationCheck->execute();
        $projectLocation = $locationCheck->fetch(PDO::FETCH_ASSOC);

        if ($projectLocation) {
            // Calculate distance using Haversine formula
            $earth_radius = 6371000; // meters
            $lat1 = deg2rad($projectLocation['latitude']);
            $lon1 = deg2rad($projectLocation['longitude']);
            $lat2 = deg2rad($latitude);
            $lon2 = deg2rad($longitude);

            $dlat = $lat2 - $lat1;
            $dlon = $lon2 - $lon1;

            $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            $distance = $earth_radius * $c;

            $allowed_radius = $projectLocation['radius_meters'] ?: 100;
            $location_verified = $distance <= $allowed_radius;
        }
    }

    // Insert progress update
    $stmt = $db->prepare("
        INSERT INTO construction_progress_updates (
            project_id, contractor_id, homeowner_id, stage_name, stage_status, 
            completion_percentage, remarks, delay_reason, delay_description, 
            photo_paths, latitude, longitude, location_verified
        ) VALUES (
            :project_id, :contractor_id, :homeowner_id, :stage_name, :stage_status,
            :completion_percentage, :remarks, :delay_reason, :delay_description,
            :photo_paths, :latitude, :longitude, :location_verified
        )
    ");

    $stmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $stmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $stmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $stmt->bindValue(':stage_name', $stage_name, PDO::PARAM_STR);
    $stmt->bindValue(':stage_status', $stage_status, PDO::PARAM_STR);
    $stmt->bindValue(':completion_percentage', $completion_percentage, PDO::PARAM_STR);
    $stmt->bindValue(':remarks', $remarks, PDO::PARAM_STR);
    $stmt->bindValue(':delay_reason', $delay_reason, PDO::PARAM_STR);
    $stmt->bindValue(':delay_description', $delay_description, PDO::PARAM_STR);
    $stmt->bindValue(':photo_paths', json_encode($photo_paths), PDO::PARAM_STR);
    $stmt->bindValue(':latitude', $latitude, PDO::PARAM_STR);
    $stmt->bindValue(':longitude', $longitude, PDO::PARAM_STR);
    $stmt->bindValue(':location_verified', $location_verified, PDO::PARAM_BOOL);

    if ($stmt->execute()) {
        $progress_update_id = $db->lastInsertId();

        // Create notification for homeowner
        $notification_type = $stage_status === 'Completed' ? 'stage_completed' : 'progress_update';
        $notification_title = $stage_status === 'Completed' 
            ? "Stage Completed: {$stage_name}" 
            : "Progress Update: {$stage_name}";
        
        $notification_message = "Contractor has updated progress for {$stage_name} stage. ";
        $notification_message .= "Status: {$stage_status}, Progress: {$completion_percentage}%";
        if ($remarks) {
            $notification_message .= ". Remarks: " . substr($remarks, 0, 100);
        }

        $notificationStmt = $db->prepare("
            INSERT INTO progress_notifications (
                progress_update_id, homeowner_id, contractor_id, type, title, message
            ) VALUES (
                :progress_update_id, :homeowner_id, :contractor_id, :type, :title, :message
            )
        ");

        $notificationStmt->bindValue(':progress_update_id', $progress_update_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':type', $notification_type, PDO::PARAM_STR);
        $notificationStmt->bindValue(':title', $notification_title, PDO::PARAM_STR);
        $notificationStmt->bindValue(':message', $notification_message, PDO::PARAM_STR);
        $notificationStmt->execute();

        echo json_encode([
            'success' => true, 
            'message' => 'Progress update submitted successfully',
            'data' => [
                'progress_update_id' => $progress_update_id,
                'photos_uploaded' => count($photo_paths),
                'location_verified' => $location_verified
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit progress update']);
    }

} catch (Exception $e) {
    error_log("Progress update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>