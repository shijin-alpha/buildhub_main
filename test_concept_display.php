<?php
require_once 'backend/config/database.php';

try {
    $db = new PDO("sqlite:buildhub.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Testing Concept Display Logic ===\n\n";
    
    $architect_id = 1;
    
    // Simulate the exact query from get_concept_previews.php
    $previewsStmt = $db->prepare("
        SELECT 
            cp.*,
            lr.plot_size,
            lr.budget_range,
            COALESCE(u.first_name || ' ' || u.last_name, 'Unknown') as homeowner_name
        FROM concept_previews cp
        JOIN layout_requests lr ON cp.layout_request_id = lr.id
        JOIN users u ON lr.homeowner_id = u.id
        WHERE cp.architect_id = :architect_id 
        ORDER BY cp.created_at DESC
    ");
    
    $previewsStmt->execute([':architect_id' => $architect_id]);
    $previews = $previewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($previews) . " concept previews:\n\n";
    
    foreach ($previews as &$preview) {
        echo "--- Preview ID: {$preview['id']} ---\n";
        echo "Status: {$preview['status']}\n";
        echo "Original Image URL: " . ($preview['image_url'] ?? 'NULL') . "\n";
        
        // Apply the same URL normalization as the API (fixed version)
        if ($preview['image_url'] && !filter_var($preview['image_url'], FILTER_VALIDATE_URL)) {
            // Only add /buildhub/ if it's not already there
            if (strpos($preview['image_url'], '/buildhub/') !== 0) {
                $preview['image_url'] = '/buildhub/' . ltrim($preview['image_url'], '/');
            }
        }
        
        echo "Normalized Image URL: " . ($preview['image_url'] ?? 'NULL') . "\n";
        echo "Homeowner: {$preview['homeowner_name']}\n";
        echo "Description: " . substr($preview['original_description'], 0, 50) . "...\n";
        
        if ($preview['image_url']) {
            $imagePath = str_replace('/buildhub/', '', $preview['image_url']);
            $fullPath = __DIR__ . '/' . $imagePath;
            echo "Checking file: $fullPath\n";
            echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
            if (file_exists($fullPath)) {
                echo "File size: " . filesize($fullPath) . " bytes\n";
            }
        }
        echo "\n";
    }
    
    // Create the JSON response that would be sent to frontend
    $response = [
        'success' => true,
        'previews' => $previews
    ];
    
    echo "JSON Response:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>