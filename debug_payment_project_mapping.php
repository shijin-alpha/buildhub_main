<?php
// Debug payment project mapping issue
header('Content-Type: text/html; charset=utf-8');

try {
    // Database connection
    $host = 'localhost';
    $dbname = 'buildhub';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔍 Payment Project Mapping Debug</h1>";
    
    // 1. Check what projects exist in construction_projects table
    echo "<h2>1. Construction Projects Table</h2>";
    $cp_query = "SELECT id, project_name, homeowner_id, contractor_id, estimate_id FROM construction_projects ORDER BY id";
    $stmt = $pdo->prepare($cp_query);
    $stmt->execute();
    $construction_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Project Name</th><th>Homeowner ID</th><th>Contractor ID</th><th>Estimate ID</th></tr>";
    foreach ($construction_projects as $project) {
        echo "<tr>";
        echo "<td>{$project['id']}</td>";
        echo "<td>{$project['project_name']}</td>";
        echo "<td>{$project['homeowner_id']}</td>";
        echo "<td>{$project['contractor_id']}</td>";
        echo "<td>{$project['estimate_id']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Check what payment requests exist
    echo "<h2>2. Stage Payment Requests Table</h2>";
    $spr_query = "SELECT id, project_id, contractor_id, homeowner_id, stage_name, requested_amount, status FROM stage_payment_requests ORDER BY id";
    $stmt = $pdo->prepare($spr_query);
    $stmt->execute();
    $payment_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Project ID</th><th>Contractor ID</th><th>Homeowner ID</th><th>Stage</th><th>Amount</th><th>Status</th></tr>";
    foreach ($payment_requests as $payment) {
        echo "<tr>";
        echo "<td>{$payment['id']}</td>";
        echo "<td>{$payment['project_id']}</td>";
        echo "<td>{$payment['contractor_id']}</td>";
        echo "<td>{$payment['homeowner_id']}</td>";
        echo "<td>{$payment['stage_name']}</td>";
        echo "<td>₹" . number_format($payment['requested_amount']) . "</td>";
        echo "<td>{$payment['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Check contractor_send_estimates table
    echo "<h2>3. Contractor Send Estimates Table</h2>";
    $cse_query = "SELECT id, contractor_id, total_cost, status FROM contractor_send_estimates WHERE contractor_id = 29 ORDER BY id";
    $stmt = $pdo->prepare($cse_query);
    $stmt->execute();
    $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Contractor ID</th><th>Total Cost</th><th>Status</th></tr>";
    foreach ($estimates as $estimate) {
        echo "<tr>";
        echo "<td>{$estimate['id']}</td>";
        echo "<td>{$estimate['contractor_id']}</td>";
        echo "<td>₹" . number_format($estimate['total_cost']) . "</td>";
        echo "<td>{$estimate['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4. Test the payment API query for project 37
    echo "<h2>4. Payment API Query Test for Project 37</h2>";
    $project_id = 37;
    
    // Test the project existence query from the API
    $project_exists_query = "
        SELECT 
            CASE 
                WHEN cp.id IS NOT NULL THEN 'construction_projects'
                WHEN lr.id IS NOT NULL THEN 'layout_requests'
                ELSE NULL
            END as project_source,
            COALESCE(cp.id, lr.id) as project_id,
            COALESCE(cp.homeowner_id, lr.user_id) as homeowner_id
        FROM (SELECT ? as search_id) s
        LEFT JOIN construction_projects cp ON cp.id = s.search_id
        LEFT JOIN layout_requests lr ON lr.id = s.search_id
        WHERE cp.id IS NOT NULL OR lr.id IS NOT NULL
    ";
    
    $stmt = $pdo->prepare($project_exists_query);
    $stmt->execute([$project_id]);
    $project_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Query: " . $project_exists_query . "\n";
    echo "Project ID: " . $project_id . "\n";
    echo "Result: " . print_r($project_result, true);
    echo "</pre>";
    
    if ($project_result) {
        echo "<p style='color: green;'>✅ Project 37 found in {$project_result['project_source']} table</p>";
        
        // Now test the payment query
        echo "<h3>Payment Query for Project 37</h3>";
        $payment_query = "
            SELECT 
                spr.*,
                u.first_name, u.last_name
            FROM stage_payment_requests spr
            LEFT JOIN users u ON spr.homeowner_id = u.id
            WHERE spr.project_id = ? 
            AND spr.contractor_id = ?
            ORDER BY spr.request_date DESC
        ";
        
        $stmt = $pdo->prepare($payment_query);
        $stmt->execute([$project_id, 29]); // contractor_id = 29
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Found " . count($payments) . " payment requests for Project 37</p>";
        
        if (count($payments) == 0) {
            echo "<p style='color: red;'>❌ No payment requests found for Project 37</p>";
            echo "<p><strong>Issue:</strong> Payment requests exist for project_id = 1, but user is selecting project_id = 37</p>";
            
            // Check if there's a mapping issue
            echo "<h3>Mapping Analysis</h3>";
            echo "<p>The issue is that:</p>";
            echo "<ul>";
            echo "<li>Progress system uses project_id = 37 (from construction_projects or estimates)</li>";
            echo "<li>Payment system has data for project_id = 1</li>";
            echo "<li>These need to be mapped correctly</li>";
            echo "</ul>";
            
            // Check if project 37 maps to estimate 1 or something similar
            echo "<h3>Checking Project 37 Details</h3>";
            $project_detail_query = "
                SELECT cp.*, cse.id as estimate_id, cse.total_cost 
                FROM construction_projects cp 
                LEFT JOIN contractor_send_estimates cse ON cp.estimate_id = cse.id 
                WHERE cp.id = ? OR cp.estimate_id = ?
            ";
            $stmt = $pdo->prepare($project_detail_query);
            $stmt->execute([$project_id, $project_id]);
            $project_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<pre>";
            echo "Project 37 Details: " . print_r($project_details, true);
            echo "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Project 37 not found in construction_projects or layout_requests tables</p>";
    }
    
    // 5. Suggest fix
    echo "<h2>5. Suggested Fix</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
    echo "<p><strong>The issue is a project ID mapping problem:</strong></p>";
    echo "<ol>";
    echo "<li>Payment requests exist for <code>project_id = 1</code></li>";
    echo "<li>User is selecting <code>project_id = 37</code> from the project dropdown</li>";
    echo "<li>The payment API needs to map between these IDs correctly</li>";
    echo "</ol>";
    
    echo "<p><strong>Possible solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Update payment requests to use the correct project_id (37)</li>";
    echo "<li>Modify the payment API to handle ID mapping</li>";
    echo "<li>Check if project 37 should map to estimate_id 1</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>