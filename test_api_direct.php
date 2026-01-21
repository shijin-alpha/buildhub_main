<?php
// Simulate the API call directly
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'architect';

echo "=== Testing API Direct ===\n\n";

// Include the API file directly
ob_start();
include 'backend/api/architect/get_concept_previews.php';
$output = ob_get_clean();

echo "API Output:\n";
echo $output . "\n";

// Parse the JSON response
$data = json_decode($output, true);
if ($data) {
    echo "\nParsed Response:\n";
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    
    if (isset($data['previews'])) {
        echo "Number of previews: " . count($data['previews']) . "\n";
        
        foreach ($data['previews'] as $preview) {
            echo "\n--- Preview ---\n";
            echo "ID: " . $preview['id'] . "\n";
            echo "Status: " . $preview['status'] . "\n";
            echo "Image URL: " . ($preview['image_url'] ?? 'NULL') . "\n";
            echo "Homeowner: " . ($preview['homeowner_name'] ?? 'Unknown') . "\n";
            
            if ($preview['image_url']) {
                $imagePath = str_replace('/buildhub/', '', $preview['image_url']);
                $fullPath = __DIR__ . '/' . $imagePath;
                echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
                if (file_exists($fullPath)) {
                    echo "File size: " . filesize($fullPath) . " bytes\n";
                }
            }
        }
    }
} else {
    echo "Failed to parse JSON response\n";
}

echo "\n=== Test Complete ===\n";
?>