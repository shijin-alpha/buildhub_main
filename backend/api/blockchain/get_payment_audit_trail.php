<?php
/**
 * Get Payment Audit Trail API
 * 
 * Retrieves blockchain audit trail for a specific payment
 * Contract Address: 0xf8e81D47203A594245E36C48e151709F0C19fBe8
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';
require_once '../../blockchain/PaymentBlockchainIntegrator.php';
require_once '../../blockchain/config/blockchain_config.php';

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize blockchain integrator
    $integrator = new PaymentBlockchainIntegrator($db);
    
    // Get payment ID from request
    $paymentId = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $paymentId = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : null;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $paymentId = isset($input['payment_id']) ? intval($input['payment_id']) : null;
    }
    
    // Validate payment ID
    if (!$paymentId || $paymentId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid or missing payment_id',
            'contract_address' => TRUST_CONTRACT_ADDRESS
        ]);
        exit;
    }
    
    // Check if blockchain integration is enabled
    if (!BLOCKCHAIN_ENABLED || !ENABLE_AUDIT_TRAIL_API) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'Blockchain audit trail is not enabled',
            'contract_address' => TRUST_CONTRACT_ADDRESS,
            'enabled' => BLOCKCHAIN_ENABLED,
            'api_enabled' => ENABLE_AUDIT_TRAIL_API
        ]);
        exit;
    }
    
    // Verify payment exists
    $sql = "SELECT id, project_id, amount, stage, status, created_at FROM payment_requests WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $paymentResult = $stmt->get_result();
    
    if ($paymentResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Payment not found',
            'payment_id' => $paymentId,
            'contract_address' => TRUST_CONTRACT_ADDRESS
        ]);
        exit;
    }
    
    $paymentData = $paymentResult->fetch_assoc();
    
    // Get blockchain audit trail
    $auditTrail = $integrator->getPaymentAuditTrail($paymentId);
    
    // Get blockchain integration status
    $blockchainStatus = $integrator->getPaymentBlockchainStatus($paymentId);
    
    // Get detailed blockchain records from database
    $sql = "SELECT 
                btr.*,
                CASE 
                    WHEN btr.verifier_type = 1 THEN 'contractor'
                    WHEN btr.verifier_type = 2 THEN 'admin'
                    ELSE NULL
                END as verifier_role,
                CASE 
                    WHEN btr.blockchain_tx_hash IS NOT NULL THEN 'confirmed'
                    ELSE 'pending'
                END as blockchain_status,
                ppd.proof_data,
                ppd.metadata as proof_metadata
            FROM blockchain_trust_records btr
            LEFT JOIN payment_proof_data ppd ON btr.payment_id = ppd.payment_id AND btr.proof_hash = ppd.proof_hash
            WHERE btr.payment_id = ?
            ORDER BY btr.created_at ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $blockchainRecords = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get integration events
    $sql = "SELECT event_type, event_data, created_at 
            FROM blockchain_integration_status 
            WHERE payment_id = ? 
            ORDER BY created_at ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $integrationEvents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Process integration events
    foreach ($integrationEvents as &$event) {
        if ($event['event_data']) {
            $event['event_data'] = json_decode($event['event_data'], true);
        }
    }
    
    // Add explorer URLs to blockchain records
    foreach ($blockchainRecords as &$record) {
        if ($record['blockchain_tx_hash']) {
            $record['explorer_url'] = getTransactionExplorerUrl($record['blockchain_tx_hash']);
        }
        
        // Parse JSON fields
        if ($record['proof_data']) {
            $record['proof_data'] = json_decode($record['proof_data'], true);
        }
        if ($record['proof_metadata']) {
            $record['proof_metadata'] = json_decode($record['proof_metadata'], true);
        }
    }
    
    // Get operation logs for this payment
    $sql = "SELECT operation_type, status, message, error_message, blockchain_tx_hash, 
                   gas_used, gas_price, execution_time_ms, created_at
            FROM blockchain_operation_logs 
            WHERE payment_id = ? 
            ORDER BY created_at ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $operationLogs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Add explorer URLs to operation logs
    foreach ($operationLogs as &$log) {
        if ($log['blockchain_tx_hash']) {
            $log['explorer_url'] = getTransactionExplorerUrl($log['blockchain_tx_hash']);
        }
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'payment_id' => $paymentId,
        'payment_data' => $paymentData,
        'blockchain_integration' => [
            'enabled' => BLOCKCHAIN_ENABLED,
            'contract_address' => TRUST_CONTRACT_ADDRESS,
            'contract_explorer_url' => getAddressExplorerUrl(TRUST_CONTRACT_ADDRESS),
            'network' => ETHEREUM_NETWORK,
            'status' => $blockchainStatus
        ],
        'audit_trail' => [
            'blockchain_audit_trail' => $auditTrail,
            'local_blockchain_records' => $blockchainRecords,
            'integration_events' => $integrationEvents,
            'operation_logs' => $operationLogs
        ],
        'summary' => [
            'total_blockchain_operations' => count($blockchainRecords),
            'confirmed_operations' => count(array_filter($blockchainRecords, function($r) { 
                return !empty($r['blockchain_tx_hash']); 
            })),
            'pending_operations' => count(array_filter($blockchainRecords, function($r) { 
                return empty($r['blockchain_tx_hash']); 
            })),
            'has_initiation' => !empty(array_filter($blockchainRecords, function($r) { 
                return $r['operation_type'] === 'initiation'; 
            })),
            'has_completion' => !empty(array_filter($blockchainRecords, function($r) { 
                return $r['operation_type'] === 'completion'; 
            })),
            'verification_count' => count(array_filter($blockchainRecords, function($r) { 
                return $r['operation_type'] === 'verification'; 
            })),
            'last_blockchain_update' => $blockchainStatus['last_blockchain_update'] ?? null
        ],
        'metadata' => [
            'retrieved_at' => date('Y-m-d H:i:s'),
            'api_version' => '1.0',
            'blockchain_config' => [
                'network' => ETHEREUM_NETWORK,
                'chain_id' => ETHEREUM_CHAIN_ID,
                'contract_address' => TRUST_CONTRACT_ADDRESS,
                'async_mode' => BLOCKCHAIN_ASYNC_MODE,
                'fail_silently' => BLOCKCHAIN_FAIL_SILENTLY
            ]
        ]
    ];
    
    // Add health check if requested
    if (isset($_GET['include_health']) && $_GET['include_health'] === 'true') {
        $response['health_check'] = $integrator->healthCheck();
    }
    
    // Add contract statistics if requested
    if (isset($_GET['include_stats']) && $_GET['include_stats'] === 'true') {
        $response['contract_statistics'] = $integrator->getContractStatistics();
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'contract_address' => TRUST_CONTRACT_ADDRESS ?? '0xf8e81D47203A594245E36C48e151709F0C19fBe8',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Log the error
    error_log("Blockchain audit trail API error: " . $e->getMessage());
}
?>