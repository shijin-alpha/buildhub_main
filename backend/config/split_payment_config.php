<?php
/**
 * Split Payment Configuration
 * 
 * Handles large payments by splitting them into smaller transactions
 * within Razorpay limits
 */

require_once 'payment_limits.php';

// Split payment settings
define('ENABLE_SPLIT_PAYMENTS', true);
define('MIN_SPLIT_AMOUNT', 10000); // Minimum ₹10,000 per split
define('MAX_SPLITS_PER_PAYMENT', 10); // Maximum 10 splits per payment (increased for larger amounts)
define('SPLIT_BUFFER_PERCENTAGE', 0.05); // 5% buffer below max limit

/**
 * Calculate optimal payment splits
 */
function calculatePaymentSplits($totalAmount) {
    if (!ENABLE_SPLIT_PAYMENTS) {
        return [
            'can_split' => false,
            'message' => 'Split payments are disabled'
        ];
    }
    
    $maxAmount = getMaxPaymentAmount();
    $maxSplitAmount = $maxAmount * (1 - SPLIT_BUFFER_PERCENTAGE); // Apply buffer
    
    // If amount is within single payment limit, no split needed
    if ($totalAmount <= $maxSplitAmount) {
        return [
            'can_split' => true, // Changed to true for consistency
            'splits' => [['amount' => $totalAmount, 'sequence' => 1, 'description' => 'Single payment']],
            'total_splits' => 1,
            'message' => 'No split required - amount within single payment limit'
        ];
    }
    
    // Calculate number of splits needed
    $minSplits = ceil($totalAmount / $maxSplitAmount);
    
    if ($minSplits > MAX_SPLITS_PER_PAYMENT) {
        return [
            'can_split' => false,
            'message' => "Amount too large - would require {$minSplits} splits, maximum allowed is " . MAX_SPLITS_PER_PAYMENT
        ];
    }
    
    // Calculate split amounts
    $splits = [];
    $remainingAmount = $totalAmount;
    
    for ($i = 1; $i <= $minSplits; $i++) {
        if ($i == $minSplits) {
            // Last split gets remaining amount
            $splitAmount = $remainingAmount;
        } else {
            // Calculate equal splits, but ensure each is above minimum
            $splitAmount = min($maxSplitAmount, $remainingAmount / ($minSplits - $i + 1));
            $splitAmount = max($splitAmount, MIN_SPLIT_AMOUNT);
        }
        
        $splits[] = [
            'amount' => round($splitAmount, 2),
            'sequence' => $i,
            'description' => "Payment {$i} of {$minSplits}"
        ];
        
        $remainingAmount -= $splitAmount;
    }
    
    return [
        'can_split' => true,
        'splits' => $splits,
        'total_splits' => count($splits),
        'total_amount' => $totalAmount,
        'max_single_amount' => $maxSplitAmount,
        'message' => "Payment will be split into " . count($splits) . " transactions"
    ];
}

/**
 * Validate split payment configuration
 */
function validateSplitPayment($totalAmount, $splits) {
    $errors = [];
    
    // Check total amount matches
    $splitTotal = array_sum(array_column($splits, 'amount'));
    if (abs($splitTotal - $totalAmount) > 0.01) {
        $errors[] = "Split amounts don't match total: ₹{$splitTotal} vs ₹{$totalAmount}";
    }
    
    // Check each split is within limits
    $maxAmount = getMaxPaymentAmount() * (1 - SPLIT_BUFFER_PERCENTAGE);
    foreach ($splits as $split) {
        if ($split['amount'] > $maxAmount) {
            $errors[] = "Split amount ₹{$split['amount']} exceeds limit ₹{$maxAmount}";
        }
        if ($split['amount'] < MIN_SPLIT_AMOUNT) {
            $errors[] = "Split amount ₹{$split['amount']} below minimum ₹" . MIN_SPLIT_AMOUNT;
        }
    }
    
    // Check number of splits
    if (count($splits) > MAX_SPLITS_PER_PAYMENT) {
        $errors[] = "Too many splits: " . count($splits) . " (max: " . MAX_SPLITS_PER_PAYMENT . ")";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Get split payment status options
 */
function getSplitPaymentStatuses() {
    return [
        'pending' => 'Pending - waiting for first payment',
        'partial' => 'Partial - some payments completed',
        'completed' => 'Completed - all payments successful',
        'failed' => 'Failed - one or more payments failed',
        'cancelled' => 'Cancelled - payment series cancelled'
    ];
}

/**
 * Calculate split payment progress
 */
function calculateSplitProgress($completedSplits, $totalSplits, $completedAmount, $totalAmount) {
    return [
        'splits_progress' => round(($completedSplits / $totalSplits) * 100, 1),
        'amount_progress' => round(($completedAmount / $totalAmount) * 100, 1),
        'completed_splits' => $completedSplits,
        'total_splits' => $totalSplits,
        'completed_amount' => $completedAmount,
        'total_amount' => $totalAmount,
        'remaining_amount' => $totalAmount - $completedAmount,
        'remaining_splits' => $totalSplits - $completedSplits
    ];
}

/**
 * Get recommended split strategy
 */
function getRecommendedSplitStrategy($amount) {
    $maxAmount = getMaxPaymentAmount() * (1 - SPLIT_BUFFER_PERCENTAGE);
    
    if ($amount <= $maxAmount) {
        return [
            'strategy' => 'single',
            'description' => 'Single payment - amount within limits',
            'splits' => 1
        ];
    }
    
    // Try to minimize number of splits while keeping amounts reasonable
    $idealSplitAmount = min($maxAmount, $amount / 2); // Prefer 2 splits if possible
    $recommendedSplits = ceil($amount / $idealSplitAmount);
    
    if ($recommendedSplits <= MAX_SPLITS_PER_PAYMENT) {
        return [
            'strategy' => 'equal',
            'description' => "Split into {$recommendedSplits} equal payments",
            'splits' => $recommendedSplits,
            'amount_per_split' => round($amount / $recommendedSplits, 2)
        ];
    }
    
    return [
        'strategy' => 'maximum',
        'description' => "Split into maximum allowed " . MAX_SPLITS_PER_PAYMENT . " payments",
        'splits' => MAX_SPLITS_PER_PAYMENT,
        'amount_per_split' => round($amount / MAX_SPLITS_PER_PAYMENT, 2)
    ];
}
?>