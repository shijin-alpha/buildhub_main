<?php
/**
 * Alternative Payment Methods Configuration
 * 
 * Multiple payment options when Razorpay limits are exceeded
 */

// Enable alternative payment methods
define('ENABLE_ALTERNATIVE_PAYMENTS', true);

// Bank Transfer Configuration
define('ENABLE_BANK_TRANSFER', true);
define('BANK_TRANSFER_MAX_AMOUNT', 10000000); // ₹1 crore limit
define('BANK_TRANSFER_PROCESSING_TIME', '1-2 business days');

// UPI Payment Configuration
define('ENABLE_UPI_PAYMENTS', true);
define('UPI_MAX_AMOUNT', 1000000); // ₹10 lakh per transaction
define('UPI_DAILY_LIMIT', 10000000); // ₹1 crore daily

// Cash Payment Configuration
define('ENABLE_CASH_PAYMENTS', true);
define('CASH_MAX_AMOUNT', 200000); // ₹2 lakh limit for cash
define('CASH_VERIFICATION_REQUIRED', true);

// Cheque Payment Configuration
define('ENABLE_CHEQUE_PAYMENTS', true);
define('CHEQUE_MAX_AMOUNT', 50000000); // ₹5 crore limit
define('CHEQUE_CLEARING_TIME', '3-5 business days');

// Cryptocurrency Payment (for international)
define('ENABLE_CRYPTO_PAYMENTS', false); // Disabled by default
define('CRYPTO_SUPPORTED_COINS', ['BTC', 'ETH', 'USDT']);

/**
 * Get available payment methods for amount
 */
function getAvailablePaymentMethods($amount) {
    $methods = [];
    
    // Bank Transfer
    if (ENABLE_BANK_TRANSFER && $amount <= BANK_TRANSFER_MAX_AMOUNT) {
        $methods['bank_transfer'] = [
            'name' => 'Bank Transfer (NEFT/RTGS)',
            'max_amount' => BANK_TRANSFER_MAX_AMOUNT,
            'processing_time' => BANK_TRANSFER_PROCESSING_TIME,
            'fees' => 'No fees',
            'description' => 'Direct bank transfer to contractor account',
            'suitable_for' => 'Large amounts, secure transactions',
            'icon' => '🏦'
        ];
    }
    
    // UPI Payments
    if (ENABLE_UPI_PAYMENTS && $amount <= UPI_MAX_AMOUNT) {
        $methods['upi'] = [
            'name' => 'UPI Payment',
            'max_amount' => UPI_MAX_AMOUNT,
            'processing_time' => 'Instant',
            'fees' => 'No fees',
            'description' => 'Pay using UPI apps like PhonePe, GPay, Paytm',
            'suitable_for' => 'Quick payments up to ₹10 lakhs',
            'icon' => '📱'
        ];
    }
    
    // Cash Payments
    if (ENABLE_CASH_PAYMENTS && $amount <= CASH_MAX_AMOUNT) {
        $methods['cash'] = [
            'name' => 'Cash Payment',
            'max_amount' => CASH_MAX_AMOUNT,
            'processing_time' => 'Immediate',
            'fees' => 'No fees',
            'description' => 'Direct cash payment to contractor',
            'suitable_for' => 'Small amounts, immediate needs',
            'verification_required' => CASH_VERIFICATION_REQUIRED,
            'icon' => '💵'
        ];
    }
    
    // Cheque Payments
    if (ENABLE_CHEQUE_PAYMENTS && $amount <= CHEQUE_MAX_AMOUNT) {
        $methods['cheque'] = [
            'name' => 'Cheque Payment',
            'max_amount' => CHEQUE_MAX_AMOUNT,
            'processing_time' => CHEQUE_CLEARING_TIME,
            'fees' => 'Bank charges may apply',
            'description' => 'Issue cheque in favor of contractor',
            'suitable_for' => 'Very large amounts, formal transactions',
            'icon' => '📝'
        ];
    }
    
    return $methods;
}

/**
 * Get recommended payment method for amount
 */
function getRecommendedPaymentMethod($amount) {
    if ($amount <= 200000) {
        return 'upi'; // Up to ₹2 lakhs - UPI
    } elseif ($amount <= 1000000) {
        return 'upi'; // Up to ₹10 lakhs - UPI
    } elseif ($amount <= 10000000) {
        return 'bank_transfer'; // Up to ₹1 crore - Bank Transfer
    } else {
        return 'cheque'; // Above ₹1 crore - Cheque
    }
}

