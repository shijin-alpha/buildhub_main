<?php
/**
 * Verify Audit Ledger Integrity API
 * 
 * Performs comprehensive integrity verification of the immutable audit ledger.
 * Checks hash consistency, chain linkage, and tamper detection.
 * 
 * This endpoint is primarily for system administrators and monitoring systems.
 */

// Disable error display and ensure JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Catch any fatal errors and return JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

require_once '../../config/database.php';
require_once '../../blockchain/PaymentAuditIntegrator.php';

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Start session for authentication
    session_start();
    
    // Check admin authentication
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        throw new Exception('Admin authentication required for ledger integrity verification');
    }
    
    $adminUsername = $_SESSION['admin_username'] ?? 'admin';
    
    // Get request parameters
    $startBlock = null;
    $endBlock = null;
    $fullVerification = false;
    $generateReport = false;
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $startBlock = isset($_GET['start_block']) ? (int)$_GET['start_block'] : null;
        $endBlock = isset($_GET['end_block']) ? (int)$_GET['end_block'] : null;
        $fullVerification = isset($_GET['full_verification']) && $_GET['full_verification'] === 'true';
        $generateReport = isset($_GET['generate_report']) && $_GET['generate_report'] === 'true';
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $startBlock = isset($input['start_block']) ? (int)$input['start_block'] : null;
        $endBlock = isset($input['end_block']) ? (int)$input['end_block'] : null;
        $fullVerification = isset($input['full_verification']) && $input['full_verification'] === true;
        $generateReport = isset($input['generate_report']) && $input['generate_report'] === true;
    }
    
    // Validate block range
    if ($startBlock !== null && $endBlock !== null && $startBlock > $endBlock) {
        throw new Exception('Invalid block range: start_block cannot be greater than end_block');
    }
    
    // Record verification start time
    $verificationStartTime = microtime(true);
    
    // Perform integrity verification
    $integrityResult = PaymentAuditIntegrator::verifyLedgerIntegrity($db, $startBlock, $endBlock);
    
    if (!$integrityResult) {
        throw new Exception('Integrity verification failed to execute');
    }
    
    // Calculate verification duration
    $verificationDuration = microtime(true) - $verificationStartTime;
    
    // Get audit statistics
    $auditStats = PaymentAuditIntegrator::getAuditStatistics($db);
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'integrity_verification' => $integrityResult,
            'verification_metadata' => [
                'verified_by' => $adminUsername,
                'verification_timestamp' => date('Y-m-d H:i:s'),
                'verification_duration_seconds' => round($verificationDuration, 3),
                'block_range' => [
                    'start_block' => $startBlock,
                    'end_block' => $endBlock,
                    'full_verification' => $fullVerification
                ]
            ]
        ]
    ];
    
    // Add audit statistics if available
    if ($auditStats) {
        $response['data']['audit_statistics'] = $auditStats;
    }
    
    // Perform additional checks for full verification
    if ($fullVerification) {
        $additionalChecks = [];
        
        // Check for orphaned entries
        try {
            $orphanStmt = $db->prepare("
                SELECT COUNT(*) as orphan_count
                FROM immutable_payment_audit_ledger ial
                LEFT JOIN stage_payment_requests spr ON ial.payment_id = spr.id
                LEFT JOIN alternative_payments ap ON ial.payment_id = ap.id
                WHERE spr.id IS NULL AND ap.id IS NULL
            ");
            $orphanStmt->execute();
            $orphanResult = $orphanStmt->fetch(PDO::FETCH_ASSOC);
            $additionalChecks['orphaned_entries'] = (int)$orphanResult['orphan_count'];
        } catch (Exception $e) {
            $additionalChecks['orphaned_entries'] = 'check_failed';
        }
        
        // Check for duplicate block numbers
        try {
            $duplicateStmt = $db->prepare("
                SELECT block_number, COUNT(*) as duplicate_count
                FROM immutable_payment_audit_ledger
                GROUP BY block_number
                HAVING COUNT(*) > 1
            ");
            $duplicateStmt->execute();
            $duplicates = $duplicateStmt->fetchAll(PDO::FETCH_ASSOC);
            $additionalChecks['duplicate_block_numbers'] = count($duplicates);
            if (count($duplicates) > 0) {
                $additionalChecks['duplicate_blocks'] = $duplicates;
            }
        } catch (Exception $e) {
            $additionalChecks['duplicate_block_numbers'] = 'check_failed';
        }
        
        // Check for missing verification entries
        try {
            $missingStmt = $db->prepare("
                SELECT 
                    COUNT(CASE WHEN entry_type = 'payment_completion' THEN 1 END) as completion_entries,
                    COUNT(CASE WHEN entry_type = 'contractor_verification' THEN 1 END) as contractor_verifications,
                    COUNT(CASE WHEN entry_type = 'admin_verification' THEN 1 END) as admin_verifications
                FROM immutable_payment_audit_ledger
            ");
            $missingStmt->execute();
            $entryTypes = $missingStmt->fetch(PDO::FETCH_ASSOC);
            $additionalChecks['entry_type_distribution'] = $entryTypes;
        } catch (Exception $e) {
            $additionalChecks['entry_type_distribution'] = 'check_failed';
        }
        
        $response['data']['additional_checks'] = $additionalChecks;
    }
    
    // Generate detailed report if requested
    if ($generateReport) {
        $report = [
            'report_generated_at' => date('Y-m-d H:i:s'),
            'report_generated_by' => $adminUsername,
            'system_status' => $integrityResult['valid'] ? 'HEALTHY' : 'COMPROMISED',
            'summary' => [
                'total_entries_verified' => $integrityResult['total_entries'],
                'valid_entries' => $integrityResult['verified_entries'],
                'invalid_entries_count' => count($integrityResult['invalid_entries']),
                'integrity_percentage' => $integrityResult['integrity_percentage'] ?? 0
            ],
            'recommendations' => []
        ];
        
        // Add recommendations based on findings
        if (!$integrityResult['valid']) {
            $report['recommendations'][] = 'CRITICAL: Ledger integrity compromised. Investigate invalid entries immediately.';
            $report['recommendations'][] = 'Review system access logs for unauthorized modifications.';
            $report['recommendations'][] = 'Consider restoring from backup if tampering is confirmed.';
        } else {
            $report['recommendations'][] = 'Ledger integrity verified. System is operating normally.';
        }
        
        if ($fullVerification && isset($additionalChecks['orphaned_entries']) && $additionalChecks['orphaned_entries'] > 0) {
            $report['recommendations'][] = "Found {$additionalChecks['orphaned_entries']} orphaned entries. Consider cleanup.";
        }
        
        if ($fullVerification && isset($additionalChecks['duplicate_block_numbers']) && $additionalChecks['duplicate_block_numbers'] > 0) {
            $report['recommendations'][] = "CRITICAL: Found duplicate block numbers. Database integrity may be compromised.";
        }
        
        $response['data']['detailed_report'] = $report;
    }
    
    // Log the integrity check
    error_log("Audit ledger integrity verification completed by {$adminUsername}: " . 
              ($integrityResult['valid'] ? 'PASSED' : 'FAILED') . 
              " ({$integrityResult['verified_entries']}/{$integrityResult['total_entries']} entries verified)");
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Verify audit ledger integrity error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'INTEGRITY_VERIFICATION_ERROR'
    ]);
}