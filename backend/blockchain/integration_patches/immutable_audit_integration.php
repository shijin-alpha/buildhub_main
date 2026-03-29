<?php
/**
 * Immutable Audit Integration Patches
 * 
 * This file contains integration code snippets that can be added to existing
 * payment verification APIs to enable immutable audit logging.
 * 
 * These patches are designed to be non-intrusive and fail-safe.
 * They will not affect existing functionality if they encounter errors.
 */

/**
 * INTEGRATION PATCH 1: Contractor Payment Verification
 * 
 * Add this code to: backend/api/contractor/verify_payment_receipt.php
 * Location: After successful verification update (around line 120-130)
 * 
 * Add after the database update but before the final JSON response
 */

/*
// === IMMUTABLE AUDIT INTEGRATION - CONTRACTOR VERIFICATION ===
try {
    require_once __DIR__ . '/../../blockchain/PaymentAuditIntegrator.php';
    
    // Prepare payment data for audit
    $auditPaymentData = [
        'payment_id' => $payment_id,
        'project_id' => $payment['project_id'],
        'amount' => $payment['requested_amount'],
        'stage' => $payment['stage_name'],
        'payment_method' => 'mixed' // Contractor verifies various payment methods
    ];
    
    // Prepare verification data for audit
    $auditVerificationData = [
        'verification_status' => $verification_status,
        'verification_notes' => $verification_notes,
        'verifier_type' => 'contractor',
        'contractor_id' => $contractor_id
    ];
    
    // Record contractor verification in immutable audit ledger
    PaymentAuditIntegrator::onContractorVerification($db, $auditPaymentData, $auditVerificationData);
    
} catch (Exception $auditError) {
    // Audit integration failure should not affect payment verification
    error_log("Immutable audit integration failed for contractor verification (non-critical): " . $auditError->getMessage());
}
// === END IMMUTABLE AUDIT INTEGRATION ===
*/

/**
 * INTEGRATION PATCH 2: Admin Payment Verification
 * 
 * Add this code to: backend/api/admin/verify_payment_receipt.php
 * Location: After successful verification and before blockchain integration (around line 180-190)
 * 
 * Add after the database commit but before the existing blockchain integration
 */

/*
// === IMMUTABLE AUDIT INTEGRATION - ADMIN VERIFICATION ===
try {
    require_once __DIR__ . '/../../blockchain/PaymentAuditIntegrator.php';
    
    // Prepare payment data for audit
    $auditPaymentData = [
        'payment_id' => $payment_id,
        'project_id' => $payment['project_id'],
        'amount' => $payment['requested_amount'],
        'stage' => $payment['stage_name'],
        'payment_method' => 'mixed' // Admin verifies various payment methods
    ];
    
    // Prepare verification data for audit
    $auditVerificationData = [
        'verification_action' => $verification_action,
        'admin_notes' => $admin_notes,
        'admin_username' => $admin_username,
        'auto_progress_update' => $auto_progress_update,
        'verifier_type' => 'admin'
    ];
    
    // Record admin verification in immutable audit ledger
    PaymentAuditIntegrator::onAdminVerification($db, $auditPaymentData, $auditVerificationData);
    
} catch (Exception $auditError) {
    // Audit integration failure should not affect payment verification
    error_log("Immutable audit integration failed for admin verification (non-critical): " . $auditError->getMessage());
}
// === END IMMUTABLE AUDIT INTEGRATION ===
*/

/**
 * INTEGRATION PATCH 3: Payment Completion Hook
 * 
 * Add this code to payment completion endpoints:
 * - Razorpay payment verification endpoints
 * - Bank transfer confirmation endpoints
 * - Alternative payment completion endpoints
 * 
 * Location: After payment status is updated to 'completed' or 'paid'
 */

/*
// === IMMUTABLE AUDIT INTEGRATION - PAYMENT COMPLETION ===
try {
    require_once __DIR__ . '/../../blockchain/PaymentAuditIntegrator.php';
    
    // Prepare payment data for audit
    $auditPaymentData = [
        'payment_id' => $payment_id, // or $paymentData['id']
        'project_id' => $project_id, // or $paymentData['project_id']
        'amount' => $amount, // or $paymentData['amount']
        'stage' => $stage_name, // or $paymentData['stage']
        'payment_method' => 'razorpay' // or 'bank_transfer', 'upi', etc.
    ];
    
    // Record payment completion in immutable audit ledger
    PaymentAuditIntegrator::onPaymentCompleted($db, $auditPaymentData);
    
} catch (Exception $auditError) {
    // Audit integration failure should not affect payment processing
    error_log("Immutable audit integration failed for payment completion (non-critical): " . $auditError->getMessage());
}
// === END IMMUTABLE AUDIT INTEGRATION ===
*/

