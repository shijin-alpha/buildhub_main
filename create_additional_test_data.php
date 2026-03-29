<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $project_id = 1; // Using the project we just assigned to contractor 32
    $homeowner_id = 28; // From the project data
    
    echo "Creating additional test data for project $project_id...\n";
    
    // Create a weekly summary
    $weekly_query = "INSERT INTO weekly_progress_summaries (
        project_id, contractor_id, homeowner_id, week_start_date, week_end_date,
        stages_worked, weekly_remarks, created_at
    ) VALUES (?, 32, ?, '2026-01-20', '2026-01-26', ?, 'Foundation work progressing well. Good weather conditions.', NOW())";
    
    $weekly_stmt = $pdo->prepare($weekly_query);
    $weekly_stmt->execute([
        $project_id,
        $homeowner_id,
        json_encode(['Foundation'])
    ]);
    echo "Created weekly summary\n";
    
    // Create another weekly summary
    $weekly_query2 = "INSERT INTO weekly_progress_summaries (
        project_id, contractor_id, homeowner_id, week_start_date, week_end_date,
        stages_worked, weekly_remarks, created_at
    ) VALUES (?, 32, ?, '2026-01-27', '2026-02-02', ?, 'Continued foundation work. Started preparing for next phase.', NOW())";
    
    $weekly_stmt2 = $pdo->prepare($weekly_query2);
    $weekly_stmt2->execute([
        $project_id,
        $homeowner_id,
        json_encode(['Foundation', 'Preparation'])
    ]);
    echo "Created second weekly summary\n";
    
    // Create a monthly report
    $monthly_query = "INSERT INTO monthly_progress_reports (
        project_id, contractor_id, homeowner_id, report_month, report_year,
        milestones_achieved, contractor_remarks, created_at
    ) VALUES (?, 32, ?, 1, 2026, ?, 'Foundation work is on track. Weather has been favorable for construction.', NOW())";
    
    $monthly_stmt = $pdo->prepare($monthly_query);
    $monthly_stmt->execute([
        $project_id,
        $homeowner_id,
        json_encode(['Foundation Started', 'Site Preparation Completed'])
    ]);
    echo "Created monthly report\n";
    
    // Create another daily progress update
    $daily_query = "INSERT INTO daily_progress_updates (
        project_id, contractor_id, homeowner_id, update_date, construction_stage,
        work_done_today, incremental_completion_percentage, cumulative_completion_percentage,
        working_hours, weather_condition, site_issues, created_at, updated_at
    ) VALUES (?, 32, ?, '2026-02-01', 'Foundation', 'Completed concrete pouring for foundation. Started curing process.', 5.0, 25.0, 8.0, 'Sunny', '', NOW(), NOW())";
    
    $daily_stmt = $pdo->prepare($daily_query);
    $daily_stmt->execute([$project_id, $homeowner_id]);
    echo "Created additional daily progress update\n";
    
    echo "\nTesting API response with new data...\n";
    
    // Test the API response
    $contractor_id = 32;
    $url = "http://localhost/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id=" . $contractor_id;
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Content-Type: application/json',
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);
    
    if ($data && isset($data['data']['projects'])) {
        foreach ($data['data']['projects'] as $project) {
            if ($project['id'] == $project_id) {
                echo "\nProject {$project_id} final update counts:\n";
                echo "Daily: {$project['daily_updates_count']} updates\n";
                echo "Weekly: {$project['weekly_summaries_count']} summaries\n";
                echo "Monthly: {$project['monthly_reports_count']} reports\n";
                echo "Latest Update: {$project['latest_update_timestamp']}\n";
                echo "Completion: {$project['completion_percentage']}%\n";
                break;
            }
        }
    }
    
    echo "\nTest data creation completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>