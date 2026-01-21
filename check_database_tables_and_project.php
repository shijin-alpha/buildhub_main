<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== BUILDHUB DATABASE TABLES ===\n";
    
    // Get all table names
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach($tables as $table) {
        echo "- $table\n";
    }
    
    echo "\n=== SEARCHING FOR PROJECT ID REFERENCES ===\n";
    
    // Look for project-related tables and check their structure
    $projectTables = [];
    foreach($tables as $table) {
        if (stripos($table, 'project') !== false || 
            stripos($table, 'estimate') !== false || 
            stripos($table, 'contractor') !== false ||
            stripos($table, 'homeowner') !== false ||
            stripos($table, 'construction') !== false) {
            $projectTables[] = $table;
        }
    }
    
    echo "\nProject-related tables found:\n";
    foreach($projectTables as $table) {
        echo "- $table\n";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "  Columns: ";
        $columnNames = array_map(function($col) { return $col['Field']; }, $columns);
        echo implode(', ', $columnNames) . "\n";
        
        // Check if table has project_id or id column
        $hasProjectId = in_array('project_id', $columnNames);
        $hasId = in_array('id', $columnNames);
        
        if ($hasProjectId || $hasId) {
            echo "  -> Has project reference column\n";
        }
        echo "\n";
    }
    
    echo "\n=== LOOKING FOR SPECIFIC PROJECT DATA ===\n";
    
    // Let's look for the project mentioned in the HTML (seems to be project 37 or similar)
    // First, let's check what projects exist
    
    $projectQueries = [
        "SELECT * FROM homeowner_requests LIMIT 5",
        "SELECT * FROM contractor_estimates LIMIT 5", 
        "SELECT * FROM contractor_send_estimates LIMIT 5",
        "SELECT * FROM construction_projects LIMIT 5"
    ];
    
    foreach($projectQueries as $query) {
        try {
            echo "Query: $query\n";
            $stmt = $pdo->query($query);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                echo "Results found: " . count($results) . " rows\n";
                // Show first row structure
                if (isset($results[0])) {
                    echo "Sample row keys: " . implode(', ', array_keys($results[0])) . "\n";
                    
                    // Look for SHIJIN THOMAS project
                    foreach($results as $row) {
                        if (stripos(json_encode($row), 'shijin') !== false || 
                            stripos(json_encode($row), 'thomas') !== false) {
                            echo "Found SHIJIN THOMAS related data:\n";
                            print_r($row);
                            break;
                        }
                    }
                }
            } else {
                echo "No results found\n";
            }
            echo "\n";
        } catch(Exception $e) {
            echo "Error with query: " . $e->getMessage() . "\n\n";
        }
    }
    
} catch(Exception $e) {
    echo 'Database Error: ' . $e->getMessage() . "\n";
}
?>