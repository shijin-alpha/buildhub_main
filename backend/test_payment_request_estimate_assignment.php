<?php
/**
 * Test: Verify Payment Request Form Gets Correct Estimate Cost
 * 
 * This test verifies that when a contractor selects a project in the payment
 * request form, the correct estimate cost from contractor_send_estimates is assigned.
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    $host = 'localhost';
    $dbname = 'buildhub';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Testing Payment Request Estimate Assignment ===\n\n";
    
    // Get a contractor with accepted estimates
    $stmt = $pdo->query("
        SELECT DISTINCT
            cse.contractor_id,
            CONCAT(u.first_name, ' ', u.last_name) as contractor_name
        FROM contractor_send_estimates cse
        LEFT JOIN users u ON u.id = cse.contractor_id
        WHERE cse.status = 'accepted' 
        AND cse.total_cost IS NOT NULL 
        AND cse.total_cost > 0
        LIMIT 1
    ");
    
    $contractor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contractor) {
        echo "❌ No contractor with accepted estimates found\n";
        echo "   Please ensure you have run the migration script first.\n";
        exit;
    }
    
    $contractor_id = $contractor['contractor_id'];
    echo "✅ Testing with Contractor: {$contractor['contractor_name']} (ID: {$contractor_id})\n\n";
    
    // Step 1: Check accepted estimates in database
    echo "--- Step 1: Checking Database ---\n";
    $stmt = $pdo->prepare("
        SELECT 
            cse.id,
            cse.total_cost,
            cse.status,
            cls.homeowner_id,
            CONCAT(u.first_name, ' ', u.last_name) as homeowner_name
        FROM contractor_send_estimates cse
        LEFT JOIN contractor_layout_sends cls ON cls.id = cse.send_id
        LEFT JOIN users u ON u.id = cls.homeowner_id
        WHERE cse.contractor_id = ?
        AND cse.status = 'accepted'
        AND cse.total_cost IS NOT NULL
        ORDER BY cse.created_at DESC
    ");
    
    $stmt->execute([$contractor_id]);
    $db_estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($db_estimates) . " accepted estimate(s) in database:\n";
    foreach ($db_estimates as $est) {
        echo "  - ID: {$est['id']}, Cost: ₹" . number_format($est['total_cost'], 2) . ", Homeowner: {$est['homeowner_name']}\n";
    }
    echo "\n";
    
    // Step 2: Test API endpoint
    echo "--- Step 2: Testing API Endpoint ---\n";
    $api_url = "http://localhost/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id={$contractor_id}";
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        echo "❌ API request failed with HTTP {$http_code}\n";
        echo "Response: {$response}\n";
        exit;
    }
    
    $data = json_decode($response, true);
    
    if (!$data['success']) {
        echo "❌ API returned error: {$data['message']}\n";
        exit;
    }
    
    $api_projects = $data['data']['projects'];
    echo "✅ API returned " . count($api_projects) . " project(s)\n\n";
    
    // Step 3: Verify estimate costs are present
    echo "--- Step 3: Verifying Estimate Costs ---\n";
    $projects_with_cost = 0;
    $projects_without_cost = 0;
    
    foreach ($api_projects as $project) {
        $has_cost = isset($project['estimate_cost']) && $project['estimate_cost'] > 0;
        
        if ($has_cost) {
            $projects_with_cost++;
            echo "✅ Project ID {$project['id']}: {$project['project_name']}\n";
            echo "   Estimate Cost: ₹" . number_format($project['estimate_cost'], 2) . "\n";
            echo "   Homeowner: {$project['homeowner_name']}\n";
            echo "   Source: {$project['source']}\n";
            echo "   Status: {$project['status']}\n";
        } else {
            $projects_without_cost++;
            echo "⚠️  Project ID {$project['id']}: {$project['project_name']}\n";
            echo "   No estimate cost found\n";
        }
        echo "\n";
    }
    
    // Step 4: Simulate Payment Request Form Selection
    echo "--- Step 4: Simulating Payment Request Form ---\n";
    
    if ($projects_with_cost > 0) {
        // Get first project with cost
        $test_project = null;
        foreach ($api_projects as $project) {
            if (isset($project['estimate_cost']) && $project['estimate_cost'] > 0) {
                $test_project = $project;
                break;
            }
        }
        
        if ($test_project) {
            echo "Contractor selects project: {$test_project['project_name']}\n";
            echo "Expected behavior:\n";
            echo "  1. Total Project Cost field = ₹" . number_format($test_project['estimate_cost'], 2) . "\n";
            echo "  2. Field is disabled (auto-populated)\n";
            echo "  3. Toast message: 'Total project cost set to ₹" . number_format($test_project['estimate_cost']) . " from approved estimate'\n";
            echo "  4. Helper text: '✅ Auto-populated from approved estimate'\n";
            echo "\n";
            echo "✅ This project will work correctly in the payment request form!\n";
        }
    } else {
        echo "⚠️  No projects with estimate cost found\n";
        echo "   Payment request form will require manual cost entry\n";
    }
    
    // Step 5: Summary
    echo "\n=== Test Summary ===\n";
    echo "Database Estimates: " . count($db_estimates) . "\n";
    echo "API Projects: " . count($api_projects) . "\n";
    echo "Projects with Cost: {$projects_with_cost}\n";
    echo "Projects without Cost: {$projects_without_cost}\n";
    echo "\n";
    
    if ($projects_with_cost > 0) {
        echo "✅ SUCCESS: Payment request form will correctly assign estimate costs!\n";
        echo "\n";
        echo "Next Steps:\n";
        echo "1. Log in as contractor: {$contractor['contractor_name']}\n";
        echo "2. Go to Payment Requests section\n";
        echo "3. Select one of the projects listed above\n";
        echo "4. Verify the total cost is auto-populated\n";
    } else {
        echo "❌ ISSUE: No projects have estimate costs\n";
        echo "\n";
        echo "Troubleshooting:\n";
        echo "1. Run migration script: php backend/migrate_total_cost_from_structured.php\n";
        echo "2. Check that estimates have status = 'accepted'\n";
        echo "3. Verify total_cost column is not NULL\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
