<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../utils/notification_helper.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    session_start();
    $contractor_id = $_SESSION['user_id'] ?? $_POST['contractor_id'] ?? null;
    
    if (!$contractor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Contractor not authenticated'
        ]);
        exit;
    }
    
    // Get form data
    $project_id = $_POST['project_id'] ?? null;
    $stage_name = $_POST['stage_name'] ?? null;
    $stage_status = $_POST['stage_status'] ?? null;
    $completion_percentage = $_POST['completion_percentage'] ?? null;
    $remarks = $_POST['remarks'] ?? '';
    $delay_reason = $_POST['delay_reason'] ?? null;
    $delay_description = $_POST['delay_description'] ?? null;
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    
    // Enhanced worker management data
    $selected_workers = json_decode($_POST['selected_workers'] ?? '[]', true);
    $work_duration = json_decode($_POST['work_duration'] ?? '{}', true);
    $total_labour_cost = floatval($_POST['total_labour_cost'] ?? 0);
    $geo_photo_ids = $_POST['geo_photo_ids'] ?? [];
    
    // Validate required fields
    if (!$project_id || !$stage_name || !$stage_status || $completion_percentage === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    if (empty($selected_workers)) {
        echo json_encode([
            'success' => false,
            'message' => 'At least one worker must be selected'
        ]);
        exit;
    }
    
    // Create enhanced progress updates table if it doesn't exist
    $create_table_query = "
        CREATE TABLE IF NOT EXISTS enhanced_progress_updates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            contractor_id INT NOT NULL,
            stage_name VARCHAR(100) NOT NULL,
            stage_status ENUM('Not Started', 'In Progress', 'Completed') NOT NULL,
            completion_percentage DECIMAL(5,2) NOT NULL,
            remarks TEXT,
            delay_reason VARCHAR(100),
            delay_description TEXT,
            
            -- Worker management fields
            selected_workers JSON NOT NULL,
            work_duration JSON NOT NULL,
            total_labour_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
            worker_performance_notes TEXT,
            
            -- Location data
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            location_address TEXT,
            
            -- Photo references
            regular_photos JSON,
            geo_photo_ids JSON,
            
            -- Timestamps
            work_start_date DATE,
            work_end_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Foreign keys
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (contractor_id) REFERENCES users(id) ON DELETE CASCADE,
            
            -- Indexes
            INDEX idx_project_stage (project_id, stage_name),
            INDEX idx_contractor (contractor_id),
            INDEX idx_status (stage_status),
            INDEX idx_completion (completion_percentage),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->exec($create_table_query);
    
    // Create worker assignments table for detailed tracking
    $create_worker_table_query = "
        CREATE TABLE IF NOT EXISTS progress_worker_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            progress_update_id INT NOT NULL,
            worker_id INT NOT NULL,
            worker_name VARCHAR(255) NOT NULL,
            worker_type VARCHAR(100) NOT NULL,
            worker_category VARCHAR(50) NOT NULL,
            worker_level ENUM('supervisor', 'specialist', 'skilled', 'semi_skilled', 'apprentice', 'laborer') NOT NULL,
            
            -- Wage and cost tracking
            base_wage DECIMAL(8,2) NOT NULL,
            actual_wage DECIMAL(8,2) NOT NULL,
            hours_worked DECIMAL(5,2) NOT NULL,
            days_worked DECIMAL(4,1) NOT NULL,
            total_cost DECIMAL(10,2) NOT NULL,
            
            -- Performance tracking
            performance_rating DECIMAL(3,1) DEFAULT NULL,
            attendance_status ENUM('present', 'absent', 'partial') DEFAULT 'present',
            work_quality_notes TEXT,
            
            -- Timestamps
            work_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            -- Foreign keys
            FOREIGN KEY (progress_update_id) REFERENCES enhanced_progress_updates(id) ON DELETE CASCADE,
            
            -- Indexes
            INDEX idx_progress_update (progress_update_id),
            INDEX idx_worker (worker_id),
            INDEX idx_work_date (work_date),
            INDEX idx_worker_type (worker_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $db->exec($create_worker_table_query);
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Handle photo uploads
        $uploaded_photos = [];
        if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
            $upload_dir = '../../uploads/progress_photos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['photos']['type'][$i];
                    $file_size = $_FILES['photos']['size'][$i];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception("Invalid file type: {$file_type}");
                    }
                    
                    if ($file_size > $max_size) {
                        throw new Exception("File too large: " . ($_FILES['photos']['name'][$i]));
                    }
                    
                    $file_extension = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
                    $new_filename = 'progress_' . $project_id . '_' . time() . '_' . $i . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $upload_path)) {
                        $uploaded_photos[] = [
                            'filename' => $new_filename,
                            'original_name' => $_FILES['photos']['name'][$i],
                            'size' => $file_size,
                            'type' => $file_type
                        ];
                    }
                }
            }
        }
        
        // Calculate work dates
        $work_start_date = date('Y-m-d');
        $work_end_date = date('Y-m-d', strtotime("+{$work_duration['days']} days"));
        
        // Insert enhanced progress update
        $insert_query = "
            INSERT INTO enhanced_progress_updates (
                project_id, contractor_id, stage_name, stage_status, completion_percentage,
                remarks, delay_reason, delay_description, selected_workers, work_duration,
                total_labour_cost, latitude, longitude, regular_photos, geo_photo_ids,
                work_start_date, work_end_date
            ) VALUES (
                :project_id, :contractor_id, :stage_name, :stage_status, :completion_percentage,
                :remarks, :delay_reason, :delay_description, :selected_workers, :work_duration,
                :total_labour_cost, :latitude, :longitude, :regular_photos, :geo_photo_ids,
                :work_start_date, :work_end_date
            )
        ";
        
        $stmt = $db->prepare($insert_query);
        $stmt->execute([
            ':project_id' => $project_id,
            ':contractor_id' => $contractor_id,
            ':stage_name' => $stage_name,
            ':stage_status' => $stage_status,
            ':completion_percentage' => $completion_percentage,
            ':remarks' => $remarks,
            ':delay_reason' => $delay_reason,
            ':delay_description' => $delay_description,
            ':selected_workers' => json_encode($selected_workers),
            ':work_duration' => json_encode($work_duration),
            ':total_labour_cost' => $total_labour_cost,
            ':latitude' => $latitude,
            ':longitude' => $longitude,
            ':regular_photos' => json_encode($uploaded_photos),
            ':geo_photo_ids' => json_encode($geo_photo_ids),
            ':work_start_date' => $work_start_date,
            ':work_end_date' => $work_end_date
        ]);
        
        $progress_update_id = $db->lastInsertId();
        
        // Insert worker assignments with detailed tracking
        foreach ($selected_workers as $worker) {
            $daily_wage = floatval($worker['base_wage']);
            $hours_per_day = floatval($work_duration['hours_per_day']);
            $days = floatval($work_duration['days']);
            $hourly_wage = $daily_wage / 8; // Assuming 8-hour standard day
            $total_worker_cost = $hourly_wage * $hours_per_day * $days;
            
            $worker_insert_query = "
                INSERT INTO progress_worker_assignments (
                    progress_update_id, worker_id, worker_name, worker_type, worker_category,
                    worker_level, base_wage, actual_wage, hours_worked, days_worked,
                    total_cost, work_date
                ) VALUES (
                    :progress_update_id, :worker_id, :worker_name, :worker_type, :worker_category,
                    :worker_level, :base_wage, :actual_wage, :hours_worked, :days_worked,
                    :total_cost, :work_date
                )
            ";
            
            $worker_stmt = $db->prepare($worker_insert_query);
            $worker_stmt->execute([
                ':progress_update_id' => $progress_update_id,
                ':worker_id' => $worker['id'],
                ':worker_name' => $worker['name'],
                ':worker_type' => $worker['type'],
                ':worker_category' => $worker['category'],
                ':worker_level' => $worker['level'],
                ':base_wage' => $daily_wage,
                ':actual_wage' => $daily_wage, // Can be different if overtime/bonus
                ':hours_worked' => $hours_per_day * $days,
                ':days_worked' => $days,
                ':total_cost' => $total_worker_cost,
                ':work_date' => $work_start_date
            ]);
        }
        
        // Update project progress if this is the latest update
        $update_project_query = "
            UPDATE projects 
            SET 
                current_stage = :stage_name,
                completion_percentage = :completion_percentage,
                total_labour_cost = COALESCE(total_labour_cost, 0) + :labour_cost,
                last_update_date = NOW(),
                updated_at = NOW()
            WHERE id = :project_id
        ";
        
        $project_stmt = $db->prepare($update_project_query);
        $project_stmt->execute([
            ':stage_name' => $stage_name,
            ':completion_percentage' => $completion_percentage,
            ':labour_cost' => $total_labour_cost,
            ':project_id' => $project_id
        ]);
        
        // Link geo photos to this progress update
        if (!empty($geo_photo_ids)) {
            foreach ($geo_photo_ids as $geo_photo_id) {
                $link_geo_query = "
                    UPDATE geo_photos 
                    SET progress_update_id = :progress_update_id,
                        updated_at = NOW()
                    WHERE id = :geo_photo_id
                ";
                
                $geo_stmt = $db->prepare($link_geo_query);
                $geo_stmt->execute([
                    ':progress_update_id' => $progress_update_id,
                    ':geo_photo_id' => $geo_photo_id
                ]);
            }
        }
        
        // Get homeowner information for notification
        $homeowner_query = "
            SELECT u.id as homeowner_id, u.name as homeowner_name, u.email as homeowner_email,
                   p.project_name
            FROM projects p
            JOIN users u ON p.homeowner_id = u.id
            WHERE p.id = :project_id
        ";
        
        $homeowner_stmt = $db->prepare($homeowner_query);
        $homeowner_stmt->execute([':project_id' => $project_id]);
        $homeowner_info = $homeowner_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($homeowner_info) {
            // Create comprehensive notification message
            $notification_title = "🚀 Enhanced Progress Update: {$stage_name}";
            $notification_message = "Your construction project has been updated with enhanced details:\n\n";
            $notification_message .= "📋 Stage: {$stage_name} ({$stage_status})\n";
            $notification_message .= "📊 Completion: {$completion_percentage}%\n";
            $notification_message .= "👷‍♂️ Workers Assigned: " . count($selected_workers) . "\n";
            $notification_message .= "💰 Labour Cost: ₹" . number_format($total_labour_cost, 0) . "\n";
            $notification_message .= "📅 Duration: {$work_duration['days']} days × {$work_duration['hours_per_day']} hours/day\n\n";
            
            if (!empty($uploaded_photos)) {
                $notification_message .= "📷 Regular Photos: " . count($uploaded_photos) . "\n";
            }
            
            if (!empty($geo_photo_ids)) {
                $notification_message .= "📍 Geo-Tagged Photos: " . count($geo_photo_ids) . "\n";
            }
            
            if ($remarks) {
                $notification_message .= "\n📝 Work Description:\n{$remarks}\n";
            }
            
            if ($delay_reason) {
                $notification_message .= "\n⚠️ Delay Information:\n";
                $notification_message .= "Reason: {$delay_reason}\n";
                if ($delay_description) {
                    $notification_message .= "Details: {$delay_description}\n";
                }
            }
            
            $notification_message .= "\n🔍 Worker Details:\n";
            foreach ($selected_workers as $worker) {
                $notification_message .= "• {$worker['name']} ({$worker['type']}) - ₹{$worker['base_wage']}/day\n";
            }
            
            // Send notification to homeowner
            $notification_metadata = [
                'type' => 'enhanced_progress_update',
                'progress_update_id' => $progress_update_id,
                'project_id' => $project_id,
                'stage_name' => $stage_name,
                'completion_percentage' => $completion_percentage,
                'worker_count' => count($selected_workers),
                'labour_cost' => $total_labour_cost,
                'photo_count' => count($uploaded_photos),
                'geo_photo_count' => count($geo_photo_ids)
            ];
            
            // Insert into inbox messages
            $inbox_query = "
                INSERT INTO inbox_messages (
                    user_id, sender_type, title, message, metadata, is_read, created_at
                ) VALUES (
                    :user_id, 'contractor', :title, :message, :metadata, 0, NOW()
                )
            ";
            
            $inbox_stmt = $db->prepare($inbox_query);
            $inbox_stmt->execute([
                ':user_id' => $homeowner_info['homeowner_id'],
                ':title' => $notification_title,
                ':message' => $notification_message,
                ':metadata' => json_encode($notification_metadata)
            ]);
        }
        
        // Commit transaction
        $db->commit();
        
        // Prepare response data
        $response_data = [
            'progress_update_id' => $progress_update_id,
            'project_id' => $project_id,
            'stage_name' => $stage_name,
            'stage_status' => $stage_status,
            'completion_percentage' => $completion_percentage,
            'worker_assignments' => count($selected_workers),
            'total_labour_cost' => $total_labour_cost,
            'work_duration' => $work_duration,
            'photos_uploaded' => count($uploaded_photos),
            'geo_photos_linked' => count($geo_photo_ids),
            'notification_sent' => isset($homeowner_info)
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Enhanced progress update submitted successfully with worker management and cost tracking',
            'data' => $response_data
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Enhanced progress update error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting enhanced progress update: ' . $e->getMessage()
    ]);
}
?>