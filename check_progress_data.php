<?php
/**
 * Check progress data in database
 */

try {
    $db = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔍 Progress Data Check</h1>\n";
    
    // Check daily progress updates
    echo "<h2>Daily Progress Updates</h2>\n";
    $stmt = $db->query("
        SELECT project_id, construction_stage, incremental_completion_percentage, 
               cumulative_completion_percentage, update_date 
        FROM daily_progress_updates 
        ORDER BY update_date DESC 
        LIMIT 10
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Project ID</th><th>Stage</th><th>Incremental %</th><th>Cumulative %</th><th>Date</th></tr>\n";
    
    $hasData = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hasData = true;
        echo "<tr>";
        echo "<td>{$row['project_id']}</td>";
        echo "<td>{$row['construction_stage']}</td>";
        echo "<td>{$row['incremental_completion_percentage']}%</td>";
        echo "<td>{$row['cumulative_completion_percentage']}%</td>";
        echo "<td>{$row['update_date']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    if (!$hasData) {
        echo "<p>❌ No progress updates found in database</p>\n";
    }
    
    // Check for specific project (37)
    echo "<h2>Project 37 Progress Data</h2>\n";
    $stmt37 = $db->prepare("
        SELECT construction_stage, incremental_completion_percentage, 
               cumulative_completion_percentage, update_date 
        FROM daily_progress_updates 
        WHERE project_id = 37 
        ORDER BY update_date DESC
    ");
    $stmt37->execute();
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Stage</th><th>Incremental %</th><th>Cumulative %</th><th>Date</th></tr>\n";
    
    $project37HasData = false;
    while ($row = $stmt37->fetch(PDO::FETCH_ASSOC)) {
        $project37HasData = true;
        echo "<tr>";
        echo "<td>{$row['construction_stage']}</td>";
        echo "<td>{$row['incremental_completion_percentage']}%</td>";
        echo "<td>{$row['cumulative_completion_percentage']}%</td>";
        echo "<td>{$row['update_date']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    if (!$project37HasData) {
        echo "<p>❌ No progress updates found for Project 37</p>\n";
    }
    
    // Check construction projects table
    echo "<h2>Construction Projects</h2>\n";
    $projects_stmt = $db->query("
        SELECT id, project_name, estimate_id, status 
        FROM construction_projects 
        ORDER BY id DESC 
        LIMIT 5
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>ID</th><th>Project Name</th><th>Estimate ID</th><th>Status</th></tr>\n";
    
    while ($row = $projects_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['project_name']}</td>";
        echo "<td>{$row['estimate_id']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>