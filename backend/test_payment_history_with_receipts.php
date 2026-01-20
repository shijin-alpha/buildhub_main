<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Testing Payment History API with Receipts...\n\n";
    
    // Test 1: Check if receipt columns exist
    echo "1. Checking database schema...\n";
    $columns = $db->query("DESCRIBE stage_payment_requests");
    $columnNames = [];
    while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
        $columnNames[] = $column['Field'];
    }
    
    $requiredColumns = [
        'transaction_reference', 'payment_date', 'receipt_file_path', 
        'payment_method', 'verification_status', 'verified_by', 
        'verified_at', 'verification_notes'
    ];
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columnNames)) {
            echo "   ✅ Column '$col' exists\n";
        } else {
            echo "   ❌ Column '$col' missing\n";
        }
    }
    
    // Test 2: Check sample data
    echo "\n2. Checking sample payment data...\n";
    $sampleQuery = "SELECT COUNT(*) as count FROM stage_payment_requests WHERE receipt_file_path IS NOT NULL";
    $sampleResult = $db->query($sampleQuery);
    $sampleCount = $sampleResult->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Found $sampleCount payment requests with receipt data\n";
    
    // Test 3: Test API endpoint directly
    echo "\n3. Testing API endpoint...\n";
    
    // Get a project with payment data
    $projectQuery = "SELECT DISTINCT project_id FROM stage_payment_requests LIMIT 1";
    $projectResult = $db->query($projectQuery);
    $project = $projectResult->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
        $project_id = $project['project_id'];
        echo "   Testing with project ID: $project_id\n";
        
        // Simulate API call by setting up the environment
        $originalGet = $_GET;
        $originalSession = $_SESSION ?? [];
        
        $_GET = ['project_id' => $project_id];
        $_SESSION = ['user_id' => 29]; // Contractor ID from our sample data
        
        // Change to API directory and include
        $currentDir = getcwd();
        chdir(__DIR__ . '/api/contractor');
        
        try {
            ob_start();
            include 'get_payment_history.php';
            $apiOutput = ob_get_clean();
        } finally {
            chdir($currentDir);
            $_GET = $originalGet;
            $_SESSION = $originalSession;
        }
        
        // Parse JSON response
        $apiData = json_decode($apiOutput, true);
        
        if ($apiData && $apiData['success']) {
            echo "   ✅ API call successful\n";
            echo "   Found " . count($apiData['data']['payment_requests']) . " payment requests\n";
            
            // Check for receipt data
            $receiptsFound = 0;
            foreach ($apiData['data']['payment_requests'] as $request) {
                if ($request['receipt_file_path']) {
                    $receiptsFound++;
                    echo "   ✅ Receipt data found for: " . $request['stage_name'] . "\n";
                    echo "      Payment method: " . ($request['payment_method'] ?: 'Not specified') . "\n";
                    echo "      Transaction ref: " . ($request['transaction_reference'] ?: 'Not specified') . "\n";
                    echo "      Verification status: " . ($request['verification_status'] ?: 'Not specified') . "\n";
                    echo "      Files: " . (is_array($request['receipt_file_path']) ? count($request['receipt_file_path']) : 0) . "\n";
                }
            }
            
            echo "   Total requests with receipts: $receiptsFound\n";
            
            // Test 4: Verify JSON structure
            echo "\n4. Verifying JSON structure...\n";
            $firstRequest = $apiData['data']['payment_requests'][0] ?? null;
            if ($firstRequest) {
                $expectedFields = [
                    'id', 'stage_name', 'requested_amount', 'status', 
                    'transaction_reference', 'payment_date', 'payment_method',
                    'receipt_file_path', 'verification_status'
                ];
                
                foreach ($expectedFields as $field) {
                    if (array_key_exists($field, $firstRequest)) {
                        echo "   ✅ Field '$field' present\n";
                    } else {
                        echo "   ❌ Field '$field' missing\n";
                    }
                }
            }
            
        } else {
            echo "   ❌ API call failed\n";
            echo "   Error: " . ($apiData['message'] ?? 'Unknown error') . "\n";
            echo "   Raw output: " . substr($apiOutput, 0, 200) . "...\n";
        }
        
    } else {
        echo "   ❌ No projects found with payment data\n";
    }
    
    // Test 5: Check receipt file structure
    echo "\n5. Checking receipt file structure...\n";
    $receiptQuery = "SELECT receipt_file_path FROM stage_payment_requests WHERE receipt_file_path IS NOT NULL LIMIT 1";
    $receiptResult = $db->query($receiptQuery);
    $receiptData = $receiptResult->fetch(PDO::FETCH_ASSOC);
    
    if ($receiptData && $receiptData['receipt_file_path']) {
        $receiptFiles = json_decode($receiptData['receipt_file_path'], true);
        if ($receiptFiles && is_array($receiptFiles)) {
            echo "   ✅ Receipt file data is valid JSON array\n";
            echo "   Sample file structure:\n";
            $firstFile = $receiptFiles[0] ?? null;
            if ($firstFile) {
                foreach ($firstFile as $key => $value) {
                    echo "      $key: $value\n";
                }
            }
        } else {
            echo "   ❌ Receipt file data is not valid JSON\n";
        }
    } else {
        echo "   ❌ No receipt file data found\n";
    }
    
    echo "\n🎉 Payment History with Receipts test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>