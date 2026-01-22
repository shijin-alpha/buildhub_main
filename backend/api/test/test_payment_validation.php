<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Prevent any output before JSON headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Clear any previous output
ob_clean();

try {
    require_once __DIR__ . '/../../utils/PaymentRequestValidator.php';

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid JSON input'
        ]);
        exit;
    }
    
    $payment_type = $input['payment_type'] ?? '';
    $request_data = $input['request_data'] ?? [];
    $project_data = $input['project_data'] ?? [];
    
    if (empty($payment_type)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Payment type is required'
        ]);
        exit;
    }
    
    // Add required IDs for testing
    $request_data['project_id'] = $request_data['project_id'] ?? 1;
    $request_data['contractor_id'] = $request_data['contractor_id'] ?? 1;
    $request_data['homeowner_id'] = $request_data['homeowner_id'] ?? 1;
    
    // Run appropriate validation based on payment type
    if ($payment_type === 'stage_payment') {
        $validation_result = PaymentRequestValidator::validateStagePaymentRequest($request_data, $project_data);
    } elseif ($payment_type === 'custom_payment') {
        $validation_result = PaymentRequestValidator::validateCustomPaymentRequest($request_data, $project_data);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid payment type. Must be stage_payment or custom_payment'
        ]);
        exit;
    }
    
    // Add test metadata
    $validation_result['test_metadata'] = [
        'test_timestamp' => date('Y-m-d H:i:s'),
        'payment_type' => $payment_type,
        'input_data_summary' => [
            'requested_amount' => $request_data['requested_amount'] ?? 0,
            'project_total_cost' => $project_data['total_cost'] ?? 0,
            'has_work_description' => !empty($request_data['work_description']),
            'has_cost_breakdown' => isset($request_data['labor_cost']) || isset($request_data['material_cost'])
        ]
    ];
    
    // Add validation summary
    $validation_result['summary'] = PaymentRequestValidator::getValidationSummary($validation_result);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment validation test completed successfully',
        'validation_result' => $validation_result
    ]);
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_clean();
    
    error_log("Payment validation test error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during validation test',
        'debug' => [
            'error_type' => get_class($e),
            'error_message' => $e->getMessage()
        ]
    ]);
} catch (Error $e) {
    // Handle PHP fatal errors
    ob_clean();
    
    error_log("PHP Error in payment validation test: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'A system error occurred during validation test',
        'debug' => [
            'error_type' => 'PHP Error',
            'error_message' => $e->getMessage()
        ]
    ]);
}