<?php
/**
 * Calculate Real Project Progress
 * Updates project completion percentages based on actual stage payment requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all construction projects
    $projectsStmt = $db->prepare("
        SELECT id, project_name, current_stage, completion_percentage, status
        FROM construction_projects 
        ORDER BY id
    ");
    $projectsStmt->execute();
    $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updatedProjects = [];
    
    foreach ($projects as $project) {
        $projectId = $project['id'];
        
        // Get all stage payment requests for this project
        $stageStmt = $db->prepare("
            SELECT 
                stage_name,
                completion_percentage,
                status,
                request_date,
                response_date
            FROM stage_payment_requests 
            WHERE project_id = :project_id
            ORDER BY request_date ASC
        ");
        $stageStmt->execute([':project_id' => $projectId]);
        $stages = $stageStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate real progress based on completed/paid stages
        $totalProgress = 0;
        $currentStage = 'Planning';
        $lastCompletedStage = null;
        
        foreach ($stages as $stage) {
            if (in_array($stage['status'], ['paid', 'approved'])) {
                $totalProgress += $stage['completion_percentage'];
                $lastCompletedStage = $stage['stage_name'];
                
                // Determine current stage based on last completed stage
                switch ($stage['stage_name']) {
                    case 'Site Preparation':
                        $currentStage = 'Foundation';
                        break;
                    case 'Foundation':
                        $currentStage = 'Structure';
                        break;
                    case 'Structure':
                        $currentStage = 'Brickwork';
                        break;
                    case 'Brickwork':
                        $currentStage = 'Roofing';
                        break;
                    case 'Roofing':
                        $currentStage = 'Electrical';
                        break;
                    case 'Electrical':
                        $currentStage = 'Plumbing';
                        break;
                    case 'Plumbing':
                        $currentStage = 'Finishing';
                        break;
                    case 'Finishing':
                        $currentStage = 'Final Inspection';
                        break;
                    case 'Final Inspection':
                        $currentStage = 'Completed';
                        break;
                    default:
                        $currentStage = $stage['stage_name'];
                }
            }
        }
        
        // If no stages completed, keep original stage
        if ($totalProgress == 0) {
            $currentStage = $project['current_stage'];
        }
        
        // Cap progress at 100%
        $totalProgress = min($totalProgress, 100);
        
        // Determine project status based on progress
        $projectStatus = $project['status'];
        if ($totalProgress > 0 && $projectStatus == 'created') {
            $projectStatus = 'in_progress';
        }
        if ($totalProgress >= 100) {
            $projectStatus = 'completed';
            $currentStage = 'Completed';
        }
        
        // Update the project with real progress
        $updateStmt = $db->prepare("
            UPDATE construction_projects 
            SET 
                completion_percentage = :progress,
                current_stage = :current_stage,
                status = :status,
                last_update_date = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :project_id
        ");
        
        $updateStmt->execute([
            ':progress' => $totalProgress,
            ':current_stage' => $currentStage,
            ':status' => $projectStatus,
            ':project_id' => $projectId
        ]);
        
        $updatedProjects[] = [
            'project_id' => $projectId,
            'project_name' => $project['project_name'],
            'old_progress' => (float)$project['completion_percentage'],
            'new_progress' => (float)$totalProgress,
            'old_stage' => $project['current_stage'],
            'new_stage' => $currentStage,
            'old_status' => $project['status'],
            'new_status' => $projectStatus,
            'completed_stages' => array_filter($stages, function($stage) {
                return in_array($stage['status'], ['paid', 'approved']);
            }),
            'total_stages' => count($stages)
        ];
    }
    
    // Get updated project statistics
    $statsStmt = $db->prepare("
        SELECT 
            COUNT(*) as total_projects,
            AVG(completion_percentage) as avg_progress,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as active_projects,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects,
            SUM(CASE WHEN completion_percentage > 0 THEN 1 ELSE 0 END) as started_projects
        FROM construction_projects
    ");
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Project progress updated based on real stage completion data',
        'updated_projects' => $updatedProjects,
        'statistics' => [
            'total_projects' => (int)$stats['total_projects'],
            'average_progress' => round((float)$stats['avg_progress'], 2),
            'active_projects' => (int)$stats['active_projects'],
            'completed_projects' => (int)$stats['completed_projects'],
            'started_projects' => (int)$stats['started_projects']
        ],
        'calculation_method' => 'Based on paid/approved stage payment requests',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Error calculating real progress: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to calculate real project progress',
        'error' => $e->getMessage()
    ]);
}
?>