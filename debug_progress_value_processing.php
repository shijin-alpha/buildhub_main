<?php
/**
 * Debug Progress Value Processing
 * Test to see exactly what happens to the input value during processing
 */

echo "=== Progress Value Processing Debug ===\n\n";

// Test different input values
$test_values = ['5', '5.0', '5.00', 4.9, 5, 5.1, '4.999999'];

foreach ($test_values as $test_value) {
    echo "Input: " . var_export($test_value, true) . "\n";
    
    // Simulate the current backend processing
    $processed_float = (float)$test_value;
    echo "After (float): " . var_export($processed_float, true) . "\n";
    
    $processed_round = round($processed_float, 2);
    echo "After round(, 2): " . var_export($processed_round, true) . "\n";
    
    // Test different approaches
    $direct_string = (string)$test_value;
    echo "Direct string: " . var_export($direct_string, true) . "\n";
    
    $number_format = number_format($processed_float, 2, '.', '');
    echo "number_format: " . var_export($number_format, true) . "\n";
    
    echo "---\n";
}

echo "\n=== Testing POST simulation ===\n";

// Simulate POST data
$_POST['incremental_completion_percentage'] = '5';
echo "POST value: " . $_POST['incremental_completion_percentage'] . "\n";

$current_processing = isset($_POST['incremental_completion_percentage']) ? round((float)$_POST['incremental_completion_percentage'], 2) : 0;
echo "Current backend processing result: " . $current_processing . "\n";

// Test alternative processing
$alternative_processing = isset($_POST['incremental_completion_percentage']) ? (float)$_POST['incremental_completion_percentage'] : 0;
echo "Alternative (no rounding): " . $alternative_processing . "\n";

// Test string preservation
$string_preservation = isset($_POST['incremental_completion_percentage']) ? $_POST['incremental_completion_percentage'] : '0';
echo "String preservation: " . $string_preservation . "\n";

echo "\n=== Testing Database Storage ===\n";

// Test what happens with DECIMAL(6,2)
echo "DECIMAL(6,2) can store: 9999.99 max\n";
echo "Input 5 should be stored as: 5.00\n";
echo "Input 5.0 should be stored as: 5.00\n";
echo "Input 4.999999 should be stored as: 5.00 (rounded)\n";

// Test precision
$precision_test = 5.0;
echo "PHP precision test: " . sprintf("%.2f", $precision_test) . "\n";
echo "PHP precision test (10 decimals): " . sprintf("%.10f", $precision_test) . "\n";

$precision_test2 = 4.999999;
echo "PHP precision test 2: " . sprintf("%.2f", $precision_test2) . "\n";
echo "PHP precision test 2 (10 decimals): " . sprintf("%.10f", $precision_test2) . "\n";