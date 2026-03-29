<?php
require_once 'backend/config/database.php';
$db = (new Database())->getConnection();

echo "=== Checking Available Timeline Data ===\n\n";

// Check construction projects table
echo "1. Construction Projects:\n";
$stmt = $db->query('SELECT id, project_name, start_date, expected_completion_date, status, timeline FROM construction_projects LIMIT 5');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  Project: {$row['project_name']}\n";
    echo "  Start: {$row['start_date']}, Expected: {$row['expected_completion_date']}\n";
    echo "  Status: {$row['status']}, Timeline: {$row['timeline']}\n\n";
}

// Check progress updates
echo "2. Progress Updates:\n";
$stmt = $db->query('SELECT project_id, stage_name, completion_percentage, created_at FROM construction_progress_updates ORDER BY created_at DESC LIMIT 5');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  Project {$row['project_id']}: {$row['stage_name']} - {$row['completion_percentage']}% on {$row['created_at']}\n";
}

// Check daily progress updates
echo "\n3. Daily Progress Updates:\n";
$stmt = $db->query('SELECT project_id, construction_stage, cumulative_completion_percentage, update_date FROM daily_progress_updates ORDER BY update_date DESC LIMIT 5');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  Project {$row['project_id']}: {$row['construction_stage']} - {$row['cumulative_completion_percentage']}% on {$row['update_date']}\n";
}

// Check contractor estimates for project start info
echo "\n4. Project Estimates (Start Info):\n";
$stmt = $db->query('DESCRIBE contractor_send_estimates');
echo "Available columns: ";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " ";
}
echo "\n\n";

$stmt = $db->query('SELECT id, timeline, status, created_at FROM contractor_send_estimates WHERE status = "accepted" LIMIT 5');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  Estimate {$row['id']}\n";
    echo "  Timeline: {$row['timeline']}, Status: {$row['status']}, Created: {$row['created_at']}\n\n";
}
?>