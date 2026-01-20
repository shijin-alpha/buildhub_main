/**
 * Split Payment Handler
 * 
 * Handles large payments by splitting them into smaller transactions
 * within Razorpay limits
 */

class SplitPaymentHandler {
    constructor() {
        this.maxSingleAmount = 2000000; // ₹20,00,000 (20 lakhs as requested)
        this.minSplitAmount = 10000;    // ₹10,000 minimum per split
        this.maxSplits = 5;             // Maximum 5 splits per payment
    }
    
    /**
     * Check if payment needs to be split
     */
    needsSplit(amount) {
        return amount > this.maxSingleAmount;
    }
    
    /**
     * Calculate optimal payment splits
     */
    calculateSplits(totalAmount) {
        if (totalAmount <= this.maxSingleAmount) {
            return {
                needsSplit: false,
                splits: [{ amount: totalAmount, sequence: 1, description: 'Single payment' }],
                totalSplits: 1
            };
        }
        
        // Calculate number of splits needed
        const minSplits = Math.ceil(totalAmount / this.maxSingleAmount);
        
        if (minSplits > this.maxSplits) {
            return {
                needsSplit: true,
                canSplit: false,
                error: `Amount too large - would require ${minSplits} splits, maximum allowed is ${this.maxSplits}`
            };
        }
        
        // Calculate split amounts
        const splits = [];
        let remainingAmount = totalAmount;
        
        for (let i = 1; i <= minSplits; i++) {
            let splitAmount;
            
            if (i === minSplits) {
                // Last split gets remaining amount
                splitAmount = remainingAmount;
            } else {
                // Calculate equal splits
                splitAmount = Math.min(this.maxSingleAmount, remainingAmount / (minSplits - i + 1));
                splitAmount = Math.max(splitAmount, this.minSplitAmount);
            }
            
            splits.push({
                amount: Math.round(splitAmount * 100) / 100, // Round to 2 decimal places
                sequence: i,
                description: `Payment ${i} of ${minSplits}`
            });
            
            remainingAmount -= splitAmount;
        }
        
        return {
            needsSplit: true,
            canSplit: true,
            splits: splits,
            totalSplits: splits.length,
            totalAmount: totalAmount
        };
    }
    
    /**
     * Initiate split payment
     */
    async initiateSplitPayment(paymentData) {
        try {
            const response = await fetch('/backend/api/homeowner/initiate_split_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(paymentData)
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Failed to initiate split payment');
            }
            
            return result.data;
            
        } catch (error) {
            console.error('Split payment initiation error:', error);
            throw error;
        }
    }
    
    /**
     * Process individual split payment
     */
    async processSplitPayment(splitGroupId, sequenceNumber) {
        try {
            const response = await fetch('/backend/api/homeowner/process_split_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    split_group_id: splitGroupId,
                    sequence_number: sequenceNumber
                })
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Failed to process split payment');
            }
            
