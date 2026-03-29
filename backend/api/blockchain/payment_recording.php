<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../blockchain/services/TrustLayerService.php';
require_once __DIR__ . '/../../config/cors.php';

/**
 * Blockchain Payment Recording API
 * 
 * Handles recording payment events on the blockchain trust layer
 */

try {
    $trustLayerService = new TrustLayerService();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($method) {
        case 'POST':
            handlePaymentRecording($trustLayerService, $input);
            break;
            
        case 'GET':
            handlePaymentStatus($trustLayerService);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}

function handlePaymentRecording($trustLayerService, $input) {
    if (!$input || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing action parameter']);
        return;
    }
    
    switch ($input['action']) {
        case 'initiate':
            recordPaymentInitiation($trustLayerService, $input);
            break;
            
        case 'complete':
            recordPaymentCompletion($trustLayerService, $input);
            break;
            
        case 'verify':
            recordPaymentVerification($trustLayerService, $input);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}
function recordPaymentInitiation($trustLayerService, $input) {
    $requiredFields = ['project_id', 'stage', 'amount'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: {$field}"]);
            return;
        }
    }
    
    $paymentData = [
        'project_id' => $input['project_id'],
        'stage' => $input['stage'],
        'amount' => $input['amount'],
        'type' => $input['type'] ?? 'standard',
        'timestamp' => time(),
        'verification_required' => $input['verification_required'] ?? false
    ];
    
    $result = $trustLayerService->recordPaymentInitiation($paymentData);
    
    if ($result['success']) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Payment initiation recorded on blockchain',
            'data' => $result
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
}

function recordPaymentCompletion($trustLayerService, $input) {
    if (!isset($input['proof_hash'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing proof_hash parameter']);
        return;
    }
    
    $completionData = [
        'completed_at' => $input['completed_at'] ?? time(),
        'type' => $input['type'] ?? 'standard',
        'verified_by' => $input['verified_by'] ?? 'system'
    ];
    
    $result = $trustLayerService->recordPaymentCompletion($input['proof_hash'], $completionData);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Payment completion recorded on blockchain',
            'data' => $result
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
}

function recordPaymentVerification($trustLayerService, $input) {
    $requiredFields = ['proof_hash', 'verifier_type'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: {$field}"]);
            return;
        }
    }
    
    $verificationData = [
        'verifier_type' => $input['verifier_type'],
        'verification_data' => $input['verification_data'] ?? '',
        'timestamp' => time()
    ];
    
    $result = $trustLayerService->recordVerification($input['proof_hash'], $verificationData);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Verification recorded on blockchain',
            'data' => $result
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
    }
}

function handlePaymentStatus($trustLayerService) {
    if (!isset($_GET['proof_hash'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing proof_hash parameter']);
        return;
    }
    
    $status = $trustLayerService->getPaymentStatus($_GET['proof_hash']);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $status
    ]);
}
?>