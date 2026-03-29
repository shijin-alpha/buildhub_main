<?php
/**
 * Payment Limits Configuration
 * 
 * Configure payment limits based on your Razorpay account type and requirements
 */

// Minimum payment amount (Razorpay minimum is ₹1)
define('RAZORPAY_MIN_AMOUNT', 1); // ₹1 (minimum allowed by Razorpay)

// Razorpay Test Mode Limits (updated for your requirements)
define('RAZORPAY_TEST_MAX_AMOUNT', 2000000); // ₹20,00,000 (20 lakhs as requested)
define('RAZORPAY_TEST_DAILY_LIMIT', 10000000); // ₹1,00,00,000 (1 crore daily)

// Razorpay Live Mode Limits (with KYC completed)
define('RAZORPAY_LIVE_MAX_AMOUNT', 10000000); // ₹1,00,00,000 (1 crore)
define('RAZORPAY_LIVE_DAILY_LIMIT', 100000000); // ₹10,00,00,000 (10 crores)

// Custom Enterprise Limits (contact Razorpay for approval)
define('RAZORPAY_ENTERPRISE_MAX_AMOUNT', 1000000000000); // ₹1000000 lakhs (₹100 crores)
define('RAZORPAY_ENTERPRISE_DAILY_LIMIT', 10000000000000); // ₹1000 crores

// Current mode configuration
define('PAYMENT_MODE', 'test'); // 'test', 'live', 'enterprise', or 'project'

// Project-specific limits (20 lakhs as requested)
define('PROJECT_MAX_AMOUNT', 2000000); // ₹20,00,000 (20 lakhs as requested)
define('PROJECT_DAILY_LIMIT', 10000000); // ₹1,00,00,000 (1 crore daily)

/**
 * Get maximum payment amount based on current mode
 */
function getMaxPaymentAmount() {
    switch (PAYMENT_MODE) {
        case 'live':
            return RAZORPAY_LIVE_MAX_AMOUNT;
        case 'enterprise':
            return RAZORPAY_ENTERPRISE_MAX_AMOUNT;
        case 'project':
            return PROJECT_MAX_AMOUNT;
        case 'test':
        default:
            return RAZORPAY_TEST_MAX_AMOUNT;
    }
}

/**
 * Get daily payment limit based on current mode
 */
function getDailyPaymentLimit() {
    switch (PAYMENT_MODE) {
        case 'live':
            return RAZORPAY_LIVE_DAILY_LIMIT;
        case 'enterprise':
            return RAZORPAY_ENTERPRISE_DAILY_LIMIT;
        case 'project':
            return PROJECT_DAILY_LIMIT;
        case 'test':
        default:
            return RAZORPAY_TEST_DAILY_LIMIT;
    }
}

/**
 * Validate payment amount against limits
 */
function validatePaymentAmount($amount) {
    $minAmount = RAZORPAY_MIN_AMOUNT;
    $maxAmount = getMaxPaymentAmount();
    
    if ($amount <= 0) {
        return [
            'valid' => false,
            'message' => 'Payment amount must be greater than zero'
        ];
    }
    
    if ($amount < $minAmount) {
        return [
            'valid' => false,
            'message' => "Payment amount ₹" . number_format($amount, 2) . " is below minimum allowed amount of ₹" . number_format($minAmount, 2)
        ];
    }
    
    if ($amount > $maxAmount) {
        $mode = strtoupper(PAYMENT_MODE);
        return [
            'valid' => false,
            'message' => "Payment amount ₹" . number_format($amount, 2) . " exceeds {$mode} mode limit of ₹" . number_format($maxAmount, 2) . ". " . getUpgradeMessage()
        ];
    }
    
    return [
        'valid' => true,
        'message' => 'Payment amount is valid'
    ];
}

/**
 * Get upgrade message based on current mode
 */
function getUpgradeMessage() {
    switch (PAYMENT_MODE) {
        case 'test':
            return "Switch to live mode for higher limits (up to ₹1 crore) or contact Razorpay for enterprise limits.";
        case 'live':
            return "Contact Razorpay support for enterprise limits (up to ₹100 crores with approval).";
        case 'enterprise':
            return "You are already using enterprise limits. Contact Razorpay for further increases.";
        default:
            return "Contact support for higher payment limits.";
    }
}

/**
 * Get payment limits information
 */
function getPaymentLimitsInfo() {
    return [
        'current_mode' => PAYMENT_MODE,
        'min_amount' => RAZORPAY_MIN_AMOUNT,
        'max_amount' => getMaxPaymentAmount(),
        'daily_limit' => getDailyPaymentLimit(),
        'min_amount_formatted' => '₹' . number_format(RAZORPAY_MIN_AMOUNT, 2),
        'max_amount_formatted' => '₹' . number_format(getMaxPaymentAmount(), 2),
        'daily_limit_formatted' => '₹' . number_format(getDailyPaymentLimit(), 2),
        'upgrade_message' => getUpgradeMessage()
    ];
}
?>