            return result.data;
            
        } catch (error) {
            console.error('Split payment processing error:', error);
            throw error;
        }
    }
    
    /**
     * Verify split payment
     */
    async verifySplitPayment(verificationData) {
        try {
            const response = await fetch('/backend/api/homeowner/verify_split_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(verificationData)
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Payment verification failed');
            }
            
            return result.data;
            
        } catch (error) {
            console.error('Split payment verification error:', error);
            throw error;
        }
    }
    
    /**
     * Handle complete split payment flow
     */
    async handleSplitPaymentFlow(paymentData, onProgress, onComplete, onError) {
        try {
            // Step 1: Initiate split payment
            const splitData = await this.initiateSplitPayment(paymentData);
            
            if (onProgress) {
                onProgress({
                    step: 'initiated',
                    message: `Split payment created: ${splitData.total_splits} payments`,
                    data: splitData
                });
            }
            
            // Step 2: Process each split payment
            await this.processSplitSequence(splitData, 1, onProgress, onComplete, onError);
            
        } catch (error) {
            console.error('Split payment flow error:', error);
            if (onError) {
                onError(error);
            }
        }
    }
    
    /**
     * Process split payment sequence
     */
    async processSplitSequence(splitData, currentSequence, onProgress, onComplete, onError) {
        try {
            // Get payment order for current sequence
            const paymentOrder = await this.processSplitPayment(splitData.split_group_id, currentSequence);
            
            if (onProgress) {
                onProgress({
                    step: 'processing',
                    sequence: currentSequence,
                    total: splitData.total_splits,
                    message: `Processing payment ${currentSequence} of ${splitData.total_splits}`,
                    amount: paymentOrder.progress.this_payment_amount
                });
            }
            
            // Create Razorpay options
            const razorpayOptions = {
                key: paymentOrder.razorpay_key_id,
                amount: paymentOrder.amount,
                currency: paymentOrder.currency,
                order_id: paymentOrder.razorpay_order_id,
                name: 'BuildHub',
                description: paymentOrder.description,
                image: '/frontend/assets/logo.png',
                prefill: {
                    name: paymentOrder.customer_name || '',
                    email: paymentData.customerEmail || '',
                    contact: paymentData.customerPhone || ''
                },
                notes: {
                    split_group_id: splitData.split_group_id,
                    sequence_number: currentSequence,
                    total_splits: splitData.total_splits
                },
                theme: {
                    color: '#3399cc'
                },
                handler: async (response) => {
                    try {
                        // Verify this split payment
                        const verificationData = {
                            split_group_id: splitData.split_group_id,
                            transaction_id: paymentOrder.transaction_id,
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_signature: response.razorpay_signature
                        };
                        
                        const verificationResult = await this.verifySplitPayment(verificationData);
                        
                        if (onProgress) {
                            onProgress({
                                step: 'completed',
                                sequence: currentSequence,
                                total: splitData.total_splits,
                                message: `Payment ${currentSequence} completed successfully`,
                                progress: verificationResult.progress,
                                allCompleted: verificationResult.all_completed
                            });
                        }
                        
                        // Check if all payments are completed
                        if (verificationResult.all_completed) {
                            if (onComplete) {
                                onComplete({
                                    message: 'All split payments completed successfully!',
                                    totalAmount: splitData.total_amount,
                                    totalSplits: splitData.total_splits,
                                    finalProgress: verificationResult.progress
                                });
                            }
                        } else {
                            // Process next payment
                            setTimeout(() => {
                                this.processSplitSequence(
                                    splitData, 
                                    currentSequence + 1, 
                                    onProgress, 
                                    onComplete, 
                                    onError
                                );
                            }, 2000); // 2 second delay between payments
                        }
                        
                    } catch (error) {
                        console.error('Split payment verification error:', error);
                        if (onError) {
                            onError(error);
                        }
                    }
                },
                modal: {
                    ondismiss: function() {
                        if (onError) {
                            onError(new Error(`Payment ${currentSequence} cancelled by user`));
                        }
                    }
                }
            };
            
            // Open Razorpay checkout
            if (typeof Razorpay === 'undefined') {
                throw new Error('Razorpay SDK not loaded');
            }
            
            const razorpay = new Razorpay(razorpayOptions);
            
            razorpay.on('payment.failed', function(response) {
                console.error('Split payment failed:', response.error);
                if (onError) {
                    onError(new Error(`Payment ${currentSequence} failed: ${response.error.description}`));
                }
            });
            
            razorpay.open();
            
        } catch (error) {
            console.error('Split sequence processing error:', error);
            if (onError) {
                onError(error);
            }
        }
    }
    
    /**
     * Format amount for display
     */
    formatAmount(amount) {
        return '₹' + amount.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    /**
     * Get split payment summary
     */
    getSplitSummary(totalAmount) {
        const splitInfo = this.calculateSplits(totalAmount);
        
        if (!splitInfo.needsSplit) {
            return {
                message: `Single payment of ${this.formatAmount(totalAmount)}`,
                type: 'single'
            };
        }
        
        if (!splitInfo.canSplit) {
            return {
                message: splitInfo.error,
                type: 'error'
            };
        }
        
        const splitAmounts = splitInfo.splits.map(split => this.formatAmount(split.amount));
        
        return {
            message: `Split into ${splitInfo.totalSplits} payments: ${splitAmounts.join(', ')}`,
            type: 'split',
            splits: splitInfo.splits,
            totalSplits: splitInfo.totalSplits
        };
    }
}

// Export for use in other modules
window.SplitPaymentHandler = SplitPaymentHandler;

// Create global instance
window.splitPaymentHandler = new SplitPaymentHandler();