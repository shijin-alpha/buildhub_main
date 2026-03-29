<?php
require_once 'backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

// Find projects with existing progress
$stmt = $db->prepare('SELECT DISTINCT project_id, contractor_id FROM daily_progress_updates LIMIT 5');
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Checking existing projects for stage validation issues:\n\n";

foreach ($projects as $project) {
    echo "Project {$project['project_id']}, Contractor {$project['contractor_id']}:\n";
    
    $progressStmt = $db->prepare('
        SELECT construction_stage, 
               SUM(incremental_completion_percentage) as total_stage_progress
        FROM daily_progress_updates 
        WHERE project_id = :project_id AND contractor_id = :contractor_id
        GROUP BY construction_stage
        ORDER BY construction_stage
    ');
    $progressStmt->bindValue(':project_id', $project['project_id'], PDO::PARAM_INT);
    $progressStmt->bindValue(':contractor_id', $project['contractor_id'], PDO::PARAM_INT);
    $progressStmt->execute();
    $stageProgress = $progressStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stageProgress as $stage) {
        $remaining = 12.5 - floatval($stage['total_stage_progress']);
        echo "  {$stage['construction_stage']}: {$stage['total_stage_progress']}% (remaining: " . number_format($remaining, 2) . "%)\n";
        
        if ($remaining < 12.5 && $remaining > 0) {
            echo "    ⚠️ This stage would FAIL 12.5% submission (only " . number_format($remaining, 2) . "% remaining)\n";
        }
    }
    echo "\n";
}
?>