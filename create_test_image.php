<?php
// Create a simple test image for testing the Room Improvement Assistant

// Create a 400x300 image
$width = 400;
$height = 300;
$image = imagecreatetruecolor($width, $height);

// Create colors
$white = imagecolorallocate($image, 255, 255, 255);
$blue = imagecolorallocate($image, 70, 130, 180);
$gray = imagecolorallocate($image, 128, 128, 128);
$black = imagecolorallocate($image, 0, 0, 0);

// Fill background
imagefill($image, 0, 0, $white);

// Draw a simple room representation
// Floor
imagefilledrectangle($image, 0, 200, $width, $height, $gray);

// Wall
imagefilledrectangle($image, 0, 0, $width, 200, $blue);

// Window
imagefilledrectangle($image, 50, 50, 150, 120, $white);
imagerectangle($image, 50, 50, 150, 120, $black);

// Door
imagefilledrectangle($image, 300, 120, 350, 200, imagecolorallocate($image, 139, 69, 19));
imagerectangle($image, 300, 120, 350, 200, $black);

// Add text
$font_size = 3;
imagestring($image, $font_size, 10, 10, "Test Room Image", $black);
imagestring($image, 2, 10, 30, "For Room Improvement Assistant Testing", $black);

// Save as JPEG
$filename = 'test_room_image.jpg';
imagejpeg($image, $filename, 90);

// Clean up
imagedestroy($image);

echo "Test image created: $filename\n";
echo "File size: " . filesize($filename) . " bytes\n";
echo "You can use this image for testing the Room Improvement Assistant.\n";
?>