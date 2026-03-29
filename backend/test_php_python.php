<?php
/**
 * Test PHP-Python integration directly
 */

echo "🧪 Testing PHP-Python Integration\n";

// Test data
$testData = [
    'plot_size' => 2000,
    'budget' => 3000000,
    'num_floors' => 2
];

echo "Test data: " . json_encode($testData) . "\n";

// Create temp files
$tempFile = tempnam(sys_get_temp_dir(), 'buildhub_ml_');
$inputFile = $tempFile . '_input.json';
$outputFile = $tempFile . '_output.json';

echo "Input file: $inputFile\n";
echo "Output file: $outputFile\n";

// Write input data
file_put_contents($inputFile, json_encode($testData));
echo "✅ Input data written\n";

// Try different Python commands
$pythonCommands = ['python', 'python3', 'py'];
$scriptPath = __DIR__ . '/ml_simple.py';

echo "Script path: $scriptPath\n";
echo "Script exists: " . (file_exists($scriptPath) ? 'YES' : 'NO') . "\n";

$result = null;
foreach ($pythonCommands as $pythonCmd) {
    echo "Trying command: $pythonCmd\n";
    
    $command = $pythonCmd . " " . escapeshellarg($scriptPath);
    $command .= " " . escapeshellarg($inputFile) . " " . escapeshellarg($outputFile);
    
    echo "Full command: $command\n";
    
    $output = shell_exec($command . " 2>&1");
    echo "Command output: $output\n";
    
    // Check if we got valid output
    if (file_exists($outputFile)) {
        $result = json_decode(file_get_contents($outputFile), true);
        echo "✅ Output file created, result: " . json_encode($result) . "\n";
        unlink($outputFile);
        break;
    } elseif ($output && !empty(trim($output))) {
        $result = json_decode($output, true);
        if ($result !== null) {
            echo "✅ Direct output, result: " . json_encode($result) . "\n";
            break;
        }
    }
    
    echo "❌ Command failed or no valid output\n";
}

// Cleanup
if (file_exists($inputFile)) unlink($inputFile);
if (file_exists($outputFile)) unlink($outputFile);
unlink($tempFile);

echo "Final result: " . json_encode($result) . "\n";
?>









