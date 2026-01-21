<?php
// Test the delete concept API
session_start();

// Simulate architect session
$_SESSION['user_id'] = 27; // Use actual architect ID
$_SESSION['role'] = 'architect';

echo "=== Testing Delete Concept API ===\n\n";

// First, let's see what concepts exist
require_once 'backend/config/database.php';

$stmt = $db->query("SELECT id, status, image_url, original_description FROM concept_previews ORDER BY created_at DESC");
$concepts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Available concepts:\n";
foreach ($concepts as $concept) {
    echo "ID: {$concept['id']}, Status: {$concept['status']}, Has Image: " . ($concept['image_url'] ? 'YES' : 'NO') . "\n";
    echo "  Description: " . substr($concept['original_description'] ?: 'No description', 0, 50) . "...\n";
}

if (count($concepts) > 0) {
    // Test deleting the last concept
    $conceptToDelete = end($concepts);
    $previewId = $conceptToDelete['id'];
    
    echo "\nTesting deletion of concept ID: $previewId\n";
    
    // Test the API endpoint
    $postData = json_encode(['preview_id' => $previewId]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/buildhub/backend/api/architect/delete_concept_preview.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result && $result['success']) {
            echo "✓ Concept deleted successfully\n";
            
            // Verify deletion
            $stmt = $db->prepare("SELECT id FROM concept_previews WHERE id = ?");
            $stmt->execute([$previewId]);
            $exists = $stmt->fetch();
            
            echo "Concept still exists in database: " . ($exists ? 'YES' : 'NO') . "\n";
        } else {
            echo "✗ Deletion failed: " . ($result['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "✗ HTTP error: $httpCode\n";
    }
} else {
    echo "\nNo concepts available to test deletion\n";
}

echo "\n=== Test Complete ===\n";
?>