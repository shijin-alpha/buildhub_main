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
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    $homeowner_id = $_SESSION['user_id'] ?? null;
    
    if (!$homeowner_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Homeowner not authenticated'
        ]);
        exit;
    }

    $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
    
    if ($project_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        exit;
    }

    // Verify homeowner owns this project
    $projectCheck = $db->prepare("
        SELECT cse.id, cls.homeowner_id, cse.total_cost, 
               COALESCE(cse.structured, '{}') as project_data
        FROM contractor_send_estimates cse 
        INNER JOIN contractor_layout_sends cls ON cse.send_id = cls.id
        WHERE cse.id = :project_id AND cls.homeowner_id = :homeowner_id
        LIMIT 1
    ");
    $projectCheck->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $projectCheck->bindValue(':homeowner_id', $homeowner_id, PDO::PARAM_INT);
    $projectCheck->execute();
    $project = $projectCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit;
    }

    // Define fixed logical stage order and mapping with weights
    $stageOrder = [
        'Foundation' => ['order' => 1, 'visual_layer' => 'foundation', 'weight' => 20],
        'Structure' => ['order' => 2, 'visual_layer' => 'structure', 'weight' => 25], 
        'Walls' => ['order' => 3, 'visual_layer' => 'walls', 'weight' => 20],
        'Brickwork' => ['order' => 3, 'visual_layer' => 'walls', 'weight' => 20], // Map to walls
        'Roofing' => ['order' => 4, 'visual_layer' => 'roofing', 'weight' => 15],
        'Finishing' => ['order' => 5, 'visual_layer' => 'finishing', 'weight' => 20]
    ];

    // Get latest progress updates for each stage
    $progressQuery = $db->prepare("
        SELECT 
            cpu.stage_name,
            cpu.stage_status,
            cpu.completion_percentage,
            cpu.created_at,
            cpu.remarks,
            ROW_NUMBER() OVER (PARTITION BY cpu.stage_name ORDER BY cpu.created_at DESC) as rn
        FROM construction_progress_updates cpu
        WHERE cpu.project_id = :project_id
        ORDER BY cpu.created_at DESC
    ");
    $progressQuery->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $progressQuery->execute();
    $allUpdates = $progressQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get only the latest update per stage
    $stageProgress = [];
    foreach ($allUpdates as $update) {
        if ($update['rn'] == 1) { // Latest update for this stage
            $stageProgress[$update['stage_name']] = $update;
        }
    }

    // Calculate completion based on actual percentages from daily updates
    $completedStages = [];
    $totalWeightedProgress = 0;
    $totalWeight = 0;
    $currentStage = null;
    $overallProgress = 0;

    // Group stages by visual layer to handle multiple stages mapping to same layer
    $layerProgress = [
        'foundation' => 0,
        'structure' => 0,
        'walls' => 0,
        'roofing' => 0,
        'finishing' => 0
    ];

    foreach ($stageOrder as $stageName => $stageInfo) {
        $stagePercentage = 0;
        $stageData = null;
        
        // Check if we have progress data for this stage
        if (isset($stageProgress[$stageName])) {
            $stageData = $stageProgress[$stageName];
            $stagePercentage = (float)$stageData['completion_percentage'];
        }
        
        // Calculate weighted progress contribution
        $stageWeight = $stageInfo['weight'];
        $totalWeight += $stageWeight;
        $totalWeightedProgress += ($stagePercentage / 100) * $stageWeight;
        
        // Update layer progress (take maximum if multiple stages map to same layer)
        $layer = $stageInfo['visual_layer'];
        $layerProgress[$layer] = max($layerProgress[$layer], $stagePercentage);
        
        // Track completed stages (100% complete)
        if ($stagePercentage >= 100) {
            $completedStages[] = $stageName;
        } else if (!$currentStage && $stagePercentage > 0) {
            // First stage with progress > 0 but < 100 is current active stage
            $currentStage = $stageName;
        }

        $stageOrder[$stageName]['completed'] = $stagePercentage >= 100;
        $stageOrder[$stageName]['percentage'] = $stagePercentage;
        $stageOrder[$stageName]['data'] = $stageData;
    }

    // Calculate overall progress as weighted average
    $overallProgress = $totalWeight > 0 ? ($totalWeightedProgress / $totalWeight) * 100 : 0;

    // If no current stage found, determine based on progress
    if (!$currentStage) {
        if ($overallProgress >= 100) {
            $currentStage = 'Project Complete';
        } else {
            // Find first incomplete stage
            foreach ($stageOrder as $stageName => $stageInfo) {
                if ($stageInfo['percentage'] < 100) {
                    $currentStage = $stageName;
                    break;
                }
            }
            if (!$currentStage) {
                $currentStage = 'Foundation';
            }
        }
    }

    // Build visual layers status with percentage-based opacity
    $visualLayers = [
        'foundation' => $layerProgress['foundation'] / 100,
        'structure' => $layerProgress['structure'] / 100, 
        'walls' => $layerProgress['walls'] / 100,
        'roofing' => $layerProgress['roofing'] / 100,
        'finishing' => $layerProgress['finishing'] / 100
    ];

    // Get recent progress updates for timeline
    $recentUpdates = array_slice($allUpdates, 0, 5);

    echo json_encode([
        'success' => true,
        'data' => [
            'project' => [
                'id' => $project['id'],
                'name' => 'Construction Project', // Default name since structured data is complex
                'total_cost' => $project['total_cost']
            ],
            'progress' => [
                'overall_percentage' => round($overallProgress, 1),
                'current_stage' => $currentStage,
                'completed_stages' => $completedStages,
                'total_weight' => $totalWeight,
                'completed_count' => count($completedStages)
            ],
            'visual_layers' => $visualLayers,
            'layer_percentages' => $layerProgress,
            'stage_details' => $stageOrder,
            'recent_updates' => $recentUpdates
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving project progress: ' . $e->getMessage()
    ]);
}
?>