<?php
/**
 * Check Final Stage Issue for Project 37
 */

$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║         CHECKING FINAL STAGE ISSUE - PROJECT 37              ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";
    
    $projectId = 37;
    
    // Check all stages with "Final" in the name
    echo "Searching for Final stages:\n\n";
    
    $stmt = $db->query("SELECT stage_name, completion_percentage, stage_status 
                        FROM construction_progress_updates 
                        WHERE project_id = $projectId 
                        AND stage_name LIKE '%Final%'");
    $finalStages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($finalStages)) {
        echo "❌ No stages with 'Final' found!\n\n";
    } else {
        foreach ($finalStages as $stage) {
            echo "Found: {$stage['stage_name']}\n";
            echo "  Completion: {$stage['completion_percentage']}%\n";
            echo "  Status: {$stage['stage_status']}\n\n";
        }
    }
    
    // Check daily progress for Final stages
    echo "Checking daily progress updates:\n\n";
    
    $stmt = $db->query("SELECT construction_stage, 
                        COUNT(*) as report_count,
                        MAX(cumulative_completion_percentage) as max_progress
                        FROM daily_progress_updates 
                        WHERE project_id = $projectId 
                        AND construction_stage LIKE '%Final%'
                        GROUP BY construction_stage");
    $dailyProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($dailyProgress)) {
        echo "❌ No daily progress for Final stages!\n\n";
    } else {
        foreach ($dailyProgress as $progress) {
            echo "Stage: {$progress['construction_stage']}\n";
            echo "  Reports: {$progress['report_count']}\n";
            echo "  Max Progress: {$progress['max_progress']}%\n\n";
        }
    }
    
    // List ALL stages for this project
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "ALL STAGES IN PROJECT 37:\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $stmt = $db->query("SELECT stage_name, completion_percentage, stage_status 
                        FROM construction_progress_updates 
                        WHERE project_id = $projectId 
                        ORDER BY created_at");
    $allStages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allStages as $stage) {
        $icon = $stage['completion_percentage'] == 100 ? '✅' : '❌';
        echo "$icon {$stage['stage_name']}: {$stage['completion_percentage']}% ({$stage['stage_status']})\n";
    }
    
    echo "\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
