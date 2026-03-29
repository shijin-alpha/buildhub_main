<?php
$host = 'localhost';
$dbname = 'buildhub';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== AVAILABLE TABLES ===\n\n";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    echo "\n=== CHECKING FOR PROJECT-RELATED TABLES ===\n\n";
    
    // Check for real_projects
    if (in_array('real_projects', $tables)) {
        echo "Found 'real_projects' table\n";
        $stmt = $db->query("SELECT COUNT(*) as count FROM real_projects WHERE project_id = 37");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Project 37 exists: " . ($count['count'] > 0 ? 'Yes' : 'No') . "\n\n";
        
        if ($count['count'] > 0) {
            $stmt = $db->query("SELECT * FROM real_projects WHERE project_id = 37");
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Project Details:\n";
            print_r($project);
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
