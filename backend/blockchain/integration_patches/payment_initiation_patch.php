<?php

/**
 * Blockchain Integration Patch for Payment Initiation
 * 
 * This patch can be added to existing payment initiation endpoints
 * without modifying their core logic. Add this code after successful
 * payment creation but before the final response.
 */

// Add this code to existing payment initiation endpoints:
// - backend/api/homeowner/initiate_stage_payment.php
// - backend/api/homeowner/initiate_alternative_payment.php
// - backend/api/homeowner/initiate_split_payment.php

// Example integration for initiate_stage_payment.php:

/*
// Add this after successful Razorpay order creation and before final response

// Blockchain Trust Layer Integration (non-intrusive)
try {
    require_once __DIR__ . '/../../blockchain/PaymentBlockchainIntegrator.php';
    
    $blockchainIntegrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
    
    // Prepare payment data for blockchain proof generation
    $paymentData = [
        'payment_request_id' => $payment_request_id,
        'project_id' => $request['project_id'] ?? null,
        'homeowner_id' => $homeowner_id,
        'contractor_id' => $request['contractor_id'] ?? null,
        'amount' => $amount,
        'stage_name' => $request['stage_name'] ?? null,
        'payment_method' => 'razorpay',
        'razorpay_order_id' => $razorpayOrder['id'] ?? null
    ];
    
    // Record payment initiation on blockchain (non-blocking)
    $blockchainIntegrator->onPaymentInitiated($paymentData);
    
} catch (Exception $e) {
    // Blockchain integration failure should not affect payment processing
    error_log("Blockchain integration failed during payment initiation: " . $e->getMessage());
}
*/

// Example integration for initiate_alternative_payment.php:

/*
// Add this after successful alternative payment setup and before final response

// Blockchain Trust Layer Integration (non-intrusive)
try {
    require_once __DIR__ . '/../../blockchain/PaymentBlockchainIntegrator.php';
    
    $blockchainIntegrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
    
    // Prepare payment data for blockchain proof generation
    $paymentData = [
        'payment_request_id' => $reference_id,
        'project_id' => null, // Will be determined from reference
        'homeowner_id' => $homeowner_id,
        'contractor_id' => $contractor_id,
        'amount' => $amount,
        'stage_name' => null, // Alternative payments may not have stages
        'payment_method' => $payment_method,
        'alternative_payment_id' => $alternativePaymentId
    ];
    
    // Record payment initiation on blockchain (non-blocking)
    $blockchainIntegrator->onPaymentInitiated($paymentData);
    
} catch (Exception $e) {
    error_log("Blockchain integration failed during alternative payment initiation: " . $e->getMessage());
}
*/

// Integration Instructions:
// 1. Add the blockchain integration code after successful payment creation
// 2. Before the final JSON response is sent
// 3. Wrap in try-catch to ensure payment processing is not affected
// 4. Use appropriate payment data based on the endpoint context

// For initiate_stage_payment.php, add after line ~150 (after Razorpay order creation)
// For initiate_alternative_payment.php, add after line ~120 (after alternative payment record creation)
// For initiate_split_payment.php, add after line ~180 (after split payment group creation)

/**
 * Generic integration function that can be called from any payment initiation endpoint
 */
function integrateBlockchainPaymentInitiation($db, $paymentData) {
    try {
        require_once __DIR__ . '/../../blockchain/PaymentBlockchainIntegrator.php';
        
        $blockchainIntegrator = PaymentBlockchainIntegrator::createIntegrationHooks($db);
        $blockchainIntegrator->onPaymentInitiated($paymentData);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Blockchain payment initiation integration failed: " . $e->getMessage());
        return false;
    }
}

// Usage example:
/*
$paymentData = [
    'payment_request_id' => $payment_request_id,
    'project_id' => $project_id,
    'homeowner_id' => $homeowner_id,
    'contractor_id' => $contractor_id,
    'amount' => $amount,
    'stage_name' => $stage_name,
    'payment_method' => $payment_method
];

integrateBlockchainPaymentInitiation($db, $paymentData);
*/