<?php
/**
 * Test script to verify contractor projects API fetches accepted estimates
 */

header('Content-Type: application/json');

try {
    $host = 'localhost';
    $dbname = 'buildhub';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Testing Contractor Projects API with Estimates ===\n\n";
    
    // Get a contractor ID
    $stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role = 'contractor' LIMIT 1");
    $contractor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$contractor) {
        echo "❌ No contractor found in database\n";
        exit;
    }
    
    $contractor_id = $contractor['id'];
    echo "✅ Testing with Contractor: {$contractor['name']} (ID: {$contractor_id})\n\n";
    
    // Check for accepted estimates in contractor_estimates table
    echo "--- Checking contractor_estimates table ---\n";
    $stmt = $pdo->prepare("
        SELECT 
            ce.id,
            ce.project_name,
            ce.total_cost,
            ce.status,
            ce.homeowner_id,
            CONCAT(u.first_name, ' ', u.last_name) as homeowner_name
        FROM contractor_estimates ce
        LEFT JOIN users u ON u.id = ce.homeowner_id
        WHERE ce.contractor_id = ? 
        AND ce.status = 'accepted'
    ");
    $stmt->execute([$contractor_id]);
    $accepted_estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($accepted_estimates)) {
        echo "⚠️  No accepted estimates found in contractor_estimates table\n";
        echo "   Creating a sample accepted estimate...\n";
        
        // Get a homeowner
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'homeowner' LIMIT 1");
        $homeowner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($homeowner) {
            $pdo->exec("
                INSERT INTO contractor_estimates 
                (contractor_id, homeowner_id, project_name, location, total_cost, timeline, status, created_at)
                VALUES 
                ({$contractor_id}, {$homeowner['id']}, 'Test Villa Construction', 'Mumbai', 4500000.00, '6 months', 'accepted', NOW())
            ");
            echo "   ✅ Sample estimate created\n";
            
            // Re-fetch
            $stmt->execute([$contractor_id]);
            $accepted_estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    echo "Found " . count($accepted_estimates) . " accepted estimate(s):\n";
    foreach ($accepted_estimates as $est) {
        echo "  - ID: {$est['id']}, Project: {$est['project_name']}, Cost: ₹" . number_format($est['total_cost']) . ", Homeowner: {$est['homeowner_name']}\n";
    }
    echo "\n";
    
    // Check for accepted estimates in contractor_send_estimates table
    echo "--- Checking contractor_send_estimates table (legacy) ---\n";
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
    ");
    $stmt->execute([$contractor_id]);
    $legacy_estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($legacy_estimates) . " legacy accepted estimate(s)\n";
    foreach ($legacy_estimates as $est) {
        echo "  - ID: {$est['id']}, Cost: ₹" . number_format($est['total_cost']) . ", Homeowner: {$est['homeowner_name']}\n";
    }
    echo "\n";
    
    // Check construction_projects table
    echo "--- Checking construction_projects table ---\n";
    $stmt = $pdo->prepare("
        SELECT id, project_name, total_cost, status
        FROM construction_projects
        WHERE contractor_id = ?
    ");
    $stmt->execute([$contractor_id]);
    $construction_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($construction_projects) . " construction project(s)\n";
    foreach ($construction_projects as $proj) {
        echo "  - ID: {$proj['id']}, Project: {$proj['project_name']}, Cost: ₹" . number_format($proj['total_cost']) . ", Status: {$proj['status']}\n";
    }
    echo "\n";
    
    // Now test the API
    echo "--- Testing get_contractor_projects.php API ---\n";
    $api_url = "http://localhost/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id={$contractor_id}";
    echo "API URL: {$api_url}\n\n";
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Status: {$http_code}\n";
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        
        if ($data['success']) {
            $projects = $data['data']['projects'];
            echo "✅ API returned " . count($projects) . " project(s)\n\n";
            
            foreach ($projects as $project) {
                echo "Project Details:\n";
                echo "  - ID: {$project['id']}\n";
                echo "  - Name: {$project['project_name']}\n";
                echo "  - Homeowner: {$project['homeowner_name']}\n";
                echo "  - Estimate Cost: " . ($project['estimate_cost'] ? "₹" . number_format($project['estimate_cost']) : "Not set") . "\n";
                echo "  - Status: {$project['status']}\n";
                echo "  - Source: {$project['source']}\n";
                echo "  - Needs Project Creation: " . ($project['needs_project_creation'] ? 'Yes' : 'No') . "\n";
                echo "\n";
            }
            
            // Check if any project has estimate_cost
            $projects_with_cost = array_filter($projects, function($p) {
                return $p['estimate_cost'] !== null && $p['estimate_cost'] > 0;
            });
            
            if (count($projects_with_cost) > 0) {
                echo "✅ SUCCESS: " . count($projects_with_cost) . " project(s) have estimate cost!\n";
            } else {
                echo "❌ ISSUE: No projects have estimate cost set\n";
            }
        } else {
            echo "❌ API returned error: {$data['message']}\n";
        }
    } else {
        echo "❌ API request failed with HTTP {$http_code}\n";
        echo "Response: {$response}\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
