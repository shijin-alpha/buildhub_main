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

    // Handle worker assignments
    $workers = [];
    if ($isMultipart && isset($_POST['workers'])) {
        $workers = $_POST['workers'];
        
        // Validate worker data
        foreach ($workers as $index => $worker) {
            $worker_id = isset($worker['worker_id']) ? (int)$worker['worker_id'] : 0;
            $hours_worked = isset($worker['hours_worked']) ? (float)$worker['hours_worked'] : 8;
            $overtime_hours = isset($worker['overtime_hours']) ? (float)$worker['overtime_hours'] : 0;
            $daily_wage = isset($worker['daily_wage']) ? (float)$worker['daily_wage'] : 0;
            
            if ($worker_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid worker ID']);
                exit;
            }
            
            if ($hours_worked <= 0 || $hours_worked > 16) {
                echo json_encode(['success' => false, 'message' => 'Work hours must be between 1-16 hours']);
                exit;
            }
            
            if ($overtime_hours < 0 || $overtime_hours > 8) {
                echo json_encode(['success' => false, 'message' => 'Overtime hours must be between 0-8 hours']);
                exit;
            }
            
            // Verify worker belongs to this contractor
            $workerCheck = $db->prepare("
                SELECT id, worker_name, daily_wage 
                FROM contractor_workers 
                WHERE id = :worker_id AND contractor_id = :contractor_id AND is_available = 1
            ");
            $workerCheck->bindValue(':worker_id', $worker_id, PDO::PARAM_INT);
            $workerCheck->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
            $workerCheck->execute();
            $workerData = $workerCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$workerData) {
                echo json_encode(['success' => false, 'message' => 'Worker not found or not available']);
                exit;
            }
            
            $workers[$index] = [
                'worker_id' => $worker_id,
                'hours_worked' => $hours_worked,
                'overtime_hours' => $overtime_hours,
                'daily_wage' => $daily_wage,
                'work_description' => $worker['work_description'] ?? ''
            ];
        }
    }

    // Handle geo photo IDs (these are already uploaded)
    $geo_photo_ids = [];
    if ($isMultipart && isset($_POST['geo_photo_ids'])) {
        $geo_photo_ids = is_array($_POST['geo_photo_ids']) ? $_POST['geo_photo_ids'] : [$_POST['geo_photo_ids']];
        
        // Validate geo photo IDs belong to this project and contractor
        if (!empty($geo_photo_ids)) {
            $placeholders = str_repeat('?,', count($geo_photo_ids) - 1) . '?';
            $geoPhotoCheck = $db->prepare("
                SELECT id FROM geo_photos 
                WHERE id IN ($placeholders) 
                AND project_id = ? 
                AND contractor_id = ?
            ");
            $params = array_merge($geo_photo_ids, [$project_id, $contractor_id]);
            $geoPhotoCheck->execute($params);
            $validGeoPhotos = $geoPhotoCheck->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($validGeoPhotos) !== count($geo_photo_ids)) {
                echo json_encode(['success' => false, 'message' => 'Invalid geo photo IDs provided']);
                exit;
            }
        }
    }

    // Validate mandatory photo for completed stages (including geo photos)
    $total_photos = count($photo_paths) + count($geo_photo_ids);
    if ($stage_status === 'Completed' && $total_photos === 0) {
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

        // Insert worker assignments
        if (!empty($workers)) {
            $workerStmt = $db->prepare("
                INSERT INTO progress_worker_assignments (
                    progress_update_id, worker_id, work_date, hours_worked, 
                    overtime_hours, daily_wage, overtime_rate, work_description
                ) VALUES (
                    :progress_update_id, :worker_id, CURDATE(), :hours_worked,
                    :overtime_hours, :daily_wage, :overtime_rate, :work_description
                )
            ");
            
            foreach ($workers as $worker) {
                $overtime_rate = $worker['daily_wage'] / 8 * 1.5; // 1.5x overtime rate
                
                $workerStmt->bindValue(':progress_update_id', $progress_update_id, PDO::PARAM_INT);
                $workerStmt->bindValue(':worker_id', $worker['worker_id'], PDO::PARAM_INT);
                $workerStmt->bindValue(':hours_worked', $worker['hours_worked'], PDO::PARAM_STR);
                $workerStmt->bindValue(':overtime_hours', $worker['overtime_hours'], PDO::PARAM_STR);
                $workerStmt->bindValue(':daily_wage', $worker['daily_wage'], PDO::PARAM_STR);
                $workerStmt->bindValue(':overtime_rate', $overtime_rate, PDO::PARAM_STR);
                $workerStmt->bindValue(':work_description', $worker['work_description'], PDO::PARAM_STR);
                
                if (!$workerStmt->execute()) {
                    error_log("Failed to insert worker assignment: " . print_r($workerStmt->errorInfo(), true));
                }
            }
        }

        // Link geo photos to this progress update
        if (!empty($geo_photo_ids)) {
            $geoLinkStmt = $db->prepare("
                UPDATE geo_photos 
                SET progress_update_id = :progress_update_id 
                WHERE id = :geo_photo_id
            ");
            
            foreach ($geo_photo_ids as $geo_photo_id) {
                $geoLinkStmt->bindValue(':progress_update_id', $progress_update_id, PDO::PARAM_INT);
                $geoLinkStmt->bindValue(':geo_photo_id', $geo_photo_id, PDO::PARAM_INT);
                $geoLinkStmt->execute();
            }
        }

        // Create notification for homeowner
        $notification_type = $stage_status === 'Completed' ? 'stage_completed' : 'progress_update';
        $notification_title = $stage_status === 'Completed' 
            ? "Stage Completed: {$stage_name}" 
            : "Progress Update: {$stage_name}";
        
        $notification_message = "Contractor has updated progress for {$stage_name} stage. ";
        $notification_message .= "Status: {$stage_status}, Progress: {$completion_percentage}%";
        
        // Add photo information
        $total_photos = count($photo_paths) + count($geo_photo_ids);
        if ($total_photos > 0) {
            $photo_details = [];
            if (count($photo_paths) > 0) {
                $photo_details[] = count($photo_paths) . " regular photo(s)";
            }
            if (count($geo_photo_ids) > 0) {
                $photo_details[] = count($geo_photo_ids) . " geo-tagged photo(s)";
            }
            $notification_message .= ". Includes " . implode(" and ", $photo_details);
        }
        
        // Add worker information to notification
        if (!empty($workers)) {
            $worker_count = count($workers);
            $total_cost = 0;
            foreach ($workers as $worker) {
                $regular_pay = ($worker['hours_worked'] / 8) * $worker['daily_wage'];
                $overtime_pay = $worker['overtime_hours'] * ($worker['daily_wage'] / 8) * 1.5;
                $total_cost += $regular_pay + $overtime_pay;
            }
            $notification_message .= ". Work team: {$worker_count} worker(s), Total cost: ₹" . number_format($total_cost, 2);
        }

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

        // Associate geo photos with this progress update
        if (!empty($geo_photo_ids)) {
            foreach ($geo_photo_ids as $geo_photo_id) {
                $geoPhotoAssocStmt = $db->prepare("
                    UPDATE geo_photos 
                    SET progress_update_id = ?, 
                        is_included_in_progress = TRUE,
                        progress_association_date = NOW()
                    WHERE id = ? AND project_id = ? AND contractor_id = ?
                ");
                $geoPhotoAssocStmt->execute([$progress_update_id, $geo_photo_id, $project_id, $contractor_id]);
            }
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Progress update submitted successfully',
            'data' => [
                'progress_update_id' => $progress_update_id,
                'photos_uploaded' => count($photo_paths),
                'geo_photos_included' => count($geo_photo_ids),
                'total_photos' => count($photo_paths) + count($geo_photo_ids),
                'workers_assigned' => count($workers),
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