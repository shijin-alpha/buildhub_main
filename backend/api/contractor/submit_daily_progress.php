<?php
/**
 * Submit Daily Progress Update API
 * Handles comprehensive daily progress tracking with labour and materials
 */

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
        $update_date = trim($_POST['update_date'] ?? date('Y-m-d'));
        $construction_stage = trim($_POST['construction_stage'] ?? '');
        $work_done_today = trim($_POST['work_done_today'] ?? '');
        $incremental_completion = isset($_POST['incremental_completion_percentage']) ? (float)$_POST['incremental_completion_percentage'] : 0;
        $working_hours = isset($_POST['working_hours']) ? (float)$_POST['working_hours'] : 8.0;
        $materials_used = trim($_POST['materials_used'] ?? '');
        $weather_condition = trim($_POST['weather_condition'] ?? '');
        $site_issues = trim($_POST['site_issues'] ?? '');
        $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
        
        // Parse labour data (JSON string from form)
        $labour_data = isset($_POST['labour_data']) ? json_decode($_POST['labour_data'], true) : [];
    } else {
        // Handle JSON input
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $project_id = isset($input['project_id']) ? (int)$input['project_id'] : 0;
        $contractor_id = isset($input['contractor_id']) ? (int)$input['contractor_id'] : 0;
        $update_date = trim($input['update_date'] ?? date('Y-m-d'));
        $construction_stage = trim($input['construction_stage'] ?? '');
        $work_done_today = trim($input['work_done_today'] ?? '');
        $incremental_completion = isset($input['incremental_completion_percentage']) ? (float)$input['incremental_completion_percentage'] : 0;
        $working_hours = isset($input['working_hours']) ? (float)$input['working_hours'] : 8.0;
        $materials_used = trim($input['materials_used'] ?? '');
        $weather_condition = trim($input['weather_condition'] ?? '');
        $site_issues = trim($input['site_issues'] ?? '');
        $latitude = !empty($input['latitude']) ? (float)$input['latitude'] : null;
        $longitude = !empty($input['longitude']) ? (float)$input['longitude'] : null;
        $labour_data = $input['labour_data'] ?? [];
    }

    // Validation
    if ($project_id <= 0 || $contractor_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing project_id or contractor_id']);
        exit;
    }

    if (empty($construction_stage) || empty($work_done_today) || empty($weather_condition)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: construction_stage, work_done_today, weather_condition']);
        exit;
    }

    $valid_stages = ['Foundation', 'Structure', 'Brickwork', 'Roofing', 'Electrical', 'Plumbing', 'Finishing', 'Other'];
    $valid_weather = ['Sunny', 'Cloudy', 'Rainy', 'Stormy', 'Foggy', 'Hot', 'Cold', 'Windy'];

    if (!in_array($construction_stage, $valid_stages)) {
        echo json_encode(['success' => false, 'message' => 'Invalid construction stage']);
        exit;
    }

    if (!in_array($weather_condition, $valid_weather)) {
        echo json_encode(['success' => false, 'message' => 'Invalid weather condition']);
        exit;
    }

    if ($incremental_completion < 0 || $incremental_completion > 100) {
        echo json_encode(['success' => false, 'message' => 'Incremental completion percentage must be between 0 and 100']);
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

    // Check if daily update already exists for this date
    $existingCheck = $db->prepare("
        SELECT id FROM daily_progress_updates 
        WHERE project_id = :project_id AND contractor_id = :contractor_id AND update_date = :update_date
    ");
    $existingCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $existingCheck->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $existingCheck->bindValue(':update_date', $update_date, PDO::PARAM_STR);
    $existingCheck->execute();

    if ($existingCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Daily update already exists for this date. Each day can have only one update.']);
        exit;
    }

    // Calculate cumulative progress
    $lastProgressCheck = $db->prepare("
        SELECT cumulative_completion_percentage 
        FROM daily_progress_updates 
        WHERE project_id = :project_id AND contractor_id = :contractor_id 
        ORDER BY update_date DESC 
        LIMIT 1
    ");
    $lastProgressCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $lastProgressCheck->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $lastProgressCheck->execute();
    $lastProgress = $lastProgressCheck->fetch(PDO::FETCH_ASSOC);

    $previous_cumulative = $lastProgress ? $lastProgress['cumulative_completion_percentage'] : 0;
    $cumulative_completion = min(100, $previous_cumulative + $incremental_completion);

    // Validate that progress doesn't decrease
    if ($cumulative_completion < $previous_cumulative) {
        echo json_encode(['success' => false, 'message' => 'Cumulative progress cannot decrease from previous updates']);
        exit;
    }

    // Handle photo uploads
    $progress_photos = [];
    if ($isMultipart && isset($_FILES['progress_photos'])) {
        $upload_dir = __DIR__ . '/../../uploads/daily_progress/' . $project_id . '/' . $update_date . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        // Handle multiple files
        $files = $_FILES['progress_photos'];
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
                        $progress_photos[] = [
                            'path' => '/uploads/daily_progress/' . $project_id . '/' . $update_date . '/' . $filename,
                            'timestamp' => date('Y-m-d H:i:s'),
                            'original_name' => $files['name'][$i]
                        ];
                    }
                }
            }
        }
    }

    // Validate mandatory photos for high completion claims
    if ($incremental_completion >= 10 && empty($progress_photos)) {
        echo json_encode(['success' => false, 'message' => 'Progress photos are mandatory for completion claims of 10% or more']);
        exit;
    }

    // Verify location if available
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

    // Begin transaction
    $db->beginTransaction();

    try {
        // Insert daily progress update
        $stmt = $db->prepare("
            INSERT INTO daily_progress_updates (
                project_id, contractor_id, homeowner_id, update_date, construction_stage, 
                work_done_today, incremental_completion_percentage, cumulative_completion_percentage,
                working_hours, materials_used, weather_condition, site_issues, 
                progress_photos, latitude, longitude, location_verified
            ) VALUES (
                :project_id, :contractor_id, :homeowner_id, :update_date, :construction_stage,
                :work_done_today, :incremental_completion, :cumulative_completion,
                :working_hours, :materials_used, :weather_condition, :site_issues,
                :progress_photos, :latitude, :longitude, :location_verified
            )
        ");

        $stmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
        $stmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
        $stmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
        $stmt->bindValue(':update_date', $update_date, PDO::PARAM_STR);
        $stmt->bindValue(':construction_stage', $construction_stage, PDO::PARAM_STR);
        $stmt->bindValue(':work_done_today', $work_done_today, PDO::PARAM_STR);
        $stmt->bindValue(':incremental_completion', $incremental_completion, PDO::PARAM_STR);
        $stmt->bindValue(':cumulative_completion', $cumulative_completion, PDO::PARAM_STR);
        $stmt->bindValue(':working_hours', $working_hours, PDO::PARAM_STR);
        $stmt->bindValue(':materials_used', $materials_used, PDO::PARAM_STR);
        $stmt->bindValue(':weather_condition', $weather_condition, PDO::PARAM_STR);
        $stmt->bindValue(':site_issues', $site_issues, PDO::PARAM_STR);
        $stmt->bindValue(':progress_photos', json_encode($progress_photos), PDO::PARAM_STR);
        $stmt->bindValue(':latitude', $latitude, PDO::PARAM_STR);
        $stmt->bindValue(':longitude', $longitude, PDO::PARAM_STR);
        $stmt->bindValue(':location_verified', $location_verified, PDO::PARAM_BOOL);

        $stmt->execute();
        $daily_progress_id = $db->lastInsertId();

        // Insert labour tracking data
        if (!empty($labour_data)) {
            $labourStmt = $db->prepare("
                INSERT INTO daily_labour_tracking (
                    daily_progress_id, worker_type, worker_count, hours_worked, 
                    overtime_hours, absent_count, hourly_rate, total_wages,
                    productivity_rating, safety_compliance, remarks
                ) VALUES (
                    :daily_progress_id, :worker_type, :worker_count, :hours_worked,
                    :overtime_hours, :absent_count, :hourly_rate, :total_wages,
                    :productivity_rating, :safety_compliance, :remarks
                )
            ");

            foreach ($labour_data as $labour) {
                $labourStmt->bindValue(':daily_progress_id', $daily_progress_id, PDO::PARAM_INT);
                $labourStmt->bindValue(':worker_type', $labour['worker_type'] ?? 'Other', PDO::PARAM_STR);
                $labourStmt->bindValue(':worker_count', $labour['worker_count'] ?? 0, PDO::PARAM_INT);
                $labourStmt->bindValue(':hours_worked', $labour['hours_worked'] ?? 8.0, PDO::PARAM_STR);
                $labourStmt->bindValue(':overtime_hours', $labour['overtime_hours'] ?? 0.0, PDO::PARAM_STR);
                $labourStmt->bindValue(':absent_count', $labour['absent_count'] ?? 0, PDO::PARAM_INT);
                $labourStmt->bindValue(':hourly_rate', $labour['hourly_rate'] ?? 0.0, PDO::PARAM_STR);
                $labourStmt->bindValue(':total_wages', $labour['total_wages'] ?? 0.0, PDO::PARAM_STR);
                $labourStmt->bindValue(':productivity_rating', $labour['productivity_rating'] ?? 5, PDO::PARAM_INT);
                $labourStmt->bindValue(':safety_compliance', $labour['safety_compliance'] ?? 'good', PDO::PARAM_STR);
                $labourStmt->bindValue(':remarks', $labour['remarks'] ?? '', PDO::PARAM_STR);
                $labourStmt->bindValue(':overtime_hours', $labour['overtime_hours'] ?? 0.0, PDO::PARAM_STR);
                $labourStmt->bindValue(':absent_count', $labour['absent_count'] ?? 0, PDO::PARAM_INT);
                $labourStmt->bindValue(':remarks', $labour['remarks'] ?? '', PDO::PARAM_STR);
                $labourStmt->execute();
            }
        }

        // Create notification for homeowner
        $notification_title = "Daily Progress Update - {$construction_stage}";
        $notification_message = "Contractor has submitted daily progress update for {$update_date}. ";
        $notification_message .= "Stage: {$construction_stage}, Progress: +{$incremental_completion}% (Total: {$cumulative_completion}%)";

        $notificationStmt = $db->prepare("
            INSERT INTO enhanced_progress_notifications (
                project_id, contractor_id, homeowner_id, notification_type, 
                reference_id, title, message
            ) VALUES (
                :project_id, :contractor_id, :homeowner_id, 'daily_update',
                :reference_id, :title, :message
            )
        ");

        $notificationStmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':reference_id', $daily_progress_id, PDO::PARAM_INT);
        $notificationStmt->bindValue(':title', $notification_title, PDO::PARAM_STR);
        $notificationStmt->bindValue(':message', $notification_message, PDO::PARAM_STR);
        $notificationStmt->execute();

        // Commit transaction
        $db->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Daily progress update submitted successfully',
            'data' => [
                'daily_progress_id' => $daily_progress_id,
                'cumulative_progress' => $cumulative_completion,
                'incremental_progress' => $incremental_completion,
                'photos_uploaded' => count($progress_photos),
                'labour_entries' => count($labour_data),
                'location_verified' => $location_verified
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Daily progress update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred: ' . $e->getMessage()]);
}
?>