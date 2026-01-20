import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/ContractorPaymentVerification.css';

const ContractorPaymentVerification = ({ contractorId }) => {
  const toast = useToast();
  
  const [pendingPayments, setPendingPayments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedPayment, setSelectedPayment] = useState(null);
  const [showVerificationModal, setShowVerificationModal] = useState(false);
  const [verificationAction, setVerificationAction] = useState('');
  const [verificationNotes, setVerificationNotes] = useState('');
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    loadPendingPayments();
  }, [contractorId]);

  const loadPendingPayments = async () => {
    try {
      setLoading(true);
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_pending_payment_verifications.php?contractor_id=${contractorId}`,
        { credentials: 'include' }
      );
      const data = await response.json();
      
      if (data.success) {
        setPendingPayments(data.data.payments || []);
      } else {
        toast.error('Failed to load pending payments: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading pending payments:', error);
      toast.error('Error loading pending payments');
    } finally {
      setLoading(false);
    }
  };

  const openVerificationModal = (payment, action) => {
    setSelectedPayment(payment);
    setVerificationAction(action);
    setVerificationNotes('');
    setShowVerificationModal(true);
  };

  const closeVerificationModal = () => {
    setShowVerificationModal(false);
    setSelectedPayment(null);
    setVerificationAction('');
    setVerificationNotes('');
  };

  const submitVerification = async () => {
    if (!selectedPayment || !verificationAction) return;

    if (verificationAction === 'rejected' && !verificationNotes.trim()) {
      toast.error('Please provide a reason for rejecting the payment');
      return;
    }

    try {
      setProcessing(true);
      
      const response = await fetch('/buildhub/backend/api/contractor/verify_payment_receipt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          payment_id: selectedPayment.id,
          verification_action: verificationAction,
          contractor_notes: verificationNotes
        })
      });

      const data = await response.json();
      
      if (data.success) {
        toast.success(`Payment ${verificationAction} successfully!`);
        loadPendingPayments(); // Reload the list
        closeVerificationModal();
      } else {
        toast.error('Failed to verify payment: ' + data.message);
      }
    } catch (error) {
      console.error('Error verifying payment:', error);
      toast.error('Error verifying payment');
    } finally {
      setProcessing(false);
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

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const getPaymentMethodIcon = (method) => {
    switch (method) {
      case 'bank_transfer': return '🏦';
      case 'upi': return '📱';
      case 'cash': return '💵';
      case 'cheque': return '📝';
      default: return '💳';
    }
  };

  const getPaymentMethodName = (method) => {
    switch (method) {
      case 'bank_transfer': return 'Bank Transfer';
      case 'upi': return 'UPI Payment';
      case 'cash': return 'Cash Payment';
      case 'cheque': return 'Cheque Payment';
      default: return 'Payment';
    }
  };

  const viewReceiptFile = (filePath) => {
    const fullPath = `/buildhub/backend/${filePath}`;
    window.open(fullPath, '_blank');
  };

  if (loading) {
    return (
      <div className="verification-dashboard">
        <div className="loading-spinner">
          <div className="spinner"></div>
          <p>Loading pending verifications...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="verification-dashboard">
      <div className="dashboard-header">
        <h2>🔍 Payment Verification</h2>
        <p>Review and verify payment receipts from homeowners</p>
      </div>

      {pendingPayments.length === 0 ? (
        <div className="no-payments">
          <div className="no-payments-icon">✅</div>
          <h3>No Pending Verifications</h3>
          <p>All payment receipts have been verified.</p>
          <p>New receipts will appear here when homeowners upload them.</p>
        </div>
      ) : (
        <div className="payments-grid">
          {pendingPayments.map(payment => (
            <div key={payment.id} className="payment-verification-card">
              <div className="card-header">
                <div className="payment-info">
                  <h3>{getPaymentMethodIcon(payment.payment_method)} {getPaymentMethodName(payment.payment_method)}</h3>
                  <p className="homeowner-name">from {payment.homeowner_name}</p>
                </div>
                <div className="amount-badge">
                  {formatCurrency(payment.amount)}
                </div>
              </div>

              <div className="payment-details">
                <div className="detail-row">
                  <span className="label">Transaction Reference:</span>
                  <span className="value">{payment.transaction_reference}</span>
                </div>
                <div className="detail-row">
                  <span className="label">Payment Date:</span>
                  <span className="value">{formatDate(payment.payment_date)}</span>
                </div>
                <div className="detail-row">
                  <span className="label">Uploaded:</span>
                  <span className="value">{formatDate(payment.updated_at)}</span>
                </div>
                {payment.homeowner_notes && (
                  <div className="detail-row">
                    <span className="label">Notes:</span>
                    <span className="value">{payment.homeowner_notes}</span>
                  </div>
                )}
              </div>

              {payment.receipt_files && payment.receipt_files.length > 0 && (
                <div className="receipt-files">
                  <h4>📎 Uploaded Receipts ({payment.receipt_files.length})</h4>
                  <div className="files-list">
                    {payment.receipt_files.map((file, index) => (
                      <div key={index} className="file-item">
                        <div className="file-info">
                          <div className="file-icon">
                            {file.file_type?.startsWith('image/') ? '🖼️' : '📄'}
                          </div>
                          <div className="file-details">
                            <div className="file-name">{file.original_name}</div>
                            <div className="file-size">
                              {file.file_size ? Math.round(file.file_size / 1024) + ' KB' : 'Unknown size'}
                            </div>
                          </div>
                        </div>
                        <button
                          className="view-file-btn"
                          onClick={() => viewReceiptFile(file.file_path)}
                        >
                          👁️ View
                        </button>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              <div className="verification-actions">
                <button
                  className="approve-btn"
                  onClick={() => openVerificationModal(payment, 'approved')}
                >
                  ✅ Approve Payment
                </button>
                <button
                  className="reject-btn"
                  onClick={() => openVerificationModal(payment, 'rejected')}
                >
                  ❌ Reject Payment
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Verification Modal */}
      {showVerificationModal && selectedPayment && (
        <div className="verification-modal">
          <div className="modal-overlay">
            <div className="modal-content">
              <div className="modal-header">
                <h2>
                  {verificationAction === 'approved' ? '✅ Approve Payment' : '❌ Reject Payment'}
                </h2>
                <button className="close-btn" onClick={closeVerificationModal}>×</button>
              </div>

              <div className="modal-body">
                <div className="payment-summary">
                  <h3>Payment Summary</h3>
                  <div className="summary-details">
                    <p><strong>Amount:</strong> {formatCurrency(selectedPayment.amount)}</p>
                    <p><strong>Method:</strong> {getPaymentMethodName(selectedPayment.payment_method)}</p>
                    <p><strong>Reference:</strong> {selectedPayment.transaction_reference}</p>
                    <p><strong>Date:</strong> {formatDate(selectedPayment.payment_date)}</p>
                    <p><strong>Homeowner:</strong> {selectedPayment.homeowner_name}</p>
                  </div>
                </div>

                <div className="verification-form">
                  <div className="form-group">
                    <label htmlFor="verification_notes">
                      {verificationAction === 'approved' ? 'Approval Notes' : 'Rejection Reason'} 
                      {verificationAction === 'rejected' && ' *'}
                    </label>
                    <textarea
                      id="verification_notes"
                      value={verificationNotes}
                      onChange={(e) => setVerificationNotes(e.target.value)}
                      placeholder={
                        verificationAction === 'approved' 
                          ? 'Add any notes about the approval (optional)...'
                          : 'Please explain why you are rejecting this payment...'
                      }
                      rows="4"
                      required={verificationAction === 'rejected'}
                      maxLength="1000"
                    />
                    <div className="field-info">
                      {verificationNotes.length}/1000 characters
                    </div>
                  </div>

                  {verificationAction === 'approved' && (
                    <div className="approval-info">
                      <h4>✅ Approving this payment will:</h4>
                      <ul>
                        <li>Mark the payment as "Completed"</li>
                        <li>Notify the homeowner of successful verification</li>
                        <li>Update the payment status in the system</li>
                        <li>Complete the payment verification process</li>
                      </ul>
                    </div>
                  )}

                  {verificationAction === 'rejected' && (
                    <div className="rejection-info">
                      <h4>❌ Rejecting this payment will:</h4>
                      <ul>
                        <li>Mark the payment as "Failed"</li>
                        <li>Notify the homeowner with your rejection reason</li>
                        <li>Allow the homeowner to re-upload correct receipt</li>
                        <li>Require homeowner to make payment again if needed</li>
                      </ul>
                    </div>
                  )}
                </div>
              </div>

              <div className="modal-actions">
                <button
                  className="secondary-btn"
                  onClick={closeVerificationModal}
                  disabled={processing}
                >
                  Cancel
                </button>
                <button
                  className={verificationAction === 'approved' ? 'approve-btn' : 'reject-btn'}
                  onClick={submitVerification}
                  disabled={processing || (verificationAction === 'rejected' && !verificationNotes.trim())}
                >
                  {processing ? 'Processing...' : 
                   verificationAction === 'approved' ? 'Approve Payment' : 'Reject Payment'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ContractorPaymentVerification;