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
    
    $project_id = isset($input['project_id']) ? (int)$input['project_id'] : 0;
    $stage_name = trim($input['stage_name'] ?? '');
    $stage_status = trim($input['stage_status'] ?? '');
    $completion_percentage = isset($input['completion_percentage']) ? (float)$input['completion_percentage'] : 0;
    $remarks = trim($input['remarks'] ?? '');

    // Validation
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        exit;
    }

    $valid_stages = ['Foundation', 'Structure', 'Walls', 'Brickwork', 'Roofing', 'Finishing'];
    $valid_statuses = ['Not Started', 'In Progress', 'Completed'];

    if (!in_array($stage_name, $valid_stages)) {
        echo json_encode(['success' => false, 'message' => 'Invalid stage name']);
        exit;
    }

    if (!in_array($stage_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid stage status']);
        exit;
    }

    // Verify contractor is assigned to this project
    $projectCheck = $db->prepare("
        SELECT cse.id, cls.homeowner_id, cse.contractor_id 
        FROM contractor_send_estimates cse 
        INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
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

    // Check if there's an existing progress update for this stage
    $existingUpdate = $db->prepare("
        SELECT id, stage_status, completion_percentage 
        FROM construction_progress_updates 
        WHERE project_id = :project_id AND stage_name = :stage_name 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $existingUpdate->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $existingUpdate->bindValue(':stage_name', $stage_name, PDO::PARAM_STR);
    $existingUpdate->execute();
    $existing = $existingUpdate->fetch(PDO::FETCH_ASSOC);

    // Insert new progress update
    $insertUpdate = $db->prepare("
        INSERT INTO construction_progress_updates (
            project_id, contractor_id, homeowner_id, stage_name, stage_status, 
            completion_percentage, remarks, created_at
        ) VALUES (
            :project_id, :contractor_id, :homeowner_id, :stage_name, :stage_status,
            :completion_percentage, :remarks, NOW()
        )
    ");
    
    $insertUpdate->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $insertUpdate->bindValue(':contractor_id', $contractor_id, PDO::PARAM_INT);
    $insertUpdate->bindValue(':homeowner_id', $project['homeowner_id'], PDO::PARAM_INT);
    $insertUpdate->bindValue(':stage_name', $stage_name, PDO::PARAM_STR);
    $insertUpdate->bindValue(':stage_status', $stage_status, PDO::PARAM_STR);
    $insertUpdate->bindValue(':completion_percentage', $completion_percentage, PDO::PARAM_STR);
    $insertUpdate->bindValue(':remarks', $remarks, PDO::PARAM_STR);
    
    if ($insertUpdate->execute()) {
        $update_id = $db->lastInsertId();
        
        // Calculate overall project progress
        $stageOrder = ['Foundation', 'Structure', 'Walls', 'Roofing', 'Finishing'];
        $completedStages = [];
        
        foreach ($stageOrder as $stageName) {
            $stageCheck = $db->prepare("
                SELECT stage_status 
                FROM construction_progress_updates 
                WHERE project_id = :project_id AND stage_name = :stage_name 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stageCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
            $stageCheck->bindValue(':stage_name', $stageName, PDO::PARAM_STR);
            $stageCheck->execute();
            $stageData = $stageCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($stageData && $stageData['stage_status'] === 'Completed') {
                $completedStages[] = $stageName;
            }
        }
        
        $overallProgress = (count($completedStages) / count($stageOrder)) * 100;
        
        // Create notification for homeowner about progress update
        $notification = $db->prepare("
            INSERT INTO notifications (
                user_id, type, title, message, related_id, created_at
            ) VALUES (
                :homeowner_id, 'progress_update', 'Construction Progress Update', 
                :message, :project_id, NOW()
            )
        ");
        
        $notificationMessage = "Stage '{$stage_name}' has been marked as '{$stage_status}' by your contractor.";
        if ($stage_status === 'Completed') {
            $notificationMessage .= " Overall project progress: " . round($overallProgress, 1) . "%";
        }
        
        $notification->bindValue(':homeowner_id', $project['homeowner_id'], PDO::PARAM_INT);
        $notification->bindValue(':message', $notificationMessage, PDO::PARAM_STR);
        $notification->bindValue(':project_id', $project_id, PDO::PARAM_INT);
        $notification->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Stage progress updated successfully',
            'data' => [
                'update_id' => $update_id,
                'project_id' => $project_id,
                'stage_name' => $stage_name,
                'stage_status' => $stage_status,
                'completion_percentage' => $completion_percentage,
                'overall_progress' => round($overallProgress, 1),
                'completed_stages' => $completedStages,
                'total_stages' => count($stageOrder)
            ]
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update stage progress'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating stage progress: ' . $e->getMessage()
    ]);
}
?>