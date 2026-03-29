<?php
/**
 * Check Custom Payment Request Flow
 * Verify the complete flow from submission to homeowner display
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=buildhub', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Checking Custom Payment Request Flow\n\n";
    
    // 1. Check if any custom payment requests exist
    echo "1. Checking custom payment requests in database:\n";
    $stmt = $pdo->query("SELECT * FROM custom_payment_requests ORDER BY created_at DESC");
    $customRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($customRequests)) {
        echo "   ❌ No custom payment requests found in database\n";
        echo "   This means either:\n";
        echo "   - Submissions are failing silently\n";
        echo "   - Frontend is not calling the API correctly\n";
        echo "   - API is not inserting data\n\n";
    } else {
        echo "   ✅ Found " . count($customRequests) . " custom payment requests:\n";
        foreach ($customRequests as $req) {
            echo "   - ID: {$req['id']}, Project: {$req['project_id']}, Title: {$req['request_title']}, Amount: ₹" . number_format($req['requested_amount']) . ", Status: {$req['status']}\n";
        }
        echo "\n";
    }
    
    // 2. Check homeowner payment requests API
    echo "2. Checking homeowner payment requests API:\n";
    $homeowner_id = 28; // Assuming homeowner ID 28
    
    // Check if the API file exists
    $apiFile = 'backend/api/homeowner/get_payment_requests.php';
    if (file_exists($apiFile)) {
        echo "   ✅ Homeowner payment requests API exists\n";
        
        // Check if it includes custom payment requests
        $apiContent = file_get_contents($apiFile);
        if (strpos($apiContent, 'custom_payment_requests') !== false) {
            echo "   ✅ API includes custom payment requests\n";
        } else {
            echo "   ❌ API does NOT include custom payment requests\n";
            echo "   This is likely the main issue!\n";
        }
    } else {
        echo "   ❌ Homeowner payment requests API does not exist\n";
    }
    echo "\n";
    
    // 3. Check what the homeowner should see
    echo "3. What homeowner should see for project 37:\n";
    
    // Stage payment requests
    $stmt = $pdo->prepare("
        SELECT 'stage' as type, id, stage_name as title, requested_amount, status, request_date, contractor_id, homeowner_id
        FROM stage_payment_requests 
        WHERE project_id = 37 AND homeowner_id = ?
    ");
    $stmt->execute([$homeowner_id]);
    $stageRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Custom payment requests
    $stmt = $pdo->prepare("
        SELECT 'custom' as type, id, request_title as title, requested_amount, status, request_date, contractor_id, homeowner_id
        FROM custom_payment_requests 
        WHERE project_id = 37 AND homeowner_id = ?
    ");
    $stmt->execute([$homeowner_id]);
    $customRequestsForHomeowner = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $allRequests = array_merge($stageRequests, $customRequestsForHomeowner);
    
    if (empty($allRequests)) {
        echo "   ❌ No payment requests found for homeowner $homeowner_id on project 37\n";
    } else {
        echo "   ✅ Found " . count($allRequests) . " payment requests:\n";
        foreach ($allRequests as $req) {
            echo "   - Type: {$req['type']}, ID: {$req['id']}, Title: {$req['title']}, Amount: ₹" . number_format($req['requested_amount']) . ", Status: {$req['status']}\n";
        }
    }
    echo "\n";
    
    // 4. Check homeowner dashboard component
    echo "4. Checking homeowner dashboard integration:\n";
    $homeownerDashboardFile = 'frontend/src/components/HomeownerDashboard.jsx';
    if (file_exists($homeownerDashboardFile)) {
        echo "   ✅ HomeownerDashboard.jsx exists\n";
        
        $dashboardContent = file_get_contents($homeownerDashboardFile);
        if (strpos($dashboardContent, 'custom_payment') !== false || strpos($dashboardContent, 'CustomPayment') !== false) {
            echo "   ✅ Dashboard includes custom payment references\n";
        } else {
            echo "   ❌ Dashboard does NOT include custom payment references\n";
            echo "   Need to add custom payment display to homeowner dashboard\n";
        }
    } else {
        echo "   ❌ HomeownerDashboard.jsx not found\n";
    }
    echo "\n";
    
    // 5. Identify what needs to be fixed
    echo "5. Issues to fix:\n";
    
    if (empty($customRequests)) {
        echo "   🔧 PRIORITY 1: Fix custom payment request submission\n";
        echo "      - Check API endpoint is being called correctly\n";
        echo "      - Verify data is being inserted into database\n";
        echo "      - Check for JavaScript errors in browser console\n";
    }
    
    if (file_exists($apiFile)) {
        $apiContent = file_get_contents($apiFile);
        if (strpos($apiContent, 'custom_payment_requests') === false) {
            echo "   🔧 PRIORITY 2: Update homeowner payment requests API\n";
            echo "      - Add custom_payment_requests to the query\n";
            echo "      - Merge custom requests with stage requests\n";
            echo "      - Return unified payment request list\n";
        }
    }
    
    if (file_exists($homeownerDashboardFile)) {
        $dashboardContent = file_get_contents($homeownerDashboardFile);
        if (strpos($dashboardContent, 'custom_payment') === false) {
            echo "   🔧 PRIORITY 3: Update homeowner dashboard\n";
            echo "      - Add custom payment request display\n";
            echo "      - Show both stage and custom requests\n";
            echo "      - Add approval/rejection functionality\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>