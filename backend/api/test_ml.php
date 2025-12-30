<?php
/**
 * Simple ML API test
 */

header('Content-Type: application/json');

// Test data
$testData = [
    'plot_size' => 2000,
    'budget' => 3000000,
    'num_floors' => 2
];

echo "Testing ML API with data: " . json_encode($testData) . "\n";

// Create temp files
$tempFile = tempnam(sys_get_temp_dir(), 'buildhub_ml_');
$inputFile = $tempFile . '_input.json';
$outputFile = $tempFile . '_output.json';

// Write input data
file_put_contents($inputFile, json_encode($testData));

// Execute Python script
$scriptPath = __DIR__ . '/../ml_simple.py';
$command = "python " . escapeshellarg($scriptPath);
$command .= " " . escapeshellarg($inputFile) . " " . escapeshellarg($outputFile);

echo "Command: $command\n";

$output = shell_exec($command . " 2>&1");
echo "Output: $output\n";

// Read result
if (file_exists($outputFile)) {
    $result = json_decode(file_get_contents($outputFile), true);
    echo "Result from file: " . json_encode($result) . "\n";
} else {
    echo "No output file created\n";
}

// Cleanup
if (file_exists($inputFile)) unlink($inputFile);
if (file_exists($outputFile)) unlink($outputFile);
unlink($tempFile);

// Return result
echo json_encode([
    'success' => true,
    'data' => $result
]);
?>









