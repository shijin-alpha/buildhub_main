/**
 * Demo Payment Handler for Testing
 * 
 * This simulates Razorpay payment flow for testing purposes
 * when you don't have actual Razorpay keys configured.
 */

window.DemoPaymentHandler = {
    
    /**
     * Simulate Razorpay payment
     */
    simulatePayment: function(options) {
        console.log('🎭 Demo Payment Handler - Simulating Razorpay payment');
        console.log('Payment options:', options);
        
        // Show a demo payment dialog
        const confirmed = confirm(
            `Demo Payment Simulation\n\n` +
            `Amount: ₹${(options.amount / 100).toLocaleString('en-IN')}\n` +
            `Description: ${options.description}\n\n` +
            `Click OK to simulate successful payment, Cancel to simulate failure.`
        );
        
        if (confirmed) {
            // Simulate successful payment after a delay
            setTimeout(() => {
                const mockResponse = {
                    razorpay_payment_id: 'pay_demo_' + Date.now(),
                    razorpay_order_id: options.order_id,
                    razorpay_signature: 'sig_demo_' + Date.now()
                };
                
                console.log('✅ Demo payment successful:', mockResponse);
                
                if (options.handler) {
                    options.handler(mockResponse);
                }
            }, 1000);
        } else {
            console.log('❌ Demo payment cancelled');
            alert('Payment cancelled');
        }
    },
    
    /**
     * Check if we should use demo mode
     */
    shouldUseDemoMode: function(keyId) {
        // Only use demo mode for placeholder keys
        return keyId === 'rzp_test_demo_key_for_testing' || 
               keyId.includes('demo') || 
               keyId.includes('test_1234567890') ||
               keyId === 'rzp_test_your_key_here';
    }
};

/**
 * Override Razorpay constructor for demo mode
 */
window.OriginalRazorpay = window.Razorpay;

window.Razorpay = function(options) {
    // Check if we should use demo mode
    if (window.DemoPaymentHandler.shouldUseDemoMode(options.key)) {
        console.log('🎭 Using Demo Payment Handler instead of Razorpay');
        
        return {
            open: function() {
                window.DemoPaymentHandler.simulatePayment(options);
            }
        };
    } else {
        // Use real Razorpay for valid keys
        console.log('💳 Using Real Razorpay with key:', options.key);
        
        if (window.OriginalRazorpay) {
            return new window.OriginalRazorpay(options);
        } else {
            console.error('Razorpay not loaded');
            alert('Payment system not available. Please check Razorpay configuration.');
        }
    }
};

console.log('💳 Payment Handler loaded - Real Razorpay mode enabled');