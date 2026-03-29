<?php

/**
 * Blockchain Integration Patch for Contractor Verification
 * 
 * This patch can be added to existing contractor verification endpoints
 * without modifying their core logic. Add this code after successful
 * contractor verification but before the final response.
 */

// Add this code to existing contractor verification endpoints:
// - backend/api/contractor/verify_payment_receipt.php (for alternative payments)
// - Any endpoint where contractor confirms payment receipt

// Example integration for contractor payment receipt verification:

/*
// Add this after successful contractor verification and database update

// Blockchain Trust Layer Integration (non-intrusive)
try {
    require_once __DIR__ . '/../../blockchain/PaymentBlockchainIntegrator.php';
    
    $blockchainIntegrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
    
    // Prepare payment data for blockchain contractor verification recording
    $paymentData = [
        'payment_request_id' => $payment['reference_id'],
        'project_id' => null, // Will be determined from reference
        'homeowner_id' => $payment['homeowner_id'],
        'contractor_id' => $contractor_id,
        'amount' => $payment['amount'],
        'stage_name' => null,
        'payment_method' => $payment['payment_method']
    ];
    
    // Prepare verification data
    $verificationData = [
        'verifier_id' => $contractor_id,
        'verifier_type' => 'contractor',
        'verification_action' => 'confirmed',
        'verification_timestamp' => time(),
        'verification_notes' => $contractor_notes ?? null,
        'alternative_payment_id' => $payment['id']
    ];
    
    // Record contractor verification on blockchain (non-blocking)
    $blockchainIntegrator->onContractorVerification($paymentData, $verificationData);
    
} catch (Exception $e) {
    // Blockchain integration failure should not affect payment processing
    error_log("Blockchain integration failed during contractor verification: " . $e->getMessage());
}
*/

// Example integration for stage payment contractor confirmation:

/*
// Add this after contractor confirms receipt of stage payment

// Blockchain Trust Layer Integration (non-intrusive)
try {
    require_once __DIR__ . '/../../blockchain/PaymentBlockchainIntegrator.php';
    
    $blockchainIntegrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
    
    // Prepare payment data
    $paymentData = [
        'payment_request_id' => $payment_request_id,
        'project_id' => $paymentRequest['project_id'],
        'homeowner_id' => $paymentRequest['homeowner_id'],
        'contractor_id' => $contractor_id,
        'amount' => $paymentRequest['requested_amount'],
        'stage_name' => $paymentRequest['stage_name'],
        'payment_method' => 'razorpay'
    ];
    
    // Prepare verification data
    $verificationData = [
        'verifier_id' => $contractor_id,
        'verifier_type' => 'contractor',
        'verification_action' => 'receipt_confirmed',
        'verification_timestamp' => time(),
        'verification_notes' => 'Contractor confirmed payment receipt'
    ];
    
    // Record contractor verification on blockchain (non-blocking)
    $blockchainIntegrator->onContractorVerification($paymentData, $verificationData);
    
} catch (Exception $e) {
    error_log("Blockchain integration failed during contractor verification: " . $e->getMessage());
}
*/

// Integration Instructions:
// 1. Add the blockchain integration code after successful contractor verification
// 2. After database status updates but before final JSON response
// 3. Wrap in try-catch to ensure verification processing is not affected
// 4. Use appropriate payment and verification data based on the endpoint context

// For contractor payment receipt verification, add after verification status update
// For stage payment contractor confirmation, add after contractor acknowledgment

/**
 * Generic integration function for contractor verification
 */
function integrateBlockchainContractorVerification($db, $paymentData, $verificationData = []) {
    try {
        require_once __DIR__ . '/../../blockchain/PaymentBlockchainIntegrator.php';
        
        $blockchainIntegrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
        $blockchainIntegrator->onContractorVerification($paymentData, $verificationData);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Blockchain contractor verification integration failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Integration function specifically for alternative payment contractor verification
 */
function integrateBlockchainAlternativeContractorVerification($db, $alternativePayment, $contractorId, $notes = null) {
    $paymentData = [
        'payment_request_id' => $alternativePayment['reference_id'],
        'homeowner_id' => $alternativePayment['homeowner_id'],
        'contractor_id' => $contractorId,
        'amount' => $alternativePayment['amount'],
        'payment_method' => $alternativePayment['payment_method']
    ];
    
    $verificationData = [
        'verifier_id' => $contractorId,
        'verifier_type' => 'contractor',
        'verification_action' => 'receipt_verified',
        'verification_timestamp' => time(),
        'verification_notes' => $notes,
        'alternative_payment_id' => $alternativePayment['id'],
        'payment_method' => $alternativePayment['payment_method']
    ];
    
    return integrateBlockchainContractorVerification($db, $paymentData, $verificationData);
}

/**
 * Integration function specifically for stage payment contractor verification
 */
function integrateBlockchainStageContractorVerification($db, $paymentRequest, $contractorId, $notes = null) {
    $paymentData = [
        'payment_request_id' => $paymentRequest['id'],
        'project_id' => $paymentRequest['project_id'],
        'homeowner_id' => $paymentRequest['homeowner_id'],
        'contractor_id' => $contractorId,
        'amount' => $paymentRequest['requested_amount'],
        'stage_name' => $paymentRequest['stage_name'],
        'payment_method' => 'razorpay'
    ];
    
    $verificationData = [
        'verifier_id' => $contractorId,
        'verifier_type' => 'contractor',
        'verification_action' => 'receipt_confirmed',
        'verification_timestamp' => time(),
        'verification_notes' => $notes ?? 'Contractor confirmed payment receipt',
        'stage_name' => $paymentRequest['stage_name']
    ];
    
    return integrateBlockchainContractorVerification($db, $paymentData, $verificationData);
}

/**
 * Integration function for contractor work completion verification
 */
function integrateBlockchainWorkCompletionVerification($db, $paymentRequest, $contractorId, $completionData) {
    $paymentData = [
        'payment_request_id' => $paymentRequest['id'],
        'project_id' => $paymentRequest['project_id'],
        'homeowner_id' => $paymentRequest['homeowner_id'],
        'contractor_id' => $contractorId,
        'amount' => $paymentRequest['requested_amount'],
        'stage_name' => $paymentRequest['stage_name'],
        'payment_method' => 'razorpay'
    ];
    
    $verificationData = [
        'verifier_id' => $contractorId,
        'verifier_type' => 'contractor',
        'verification_action' => 'work_completed',
        'verification_timestamp' => time(),
        'verification_notes' => 'Contractor verified work completion',
        'completion_percentage' => $completionData['completion_percentage'] ?? null,
        'work_description' => $completionData['work_description'] ?? null
    ];
    
    return integrateBlockchainContractorVerification($db, $paymentData, $verificationData);
}

// Usage examples:
/*
// For alternative payment contractor verification:
integrateBlockchainAlternativeContractorVerification($db, $alternativePaymentRecord, $contractor_id, $contractor_notes);

// For stage payment contractor verification:
integrateBlockchainStageContractorVerification($db, $paymentRequestRecord, $contractor_id, $contractor_notes);

// For work completion verification:
$completionData = [
    'completion_percentage' => 100,
    'work_description' => 'Foundation work completed as per specifications'
];
integrateBlockchainWorkCompletionVerification($db, $paymentRequestRecord, $contractor_id, $completionData);
*/