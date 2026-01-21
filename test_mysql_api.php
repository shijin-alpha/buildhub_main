<?php
// Test the actual API that the frontend calls
require_once 'backend/config/database.php';

try {
    echo "=== Testing MySQL API Response ===\n\n";
    
    // Simulate the exact query from get_concept_previews.php
    $architect_id = 27; // Use the actual architect ID from the system
    
    $previewsStmt = $db->prepare("
        SELECT 
            cp.*,
            lr.plot_size,
            lr.budget_range,
            CONCAT(u.first_name, ' ', u.last_name) as homeowner_name
        FROM concept_previews cp
        JOIN layout_requests lr ON cp.layout_request_id = lr.id
        JOIN users u ON lr.homeowner_id = u.id
        WHERE cp.architect_id = :architect_id 
        ORDER BY cp.created_at DESC
    ");
    
    $previewsStmt->execute([':architect_id' => $architect_id]);
    $previews = $previewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($previews) . " concept previews for architect ID $architect_id\n\n";
    
    if (count($previews) === 0) {
        // Try with any architect that has concepts
        echo "No previews for architect $architect_id, checking all concepts...\n";
        
        $stmt = $db->query("SELECT DISTINCT architect_id FROM concept_previews");
        $architectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Architects with concepts: " . implode(', ', $architectIds) . "\n";
        
        if (count($architectIds) > 0) {
            $architect_id = $architectIds[0];
            echo "Using architect ID: $architect_id\n\n";
            
            // Try again with the first architect that has concepts
            $previewsStmt->execute([':architect_id' => $architect_id]);
            $previews = $previewsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // Process each preview (same logic as the API)
    foreach ($previews as &$preview) {
        // Ensure image URLs are absolute (fixed version)
        if ($preview['image_url'] && !filter_var($preview['image_url'], FILTER_VALIDATE_URL)) {
            // Only add /buildhub/ if it's not already there
            if (strpos($preview['image_url'], '/buildhub/') !== 0) {
                $preview['image_url'] = '/buildhub/' . ltrim($preview['image_url'], '/');
            }
        }
    }
    
    // Create the response that would be sent to frontend
    $response = [
        'success' => true,
        'previews' => $previews
    ];
    
    echo "API Response:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
    
    // Verify each image file
    echo "=== Image File Verification ===\n";
    foreach ($previews as $preview) {
        echo "Concept ID: {$preview['id']}\n";
        echo "Status: {$preview['status']}\n";
        echo "Image URL: " . ($preview['image_url'] ?? 'NULL') . "\n";
        
        if ($preview['image_url']) {
            $imagePath = str_replace('/buildhub/', '', $preview['image_url']);
            $fullPath = __DIR__ . '/' . $imagePath;
            echo "File path: $fullPath\n";
            echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
            if (file_exists($fullPath)) {
                echo "File size: " . filesize($fullPath) . " bytes\n";
            }
        }
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>