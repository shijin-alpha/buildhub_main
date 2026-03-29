<?php
header('Content-Type: application/json');

// Test GD extension availability
$result = [
    'gd_loaded' => extension_loaded('gd'),
    'gd_info' => [],
    'supported_formats' => [],
    'test_status' => 'unknown'
];

if (extension_loaded('gd')) {
    $result['gd_info'] = gd_info();
    
    // Check supported formats
    $result['supported_formats'] = [
        'jpeg' => function_exists('imagecreatefromjpeg'),
        'png' => function_exists('imagecreatefrompng'),
        'gif' => function_exists('imagecreatefromgif')
    ];
    
    // Try to create a simple test image
    try {
        $test_image = imagecreate(100, 100);
        if ($test_image) {
            $white = imagecolorallocate($test_image, 255, 255, 255);
            imagedestroy($test_image);
            $result['test_status'] = 'success';
        } else {
            $result['test_status'] = 'failed_to_create_image';
        }
    } catch (Exception $e) {
        $result['test_status'] = 'exception: ' . $e->getMessage();
    }
} else {
    $result['test_status'] = 'gd_not_loaded';
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>