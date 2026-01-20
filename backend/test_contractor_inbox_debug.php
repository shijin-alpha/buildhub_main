<?php
require_once 'config/database.php';

echo "Debugging Contractor Inbox Layout Images...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get a sample contractor inbox item
    $stmt = $db->prepare("
        SELECT s.*, 
               CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS homeowner_name,
               u.email AS homeowner_email
        FROM contractor_layout_sends s
        LEFT JOIN users u ON u.id = s.homeowner_id
        ORDER BY s.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo "No contractor inbox items found.\n";
        echo "Creating a test item...\n\n";
        
        // Create a test item
        $testPayload = [
            'type' => 'house_plan',
            'house_plan_id' => 1,
            'plan_name' => 'Test Villa Layout',
            'plot_dimensions' => '40x60 ft',
            'total_area' => 2400,
            'technical_details' => [
                'layout_image' => [
                    'name' => 'test_villa_layout.png',
                    'stored' => '1_layout_image_test.png',
                    'uploaded' => true
                ]
            ],
            'layout_images' => [
                [
                    'original' => 'test_villa_layout.png',
                    'stored' => '1_layout_image_test.png',
                    'url' => '/buildhub/backend/uploads/house_plans/1_layout_image_test.png',
                    'path' => '/buildhub/backend/uploads/house_plans/1_layout_image_test.png',
                    'type' => 'layout_image'
                ]
            ],
            'layout_image_url' => '/buildhub/backend/uploads/house_plans/1_layout_image_test.png',
            'forwarded_design' => [
                'title' => 'Test Villa Layout',
                'description' => 'Test house plan with layout image',
                'technical_details' => [
                    'layout_image' => [
                        'name' => 'test_villa_layout.png',
                        'stored' => '1_layout_image_test.png',
                        'uploaded' => true
                    ]
                ],
                'files' => [
                    [
                        'original' => 'test_villa_layout.png',
                        'stored' => '1_layout_image_test.png',
                        'url' => '/buildhub/backend/uploads/house_plans/1_layout_image_test.png',
                        'path' => '/buildhub/backend/uploads/house_plans/1_layout_image_test.png',
                        'type' => 'layout_image'
                    ]
                ]
            ]
        ];
        
        $insertStmt = $db->prepare("
            INSERT INTO contractor_layout_sends 
            (contractor_id, homeowner_id, house_plan_id, message, payload, created_at) 
            VALUES (2, 1, 1, 'Test layout image message', :payload, NOW())
        ");
        
        $insertStmt->execute([':payload' => json_encode($testPayload)]);
        
        echo "Test item created. Re-running query...\n\n";
        
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    foreach ($items as $index => $item) {
        echo "=== Inbox Item " . ($index + 1) . " ===\n";
        echo "ID: " . $item['id'] . "\n";
        echo "Contractor ID: " . $item['contractor_id'] . "\n";
        echo "Homeowner: " . ($item['homeowner_name'] ?: 'Unknown') . "\n";
        echo "Message: " . ($item['message'] ?: 'No message') . "\n";
        echo "Created: " . $item['created_at'] . "\n";
        
        if (!empty($item['payload'])) {
            $payload = json_decode($item['payload'], true);
            
            echo "\nPayload Analysis:\n";
            echo "- Type: " . ($payload['type'] ?? 'Not set') . "\n";
            echo "- Layout Image URL: " . ($payload['layout_image_url'] ?? 'Not set') . "\n";
            echo "- Layout Images Count: " . (isset($payload['layout_images']) ? count($payload['layout_images']) : 0) . "\n";
            
            if (isset($payload['layout_images']) && is_array($payload['layout_images'])) {
                foreach ($payload['layout_images'] as $idx => $img) {
                    echo "  Layout Image $idx:\n";
                    echo "    - Original: " . ($img['original'] ?? 'Not set') . "\n";
                    echo "    - Stored: " . ($img['stored'] ?? 'Not set') . "\n";
                    echo "    - URL: " . ($img['url'] ?? 'Not set') . "\n";
                    echo "    - Type: " . ($img['type'] ?? 'Not set') . "\n";
                }
            }
            
            if (isset($payload['forwarded_design']['files']) && is_array($payload['forwarded_design']['files'])) {
                echo "- Forwarded Design Files Count: " . count($payload['forwarded_design']['files']) . "\n";
                foreach ($payload['forwarded_design']['files'] as $idx => $file) {
                    echo "  File $idx:\n";
                    echo "    - Original: " . ($file['original'] ?? 'Not set') . "\n";
                    echo "    - Stored: " . ($file['stored'] ?? 'Not set') . "\n";
                    echo "    - URL: " . ($file['url'] ?? 'Not set') . "\n";
                }
            }
            
            if (isset($payload['technical_details']['layout_image'])) {
                $layoutImg = $payload['technical_details']['layout_image'];
                echo "- Technical Details Layout Image:\n";
                echo "    - Name: " . ($layoutImg['name'] ?? 'Not set') . "\n";
                echo "    - Stored: " . ($layoutImg['stored'] ?? 'Not set') . "\n";
                echo "    - Uploaded: " . ($layoutImg['uploaded'] ? 'Yes' : 'No') . "\n";
            }
        } else {
            echo "\nNo payload found.\n";
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
    
    echo "Debug complete. Check the payload structure above.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>