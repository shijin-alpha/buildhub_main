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
    $architect_id = $_SESSION['user_id'] ?? null;
    
    if (!$architect_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Architect not authenticated'
        ]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = $_POST; }
    
    // Validate required fields
    if (empty($input['layout_request_id']) || empty($input['title'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: layout_request_id and title'
        ]);
        exit;
    }
    
    $layout_request_id = $input['layout_request_id'];
    $project_id = $input['project_id'] ?? null;
    $title = $input['title'];
    $description = $input['description'] ?? '';
    $rooms_data = $input['rooms_data'] ?? '[]';
    $plot_dimensions = $input['plot_dimensions'] ?? '{}';
    $scale_ratio = $input['scale_ratio'] ?? 1.2;
    $total_area = $input['total_area'] ?? 0;
    $construction_area = $input['construction_area'] ?? 0;
    
    // Integration-specific fields
    $technical_details = $input['technical_details'] ?? '{}';
    $workflow_stage = $input['workflow_stage'] ?? 'draft';
    $milestone_id = $input['milestone_id'] ?? null;
    $integration_data = $input['integration_data'] ?? '{}';
    
    // Get project information if not provided
    if (!$project_id && $layout_request_id) {
        $project_query = "SELECT id FROM projects WHERE layout_request_id = :layout_request_id LIMIT 1";
        $project_stmt = $db->prepare($project_query);
        $project_stmt->execute([':layout_request_id' => $layout_request_id]);
        $project_result = $project_stmt->fetch(PDO::FETCH_ASSOC);
        $project_id = $project_result['id'] ?? null;
    }
    
    // Get layout request details for integration
    $request_query = "
        SELECT 
            lr.*,
            p.homeowner_id,
            p.project_name,
            p.enable_house_plans,
            p.enable_geo_photos,
            p.enable_progress_reports
        FROM layout_requests lr
        LEFT JOIN projects p ON lr.id = p.layout_request_id
        WHERE lr.id = :layout_request_id
        AND lr.id IN (
            SELECT layout_request_id 
            FROM layout_request_assignments 
            WHERE architect_id = :architect_id
        )
    ";
    
    $request_stmt = $db->prepare($request_query);
    $request_stmt->execute([
        ':layout_request_id' => $layout_request_id,
        ':architect_id' => $architect_id
    ]);
    
    $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request_data) {
        echo json_encode([
            'success' => false,
            'message' => 'Layout request not found or not assigned to this architect'
        ]);
        exit;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Create the integrated house plan
        $house_plan_query = "
            INSERT INTO house_plans (
                architect_id, layout_request_id, project_id, milestone_id,
                title, description, rooms_data, plot_dimensions, 
                scale_ratio, total_area, construction_area,
                technical_details, workflow_stage, integration_data,
                status, created_at
            ) VALUES (
                :architect_id, :layout_request_id, :project_id, :milestone_id,
                :title, :description, :rooms_data, :plot_dimensions,
                :scale_ratio, :total_area, :construction_area,
                :technical_details, :workflow_stage, :integration_data,
                'draft', NOW()
            )
        ";
        
        $house_plan_stmt = $db->prepare($house_plan_query);
        $house_plan_stmt->execute([
            ':architect_id' => $architect_id,
            ':layout_request_id' => $layout_request_id,
            ':project_id' => $project_id,
            ':milestone_id' => $milestone_id,
            ':title' => $title,
            ':description' => $description,
            ':rooms_data' => is_string($rooms_data) ? $rooms_data : json_encode($rooms_data),
            ':plot_dimensions' => is_string($plot_dimensions) ? $plot_dimensions : json_encode($plot_dimensions),
            ':scale_ratio' => $scale_ratio,
            ':total_area' => $total_area,
            ':construction_area' => $construction_area,
            ':technical_details' => is_string($technical_details) ? $technical_details : json_encode($technical_details),
            ':workflow_stage' => $workflow_stage,
            ':integration_data' => is_string($integration_data) ? $integration_data : json_encode($integration_data)
        ]);
        
        $house_plan_id = $db->lastInsertId();
        
        // Update project with active house plan
        if ($project_id) {
            $update_project_query = "
                UPDATE projects 
                SET active_house_plan_id = :house_plan_id,
                    updated_at = NOW()
                WHERE id = :project_id
            ";
            
            $update_project_stmt = $db->prepare($update_project_query);
            $update_project_stmt->execute([
                ':house_plan_id' => $house_plan_id,
                ':project_id' => $project_id
            ]);
        }
        
        // Update milestone if specified
        if ($milestone_id) {
            $update_milestone_query = "
                UPDATE project_milestones 
                SET house_plan_id = :house_plan_id,
                    status = 'in_progress',
                    actual_start_date = CURDATE(),
                    updated_at = NOW()
                WHERE id = :milestone_id AND project_id = :project_id
            ";
            
            $update_milestone_stmt = $db->prepare($update_milestone_query);
            $update_milestone_stmt->execute([
                ':house_plan_id' => $house_plan_id,
                ':milestone_id' => $milestone_id,
                ':project_id' => $project_id
            ]);
        }
        
        // Update layout request workflow status
        $update_request_query = "
            UPDATE layout_requests 
            SET workflow_status = 'design_phase',
                updated_at = NOW()
            WHERE id = :layout_request_id
        ";
        
        $update_request_stmt = $db->prepare($update_request_query);
        $update_request_stmt->execute([':layout_request_id' => $layout_request_id]);
        
        // Update feature integration usage
        if ($project_id) {
            $update_feature_query = "
                UPDATE feature_integrations 
                SET is_active = 1,
                    total_usage_count = total_usage_count + 1,
                    last_used_at = NOW()
                WHERE project_id = :project_id AND feature_name = 'house_plan_designer'
            ";
            
            $update_feature_stmt = $db->prepare($update_feature_query);
            $update_feature_stmt->execute([':project_id' => $project_id]);
        }
        
        // Create workflow notification for homeowner
        $homeowner_id = $request_data['homeowner_id'];
        $project_name = $request_data['project_name'] ?? 'Your Construction Project';
        
        $notification_title = "🏠 New House Plan Created";
        $notification_message = "Your architect has created a new house plan for {$project_name}.\n\n";
        $notification_message .= "📋 Plan Details:\n";
        $notification_message .= "• Title: {$title}\n";
        $notification_message .= "• Total Area: {$total_area} sq ft\n";
        $notification_message .= "• Construction Area: {$construction_area} sq ft\n";
        $notification_message .= "• Status: " . ucfirst($workflow_stage) . "\n\n";
        
        if ($request_data['enable_geo_photos']) {
            $notification_message .= "📍 Geo-tagged photos will be linked to this plan during construction.\n";
        }
        if ($request_data['enable_progress_reports']) {
            $notification_message .= "📊 Progress reports will reference this approved design.\n";
        }
        
        $notification_message .= "\n🎯 Next Steps:\n";
        $notification_message .= "1. Review the house plan design\n";
        $notification_message .= "2. Provide feedback or approve the plan\n";
        $notification_message .= "3. Technical details will be finalized\n";
        $notification_message .= "4. Construction can begin with integrated tracking\n";
        
        // Insert workflow notification
        $notification_query = "
            INSERT INTO workflow_notifications (
                project_id, user_id, notification_type, title, message,
                action_required, action_url, house_plan_id,
                notification_metadata, created_at
            ) VALUES (
                :project_id, :user_id, 'house_plan_update', :title, :message,
                1, :action_url, :house_plan_id, :metadata, NOW()
            )
        ";
        
        $action_url = "/homeowner-dashboard?tab=house_plans&plan_id={$house_plan_id}";
        $metadata = json_encode([
            'house_plan_id' => $house_plan_id,
            'architect_id' => $architect_id,
            'workflow_stage' => $workflow_stage,
            'integration_features' => [
                'geo_photos' => $request_data['enable_geo_photos'],
                'progress_reports' => $request_data['enable_progress_reports']
            ]
        ]);
        
        $notification_stmt = $db->prepare($notification_query);
        $notification_stmt->execute([
            ':project_id' => $project_id,
            ':user_id' => $homeowner_id,
            ':title' => $notification_title,
            ':message' => $notification_message,
            ':action_url' => $action_url,
            ':house_plan_id' => $house_plan_id,
            ':metadata' => $metadata
        ]);
        
        // Send inbox message to homeowner
        $inbox_message_query = "
            INSERT INTO inbox_messages (
                user_id, sender_type, title, message, metadata,
                is_read, created_at
            ) VALUES (
                :user_id, 'architect', :title, :message, :metadata,
                0, NOW()
            )
        ";
        
        $inbox_metadata = json_encode([
            'type' => 'house_plan_created',
            'house_plan_id' => $house_plan_id,
            'project_id' => $project_id,
            'architect_id' => $architect_id
        ]);
        
        $inbox_stmt = $db->prepare($inbox_message_query);
        $inbox_stmt->execute([
            ':user_id' => $homeowner_id,
            ':title' => $notification_title,
            ':message' => $notification_message,
            ':metadata' => $inbox_metadata
        ]);
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Integrated house plan created successfully',
            'house_plan_id' => $house_plan_id,
            'project_id' => $project_id,
            'workflow_stage' => $workflow_stage,
            'integration_features' => [
                'geo_photos_enabled' => (bool)$request_data['enable_geo_photos'],
                'progress_reports_enabled' => (bool)$request_data['enable_progress_reports'],
                'milestone_linked' => !is_null($milestone_id)
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
    error_log("Integrated house plan creation error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error creating integrated house plan: ' . $e->getMessage()
    ]);
}
?>