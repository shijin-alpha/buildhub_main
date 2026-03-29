<?php
/**
 * Test receipt file access and content
 */

$filePath = "backend/uploads/payment_receipts/22/receipt_1769100372_0.png";

echo "🔍 Testing Receipt File Access\n\n";

// Check if file exists
if (file_exists($filePath)) {
    echo "✅ File exists at: $filePath\n";
    
    // Get file info
    $fileSize = filesize($filePath);
    $mimeType = mime_content_type($filePath);
    
    echo "File size: $fileSize bytes\n";
    echo "MIME type: $mimeType\n";
    
    // Check if it's a valid image
    $imageInfo = getimagesize($filePath);
    if ($imageInfo) {
        echo "Image dimensions: {$imageInfo[0]} x {$imageInfo[1]}\n";
        echo "Image type: {$imageInfo['mime']}\n";
    } else {
        echo "❌ Not a valid image file\n";
    }
    
    // Read first few bytes to see content
    $handle = fopen($filePath, 'rb');
    $firstBytes = fread($handle, 20);
    fclose($handle);
    
    echo "First 20 bytes (hex): " . bin2hex($firstBytes) . "\n";
    echo "First 20 bytes (text): " . $firstBytes . "\n";
    
    // Check if it's a PNG file
    if (substr($firstBytes, 0, 8) === "\x89PNG\r\n\x1a\n") {
        echo "✅ Valid PNG header detected\n";
    } else {
        echo "❌ Invalid PNG header\n";
    }
    
} else {
    echo "❌ File does not exist at: $filePath\n";
}

// Test web access
echo "\n🌐 Testing Web Access:\n";
$webUrl = "http://localhost:3000/buildhub/backend/uploads/payment_receipts/22/receipt_1769100372_0.png";
echo "URL: $webUrl\n";

// Use curl to test web access
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Content-Type: $contentType\n";

if ($httpCode === 200) {
    echo "✅ File is accessible via web\n";
} else {
    echo "❌ File is not accessible via web (HTTP $httpCode)\n";
}
?>