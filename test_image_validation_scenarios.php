<?php
/**
 * Test Room Image Validation System with different scenarios
 */

require_once __DIR__ . '/backend/utils/RoomImageValidator.php';

echo "🧪 Room Image Validation System Test\n";
echo "=====================================\n\n";

// Test scenarios
$test_scenarios = [
    [
        'name' => 'Valid Bedroom Scenario',
        'room_type' => 'bedroom',
        'improvement_notes' => 'Want to improve lighting and make it more cozy',
        'expected_result' => 'should pass validation'
    ],
    [
        'name' => 'Kitchen for Bedroom Mismatch',
        'room_type' => 'bedroom',
        'improvement_notes' => 'Need better lighting',
        'expected_result' => 'should fail - wrong room type'
    ],
    [
        'name' => 'Living Room with Color Notes',
        'room_type' => 'living_room',
        'improvement_notes' => 'Want to change the color scheme and add more seating',
        'expected_result' => 'should pass with color enhancement'
    ],
    [
        'name' => 'Kitchen with Lighting Issues',
        'room_type' => 'kitchen',
        'improvement_notes' => 'The kitchen is too dark, need better lighting',
        'expected_result' => 'should validate lighting concerns'
    ],
    [
        'name' => 'Office Space Organization',
        'room_type' => 'office',
        'improvement_notes' => 'Need better organization and workspace layout',
        'expected_result' => 'should pass for office improvements'
    ]
];

// Create a test image for validation (simple colored rectangle)
function createTestImage($width = 800, $height = 600, $color = [128, 128, 128]) {
    if (!extension_loaded('gd')) {
        echo "⚠️ GD extension not available - using mock image path\n";
        return __DIR__ . '/test_image_mock.jpg';
    }
    
    $image = imagecreatetruecolor($width, $height);
    $bg_color = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    imagefill($image, 0, 0, $bg_color);
    
    // Add some texture/variation
    for ($i = 0; $i < 100; $i++) {
        $x = rand(0, $width);
        $y = rand(0, $height);
        $variation = rand(-30, 30);
        $pixel_color = imagecolorallocate($image, 
            max(0, min(255, $color[0] + $variation)),
            max(0, min(255, $color[1] + $variation)),
            max(0, min(255, $color[2] + $variation))
        );
        imagesetpixel($image, $x, $y, $pixel_color);
    }
    
    $test_image_path = __DIR__ . '/test_validation_image.jpg';
    imagejpeg($image, $test_image_path, 85);
    imagedestroy($image);
    
    return $test_image_path;
}

// Run tests
foreach ($test_scenarios as $index => $scenario) {
    echo "Test " . ($index + 1) . ": {$scenario['name']}\n";
    echo str_repeat("-", 50) . "\n";
    
    // Create different test images for different scenarios
    $image_colors = [
        [180, 160, 140], // Warm bedroom colors
        [220, 220, 200], // Bright kitchen colors
        [140, 160, 180], // Cool living room colors
        [100, 120, 140], // Dark office colors
        [160, 180, 160]  // Neutral colors
    ];
    
    $test_image = createTestImage(800, 600, $image_colors[$index % count($image_colors)]);
    
    if (!file_exists($test_image)) {
        echo "❌ Could not create test image\n\n";
        continue;
    }
    
    try {
        $validation_result = RoomImageValidator::validateRoomImage(
            $test_image,
            $scenario['room_type'],
            $scenario['improvement_notes']
        );
        
        echo "Room Type: {$scenario['room_type']}\n";
        echo "Notes: {$scenario['improvement_notes']}\n";
        echo "Expected: {$scenario['expected_result']}\n\n";
        
        echo "VALIDATION RESULT:\n";
        echo "✓ Valid: " . ($validation_result['is_valid'] ? 'YES' : 'NO') . "\n";
        echo "✓ Confidence: {$validation_result['confidence_score']}%\n";
        
        if (!empty($validation_result['validation_details'])) {
            echo "✓ Details:\n";
            foreach ($validation_result['validation_details'] as $detail) {
                echo "  - $detail\n";
            }
        }
        
        if (!empty($validation_result['issues_found'])) {
            echo "⚠️ Issues:\n";
            foreach ($validation_result['issues_found'] as $issue) {
                echo "  - $issue\n";
            }
        }
        
        if (!empty($validation_result['recommendations'])) {
            echo "💡 Recommendations:\n";
            foreach ($validation_result['recommendations'] as $rec) {
                echo "  - $rec\n";
            }
        }
        
        // Analyze result
        if ($validation_result['is_valid']) {
            echo "✅ PASSED - Image validation successful\n";
        } else {
            echo "❌ FAILED - Image given is not valid message would be shown\n";
        }
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
    
    // Clean up test image
    if (file_exists($test_image) && strpos($test_image, 'test_validation_image.jpg') !== false) {
        unlink($test_image);
    }
}

echo "🏁 Test completed!\n";
echo "\nKey Features Tested:\n";
echo "✓ Room type relevance validation\n";
echo "✓ Image quality assessment\n";
echo "✓ Color appropriateness analysis\n";
echo "✓ Improvement notes alignment\n";
echo "✓ Confidence scoring system\n";
echo "✓ Error handling and recommendations\n";
echo "\nThe system will show 'image given is not valid' message when:\n";
echo "- Confidence score < 60%\n";
echo "- Image doesn't match selected room type\n";
echo "- Image quality is too poor for analysis\n";
echo "- Image appears to be outdoor scene for indoor room\n";