<?php
/**
 * Blockchain Health Check API
 * 
 * Provides comprehensive health status of blockchain integration
 * Contract Address: 0xf8e81D47203A594245E36C48e151709F0C19fBe8
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    
    // Perform comprehensive health check
    $healthCheck = $integrator->healthCheck();
    
    // Get contract statistics
    $contractStats = null;
    if ($healthCheck['enabled']) {
        try {
            $contractStats = $integrator->getContractStatistics();
        } catch (Exception $e) {
            $contractStats = ['error' => $e->getMessage()];
        }
    }
    
    // Get database health metrics
    $databaseHealth = [];
    try {
        // Check blockchain tables
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM blockchain_trust_records) as total_records,
                    (SELECT COUNT(*) FROM blockchain_trust_records WHERE blockchain_tx_hash IS NOT NULL) as confirmed_records,
                    (SELECT COUNT(*) FROM blockchain_trust_records WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOURS)) as records_last_24h,
                    (SELECT COUNT(*) FROM blockchain_operation_queue WHERE status = 'pending') as pending_operations,
                    (SELECT COUNT(*) FROM blockchain_operation_queue WHERE status = 'failed') as failed_operations,
                    (SELECT COUNT(DISTINCT payment_id) FROM blockchain_trust_records) as integrated_payments,
                    (SELECT COUNT(*) FROM blockchain_operation_logs WHERE status = 'error' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)) as errors_last_hour";
        
        $result = $db->query($sql);
        if ($result) {
            $databaseHealth = $result->fetch_assoc();
            
            // Calculate health metrics
            $databaseHealth['confirmation_rate'] = $databaseHealth['total_records'] > 0 
                ? round(($databaseHealth['confirmed_records'] / $databaseHealth['total_records']) * 100, 2) 
                : 0;
            
            $databaseHealth['health_status'] = 'healthy';
            if ($databaseHealth['errors_last_hour'] > 5 || $databaseHealth['pending_operations'] > 50) {
                $databaseHealth['health_status'] = 'critical';
            } elseif ($databaseHealth['errors_last_hour'] > 0 || $databaseHealth['pending_operations'] > 10) {
                $databaseHealth['health_status'] = 'warning';
            }
        }
    } catch (Exception $e) {
        $databaseHealth = ['error' => $e->getMessage()];
    }
    
    // Get network status
    $networkStatus = [];
    try {
        $sql = "SELECT * FROM blockchain_network_status 
                WHERE contract_address = ? 
                ORDER BY checked_at DESC 
                LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $contractAddress = TRUST_CONTRACT_ADDRESS;
        $stmt->bind_param('s', $contractAddress);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $networkStatus = $result->fetch_assoc();
        }
    } catch (Exception $e) {
        $networkStatus = ['error' => $e->getMessage()];
    }
    
    // Get recent operation logs
    $recentLogs = [];
    try {
        $sql = "SELECT operation_type, status, message, error_message, created_at
                FROM blockchain_operation_logs 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ORDER BY created_at DESC 
                LIMIT 20";
        
        $result = $db->query($sql);
        if ($result) {
            $recentLogs = $result->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        $recentLogs = ['error' => $e->getMessage()];
    }
    
    // Get configuration validation
    $configValidation = validateBlockchainConfig();
    
    // Determine overall health status
    $overallStatus = 'healthy';
    $issues = [];
    
    if (!$healthCheck['enabled']) {
        $overallStatus = 'disabled';
        $issues[] = 'Blockchain integration is disabled';
    } elseif (!empty($configValidation)) {
        $overallStatus = 'critical';
        $issues = array_merge($issues, $configValidation);
    } elseif (!$healthCheck['blockchain_health']['contract_accessible']) {
        $overallStatus = 'critical';
        $issues[] = 'Smart contract is not accessible';
    } elseif (!$healthCheck['integration_health']['database_accessible']) {
        $overallStatus = 'critical';
        $issues[] = 'Database is not accessible';
    } elseif (isset($databaseHealth['health_status']) && $databaseHealth['health_status'] === 'critical') {
        $overallStatus = 'critical';
        $issues[] = 'High error rate or too many pending operations';
    } elseif (isset($databaseHealth['health_status']) && $databaseHealth['health_status'] === 'warning') {
        $overallStatus = 'warning';
        $issues[] = 'Some errors or pending operations detected';
    }
    
    // Prepare comprehensive response
    $response = [
        'success' => true,
        'overall_status' => $overallStatus,
        'issues' => $issues,
        'timestamp' => date('Y-m-d H:i:s'),
        'blockchain_integration' => [
            'enabled' => BLOCKCHAIN_ENABLED,
            'contract_address' => TRUST_CONTRACT_ADDRESS,
            'contract_explorer_url' => getAddressExplorerUrl(TRUST_CONTRACT_ADDRESS),
            'network' => ETHEREUM_NETWORK,
            'chain_id' => ETHEREUM_CHAIN_ID,
            'async_mode' => BLOCKCHAIN_ASYNC_MODE,
            'fail_silently' => BLOCKCHAIN_FAIL_SILENTLY
        ],
        'health_checks' => [
            'blockchain_health' => $healthCheck['blockchain_health'] ?? null,
            'integration_health' => $healthCheck['integration_health'] ?? null,
            'database_health' => $databaseHealth,
            'network_status' => $networkStatus
        ],
        'statistics' => [
            'contract_stats' => $contractStats,
            'local_stats' => $databaseHealth
        ],
        'configuration' => [
            'validation_errors' => $configValidation,
            'settings' => [
                'network' => ETHEREUM_NETWORK,
                'contract_address' => TRUST_CONTRACT_ADDRESS,
                'rpc_url' => ETHEREUM_RPC_URL,
                'gas_limit' => DEFAULT_GAS_LIMIT,
                'gas_price' => DEFAULT_GAS_PRICE,
                'retry_attempts' => BLOCKCHAIN_RETRY_ATTEMPTS,
                'timeout' => BLOCKCHAIN_TIMEOUT,
                'log_level' => BLOCKCHAIN_LOG_LEVEL
            ],
            'feature_flags' => [
                'payment_initiation_recording' => ENABLE_PAYMENT_INITIATION_RECORDING,
                'payment_completion_recording' => ENABLE_PAYMENT_COMPLETION_RECORDING,
                'contractor_verification_recording' => ENABLE_CONTRACTOR_VERIFICATION_RECORDING,
                'admin_verification_recording' => ENABLE_ADMIN_VERIFICATION_RECORDING,
                'audit_trail_api' => ENABLE_AUDIT_TRAIL_API
            ]
        ],
        'recent_activity' => [
            'operation_logs' => $recentLogs,
            'log_count' => count($recentLogs)
        ],
        'recommendations' => []
    ];
    
    // Add recommendations based on health status
    if ($overallStatus === 'critical') {
        $response['recommendations'][] = 'Immediate attention required - check configuration and network connectivity';
    } elseif ($overallStatus === 'warning') {
        $response['recommendations'][] = 'Monitor system closely - some issues detected';
    }
    
    if (isset($databaseHealth['pending_operations']) && $databaseHealth['pending_operations'] > 10) {
        $response['recommendations'][] = 'High number of pending operations - check async processing';
    }
    
    if (isset($databaseHealth['errors_last_hour']) && $databaseHealth['errors_last_hour'] > 0) {
        $response['recommendations'][] = 'Errors detected in last hour - check operation logs';
    }
    
    if (!empty($configValidation)) {
        $response['recommendations'][] = 'Fix configuration errors: ' . implode(', ', $configValidation);
    }
    
    // Set appropriate HTTP status code
    if ($overallStatus === 'critical') {
        http_response_code(503); // Service Unavailable
    } elseif ($overallStatus === 'warning') {
        http_response_code(200); // OK but with warnings
    } else {
        http_response_code(200); // OK
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'overall_status' => 'error',
        'error' => 'Health check failed',
        'message' => $e->getMessage(),
        'contract_address' => TRUST_CONTRACT_ADDRESS ?? '0xf8e81D47203A594245E36C48e151709F0C19fBe8',
        'timestamp' => date('Y-m-d H:i:s'),
        'issues' => ['Health check system failure']
    ]);
    
    // Log the error
    error_log("Blockchain health check API error: " . $e->getMessage());
}
?>