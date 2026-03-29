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
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) { $input = $_POST; }
    
    // Validate required fields
    if (empty($input['plot_size']) || empty($input['budget_range'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: plot_size and budget_range'
        ]);
        exit;
    }
    
    // Extract all request data
    $plot_size = $input['plot_size'];
    $building_size = $input['building_size'] ?? null;
    $budget_range = $input['budget_range'];
    $requirements = $input['requirements'] ?? '';
    $location = $input['location'] ?? '';
    $timeline = $input['timeline'] ?? '';
    $selected_architect_ids = $input['selected_architect_ids'] ?? [];
    
    // New integrated features flags
    $requires_house_plan = $input['requires_house_plan'] ?? true;
    $enable_progress_tracking = $input['enable_progress_tracking'] ?? true;
    $enable_geo_photos = $input['enable_geo_photos'] ?? true;
    
    // Detailed requirements for house plan designer
    $house_plan_requirements = [
        'plot_shape' => $input['plot_shape'] ?? 'rectangular',
        'topography' => $input['topography'] ?? 'flat',
        'rooms' => $input['rooms'] ?? [],
        'floors' => $input['num_floors'] ?? 1,
        'style_preference' => $input['preferred_style'] ?? 'modern',
        'special_requirements' => $input['special_requirements'] ?? [],
        'vastu_compliance' => $input['vastu_compliance'] ?? false,
        'parking_requirements' => $input['parking_requirements'] ?? 'none'
    ];
    
    // Begin transaction for data consistency
    $db->beginTransaction();
    
    try {
        // 1. Create the main layout request
        $query = "INSERT INTO layout_requests (
                    user_id, homeowner_id, plot_size, building_size, budget_range, 
                    requirements, location, timeline, layout_type, status,
                    requires_house_plan, enable_progress_tracking, enable_geo_photos,
                    house_plan_requirements
                  ) VALUES (
                    :user_id, :homeowner_id, :plot_size, :building_size, :budget_range,
                    :requirements, :location, :timeline, 'custom', 'pending',
                    :requires_house_plan, :enable_progress_tracking, :enable_geo_photos,
                    :house_plan_requirements
                  )";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':user_id' => $homeowner_id,
            ':homeowner_id' => $homeowner_id,
            ':plot_size' => $plot_size,
            ':building_size' => $building_size,
            ':budget_range' => $budget_range,
            ':requirements' => $requirements,
            ':location' => $location,
            ':timeline' => $timeline,
            ':requires_house_plan' => $requires_house_plan ? 1 : 0,
            ':enable_progress_tracking' => $enable_progress_tracking ? 1 : 0,
            ':enable_geo_photos' => $enable_geo_photos ? 1 : 0,
            ':house_plan_requirements' => json_encode($house_plan_requirements)
        ]);
        
        $request_id = $db->lastInsertId();
        
        // 2. Create project entry for integrated tracking
        $project_query = "INSERT INTO projects (
                            homeowner_id, layout_request_id, project_name, status,
                            enable_house_plans, enable_geo_photos, enable_progress_reports,
                            created_at
                          ) VALUES (
                            :homeowner_id, :layout_request_id, :project_name, 'planning',
                            :enable_house_plans, :enable_geo_photos, :enable_progress_reports,
                            NOW()
                          )";
        
        $project_stmt = $db->prepare($project_query);
        $project_name = "Construction Project - " . $plot_size . " (" . $location . ")";
        
        $project_stmt->execute([
            ':homeowner_id' => $homeowner_id,
            ':layout_request_id' => $request_id,
            ':project_name' => $project_name,
            ':enable_house_plans' => $requires_house_plan ? 1 : 0,
            ':enable_geo_photos' => $enable_geo_photos ? 1 : 0,
            ':enable_progress_reports' => $enable_progress_tracking ? 1 : 0
        ]);
        
        $project_id = $db->lastInsertId();
        
        // 3. Assign to selected architects with integrated workflow instructions
        if (!empty($selected_architect_ids)) {
            foreach ($selected_architect_ids as $architect_id) {
                // Create architect assignment
                $assign_query = "INSERT INTO layout_request_assignments (
                                   layout_request_id, homeowner_id, architect_id, project_id,
                                   message, workflow_instructions, status
                                 ) VALUES (
                                   :layout_request_id, :homeowner_id, :architect_id, :project_id,
                                   :message, :workflow_instructions, 'sent'
                                 )";
                
                $workflow_instructions = [
                    'step_1' => 'Review client requirements and site details',
                    'step_2' => 'Create custom house plan using House Plan Designer',
                    'step_3' => 'Submit technical details and layout for client approval',
                    'step_4' => 'Coordinate with contractor for construction phase',
                    'features_enabled' => [
                        'house_plan_designer' => $requires_house_plan,
                        'geo_photo_tracking' => $enable_geo_photos,
                        'progress_reports' => $enable_progress_tracking
                    ]
                ];
                
                $assign_stmt = $db->prepare($assign_query);
                $assign_stmt->execute([
                    ':layout_request_id' => $request_id,
                    ':homeowner_id' => $homeowner_id,
                    ':architect_id' => $architect_id,
                    ':project_id' => $project_id,
                    ':message' => "New integrated construction project with house plan design requirements",
                    ':workflow_instructions' => json_encode($workflow_instructions)
                ]);
                
                // 4. Send notification to architect with feature integration info
                $notification_message = "🏗️ New Project Assignment: {$project_name}\n\n";
                $notification_message .= "📋 Requirements:\n";
                $notification_message .= "• Plot Size: {$plot_size}\n";
                $notification_message .= "• Budget: {$budget_range}\n";
                $notification_message .= "• Location: {$location}\n\n";
                
                if ($requires_house_plan) {
                    $notification_message .= "🏠 House Plan Designer: Use the interactive designer to create custom floor plans\n";
                }
                if ($enable_geo_photos) {
                    $notification_message .= "📍 Geo-Tagged Photos: Enable GPS photo documentation during construction\n";
                }
                if ($enable_progress_tracking) {
                    $notification_message .= "📊 Progress Reports: Submit detailed progress updates with photos\n";
                }
                
                $notification_message .= "\n🎯 Next Steps:\n";
                $notification_message .= "1. Review client requirements in detail\n";
                $notification_message .= "2. Create house plan using the House Plan Designer\n";
                $notification_message .= "3. Submit technical details and layout for approval\n";
                
                // Send inbox message to architect
                sendInboxMessage($db, $architect_id, 'system', 'New Integrated Project Assignment', $notification_message, [
                    'type' => 'project_assignment',
                    'request_id' => $request_id,
                    'project_id' => $project_id,
                    'features' => [
                        'house_plan_designer' => $requires_house_plan,
                        'geo_photos' => $enable_geo_photos,
                        'progress_reports' => $enable_progress_tracking
                    ]
                ]);
            }
        }
        
        // 5. Create initial project milestones for tracking
        $milestones = [
            ['name' => 'Requirements Review', 'phase' => 'planning', 'order' => 1],
            ['name' => 'House Plan Design', 'phase' => 'design', 'order' => 2],
            ['name' => 'Technical Approval', 'phase' => 'approval', 'order' => 3],
            ['name' => 'Construction Start', 'phase' => 'construction', 'order' => 4],
            ['name' => 'Progress Tracking', 'phase' => 'construction', 'order' => 5],
            ['name' => 'Final Completion', 'phase' => 'completion', 'order' => 6]
        ];
        
        foreach ($milestones as $milestone) {
            $milestone_query = "INSERT INTO project_milestones (
                                  project_id, milestone_name, phase, order_sequence, 
                                  status, created_at
                                ) VALUES (
                                  :project_id, :milestone_name, :phase, :order_sequence,
                                  'pending', NOW()
                                )";
            
            $milestone_stmt = $db->prepare($milestone_query);
            $milestone_stmt->execute([
                ':project_id' => $project_id,
                ':milestone_name' => $milestone['name'],
                ':phase' => $milestone['phase'],
                ':order_sequence' => $milestone['order']
            ]);
        }
        
        // 6. Send confirmation to homeowner
        $homeowner_message = "✅ Your construction request has been submitted successfully!\n\n";
        $homeowner_message .= "📋 Project Details:\n";
        $homeowner_message .= "• Project Name: {$project_name}\n";
        $homeowner_message .= "• Request ID: #{$request_id}\n";
        $homeowner_message .= "• Assigned Architects: " . count($selected_architect_ids) . "\n\n";
        
        $homeowner_message .= "🎯 What's Next:\n";
        $homeowner_message .= "1. Architects will review your requirements\n";
        if ($requires_house_plan) {
            $homeowner_message .= "2. Custom house plans will be created using our interactive designer\n";
        }
        $homeowner_message .= "3. You'll receive technical details and layouts for approval\n";
        $homeowner_message .= "4. Construction will begin with integrated progress tracking\n\n";
        
        $homeowner_message .= "🔔 You'll receive notifications for:\n";
        $homeowner_message .= "• House plan submissions and updates\n";
        $homeowner_message .= "• Technical detail approvals\n";
        if ($enable_geo_photos) {
            $homeowner_message .= "• GPS-tagged construction photos\n";
        }
        if ($enable_progress_tracking) {
            $homeowner_message .= "• Detailed progress reports with milestones\n";
        }
        
        sendInboxMessage($db, $homeowner_id, 'system', 'Construction Request Submitted', $homeowner_message, [
            'type' => 'request_confirmation',
            'request_id' => $request_id,
            'project_id' => $project_id
        ]);
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Enhanced construction request submitted successfully',
            'request_id' => $request_id,
            'project_id' => $project_id,
            'features_enabled' => [
                'house_plan_designer' => $requires_house_plan,
                'geo_photo_tracking' => $enable_geo_photos,
                'progress_reports' => $enable_progress_tracking
            ],
            'next_steps' => [
                'architects_notified' => count($selected_architect_ids),
                'milestones_created' => count($milestones),
                'workflow_initiated' => true
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Enhanced request submission error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting enhanced request: ' . $e->getMessage()
    ]);
}

// Helper function to send inbox messages
function sendInboxMessage($db, $user_id, $sender_type, $title, $message, $metadata = []) {
    try {
        $query = "INSERT INTO inbox_messages (
                    user_id, sender_type, title, message, metadata, 
                    is_read, created_at
                  ) VALUES (
                    :user_id, :sender_type, :title, :message, :metadata,
                    0, NOW()
                  )";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':sender_type' => $sender_type,
            ':title' => $title,
            ':message' => $message,
            ':metadata' => json_encode($metadata)
        ]);
        
        return $db->lastInsertId();
    } catch (Exception $e) {
        error_log("Failed to send inbox message: " . $e->getMessage());
        return false;
    }
}
?>