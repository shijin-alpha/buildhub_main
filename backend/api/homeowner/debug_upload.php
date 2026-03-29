<?php
/**
 * Receipt Upload Debug API
 * Tests multipart form data handling
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit('OK');
}

// Log everything
$output = [];

$output['request_method'] = $_SERVER['REQUEST_METHOD'];
$output['content_type'] = $_SERVER['CONTENT_TYPE'] ?? 'NOT SET';
$output['post_data'] = $_POST;
$output['files_data'] = $_FILES;
$output['post_count'] = count($_POST);
$output['files_count'] = count($_FILES);

// Try to get raw post data
$rawPost = file_get_contents('php://input');
$output['raw_post_length'] = strlen($rawPost);
$output['raw_post_first_200_chars'] = substr($rawPost, 0, 200);

// Log to error log
error_log("=== RECEIPT UPLOAD DEBUG ===");
error_log("Full debug data: " . json_encode($output, JSON_PRETTY_PRINT));

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
