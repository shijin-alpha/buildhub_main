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
    $contractor_id = $_SESSION['user_id'] ?? null;
    
    if (!$contractor_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Contractor not authenticated'
        ]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = $_POST; }
    
    // Validate required fields
    if (empty($input['project_id']) || empty($input['report_title'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: project_id and report_title'
        ]);
        exit;
    }
    
    $project_id = $input['project_id'];
    $milestone_id = $input['milestone_id'] ?? null;
    $report_title = $input['report_title'];
    $report_description = $input['report_description'] ?? '';
    $work_completed = $input['work_completed'] ?? '';
    $work_planned = $input['work_planned'] ?? '';
    $completion_percentage = $input['completion_percentage'] ?? 0;
    
    // Integration-specific data
    $house_plan_updates = $input['house_plan_updates'] ?? '{}';
    $geo_photo_ids = $input['geo_photo_ids'] ?? '[]';
    $material_usage = $input['material_usage'] ?? '{}';
    $labor_details = $input['labor_details'] ?? '{}';
    $quality_checks = $input['quality_checks'] ?? '{}';
    $report_metadata = $input['report_metadata'] ?? '{}';
    
    // Get project information for integration
    $project_query = "
        SELECT 
            p.*,
            lr.house_plan_requirements,
            hp.id as active_house_plan_id,
            hp.title as house_plan_title,
            u.name as homeowner_name
        FROM projects p
        LEFT JOIN layout_requests lr ON p.layout_request_id = lr.id
        LEFT JOIN house_plans hp ON p.active_house_plan_id = hp.id
        LEFT JOIN users u ON p.homeowner_id = u.id
        WHERE p.id = :project_id
        AND (p.contractor_id = :contractor_id OR p.contractor_id IS NULL)
    ";
    
    $project_stmt = $db->prepare($project_query);
    $project_stmt->execute([
        ':project_id' => $project_id,
        ':contractor_id' => $contractor_id
    ]);
    
    $project_data = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project_data) {
        echo json_encode([
            'success' => false,
            'message' => 'Project not found or not assigned to this contractor'
        ]);
        exit;
    }
    
    // Assign contractor to project if not already assigned
    if (!$project_data['contractor_id']) {
        $assign_contractor_query = "
            UPDATE projects 
            SET contractor_id = :contractor_id,
                updated_at = NOW()
            WHERE id = :project_id
        ";
        
        $assign_stmt = $db->prepare($assign_contractor_query);
        $assign_stmt->execute([
            ':contractor_id' => $contractor_id,
            ':project_id' => $project_id
        ]);
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Create the integrated progress report
        $report_query = "
            INSERT INTO project_progress_reports (
                project_id, milestone_id, contractor_id, homeowner_id,
                report_title, report_description, work_completed, work_planned,
                completion_percentage, house_plan_updates, geo_photo_ids,
                material_usage, labor_details, quality_checks, report_metadata,
                report_status, submission_date, created_at
            ) VALUES (
                :project_id, :milestone_id, :contractor_id, :homeowner_id,
                :report_title, :report_description, :work_completed, :work_planned,
                :completion_percentage, :house_plan_updates, :geo_photo_ids,
                :material_usage, :labor_details, :quality_checks, :report_metadata,
                'submitted', NOW(), NOW()
            )
        ";
        
        $report_stmt = $db->prepare($report_query);
        $report_stmt->execute([
            ':project_id' => $project_id,
            ':milestone_id' => $milestone_id,
            ':contractor_id' => $contractor_id,
            ':homeowner_id' => $project_data['homeowner_id'],
            ':report_title' => $report_title,
            ':report_description' => $report_description,
            ':work_completed' => $work_completed,
            ':work_planned' => $work_planned,
            ':completion_percentage' => $completion_percentage,
            ':house_plan_updates' => is_string($house_plan_updates) ? $house_plan_updates : json_encode($house_plan_updates),
            ':geo_photo_ids' => is_string($geo_photo_ids) ? $geo_photo_ids : json_encode($geo_photo_ids),
            ':material_usage' => is_string($material_usage) ? $material_usage : json_encode($material_usage),
            ':labor_details' => is_string($labor_details) ? $labor_details : json_encode($labor_details),
            ':quality_checks' => is_string($quality_checks) ? $quality_checks : json_encode($quality_checks),
            ':report_metadata' => is_string($report_metadata) ? $report_metadata : json_encode($report_metadata)
        ]);
        
        $report_id = $db->lastInsertId();
        
        // Update milestone progress if specified
        if ($milestone_id) {
            $update_milestone_query = "
                UPDATE project_milestones 
                SET progress_report_id = :report_id,
                    progress_percentage = :completion_percentage,
                    status = CASE 
                        WHEN :completion_percentage >= 100 THEN 'completed'
                        WHEN :completion_percentage > 0 THEN 'in_progress'
                        ELSE status
                    END,
                    actual_end_date = CASE 
                        WHEN :completion_percentage >= 100 THEN CURDATE()
                        ELSE actual_end_date
                    END,
                    updated_at = NOW()
                WHERE id = :milestone_id AND project_id = :project_id
            ";
            
            $update_milestone_stmt = $db->prepare($update_milestone_query);
            $update_milestone_stmt->execute([
                ':report_id' => $report_id,
                ':completion_percentage' => $completion_percentage,
                ':milestone_id' => $milestone_id,
                ':project_id' => $project_id
            ]);
        }
        
        // Link geo photos to this report
        if (!empty($geo_photo_ids)) {
            $photo_ids = is_string($geo_photo_ids) ? json_decode($geo_photo_ids, true) : $geo_photo_ids;
            if (is_array($photo_ids) && !empty($photo_ids)) {
                $photo_ids_str = implode(',', array_map('intval', $photo_ids));
                $link_photos_query = "
                    UPDATE geo_photos 
                    SET progress_report_id = :report_id,
                        updated_at = NOW()
                    WHERE id IN ({$photo_ids_str}) 
                    AND project_id = :project_id
                    AND contractor_id = :contractor_id
                ";
                
                $link_photos_stmt = $db->prepare($link_photos_query);
                $link_photos_stmt->execute([
                    ':report_id' => $report_id,
                    ':project_id' => $project_id,
                    ':contractor_id' => $contractor_id
                ]);
            }
        }
        
        // Update project statistics
        $update_project_query = "
            UPDATE projects 
            SET total_progress_reports = total_progress_reports + 1,
                status = CASE 
                    WHEN :completion_percentage >= 100 THEN 'completed'
                    WHEN :completion_percentage > 0 THEN 'construction'
                    ELSE status
                END,
                updated_at = NOW()
            WHERE id = :project_id
        ";
        
        $update_project_stmt = $db->prepare($update_project_query);
        $update_project_stmt->execute([
            ':completion_percentage' => $completion_percentage,
            ':project_id' => $project_id
        ]);
        
        // Update feature integration usage
        $update_feature_query = "
            UPDATE feature_integrations 
            SET is_active = 1,
                total_usage_count = total_usage_count + 1,
                last_used_at = NOW()
            WHERE project_id = :project_id AND feature_name = 'progress_reports'
        ";
        
        $update_feature_stmt = $db->prepare($update_feature_query);
        $update_feature_stmt->execute([':project_id' => $project_id]);
        
        // Count geo photos in this report
        $geo_photo_count = 0;
        if (!empty($geo_photo_ids)) {
            $photo_ids = is_string($geo_photo_ids) ? json_decode($geo_photo_ids, true) : $geo_photo_ids;
            $geo_photo_count = is_array($photo_ids) ? count($photo_ids) : 0;
        }
        
        // Create workflow notification for homeowner
        $notification_title = "📊 New Progress Report Submitted";
        $notification_message = "A new progress report has been submitted for {$project_data['project_name']}.\n\n";
        $notification_message .= "📋 Report Details:\n";
        $notification_message .= "• Title: {$report_title}\n";
        $notification_message .= "• Completion: {$completion_percentage}%\n";
        $notification_message .= "• Geo Photos: {$geo_photo_count} photos\n";
        
        if ($project_data['active_house_plan_id']) {
            $notification_message .= "• House Plan: {$project_data['house_plan_title']}\n";
        }
        
        if ($milestone_id) {
            $milestone_query = "SELECT milestone_name FROM project_milestones WHERE id = :milestone_id";
            $milestone_stmt = $db->prepare($milestone_query);
            $milestone_stmt->execute([':milestone_id' => $milestone_id]);
            $milestone_data = $milestone_stmt->fetch(PDO::FETCH_ASSOC);
            if ($milestone_data) {
                $notification_message .= "• Milestone: {$milestone_data['milestone_name']}\n";
            }
        }
        
        $notification_message .= "\n📍 Integration Features:\n";
        if ($project_data['enable_geo_photos']) {
            $notification_message .= "• GPS-tagged photos included in report\n";
        }
        if ($project_data['enable_house_plans'] && $project_data['active_house_plan_id']) {
            $notification_message .= "• Progress tracked against approved house plan\n";
        }
        
        $notification_message .= "\n🎯 Next Steps:\n";
        $notification_message .= "1. Review the progress report details\n";
        $notification_message .= "2. Check geo-tagged photos for work verification\n";
        $notification_message .= "3. Approve or provide feedback on progress\n";
        
        // Insert workflow notification
        $notification_query = "
            INSERT INTO workflow_notifications (
                project_id, user_id, notification_type, title, message,
                action_required, action_url, progress_report_id,
                notification_metadata, created_at
            ) VALUES (
                :project_id, :user_id, 'progress_report', :title, :message,
                1, :action_url, :progress_report_id, :metadata, NOW()
            )
        ";
        
        $action_url = "/homeowner-dashboard?tab=progress_reports&report_id={$report_id}";
        $metadata = json_encode([
            'progress_report_id' => $report_id,
            'contractor_id' => $contractor_id,
            'completion_percentage' => $completion_percentage,
            'geo_photo_count' => $geo_photo_count,
            'milestone_id' => $milestone_id,
            'house_plan_id' => $project_data['active_house_plan_id']
        ]);
        
        $notification_stmt = $db->prepare($notification_query);
        $notification_stmt->execute([
            ':project_id' => $project_id,
            ':user_id' => $project_data['homeowner_id'],
            ':title' => $notification_title,
            ':message' => $notification_message,
            ':action_url' => $action_url,
            ':progress_report_id' => $report_id,
            ':metadata' => $metadata
        ]);
        
        // Send inbox message to homeowner
        $inbox_message_query = "
            INSERT INTO inbox_messages (
                user_id, sender_type, title, message, metadata,
                is_read, created_at
            ) VALUES (
                :user_id, 'contractor', :title, :message, :metadata,
                0, NOW()
            )
        ";
        
        $inbox_metadata = json_encode([
            'type' => 'progress_report_submitted',
            'progress_report_id' => $report_id,
            'project_id' => $project_id,
            'contractor_id' => $contractor_id,
            'completion_percentage' => $completion_percentage
        ]);
        
        $inbox_stmt = $db->prepare($inbox_message_query);
        $inbox_stmt->execute([
            ':user_id' => $project_data['homeowner_id'],
            ':title' => $notification_title,
            ':message' => $notification_message,
            ':metadata' => $inbox_metadata
        ]);
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Integrated progress report submitted successfully',
            'progress_report_id' => $report_id,
            'project_id' => $project_id,
            'completion_percentage' => $completion_percentage,
            'integration_features' => [
                'geo_photos_linked' => $geo_photo_count,
                'house_plan_referenced' => !is_null($project_data['active_house_plan_id']),
                'milestone_updated' => !is_null($milestone_id),
                'project_status_updated' => true
            ],
            'notifications_sent' => [
                'homeowner_notification' => true,
                'inbox_message' => true,
                'workflow_updated' => true
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Integrated progress report submission error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting integrated progress report: ' . $e->getMessage()
    ]);
}
?>