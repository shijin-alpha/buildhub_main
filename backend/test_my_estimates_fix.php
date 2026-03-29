<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test My Estimates Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: #27ae60; background: #d5f4e6; padding: 10px; border-radius: 4px; }
        .error { color: #e74c3c; background: #fadbd8; padding: 10px; border-radius: 4px; }
        .info { color: #3498db; background: #ebf3fd; padding: 10px; border-radius: 4px; }
        .warning { color: #f39c12; background: #fef9e7; padding: 10px; border-radius: 4px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .estimate-card { border: 1px solid #e0e0e0; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Test My Estimates Fix</h1>
        <p>Testing the updated get_my_estimates.php API to ensure it reads from both tables</p>

        <?php
        try {
            // Database connection
            require_once 'config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            echo '<div class="test-section">';
            echo '<h3>✅ Step 1: Database Connection</h3>';
            echo '<div class="success">Successfully connected to database</div>';
            echo '</div>';
            
            // Test contractor ID
            $testContractorId = 1;
            
            // Check both tables
            echo '<div class="test-section">';
            echo '<h3>📊 Step 2: Check Both Tables</h3>';
            
            // Check contractor_estimates table
            $newEstimatesStmt = $db->prepare("SELECT COUNT(*) as count FROM contractor_estimates WHERE contractor_id = ?");
            $newEstimatesStmt->execute([$testContractorId]);
            $newCount = $newEstimatesStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Check contractor_send_estimates table
            $legacyEstimatesStmt = $db->prepare("SELECT COUNT(*) as count FROM contractor_send_estimates WHERE contractor_id = ?");
            $legacyEstimatesStmt->execute([$testContractorId]);
            $legacyCount = $legacyEstimatesStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo '<div class="info">contractor_estimates table: ' . $newCount . ' estimates</div>';
            echo '<div class="info">contractor_send_estimates table: ' . $legacyCount . ' estimates</div>';
            echo '<div class="info">Total expected: ' . ($newCount + $legacyCount) . ' estimates</div>';
            echo '</div>';
            
            // Create a test estimate in contractor_estimates if none exist
            if ($newCount == 0) {
                echo '<div class="test-section">';
                echo '<h3>➕ Step 3: Create Test Estimate</h3>';
                echo '<div class="info">Creating a test estimate in contractor_estimates table...</div>';
                
                $testData = [
                    'contractor_id' => $testContractorId,
                    'homeowner_id' => 1,
                    'inbox_item_id' => 1,
                    'project_name' => 'Test Project - My Estimates Fix',
                    'location' => '123 Test Street, Fix City',
                    'client_name' => 'Test Client',
                    'timeline' => '90 days',
                    'notes' => 'Test estimate created to verify My Estimates fix',
                    'status' => 'submitted'
                ];
                
                $totalsData = [
                    'materials' => 75000,
                    'labor' => 60000,
                    'utilities' => 25000,
                    'misc' => 15000,
                    'grand' => 175000
                ];
                
                $materialsData = [
                    'cement' => ['name' => 'Cement', 'qty' => '60', 'rate' => '450', 'amount' => '27000'],
                    'sand' => ['name' => 'Sand', 'qty' => '6', 'rate' => '2500', 'amount' => '15000'],
                    'bricks' => ['name' => 'Bricks', 'qty' => '3000', 'rate' => '10', 'amount' => '30000']
                ];
                
                $laborData = [
                    'masonry' => ['name' => 'Masonry Work', 'qty' => '1', 'rate' => '35000', 'amount' => '35000'],
                    'plumbing' => ['name' => 'Plumbing', 'qty' => '1', 'rate' => '15000', 'amount' => '15000'],
                    'electrical' => ['name' => 'Electrical', 'qty' => '1', 'rate' => '10000', 'amount' => '10000']
                ];
                
                $insertStmt = $db->prepare("
                    INSERT INTO contractor_estimates (
                        contractor_id, homeowner_id, inbox_item_id, project_name, location, 
                        client_name, timeline, notes, status, materials_data, labor_data, 
                        utilities_data, misc_data, totals_data
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $insertStmt->execute([
                    $testData['contractor_id'],
                    $testData['homeowner_id'],
                    $testData['inbox_item_id'],
                    $testData['project_name'],
                    $testData['location'],
                    $testData['client_name'],
                    $testData['timeline'],
                    $testData['notes'],
                    $testData['status'],
                    json_encode($materialsData),
                    json_encode($laborData),
                    json_encode([]),
                    json_encode([]),
                    json_encode($totalsData)
                ]);
                
                $newEstimateId = $db->lastInsertId();
                echo '<div class="success">✅ Test estimate created with ID: ' . $newEstimateId . '</div>';
                echo '</div>';
            }
            
            // Test the updated API
            echo '<div class="test-section">';
            echo '<h3>🔌 Step 4: Test Updated API</h3>';
            
            // Simulate the API call by including the logic directly
            ob_start();
            $_GET['contractor_id'] = $testContractorId;
            include 'api/contractor/get_my_estimates.php';
            $apiResponse = ob_get_clean();
            
            echo '<div class="info">API Response:</div>';
            echo '<pre>' . htmlspecialchars($apiResponse) . '</pre>';
            
            $apiData = json_decode($apiResponse, true);
            if ($apiData && isset($apiData['success']) && $apiData['success']) {
                echo '<div class="success">✅ API returned success = true</div>';
                echo '<div class="info">Total estimates returned: ' . count($apiData['estimates']) . '</div>';
                echo '<div class="info">New estimates: ' . ($apiData['new_estimates_count'] ?? 0) . '</div>';
                echo '<div class="info">Legacy estimates: ' . ($apiData['legacy_estimates_count'] ?? 0) . '</div>';
                
                if (count($apiData['estimates']) > 0) {
                    echo '<h4>Returned Estimates:</h4>';
                    foreach ($apiData['estimates'] as $est) {
                        echo '<div class="estimate-card">';
                        echo '<h5>Estimate #' . $est['id'] . ' (' . ($est['source_table'] ?? 'unknown') . ')</h5>';
                        echo '<p><strong>Project:</strong> ' . ($est['project_name'] ?? 'N/A') . '</p>';
                        echo '<p><strong>Client:</strong> ' . ($est['homeowner_name'] ?? $est['client_name'] ?? 'N/A') . '</p>';
                        echo '<p><strong>Total:</strong> ₹' . number_format($est['total_cost'] ?? 0, 0, '.', ',') . '</p>';
                        echo '<p><strong>Status:</strong> ' . ($est['status'] ?? 'submitted') . '</p>';
                        echo '<p><strong>Created:</strong> ' . $est['created_at'] . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="warning">⚠️ No estimates returned despite database having data</div>';
                }
            } else {
                echo '<div class="error">❌ API returned error or invalid response</div>';
                if ($apiData && isset($apiData['message'])) {
                    echo '<div class="error">Error message: ' . $apiData['message'] . '</div>';
                }
            }
            echo '</div>';
            
            // Final status
            echo '<div class="test-section">';
            echo '<h3>🎯 Step 5: Final Status</h3>';
            
            if ($apiData && isset($apiData['success']) && $apiData['success'] && count($apiData['estimates']) > 0) {
                echo '<div class="success">✅ SUCCESS: My Estimates fix is working correctly!</div>';
                echo '<div class="info">The contractor dashboard should now show submitted estimates in the My Estimates section.</div>';
            } else {
                echo '<div class="error">❌ ISSUE: The fix may not be working as expected</div>';
                echo '<div class="warning">Check the API response above for more details</div>';
            }
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="test-section">';
            echo '<div class="error">❌ Error during testing: ' . $e->getMessage() . '</div>';
            echo '<div class="error">Stack trace:</div>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>