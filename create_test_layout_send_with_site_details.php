<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Test Layout Send with Site Details</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .info { background: #d1ecf1; border-color: #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .btn { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🚀 Create Test Layout Send with Site Details</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once 'backend/config/database.php';
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            echo '<div class="section info">';
            echo '<h2>Creating Test Layout Send...</h2>';
            
            // Get the latest layout request for homeowner 28
            $layoutRequestStmt = $db->prepare("
                SELECT 
                    plot_size, building_size, budget_range, location, timeline, 
                    num_floors, orientation, site_considerations, material_preferences,
                    budget_allocation, preferred_style, requirements
                FROM layout_requests 
                WHERE homeowner_id = 28 
                AND status NOT IN ('deleted', 'rejected')
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $layoutRequestStmt->execute();
            $layoutRequestRow = $layoutRequestStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$layoutRequestRow) {
                echo '<div class="error">❌ No layout requests found for homeowner 28</div>';
                exit;
            }
            
            echo '<h3>✅ Found Layout Request Data:</h3>';
            echo '<pre>' . print_r($layoutRequestRow, true) . '</pre>';
            
            // Parse requirements if it's JSON
            $parsed_requirements = null;
            if (!empty($layoutRequestRow['requirements'])) {
                try {
                    $parsed_requirements = json_decode($layoutRequestRow['requirements'], true);
                } catch (Throwable $e) {
                    // Keep original requirements if JSON parsing fails
                }
            }
            
            // Get technical details from the latest house plan
            $housePlanStmt = $db->prepare("
                SELECT technical_details, plot_width, plot_height, total_area
                FROM house_plans 
                WHERE architect_id = 29 
                AND technical_details IS NOT NULL 
                AND technical_details != ''
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $housePlanStmt->execute();
            $housePlanRow = $housePlanStmt->fetch(PDO::FETCH_ASSOC);
            
            $technical_details = null;
            if ($housePlanRow && !empty($housePlanRow['technical_details'])) {
                $technical_details = json_decode($housePlanRow['technical_details'], true);
                echo '<h3>✅ Found Technical Details:</h3>';
                echo '<pre>Construction Cost: ' . ($technical_details['construction_cost'] ?? 'NOT SET') . '</pre>';
                echo '<pre>Foundation Type: ' . ($technical_details['foundation_type'] ?? 'NOT SET') . '</pre>';
                echo '<pre>Structure Type: ' . ($technical_details['structure_type'] ?? 'NOT SET') . '</pre>';
            }
            
            // Create comprehensive payload
            $payload = [
                'layout_id' => null,
                'design_id' => null,
                'message' => 'Test layout send with comprehensive site details for estimation form testing',
                'forwarded_design' => null,
                'layout_image_url' => null,
                'floor_details' => null,
                'technical_details' => $technical_details,
                'plot_size' => $layoutRequestRow['plot_size'] ?: '2000 sq ft',
                'building_size' => $layoutRequestRow['building_size'] ?: '1800 sq ft',
                'layout_request_details' => [
                    'plot_size' => $layoutRequestRow['plot_size'] ?: '2000 sq ft',
                    'building_size' => $layoutRequestRow['building_size'] ?: '1800 sq ft',
                    'budget_range' => $layoutRequestRow['budget_range'] ?: '10-15 lakhs',
                    'location' => $layoutRequestRow['location'] ?: 'Mumbai, Maharashtra',
                    'timeline' => $layoutRequestRow['timeline'] ?: '6-12 months',
                    'num_floors' => $layoutRequestRow['num_floors'] ?: '2',
                    'orientation' => $layoutRequestRow['orientation'] ?: 'South-facing',
                    'site_considerations' => $layoutRequestRow['site_considerations'] ?: 'Standard residential site',
                    'material_preferences' => $layoutRequestRow['material_preferences'] ?: 'Granite, Vitrified Tiles',
                    'budget_allocation' => $layoutRequestRow['budget_allocation'] ?: 'Balanced approach',
                    'preferred_style' => $layoutRequestRow['preferred_style'] ?: 'Modern',
                    'requirements' => $layoutRequestRow['requirements'],
                    'parsed_requirements' => $parsed_requirements
                ]
            ];
            
            echo '<h3>📦 Creating Payload:</h3>';
            echo '<pre>' . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
            
            // Insert into contractor_layout_sends
            $insertStmt = $db->prepare("
                INSERT INTO contractor_layout_sends 
                (contractor_id, homeowner_id, layout_id, design_id, message, payload) 
                VALUES (37, 28, NULL, NULL, ?, ?)
            ");
            
            $message = 'Test layout send with comprehensive site details for estimation form testing';
            $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            $insertStmt->bindValue(1, $message, PDO::PARAM_STR);
            $insertStmt->bindValue(2, $payloadJson, PDO::PARAM_STR);
            $insertStmt->execute();
            
            $insertId = $db->lastInsertId();
            
            echo '<div class="success">';
            echo '<h3>✅ Test Layout Send Created Successfully!</h3>';
            echo '<p><strong>Insert ID:</strong> ' . $insertId . '</p>';
            echo '<p><strong>Contractor ID:</strong> 37</p>';
            echo '<p><strong>Homeowner ID:</strong> 28</p>';
            echo '<p><strong>Message:</strong> ' . $message . '</p>';
            echo '</div>';
            
            echo '<h3>🎯 Next Steps:</h3>';
            echo '<ol>';
            echo '<li>Go to contractor dashboard (contractor ID: 37)</li>';
            echo '<li>Check the inbox for the new layout send</li>';
            echo '<li>Open the estimation form</li>';
            echo '<li>Verify that site details appear in Basic Information and Site Details sections</li>';
            echo '</ol>';
            
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="section error">';
            echo '<h2>❌ Database Error</h2>';
            echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
    } else {
        ?>
        <div class="section info">
            <h2>Test Layout Send Creation</h2>
            <p>This will create a test layout send from homeowner 28 to contractor 37 with comprehensive site details.</p>
            
            <h3>What this will do:</h3>
            <ul>
                <li>✅ Get the latest layout request data for homeowner 28</li>
                <li>✅ Get technical details from the latest house plan</li>
                <li>✅ Create a comprehensive payload with layout_request_details</li>
                <li>✅ Insert the test data into contractor_layout_sends table</li>
            </ul>
            
            <form method="POST">
                <button type="submit" class="btn">🚀 Create Test Layout Send</button>
            </form>
        </div>
        <?php
    }
    ?>
</body>
</html>