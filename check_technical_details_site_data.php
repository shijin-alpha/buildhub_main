<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Technical Details Site Data</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .info { background: #d1ecf1; border-color: #bee5eb; }
        .warning { background: #fff3cd; border-color: #ffeaa7; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .highlight { background: yellow; padding: 2px 4px; font-weight: bold; }
        .data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .data-table th { background: #f8f9fa; font-weight: bold; }
        .empty-value { color: #dc3545; font-style: italic; }
        .has-value { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Check Technical Details Site Data</h1>
    
    <?php
    require_once 'backend/config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        echo '<div class="section info">';
        echo '<h2>1. Check Layout Requests Table for Site Details</h2>';
        
        // Get layout requests with site details
        $layoutStmt = $db->prepare("
            SELECT id, homeowner_id, plot_size, building_size, budget_range, timeline, 
                   num_floors, orientation, material_preferences, preferred_style, 
                   location, status, requirements, created_at
            FROM layout_requests 
            WHERE homeowner_id = 28 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $layoutStmt->execute();
        $layoutRequests = $layoutStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($layoutRequests) {
            echo '<div class="success">';
            echo '<h3>✅ Found ' . count($layoutRequests) . ' layout requests for homeowner 28</h3>';
            
            echo '<table class="data-table">';
            echo '<tr><th>ID</th><th>Plot Size</th><th>Building Size</th><th>Budget Range</th><th>Timeline</th><th>Floors</th><th>Orientation</th><th>Status</th></tr>';
            
            foreach ($layoutRequests as $request) {
                echo '<tr>';
                echo '<td>' . $request['id'] . '</td>';
                echo '<td class="' . ($request['plot_size'] ? 'has-value' : 'empty-value') . '">' . ($request['plot_size'] ?: 'EMPTY') . '</td>';
                echo '<td class="' . ($request['building_size'] ? 'has-value' : 'empty-value') . '">' . ($request['building_size'] ?: 'EMPTY') . '</td>';
                echo '<td class="' . ($request['budget_range'] ? 'has-value' : 'empty-value') . '">' . ($request['budget_range'] ?: 'EMPTY') . '</td>';
                echo '<td class="' . ($request['timeline'] ? 'has-value' : 'empty-value') . '">' . ($request['timeline'] ?: 'EMPTY') . '</td>';
                echo '<td class="' . ($request['num_floors'] ? 'has-value' : 'empty-value') . '">' . ($request['num_floors'] ?: 'EMPTY') . '</td>';
                echo '<td class="' . ($request['orientation'] ? 'has-value' : 'empty-value') . '">' . ($request['orientation'] ?: 'EMPTY') . '</td>';
                echo '<td>' . $request['status'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            // Show detailed view of the latest request
            $latestRequest = $layoutRequests[0];
            echo '<h4>📋 Latest Request Details (ID: ' . $latestRequest['id'] . ')</h4>';
            echo '<pre>';
            echo 'Plot Size: <span class="highlight">' . ($latestRequest['plot_size'] ?: 'NOT SET') . '</span>' . "\n";
            echo 'Building Size: <span class="highlight">' . ($latestRequest['building_size'] ?: 'NOT SET') . '</span>' . "\n";
            echo 'Budget Range: <span class="highlight">' . ($latestRequest['budget_range'] ?: 'NOT SET') . '</span>' . "\n";
            echo 'Timeline: <span class="highlight">' . ($latestRequest['timeline'] ?: 'NOT SET') . '</span>' . "\n";
            echo 'Number of Floors: <span class="highlight">' . ($latestRequest['num_floors'] ?: 'NOT SET') . '</span>' . "\n";
            echo 'Orientation: <span class="highlight">' . ($latestRequest['orientation'] ?: 'NOT SET') . '</span>' . "\n";
            echo 'Material Preferences: <span class="highlight">' . ($latestRequest['material_preferences'] ?: 'NOT SET') . '</span>' . "\n";
            echo 'Preferred Style: <span class="highlight">' . ($latestRequest['preferred_style'] ?: 'NOT SET') . '</span>' . "\n";
            echo 'Location: <span class="highlight">' . ($latestRequest['location'] ?: 'NOT SET') . '</span>' . "\n";
            echo 'Status: <span class="highlight">' . $latestRequest['status'] . '</span>' . "\n";
            echo 'Created: ' . $latestRequest['created_at'] . "\n";
            
            if ($latestRequest['requirements']) {
                echo "\nRequirements (JSON):\n";
                $requirements = json_decode($latestRequest['requirements'], true);
                if ($requirements) {
                    foreach ($requirements as $key => $value) {
                        echo "  $key: <span class=\"highlight\">" . ($value ?: 'NOT SET') . "</span>\n";
                    }
                } else {
                    echo "  Invalid JSON: " . $latestRequest['requirements'] . "\n";
                }
            }
            echo '</pre>';
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<h3>❌ No layout requests found for homeowner 28</h3>';
            echo '</div>';
        }
        echo '</div>';
        
        echo '<div class="section info">';
        echo '<h2>2. Check House Plans Technical Details</h2>';
        
        // Check house plans with technical details
        $housePlanStmt = $db->prepare("
            SELECT id, plan_name, technical_details, plot_width, plot_height, total_area, 
                   unlock_price, status, created_at
            FROM house_plans 
            WHERE architect_id = 29 OR layout_request_id IN (
                SELECT id FROM layout_requests WHERE homeowner_id = 28
            )
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $housePlanStmt->execute();
        $housePlans = $housePlanStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($housePlans) {
            echo '<div class="success">';
            echo '<h3>✅ Found ' . count($housePlans) . ' house plans with technical details</h3>';
            
            foreach ($housePlans as $plan) {
                echo '<h4>🏠 House Plan: ' . $plan['plan_name'] . ' (ID: ' . $plan['id'] . ')</h4>';
                echo '<pre>';
                echo 'Plot Dimensions: <span class="highlight">' . $plan['plot_width'] . ' × ' . $plan['plot_height'] . '</span>' . "\n";
                echo 'Total Area: <span class="highlight">' . ($plan['total_area'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Unlock Price: <span class="highlight">₹' . ($plan['unlock_price'] ?: 'NOT SET') . '</span>' . "\n";
                echo 'Status: <span class="highlight">' . $plan['status'] . '</span>' . "\n";
                echo 'Created: ' . $plan['created_at'] . "\n";
                
                if ($plan['technical_details']) {
                    echo "\nTechnical Details:\n";
                    $techDetails = json_decode($plan['technical_details'], true);
                    if ($techDetails) {
                        // Check for site-related fields in technical details
                        $siteFields = [
                            'site_area', 'built_up_area', 'carpet_area', 'plot_size', 'building_size',
                            'setback_front', 'setback_rear', 'setback_left', 'setback_right',
                            'construction_cost', 'foundation_type', 'structure_type'
                        ];
                        
                        echo "Site-Related Fields:\n";
                        foreach ($siteFields as $field) {
                            if (isset($techDetails[$field])) {
                                echo "  $field: <span class=\"highlight\">" . ($techDetails[$field] ?: 'EMPTY') . "</span>\n";
                            }
                        }
                        
                        echo "\nAll Technical Detail Fields:\n";
                        foreach ($techDetails as $key => $value) {
                            if (is_array($value)) {
                                echo "  $key: [ARRAY - " . count($value) . " items]\n";
                            } else {
                                $displayValue = $value ?: 'EMPTY';
                                if (strlen($displayValue) > 50) {
                                    $displayValue = substr($displayValue, 0, 50) . '...';
                                }
                                echo "  $key: <span class=\"highlight\">$displayValue</span>\n";
                            }
                        }
                    } else {
                        echo "  Invalid JSON technical details\n";
                    }
                } else {
                    echo "\n<span class=\"highlight\">NO TECHNICAL DETAILS</span>\n";
                }
                echo '</pre>';
            }
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<h3>❌ No house plans found with technical details</h3>';
            echo '</div>';
        }
        echo '</div>';
        
        echo '<div class="section info">';
        echo '<h2>3. Check Contractor Layout Sends Payload</h2>';
        
        // Check contractor layout sends payload
        $sendsStmt = $db->prepare("
            SELECT id, contractor_id, homeowner_id, layout_id, design_id, 
                   message, payload, created_at
            FROM contractor_layout_sends 
            WHERE contractor_id = 37 OR homeowner_id = 28
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $sendsStmt->execute();
        $layoutSends = $sendsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($layoutSends) {
            echo '<div class="success">';
            echo '<h3>✅ Found ' . count($layoutSends) . ' layout sends</h3>';
            
            foreach ($layoutSends as $send) {
                echo '<h4>📤 Layout Send ID: ' . $send['id'] . ' (Contractor: ' . $send['contractor_id'] . ', Homeowner: ' . $send['homeowner_id'] . ')</h4>';
                echo '<pre>';
                echo 'Created: ' . $send['created_at'] . "\n";
                echo 'Message: ' . ($send['message'] ?: 'NO MESSAGE') . "\n";
                
                if ($send['payload']) {
                    echo "\nPayload Analysis:\n";
                    $payload = json_decode($send['payload'], true);
                    if ($payload) {
                        // Check for site details in payload
                        echo "Direct Site Fields:\n";
                        echo "  plot_size: <span class=\"highlight\">" . ($payload['plot_size'] ?: 'NOT SET') . "</span>\n";
                        echo "  building_size: <span class=\"highlight\">" . ($payload['building_size'] ?: 'NOT SET') . "</span>\n";
                        
                        echo "\nLayout Request Details:\n";
                        if (isset($payload['layout_request_details']) && $payload['layout_request_details']) {
                            foreach ($payload['layout_request_details'] as $key => $value) {
                                echo "  $key: <span class=\"highlight\">" . ($value ?: 'NOT SET') . "</span>\n";
                            }
                        } else {
                            echo "  <span class=\"highlight\">NO LAYOUT REQUEST DETAILS</span>\n";
                        }
                        
                        echo "\nTechnical Details:\n";
                        if (isset($payload['technical_details']) && $payload['technical_details']) {
                            $techDetails = $payload['technical_details'];
                            if (is_array($techDetails)) {
                                $siteFields = ['site_area', 'built_up_area', 'carpet_area', 'construction_cost'];
                                foreach ($siteFields as $field) {
                                    if (isset($techDetails[$field])) {
                                        echo "  $field: <span class=\"highlight\">" . ($techDetails[$field] ?: 'EMPTY') . "</span>\n";
                                    }
                                }
                            }
                        } else {
                            echo "  <span class=\"highlight\">NO TECHNICAL DETAILS</span>\n";
                        }
                        
                        echo "\nAll Payload Keys:\n";
                        foreach ($payload as $key => $value) {
                            if (is_array($value)) {
                                echo "  $key: [ARRAY - " . count($value) . " items]\n";
                            } else {
                                $displayValue = $value ?: 'EMPTY';
                                if (strlen($displayValue) > 50) {
                                    $displayValue = substr($displayValue, 0, 50) . '...';
                                }
                                echo "  $key: <span class=\"highlight\">$displayValue</span>\n";
                            }
                        }
                    } else {
                        echo "Invalid JSON payload\n";
                    }
                } else {
                    echo "\n<span class=\"highlight\">NO PAYLOAD</span>\n";
                }
                echo '</pre>';
            }
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<h3>❌ No layout sends found</h3>';
            echo '</div>';
        }
        echo '</div>';
        
        echo '<div class="section warning">';
        echo '<h2>4. Data Availability Summary</h2>';
        
        $hasLayoutRequests = !empty($layoutRequests);
        $hasHousePlans = !empty($housePlans);
        $hasLayoutSends = !empty($layoutSends);
        
        $layoutRequestsWithData = 0;
        $housePlansWithTechDetails = 0;
        $layoutSendsWithPayload = 0;
        
        if ($hasLayoutRequests) {
            foreach ($layoutRequests as $request) {
                if ($request['plot_size'] || $request['building_size'] || $request['budget_range']) {
                    $layoutRequestsWithData++;
                }
            }
        }
        
        if ($hasHousePlans) {
            foreach ($housePlans as $plan) {
                if ($plan['technical_details']) {
                    $housePlansWithTechDetails++;
                }
            }
        }
        
        if ($hasLayoutSends) {
            foreach ($layoutSends as $send) {
                if ($send['payload']) {
                    $payload = json_decode($send['payload'], true);
                    if ($payload && (isset($payload['plot_size']) || isset($payload['layout_request_details']))) {
                        $layoutSendsWithPayload++;
                    }
                }
            }
        }
        
        echo '<table class="data-table">';
        echo '<tr><th>Data Source</th><th>Total Found</th><th>With Site Data</th><th>Status</th></tr>';
        echo '<tr>';
        echo '<td>Layout Requests</td>';
        echo '<td>' . count($layoutRequests) . '</td>';
        echo '<td class="' . ($layoutRequestsWithData > 0 ? 'has-value' : 'empty-value') . '">' . $layoutRequestsWithData . '</td>';
        echo '<td>' . ($layoutRequestsWithData > 0 ? '✅ Available' : '❌ Missing') . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>House Plans</td>';
        echo '<td>' . count($housePlans) . '</td>';
        echo '<td class="' . ($housePlansWithTechDetails > 0 ? 'has-value' : 'empty-value') . '">' . $housePlansWithTechDetails . '</td>';
        echo '<td>' . ($housePlansWithTechDetails > 0 ? '✅ Available' : '❌ Missing') . '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td>Layout Sends</td>';
        echo '<td>' . count($layoutSends) . '</td>';
        echo '<td class="' . ($layoutSendsWithPayload > 0 ? 'has-value' : 'empty-value') . '">' . $layoutSendsWithPayload . '</td>';
        echo '<td>' . ($layoutSendsWithPayload > 0 ? '✅ Available' : '❌ Missing') . '</td>';
        echo '</tr>';
        echo '</table>';
        
        echo '<h3>🔧 Recommendations:</h3>';
        echo '<ul>';
        
        if ($layoutRequestsWithData == 0) {
            echo '<li class="empty-value">❌ <strong>Create Layout Request:</strong> Go to homeowner dashboard and create a layout request with plot size, building size, and budget details.</li>';
        } else {
            echo '<li class="has-value">✅ Layout requests have site data available.</li>';
        }
        
        if ($housePlansWithTechDetails == 0) {
            echo '<li class="empty-value">❌ <strong>Add Technical Details:</strong> House plans need technical details with site specifications.</li>';
        } else {
            echo '<li class="has-value">✅ House plans have technical details available.</li>';
        }
        
        if ($layoutSendsWithPayload == 0) {
            echo '<li class="empty-value">❌ <strong>Send Layout to Contractor:</strong> Send a layout from homeowner to contractor to trigger the enhanced API.</li>';
        } else {
            echo '<li class="has-value">✅ Layout sends have payload data available.</li>';
        }
        
        echo '</ul>';
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
            <li><strong>If Layout Requests are empty:</strong> Create a new layout request with complete site details</li>
            <li><strong>If House Plans lack technical details:</strong> Add technical details with site specifications</li>
            <li><strong>If Layout Sends lack payload:</strong> Re-send a layout from homeowner to contractor</li>
            <li><strong>Test the estimation form:</strong> Open contractor dashboard and check if site details appear</li>
        </ol>
        
        <h3>🎯 Expected Result:</h3>
        <p>After fixing the data issues, the contractor estimation form should show:</p>
        <ul>
            <li>✅ Plot Size in Basic Information section</li>
            <li>✅ Building Size in Basic Information section</li>
            <li>✅ Complete Site Details section with all specifications</li>
            <li>✅ Technical Details section with construction specifications</li>
        </ul>
    </div>
</body>
</html>