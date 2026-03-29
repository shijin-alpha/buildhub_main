/**
 * International Payment Handler
 * 
 * Handles international payments with enhanced error handling and fallback options
 */

class InternationalPaymentHandler {
    constructor() {
        this.supportedCountries = {
            'IN': 'India',
            'US': 'United States', 
            'GB': 'United Kingdom',
            'CA': 'Canada',
            'AU': 'Australia',
            'SG': 'Singapore',
            'AE': 'United Arab Emirates',
            'MY': 'Malaysia'
        };
        
        this.supportedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'AUD', 'CAD'];
        
        this.paymentMethods = {
            'IN': ['card', 'netbanking', 'wallet', 'upi'],
            'default': ['card']
        };
    }
    
    /**
     * Detect user's country (simplified - use proper geolocation in production)
     */
    async detectUserCountry() {
        try {
            // Try to get country from browser locale
            const locale = navigator.language || navigator.userLanguage;
            const countryCode = locale.split('-')[1] || 'IN';
            return countryCode.toUpperCase();
        } catch (error) {
            console.warn('Could not detect country, defaulting to IN:', error);
            return 'IN';
        }
    }
    
    /**
     * Get supported payment methods for country
     */
    getPaymentMethodsForCountry(countryCode) {
        return this.paymentMethods[countryCode] || this.paymentMethods['default'];
    }
    
    /**
     * Validate international payment
     */
    validatePayment(amount, currency, countryCode) {
        const errors = [];
        
        if (!this.supportedCountries[countryCode]) {
            errors.push(`Payments from ${countryCode} are not currently supported`);
        }
        
        if (!this.supportedCurrencies.includes(currency)) {
            errors.push(`Currency ${currency} is not supported`);
        }
        
        if (amount <= 0) {
            errors.push('Payment amount must be greater than zero');
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }
    
    /**
     * Initialize international payment
     */
    async initiatePayment(paymentData) {
        try {
            const countryCode = paymentData.countryCode || await this.detectUserCountry();
            
            // Validate payment
            const validation = this.validatePayment(
                paymentData.amount, 
                paymentData.currency || 'INR', 
                countryCode
            );
            
            if (!validation.valid) {
                throw new Error(validation.errors.join(', '));
            }
            
            // Prepare payment request
            const paymentRequest = {
                ...paymentData,
                country_code: countryCode,
                currency: paymentData.currency || 'INR'
            };
            
            // Call backend API
            const response = await fetch('/backend/api/homeowner/initiate_international_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(paymentRequest)
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Payment initiation failed');
            }
            
            return result.data;
            
        } catch (error) {
            console.error('Payment initiation error:', error);
            throw error;
        }
    }
    
    /**
     * Handle Razorpay payment with international support
     */
    async processRazorpayPayment(paymentData, onSuccess, onError) {
        try {
            const orderData = await this.initiatePayment(paymentData);
            
            // Enhanced Razorpay options for international payments
            const razorpayOptions = {
                key: orderData.razorpay_key_id,
                amount: orderData.amount,
                currency: orderData.currency,
                order_id: orderData.razorpay_order_id,
                name: 'BuildHub',
                description: orderData.description,
                image: '/frontend/assets/logo.png',
                prefill: {
                    name: paymentData.customerName || '',
                    email: paymentData.customerEmail || '',
                    contact: paymentData.customerPhone || ''
                },
                notes: {
                    payment_type: paymentData.payment_type,
                    international_payment: orderData.international_payment,
                    original_currency: orderData.original_currency,
                    country_code: orderData.country_code
                },
                theme: {
                    color: '#3399cc'
                },
                method: {
                    card: orderData.supported_methods.includes('card'),
                    netbanking: orderData.supported_methods.includes('netbanking'),
                    wallet: orderData.supported_methods.includes('wallet'),
                    upi: orderData.supported_methods.includes('upi')
                },
                handler: function(response) {
                    console.log('Payment successful:', response);
                    if (onSuccess) {
                        onSuccess({
                            ...response,
                            payment_id: orderData.payment_id,
                            original_amount: orderData.original_amount,
                            original_currency: orderData.original_currency
                        });
                    }
                },
                modal: {
                    ondismiss: function() {
                        console.log('Payment modal dismissed');
                        if (onError) {
                            onError(new Error('Payment cancelled by user'));
                        }
                    }
                }
            };
            
            // Check if Razorpay is loaded
            if (typeof Razorpay === 'undefined') {
                throw new Error('Razorpay SDK not loaded. Please check your internet connection.');
            }
            
            // Create and open Razorpay checkout
            const razorpay = new Razorpay(razorpayOptions);
            
            razorpay.on('payment.failed', function(response) {
                console.error('Payment failed:', response.error);
                
                let errorMessage = 'Payment failed';
                if (response.error.description) {
                    errorMessage = response.error.description;
                } else if (response.error.reason) {
                    errorMessage = response.error.reason;
                }
                
                // Handle specific international card errors
                if (errorMessage.toLowerCase().includes('international')) {
                    errorMessage = 'International cards are not supported. Please try with an Indian card or contact support for assistance.';
                }
                
                if (onError) {
                    onError(new Error(errorMessage));
                }
            });
            
            razorpay.open();
            
        } catch (error) {
            console.error('Razorpay payment processing error:', error);
            if (onError) {
                onError(error);
            }
        }
    }
    
    /**
     * Show alternative payment options for international users
     */
    showAlternativePaymentOptions(countryCode) {
        const alternatives = [];
        
        if (!this.supportedCountries[countryCode]) {
            alternatives.push({
                method: 'bank_transfer',
                title: 'International Bank Transfer',
                description: 'Contact our support team for bank transfer details',
                action: 'contact_support'
            });
            
            alternatives.push({
                method: 'paypal',
                title: 'PayPal (Coming Soon)',
                description: 'PayPal integration is being added for international payments',
                action: 'notify_when_available'
            });
        }
        
        return alternatives;
    }
}

// Export for use in other modules
window.InternationalPaymentHandler = InternationalPaymentHandler;

// Create global instance
window.internationalPaymentHandler = new InternationalPaymentHandler();