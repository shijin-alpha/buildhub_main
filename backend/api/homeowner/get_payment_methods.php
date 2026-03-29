<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config/alternative_payment_config.php';

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    $amount = isset($input['amount']) ? (float)$input['amount'] : 0;
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    
    // Get available payment methods
    $availableMethods = getAvailablePaymentMethods($amount);
    $recommendedMethod = getRecommendedPaymentMethod($amount);
    
    // Add Razorpay as option with limitations
    $razorpayStatus = 'limited';
    $razorpayMessage = 'May have amount limitations';
    
    if ($amount > 2000000) { // Above ₹20 lakhs
        $razorpayStatus = 'unavailable';
        $razorpayMessage = 'Amount exceeds Razorpay limits';
    }
    
    $allMethods = [
        'razorpay' => [
            'name' => 'Razorpay (Online Payment)',
            'max_amount' => 2000000,
            'processing_time' => 'Instant',
            'fees' => '2-3% processing fee',
            'description' => 'Credit/Debit cards, UPI, Net Banking',
            'suitable_for' => 'Quick online payments',
            'status' => $razorpayStatus,
            'message' => $razorpayMessage,
            'icon' => '💳'
        ]
    ];
    
    // Merge with alternative methods
    $allMethods = array_merge($allMethods, $availableMethods);
    
    // Filter methods based on amount
    $suitableMethods = [];
    foreach ($allMethods as $key => $method) {
        if ($key === 'razorpay') {
            $suitableMethods[$key] = $method;
        } elseif ($amount <= $method['max_amount']) {
            $suitableMethods[$key] = $method;
        }
    }
    
    // Generate recommendations
    $recommendations = [];
    
    if ($amount <= 200000) {
        $recommendations[] = [
            'method' => 'upi',
            'reason' => 'Best for amounts up to ₹2 lakhs - instant and free'
        ];
        $recommendations[] = [
            'method' => 'razorpay',
            'reason' => 'Online payment with multiple options'
        ];
    } elseif ($amount <= 1000000) {
        $recommendations[] = [
            'method' => 'upi',
            'reason' => 'Instant payment, no fees, up to ₹10 lakhs'
        ];
        $recommendations[] = [
            'method' => 'bank_transfer',
            'reason' => 'Secure for large amounts, 1-2 days processing'
        ];
    } elseif ($amount <= 10000000) {
        $recommendations[] = [
            'method' => 'bank_transfer',
            'reason' => 'Most suitable for amounts above ₹10 lakhs'
        ];
        $recommendations[] = [
            'method' => 'cheque',
            'reason' => 'Traditional method for large payments'
        ];
    } else {
        $recommendations[] = [
            'method' => 'cheque',
            'reason' => 'Best for very large amounts above ₹1 crore'
        ];
        $recommendations[] = [
            'method' => 'bank_transfer',
            'reason' => 'Alternative for large amounts'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'amount' => $amount,
            'formatted_amount' => '₹' . number_format($amount, 2),
            'available_methods' => $suitableMethods,
            'recommended_method' => $recommendedMethod,
            'recommendations' => $recommendations,
            'summary' => [
                'total_methods' => count($suitableMethods),
                'instant_methods' => array_keys(array_filter($suitableMethods, function($m) { 
                    return $m['processing_time'] === 'Instant'; 
                })),
                'no_fee_methods' => array_keys(array_filter($suitableMethods, function($m) { 
                    return $m['fees'] === 'No fees'; 
                })),
                'high_limit_methods' => array_keys(array_filter($suitableMethods, function($m) { 
                    return $m['max_amount'] >= 10000000; 
                }))
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get payment methods error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred: ' . $e->getMessage()
    ]);
}
?>