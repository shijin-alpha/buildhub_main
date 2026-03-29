/**
 * Alternative Payment Handler
 * 
 * Handles non-Razorpay payment methods like bank transfer, UPI, cash, cheque
 */

class AlternativePaymentHandler {
    constructor() {
        this.paymentMethods = {
            'bank_transfer': {
                name: 'Bank Transfer (NEFT/RTGS)',
                icon: '🏦',
                color: '#007bff',
                maxAmount: 10000000,
                processingTime: '1-2 business days'
            },
            'upi': {
                name: 'UPI Payment',
                icon: '📱',
                color: '#28a745',
                maxAmount: 1000000,
                processingTime: 'Instant'
            },
            'cash': {
                name: 'Cash Payment',
                icon: '💵',
                color: '#ffc107',
                maxAmount: 200000,
                processingTime: 'Immediate'
            },
            'cheque': {
                name: 'Cheque Payment',
                icon: '📝',
                color: '#6f42c1',
                maxAmount: 50000000,
                processingTime: '3-5 business days'
            }
        };
    }
    
    /**
     * Get available payment methods for amount
     */
    async getPaymentMethods(amount) {
        try {
            const response = await fetch('/backend/api/homeowner/get_payment_methods.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ amount: amount })
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Failed to get payment methods');
            }
            
            return result.data;
            
        } catch (error) {
            console.error('Get payment methods error:', error);
            throw error;
        }
    }
    
    /**
     * Initiate alternative payment
     */
    async initiatePayment(paymentData) {
        try {
            const response = await fetch('/backend/api/homeowner/initiate_alternative_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(paymentData)
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Failed to initiate payment');
            }
            
            return result.data;
            
        } catch (error) {
            console.error('Payment initiation error:', error);
            throw error;
        }
    }
    
    /**
     * Generate QR code for UPI payment
     */
    generateUPIQR(upiId, amount, note) {
        const upiString = `upi://pay?pa=${upiId}&am=${amount}&tn=${encodeURIComponent(note)}`;
        return `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(upiString)}`;
    }
    
    /**
     * Format bank details for display
     */
    formatBankDetails(bankDetails) {
        return {
            accountName: bankDetails.account_name,
            accountNumber: bankDetails.account_number,
            ifscCode: bankDetails.ifsc_code,
            bankName: bankDetails.bank_name,
            branchName: bankDetails.branch_name,
            upiId: bankDetails.upi_id
        };
    }
    
    /**
     * Create payment instructions UI
     */
    createInstructionsUI(instructions, bankDetails, amount) {
        const container = document.createElement('div');
        container.className = 'payment-instructions';
        
        let html = `
            <div class="instructions-header">
                <h3>${instructions.title}</h3>
                <div class="processing-time">
                    <span class="time-badge">⏱️ ${instructions.processing_time}</span>
                </div>
            </div>
            
            <div class="instructions-content">
                <div class="steps-section">
                    <h4>📋 Step-by-Step Instructions:</h4>
                    <ol class="instruction-steps">
        `;
        
        instructions.steps.forEach(step => {
            html += `<li>${step}</li>`;
        });
        
        html += `
                    </ol>
                </div>
        `;
        
        // Add bank details if available
        if (bankDetails) {
            html += `
                <div class="bank-details-section">
                    <h4>🏦 Bank Details:</h4>
                    <div class="bank-details-grid">
                        <div class="detail-item">
                            <label>Account Name:</label>
                            <span class="copyable" data-copy="${bankDetails.account_name}">${bankDetails.account_name}</span>
                        </div>
                        <div class="detail-item">
                            <label>Account Number:</label>
                            <span class="copyable" data-copy="${bankDetails.account_number}">${bankDetails.account_number}</span>
                        </div>
                        <div class="detail-item">
                            <label>IFSC Code:</label>
                            <span class="copyable" data-copy="${bankDetails.ifsc_code}">${bankDetails.ifsc_code}</span>
                        </div>
                        <div class="detail-item">
                            <label>Bank Name:</label>
                            <span>${bankDetails.bank_name}</span>
                        </div>
                        ${bankDetails.upi_id ? `
                        <div class="detail-item">
                            <label>UPI ID:</label>
                            <span class="copyable" data-copy="${bankDetails.upi_id}">${bankDetails.upi_id}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }
        
        // Add UPI QR code if UPI payment
        if (bankDetails && bankDetails.upi_id && instructions.title.includes('UPI')) {
            const qrUrl = this.generateUPIQR(bankDetails.upi_id, amount, 'BuildHub Payment');
            html += `
                <div class="upi-qr-section">
                    <h4>📱 UPI QR Code:</h4>
                    <div class="qr-code-container">
                        <img src="${qrUrl}" alt="UPI QR Code" class="upi-qr-code">
                        <p>Scan this QR code with any UPI app</p>
                    </div>
                </div>
            `;
        }
        
        // Add safety note if cash payment
        if (instructions.safety_note) {
            html += `
                <div class="safety-note">
                    <h4>⚠️ Safety Note:</h4>
                    <p>${instructions.safety_note}</p>
                </div>
            `;
        }
        
        html += `
                <div class="verification-note">
                    <h4>✅ Verification Required:</h4>
                    <p>After completing the payment, please upload your receipt/proof for verification. 
                    The payment will be marked as completed once verified by the contractor or admin.</p>
                </div>
            </div>
        `;
        
        container.innerHTML = html;
        
        // Add copy functionality
        container.querySelectorAll('.copyable').forEach(element => {
            element.addEventListener('click', () => {
                const textToCopy = element.dataset.copy;
                navigator.clipboard.writeText(textToCopy).then(() => {
                    element.classList.add('copied');
                    setTimeout(() => element.classList.remove('copied'), 2000);
                });
            });
        });
        
        return container;
    }
    
