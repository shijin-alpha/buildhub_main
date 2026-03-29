<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Find a project that doesn't have a contractor assigned or has contractor 29
    $project_query = "SELECT id, project_name, contractor_id, homeowner_id, status FROM construction_projects WHERE contractor_id IS NULL OR contractor_id = 29 LIMIT 1";
    $project_stmt = $pdo->prepare($project_query);
    $project_stmt->execute();
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo "No suitable projects found to assign to contractor 32.\n";
        exit;
    }
    
    $project_id = $project['id'];
    echo "Found project: ID {$project_id}, Name: {$project['project_name']}, Current Contractor: " . ($project['contractor_id'] ?? 'NULL') . "\n";
    
    // Assign contractor 32 to this project
    $assign_query = "UPDATE construction_projects SET contractor_id = 32, updated_at = NOW() WHERE id = ?";
    $assign_stmt = $pdo->prepare($assign_query);
    $assign_stmt->execute([$project_id]);
    
    echo "Assigned contractor 32 to project {$project_id}\n";
    
    // Update any existing progress updates to be associated with contractor 32
    $update_progress_query = "UPDATE daily_progress_updates SET contractor_id = 32 WHERE project_id = ?";
    $update_progress_stmt = $pdo->prepare($update_progress_query);
    $update_progress_stmt->execute([$project_id]);
    
    $affected_rows = $update_progress_stmt->rowCount();
    echo "Updated {$affected_rows} daily progress updates to contractor 32\n";
    
    // Update weekly summaries
    $update_weekly_query = "UPDATE weekly_progress_summaries SET contractor_id = 32 WHERE project_id = ?";
    $update_weekly_stmt = $pdo->prepare($update_weekly_query);
    $update_weekly_stmt->execute([$project_id]);
    
    $weekly_affected = $update_weekly_stmt->rowCount();
    echo "Updated {$weekly_affected} weekly summaries to contractor 32\n";
    
    // Update monthly reports
    $update_monthly_query = "UPDATE monthly_progress_reports SET contractor_id = 32 WHERE project_id = ?";
    $update_monthly_stmt = $pdo->prepare($update_monthly_query);
    $update_monthly_stmt->execute([$project_id]);
    
    $monthly_affected = $update_monthly_stmt->rowCount();
    echo "Updated {$monthly_affected} monthly reports to contractor 32\n";
    
    echo "\nNow testing the API response...\n";
    
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
                echo "\nProject {$project_id} update counts:\n";
                echo "Daily: {$project['daily_updates_count']}\n";
                echo "Weekly: {$project['weekly_summaries_count']}\n";
                echo "Monthly: {$project['monthly_reports_count']}\n";
                break;
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>