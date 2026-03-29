<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get a sample project ID to test with
    $project_query = "SELECT id, project_name FROM construction_projects LIMIT 5";
    $project_stmt = $pdo->prepare($project_query);
    $project_stmt->execute();
    $projects = $project_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Available Projects ===\n";
    foreach ($projects as $project) {
        echo "Project ID: {$project['id']}, Name: {$project['project_name']}\n";
    }
    echo "\n";
    
    if (empty($projects)) {
        echo "No projects found in construction_projects table.\n";
        exit;
    }
    
    // Test with the first project
    $projectId = $projects[0]['id'];
    echo "=== Testing Update Counts for Project ID: $projectId ===\n";
    
    // Check daily updates
    $daily_query = "SELECT COUNT(*) as count, MAX(created_at) as latest FROM daily_progress_updates WHERE project_id = ?";
    $daily_stmt = $pdo->prepare($daily_query);
    $daily_stmt->execute([$projectId]);
    $daily_result = $daily_stmt->fetch(PDO::FETCH_ASSOC);
    echo "Daily Updates Count: {$daily_result['count']}\n";
    echo "Latest Daily Update: {$daily_result['latest']}\n";
    
    // Check weekly summaries
    $weekly_query = "SELECT COUNT(*) as count FROM weekly_progress_summaries WHERE project_id = ?";
    $weekly_stmt = $pdo->prepare($weekly_query);
    $weekly_stmt->execute([$projectId]);
    $weekly_result = $weekly_stmt->fetch(PDO::FETCH_ASSOC);
    echo "Weekly Summaries Count: {$weekly_result['count']}\n";
    
    // Check monthly reports
    $monthly_query = "SELECT COUNT(*) as count FROM monthly_progress_reports WHERE project_id = ?";
    $monthly_stmt = $pdo->prepare($monthly_query);
    $monthly_stmt->execute([$projectId]);
    $monthly_result = $monthly_stmt->fetch(PDO::FETCH_ASSOC);
    echo "Monthly Reports Count: {$monthly_result['count']}\n";
    
    echo "\n=== Sample Daily Progress Updates ===\n";
    $sample_query = "SELECT id, update_date, construction_stage, work_done_today, created_at FROM daily_progress_updates WHERE project_id = ? ORDER BY created_at DESC LIMIT 3";
    $sample_stmt = $pdo->prepare($sample_query);
    $sample_stmt->execute([$projectId]);
    $samples = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($samples)) {
        echo "No daily progress updates found for this project.\n";
    } else {
        foreach ($samples as $sample) {
            echo "ID: {$sample['id']}, Date: {$sample['update_date']}, Stage: {$sample['construction_stage']}, Created: {$sample['created_at']}\n";
        }
    }
    
    echo "\n=== Testing get_contractor_projects.php API Response ===\n";
    
    // Simulate the API call logic
    $update_counts = [
        'daily_updates_count' => 0,
        'weekly_summaries_count' => 0,
        'monthly_reports_count' => 0,
        'latest_update_date' => null
    ];
    
    // Check daily updates - try both project ID and estimate ID
    $daily_query = "SELECT COUNT(*) as count, MAX(created_at) as latest FROM daily_progress_updates WHERE project_id = ? OR project_id = ?";
    $daily_stmt = $pdo->prepare($daily_query);
    $daily_stmt->execute([$projectId, $projectId]); // Using same ID for both since we don't have estimate_id
    $daily_result = $daily_stmt->fetch(PDO::FETCH_ASSOC);
    $update_counts['daily_updates_count'] = (int)$daily_result['count'];
    $update_counts['latest_update_date'] = $daily_result['latest'];
    
    // Check weekly summaries
    $weekly_query = "SELECT COUNT(*) as count FROM weekly_progress_summaries WHERE project_id = ? OR project_id = ?";
    $weekly_stmt = $pdo->prepare($weekly_query);
    $weekly_stmt->execute([$projectId, $projectId]);
    $weekly_result = $weekly_stmt->fetch(PDO::FETCH_ASSOC);
    $update_counts['weekly_summaries_count'] = (int)$weekly_result['count'];
    
    // Check monthly reports
    $monthly_query = "SELECT COUNT(*) as count FROM monthly_progress_reports WHERE project_id = ? OR project_id = ?";
    $monthly_stmt = $pdo->prepare($monthly_query);
    $monthly_stmt->execute([$projectId, $projectId]);
    $monthly_result = $monthly_stmt->fetch(PDO::FETCH_ASSOC);
    $update_counts['monthly_reports_count'] = (int)$monthly_result['count'];
    
    echo "API Response Update Counts:\n";
    echo "Daily: {$update_counts['daily_updates_count']}\n";
    echo "Weekly: {$update_counts['weekly_summaries_count']}\n";
    echo "Monthly: {$update_counts['monthly_reports_count']}\n";
    echo "Latest Update: {$update_counts['latest_update_date']}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>