/**
 * HELPER FUNCTIONS FOR INTEGRATION
 */

/**
 * Generic function to integrate audit logging for contractor verification
 */
function integrateImmutableAuditContractorVerification($db, $paymentId, $paymentData, $verificationStatus, $verificationNotes, $contractorId) {
    try {
        require_once __DIR__ . '/../PaymentAuditIntegrator.php';
        
        $auditPaymentData = [
            'payment_id' => $paymentId,
            'project_id' => $paymentData['project_id'] ?? null,
            'amount' => $paymentData['requested_amount'] ?? $paymentData['amount'] ?? 0,
            'stage' => $paymentData['stage_name'] ?? $paymentData['stage'] ?? '',
            'payment_method' => 'mixed'
        ];
        
        $auditVerificationData = [
            'verification_status' => $verificationStatus,
            'verification_notes' => $verificationNotes,
            'verifier_type' => 'contractor',
            'contractor_id' => $contractorId
        ];
        
        return PaymentAuditIntegrator::onContractorVerification($db, $auditPaymentData, $auditVerificationData);
        
    } catch (Exception $e) {
        error_log("Immutable audit integration failed (non-critical): " . $e->getMessage());
        return null;
    }
}

/**
 * Generic function to integrate audit logging for admin verification
 */
function integrateImmutableAuditAdminVerification($db, $paymentId, $paymentData, $verificationAction, $adminNotes, $adminUsername, $autoProgressUpdate = false) {
    try {
        require_once __DIR__ . '/../PaymentAuditIntegrator.php';
        
        $auditPaymentData = [
            'payment_id' => $paymentId,
            'project_id' => $paymentData['project_id'] ?? null,
            'amount' => $paymentData['requested_amount'] ?? $paymentData['amount'] ?? 0,
            'stage' => $paymentData['stage_name'] ?? $paymentData['stage'] ?? '',
            'payment_method' => 'mixed'
        ];
        
        $auditVerificationData = [
            'verification_action' => $verificationAction,
            'admin_notes' => $adminNotes,
            'admin_username' => $adminUsername,
            'auto_progress_update' => $autoProgressUpdate,
            'verifier_type' => 'admin'
        ];
        
        return PaymentAuditIntegrator::onAdminVerification($db, $auditPaymentData, $auditVerificationData);
        
    } catch (Exception $e) {
        error_log("Immutable audit integration failed (non-critical): " . $e->getMessage());
        return null;
    }
}

/**
 * Generic function to integrate audit logging for payment completion
 */
function integrateImmutableAuditPaymentCompletion($db, $paymentId, $projectId, $amount, $stage, $paymentMethod) {
    try {
        require_once __DIR__ . '/../PaymentAuditIntegrator.php';
        
        $auditPaymentData = [
            'payment_id' => $paymentId,
            'project_id' => $projectId,
            'amount' => $amount,
            'stage' => $stage,
            'payment_method' => $paymentMethod
        ];
        
        return PaymentAuditIntegrator::onPaymentCompleted($db, $auditPaymentData);
        
    } catch (Exception $e) {
        error_log("Immutable audit integration failed (non-critical): " . $e->getMessage());
        return null;
    }
}

/**
 * INTEGRATION INSTRUCTIONS:
 * 
 * 1. For Contractor Verification:
 *    - Open backend/api/contractor/verify_payment_receipt.php
 *    - Find the section after the database update (around line 120-130)
 *    - Add the contractor verification integration code
 * 
 * 2. For Admin Verification:
 *    - Open backend/api/admin/verify_payment_receipt.php
 *    - Find the section after the database commit (around line 180-190)
 *    - Add the admin verification integration code
 * 
 * 3. For Payment Completion:
 *    - Identify all payment completion endpoints
 *    - Add the payment completion integration code after status updates
 * 
 * 4. Testing:
 *    - All integrations are fail-safe and will not break existing functionality
 *    - Check error logs for any integration issues
 *    - Use the audit trail API to verify entries are being created
 * 
 * 5. Monitoring:
 *    - Monitor error logs for audit integration messages
 *    - Use PaymentAuditIntegrator::getAuditStatistics() for monitoring
 *    - Use PaymentAuditIntegrator::verifyLedgerIntegrity() for health checks
 */