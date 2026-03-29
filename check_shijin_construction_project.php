<?php
require_once 'backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Finding SHIJIN THOMAS Construction Projects ===\n";
$stmt = $db->query("SELECT cp.*, u.first_name, u.last_name 
                    FROM construction_projects cp 
                    LEFT JOIN users u ON cp.homeowner_id = u.id 
                    WHERE u.first_name LIKE '%SHIJIN%' OR cp.homeowner_name LIKE '%SHIJIN%'");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($projects) . " projects\n\n";
foreach($projects as $p) {
    echo "Project ID: {$p['id']}\n";
    echo "  Name: {$p['project_name']}\n";
    echo "  Homeowner: {$p['homeowner_name']}\n";
    echo "  Contractor ID: {$p['contractor_id']}\n";
    echo "  Homeowner ID: {$p['homeowner_id']}\n";
    echo "  Status: {$p['status']}\n";
    echo "  Current Stage: {$p['current_stage']}\n";
    echo "  Completion: {$p['completion_percentage']}%\n";
    echo "  Created: {$p['created_at']}\n\n";
}

echo "\n=== Daily Progress Updates for SHIJIN THOMAS Projects ===\n";
if (count($projects) > 0) {
    foreach($projects as $project) {
        $projectId = $project['id'];
        echo "\nProject ID: $projectId - {$project['project_name']}\n";
        $stmt = $db->prepare("SELECT * FROM daily_progress_updates WHERE project_id = ? ORDER BY update_date DESC");
        $stmt->execute([$projectId]);
        $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  Found " . count($progress) . " daily progress records\n";
        foreach($progress as $p) {
            echo "    - Date: {$p['update_date']}, Stage: {$p['construction_stage']}, Progress: {$p['cumulative_completion_percentage']}%\n";
            echo "      Work Done: " . substr($p['work_done_today'], 0, 80) . "\n";
        }
    }
}

echo "\n=== Checking Project #2 Specifically ===\n";
$stmt = $db->query("SELECT * FROM construction_projects WHERE project_name LIKE '%Project #2%' OR project_name LIKE '%Foundation%'");
$project2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($project2) . " projects matching 'Project #2' or 'Foundation'\n";
foreach($project2 as $p) {
    echo "Project ID: {$p['id']}, Name: {$p['project_name']}, Homeowner ID: {$p['homeowner_id']}, Contractor: {$p['contractor_id']}\n";
}
