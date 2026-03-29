<?php
header('Content-Type: application/json');

$upload_dir = 'uploads/room_improvements/';
$test_file = $upload_dir . 'test_' . time() . '.txt';

$results = [
    'upload_dir' => $upload_dir,
    'dir_exists' => file_exists($upload_dir),
    'dir_readable' => is_readable($upload_dir),
    'dir_writable' => is_writable($upload_dir),
    'php_upload_max_filesize' => ini_get('upload_max_filesize'),
    'php_post_max_size' => ini_get('post_max_size'),
    'php_max_file_uploads' => ini_get('max_file_uploads'),
    'php_max_execution_time' => ini_get('max_execution_time'),
    'test_write' => false,
    'test_file' => $test_file
];

// Test write permissions
try {
    if (file_put_contents($test_file, 'test content')) {
        $results['test_write'] = true;
        // Clean up test file
        unlink($test_file);
    }
} catch (Exception $e) {
    $results['test_write_error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>