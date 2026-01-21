<?php
require_once 'backend/config/database.php';

try {
    $db = new PDO("sqlite:buildhub.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Manual Concept Update Test ===\n\n";
    
    // Check existing images in uploads/conceptual_images
    $imageDir = 'uploads/conceptual_images';
    $images = glob("$imageDir/*.png");
    
    if (empty($images)) {
        echo "No images found in $imageDir\n";
        exit;
    }
    
    echo "Found images:\n";
    foreach ($images as $i => $image) {
        echo "$i: $image\n";
    }
    
    // Use the most recent image
    $latestImage = end($images);
    echo "\nUsing latest image: $latestImage\n";
    
    // Check if we have any concept previews
    $stmt = $db->query("SELECT * FROM concept_previews ORDER BY id DESC LIMIT 1");
    $concept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$concept) {
        echo "No concept previews found. Creating one...\n";
        
        $stmt = $db->prepare("
            INSERT INTO concept_previews (architect_id, layout_request_id, job_id, original_description, status)
            VALUES (1, 1, 'manual_test', 'Test concept for display', 'processing')
        ");
        $stmt->execute();
        $concept_id = $db->lastInsertId();
    } else {
        $concept_id = $concept['id'];
        echo "Using existing concept ID: $concept_id\n";
    }
    
    // Update the concept with the real image
    $imageUrl = "/buildhub/$latestImage";
    
    echo "Updating concept with image URL: $imageUrl\n";
    
    $stmt = $db->prepare("
        UPDATE concept_previews 
        SET 
            status = 'completed',
            image_url = :image_url,
            image_path = :image_path,
            is_placeholder = 0,
            updated_at = datetime('now')
        WHERE id = :id
    ");
    
    $stmt->execute([
        ':image_url' => $imageUrl,
        ':image_path' => $latestImage,
        ':id' => $concept_id
    ]);
    
    echo "✓ Concept updated successfully!\n";
    
    // Verify the update
    $stmt = $db->prepare("SELECT * FROM concept_previews WHERE id = :id");
    $stmt->execute([':id' => $concept_id]);
    $updated_concept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nUpdated concept details:\n";
    echo "ID: " . $updated_concept['id'] . "\n";
    echo "Status: " . $updated_concept['status'] . "\n";
    echo "Image URL: " . $updated_concept['image_url'] . "\n";
    echo "Image Path: " . $updated_concept['image_path'] . "\n";
    echo "Is Placeholder: " . ($updated_concept['is_placeholder'] ? 'YES' : 'NO') . "\n";
    
    // Check if file exists
    if (file_exists($latestImage)) {
        echo "✓ Image file exists (" . filesize($latestImage) . " bytes)\n";
    } else {
        echo "✗ Image file not found\n";
    }
    
    // Test the API response
    echo "\nTesting API response...\n";
    
    // Simulate the get_concept_previews.php response
    $previewsStmt = $db->prepare("
        SELECT 
            cp.*,
            lr.plot_size,
            lr.budget_range,
            COALESCE(u.first_name || ' ' || u.last_name, 'Unknown') as homeowner_name
        FROM concept_previews cp
        JOIN layout_requests lr ON cp.layout_request_id = lr.id
        JOIN users u ON lr.homeowner_id = u.id
        WHERE cp.architect_id = 1
        ORDER BY cp.created_at DESC
    ");
    
    $previewsStmt->execute();
    $previews = $previewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "API would return " . count($previews) . " previews:\n";
    foreach ($previews as $preview) {
        echo "- ID: {$preview['id']}, Status: {$preview['status']}, URL: " . ($preview['image_url'] ?? 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>