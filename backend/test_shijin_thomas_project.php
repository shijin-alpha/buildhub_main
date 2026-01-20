<?php
/**
 * Test: Verify Shijin Thomas's project with project_created status appears
 */

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Testing Shijin Thomas Project ===\n\n";
    
    // Get Shijin Thomas's contractor ID
    $stmt = $pdo->query("
        SELECT id, CONCAT(first_name, ' ', last_name) as name
        FROM users 
        WHERE first_name = 'Shijin' AND last_name = 'Thomas'
        LIMIT 1
    ");
    
    $contractor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contractor) {
        echo "❌ Shijin Thomas not found in database\n";
        exit;
    }
    
    $contractor_id = $contractor['id'];
    echo "✅ Found Contractor: {$contractor['name']} (ID: {$contractor_id})\n\n";
    
    // Check estimate in database
    echo "--- Database Check ---\n";
    $stmt = $pdo->prepare("
        SELECT id, status, total_cost
        FROM contractor_send_estimates
        WHERE contractor_id = ? AND id = 37
    ");
    $stmt->execute([$contractor_id]);
    $estimate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($estimate) {
        echo "✅ Estimate ID 37 found:\n";
        echo "   Status: {$estimate['status']}\n";
        echo "   Total Cost: ₹" . number_format($estimate['total_cost'], 2) . "\n\n";
    } else {
        echo "❌ Estimate ID 37 not found\n";
        exit;
    }
    
    // Test API
    echo "--- API Test ---\n";
    $api_url = "http://localhost/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id={$contractor_id}";
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        echo "❌ API request failed with HTTP {$http_code}\n";
        exit;
    }
    
    $data = json_decode($response, true);
    
    if (!$data['success']) {
        echo "❌ API error: {$data['message']}\n";
        exit;
    }
    
    $projects = $data['data']['projects'];
    echo "✅ API returned " . count($projects) . " project(s)\n\n";
    
    // Check if ID 37 is in the results
    $found = false;
    foreach ($projects as $project) {
        if ($project['id'] == 37) {
            $found = true;
            echo "✅ Project ID 37 FOUND in API response!\n";
            echo "   Project Name: {$project['project_name']}\n";
            echo "   Estimate Cost: ₹" . number_format($project['estimate_cost'], 2) . "\n";
            echo "   Homeowner: {$project['homeowner_name']}\n";
            echo "   Status: {$project['status']}\n";
            echo "   Source: {$project['source']}\n";
            break;
        }
    }
    
    if (!$found) {
        echo "❌ Project ID 37 NOT found in API response\n";
        echo "\nAll projects returned:\n";
        foreach ($projects as $project) {
            echo "  - ID: {$project['id']}, Name: {$project['project_name']}, Cost: ₹" . number_format($project['estimate_cost'], 2) . "\n";
        }
    }
    
    echo "\n=== Test Result ===\n";
    if ($found) {
        echo "✅ SUCCESS: Shijin Thomas can now see and use this project!\n";
        echo "\nNext Steps:\n";
        echo "1. Log in as Shijin Thomas\n";
        echo "2. Go to Payment Requests section\n";
        echo "3. Select the project from dropdown\n";
        echo "4. Total cost should auto-fill with ₹1,069,745.00\n";
    } else {
        echo "❌ FAILED: Project not appearing in API\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
