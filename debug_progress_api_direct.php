<?php
// Direct test of the progress API to debug why it's returning 0%
header('Content-Type: application/json');

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'buildhub';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔍 Progress API Debug for Project 37</h1>";
    
    // Test 1: Direct database query
    echo "<h2>1. Direct Database Query</h2>";
    $direct_query = "SELECT * FROM daily_progress_updates WHERE project_id = 37 ORDER BY update_date DESC, created_at DESC LIMIT 1";
    $stmt = $pdo->prepare($direct_query);
    $stmt->execute();
    $direct_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Query: " . $direct_query . "\n";
    echo "Result: " . print_r($direct_result, true);
    echo "</pre>";
    
    // Test 2: API query (original)
    echo "<h2>2. Original API Query (project_id only)</h2>";
    $api_query_old = "
        SELECT 
            cumulative_completion_percentage,
            incremental_completion_percentage,
            update_date,
            construction_stage,
            work_done_today,
            working_hours,
            weather_condition
        FROM daily_progress_updates dpu
        WHERE dpu.project_id = :project_id 
        ORDER BY dpu.update_date DESC, dpu.created_at DESC 
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($api_query_old);
    $stmt->bindParam(':project_id', $project_id = 37, PDO::PARAM_INT);
    $stmt->execute();
    $api_result_old = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Query: " . $api_query_old . "\n";
    echo "Result: " . print_r($api_result_old, true);
    echo "</pre>";
    
    // Test 3: New API query (with OR condition)
    echo "<h2>3. New API Query (project_id OR estimate_id)</h2>";
    $api_query_new = "
        SELECT 
            cumulative_completion_percentage,
            incremental_completion_percentage,
            update_date,
            construction_stage,
            work_done_today,
            working_hours,
            weather_condition
        FROM daily_progress_updates dpu
        WHERE dpu.project_id = :project_id OR dpu.project_id = :estimate_id
        ORDER BY dpu.update_date DESC, dpu.created_at DESC 
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($api_query_new);
    $stmt->bindParam(':project_id', $project_id = 37, PDO::PARAM_INT);
    $stmt->bindParam(':estimate_id', $estimate_id = 37, PDO::PARAM_INT);
    $stmt->execute();
    $api_result_new = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Query: " . $api_query_new . "\n";
    echo "Result: " . print_r($api_result_new, true);
    echo "</pre>";
    
    // Test 4: Check all records for project 37
    echo "<h2>4. All Records for Project 37</h2>";
    $all_query = "SELECT * FROM daily_progress_updates WHERE project_id = 37 ORDER BY created_at DESC";
    $stmt = $pdo->prepare($all_query);
    $stmt->execute();
    $all_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Query: " . $all_query . "\n";
    echo "Count: " . count($all_results) . " records\n";
    echo "Results: " . print_r($all_results, true);
    echo "</pre>";
    
    // Test 5: Test the actual API endpoint
    echo "<h2>5. Actual API Endpoint Test</h2>";
    $api_url = "http://localhost/buildhub/backend/api/contractor/get_project_current_progress.php?project_id=37";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $api_response = @file_get_contents($api_url, false, $context);
    
    echo "<pre>";
    echo "API URL: " . $api_url . "\n";
    echo "Response: " . $api_response . "\n";
    
    if ($api_response) {
        $api_data = json_decode($api_response, true);
        echo "Parsed: " . print_r($api_data, true);
    }
    echo "</pre>";
    
    // Test 6: Check construction_projects table
    echo "<h2>6. Construction Projects Table Check</h2>";
    $cp_query = "SELECT * FROM construction_projects WHERE id = 37 OR estimate_id = 37";
    $stmt = $pdo->prepare($cp_query);
    $stmt->execute();
    $cp_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Query: " . $cp_query . "\n";
    echo "Results: " . print_r($cp_results, true);
    echo "</pre>";
    
    // Test 7: Check contractor_send_estimates table
    echo "<h2>7. Contractor Send Estimates Table Check</h2>";
    $cse_query = "SELECT * FROM contractor_send_estimates WHERE id = 37";
    $stmt = $pdo->prepare($cse_query);
    $stmt->execute();
    $cse_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Query: " . $cse_query . "\n";
    echo "Results: " . print_r($cse_results, true);
    echo "</pre>";
    
    // Summary
    echo "<h2>🎯 Summary</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    
    if ($direct_result) {
        echo "✅ <strong>Database Record Found:</strong><br>";
        echo "Project ID: " . $direct_result['project_id'] . "<br>";
        echo "Cumulative Progress: " . $direct_result['cumulative_completion_percentage'] . "%<br>";
        echo "Update Date: " . $direct_result['update_date'] . "<br>";
        echo "Stage: " . $direct_result['construction_stage'] . "<br><br>";
        
        if ($api_result_old) {
            echo "✅ <strong>Original API Query Works</strong><br>";
        } else {
            echo "❌ <strong>Original API Query Failed</strong><br>";
        }
        
        if ($api_result_new) {
            echo "✅ <strong>New API Query Works</strong><br>";
        } else {
            echo "❌ <strong>New API Query Failed</strong><br>";
        }
    } else {
        echo "❌ <strong>No Database Record Found for Project 37</strong><br>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>