<?php

/**
 * Blockchain Integration Patch for Admin Verification
 * 
 * This patch can be added to existing admin verification endpoints
 * without modifying their core logic. Add this code after successful
 * admin verification but before the final response.
 */

// Add this code to existing admin verification endpoints:
// - backend/api/admin/verify_payment_receipt.php

// Example integration for admin payment receipt verification:

/*
// Add this after successful admin verification and database update

// Blockchain Trust Layer Integration (non-intrusive)
try {
    require_once __DIR__ . '/../../blockchain/PaymentBlockchainIntegrator.php';
    
    $blockchainIntegrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
    
    // Prepare payment data for blockchain admin verification recording
    $paymentData = [
        'payment_request_id' => $payment_id,
        'project_id' => $payment['project_id'],
        'homeowner_id' => $payment['homeowner_id'],
        'contractor_id' => $payment['contractor_id'],
        'amount' => $payment['requested_amount'],
        'stage_name' => $payment['stage_name'],
        'payment_method' => 'mixed' // Could be razorpay or alternative
    ];
    
    // Prepare verification data
    $verificationData = [
        'verifier_id' => null, // Admin verification doesn't have specific user ID
        'verifier_type' => 'admin',
        'verification_action' => $verification_action, // 'admin_approved' or 'admin_rejected'
        'verification_timestamp' => time(),
        'verification_notes' => $admin_notes,
        'admin_username' => $admin_username,
        'auto_progress_update' => $auto_progress_update ?? false
    ];
    
    // Record admin verification on blockchain (non-blocking)
    $blockchainIntegrator->onAdminVerification($paymentData, $verificationData);
    
} catch (Exception $e) {
    // Blockchain integration failure should not affect payment processing
    error_log("Blockchain integration failed during admin verification: " . $e->getMessage());
}
*/

// Integration Instructions:
// 1. Add the blockchain integration code after successful admin verification
// 2. After database status updates but before final JSON response
// 3. Wrap in try-catch to ensure verification processing is not affected
// 4. Use appropriate payment and verification data based on the admin action

// For verify_payment_receipt.php, add after line ~80 (after admin verification and DB update)

/**
 * Generic integration function for admin verification
 */
function integrateBlockchainAdminVerification($db, $paymentData, $verificationData = []) {
    try {
        require_once __DIR__ . '/../../blockchain/PaymentBlockchainIntegrator.php';
        
        $blockchainIntegrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
        $blockchainIntegrator->onAdminVerification($paymentData, $verificationData);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Blockchain admin verification integration failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Integration function specifically for admin payment receipt verification
 */
function integrateBlockchainAdminPaymentVerification($db, $payment, $verificationAction, $adminNotes, $adminUsername, $autoProgressUpdate = false) {
    $paymentData = [
        'payment_request_id' => $payment['id'],
        'project_id' => $payment['project_id'],
        'homeowner_id' => $payment['homeowner_id'],
        'contractor_id' => $payment['contractor_id'],
        'amount' => $payment['requested_amount'],
        'stage_name' => $payment['stage_name'],
        'payment_method' => 'mixed' // Admin verifies both razorpay and alternative payments
    ];
    
    $verificationData = [
        'verifier_type' => 'admin',
        'verification_action' => $verificationAction, // 'admin_approved' or 'admin_rejected'
        'verification_timestamp' => time(),
        'verification_notes' => $adminNotes,
        'admin_username' => $adminUsername,
        'auto_progress_update' => $autoProgressUpdate,
        'homeowner_name' => $payment['homeowner_name'] ?? null,
        'contractor_name' => $payment['contractor_name'] ?? null,
        'project_name' => $payment['project_name'] ?? null
    ];
    
    return integrateBlockchainAdminVerification($db, $paymentData, $verificationData);
}

/**
 * Integration function for admin approval with progress update
 */
function integrateBlockchainAdminApprovalWithProgress($db, $payment, $adminNotes, $adminUsername, $progressUpdateData = null) {
    $paymentData = [
        'payment_request_id' => $payment['id'],
        'project_id' => $payment['project_id'],
        'homeowner_id' => $payment['homeowner_id'],
        'contractor_id' => $payment['contractor_id'],
        'amount' => $payment['requested_amount'],
        'stage_name' => $payment['stage_name'],
        'payment_method' => 'mixed'
    ];
    
    $verificationData = [
        'verifier_type' => 'admin',
        'verification_action' => 'admin_approved_with_progress',
        'verification_timestamp' => time(),
        'verification_notes' => $adminNotes,
        'admin_username' => $adminUsername,
        'auto_progress_update' => true,
        'progress_update_data' => $progressUpdateData
    ];
    
    return integrateBlockchainAdminVerification($db, $paymentData, $verificationData);
}

/**
 * Integration function for admin rejection
 */
function integrateBlockchainAdminRejection($db, $payment, $rejectionReason, $adminUsername) {
    $paymentData = [
        'payment_request_id' => $payment['id'],
        'project_id' => $payment['project_id'],
        'homeowner_id' => $payment['homeowner_id'],
        'contractor_id' => $payment['contractor_id'],
        'amount' => $payment['requested_amount'],
        'stage_name' => $payment['stage_name'],
        'payment_method' => 'mixed'
    ];
    
    $verificationData = [
        'verifier_type' => 'admin',
        'verification_action' => 'admin_rejected',
        'verification_timestamp' => time(),
        'verification_notes' => $rejectionReason,
        'admin_username' => $adminUsername,
        'rejection_reason' => $rejectionReason
    ];
    
    return integrateBlockchainAdminVerification($db, $paymentData, $verificationData);
}

/**
 * Integration function for bulk admin verification
 */
function integrateBlockchainBulkAdminVerification($db, $payments, $verificationAction, $adminNotes, $adminUsername) {
    $results = [];
    
    foreach ($payments as $payment) {
        $result = integrateBlockchainAdminPaymentVerification(
            $db, 
            $payment, 
            $verificationAction, 
            $adminNotes, 
            $adminUsername
        );
        
        $results[] = [
            'payment_id' => $payment['id'],
            'blockchain_integration_success' => $result
        ];
    }
    
    return $results;
}

/**
 * Get admin verification blockchain audit trail
 */
function getAdminVerificationAuditTrail($db, $paymentRequestId) {
    try {
        require_once __DIR__ . '/../../blockchain/PaymentBlockchainIntegrator.php';
        
        $blockchainIntegrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
        return $blockchainIntegrator->getPaymentAuditTrail($paymentRequestId);
        
    } catch (Exception $e) {
        error_log("Admin verification audit trail retrieval failed: " . $e->getMessage());
        return null;
    }
}

// Usage examples:
/*
// For admin payment approval:
integrateBlockchainAdminPaymentVerification(
    $db, 
    $paymentRecord, 
    'admin_approved', 
    $admin_notes, 
    $admin_username, 
    $auto_progress_update
);

// For admin payment rejection:
integrateBlockchainAdminRejection($db, $paymentRecord, $rejection_reason, $admin_username);

// For admin approval with progress update:
$progressData = [
    'stage_completed' => true,
    'completion_percentage' => 100,
    'next_stage' => 'Structure Work'
];
integrateBlockchainAdminApprovalWithProgress($db, $paymentRecord, $admin_notes, $admin_username, $progressData);

// For bulk verification:
$bulkResults = integrateBlockchainBulkAdminVerification($db, $paymentRecords, 'admin_approved', $admin_notes, $admin_username);

// Get audit trail for admin review:
$auditTrail = getAdminVerificationAuditTrail($db, $payment_request_id);
*/