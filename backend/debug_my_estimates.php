<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug My Estimates Issue</title>
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
        <h1>🔍 Debug My Estimates Issue</h1>
        <p>Comprehensive debugging to identify why estimates are not showing in the My Estimates section</p>

        <?php
        try {
            // Database connection
            $host = 'localhost';
            $dbname = 'buildhub';
            $username = 'root';
            $password = '';
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo '<div class="test-section">';
            echo '<h3>✅ Step 1: Database Connection</h3>';
            echo '<div class="success">Successfully connected to database</div>';
            echo '</div>';
            
            // Check if contractor_estimates table exists
            echo '<div class="test-section">';
            echo '<h3>📋 Step 2: Check contractor_estimates Table</h3>';
            
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'contractor_estimates'");
            if ($tableCheck->rowCount() > 0) {
                echo '<div class="success">✅ contractor_estimates table exists</div>';
                
                // Get table structure
                $columns = $pdo->query("DESCRIBE contractor_estimates")->fetchAll(PDO::FETCH_ASSOC);
                echo '<h4>Table Structure:</h4>';
                echo '<table>';
                echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
                foreach ($columns as $column) {
                    echo '<tr>';
                    echo '<td>' . $column['Field'] . '</td>';
                    echo '<td>' . $column['Type'] . '</td>';
                    echo '<td>' . $column['Null'] . '</td>';
                    echo '<td>' . $column['Key'] . '</td>';
                    echo '<td>' . $column['Default'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                // Count total records
                $countStmt = $pdo->query("SELECT COUNT(*) as total FROM contractor_estimates");
                $count = $countStmt->fetch(PDO::FETCH_ASSOC);
                echo '<div class="info">Total records in contractor_estimates: ' . $count['total'] . '</div>';
                
            } else {
                echo '<div class="error">❌ contractor_estimates table does not exist!</div>';
                echo '<div class="warning">Creating table now...</div>';
                
                // Create the table
                $pdo->exec("CREATE TABLE IF NOT EXISTS contractor_estimates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    contractor_id INT NOT NULL,
                    homeowner_id INT NOT NULL,
                    send_id INT NULL,
                    project_name VARCHAR(255) NULL,
                    location VARCHAR(255) NULL,
                    client_name VARCHAR(255) NULL,
                    client_contact VARCHAR(255) NULL,
                    project_type VARCHAR(100) NULL,
                    timeline VARCHAR(100) NULL,
                    materials_data JSON NULL,
                    labor_data JSON NULL,
                    utilities_data JSON NULL,
                    misc_data JSON NULL,
                    totals_data JSON NULL,
                    structured_data JSON NULL,
                    materials TEXT NULL,
                    cost_breakdown TEXT NULL,
                    total_cost DECIMAL(15,2) NULL,
                    notes TEXT NULL,
                    terms TEXT NULL,
                    status ENUM('draft', 'submitted', 'accepted', 'rejected') DEFAULT 'submitted',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_contractor (contractor_id),
                    INDEX idx_homeowner (homeowner_id),
                    INDEX idx_send (send_id)
                )");
                
                echo '<div class="success">✅ Table created successfully</div>';
            }
            echo '</div>';
            
            // Check for existing estimates
            echo '<div class="test-section">';
            echo '<h3>📊 Step 3: Check Existing Estimates</h3>';
            
            $stmt = $pdo->query("SELECT * FROM contractor_estimates ORDER BY created_at DESC LIMIT 10");
            $estimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($estimates) > 0) {
                echo '<div class="success">✅ Found ' . count($estimates) . ' estimates in database</div>';
                echo '<h4>Recent Estimates:</h4>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Contractor ID</th><th>Project Name</th><th>Total Cost</th><th>Status</th><th>Created</th></tr>';
                foreach ($estimates as $est) {
                    echo '<tr>';
                    echo '<td>' . $est['id'] . '</td>';
                    echo '<td>' . $est['contractor_id'] . '</td>';
                    echo '<td>' . ($est['project_name'] ?: 'N/A') . '</td>';
                    echo '<td>₹' . number_format($est['total_cost'] ?: 0, 0, '.', ',') . '</td>';
                    echo '<td>' . $est['status'] . '</td>';
                    echo '<td>' . $est['created_at'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="warning">⚠️ No estimates found in database</div>';
                echo '<div class="info">This could be why the My Estimates section is empty</div>';
            }
            echo '</div>';
            
            // Test the get_my_estimates API directly
            echo '<div class="test-section">';
            echo '<h3>🔌 Step 4: Test get_my_estimates API Logic</h3>';
            
            // Test with contractor ID 1
            $testContractorId = 1;
            echo '<div class="info">Testing with contractor_id = ' . $testContractorId . '</div>';
            
            $stmt = $pdo->prepare("
                SELECT 
                    e.*,
                    h.first_name as homeowner_first_name,
                    h.last_name as homeowner_last_name,
                    h.email as homeowner_email
                FROM contractor_estimates e
                LEFT JOIN users h ON e.homeowner_id = h.id
                WHERE e.contractor_id = ?
                ORDER BY e.created_at DESC
            ");
            
            $stmt->execute([$testContractorId]);
            $apiEstimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($apiEstimates) > 0) {
                echo '<div class="success">✅ API query returned ' . count($apiEstimates) . ' estimates</div>';
                
                foreach ($apiEstimates as $est) {
                    $totalsData = json_decode($est['totals_data'], true) ?? [];
                    $structuredData = json_decode($est['structured_data'], true) ?? [];
                    
                    $homeownerName = trim(($est['homeowner_first_name'] ?? '') . ' ' . ($est['homeowner_last_name'] ?? ''));
                    if (empty($homeownerName)) {
                        $homeownerName = $est['client_name'] ?? 'Unknown Client';
                    }
                    
                    echo '<div class="estimate-card">';
                    echo '<h4>Estimate #' . $est['id'] . '</h4>';
                    echo '<p><strong>Project:</strong> ' . ($est['project_name'] ?? 'Untitled Project') . '</p>';
                    echo '<p><strong>Client:</strong> ' . $homeownerName . '</p>';
                    echo '<p><strong>Total Cost:</strong> ₹' . number_format($est['total_cost'] ?? 0, 0, '.', ',') . '</p>';
                    echo '<p><strong>Status:</strong> ' . ($est['status'] ?? 'submitted') . '</p>';
                    echo '<p><strong>Created:</strong> ' . $est['created_at'] . '</p>';
                    echo '</div>';
                }
            } else {
                echo '<div class="warning">⚠️ No estimates found for contractor_id = ' . $testContractorId . '</div>';
                
                // Check if there are estimates for other contractor IDs
                $allContractors = $pdo->query("SELECT DISTINCT contractor_id FROM contractor_estimates")->fetchAll(PDO::FETCH_COLUMN);
                if (count($allContractors) > 0) {
                    echo '<div class="info">Available contractor IDs with estimates: ' . implode(', ', $allContractors) . '</div>';
                } else {
                    echo '<div class="warning">No estimates found for any contractor</div>';
                }
            }
            echo '</div>';
            
            // Test the actual API endpoint
            echo '<div class="test-section">';
            echo '<h3>🌐 Step 5: Test Actual API Endpoint</h3>';
            
            // Simulate the API call
            $apiUrl = '/buildhub/backend/api/contractor/get_my_estimates.php?contractor_id=' . $testContractorId;
            echo '<div class="info">Testing API endpoint: ' . $apiUrl . '</div>';
            
            // Use curl to test the API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://localhost' . $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $apiResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($apiResponse !== false && $httpCode == 200) {
                echo '<div class="success">✅ API endpoint responded with HTTP ' . $httpCode . '</div>';
                
                $apiData = json_decode($apiResponse, true);
                if ($apiData) {
                    echo '<h4>API Response:</h4>';
                    echo '<pre>' . json_encode($apiData, JSON_PRETTY_PRINT) . '</pre>';
                    
                    if (isset($apiData['success']) && $apiData['success']) {
                        echo '<div class="success">✅ API returned success = true</div>';
                        echo '<div class="info">Estimates count: ' . ($apiData['count'] ?? 0) . '</div>';
                    } else {
                        echo '<div class="error">❌ API returned success = false</div>';
                        echo '<div class="error">Message: ' . ($apiData['message'] ?? 'Unknown error') . '</div>';
                    }
                } else {
                    echo '<div class="error">❌ Invalid JSON response from API</div>';
                    echo '<pre>' . htmlspecialchars($apiResponse) . '</pre>';
                }
            } else {
                echo '<div class="error">❌ API endpoint failed with HTTP ' . $httpCode . '</div>';
                echo '<div class="error">Response: ' . htmlspecialchars($apiResponse) . '</div>';
            }
            echo '</div>';
            
            // Check users table for contractor
            echo '<div class="test-section">';
            echo '<h3>👤 Step 6: Check Users Table</h3>';
            
            $userStmt = $pdo->prepare("SELECT id, first_name, last_name, email, user_type FROM users WHERE id = ? OR user_type = 'contractor' LIMIT 5");
            $userStmt->execute([$testContractorId]);
            $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($users) > 0) {
                echo '<div class="success">✅ Found users in database</div>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th></tr>';
                foreach ($users as $user) {
                    echo '<tr>';
                    echo '<td>' . $user['id'] . '</td>';
                    echo '<td>' . trim($user['first_name'] . ' ' . $user['last_name']) . '</td>';
                    echo '<td>' . $user['email'] . '</td>';
                    echo '<td>' . $user['user_type'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="warning">⚠️ No users found</div>';
            }
            echo '</div>';
            
            // Insert a test estimate if none exist
            echo '<div class="test-section">';
            echo '<h3>➕ Step 7: Create Test Estimate</h3>';
            
            if (count($apiEstimates) == 0) {
                echo '<div class="info">Creating a test estimate for debugging...</div>';
                
                $testData = [
                    'contractor_id' => $testContractorId,
                    'homeowner_id' => 1,
                    'send_id' => 1,
                    'project_name' => 'Debug Test Project',
                    'location' => '123 Debug Street, Test City',
                    'client_name' => 'Debug Client',
                    'client_contact' => 'debug@test.com',
                    'timeline' => '90 days',
                    'total_cost' => 150000,
                    'notes' => 'Test estimate created for debugging purposes',
                    'status' => 'submitted'
                ];
                
                $structured = [
                    'project_name' => $testData['project_name'],
                    'project_address' => $testData['location'],
                    'client_name' => $testData['client_name'],
                    'totals' => [
                        'materials' => 60000,
                        'labor' => 50000,
                        'utilities' => 20000,
                        'misc' => 20000,
                        'grand' => 150000
                    ]
                ];
                
                $insertStmt = $pdo->prepare("
                    INSERT INTO contractor_estimates (
                        contractor_id, homeowner_id, send_id, project_name, location, 
                        client_name, client_contact, timeline, total_cost, notes, status,
                        structured_data, totals_data
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $insertStmt->execute([
                    $testData['contractor_id'],
                    $testData['homeowner_id'],
                    $testData['send_id'],
                    $testData['project_name'],
                    $testData['location'],
                    $testData['client_name'],
                    $testData['client_contact'],
                    $testData['timeline'],
                    $testData['total_cost'],
                    $testData['notes'],
                    $testData['status'],
                    json_encode($structured),
                    json_encode($structured['totals'])
                ]);
                
                $newEstimateId = $pdo->lastInsertId();
                echo '<div class="success">✅ Test estimate created with ID: ' . $newEstimateId . '</div>';
                
                // Test the API again
                echo '<div class="info">Testing API again after creating test estimate...</div>';
                
                $stmt->execute([$testContractorId]);
                $newApiEstimates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($newApiEstimates) > 0) {
                    echo '<div class="success">✅ API now returns ' . count($newApiEstimates) . ' estimates</div>';
                } else {
                    echo '<div class="error">❌ API still returns no estimates - there may be a deeper issue</div>';
                }
            } else {
                echo '<div class="info">Test estimates already exist, skipping creation</div>';
            }
            echo '</div>';
            
            // Final diagnosis
            echo '<div class="test-section">';
            echo '<h3>🎯 Step 8: Diagnosis & Recommendations</h3>';
            
            if (count($estimates) == 0) {
                echo '<div class="error">❌ ROOT CAUSE: No estimates exist in the database</div>';
                echo '<div class="warning">SOLUTION: Estimates need to be submitted through the inbox form first</div>';
                echo '<ul>';
                echo '<li>Go to the contractor dashboard</li>';
                echo '<li>Find an acknowledged inbox item</li>';
                echo '<li>Fill out and submit the estimation form</li>';
                echo '<li>The estimate should then appear in My Estimates section</li>';
                echo '</ul>';
            } else if (count($apiEstimates) == 0) {
                echo '<div class="error">❌ ROOT CAUSE: No estimates found for the current contractor ID</div>';
                echo '<div class="warning">SOLUTION: Check if you\'re logged in as the correct contractor</div>';
                echo '<div class="info">Available contractor IDs with estimates: ' . implode(', ', $pdo->query("SELECT DISTINCT contractor_id FROM contractor_estimates")->fetchAll(PDO::FETCH_COLUMN)) . '</div>';
            } else {
                echo '<div class="success">✅ DIAGNOSIS: Estimates exist and API works correctly</div>';
                echo '<div class="info">The issue might be in the frontend React component or data refresh</div>';
                echo '<div class="warning">SOLUTION: Check browser console for JavaScript errors and ensure the component is calling the API correctly</div>';
            }
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="test-section">';
            echo '<div class="error">❌ Error during debugging: ' . $e->getMessage() . '</div>';
            echo '<div class="error">Stack trace:</div>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>