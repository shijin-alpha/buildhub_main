<?php
/**
 * Working ML API - Direct Python execution
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

/**
 * Execute Python script directly
 */
function executePythonML($data) {
    // Create temp files
    $tempFile = tempnam(sys_get_temp_dir(), 'buildhub_ml_');
    $inputFile = $tempFile . '_input.json';
    $outputFile = $tempFile . '_output.json';
    
    try {
        // Write input data
        file_put_contents($inputFile, json_encode($data));
        
        // Execute Python script
        $scriptPath = __DIR__ . '/../ml_simple.py';
        $command = "python " . escapeshellarg($scriptPath);
        $command .= " " . escapeshellarg($inputFile) . " " . escapeshellarg($outputFile);
        
        // Execute command
        $output = shell_exec($command . " 2>&1");
        
        // Read result
        $result = null;
        if (file_exists($outputFile)) {
            $result = json_decode(file_get_contents($outputFile), true);
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

// Handle different endpoints
$action = $_GET['action'] ?? $_POST['action'] ?? 'validate';

try {
    switch ($action) {
        case 'validate':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            $input['action'] = 'validate';
            $result = executePythonML($input);
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
            
            $input['action'] = 'suggestions';
            $result = executePythonML($input);
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
            
            $fieldName = $input['field'] ?? '';
            $formData = $input;
            $formData['action'] = 'get_allowed_options';
            $formData['field_name'] = $fieldName;
            
            $result = executePythonML($formData);
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

