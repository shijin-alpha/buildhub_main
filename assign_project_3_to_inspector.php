<?php
require_once 'backend/config/database.php';

echo "🔧 Assigning Project 3 to Inspector...\n\n";

$database = new Database();
$db = $database->getConnection();

// Assign Project 3 to Inspector 1001
$stmt = $db->prepare("
    INSERT INTO inspector_project_assignments (
        inspector_id, project_id, assigned_by, notes, status
    ) VALUES (
        1001, 3, 1000, 'Assigned for real progress demonstration', 'active'
    )
");

try {
    $stmt->execute();
    echo "✅ Project 3 assigned to Inspector 1001\n";
    
    // Verify the assignment
    $verifyStmt = $db->prepare("
        SELECT 
            ipa.*,
            cp.project_name,
            cp.completion_percentage as stored_progress
        FROM inspector_project_assignments ipa
        JOIN construction_projects cp ON ipa.project_id = cp.id
        WHERE ipa.inspector_id = 1001 AND ipa.project_id = 3
    ");
    $verifyStmt->execute();
    $assignment = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($assignment) {
        echo "✅ Assignment verified:\n";
        echo "   Project: {$assignment['project_name']}\n";
        echo "   Stored Progress: {$assignment['stored_progress']}%\n";
        echo "   Assigned At: {$assignment['assigned_at']}\n";
        echo "   Status: {$assignment['status']}\n";
    }
    
    // Check real progress for Project 3
    $progressStmt = $db->prepare("
        SELECT 
            SUM(spr.completion_percentage) as real_progress,
            COUNT(*) as paid_stages
        FROM stage_payment_requests spr 
        WHERE spr.project_id = 3 
        AND spr.status IN ('paid', 'approved')
    ");
    $progressStmt->execute();
    $progress = $progressStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n📊 Project 3 Progress Details:\n";
    echo "   Real Progress: {$progress['real_progress']}%\n";
    echo "   Paid Stages: {$progress['paid_stages']}\n";
    
    // Show stage payment details
    $stageStmt = $db->prepare("
        SELECT stage_name, completion_percentage, status, request_date
        FROM stage_payment_requests 
        WHERE project_id = 3
        ORDER BY request_date
    ");
    $stageStmt->execute();
    $stages = $stageStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Stage Payment Details:\n";
    foreach ($stages as $stage) {
        echo "   - {$stage['stage_name']}: {$stage['completion_percentage']}% ({$stage['status']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>