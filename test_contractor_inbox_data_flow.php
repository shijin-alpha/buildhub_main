<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Contractor Inbox Data Flow</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .info { background: #d1ecf1; border-color: #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .highlight { background: yellow; padding: 2px 4px; }
    </style>
</head>
<body>
    <h1>🔍 Test Contractor Inbox Data Flow</h1>
    
    <?php
    require_once 'backend/config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        echo '<div class="section info">';
        echo '<h2>1. Check Layout Requests Table</h2>';
        
        // Get layout requests for homeowner 28 (SHIJIN THOMAS)
        $layoutStmt = $db->prepare("
            SELECT id, homeowner_id, plot_size, building_size, budget_range, timeline, 
                   num_floors, orientation, material_preferences, preferred_style, 
                   location, status, created_at
            FROM layout_requests 
            WHERE homeowner_id = 28 
            AND status NOT IN ('deleted', 'rejected')
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $layoutStmt->execute();
        $layoutRequests = $layoutStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($layoutRequests) {
            echo '<div class="success">';
            echo '<h3>✅ Found ' . count($layoutRequests) . ' layout requests for homeowner 28</h3>';
            foreach ($layoutRequests as $request) {
                echo '<h4>Layout Request ID: ' . $request['id'] . '</h4>';
                echo '<pre>';
                echo 'Plot Size: <span class="highlight">' . ($request['plot_size'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Building Size: <span class="highlight">' . ($request['building_size'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Budget Range: <span class="highlight">' . ($request['budget_range'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Timeline: <span class="highlight">' . ($request['timeline'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Number of Floors: <span class="highlight">' . ($request['num_floors'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Orientation: <span class="highlight">' . ($request['orientation'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Material Preferences: <span class="highlight">' . ($request['material_preferences'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Preferred Style: <span class="highlight">' . ($request['preferred_style'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Location: <span class="highlight">' . ($request['location'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Status: <span class="highlight">' . $request['status'] . '</span>' . "\n";
                echo 'Created: ' . $request['created_at'] . "\n";
                echo '</pre>';
            }
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<h3>❌ No layout requests found for homeowner 28</h3>';
            echo '<p>This is why the estimation form is empty!</p>';
            echo '</div>';
        }
        echo '</div>';
        
        echo '<div class="section info">';
        echo '<h2>2. Check Contractor Layout Sends Table</h2>';
        
        // Check contractor_layout_sends for contractor 37
        $sendsStmt = $db->prepare("
            SELECT id, contractor_id, homeowner_id, layout_id, design_id, 
                   message, payload, created_at
            FROM contractor_layout_sends 
            WHERE contractor_id = 37
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $sendsStmt->execute();
        $layoutSends = $sendsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($layoutSends) {
            echo '<div class="success">';
            echo '<h3>✅ Found ' . count($layoutSends) . ' layout sends for contractor 37</h3>';
            foreach ($layoutSends as $send) {
                echo '<h4>Layout Send ID: ' . $send['id'] . '</h4>';
                echo '<pre>';
                echo 'Contractor ID: ' . $send['contractor_id'] . "\n";
                echo 'Homeowner ID: ' . ($send['homeowner_id'] ?: 'NOT SET') . "\n";
                echo 'Layout ID: ' . ($send['layout_id'] ?: 'NOT SET') . "\n";
                echo 'Design ID: ' . ($send['design_id'] ?: 'NOT SET') . "\n";
                echo 'Message: ' . ($send['message'] ?: 'NOT SET') . "\n";
                echo 'Created: ' . $send['created_at'] . "\n";
                echo "\n" . 'Payload:' . "\n";
                if ($send['payload']) {
                    $payload = json_decode($send['payload'], true);
                    if ($payload) {
                        echo 'Plot Size: <span class="highlight">' . ($payload['plot_size'] ?: 'NOT SET') . '</span>' . "\n";
                        echo 'Building Size: <span class="highlight">' . ($payload['building_size'] ?: 'NOT SET') . '</span>' . "\n";
                        echo 'Layout Request Details: <span class="highlight">' . ($payload['layout_request_details'] ? 'AVAILABLE' : 'NOT SET') . '</span>' . "\n";
                        if ($payload['layout_request_details']) {
                            echo "\nLayout Request Details:\n";
                            foreach ($payload['layout_request_details'] as $key => $value) {
                                echo "  $key: <span class=\"highlight\">" . ($value ?: 'NOT SET') . "</span>\n";
                            }
                        }
                        echo "\nFull Payload:\n" . json_encode($payload, JSON_PRETTY_PRINT);
                    } else {
                        echo 'Invalid JSON payload';
                    }
                } else {
                    echo '<span class="highlight">NO PAYLOAD</span>';
                }
                echo '</pre>';
            }
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<h3>❌ No layout sends found for contractor 37</h3>';
            echo '<p>No data has been sent to this contractor!</p>';
            echo '</div>';
        }
        echo '</div>';
        
        echo '<div class="section info">';
        echo '<h2>3. Test Data Flow Simulation</h2>';
        
        if ($layoutRequests && $layoutSends) {
            echo '<div class="success">';
            echo '<h3>✅ Data Flow Test</h3>';
            echo '<p>Simulating how data should flow from layout_requests to contractor inbox:</p>';
            
            $latestRequest = $layoutRequests[0];
            $latestSend = $layoutSends[0];
            
            echo '<h4>Source Data (Layout Request):</h4>';
            echo '<pre>';
            echo 'Plot Size: <span class="highlight">' . ($latestRequest['plot_size'] ?: 'NOT SET') . '</span>' . "\n";
            echo 'Building Size: <span class="highlight">' . ($latestRequest['building_size'] ?: 'NOT SET') . '</span>' . "\n";
            echo 'Budget Range: <span class="highlight">' . ($latestRequest['budget_range'] ?: 'NOT SET') . '</span>' . "\n";
            echo '</pre>';
            
            echo '<h4>Current Payload Data:</h4>';
            echo '<pre>';
            if ($latestSend['payload']) {
                $payload = json_decode($latestSend['payload'], true);
                echo 'Plot Size: <span class="highlight">' . ($payload['plot_size'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Building Size: <span class="highlight">' . ($payload['building_size'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Layout Request Details: <span class="highlight">' . ($payload['layout_request_details'] ? 'AVAILABLE' : 'NOT SET') . '</span>' . "\n";
            } else {
                echo '<span class="highlight">NO PAYLOAD DATA</span>';
            }
            echo '</pre>';
            
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<h3>❌ Cannot Test Data Flow</h3>';
            echo '<p>Missing either layout requests or layout sends data.</p>';
            echo '</div>';
        }
        echo '</div>';
        
        echo '<div class="section info">';
        echo '<h2>4. Recommendations</h2>';
        
        if (!$layoutRequests) {
            echo '<div class="error">';
            echo '<h3>🔧 Fix Required: Create Layout Request</h3>';
            echo '<p>1. Go to homeowner dashboard</p>';
            echo '<p>2. Create a new layout request with plot size and building size</p>';
            echo '<p>3. Make sure to fill in all the site details</p>';
            echo '</div>';
        }
        
        if (!$layoutSends) {
            echo '<div class="error">';
            echo '<h3>🔧 Fix Required: Send Layout to Contractor</h3>';
            echo '<p>1. From homeowner dashboard, send a layout to contractor</p>';
            echo '<p>2. This will trigger the enhanced send_to_contractor.php API</p>';
            echo '<p>3. The API should include layout_request_details in the payload</p>';
            echo '</div>';
        }
        
        if ($layoutRequests && $layoutSends) {
            $latestSend = $layoutSends[0];
            if ($latestSend['payload']) {
                $payload = json_decode($latestSend['payload'], true);
                if (!$payload['layout_request_details']) {
                    echo '<div class="error">';
                    echo '<h3>🔧 Fix Required: Update Send API</h3>';
                    echo '<p>The send_to_contractor.php API is not including layout_request_details.</p>';
                    echo '<p>Check if the enhanced API changes were applied correctly.</p>';
                    echo '</div>';
                }
            }
        }
        
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="section error">';
        echo '<h2>❌ Database Error</h2>';
        echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    ?>
    
    <div class="section info">
        <h2>5. Next Steps</h2>
        <ol>
            <li>Run this test to identify where the data flow is broken</li>
            <li>If layout requests exist but layout sends don't have the data, re-send a layout from homeowner to contractor</li>
            <li>Check the contractor inbox API response to see if site details are included</li>
            <li>Verify the estimation form receives and displays the data correctly</li>
        </ol>
    </div>
</body>
</html>