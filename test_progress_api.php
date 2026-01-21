<?php
/**
 * Test the progress APIs to see what data they return
 */

echo "<h1>🧪 Progress API Test</h1>\n";

// Test get_project_progress.php
echo "<h2>Test 1: get_project_progress.php</h2>\n";
try {
    $url = 'http://localhost/buildhub/backend/api/contractor/get_project_progress.php?project_id=37';
    $response = file_get_contents($url);
    
    if ($response === false) {
        echo "<p>❌ Failed to fetch API response</p>\n";
    } else {
        $data = json_decode($response, true);
        echo "<h3>API Response:</h3>\n";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>\n";
        
        if ($data && $data['success']) {
            echo "<p>✅ API call successful</p>\n";
            echo "<p><strong>Progress Updates Count:</strong> " . count($data['progress_updates']) . "</p>\n";
            
            if (!empty($data['progress_updates'])) {
                echo "<h4>Progress Updates:</h4>\n";
                foreach ($data['progress_updates'] as $update) {
                    echo "<p>Stage: {$update['construction_stage']}, Incremental: {$update['incremental_completion_percentage']}%, Cumulative: {$update['cumulative_completion_percentage']}%, Date: {$update['update_date']}</p>\n";
                }
            }
        } else {
            echo "<p>❌ API call failed: " . ($data['message'] ?? 'Unknown error') . "</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Test get_project_current_progress.php
echo "<h2>Test 2: get_project_current_progress.php</h2>\n";
try {
    $url = 'http://localhost/buildhub/backend/api/contractor/get_project_current_progress.php?project_id=37';
    $response = file_get_contents($url);
    
    if ($response === false) {
        echo "<p>❌ Failed to fetch API response</p>\n";
    } else {
        $data = json_decode($response, true);
        echo "<h3>API Response:</h3>\n";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>\n";
        
        if ($data && $data['success']) {
            echo "<p>✅ API call successful</p>\n";
            echo "<p><strong>Current Progress:</strong> " . $data['data']['current_progress'] . "%</p>\n";
        } else {
            echo "<p>❌ API call failed: " . ($data['message'] ?? 'Unknown error') . "</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>