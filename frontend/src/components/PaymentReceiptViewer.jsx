import React, { useState } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/PaymentReceiptUpload.css';

const PaymentReceiptViewer = ({ 
  show, 
  paymentRequest,
  onVerify,
  onReject,
  onClose 
}) => {
  const toast = useToast();
  const [verificationNotes, setVerificationNotes] = useState('');
  const [verifying, setVerifying] = useState(false);

  if (!show || !paymentRequest) return null;

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const getPaymentMethodIcon = (method) => {
    const icons = {
      bank_transfer: '🏦',
      upi: '📱',
      cash: '💵',
      cheque: '📝',
      online: '💳'
    };
    return icons[method] || '💰';
  };

  const handleVerify = async () => {
    setVerifying(true);
    try {
      await onVerify(paymentRequest.id, verificationNotes);
      toast.success('Payment verified successfully!');
      onClose();
    } catch (error) {
      toast.error('Failed to verify payment: ' + error.message);
    } finally {
      setVerifying(false);
    }
  };

  const handleReject = async () => {
    if (!verificationNotes.trim()) {
      toast.error('Please provide a reason for rejection');
      return;
    }
    
    setVerifying(true);
    try {
      await onReject(paymentRequest.id, verificationNotes);
      toast.success('Payment verification rejected. Homeowner has been notified.');
      onClose();
    } catch (error) {
      toast.error('Failed to reject payment: ' + error.message);
    } finally {
      setVerifying(false);
    }
  };

  const receiptFiles = paymentRequest.receipt_file_path ? 
    (Array.isArray(paymentRequest.receipt_file_path) ? 
      paymentRequest.receipt_file_path : 
      JSON.parse(paymentRequest.receipt_file_path || '[]')
    ) : [];

  return (
    <div className="receipt-upload-modal">
      <div 
        className="modal-overlay"
        onClick={onClose}
      ></div>
      
      <div className="modal-content">
        <div className="modal-body">
          <div className="modal-header">
            <div>
              <h2>🔍 Payment Receipt Verification</h2>
              <p>Review and verify the homeowner's payment receipt</p>
            </div>
            <div className="header-actions">
              <button
                className="close-btn"
                onClick={onClose}
                disabled={verifying}
              >
                ✕
              </button>
            </div>
          </div>

          <div className="payment-details-section">
            <h3>Payment Request Details</h3>
            <div className="payment-info-grid">
              <div className="info-item">
                <strong>Stage:</strong> {paymentRequest.stage_name}
              </div>
              <div className="info-item">
                <strong>Requested Amount:</strong> {formatCurrency(paymentRequest.requested_amount)}
              </div>
              <div className="info-item">
                <strong>Approved Amount:</strong> {formatCurrency(paymentRequest.approved_amount || paymentRequest.requested_amount)}
              </div>
              <div className="info-item">
                <strong>Status:</strong> {paymentRequest.status}
              </div>
              <div className="info-item">
                <strong>Request Date:</strong> {formatDate(paymentRequest.request_date)}
              </div>
              <div className="info-item">
                <strong>Homeowner:</strong> {paymentRequest.homeowner_name}
              </div>
            </div>
          </div>

          <div className="receipt-details-section">
            <h3>Homeowner's Payment Information</h3>
            <div className="payment-info-grid">
              <div className="info-item">
                <strong>Payment Method:</strong> 
                <span className="payment-method">
                  {getPaymentMethodIcon(paymentRequest.payment_method)} 
                  {paymentRequest.payment_method === 'bank_transfer' && 'Bank Transfer'}
                  {paymentRequest.payment_method === 'upi' && 'UPI Payment'}
                  {paymentRequest.payment_method === 'cash' && 'Cash Payment'}
                  {paymentRequest.payment_method === 'cheque' && 'Cheque Payment'}
                  {!paymentRequest.payment_method && 'Not specified'}
                </span>
              </div>
              
              {paymentRequest.transaction_reference && (
                <div className="info-item">
                  <strong>Transaction Reference:</strong>
                  <span className="transaction-ref">{paymentRequest.transaction_reference}</span>
                </div>
              )}
              
              {paymentRequest.payment_date && (
                <div className="info-item">
                  <strong>Payment Date:</strong>
                  <span className="payment-date">{formatDate(paymentRequest.payment_date)}</span>
                </div>
              )}
              
              <div className="info-item">
                <strong>Verification Status:</strong>
                <span className={`verification-status ${paymentRequest.verification_status}`}>
                  {paymentRequest.verification_status === 'pending' && '⏳ Pending Verification'}
                  {paymentRequest.verification_status === 'verified' && '✅ Verified'}
                  {paymentRequest.verification_status === 'rejected' && '❌ Rejected'}
                </span>
              </div>
            </div>

            {paymentRequest.homeowner_notes && (
              <div className="homeowner-notes">
                <strong>Homeowner Notes:</strong>
                <p>{paymentRequest.homeowner_notes}</p>
              </div>
            )}
          </div>

          {receiptFiles.length > 0 && (
            <div className="receipt-files-section">
              <h3>📄 Uploaded Receipt Files</h3>
              <div className="files-list">
                {receiptFiles.map((file, index) => {
                  const isCorrupted = file.file_size && file.file_size < 1000; // Files smaller than 1KB are likely corrupted
                  const fileSizeMB = file.file_size ? (file.file_size / 1024 / 1024).toFixed(2) : '0.00';
                  
                  return (
                    <div key={index} className="file-item">
                      <div className="file-info">
                        <span className="file-icon">
                          {file.file_type && file.file_type.startsWith('image/') ? '🖼️' : '📄'}
                        </span>
                        <div className="file-details">
                          <span className="file-name">{file.original_name}</span>
                          <span className="file-size">
                            {fileSizeMB} MB
                            {isCorrupted && (
                              <span className="corrupted-warning" style={{
                                color: '#dc3545',
                                fontSize: '12px',
                                marginLeft: '8px',
                                fontWeight: 'bold'
                              }}>
                                ⚠️ File may be corrupted
                              </span>
                            )}
                          </span>
                        </div>
                      </div>
                      
                      {isCorrupted ? (
                        <div className="corrupted-file-actions">
                          <button 
                            className="view-file-btn corrupted"
                            style={{
                              backgroundColor: '#ffc107',
                              color: '#212529',
                              cursor: 'not-allowed'
                            }}
                            disabled
                            title="File appears corrupted - cannot view"
                          >
                            ⚠️ Corrupted
                          </button>
                          <div className="corrupted-message" style={{
                            fontSize: '12px',
                            color: '#dc3545',
                            marginTop: '4px',
                            textAlign: 'center'
                          }}>
                            File is too small ({file.file_size} bytes)
                          </div>
                        </div>
                      ) : (
                        <a 
                          href={`/buildhub/backend/${file.file_path}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="view-file-btn"
                          title="View receipt file"
                          onClick={(e) => {
                            // Add error handling for failed file loads
                            const link = e.target;
                            setTimeout(() => {
                              // Check if the new window/tab loaded successfully
                              // This is a basic check - in a real app you might want more sophisticated error handling
                            }, 1000);
                          }}
                        >
                          👁️ View File
                        </a>
                      )}
                    </div>
                  );
                })}
              </div>
              
              {receiptFiles.some(file => file.file_size && file.file_size < 1000) && (
                <div className="corrupted-files-notice" style={{
                  marginTop: '16px',
                  padding: '12px',
                  backgroundColor: '#fff3cd',
                  border: '1px solid #ffeaa7',
                  borderRadius: '8px',
                  color: '#856404'
                }}>
                  <h4 style={{ margin: '0 0 8px 0', fontSize: '14px' }}>
                    ⚠️ Corrupted Files Detected
                  </h4>
                  <p style={{ margin: '0', fontSize: '13px', lineHeight: '1.4' }}>
                    Some uploaded files appear to be corrupted or incomplete. 
                    Please ask the homeowner to re-upload their payment receipt with clear, complete images.
                  </p>
                </div>
              )}
            </div>
          )}

          <div className="verification-section">
            <h3>Contractor Verification</h3>
            <div className="form-group">
              <label htmlFor="verification-notes">
                Verification Notes:
              </label>
              <textarea
                id="verification-notes"
                value={verificationNotes}
                onChange={(e) => setVerificationNotes(e.target.value)}
                placeholder="Add notes about the payment verification (optional for approval, required for rejection)..."
                rows="4"
              />
            </div>

            <div className="verification-actions">
              <button
                className="btn btn-verify"
                onClick={handleVerify}
                disabled={verifying}
              >
                {verifying ? '⏳ Processing...' : '✅ Verify & Accept Payment'}
              </button>
              <button
                className="btn btn-reject"
                onClick={handleReject}
                disabled={verifying || !verificationNotes.trim()}
              >
                {verifying ? '⏳ Processing...' : '❌ Reject & Request Correction'}
              </button>
              <button
                className="btn btn-cancel"
                onClick={onClose}
                disabled={verifying}
              >
                Cancel
              </button>
            </div>
          </div>

          <div className="verification-info">
            <h4>🔍 Verification Guidelines</h4>
            <ul>
              <li>✅ Verify the transaction reference matches bank records</li>
              <li>✅ Check the payment date is reasonable</li>
              <li>✅ Confirm the payment amount matches the approved amount</li>
              <li>✅ Review receipt images for authenticity</li>
              <li>❌ Reject if information doesn't match or seems suspicious</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PaymentReceiptViewer;