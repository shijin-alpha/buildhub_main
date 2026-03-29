<?php
// Check the actual database that the frontend is using
require_once 'backend/config/database.php';

try {
    // Try to connect to the same database the frontend uses
    $db = new PDO("sqlite:buildhub.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Checking Real Database ===\n\n";
    
    // Check all concept previews
    $stmt = $db->query("SELECT * FROM concept_previews ORDER BY created_at DESC");
    $concepts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total concept previews in database: " . count($concepts) . "\n\n";
    
    foreach ($concepts as $concept) {
        echo "--- Concept ID: {$concept['id']} ---\n";
        echo "Architect ID: {$concept['architect_id']}\n";
        echo "Layout Request ID: {$concept['layout_request_id']}\n";
        echo "Job ID: {$concept['job_id']}\n";
        echo "Status: {$concept['status']}\n";
        echo "Image URL: " . ($concept['image_url'] ?? 'NULL') . "\n";
        echo "Image Path: " . ($concept['image_path'] ?? 'NULL') . "\n";
        echo "Is Placeholder: " . ($concept['is_placeholder'] ? 'YES' : 'NO') . "\n";
        echo "Error Message: " . ($concept['error_message'] ?? 'NULL') . "\n";
        echo "Created: {$concept['created_at']}\n";
        echo "Updated: {$concept['updated_at']}\n";
        echo "Description: " . substr($concept['original_description'], 0, 50) . "...\n";
        
        if ($concept['image_url']) {
            $imagePath = str_replace('/buildhub/', '', $concept['image_url']);
            $fullPath = __DIR__ . '/' . $imagePath;
            echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
            if (file_exists($fullPath)) {
                echo "File size: " . filesize($fullPath) . " bytes\n";
            }
        }
        echo "\n";
    }
    
    // Check available images
    echo "=== Available Images ===\n";
    $imageDir = 'uploads/conceptual_images';
    $images = glob("$imageDir/*.png");
    
    foreach ($images as $image) {
        $fileTime = filemtime($image);
        echo "- $image (" . date('Y-m-d H:i:s', $fileTime) . ")\n";
    }
    
    // Check users table
    echo "\n=== Users ===\n";
    $stmt = $db->query("SELECT id, first_name, last_name, email, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Name: {$user['first_name']} {$user['last_name']}, Role: {$user['role']}\n";
    }
    
    // Check layout requests
    echo "\n=== Layout Requests ===\n";
    $stmt = $db->query("SELECT id, homeowner_id, plot_size, budget_range FROM layout_requests");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($requests as $request) {
        echo "ID: {$request['id']}, Homeowner: {$request['homeowner_id']}, Plot: {$request['plot_size']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>