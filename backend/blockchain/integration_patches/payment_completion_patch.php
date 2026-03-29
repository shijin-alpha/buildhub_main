<?php

/**
 * Blockchain Integration Patch for Payment Completion
 * 
 * This patch can be added to existing payment completion/verification endpoints
 * without modifying their core logic. Add this code after successful
 * payment verification but before the final response.
 */

// Add this code to existing payment completion endpoints:
// - backend/api/homeowner/verify_stage_payment.php
// - backend/api/contractor/verify_payment_receipt.php (for alternative payments)

// Example integration for verify_stage_payment.php:

/*
// Add this after successful Razorpay signature verification and database update

// Blockchain Trust Layer Integration (non-intrusive)
try {
    require_once __DIR__ . '/../../blockchain/PaymentBlockchainIntegrator.php';
    
    $blockchainIntegrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
    
    // Prepare payment data for blockchain completion recording
    $paymentData = [
        'payment_request_id' => $payment_request_id,
        'project_id' => $transaction['project_id'] ?? null,
        'homeowner_id' => $homeowner_id,
        'contractor_id' => $transaction['contractor_id'] ?? null,
        'amount' => $transaction['amount'] ?? null,
        'stage_name' => $transaction['stage_name'] ?? null,
        'payment_method' => 'razorpay'
    ];
    
    // Prepare completion data
    $completionData = [
        'razorpay_payment_id' => $razorpay_payment_id,
        'razorpay_order_id' => $razorpay_order_id,
        'razorpay_signature' => $razorpay_signature,
        'verification_level' => 'cryptographic',
        'payment_method' => 'razorpay',
        'completion_timestamp' => time()
    ];
    
    // Record payment completion on blockchain (non-blocking)
    $blockchainIntegrator->onPaymentCompleted($paymentData, $completionData);
    
} catch (Exception $e) {
    // Blockchain integration failure should not affect payment processing
    error_log("Blockchain integration failed during payment completion: " . $e->getMessage());
}
*/

// Example integration for alternative payment verification:

/*
// Add this after successful alternative payment verification and status update

// Blockchain Trust Layer Integration (non-intrusive)
try {
    require_once __DIR__ . '/../../blockchain/PaymentBlockchainIntegrator.php';
    
    $blockchainIntegrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
    
    // Prepare payment data for blockchain completion recording
    $paymentData = [
        'payment_request_id' => $payment['reference_id'],
        'project_id' => null, // Will be determined from reference
        'homeowner_id' => $payment['homeowner_id'],
        'contractor_id' => $payment['contractor_id'],
        'amount' => $payment['amount'],
        'stage_name' => null,
        'payment_method' => $payment['payment_method']
    ];
    
    // Prepare completion data
    $completionData = [
        'alternative_payment_id' => $payment['id'],
        'payment_method' => $payment['payment_method'],
        'verification_level' => 'manual',
        'receipt_file_path' => $payment['receipt_file_path'],
        'completion_timestamp' => time()
    ];
    
    // Record payment completion on blockchain (non-blocking)
    $blockchainIntegrator->onPaymentCompleted($paymentData, $completionData);
    
} catch (Exception $e) {
    error_log("Blockchain integration failed during alternative payment completion: " . $e->getMessage());
}
*/

// Integration Instructions:
// 1. Add the blockchain integration code after successful payment verification
// 2. After database status updates but before final JSON response
// 3. Wrap in try-catch to ensure payment processing is not affected
// 4. Use appropriate payment and completion data based on the endpoint context

// For verify_stage_payment.php, add after line ~100 (after signature verification and DB update)
// For alternative payment verification, add after payment status update to 'completed'

/**
 * Generic integration function for payment completion
 */
function integrateBlockchainPaymentCompletion($db, $paymentData, $completionData = []) {
    try {
        require_once __DIR__ . '/../../blockchain/PaymentBlockchainIntegrator.php';
        
        $blockchainIntegrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
        $blockchainIntegrator->onPaymentCompleted($paymentData, $completionData);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Blockchain payment completion integration failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Integration function specifically for Razorpay payments
 */
function integrateBlockchainRazorpayCompletion($db, $paymentRequestId, $razorpayData) {
    $paymentData = [
        'payment_request_id' => $paymentRequestId,
        'payment_method' => 'razorpay'
    ];
    
    $completionData = [
        'razorpay_payment_id' => $razorpayData['payment_id'],
        'razorpay_order_id' => $razorpayData['order_id'],
        'razorpay_signature' => $razorpayData['signature'],
        'verification_level' => 'cryptographic',
        'payment_method' => 'razorpay',
        'completion_timestamp' => time()
    ];
    
    return integrateBlockchainPaymentCompletion($db, $paymentData, $completionData);
}

/**
 * Integration function specifically for alternative payments
 */
function integrateBlockchainAlternativeCompletion($db, $alternativePayment) {
    $paymentData = [
        'payment_request_id' => $alternativePayment['reference_id'],
        'homeowner_id' => $alternativePayment['homeowner_id'],
        'contractor_id' => $alternativePayment['contractor_id'],
        'amount' => $alternativePayment['amount'],
        'payment_method' => $alternativePayment['payment_method']
    ];
    
    $completionData = [
        'alternative_payment_id' => $alternativePayment['id'],
        'payment_method' => $alternativePayment['payment_method'],
        'verification_level' => 'manual',
        'receipt_file_path' => $alternativePayment['receipt_file_path'] ?? null,
        'completion_timestamp' => time()
    ];
    
    return integrateBlockchainPaymentCompletion($db, $paymentData, $completionData);
}

// Usage examples:
/*
// For Razorpay completion:
$razorpayData = [
    'payment_id' => $razorpay_payment_id,
    'order_id' => $razorpay_order_id,
    'signature' => $razorpay_signature
];
integrateBlockchainRazorpayCompletion($db, $payment_request_id, $razorpayData);

// For alternative payment completion:
integrateBlockchainAlternativeCompletion($db, $alternativePaymentRecord);
*/