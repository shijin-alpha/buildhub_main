<?php
/**
 * Complete Progress Flow Test
 * Test the entire flow from input to database and back
 */

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Complete Progress Flow Test ===\n\n";
    
    // Test data
    $test_project_id = 1;
    $test_contractor_id = 29;
    $test_homeowner_id = 28;
    $test_date = date('Y-m-d');
    $test_incremental = '5'; // This is what user enters
    
    echo "1. Input Value: " . $test_incremental . "\n";
    
    // Simulate backend processing
    $processed_value = round((float)$test_incremental, 2);
    echo "2. After backend processing: " . $processed_value . "\n";
    
    // Test database insertion
    echo "3. Testing database insertion...\n";
    
    // First, delete any existing test record
    $deleteStmt = $db->prepare("DELETE FROM daily_progress_updates WHERE project_id = ? AND contractor_id = ? AND update_date = ?");
    $deleteStmt->execute([$test_project_id, $test_contractor_id, $test_date]);
    
    // Insert test record
    $insertStmt = $db->prepare("
        INSERT INTO daily_progress_updates (
            project_id, contractor_id, homeowner_id, update_date, construction_stage,
            work_done_today, incremental_completion_percentage, cumulative_completion_percentage,
            working_hours, weather_condition, site_issues, progress_photos
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $insertStmt->execute([
        $test_project_id,
        $test_contractor_id, 
        $test_homeowner_id,
        $test_date,
        'Foundation',
        'Test work',
        $processed_value, // This is the processed value
        $processed_value,
        8.0,
        'Sunny',
        '',
        '[]'
    ]);
    
    if ($result) {
        echo "   ✓ Database insertion successful\n";
        $inserted_id = $db->lastInsertId();
        echo "   Inserted ID: " . $inserted_id . "\n";
    } else {
        echo "   ❌ Database insertion failed\n";
        print_r($insertStmt->errorInfo());
    }
    
    // Retrieve the value back from database
    echo "4. Retrieving value from database...\n";
    
    $selectStmt = $db->prepare("
        SELECT incremental_completion_percentage, cumulative_completion_percentage 
        FROM daily_progress_updates 
        WHERE project_id = ? AND contractor_id = ? AND update_date = ?
        ORDER BY id DESC LIMIT 1
    ");
    $selectStmt->execute([$test_project_id, $test_contractor_id, $test_date]);
    $retrieved = $selectStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($retrieved) {
        echo "   Retrieved incremental: " . $retrieved['incremental_completion_percentage'] . "\n";
        echo "   Retrieved cumulative: " . $retrieved['cumulative_completion_percentage'] . "\n";
        
        // Check if values match
        if ($retrieved['incremental_completion_percentage'] == $test_incremental) {
            echo "   ✓ Values match!\n";
        } else {
            echo "   ❌ Values don't match!\n";
            echo "   Expected: " . $test_incremental . "\n";
            echo "   Got: " . $retrieved['incremental_completion_percentage'] . "\n";
        }
    } else {
        echo "   ❌ Could not retrieve record\n";
    }
    
    // Test the API endpoint directly
    echo "\n5. Testing API endpoint simulation...\n";
    
    // Simulate POST data
    $_POST = [
        'project_id' => $test_project_id,
        'contractor_id' => $test_contractor_id,
        'update_date' => date('Y-m-d', strtotime('+1 day')), // Use tomorrow to avoid conflicts
        'construction_stage' => 'Foundation',
        'work_done_today' => 'API test work',
        'incremental_completion_percentage' => '5', // User input
        'working_hours' => '8',
        'weather_condition' => 'Sunny',
        'site_issues' => '',
        'labour_data' => '[]'
    ];
    
    echo "   POST incremental_completion_percentage: " . $_POST['incremental_completion_percentage'] . "\n";
    
    // Simulate the exact processing from the API
    $api_processed = isset($_POST['incremental_completion_percentage']) ? round((float)$_POST['incremental_completion_percentage'], 2) : 0;
    echo "   API processed value: " . $api_processed . "\n";
    
    // Test different data types
    echo "\n6. Testing different data type handling...\n";
    
    $test_values = ['5', 5, 5.0, '5.0', '5.00'];
    foreach ($test_values as $val) {
        $processed = round((float)$val, 2);
        echo "   Input: " . var_export($val, true) . " -> Processed: " . $processed . "\n";
    }
    
    // Clean up test record
    $deleteStmt->execute([$test_project_id, $test_contractor_id, $test_date]);
    echo "\n7. Test record cleaned up\n";
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}