    /**
     * Show payment method selection modal
     */
    async showPaymentMethodSelection(amount, onMethodSelected) {
        try {
            const paymentMethods = await this.getPaymentMethods(amount);
            
            // Create modal
            const modal = document.createElement('div');
            modal.className = 'payment-method-modal';
            modal.innerHTML = `
                <div class="modal-overlay">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>💰 Choose Payment Method</h2>
                            <span class="close-modal">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div class="amount-display">
                                <h3>Amount: ${paymentMethods.formatted_amount}</h3>
                            </div>
                            <div class="methods-grid" id="methodsGrid">
                                <!-- Methods will be populated here -->
                            </div>
                            <div class="recommendations">
                                <h4>💡 Recommendations:</h4>
                                <ul id="recommendationsList">
                                    <!-- Recommendations will be populated here -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Populate methods
            const methodsGrid = modal.querySelector('#methodsGrid');
            Object.entries(paymentMethods.available_methods).forEach(([key, method]) => {
                const methodCard = document.createElement('div');
                methodCard.className = `method-card ${key === paymentMethods.recommended_method ? 'recommended' : ''}`;
                methodCard.innerHTML = `
                    <div class="method-icon">${method.icon}</div>
                    <div class="method-info">
                        <h4>${method.name}</h4>
                        <p class="method-description">${method.description}</p>
                        <div class="method-details">
                            <span class="processing-time">⏱️ ${method.processing_time}</span>
                            <span class="fees">${method.fees}</span>
                        </div>
                        ${method.status === 'unavailable' ? 
                            `<div class="status-badge unavailable">❌ ${method.message}</div>` :
                            method.status === 'limited' ?
                            `<div class="status-badge limited">⚠️ ${method.message}</div>` :
                            '<div class="status-badge available">✅ Available</div>'
                        }
                    </div>
                `;
                
                if (method.status !== 'unavailable') {
                    methodCard.addEventListener('click', () => {
                        modal.remove();
                        onMethodSelected(key, method);
                    });
                } else {
                    methodCard.classList.add('disabled');
                }
                
                methodsGrid.appendChild(methodCard);
            });
            
            // Populate recommendations
            const recommendationsList = modal.querySelector('#recommendationsList');
            paymentMethods.recommendations.forEach(rec => {
                const li = document.createElement('li');
                li.innerHTML = `<strong>${paymentMethods.available_methods[rec.method]?.name}:</strong> ${rec.reason}`;
                recommendationsList.appendChild(li);
            });
            
            // Add close functionality
            modal.querySelector('.close-modal').addEventListener('click', () => {
                modal.remove();
            });
            
            modal.querySelector('.modal-overlay').addEventListener('click', (e) => {
                if (e.target === modal.querySelector('.modal-overlay')) {
                    modal.remove();
                }
            });
            
            document.body.appendChild(modal);
            
        } catch (error) {
            console.error('Payment method selection error:', error);
            alert('Failed to load payment methods: ' + error.message);
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
}

// Export for use in other modules
window.AlternativePaymentHandler = AlternativePaymentHandler;

// Create global instance
window.alternativePaymentHandler = new AlternativePaymentHandler();