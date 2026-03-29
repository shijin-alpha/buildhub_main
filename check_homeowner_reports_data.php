<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Checking Homeowner Reports Data ===\n\n";
    
    // Check daily progress updates for homeowner ID 28
    echo "1. Daily Progress Updates for Homeowner ID 28:\n";
    $stmt = $db->prepare("
        SELECT 
            dpu.*,
            u.first_name as contractor_first_name,
            u.last_name as contractor_last_name
        FROM daily_progress_updates dpu
        LEFT JOIN users u ON dpu.contractor_id = u.id
        WHERE dpu.homeowner_id = 28
        ORDER BY dpu.update_date DESC
    ");
    $stmt->execute();
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($updates)) {
        echo "❌ No daily progress updates found\n";
    } else {
        echo "✓ Found " . count($updates) . " daily progress updates:\n";
        foreach ($updates as $update) {
            echo "  - ID: {$update['id']}, Date: {$update['update_date']}, Stage: {$update['construction_stage']}\n";
            echo "    Progress: +{$update['incremental_completion_percentage']}% (Total: {$update['cumulative_completion_percentage']}%)\n";
            echo "    Contractor: {$update['contractor_first_name']} {$update['contractor_last_name']}\n";
            echo "    Work: " . substr($update['work_done_today'], 0, 50) . "...\n\n";
        }
    }
    
    // Check notifications
    echo "2. Progress Notifications for Homeowner ID 28:\n";
    $notifStmt = $db->prepare("
        SELECT * FROM enhanced_progress_notifications 
        WHERE homeowner_id = 28 
        ORDER BY created_at DESC
    ");
    $notifStmt->execute();
    $notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notifications)) {
        echo "❌ No notifications found\n";
    } else {
        echo "✓ Found " . count($notifications) . " notifications:\n";
        foreach ($notifications as $notif) {
            echo "  - Type: {$notif['notification_type']}, Status: {$notif['status']}\n";
            echo "    Title: {$notif['title']}\n";
            echo "    Message: " . substr($notif['message'], 0, 80) . "...\n\n";
        }
    }
    
    // Check labour tracking
    echo "3. Labour Tracking Data:\n";
    $labourStmt = $db->query("
        SELECT 
            dlt.*,
            dpu.update_date,
            dpu.construction_stage
        FROM daily_labour_tracking dlt
        JOIN daily_progress_updates dpu ON dlt.daily_progress_id = dpu.id
        WHERE dpu.homeowner_id = 28
        ORDER BY dpu.update_date DESC
    ");
    $labour = $labourStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($labour)) {
        echo "❌ No labour tracking data found\n";
    } else {
        echo "✓ Found " . count($labour) . " labour entries:\n";
        foreach ($labour as $entry) {
            echo "  - Date: {$entry['update_date']}, Stage: {$entry['construction_stage']}\n";
            echo "    Worker: {$entry['worker_type']}, Count: {$entry['worker_count']}, Hours: {$entry['hours_worked']}\n";
            echo "    Productivity: {$entry['productivity_rating']}/5, Safety: {$entry['safety_compliance']}\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>