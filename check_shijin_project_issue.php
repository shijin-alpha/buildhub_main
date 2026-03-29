<?php
$db = new PDO('sqlite:buildhub.db');

// Find the project
echo "=== Finding SHIJIN THOMAS Projects ===\n";
$stmt = $db->query("SELECT p.*, u.name as homeowner_name FROM projects p LEFT JOIN users u ON p.user_id = u.id WHERE u.name LIKE '%SHIJIN THOMAS%'");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($projects as $p) {
    echo "Project ID: {$p['id']}, Name: {$p['project_name']}, Homeowner: {$p['homeowner_name']}, Contractor: {$p['contractor_id']}\n";
}

// Check daily progress for these projects
echo "\n=== Daily Progress Records ===\n";
$stmt = $db->query("SELECT * FROM daily_progress ORDER BY project_id, date DESC");
$progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($progress as $p) {
    echo "ID: {$p['id']}, Project: {$p['project_id']}, Date: {$p['date']}, Stage: {$p['stage']}, Description: " . substr($p['description'], 0, 50) . "...\n";
}

// Check contractor assignments
echo "\n=== Contractor Assignments ===\n";
$stmt = $db->query("SELECT * FROM projects WHERE contractor_id IS NOT NULL");
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($assignments as $a) {
    echo "Project ID: {$a['id']}, Name: {$a['project_name']}, Contractor ID: {$a['contractor_id']}\n";
}
