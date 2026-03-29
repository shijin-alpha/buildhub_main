<?php
require_once 'backend/config/database.php';

$db = (new Database())->getConnection();

echo "Checking projects 37 and 38:\n";
$stmt = $db->query('SELECT id, project_name FROM construction_projects WHERE id IN (37, 38)');
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($projects)) {
    echo "❌ Projects 37 and 38 do not exist in construction_projects table\n";
    echo "\nChecking what projects exist:\n";
    $stmt = $db->query('SELECT id, project_name FROM construction_projects ORDER BY id DESC LIMIT 10');
    $all_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_projects as $p) {
        echo "Project {$p['id']}: {$p['project_name']}\n";
    }
    
    echo "\nChecking daily_progress_updates table:\n";
    $stmt = $db->query('SELECT DISTINCT project_id FROM daily_progress_updates ORDER BY project_id');
    $progress_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($progress_projects as $p) {
        echo "Progress data exists for project: {$p['project_id']}\n";
    }
} else {
    foreach ($projects as $p) {
        echo "✅ Found Project {$p['id']}: {$p['project_name']}\n";
    }
}
?>