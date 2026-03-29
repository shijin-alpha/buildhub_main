<?php
/**
 * Submit contractor stage completion or daily report
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

// Check if user is logged in and is a contractor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contractor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $contractor_id = $_SESSION['user_id'];
    
    // Extract form data (multipart form data for file uploads)
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $stage_name = trim($_POST['stage_name'] ?? '');
    $submission_type = trim($_POST['submission_type'] ?? 'daily_report');
    $work_description = trim($_POST['work_description'] ?? '');
    $completion_percentage = isset($_POST['completion_percentage']) ? (float)$_POST['completion_percentage'] : 0;
    $materials_used = trim($_POST['materials_used'] ?? '');
    $labor_details = trim($_POST['labor_details'] ?? '');
    $challenges_faced = trim($_POST['challenges_faced'] ?? '');
    $next_day_plan = trim($_POST['next_day_plan'] ?? '');
    $quality_notes = trim($_POST['quality_notes'] ?? '');
    $safety_notes = trim($_POST['safety_notes'] ?? '');
    $weather_conditions = trim($_POST['weather_conditions'] ?? '');
    $worker_count = isset($_POST['worker_count']) ? (int)$_POST['worker_count'] : 0;
    $hours_worked = isset($_POST['hours_worked']) ? (float)$_POST['hours_worked'] : 0;
    $latitude = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    
    // Validation
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        exit;
    }
    
    if (empty($stage_name)) {
        echo json_encode(['success' => false, 'message' => 'Stage name is required']);
        exit;
    }
    
    if (empty($work_description)) {
        echo json_encode(['success' => false, 'message' => 'Work description is required']);
        exit;
    }
    
    if ($completion_percentage < 0 || $completion_percentage > 100) {
        echo json_encode(['success' => false, 'message' => 'Completion percentage must be between 0 and 100']);
        exit;
    }
    
    if (!in_array($submission_type, ['daily_report', 'stage_completion'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid submission type']);
        exit;
    }
    
    if ($submission_type === 'stage_completion' && $completion_percentage < 100) {
        echo json_encode(['success' => false, 'message' => 'Stage completion requires 100% completion']);
        exit;
    }
    
    // Verify contractor has access to this project
    $access_check = $db->prepare("SELECT id, project_name FROM construction_projects WHERE id = ? AND contractor_id = ?");
    $access_check->execute([$project_id, $contractor_id]);
    $project = $access_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this project']);
        exit;
    }
    
    // Get stage workflow information
    $stage_check = $db->prepare("SELECT * FROM construction_stage_workflow WHERE project_id = ? AND contractor_id = ? AND stage_name = ?");
    $stage_check->execute([$project_id, $contractor_id, $stage_name]);
    $stage_workflow = $stage_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$stage_workflow) {
        echo json_encode(['success' => false, 'message' => 'Stage workflow not found']);
        exit;
    }
    
    // Check if stage can be worked on (sequential progression)
    if ($submission_type === 'stage_completion') {
        // Verify all previous stages are completed
        $prev_stages_check = $db->prepare("
            SELECT COUNT(*) as incomplete_count 
            FROM construction_stage_workflow 
            WHERE project_id = ? AND contractor_id = ? AND stage_order < ? AND contractor_status != 'completed'
        ");
        $prev_stages_check->execute([$project_id, $contractor_id, $stage_workflow['stage_order']]);
        $incomplete_count = $prev_stages_check->fetchColumn();
        
        if ($incomplete_count > 0) {
            echo json_encode(['success' => false, 'message' => 'Previous stages must be completed first']);
            exit;
        }
        
        // Check if stage is already submitted for inspection
        if ($stage_workflow['contractor_status'] === 'submitted_for_inspection') {
            echo json_encode(['success' => false, 'message' => 'Stage is already submitted for inspection']);
            exit;
        }
        
        if ($stage_workflow['contractor_status'] === 'completed') {
            echo json_encode(['success' => false, 'message' => 'Stage is already completed']);
            exit;
        }
    }
    
    // Handle file uploads
    $photo_paths = [];
    $document_paths = [];
    
    // Create upload directories
    $upload_base_dir = __DIR__ . '/../../uploads/stage_submissions/' . $project_id . '/' . date('Y-m');
    if (!is_dir($upload_base_dir)) {
        mkdir($upload_base_dir, 0777, true);
    }
    
    // Handle photo uploads
    if (isset($_FILES['photos'])) {
        $photos = $_FILES['photos'];
        $allowed_photo_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $max_photo_size = 5 * 1024 * 1024; // 5MB
        
        if (is_array($photos['name'])) {
            for ($i = 0; $i < count($photos['name']); $i++) {
                if ($photos['error'][$i] === UPLOAD_ERR_OK) {
                    if (!in_array($photos['type'][$i], $allowed_photo_types)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid photo type. Only JPG, JPEG, PNG allowed']);
                        exit;
                    }
                    
                    if ($photos['size'][$i] > $max_photo_size) {
                        echo json_encode(['success' => false, 'message' => 'Photo size too large. Maximum 5MB allowed']);
                        exit;
                    }
                    
                    $file_ext = pathinfo($photos['name'][$i], PATHINFO_EXTENSION);
                    $file_name = 'photo_' . time() . '_' . $i . '.' . $file_ext;
                    $file_path = $upload_base_dir . '/' . $file_name;
                    
                    if (move_uploaded_file($photos['tmp_name'][$i], $file_path)) {
                        $photo_paths[] = 'uploads/stage_submissions/' . $project_id . '/' . date('Y-m') . '/' . $file_name;
                    }
                }
            }
        }
    }
    
    // Handle document uploads
    if (isset($_FILES['documents'])) {
        $documents = $_FILES['documents'];
        $allowed_doc_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $max_doc_size = 10 * 1024 * 1024; // 10MB
        
        if (is_array($documents['name'])) {
            for ($i = 0; $i < count($documents['name']); $i++) {
                if ($documents['error'][$i] === UPLOAD_ERR_OK) {
                    if (!in_array($documents['type'][$i], $allowed_doc_types)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid document type. Only PDF, DOC, DOCX allowed']);
                        exit;
                    }
                    
                    if ($documents['size'][$i] > $max_doc_size) {
                        echo json_encode(['success' => false, 'message' => 'Document size too large. Maximum 10MB allowed']);
                        exit;
                    }
                    
                    $file_ext = pathinfo($documents['name'][$i], PATHINFO_EXTENSION);
                    $file_name = 'doc_' . time() . '_' . $i . '.' . $file_ext;
                    $file_path = $upload_base_dir . '/' . $file_name;
                    
                    if (move_uploaded_file($documents['tmp_name'][$i], $file_path)) {
                        $document_paths[] = 'uploads/stage_submissions/' . $project_id . '/' . date('Y-m') . '/' . $file_name;
                    }
                }
            }
        }
    }
    
    // Prepare geo location
    $geo_location = null;
    if ($latitude && $longitude) {
        $geo_location = "POINT($longitude $latitude)";
    }
    
    $db->beginTransaction();
    
    try {
        // Insert contractor stage submission
        $submission_query = "INSERT INTO contractor_stage_submissions (
                               project_id, contractor_id, stage_name, submission_type, work_description,
                               completion_percentage, materials_used, labor_details, challenges_faced,
                               next_day_plan, quality_notes, safety_notes, photo_paths, document_paths,
                               geo_location, location_verified, weather_conditions, worker_count, hours_worked,
                               status
                             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, " . 
                             ($geo_location ? "ST_GeomFromText(?)" : "NULL") . ", ?, ?, ?, ?, 'submitted')";
        
        $submission_params = [
            $project_id, $contractor_id, $stage_name, $submission_type, $work_description,
            $completion_percentage, $materials_used, $labor_details, $challenges_faced,
            $next_day_plan, $quality_notes, $safety_notes, 
            json_encode($photo_paths), json_encode($document_paths)
        ];
        
        if ($geo_location) {
            $submission_params[] = $geo_location;
        }
        
        $submission_params = array_merge($submission_params, [
            $geo_location ? 1 : 0, $weather_conditions, $worker_count, $hours_worked
        ]);
        
        $submission_stmt = $db->prepare($submission_query);
        $submission_stmt->execute($submission_params);
        $submission_id = $db->lastInsertId();
        
        // Update stage workflow
        $new_contractor_status = $stage_workflow['contractor_status'];
        $new_completion_percentage = max($stage_workflow['stage_completion_percentage'], $completion_percentage);
        
        if ($submission_type === 'stage_completion') {
            $new_contractor_status = 'submitted_for_inspection';
            $new_completion_percentage = 100;
        } elseif ($stage_workflow['contractor_status'] === 'not_started') {
            $new_contractor_status = 'in_progress';
        }
        
        $workflow_update = $db->prepare("
            UPDATE construction_stage_workflow 
            SET contractor_status = ?, 
                stage_completion_percentage = ?,
                contractor_submission_id = ?,
                contractor_submitted_at = " . ($submission_type === 'stage_completion' ? 'CURRENT_TIMESTAMP' : 'contractor_submitted_at') . ",
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $workflow_update->execute([$new_contractor_status, $new_completion_percentage, $submission_id, $stage_workflow['id']]);
        
        // Update project current stage and completion percentage
        $project_update_query = "UPDATE construction_projects 
                               SET current_stage = (
                                 SELECT stage_name FROM construction_stage_workflow 
                                 WHERE project_id = ? AND contractor_id = ? 
                                 AND contractor_status IN ('in_progress', 'submitted_for_inspection')
                                 ORDER BY stage_order ASC LIMIT 1
                               ),
                               completion_percentage = (
                                 SELECT AVG(stage_completion_percentage) FROM construction_stage_workflow
                                 WHERE project_id = ? AND contractor_id = ?
                               ),
                               last_update_date = CURRENT_TIMESTAMP
                               WHERE id = ?";
        
        $project_update_stmt = $db->prepare($project_update_query);
        $project_update_stmt->execute([$project_id, $contractor_id, $project_id, $contractor_id, $project_id]);
        
        // Create notifications
        if ($submission_type === 'stage_completion') {
            // Notify admin/inspector for inspection
            $notification_query = "INSERT INTO stage_workflow_notifications (
                                     project_id, stage_workflow_id, recipient_id, recipient_type, 
                                     notification_type, title, message, related_submission_id, priority
                                   ) VALUES (?, ?, ?, 'admin', 'inspection_required', ?, ?, ?, 'high')";
            
            // Get admin users (assuming role 'admin' or user_id = 1)
            $admin_query = $db->prepare("SELECT id FROM users WHERE role = 'admin' OR id = 1 LIMIT 1");
            $admin_query->execute();
            $admin = $admin_query->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                $notification_title = "Stage Completion Inspection Required";
                $notification_message = "Stage '{$stage_name}' for project '{$project['project_name']}' has been submitted for inspection by the contractor.";
                
                $notification_stmt = $db->prepare($notification_query);
                $notification_stmt->execute([
                    $project_id, $stage_workflow['id'], $admin['id'], 
                    $notification_title, $notification_message, $submission_id
                ]);
            }
            
            // Notify homeowner that stage is pending inspection (but not completed yet)
            $homeowner_query = $db->prepare("SELECT homeowner_id FROM construction_projects WHERE id = ?");
            $homeowner_query->execute([$project_id]);
            $homeowner = $homeowner_query->fetch(PDO::FETCH_ASSOC);
            
            if ($homeowner) {
                $homeowner_notification_query = "INSERT INTO stage_workflow_notifications (
                                                   project_id, stage_workflow_id, recipient_id, recipient_type, 
                                                   notification_type, title, message, related_submission_id, priority
                                                 ) VALUES (?, ?, ?, 'homeowner', 'stage_submitted', ?, ?, ?, 'medium')";
                
                $homeowner_title = "Construction Stage Submitted for Inspection";
                $homeowner_message = "The contractor has completed stage '{$stage_name}' and submitted it for quality inspection. You will be notified once the inspection is approved.";
                
                $homeowner_stmt = $db->prepare($homeowner_notification_query);
                $homeowner_stmt->execute([
                    $project_id, $stage_workflow['id'], $homeowner['homeowner_id'], 
                    $homeowner_title, $homeowner_message, $submission_id
                ]);
            }
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $submission_type === 'stage_completion' ? 
                'Stage completion submitted successfully! Awaiting inspection approval.' : 
                'Daily report submitted successfully!',
            'submission_id' => $submission_id,
            'stage_status' => $new_contractor_status,
            'completion_percentage' => $new_completion_percentage
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Database error in submit_stage_completion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error in submit_stage_completion.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}
?>