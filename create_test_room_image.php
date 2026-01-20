<?php
// Create a simple test room image for AI testing

$width = 800;
$height = 600;

// Create image
$image = imagecreate($width, $height);

// Define colors
$white = imagecolorallocate($image, 255, 255, 255);
$brown = imagecolorallocate($image, 139, 69, 19);
$blue = imagecolorallocate($image, 100, 150, 200);
$gray = imagecolorallocate($image, 128, 128, 128);
$black = imagecolorallocate($image, 0, 0, 0);

// Fill background
imagefill($image, 0, 0, $white);

// Draw furniture-like rectangles
// Sofa
imagefilledrectangle($image, 50, 400, 300, 550, $blue);
imagestring($image, 3, 120, 460, 'SOFA', $white);

// Table
imagefilledrectangle($image, 350, 450, 500, 500, $brown);
imagestring($image, 2, 390, 470, 'TABLE', $white);

// Chair
imagefilledrectangle($image, 550, 420, 650, 520, $gray);
imagestring($image, 2, 580, 465, 'CHAIR', $black);

// TV (on wall)
imagefilledrectangle($image, 300, 50, 500, 150, $black);
imagestring($image, 3, 380, 90, 'TV', $white);

// Window
imagefilledrectangle($image, 600, 50, 750, 200, $blue);
imagestring($image, 2, 650, 120, 'WINDOW', $white);

// Add room title
imagestring($image, 5, 250, 300, 'Test Living Room', $black);

// Save image
$filename = 'test_room_image.jpg';
if (imagejpeg($image, $filename, 80)) {
    echo "✓ Test room image created: {$filename}\n";
    echo "Image size: " . filesize($filename) . " bytes\n";
    echo "Dimensions: {$width}x{$height}\n";
} else {
    echo "✗ Failed to create test image\n";
}

// Clean up
imagedestroy($image);
?>