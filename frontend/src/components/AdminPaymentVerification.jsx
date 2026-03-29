import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/AdminPaymentVerification.css';

const AdminPaymentVerification = () => {
  const toast = useToast();
  
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedPayment, setSelectedPayment] = useState(null);
  const [showVerificationModal, setShowVerificationModal] = useState(false);
  const [verificationAction, setVerificationAction] = useState('');
  const [adminNotes, setAdminNotes] = useState('');
  const [autoProgressUpdate, setAutoProgressUpdate] = useState(true);
  const [processing, setProcessing] = useState(false);
  const [filter, setFilter] = useState('all'); // all, pending, verified, rejected
  const [sortBy, setSortBy] = useState('priority'); // priority, date, amount
  const [summary, setSummary] = useState({});

  useEffect(() => {
    loadPendingPayments();
  }, []);

  const loadPendingPayments = async () => {
    try {
      setLoading(true);
      const response = await fetch('/buildhub/backend/api/admin/get_pending_payment_verifications.php', {
        credentials: 'include'
      });
      const data = await response.json();
      
      if (data.success) {
        setPayments(data.data.payments || []);
        setSummary(data.data.summary || {});
      } else {
        toast.error('Failed to load payment verifications: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading payment verifications:', error);
      toast.error('Error loading payment verifications');
    } finally {
      setLoading(false);
    }
  };

  const openVerificationModal = (payment, action) => {
    setSelectedPayment(payment);
    setVerificationAction(action);
    setAdminNotes('');
    setAutoProgressUpdate(true);
    setShowVerificationModal(true);
  };

  const closeVerificationModal = () => {
    setShowVerificationModal(false);
    setSelectedPayment(null);
    setVerificationAction('');
    setAdminNotes('');
  };

  const submitVerification = async () => {
    if (!selectedPayment || !verificationAction) return;

    if (verificationAction === 'admin_rejected' && !adminNotes.trim()) {
      toast.error('Please provide a reason for rejecting the payment');
      return;
    }

    try {
      setProcessing(true);
      
      const response = await fetch('/buildhub/backend/api/admin/verify_payment_receipt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          payment_id: selectedPayment.id,
          verification_action: verificationAction,
          admin_notes: adminNotes,
          auto_progress_update: autoProgressUpdate
        })
      });

      const data = await response.json();
      
      if (data.success) {
        toast.success(
          verificationAction === 'admin_approved' ? 
          'Payment verified successfully' + (autoProgressUpdate ? ' and progress updated' : '') :
          'Payment verification rejected'
        );
        
        closeVerificationModal();
        loadPendingPayments(); // Reload the list
      } else {
        toast.error('Verification failed: ' + data.message);
      }
    } catch (error) {
      console.error('Error submitting verification:', error);
      toast.error('Error submitting verification');
    } finally {
      setProcessing(false);
    }
  };

  const formatCurrency = (amount) => {
    return `₹${parseFloat(amount || 0).toLocaleString('en-IN')}`;
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getStatusBadge = (payment) => {
    const { verification_status, admin_verified } = payment;
    
    if (admin_verified) {
      if (verification_status === 'admin_approved') {
        return <span className="status-badge admin-approved">✅ Admin Approved</span>;
      } else if (verification_status === 'admin_rejected') {
        return <span className="status-badge admin-rejected">❌ Admin Rejected</span>;
      }
    }
    
    if (verification_status === 'verified') {
      return <span className="status-badge contractor-verified">🔍 Contractor Verified</span>;
    }
    
    return <span className="status-badge pending">⏳ Pending Verification</span>;
  };

  const getPriorityBadge = (score) => {
    if (score >= 7) return <span className="priority-badge high">🔴 High Priority</span>;
    if (score >= 4) return <span className="priority-badge medium">🟡 Medium Priority</span>;
    return <span className="priority-badge low">🟢 Low Priority</span>;
  };

  const getFilteredAndSortedPayments = () => {
    let filtered = payments;
    
    // Apply filter
    if (filter === 'pending') {
      filtered = payments.filter(p => p.verification_status === 'pending');
    } else if (filter === 'verified') {
      filtered = payments.filter(p => p.admin_verified);
    } else if (filter === 'contractor_verified') {
      filtered = payments.filter(p => p.verification_status === 'verified' && !p.admin_verified);
    }
    
    // Apply sorting
    filtered.sort((a, b) => {
      switch (sortBy) {
        case 'priority':
          return b.priority_score - a.priority_score;
        case 'date':
          return new Date(b.request_date) - new Date(a.request_date);
        case 'amount':
          return (b.approved_amount || b.requested_amount) - (a.approved_amount || a.requested_amount);
        default:
          return 0;
      }
    });
    
    return filtered;
  };

  if (loading) {
    return (
      <div className="admin-payment-verification">
        <div className="loading-state">
          <div className="loading-spinner">🔄</div>
          <p>Loading payment verifications...</p>
        </div>
      </div>
    );
  }

  const filteredPayments = getFilteredAndSortedPayments();

  return (
    <div className="admin-payment-verification">
      <div className="verification-header">
        <h2>🔍 Payment Verification Dashboard</h2>
        <p>Review and verify payment receipts submitted by homeowners</p>
        
        {/* Summary Cards */}
        <div className="summary-cards">
          <div className="summary-card">
            <div className="summary-value">{summary.total_payments || 0}</div>
            <div className="summary-label">Total Payments</div>
          </div>
          <div className="summary-card pending">
            <div className="summary-value">{summary.pending_verification || 0}</div>
            <div className="summary-label">Pending Verification</div>
          </div>
          <div className="summary-card verified">
            <div className="summary-value">{summary.verified_payments || 0}</div>
            <div className="summary-label">Verified</div>
          </div>
          <div className="summary-card amount">
            <div className="summary-value">{formatCurrency(summary.total_amount)}</div>
            <div className="summary-label">Total Amount</div>
          </div>
        </div>
      </div>

      {/* Filters and Controls */}
      <div className="verification-controls">
        <div className="filter-controls">
          <label>Filter:</label>
          <select value={filter} onChange={(e) => setFilter(e.target.value)}>
            <option value="all">All Payments</option>
            <option value="pending">Pending Admin Verification</option>
            <option value="contractor_verified">Contractor Verified</option>
            <option value="verified">Admin Verified</option>
          </select>
        </div>
        
        <div className="sort-controls">
          <label>Sort by:</label>
          <select value={sortBy} onChange={(e) => setSortBy(e.target.value)}>
            <option value="priority">Priority Score</option>
            <option value="date">Request Date</option>
            <option value="amount">Payment Amount</option>
          </select>
        </div>
        
        <button className="refresh-btn" onClick={loadPendingPayments}>
          🔄 Refresh
        </button>
      </div>

      {/* Payment List */}
      <div className="payments-list">
        {filteredPayments.length === 0 ? (
          <div className="no-payments">
            <div className="no-payments-icon">📝</div>
            <h3>No Payments Found</h3>
            <p>No payment verifications match the current filter criteria.</p>
          </div>
        ) : (
          filteredPayments.map((payment) => (
            <div key={payment.id} className="payment-card">
              <div className="payment-header">
                <div className="payment-title">
                  <h3>🏗️ {payment.project_name}</h3>
                  <span className="stage-name">{payment.stage_name} Stage</span>
                </div>
                <div className="payment-status">
                  {getStatusBadge(payment)}
                  {getPriorityBadge(payment.priority_score)}
                </div>
              </div>

              <div className="payment-details">
                <div className="detail-row">
                  <div className="detail-item">
                    <span className="detail-label">Homeowner:</span>
                    <span className="detail-value">{payment.homeowner_name}</span>
                  </div>
                  <div className="detail-item">
                    <span className="detail-label">Contractor:</span>
                    <span className="detail-value">{payment.contractor_name}</span>
                  </div>
                </div>

                <div className="detail-row">
                  <div className="detail-item">
                    <span className="detail-label">Amount:</span>
                    <span className="detail-value amount">
                      {formatCurrency(payment.approved_amount || payment.requested_amount)}
                    </span>
                  </div>
                  <div className="detail-item">
                    <span className="detail-label">Completion:</span>
                    <span className="detail-value">{payment.completion_percentage}%</span>
                  </div>
                </div>

                <div className="detail-row">
                  <div className="detail-item">
                    <span className="detail-label">Payment Method:</span>
                    <span className="detail-value">
                      {payment.payment_method === 'bank_transfer' && '🏦 Bank Transfer'}
                      {payment.payment_method === 'upi' && '📱 UPI Payment'}
                      {payment.payment_method === 'cash' && '💵 Cash Payment'}
                      {payment.payment_method === 'cheque' && '📝 Cheque Payment'}
                      {!payment.payment_method && '💳 Not specified'}
                    </span>
                  </div>
                  <div className="detail-item">
                    <span className="detail-label">Transaction Ref:</span>
                    <span className="detail-value">{payment.transaction_reference || 'N/A'}</span>
                  </div>
                </div>

                <div className="detail-row">
                  <div className="detail-item">
                    <span className="detail-label">Request Date:</span>
                    <span className="detail-value">{formatDate(payment.request_date)}</span>
                  </div>
                  <div className="detail-item">
                    <span className="detail-label">Days Pending:</span>
                    <span className="detail-value">{payment.days_pending} days</span>
                  </div>
                </div>

                {/* Receipt Files */}
                {payment.receipt_files && payment.receipt_files.length > 0 && (
                  <div className="receipt-files">
                    <span className="detail-label">Receipt Files:</span>
                    <div className="files-list">
                      {payment.receipt_files.map((file, index) => (
                        <div key={index} className="file-item">
                          <span className="file-icon">
                            {file.file_type && file.file_type.startsWith('image/') ? '🖼️' : '📄'}
                          </span>
                          <span className="file-name">{file.original_name}</span>
                          <a 
                            href={`/buildhub/backend/${file.file_path}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="view-file-btn"
                          >
                            👁️ View
                          </a>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Work Description */}
                {payment.work_description && (
                  <div className="work-description">
                    <span className="detail-label">Work Description:</span>
                    <p className="detail-value">{payment.work_description}</p>
                  </div>
                )}

                {/* Admin Notes */}
                {payment.admin_notes && (
                  <div className="admin-notes">
                    <span className="detail-label">Admin Notes:</span>
                    <p className="detail-value">{payment.admin_notes}</p>
                    <small>Verified by {payment.admin_verified_by} on {formatDate(payment.admin_verified_at)}</small>
                  </div>
                )}
              </div>

              {/* Action Buttons */}
              {!payment.admin_verified && (
                <div className="payment-actions">
                  <button 
                    className="btn btn-approve"
                    onClick={() => openVerificationModal(payment, 'admin_approved')}
                  >
                    ✅ Approve Payment
                  </button>
                  <button 
                    className="btn btn-reject"
                    onClick={() => openVerificationModal(payment, 'admin_rejected')}
                  >
                    ❌ Reject Payment
                  </button>
                </div>
              )}
            </div>
          ))
        )}
      </div>

      {/* Verification Modal */}
      {showVerificationModal && selectedPayment && (
        <div className="verification-modal-overlay">
          <div className="verification-modal">
            <div className="modal-header">
              <h3>
                {verificationAction === 'admin_approved' ? '✅ Approve Payment' : '❌ Reject Payment'}
              </h3>
              <button className="close-btn" onClick={closeVerificationModal}>×</button>
            </div>

            <div className="modal-body">
              <div className="payment-summary">
                <h4>Payment Details</h4>
                <p><strong>Project:</strong> {selectedPayment.project_name}</p>
                <p><strong>Stage:</strong> {selectedPayment.stage_name}</p>
                <p><strong>Amount:</strong> {formatCurrency(selectedPayment.approved_amount || selectedPayment.requested_amount)}</p>
                <p><strong>Homeowner:</strong> {selectedPayment.homeowner_name}</p>
                <p><strong>Contractor:</strong> {selectedPayment.contractor_name}</p>
              </div>

              <div className="form-group">
                <label htmlFor="adminNotes">
                  {verificationAction === 'admin_approved' ? 'Approval Notes (Optional):' : 'Rejection Reason (Required):'}
                </label>
                <textarea
                  id="adminNotes"
                  value={adminNotes}
                  onChange={(e) => setAdminNotes(e.target.value)}
                  placeholder={
                    verificationAction === 'admin_approved' 
                      ? 'Add any notes about the approval...'
                      : 'Please provide a reason for rejecting this payment...'
                  }
                  rows="4"
                  required={verificationAction === 'admin_rejected'}
                />
              </div>

              {verificationAction === 'admin_approved' && (
                <div className="form-group">
                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      checked={autoProgressUpdate}
                      onChange={(e) => setAutoProgressUpdate(e.target.checked)}
                    />
                    Automatically update construction progress to {selectedPayment.completion_percentage}%
                  </label>
                  <small className="help-text">
                    This will mark the {selectedPayment.stage_name} stage as completed and update the project progress.
                  </small>
                </div>
              )}
            </div>

            <div className="modal-actions">
              <button className="btn btn-secondary" onClick={closeVerificationModal}>
                Cancel
              </button>
              <button 
                className={`btn ${verificationAction === 'admin_approved' ? 'btn-approve' : 'btn-reject'}`}
                onClick={submitVerification}
                disabled={processing}
              >
                {processing ? 'Processing...' : 
                 verificationAction === 'admin_approved' ? '✅ Approve Payment' : '❌ Reject Payment'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default AdminPaymentVerification;