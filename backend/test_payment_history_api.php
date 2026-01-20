<?php
session_start();

// Set up a test session for contractor ID 28 (from the sample data)
$_SESSION['user_id'] = 28;

echo "Testing Payment History API...\n";
echo "Contractor ID: " . $_SESSION['user_id'] . "\n\n";

// Test 1: Get contractor projects
echo "=== Test 1: Get Contractor Projects ===\n";
$_GET['contractor_id'] = 28;

ob_start();
include 'api/contractor/get_contractor_projects.php';
$projects_response = ob_get_clean();

echo "Projects Response:\n";
echo $projects_response . "\n\n";

$projects_data = json_decode($projects_response, true);
if ($projects_data && $projects_data['success'] && !empty($projects_data['data']['projects'])) {
    $first_project = $projects_data['data']['projects'][0];
    $project_id = $first_project['id'];
    
    echo "Found project ID: $project_id\n";
    echo "Project Name: " . $first_project['project_name'] . "\n\n";
    
    // Test 2: Get payment history for this project
    echo "=== Test 2: Get Payment History ===\n";
    $_GET['project_id'] = $project_id;
    
    ob_start();
    include 'api/contractor/get_payment_history.php';
    $history_response = ob_get_clean();
    
    echo "Payment History Response:\n";
    echo $history_response . "\n\n";
    
    $history_data = json_decode($history_response, true);
    if ($history_data && $history_data['success']) {
        echo "✅ Payment history API working correctly!\n";
        echo "Total requests: " . count($history_data['data']['payment_requests']) . "\n";
        echo "Summary: " . json_encode($history_data['data']['summary'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Payment history API failed: " . ($history_data['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "❌ No projects found for contractor\n";
    echo "Creating a sample project for testing...\n";
    
    // Create a sample project directly in the database
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=buildhub;charset=utf8", 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if we have any layout requests
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM layout_requests WHERE status = 'approved'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo "✅ Found approved layout requests in database\n";
            
            // Get the first approved project
            $stmt = $pdo->query("SELECT id FROM layout_requests WHERE status = 'approved' LIMIT 1");
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($project) {
                $project_id = $project['id'];
                echo "Using project ID: $project_id\n";
                
                // Test payment history with this project
                $_GET['project_id'] = $project_id;
                
                ob_start();
                include 'api/contractor/get_payment_history.php';
                $history_response = ob_get_clean();
                
                echo "Payment History Response:\n";
                echo $history_response . "\n";
            }
        } else {
            echo "❌ No approved layout requests found in database\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
?>