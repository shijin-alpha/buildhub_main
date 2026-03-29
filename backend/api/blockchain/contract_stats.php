<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../blockchain/services/TrustLayerService.php';
require_once __DIR__ . '/../../config/cors.php';

/**
 * Blockchain Contract Statistics API
 * 
 * Returns statistics and information about the TrustLayer smart contract
 */

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    $trustLayerService = new TrustLayerService();
    $stats = $trustLayerService->getContractStats();
    
    if (isset($stats['error'])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $stats['error']
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>