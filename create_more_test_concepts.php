<?php
require_once 'backend/config/database.php';

try {
    $db = new PDO("sqlite:buildhub.db");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Creating More Test Concepts ===\n\n";
    
    // Get available images
    $imageDir = 'uploads/conceptual_images';
    $images = glob("$imageDir/*.png");
    
    if (count($images) < 2) {
        echo "Not enough images to create multiple concepts\n";
        exit;
    }
    
    $concepts = [
        [
            'description' => 'A traditional Kerala-style house with sloped roof, wooden elements, and natural materials',
            'status' => 'completed'
        ],
        [
            'description' => 'A contemporary villa with glass facades, minimalist design, and open spaces',
            'status' => 'completed'
        ],
        [
            'description' => 'A colonial-style bungalow with pillars, verandas, and classic architectural elements',
            'status' => 'processing'
        ],
        [
            'description' => 'An eco-friendly house with solar panels, green walls, and sustainable materials',
            'status' => 'generating'
        ]
    ];
    
    foreach ($concepts as $i => $concept) {
        $imageIndex = $i % count($images);
        $imageUrl = null;
        $imagePath = null;
        
        if ($concept['status'] === 'completed') {
            $imageUrl = "/buildhub/" . $images[$imageIndex];
            $imagePath = $images[$imageIndex];
        }
        
        $stmt = $db->prepare("
            INSERT INTO concept_previews (
                architect_id, 
                layout_request_id, 
                job_id, 
                original_description, 
                status,
                image_url,
                image_path,
                is_placeholder,
                created_at
            ) VALUES (
                1, 
                1, 
                :job_id, 
                :description, 
                :status,
                :image_url,
                :image_path,
                0,
                datetime('now', '-' || :hours_ago || ' hours')
            )
        ");
        
        $stmt->execute([
            ':job_id' => 'test_concept_' . ($i + 2),
            ':description' => $concept['description'],
            ':status' => $concept['status'],
            ':image_url' => $imageUrl,
            ':image_path' => $imagePath,
            ':hours_ago' => ($i + 1) * 2  // Spread them out over time
        ]);
        
        echo "✓ Created concept: " . substr($concept['description'], 0, 50) . "... (Status: {$concept['status']})\n";
    }
    
    // Check final results
    echo "\nFinal concept previews:\n";
    $stmt = $db->query("SELECT id, status, original_description, image_url FROM concept_previews ORDER BY created_at DESC");
    $previews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($previews as $preview) {
        echo "ID: {$preview['id']}, Status: {$preview['status']}, Has Image: " . ($preview['image_url'] ? 'YES' : 'NO') . "\n";
        echo "  Description: " . substr($preview['original_description'], 0, 60) . "...\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>