<?php
require_once 'backend/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Finding SHIJIN THOMAS Projects ===\n";
$stmt = $db->query("SELECT p.*, u.name as homeowner_name, u.first_name, u.last_name 
                    FROM projects p 
                    LEFT JOIN users u ON p.user_id = u.id 
                    WHERE u.name LIKE '%SHIJIN THOMAS%' OR u.first_name LIKE '%SHIJIN%'");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($projects as $p) {
    echo "Project ID: {$p['id']}, Name: {$p['project_name']}, Homeowner: {$p['homeowner_name']}, Contractor: {$p['contractor_id']}, User ID: {$p['user_id']}\n";
}

echo "\n=== Daily Progress Records ===\n";
$stmt = $db->query("SELECT dp.*, p.project_name, p.user_id 
                    FROM daily_progress dp 
                    LEFT JOIN projects p ON dp.project_id = p.id 
                    ORDER BY dp.project_id, dp.date DESC 
                    LIMIT 20");
$progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($progress as $p) {
    echo "ID: {$p['id']}, Project: {$p['project_id']} ({$p['project_name']}), User: {$p['user_id']}, Date: {$p['date']}, Stage: {$p['stage']}, Description: " . substr($p['description'], 0, 50) . "...\n";
}

echo "\n=== Checking for Project #2 ===\n";
$stmt = $db->query("SELECT * FROM projects WHERE project_name LIKE '%Project #2%' OR project_name LIKE '%Foundation%'");
$project2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($project2 as $p) {
    echo "Project ID: {$p['id']}, Name: {$p['project_name']}, User ID: {$p['user_id']}, Contractor: {$p['contractor_id']}\n";
}

echo "\n=== Daily Progress for Project #2 ===\n";
if (count($project2) > 0) {
    $projectId = $project2[0]['id'];
    $stmt = $db->prepare("SELECT * FROM daily_progress WHERE project_id = ? ORDER BY date DESC");
    $stmt->execute([$projectId]);
    $progress2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($progress2) . " daily progress records for project ID $projectId\n";
    foreach($progress2 as $p) {
        echo "  - Date: {$p['date']}, Stage: {$p['stage']}, Progress: {$p['progress_percentage']}%, Description: " . substr($p['description'], 0, 80) . "\n";
    }
}
