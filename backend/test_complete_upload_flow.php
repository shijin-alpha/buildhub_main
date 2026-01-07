<?php
require_once 'config/database.php';

echo "=== Testing Complete Upload Flow ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Step 1: Create a test layout request
    echo "1. Creating test layout request...\n";
    $stmt = $db->prepare("
        INSERT INTO layout_requests (user_id, homeowner_id, plot_size, budget_range, requirements, status, created_at)
        VALUES (19, 19, '30x40', '20-30 lakhs', 'Test request for upload flow', 'assigned', NOW())
    ");
    $stmt->execute();
    $requestId = $db->lastInsertId();
    echo "   ✓ Created layout request ID: $requestId\n";
    
    // Step 2: Create a house plan
    echo "\n2. Creating house plan...\n";
    $planData = [
        'plan_name' => 'Test Upload Plan',
        'plot_width' => 30,
        'plot_height' => 40,
        'rooms' => [],
        'scale_ratio' => 1.2,
        'total_layout_area' => 0,
        'total_construction_area' => 0,
        'floors' => [
            'total_floors' => 1,
            'current_floor' => 1,
            'floor_names' => ['1' => 'Ground Floor']
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO house_plans (
            layout_request_id, architect_id, plan_name, plot_width, plot_height, 
            plan_data, notes, status, created_at
        ) VALUES (
            :layout_request_id, 27, :plan_name, :plot_width, :plot_height,
            :plan_data, 'Test plan for upload flow', 'draft', NOW()
        )
    ");
    
    $stmt->execute([
        ':layout_request_id' => $requestId,
        ':plan_name' => $planData['plan_name'],
        ':plot_width' => $planData['plot_width'],
        ':plot_height' => $planData['plot_height'],
        ':plan_data' => json_encode($planData)
    ]);
    
    $planId = $db->lastInsertId();
    echo "   ✓ Created house plan ID: $planId\n";
    
    // Step 3: Simulate technical details with file upload
    echo "\n3. Simulating technical details with file upload...\n";
    
    // Create a test image file
    $uploadDir = 'uploads/house_plans/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $testImageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $testFileName = $planId . '_layout_image_test.png';
    $testFilePath = $uploadDir . $testFileName;
    file_put_contents($testFilePath, $testImageContent);
    echo "   ✓ Created test image file: $testFileName\n";
    
    // Technical details with uploaded file info
    $technicalDetails = [
        'foundation_type' => 'RCC',
        'structure_type' => 'RCC Frame',
        'construction_cost' => '25,00,000',
        'unlock_price' => '8000',
        'layout_image' => [
            'name' => 'test_layout.png',
            'stored' => $testFileName,
            'size' => strlen($testImageContent),
            'type' => 'image/png',
            'uploaded' => true,
            'pending_upload' => false,
            'upload_time' => date('Y-m-d H:i:s')
        ]
    ];
    
    // Step 4: Submit plan with technical details
    echo "\n4. Submitting plan with technical details...\n";
    $stmt = $db->prepare("
        UPDATE house_plans 
        SET status = 'submitted', 
            technical_details = :technical_details,
            unlock_price = 8000,
            updated_at = NOW()
        WHERE id = :id
    ");
    
    $stmt->execute([
        ':id' => $planId,
        ':technical_details' => json_encode($technicalDetails)
    ]);
    echo "   ✓ Updated plan with technical details\n";
    
    // Step 5: Test homeowner API
    echo "\n5. Testing homeowner received designs API...\n";
    
    // Simulate homeowner session
    session_start();
    $_SESSION['user_id'] = 19; // Homeowner ID
    
    // Include the API file to test it
    ob_start();
    include 'api/homeowner/get_received_designs.php';
    $apiOutput = ob_get_clean();
    
    $apiResult = json_decode($apiOutput, true);
    
    if ($apiResult && $apiResult['success']) {
        echo "   ✓ API returned success\n";
        
        $housePlans = array_filter($apiResult['designs'], function($d) {
            return $d['source_type'] === 'house_plan';
        });
        
        if (!empty($housePlans)) {
            $housePlan = $housePlans[0];
            echo "   ✓ Found house plan in results\n";
            
            if (!empty($housePlan['files'])) {
                $layoutFile = array_filter($housePlan['files'], function($f) {
                    return $f['type'] === 'layout_image';
                });
                
                if (!empty($layoutFile)) {
                    $file = array_values($layoutFile)[0];
                    echo "   ✓ Layout image found in files\n";
                    echo "     - Original: " . $file['original'] . "\n";
                    echo "     - Stored: " . $file['stored'] . "\n";
                    echo "     - Path: " . $file['path'] . "\n";
                    
                    // Check if file exists
                    $fullPath = str_replace('/buildhub/backend/', '', $file['path']);
                    if (file_exists($fullPath)) {
                        echo "   ✓ File exists on server\n";
                    } else {
                        echo "   ✗ File NOT found on server: $fullPath\n";
                    }
                } else {
                    echo "   ✗ No layout image found in files\n";
                }
            } else {
                echo "   ✗ No files found in house plan\n";
            }
        } else {
            echo "   ✗ No house plans found in results\n";
        }
    } else {
        echo "   ✗ API failed: " . ($apiResult['message'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n=== Test Complete ===\n";
    echo "House Plan ID: $planId\n";
    echo "Layout Request ID: $requestId\n";
    echo "Test file: $testFilePath\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>