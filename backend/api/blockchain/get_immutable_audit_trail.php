<?php
/**
 * Get Immutable Payment Audit Trail API
 * 
 * Provides access to the immutable audit ledger for payment verification disputes
 * and transparency. Returns the complete audit trail for a specific payment.
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
    
    // Check authentication - allow admin, contractor, or homeowner
    $isAuthenticated = false;
    $userType = null;
    $userId = null;
    
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        $isAuthenticated = true;
        $userType = 'admin';
        $userId = $_SESSION['admin_username'] ?? 'admin';
    } elseif (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        $isAuthenticated = true;
        $userType = $_SESSION['user_type'] ?? $_SESSION['role'] ?? null;
        $userId = $_SESSION['user_id'];
    }
    
    if (!$isAuthenticated) {
        throw new Exception('Authentication required');
    }
    
    // Get request parameters
    $paymentId = null;
    $projectId = null;
    $verifyIntegrity = false;
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $paymentId = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : null;
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $verifyIntegrity = isset($_GET['verify_integrity']) && $_GET['verify_integrity'] === 'true';
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $paymentId = isset($input['payment_id']) ? (int)$input['payment_id'] : null;
        $projectId = isset($input['project_id']) ? (int)$input['project_id'] : null;
        $verifyIntegrity = isset($input['verify_integrity']) && $input['verify_integrity'] === true;
    }
    
    // Validate input
    if (!$paymentId && !$projectId) {
        throw new Exception('Either payment_id or project_id is required');
    }
    
    // Authorization check - ensure user has access to the payment/project
    if ($userType !== 'admin') {
        $authorized = false;
        
        if ($paymentId) {
            // Check if user has access to this payment
            $authStmt = $db->prepare("
                SELECT spr.id, spr.homeowner_id, spr.contractor_id, spr.project_id
                FROM stage_payment_requests spr
                WHERE spr.id = ?
                UNION
                SELECT ap.id, ap.homeowner_id, ap.contractor_id, 
                       COALESCE(spr.project_id, td.project_id) as project_id
                FROM alternative_payments ap
                LEFT JOIN stage_payment_requests spr ON ap.reference_id = spr.id AND ap.payment_type = 'stage_payment'
                LEFT JOIN technical_details_payments td ON ap.reference_id = td.id AND ap.payment_type = 'technical_details'
                WHERE ap.id = ?
            ");
            
            $authStmt->execute([$paymentId, $paymentId]);
            $payment = $authStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($payment) {
                if (($userType === 'homeowner' && $payment['homeowner_id'] == $userId) ||
                    ($userType === 'contractor' && $payment['contractor_id'] == $userId)) {
                    $authorized = true;
                    if (!$projectId) {
                        $projectId = $payment['project_id'];
                    }
                }
            }
        } elseif ($projectId) {
            // Check if user has access to this project
            $authStmt = $db->prepare("
                SELECT lr.id, lr.homeowner_id, lr.contractor_id
                FROM layout_requests lr
                WHERE lr.id = ?
            ");
            
            $authStmt->execute([$projectId]);
            $project = $authStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($project) {
                if (($userType === 'homeowner' && $project['homeowner_id'] == $userId) ||
                    ($userType === 'contractor' && $project['contractor_id'] == $userId)) {
                    $authorized = true;
                }
            }
        }
        
        if (!$authorized) {
            throw new Exception('Access denied: You do not have permission to view this audit trail');
        }
    }
    
    $response = [
        'success' => true,
        'data' => []
    ];
    
    // Get audit trail for specific payment
    if ($paymentId) {
        $auditTrail = PaymentAuditIntegrator::getPaymentAuditTrail($db, $paymentId);
        
        if ($auditTrail) {
            $response['data']['payment_audit_trail'] = $auditTrail;
            
            // Verify integrity if requested
            if ($verifyIntegrity && !empty($auditTrail['entries'])) {
                $firstBlock = $auditTrail['entries'][0]['block_number'];
                $lastBlock = end($auditTrail['entries'])['block_number'];
                $integrityCheck = PaymentAuditIntegrator::verifyLedgerIntegrity($db, $firstBlock, $lastBlock);
                $response['data']['integrity_verification'] = $integrityCheck;
            }
        } else {
            $response['data']['payment_audit_trail'] = null;
            $response['message'] = 'No audit trail found for this payment';
        }
    }
    
    // Get audit trails for all payments in a project
    if ($projectId) {
        $projectStmt = $db->prepare("
            SELECT DISTINCT spr.id as payment_id
            FROM stage_payment_requests spr
            WHERE spr.project_id = ?
            UNION
            SELECT DISTINCT ap.id as payment_id
            FROM alternative_payments ap
            LEFT JOIN stage_payment_requests spr ON ap.reference_id = spr.id AND ap.payment_type = 'stage_payment'
            LEFT JOIN technical_details_payments td ON ap.reference_id = td.id AND ap.payment_type = 'technical_details'
            WHERE COALESCE(spr.project_id, td.project_id) = ?
        ");
        
        $projectStmt->execute([$projectId, $projectId]);
        $projectPayments = $projectStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $projectAuditTrails = [];
        foreach ($projectPayments as $payment) {
            $auditTrail = PaymentAuditIntegrator::getPaymentAuditTrail($db, $payment['payment_id']);
            if ($auditTrail) {
                $projectAuditTrails[] = $auditTrail;
            }
        }
        
        $response['data']['project_audit_trails'] = $projectAuditTrails;
        $response['data']['project_id'] = $projectId;
        $response['data']['total_payments_with_audit'] = count($projectAuditTrails);
    }
    
    // Add audit statistics if admin
    if ($userType === 'admin') {
        $auditStats = PaymentAuditIntegrator::getAuditStatistics($db);
        if ($auditStats) {
            $response['data']['audit_statistics'] = $auditStats;
        }
    }
    
    // Add metadata
    $response['data']['metadata'] = [
        'requested_by' => $userType,
        'user_id' => $userId,
        'timestamp' => date('Y-m-d H:i:s'),
        'integrity_verified' => $verifyIntegrity,
        'audit_system_version' => '1.0'
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Get immutable audit trail error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'AUDIT_TRAIL_ERROR'
    ]);
}