/**
 * Get payment method details
 */
function getPaymentMethodDetails($method) {
    $methods = getAvailablePaymentMethods(PHP_INT_MAX);
    return $methods[$method] ?? null;
}

/**
 * Validate payment method for amount
 */
function validatePaymentMethod($method, $amount) {
    $methods = getAvailablePaymentMethods($amount);
    
    if (!isset($methods[$method])) {
        return [
            'valid' => false,
            'message' => "Payment method '{$method}' not available for amount ₹" . number_format($amount, 2)
        ];
    }
    
    $methodDetails = $methods[$method];
    if ($amount > $methodDetails['max_amount']) {
        return [
            'valid' => false,
            'message' => "Amount ₹" . number_format($amount, 2) . " exceeds {$methodDetails['name']} limit of ₹" . number_format($methodDetails['max_amount'], 2)
        ];
    }
    
    return [
        'valid' => true,
        'message' => 'Payment method is valid',
        'method_details' => $methodDetails
    ];
}

/**
 * Get contractor bank details
 */
function getContractorBankDetails($contractor_id) {
    // This would typically fetch from database
    return [
        'account_name' => 'Contractor Name',
        'account_number' => '1234567890',
        'ifsc_code' => 'SBIN0001234',
        'bank_name' => 'State Bank of India',
        'branch' => 'Main Branch',
        'upi_id' => 'contractor@paytm'
    ];
}

/**
 * Generate payment instructions
 */
function generatePaymentInstructions($method, $amount, $contractor_id, $reference_id) {
    $bankDetails = getContractorBankDetails($contractor_id);
    $instructions = [];
    
    switch ($method) {
        case 'bank_transfer':
            $instructions = [
                'title' => 'Bank Transfer Instructions',
                'steps' => [
                    'Login to your net banking or visit bank branch',
                    'Select NEFT/RTGS transfer option',
                    'Enter beneficiary details:',
                    "  • Account Name: {$bankDetails['account_name']}",
                    "  • Account Number: {$bankDetails['account_number']}",
                    "  • IFSC Code: {$bankDetails['ifsc_code']}",
                    "  • Bank: {$bankDetails['bank_name']}",
                    "Enter amount: ₹" . number_format($amount, 2),
                    "Add reference: Payment for Project #{$reference_id}",
                    'Complete the transfer and save transaction receipt',
                    'Upload receipt in the system for verification'
                ],
                'processing_time' => BANK_TRANSFER_PROCESSING_TIME,
                'verification_required' => true
            ];
            break;
            
        case 'upi':
            $instructions = [
                'title' => 'UPI Payment Instructions',
                'steps' => [
                    'Open your UPI app (PhonePe, GPay, Paytm, etc.)',
                    'Select "Send Money" or "Pay" option',
                    "Enter UPI ID: {$bankDetails['upi_id']}",
                    "Or scan QR code provided below",
                    "Enter amount: ₹" . number_format($amount, 2),
                    "Add note: Payment for Project #{$reference_id}",
                    'Complete payment using UPI PIN',
                    'Take screenshot of success message',
                    'Upload screenshot for verification'
                ],
                'processing_time' => 'Instant',
                'verification_required' => true
            ];
            break;
            
        case 'cash':
            $instructions = [
                'title' => 'Cash Payment Instructions',
                'steps' => [
                    'Arrange meeting with contractor at safe location',
                    "Count cash amount: ₹" . number_format($amount, 2),
                    'Hand over cash to contractor',
                    'Get signed receipt from contractor',
                    'Take photo of receipt and cash handover',
                    'Upload receipt photo for verification',
                    'Both parties confirm payment in system'
                ],
                'processing_time' => 'Immediate',
                'verification_required' => true,
                'safety_note' => 'Meet in public place, bring witness if possible'
            ];
            break;
            
        case 'cheque':
            $instructions = [
                'title' => 'Cheque Payment Instructions',
                'steps' => [
                    'Write cheque for amount: ₹' . number_format($amount, 2),
                    "Make payable to: {$bankDetails['account_name']}",
                    'Write current date',
                    "Add reference: Payment for Project #{$reference_id}",
                    'Sign the cheque',
                    'Hand over cheque to contractor or post it',
                    'Take photo of cheque before giving',
                    'Upload cheque photo for records',
                    'Wait for cheque clearance (3-5 business days)'
                ],
                'processing_time' => CHEQUE_CLEARING_TIME,
                'verification_required' => true
            ];
            break;
    }
    
    return $instructions;
}
?>