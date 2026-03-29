<?php
require_once 'backend/config/database.php';

echo "🔍 Checking Inspector Project Assignments...\n\n";

$database = new Database();
$db = $database->getConnection();

// Check inspector assignments
$stmt = $db->prepare('SELECT * FROM inspector_project_assignments WHERE inspector_id = 1001');
$stmt->execute();
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Inspector 1001 assignments:\n";
foreach ($assignments as $assignment) {
    echo "Project ID: {$assignment['project_id']}, Status: {$assignment['status']}, Assigned: {$assignment['assigned_at']}\n";
}

echo "\n📊 All Projects with Stage Payments:\n";
$projectStmt = $db->prepare("
    SELECT 
        cp.id, 
        cp.project_name,
        COUNT(spr.id) as payment_count,
        SUM(CASE WHEN spr.status IN ('paid', 'approved') THEN spr.completion_percentage ELSE 0 END) as real_progress
    FROM construction_projects cp
    LEFT JOIN stage_payment_requests spr ON cp.id = spr.project_id
    GROUP BY cp.id, cp.project_name
    ORDER BY cp.id
");
$projectStmt->execute();
$projects = $projectStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($projects as $project) {
    echo "Project {$project['id']}: {$project['project_name']}\n";
    echo "  Payment Requests: {$project['payment_count']}\n";
    echo "  Real Progress: {$project['real_progress']}%\n";
    
    // Check if assigned to inspector
    $assignedStmt = $db->prepare("SELECT status FROM inspector_project_assignments WHERE project_id = ? AND inspector_id = 1001");
    $assignedStmt->execute([$project['id']]);
    $assignment = $assignedStmt->fetch();
    
    if ($assignment) {
        echo "  Assigned to Inspector: {$assignment['status']}\n";
    } else {
        echo "  NOT assigned to inspector\n";
    }
    echo "\n";
}
?>