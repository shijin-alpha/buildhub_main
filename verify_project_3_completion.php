<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Project 3 Completion Verification ===\n\n";
    
    // Get project details
    $stmt = $db->prepare("SELECT * FROM construction_projects WHERE id = 3");
    $stmt->execute();
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "PROJECT DETAILS:\n";
    echo "ID: {$project['id']}\n";
    echo "Estimate ID: {$project['estimate_id']}\n";
    echo "Name: {$project['project_name']}\n";
    echo "Status: {$project['status']}\n";
    echo "Current Stage: {$project['current_stage']}\n";
    echo "Completion: {$project['completion_percentage']}%\n";
    echo "Actual Completion Date: {$project['actual_completion_date']}\n";
    echo "Last Update: {$project['updated_at']}\n\n";
    
    // Count daily progress updates
    $stmt = $db->prepare("SELECT COUNT(*) as total, 
                          MAX(cumulative_completion_percentage) as max_progress,
                          MAX(update_date) as last_update
                          FROM daily_progress_updates WHERE project_id = 3");
    $stmt->execute();
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "DAILY PROGRESS UPDATES:\n";
    echo "Total Reports: {$progress['total']}\n";
    echo "Max Progress: {$progress['max_progress']}%\n";
    echo "Last Update Date: {$progress['last_update']}\n\n";
    
    // Get stage breakdown
    $stmt = $db->prepare("SELECT construction_stage, 
                          COUNT(*) as days,
                          MAX(cumulative_completion_percentage) as stage_progress
                          FROM daily_progress_updates 
                          WHERE project_id = 3
                          GROUP BY construction_stage
                          ORDER BY MAX(update_date)");
    $stmt->execute();
    $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "STAGES COMPLETED:\n";
    foreach ($stages as $stage) {
        echo "- {$stage['construction_stage']}: {$stage['days']} days, reached {$stage['stage_progress']}%\n";
    }
    
    // Count payment requests
    $stmt = $db->prepare("SELECT COUNT(*) as total, 
                          SUM(requested_amount) as total_amount,
                          COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved
                          FROM stage_payment_requests WHERE project_id = 3");
    $stmt->execute();
    $payments = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nPAYMENT REQUESTS:\n";
    echo "Total Requests: {$payments['total']}\n";
    echo "Approved: {$payments['approved']}\n";
    echo "Total Amount: ₹" . number_format($payments['total_amount']) . "\n\n";
    
    // Check what the API would return
    echo "=== SIMULATING API RESPONSE ===\n\n";
    
    $stmt = $db->prepare("
        SELECT 
            cp.*,
            (SELECT COUNT(*) FROM daily_progress_updates WHERE project_id = cp.id) as daily_updates_count,
            (SELECT COUNT(*) FROM weekly_progress_summary WHERE project_id = cp.id) as weekly_summaries_count,
            (SELECT COUNT(*) FROM monthly_progress_reports WHERE project_id = cp.id) as monthly_reports_count,
            (SELECT MAX(update_date) FROM daily_progress_updates WHERE project_id = cp.id) as latest_update_timestamp
        FROM construction_projects cp
        WHERE cp.id = 3
    ");
    $stmt->execute();
    $api_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "API Response for Project 3:\n";
    echo "Status: {$api_data['status']}\n";
    echo "Current Stage: {$api_data['current_stage']}\n";
    echo "Completion: {$api_data['completion_percentage']}%\n";
    echo "Daily Updates: {$api_data['daily_updates_count']}\n";
    echo "Weekly Summaries: {$api_data['weekly_summaries_count']}\n";
    echo "Monthly Reports: {$api_data['monthly_reports_count']}\n";
    echo "Latest Update: {$api_data['latest_update_timestamp']}\n\n";
    
    if ($api_data['completion_percentage'] == 100 && $api_data['status'] == 'completed') {
        echo "✅ PROJECT IS FULLY COMPLETED!\n";
        echo "✅ The website should show 100% completion\n";
        echo "✅ Try refreshing your browser (Ctrl+F5) to clear cache\n";
    } else {
        echo "⚠️ Project completion status needs update\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
