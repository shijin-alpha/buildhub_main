import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import PaymentReceiptUpload from './PaymentReceiptUpload.jsx';
import '../styles/PaymentMethodSelector.css';

const PaymentMethodSelector = ({ 
  amount, 
  paymentRequest, 
  onPaymentInitiated, 
  onCancel,
  show = false 
}) => {
  const toast = useToast();
  const [loading, setLoading] = useState(false);
  const [paymentMethods, setPaymentMethods] = useState(null);
  const [selectedMethod, setSelectedMethod] = useState(null);
  const [showInstructions, setShowInstructions] = useState(false);
  const [showReceiptUpload, setShowReceiptUpload] = useState(false);
  const [paymentInstructions, setPaymentInstructions] = useState(null);
  const [bankDetails, setBankDetails] = useState(null);
  const [alternativePaymentId, setAlternativePaymentId] = useState(null);

  useEffect(() => {
    if (show && amount > 0) {
      loadPaymentMethods();
      // Prevent body scroll when modal opens
      document.body.style.overflow = 'hidden';
      document.body.classList.add('modal-open');
    } else if (!show) {
      // Restore body scroll when modal closes
      document.body.style.overflow = '';
      document.body.classList.remove('modal-open');
    }
  }, [show, amount]);

  // Handle escape key for modal
  useEffect(() => {
    const handleEscapeKey = (event) => {
      if (event.key === 'Escape' && show) {
        onCancel();
      }
    };

    if (show) {
      document.addEventListener('keydown', handleEscapeKey);
    }

    return () => {
      document.removeEventListener('keydown', handleEscapeKey);
    };
  }, [show, onCancel]);

  const loadPaymentMethods = async () => {
    try {
      setLoading(true);
      const response = await fetch('/buildhub/backend/api/homeowner/get_payment_methods.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount: amount })
      });

      const data = await response.json();
      if (data.success) {
        setPaymentMethods(data.data);
      } else {
        toast.error('Failed to load payment methods: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading payment methods:', error);
      toast.error('Error loading payment methods');
    } finally {
      setLoading(false);
    }
  };

  const handleMethodSelect = async (methodKey, method) => {
    setSelectedMethod({ key: methodKey, ...method });
    
    if (methodKey === 'razorpay') {
      // Handle Razorpay payment
      await initiateRazorpayPayment();
    } else {
      // Handle alternative payment methods
      await initiateAlternativePayment(methodKey, method);
    }
  };

  const initiateRazorpayPayment = async () => {
    try {
      setLoading(true);
      
      // Wait for Razorpay to be available
      let attempts = 0;
      while ((!window.Razorpay || window._razorpayLoading) && attempts < 20) {
        await new Promise(resolve => setTimeout(resolve, 100));
        attempts++;
      }

      if (!window.Razorpay) {
        toast.error('Payment system not loaded. Please refresh the page and try again.');
        return;
      }

      // Create Razorpay order - use different API based on payment type
      const isCustomPayment = paymentRequest.request_type === 'custom';
      const apiEndpoint = isCustomPayment 
        ? '/buildhub/backend/api/homeowner/initiate_custom_payment.php'
        : '/buildhub/backend/api/homeowner/initiate_stage_payment.php';
      
      const orderResponse = await fetch(apiEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          payment_request_id: paymentRequest.id,
          amount: amount
        })
      });

      const orderData = await orderResponse.json();
      if (!orderData.success) {
        toast.error('Failed to create payment order: ' + orderData.message);
        return;
      }

      // Initialize Razorpay payment
      const paymentDescription = isCustomPayment 
        ? `Payment for ${paymentRequest.request_title || 'Custom Request'}`
        : `Payment for ${paymentRequest.stage_name} stage`;
      
      const options = {
        key: orderData.data.razorpay_key_id,
        amount: orderData.data.amount,
        currency: orderData.data.currency,
        name: 'BuildHub',
        description: paymentDescription,
        order_id: orderData.data.razorpay_order_id,
        handler: async function (response) {
          try {
            const verifyEndpoint = isCustomPayment 
              ? '/buildhub/backend/api/homeowner/verify_custom_payment.php'
              : '/buildhub/backend/api/homeowner/verify_stage_payment.php';
              
            const verifyResponse = await fetch(verifyEndpoint, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                payment_request_id: paymentRequest.id,
                razorpay_order_id: response.razorpay_order_id,
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_signature: response.razorpay_signature
              })
            });

            const verifyData = await verifyResponse.json();
            if (verifyData.success) {
              toast.success('Payment completed successfully!');
              if (onPaymentInitiated) {
                onPaymentInitiated(verifyData.data);
              }
            } else {
              toast.error('Payment verification failed: ' + verifyData.message);
            }
          } catch (error) {
            console.error('Payment verification error:', error);
            toast.error('Payment verification failed');
          }
        },
        prefill: {
          name: paymentRequest.homeowner_name || '',
          email: paymentRequest.homeowner_email || '',
          contact: paymentRequest.homeowner_phone || ''
        },
        theme: {
          color: '#007bff'
        }
      };

      const rzp = new window.Razorpay(options);
      rzp.open();

    } catch (error) {
      console.error('Razorpay payment error:', error);
      toast.error('Failed to initiate payment: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  const initiateAlternativePayment = async (methodKey, method) => {
    try {
      setLoading(true);
      
      const isCustomPayment = paymentRequest.request_type === 'custom';
      const paymentType = isCustomPayment ? 'custom_payment' : 'stage_payment';
      
      const response = await fetch('/buildhub/backend/api/homeowner/initiate_alternative_payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          payment_type: paymentType,
          reference_id: paymentRequest.id,
          payment_method: methodKey,
          amount: amount,
          notes: `Payment for ${paymentRequest.stage_name} stage`
        })
      });

      const data = await response.json();
      if (data.success) {
        setPaymentInstructions(data.data.instructions);
        setBankDetails(data.data.bank_details);
        setAlternativePaymentId(data.data.payment_id);
        setShowInstructions(true);
        
        toast.success(`${method.name} payment initiated. Please follow the instructions.`);
        
        if (onPaymentInitiated) {
          onPaymentInitiated(data.data);
        }
      } else {
        toast.error('Failed to initiate payment: ' + data.message);
      }
    } catch (error) {
      console.error('Alternative payment error:', error);
      toast.error('Failed to initiate payment: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  };

  const handleUploadReceipt = () => {
    setShowInstructions(false);
    setShowReceiptUpload(true);
  };

  const handleReceiptUploadComplete = (uploadData) => {
    setShowReceiptUpload(false);
    toast.success('Receipt uploaded successfully! The contractor will verify your payment.');
    if (onPaymentInitiated) {
      onPaymentInitiated(uploadData);
    }
  };

  const handleReceiptUploadCancel = () => {
    setShowReceiptUpload(false);
    setShowInstructions(true);
  };

  const copyToClipboard = (text, label) => {
    navigator.clipboard.writeText(text).then(() => {
      toast.success(`${label} copied to clipboard!`);
    }).catch(() => {
      toast.error('Failed to copy to clipboard');
    });
  };

  if (!show) return null;

  if (showInstructions && paymentInstructions) {
    return (
      <div className="payment-method-modal">
        <div 
          className="modal-overlay"
          onClick={(e) => {
            if (e.target === e.currentTarget) {
              onCancel();
            }
          }}
        >
          <div className="modal-content instructions-modal">
            <div className="modal-header">
              <h2>{selectedMethod.icon} {paymentInstructions.title}</h2>
              <button className="close-btn" onClick={onCancel}>×</button>
            </div>

            <div className="modal-body">
              <div className="amount-display">
                <h3>Amount: {formatCurrency(amount)}</h3>
                <div className="processing-time">
                  <span className="time-badge">⏱️ {paymentInstructions.processing_time}</span>
                </div>
              </div>

              {bankDetails && (
                <div className="bank-details-section">
                  <h4>🏦 Bank Details:</h4>
                  <div className="bank-details-grid">
                    <div className="detail-item">
                      <label>Account Name:</label>
                      <div className="copyable-field">
                        <span>{bankDetails.account_name}</span>
                        <button 
                          className="copy-btn"
                          onClick={() => copyToClipboard(bankDetails.account_name, 'Account Name')}
                        >
                          📋
                        </button>
                      </div>
                    </div>
                    <div className="detail-item">
                      <label>Account Number:</label>
                      <div className="copyable-field">
                        <span>{bankDetails.account_number}</span>
                        <button 
                          className="copy-btn"
                          onClick={() => copyToClipboard(bankDetails.account_number, 'Account Number')}
                        >
                          📋
                        </button>
                      </div>
                    </div>
                    <div className="detail-item">
                      <label>IFSC Code:</label>
                      <div className="copyable-field">
                        <span>{bankDetails.ifsc_code}</span>
                        <button 
                          className="copy-btn"
                          onClick={() => copyToClipboard(bankDetails.ifsc_code, 'IFSC Code')}
                        >
                          📋
                        </button>
                      </div>
                    </div>
                    <div className="detail-item">
                      <label>Bank Name:</label>
                      <span>{bankDetails.bank_name}</span>
                    </div>
                    {bankDetails.upi_id && (
                      <div className="detail-item">
                        <label>UPI ID:</label>
                        <div className="copyable-field">
                          <span>{bankDetails.upi_id}</span>
                          <button 
                            className="copy-btn"
                            onClick={() => copyToClipboard(bankDetails.upi_id, 'UPI ID')}
                          >
                            📋
                          </button>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {bankDetails && bankDetails.upi_id && selectedMethod.key === 'upi' && (
                <div className="upi-qr-section">
                  <h4>📱 UPI QR Code:</h4>
                  <div className="qr-code-container">
                    <img 
                      src={`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(`upi://pay?pa=${bankDetails.upi_id}&am=${amount}&tn=BuildHub Payment for Project ${paymentRequest.id}`)}`}
                      alt="UPI QR Code" 
                      className="upi-qr-code"
                    />
                    <p>Scan this QR code with any UPI app</p>
                  </div>
                </div>
              )}

              <div className="instructions-section">
                <h4>📋 Step-by-Step Instructions:</h4>
                <ol className="instruction-steps">
                  {paymentInstructions.steps.map((step, index) => (
                    <li key={index}>{step}</li>
                  ))}
                </ol>
              </div>

              {paymentInstructions.safety_note && (
                <div className="safety-note">
                  <h4>⚠️ Safety Note:</h4>
                  <p>{paymentInstructions.safety_note}</p>
                </div>
              )}

              <div className="verification-note">
                <h4>✅ Verification Required:</h4>
                <p>After completing the payment, please upload your receipt/proof for verification. 
                The payment will be marked as completed once verified by the contractor or admin.</p>
              </div>
            </div>

            <div className="modal-actions">
              <button className="secondary-btn" onClick={onCancel}>
                Close Instructions
              </button>
              <button className="upload-receipt-btn" onClick={handleUploadReceipt}>
                📤 Upload Receipt
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="payment-method-modal">
      <div 
        className="modal-overlay"
        onClick={(e) => {
          if (e.target === e.currentTarget) {
            onCancel();
          }
        }}
      >
        <div className="modal-content">
          <div className="modal-header">
            <h2>💰 Choose Payment Method</h2>
            <button className="close-btn" onClick={onCancel}>×</button>
          </div>

          <div className="modal-body">
            {loading ? (
              <div className="loading-spinner">
                <div className="spinner"></div>
                <p>Loading payment methods...</p>
              </div>
            ) : paymentMethods ? (
              <>
                <div className="amount-display">
                  <h3>Amount: {paymentMethods.formatted_amount}</h3>
                </div>

                <div className="methods-grid">
                  {Object.entries(paymentMethods.available_methods).map(([key, method]) => (
                    <div 
                      key={key}
                      className={`method-card ${key === paymentMethods.recommended_method ? 'recommended' : ''} ${method.status === 'unavailable' ? 'disabled' : ''}`}
                      onClick={() => method.status !== 'unavailable' && handleMethodSelect(key, method)}
                    >
                      <div className="method-icon">{method.icon}</div>
                      <div className="method-info">
                        <h4>{method.name}</h4>
                        <p className="method-description">{method.description}</p>
                        <div className="method-details">
                          <span className="processing-time">⏱️ {method.processing_time}</span>
                          <span className="fees">{method.fees}</span>
                        </div>
                        {method.status === 'unavailable' ? 
                          <div className="status-badge unavailable">❌ {method.message}</div> :
                          method.status === 'limited' ?
                          <div className="status-badge limited">⚠️ {method.message}</div> :
                          <div className="status-badge available">✅ Available</div>
                        }
                        {key === paymentMethods.recommended_method && (
                          <div className="recommended-badge">⭐ Recommended</div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>

                {paymentMethods.recommendations && paymentMethods.recommendations.length > 0 && (
                  <div className="recommendations">
                    <h4>💡 Recommendations:</h4>
                    <ul>
                      {paymentMethods.recommendations.map((rec, index) => (
                        <li key={index}>
                          <strong>{paymentMethods.available_methods[rec.method]?.name}:</strong> {rec.reason}
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </>
            ) : (
              <div className="error-message">
                <p>Failed to load payment methods. Please try again.</p>
                <button className="retry-btn" onClick={loadPaymentMethods}>
                  🔄 Retry
                </button>
              </div>
            )}
          </div>

          <div className="modal-actions">
            <button className="secondary-btn" onClick={onCancel}>
              Cancel
            </button>
          </div>
        </div>
      </div>

      {/* Payment Receipt Upload */}
      <PaymentReceiptUpload
        show={showReceiptUpload}
        paymentId={alternativePaymentId}
        paymentMethod={selectedMethod?.key}
        amount={amount}
        onUploadComplete={handleReceiptUploadComplete}
        onCancel={handleReceiptUploadCancel}
      />
    </div>
  );
};

export default PaymentMethodSelector;