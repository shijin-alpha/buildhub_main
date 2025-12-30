<?php
/**
 * BuildHub ML Rules Engine API
 * Integrates Python ML model with PHP backend
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

/**
 * Execute Python script and return JSON response
 */
function executePythonScript($script, $data = null) {
    $tempFile = tempnam(sys_get_temp_dir(), 'buildhub_ml_');
    $inputFile = $tempFile . '_input.json';
    $outputFile = $tempFile . '_output.json';
    
    try {
        // Write input data if provided
        if ($data !== null) {
            file_put_contents($inputFile, json_encode($data));
        }
        
        // Try different Python commands
        $pythonCommands = ['python', 'python3', 'py'];
        $scriptPath = __DIR__ . '/../' . $script;
        
        $result = null;
        foreach ($pythonCommands as $pythonCmd) {
            $command = $pythonCmd . " " . escapeshellarg($scriptPath);
            if ($data !== null) {
                $command .= " " . escapeshellarg($inputFile) . " " . escapeshellarg($outputFile);
            }
            
            $output = shell_exec($command . " 2>&1");
            
            // Check if we got valid output
            if ($data !== null && file_exists($outputFile)) {
                $result = json_decode(file_get_contents($outputFile), true);
                unlink($outputFile);
                break;
            } elseif ($output && !empty(trim($output))) {
                $result = json_decode($output, true);
                if ($result !== null) {
                    break;
                }
            }
        }
        
        // If no result, try direct execution
        if ($result === null && $data !== null) {
            $command = "python " . escapeshellarg($scriptPath);
            $command .= " " . escapeshellarg($inputFile) . " " . escapeshellarg($outputFile);
            $output = shell_exec($command . " 2>&1");
            
            if (file_exists($outputFile)) {
                $result = json_decode(file_get_contents($outputFile), true);
                unlink($outputFile);
            }
        }
        
        // Cleanup
        if (file_exists($inputFile)) unlink($inputFile);
        if (file_exists($outputFile)) unlink($outputFile);
        unlink($tempFile);
        
        return $result;
    } catch (Exception $e) {
        // Cleanup on error
        if (file_exists($inputFile)) unlink($inputFile);
        if (file_exists($outputFile)) unlink($outputFile);
        if (file_exists($tempFile)) unlink($tempFile);
        
        return ['error' => $e->getMessage()];
    }
}

/**
 * Validate form data using ML model
 */
function validateForm($data) {
    $script = __DIR__ . '/../ml_simple.py';
    return executePythonScript($script, $data);
}

/**
 * Get suggestions using ML model
 */
function getSuggestions($data) {
    $script = __DIR__ . '/../ml_simple.py';
    return executePythonScript($script, $data);
}

/**
 * Get allowed options for a field
 */
function getAllowedOptions($fieldName, $formData) {
    $data = [
        'action' => 'get_allowed_options',
        'field_name' => $fieldName,
        'form_data' => $formData
    ];
    $script = __DIR__ . '/../ml_simple.py';
    return executePythonScript($script, $data);
}

// Handle different endpoints
$action = $_GET['action'] ?? $_POST['action'] ?? 'validate';

try {
    switch ($action) {
        case 'validate':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            $result = validateForm($input);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'suggestions':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            $result = getSuggestions($input);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        case 'allowed_options':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            $fieldName = $input['field_name'] ?? '';
            $formData = $input['form_data'] ?? [];
            
            $result = getAllowedOptions($fieldName, $formData);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
