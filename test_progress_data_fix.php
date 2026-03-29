<?php
// Test the progress data fix
session_start();

// Set test homeowner session (user ID 28 based on the database data)
$_SESSION['user_id'] = 28;
$_SESSION['user_role'] = 'homeowner';
$_SESSION['user_name'] = 'SHIJIN THOMAS MCA2024-2026';

echo "<h2>🔧 Testing Progress Data Fix</h2>";
echo "<p>Testing with homeowner ID: " . $_SESSION['user_id'] . "</p>";

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h3>1. Checking Daily Progress Updates Data</h3>";
    
    // Check daily_progress_updates table
    $dailyQuery = "
        SELECT 
            dpu.id,
            dpu.project_id,
            dpu.update_date,
            dpu.construction_stage,
            dpu.cumulative_completion_percentage,
            dpu.incremental_completion_percentage,
            dpu.created_at,
            cp.project_name
        FROM daily_progress_updates dpu
        LEFT JOIN construction_projects cp ON dpu.project_id = cp.id
        WHERE dpu.homeowner_id = 28
        ORDER BY dpu.project_id, dpu.update_date DESC
    ";
    
    $stmt = $db->prepare($dailyQuery);
    $stmt->execute();
    $dailyUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Daily Progress Updates Found: " . count($dailyUpdates) . "</h4>";
    
    if (count($dailyUpdates) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Project ID</th><th>Project Name</th><th>Date</th><th>Stage</th><th>Progress %</th><th>Incremental %</th></tr>";
        foreach ($dailyUpdates as $update) {
            echo "<tr>";
            echo "<td>" . $update['project_id'] . "</td>";
            echo "<td>" . ($update['project_name'] ?: 'N/A') . "</td>";
            echo "<td>" . $update['update_date'] . "</td>";
            echo "<td>" . $update['construction_stage'] . "</td>";
            echo "<td>" . $update['cumulative_completion_percentage'] . "%</td>";
            echo "<td>" . $update['incremental_completion_percentage'] . "%</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No daily progress updates found!</p>";
    }
    echo "</div>";
    
    echo "<h3>2. Testing Dashboard API</h3>";
    
    // Test dashboard API
    $url = 'http://localhost/backend/api/homeowner/get_dashboard_data.php';
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Content-Type: application/json',
                'Cookie: ' . session_name() . '=' . session_id()
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "<p style='color: red;'>❌ Error: Could not fetch data from Dashboard API</p>";
    } else {
        $data = json_decode($response, true);
        
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        if ($data && $data['success']) {
            echo "<h4>✅ Dashboard API Response:</h4>";
            echo "<ul>";
            echo "<li><strong>Total Projects:</strong> " . $data['data']['overview']['total_projects'] . "</li>";
            echo "<li><strong>Average Completion:</strong> " . $data['data']['overview']['average_completion'] . "%</li>";
            echo "</ul>";
            
            if (!empty($data['data']['projects'])) {
                echo "<h5>Project Details:</h5>";
                foreach ($data['data']['projects'] as $project) {
                    echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
                    echo "<strong>Project:</strong> " . $project['name'] . "<br>";
                    echo "<strong>Progress:</strong> " . $project['progress']['completion_percentage'] . "%<br>";
                    echo "<strong>Current Stage:</strong> " . $project['progress']['current_stage'] . "<br>";
                    echo "<strong>Daily Updates:</strong> " . ($project['update_history']['daily_updates_count'] ?? 'N/A') . "<br>";
                    echo "</div>";
                }
            }
        } else {
            echo "<h4>❌ Dashboard API Error:</h4>";
            echo "<p>" . ($data['message'] ?? 'Unknown error') . "</p>";
        }
        echo "</div>";
    }
    
    echo "<h3>3. Testing Project Info API</h3>";
    
    // Test project info API for each project
    $projectsQuery = "SELECT id, project_name FROM construction_projects WHERE homeowner_id = 28";
    $projectsStmt = $db->prepare($projectsQuery);
    $projectsStmt->execute();
    $projects = $projectsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($projects as $project) {
        echo "<h4>Testing Project ID: " . $project['id'] . " (" . $project['project_name'] . ")</h4>";
        
        $projectUrl = 'http://localhost/backend/api/homeowner/get_project_info.php?project_id=' . $project['id'];
        
        $projectResponse = file_get_contents($projectUrl, false, $context);
        
        if ($projectResponse === false) {
            echo "<p style='color: red;'>❌ Error: Could not fetch project info</p>";
        } else {
            $projectData = json_decode($projectResponse, true);
            
            echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            if ($projectData && $projectData['success']) {
                $info = $projectData['data']['project_info'];
                echo "<h5>✅ Project Info API Response:</h5>";
                echo "<ul>";
                echo "<li><strong>Completion:</strong> " . $info['completion_percentage'] . "%</li>";
                echo "<li><strong>Current Stage:</strong> " . $info['current_stage'] . "</li>";
                echo "<li><strong>Daily Updates:</strong> " . $info['daily_updates_count'] . "</li>";
                echo "<li><strong>Weekly Summaries:</strong> " . $info['weekly_summaries_count'] . "</li>";
                echo "<li><strong>Monthly Reports:</strong> " . $info['monthly_reports_count'] . "</li>";
                echo "<li><strong>Last Update:</strong> " . ($info['last_update_date'] ?: 'N/A') . "</li>";
                echo "<li><strong>Completed Stages:</strong> " . $info['completed_stages'] . "</li>";
                echo "</ul>";
            } else {
                echo "<h5>❌ Project Info API Error:</h5>";
                echo "<p>" . ($projectData['message'] ?? 'Unknown error') . "</p>";
            }
            echo "</div>";
        }
    }
    
    echo "<h3>4. Checking Construction Projects Table</h3>";
    
    // Check construction_projects table
    $constructionQuery = "
        SELECT 
            id,
            project_name,
            current_stage,
            completion_percentage,
            status,
            updated_at
        FROM construction_projects 
        WHERE homeowner_id = 28
    ";
    
    $constructionStmt = $db->prepare($constructionQuery);
    $constructionStmt->execute();
    $constructionProjects = $constructionStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Construction Projects Found: " . count($constructionProjects) . "</h4>";
    
    if (count($constructionProjects) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Stage</th><th>Progress %</th><th>Status</th><th>Updated</th></tr>";
        foreach ($constructionProjects as $cp) {
            echo "<tr>";
            echo "<td>" . $cp['id'] . "</td>";
            echo "<td>" . $cp['project_name'] . "</td>";
            echo "<td>" . $cp['current_stage'] . "</td>";
            echo "<td>" . $cp['completion_percentage'] . "%</td>";
            echo "<td>" . $cp['status'] . "</td>";
            echo "<td>" . $cp['updated_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    echo "<h3>5. Summary & Recommendations</h3>";
    
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>🔍 Analysis Results:</h4>";
    echo "<ul>";
    
    if (count($dailyUpdates) > 0) {
        echo "<li>✅ Daily progress updates are available (" . count($dailyUpdates) . " records)</li>";
        
        // Group by project
        $projectUpdates = [];
        foreach ($dailyUpdates as $update) {
            $projectUpdates[$update['project_id']][] = $update;
        }
        
        foreach ($projectUpdates as $projectId => $updates) {
            $latestUpdate = $updates[0]; // Already ordered by date DESC
            echo "<li>📊 Project $projectId: Latest progress is " . $latestUpdate['cumulative_completion_percentage'] . "% (" . $latestUpdate['construction_stage'] . ")</li>";
        }
    } else {
        echo "<li>❌ No daily progress updates found - this explains why progress bars show 0%</li>";
        echo "<li>💡 Recommendation: Add some test daily progress updates to see the system working</li>";
    }
    
    if (count($constructionProjects) > 0) {
        echo "<li>✅ Construction projects exist (" . count($constructionProjects) . " projects)</li>";
    } else {
        echo "<li>❌ No construction projects found</li>";
    }
    
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>❌ Error during testing:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>