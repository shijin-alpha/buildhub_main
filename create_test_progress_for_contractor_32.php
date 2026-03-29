<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get the first project for contractor 32
    $project_query = "SELECT id, project_name, homeowner_id FROM construction_projects WHERE contractor_id = 32 LIMIT 1";
    $project_stmt = $pdo->prepare($project_query);
    $project_stmt->execute();
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo "No projects found for contractor 32. Let's check layout_requests instead.\n";
        
        // Check if there are layout requests that could be converted to construction projects
        $layout_query = "SELECT id, homeowner_id, plot_size, budget_range, preferred_style FROM layout_requests WHERE homeowner_id = 32 AND status = 'approved' LIMIT 1";
        $layout_stmt = $pdo->prepare($layout_query);
        $layout_stmt->execute();
        $layout = $layout_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($layout) {
            echo "Found approved layout request. Creating construction project...\n";
            
            // Create a construction project
            $create_project_query = "INSERT INTO construction_projects (
                contractor_id, homeowner_id, project_name, project_description, 
                status, plot_size, budget_range, preferred_style, 
                current_stage, completion_percentage, created_at, updated_at
            ) VALUES (
                32, ?, 'Test Project for Progress Updates', 'Test project to demonstrate progress tracking',
                'in_progress', ?, ?, ?, 
                'Foundation', 0.00, NOW(), NOW()
            )";
            
            $create_stmt = $pdo->prepare($create_project_query);
            $create_stmt->execute([
                $layout['homeowner_id'],
                $layout['plot_size'],
                $layout['budget_range'],
                $layout['preferred_style']
            ]);
            
            $project_id = $pdo->lastInsertId();
            echo "Created construction project with ID: $project_id\n";
            
            $project = [
                'id' => $project_id,
                'project_name' => 'Test Project for Progress Updates',
                'homeowner_id' => $layout['homeowner_id']
            ];
        } else {
            echo "No approved layout requests found for homeowner 32.\n";
            exit;
        }
    }
    
    $project_id = $project['id'];
    echo "Using project: ID {$project_id}, Name: {$project['project_name']}\n";
    
    // Create some test progress updates
    $progress_updates = [
        [
            'date' => '2026-01-28',
            'stage' => 'Foundation',
            'work_done' => 'Started excavation work for foundation. Marked the boundaries and began digging.',
            'incremental' => 5.0,
            'cumulative' => 5.0,
            'hours' => 8.0,
            'weather' => 'Sunny'
        ],
        [
            'date' => '2026-01-29',
            'stage' => 'Foundation',
            'work_done' => 'Completed excavation and started laying foundation stones. Mixed concrete for base.',
            'incremental' => 7.0,
            'cumulative' => 12.0,
            'hours' => 8.5,
            'weather' => 'Cloudy'
        ],
        [
            'date' => '2026-01-30',
            'stage' => 'Foundation',
            'work_done' => 'Poured concrete for foundation base. Set up reinforcement bars for strength.',
            'incremental' => 8.0,
            'cumulative' => 20.0,
            'hours' => 9.0,
            'weather' => 'Sunny'
        ]
    ];
    
    $insert_query = "INSERT INTO daily_progress_updates (
        project_id, contractor_id, homeowner_id, update_date, construction_stage,
        work_done_today, incremental_completion_percentage, cumulative_completion_percentage,
        working_hours, weather_condition, site_issues, created_at, updated_at
    ) VALUES (?, 32, ?, ?, ?, ?, ?, ?, ?, ?, '', NOW(), NOW())";
    
    $insert_stmt = $pdo->prepare($insert_query);
    
    foreach ($progress_updates as $update) {
        $insert_stmt->execute([
            $project_id,
            $project['homeowner_id'],
            $update['date'],
            $update['stage'],
            $update['work_done'],
            $update['incremental'],
            $update['cumulative'],
            $update['hours'],
            $update['weather']
        ]);
        echo "Created progress update for {$update['date']}: {$update['cumulative']}% complete\n";
    }
    
    // Create a weekly summary
    $weekly_query = "INSERT INTO weekly_progress_summaries (
        project_id, contractor_id, homeowner_id, week_start_date, week_end_date,
        stages_worked, weekly_remarks, created_at, updated_at
    ) VALUES (?, 32, ?, '2026-01-28', '2026-01-30', ?, 'Good progress on foundation work. Weather was favorable.', NOW(), NOW())";
    
    $weekly_stmt = $pdo->prepare($weekly_query);
    $weekly_stmt->execute([
        $project_id,
        $project['homeowner_id'],
        json_encode(['Foundation'])
    ]);
    echo "Created weekly summary\n";
    
    // Create a monthly report
    $monthly_query = "INSERT INTO monthly_progress_reports (
        project_id, contractor_id, homeowner_id, report_month, report_year,
        milestones_achieved, contractor_remarks, created_at, updated_at
    ) VALUES (?, 32, ?, 1, 2026, ?, 'Foundation work is progressing well. On track for completion.', NOW(), NOW())";
    
    $monthly_stmt = $pdo->prepare($monthly_query);
    $monthly_stmt->execute([
        $project_id,
        $project['homeowner_id'],
        json_encode(['Foundation Started', 'Excavation Completed'])
    ]);
    echo "Created monthly report\n";
    
    // Update the project completion percentage
    $update_project_query = "UPDATE construction_projects SET completion_percentage = 20.0, current_stage = 'Foundation', updated_at = NOW() WHERE id = ?";
    $update_stmt = $pdo->prepare($update_project_query);
    $update_stmt->execute([$project_id]);
    
    echo "\nTest data created successfully!\n";
    echo "Project ID: $project_id now has:\n";
    echo "- 3 daily progress updates\n";
    echo "- 1 weekly summary\n";
    echo "- 1 monthly report\n";
    echo "- 20% completion\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>