<?php
// Create test daily progress updates to fix the progress display issue

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🔧 Creating Test Daily Progress Updates</h2>";
    
    // First, check what construction projects exist for homeowner 28
    $projectsQuery = "SELECT id, project_name, contractor_id FROM construction_projects WHERE homeowner_id = 28";
    $projectsStmt = $db->prepare($projectsQuery);
    $projectsStmt->execute();
    $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Found Projects:</h3>";
    foreach ($projects as $project) {
        echo "<p>Project ID: {$project['id']}, Name: {$project['project_name']}, Contractor: {$project['contractor_id']}</p>";
    }
    
    if (empty($projects)) {
        echo "<p style='color: red;'>❌ No projects found for homeowner 28</p>";
        exit;
    }
    
    // Create test daily progress updates for each project
    $testUpdates = [
        [
            'update_date' => '2026-01-15',
            'construction_stage' => 'Foundation',
            'work_done_today' => 'Started foundation excavation and site preparation',
            'incremental_completion_percentage' => 5.0,
            'cumulative_completion_percentage' => 5.0,
            'working_hours' => 8.0,
            'weather_condition' => 'Sunny'
        ],
        [
            'update_date' => '2026-01-16',
            'construction_stage' => 'Foundation',
            'work_done_today' => 'Continued foundation work, concrete pouring started',
            'incremental_completion_percentage' => 8.0,
            'cumulative_completion_percentage' => 13.0,
            'working_hours' => 8.0,
            'weather_condition' => 'Partly Cloudy'
        ],
        [
            'update_date' => '2026-01-17',
            'construction_stage' => 'Foundation',
            'work_done_today' => 'Foundation work completed, curing started',
            'incremental_completion_percentage' => 7.0,
            'cumulative_completion_percentage' => 20.0,
            'working_hours' => 8.0,
            'weather_condition' => 'Sunny'
        ],
        [
            'update_date' => '2026-01-20',
            'construction_stage' => 'Structure',
            'work_done_today' => 'Started column construction and steel work',
            'incremental_completion_percentage' => 5.0,
            'cumulative_completion_percentage' => 25.0,
            'working_hours' => 8.0,
            'weather_condition' => 'Sunny'
        ],
        [
            'update_date' => '2026-01-21',
            'construction_stage' => 'Structure',
            'work_done_today' => 'Continued structural work, beam installation',
            'incremental_completion_percentage' => 8.0,
            'cumulative_completion_percentage' => 33.0,
            'working_hours' => 8.0,
            'weather_condition' => 'Cloudy'
        ],
        [
            'update_date' => '2026-01-22',
            'construction_stage' => 'Structure',
            'work_done_today' => 'Slab work started, reinforcement completed',
            'incremental_completion_percentage' => 7.0,
            'cumulative_completion_percentage' => 40.0,
            'working_hours' => 8.0,
            'weather_condition' => 'Sunny'
        ]
    ];
    
    $insertQuery = "
        INSERT INTO daily_progress_updates (
            project_id, contractor_id, homeowner_id, update_date, 
            construction_stage, work_done_today, incremental_completion_percentage, 
            cumulative_completion_percentage, working_hours, weather_condition, 
            site_issues, progress_photos, latitude, longitude, location_verified,
            created_at, updated_at
        ) VALUES (
            :project_id, :contractor_id, :homeowner_id, :update_date,
            :construction_stage, :work_done_today, :incremental_completion_percentage,
            :cumulative_completion_percentage, :working_hours, :weather_condition,
            '', '[]', 9.79190000, 76.40000000, 0,
            NOW(), NOW()
        )
        ON DUPLICATE KEY UPDATE
            work_done_today = VALUES(work_done_today),
            incremental_completion_percentage = VALUES(incremental_completion_percentage),
            cumulative_completion_percentage = VALUES(cumulative_completion_percentage),
            working_hours = VALUES(working_hours),
            weather_condition = VALUES(weather_condition),
            updated_at = NOW()
    ";
    
    $insertStmt = $db->prepare($insertQuery);
    
    $totalInserted = 0;
    
    foreach ($projects as $project) {
        echo "<h3>Creating updates for Project {$project['id']} ({$project['project_name']})</h3>";
        
        foreach ($testUpdates as $update) {
            try {
                $insertStmt->execute([
                    ':project_id' => $project['id'],
                    ':contractor_id' => $project['contractor_id'],
                    ':homeowner_id' => 28,
                    ':update_date' => $update['update_date'],
                    ':construction_stage' => $update['construction_stage'],
                    ':work_done_today' => $update['work_done_today'],
                    ':incremental_completion_percentage' => $update['incremental_completion_percentage'],
                    ':cumulative_completion_percentage' => $update['cumulative_completion_percentage'],
                    ':working_hours' => $update['working_hours'],
                    ':weather_condition' => $update['weather_condition']
                ]);
                
                echo "<p>✅ Added update for {$update['update_date']} - {$update['construction_stage']} ({$update['cumulative_completion_percentage']}%)</p>";
                $totalInserted++;
                
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Update for {$update['update_date']} already exists or error: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h3>✅ Summary</h3>";
    echo "<p><strong>Total updates processed:</strong> $totalInserted</p>";
    
    // Now update the construction_projects table with the latest progress
    echo "<h3>🔄 Updating Construction Projects Table</h3>";
    
    foreach ($projects as $project) {
        // Get the latest progress for this project
        $latestQuery = "
            SELECT 
                MAX(cumulative_completion_percentage) as latest_progress,
                construction_stage
            FROM daily_progress_updates 
            WHERE project_id = :project_id 
            ORDER BY update_date DESC, created_at DESC 
            LIMIT 1
        ";
        
        $latestStmt = $db->prepare($latestQuery);
        $latestStmt->execute([':project_id' => $project['id']]);
        $latest = $latestStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($latest) {
            $updateProjectQuery = "
                UPDATE construction_projects 
                SET 
                    completion_percentage = :completion_percentage,
                    current_stage = :current_stage,
                    updated_at = NOW()
                WHERE id = :project_id
            ";
            
            $updateProjectStmt = $db->prepare($updateProjectQuery);
            $updateProjectStmt->execute([
                ':completion_percentage' => $latest['latest_progress'],
                ':current_stage' => $latest['construction_stage'],
                ':project_id' => $project['id']
            ]);
            
            echo "<p>✅ Updated project {$project['id']} progress to {$latest['latest_progress']}% ({$latest['construction_stage']})</p>";
        }
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Test Data Creation Complete!</h3>";
    echo "<p>The progress data has been created and should now be visible in:</p>";
    echo "<ul>";
    echo "<li>✅ Homeowner Dashboard progress widgets</li>";
    echo "<li>✅ Project info grids showing daily update counts</li>";
    echo "<li>✅ Progress bars reflecting actual completion percentages</li>";
    echo "<li>✅ Current stage information from latest updates</li>";
    echo "</ul>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Refresh the homeowner dashboard to see the updated progress</li>";
    echo "<li>Check the project info grid for correct update counts</li>";
    echo "<li>Verify progress bars show the correct percentages</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>❌ Error creating test data:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>