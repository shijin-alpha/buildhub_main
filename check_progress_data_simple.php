<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== DAILY PROGRESS UPDATES ===\n";
    $stmt = $db->query('SELECT * FROM daily_progress_updates ORDER BY created_at DESC LIMIT 5');
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($updates as $update) {
        echo "ID: {$update['id']}, Project: {$update['project_id']}, Contractor: {$update['contractor_id']}, Homeowner: {$update['homeowner_id']}\n";
        echo "Date: {$update['update_date']}, Stage: {$update['construction_stage']}\n";
        echo "Progress: {$update['cumulative_completion_percentage']}%\n";
        echo "---\n";
    }

    echo "\n=== PROGRESS REPORTS ===\n";
    $stmt = $db->query('SELECT * FROM progress_reports ORDER BY created_at DESC LIMIT 3');
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($reports as $report) {
        echo "ID: {$report['id']}, Project: {$report['project_id']}, Type: {$report['report_type']}\n";
        echo "Contractor: {$report['contractor_id']}, Homeowner: {$report['homeowner_id']}\n";
        echo "Status: {$report['status']}\n";
        echo "---\n";
    }

    echo "\n=== USERS WITH PROGRESS DATA ===\n";
    $stmt = $db->query('
        SELECT DISTINCT u.id, u.first_name, u.last_name, u.role, 
               COUNT(dpu.id) as daily_updates_count
        FROM users u 
        LEFT JOIN daily_progress_updates dpu ON u.id = dpu.homeowner_id OR u.id = dpu.contractor_id
        WHERE u.role IN ("homeowner", "contractor")
        GROUP BY u.id
        ORDER BY daily_updates_count DESC
    ');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($users as $user) {
        echo "User {$user['id']}: {$user['first_name']} {$user['last_name']} ({$user['role']}) - {$user['daily_updates_count']} updates\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>