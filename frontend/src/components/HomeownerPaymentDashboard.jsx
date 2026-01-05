import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/HomeownerPaymentDashboard.css';

const HomeownerPaymentDashboard = ({ homeownerId }) => {
  const toast = useToast();
  
  const [paymentRequests, setPaymentRequests] = useState([]);
  const [loading, setLoading] = useState(true);
  const [processingPayment, setProcessingPayment] = useState(null);
  const [selectedRequest, setSelectedRequest] = useState(null);
  const [showResponseModal, setShowResponseModal] = useState(false);
  const [responseForm, setResponseForm] = useState({
    response: '',
    homeowner_notes: '',
    approved_amount: ''
  });

  useEffect(() => {
    loadPaymentRequests();
  }, [homeownerId]);

  const loadPaymentRequests = async () => {
    try {
      setLoading(true);
      const response = await fetch(
        `/buildhub/backend/api/homeowner/get_payment_requests.php?homeowner_id=${homeownerId}`,
        { credentials: 'include' }
      );
      const data = await response.json();
      
      if (data.success) {
        setPaymentRequests(data.data.payment_requests || []);
      } else {
        toast.error('Failed to load payment requests: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading payment requests:', error);
      toast.error('Error loading payment requests');
    } finally {
      setLoading(false);
    }
  };

  const handlePaymentResponse = async (requestId, response, approvedAmount = null, notes = '') => {
    try {
      setProcessingPayment(requestId);
      
      const submitData = {
        request_id: requestId,
        response: response,
        homeowner_notes: notes
      };
      
      if (response === 'approved' && approvedAmount) {
        submitData.approved_amount = approvedAmount;
      }

      const apiResponse = await fetch(
        '/buildhub/backend/api/homeowner/respond_payment_request.php',
        {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(submitData)
        }
      );

      const data = await apiResponse.json();
      
      if (data.success) {
        toast.success(`Payment request ${response} successfully!`);
        loadPaymentRequests(); // Reload to get updated status
        setShowResponseModal(false);
        setSelectedRequest(null);
      } else {
        toast.error('Failed to respond to payment request: ' + data.message);
      }
    } catch (error) {
      console.error('Error responding to payment request:', error);
      toast.error('Error processing payment response');
    } finally {
      setProcessingPayment(null);
    }
  };

  const openResponseModal = (request, response) => {
    setSelectedRequest(request);
    setResponseForm({
      response: response,
      homeowner_notes: '',
      approved_amount: response === 'approved' ? request.requested_amount : ''
    });
    setShowResponseModal(true);
  };

  const handleFormSubmit = (e) => {
    e.preventDefault();
    
    if (responseForm.response === 'approved' && !responseForm.approved_amount) {
      toast.error('Please enter the approved amount');
      return;
    }
    
    handlePaymentResponse(
      selectedRequest.id,
      responseForm.response,
      responseForm.approved_amount,
      responseForm.homeowner_notes
    );
  };

  const getStatusBadgeClass = (status) => {
    switch (status) {
      case 'pending': return 'status-pending';
      case 'approved': return 'status-approved';
      case 'rejected': return 'status-rejected';
      case 'paid': return 'status-paid';
      default: return 'status-unknown';
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'pending': return '⏳';
      case 'approved': return '✅';
      case 'rejected': return '❌';
      case 'paid': return '💰';
      default: return '❓';
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
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading) {
    return (
      <div className="payment-dashboard">
        <div className="loading-spinner">
          <div className="spinner"></div>
          <p>Loading payment requests...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="payment-dashboard">
      <div className="dashboard-header">
        <h2>💰 Payment Requests</h2>
        <p>Review and respond to contractor payment requests for construction stages</p>
      </div>

      {paymentRequests.length === 0 ? (
        <div className="no-requests">
          <div className="no-requests-icon">💳</div>
          <h3>No Payment Requests</h3>
          <p>You don't have any payment requests at the moment.</p>
          <p>Payment requests will appear here when contractors request stage payments.</p>
        </div>
      ) : (
        <div className="payment-requests-grid">
          {paymentRequests.map(request => (
            <div key={request.id} className={`payment-request-card ${getStatusBadgeClass(request.status)}`}>
              <div className="card-header">
                <div className="project-info">
                  <h3>{request.project_name}</h3>
                  <p className="contractor-name">by {request.contractor_name}</p>
                </div>
                <div className={`status-badge ${getStatusBadgeClass(request.status)}`}>
                  {getStatusIcon(request.status)} {request.status.toUpperCase()}
                </div>
              </div>

              <div className="stage-info">
                <div className="stage-name">
                  <strong>🏗️ {request.stage_name} Stage</strong>
                </div>
                <div className="completion-info">
                  <span>Progress: {request.completion_percentage}%</span>
                  <div className="progress-bar">
                    <div 
                      className="progress-fill" 
                      style={{ width: `${request.completion_percentage}%` }}
                    ></div>
                  </div>
                </div>
              </div>

              <div className="payment-details">
                <div className="amount-section">
                  <div className="requested-amount">
                    <span className="label">Requested Amount:</span>
                    <span className="amount">{formatCurrency(request.requested_amount)}</span>
                  </div>
                  {request.approved_amount && request.approved_amount !== request.requested_amount && (
                    <div className="approved-amount">
                      <span className="label">Approved Amount:</span>
                      <span className="amount">{formatCurrency(request.approved_amount)}</span>
                    </div>
                  )}
                </div>

                <div className="work-description">
                  <strong>Work Description:</strong>
                  <p>{request.work_description}</p>
                </div>

                {request.contractor_notes && (
                  <div className="contractor-notes">
                    <strong>Contractor Notes:</strong>
                    <p>{request.contractor_notes}</p>
                  </div>
                )}

                {request.homeowner_notes && (
                  <div className="homeowner-notes">
                    <strong>Your Response:</strong>
                    <p>{request.homeowner_notes}</p>
                  </div>
                )}
              </div>

              <div className="request-dates">
                <div className="date-item">
                  <span className="label">Requested:</span>
                  <span className="date">{formatDate(request.request_date)}</span>
                </div>
                {request.response_date && (
                  <div className="date-item">
                    <span className="label">Responded:</span>
                    <span className="date">{formatDate(request.response_date)}</span>
                  </div>
                )}
              </div>

              {request.status === 'pending' && (
                <div className="action-buttons">
                  <button
                    className="approve-btn"
                    onClick={() => openResponseModal(request, 'approved')}
                    disabled={processingPayment === request.id}
                  >
                    ✅ Approve Payment
                  </button>
                  <button
                    className="reject-btn"
                    onClick={() => openResponseModal(request, 'rejected')}
                    disabled={processingPayment === request.id}
                  >
                    ❌ Reject Request
                  </button>
                </div>
              )}

              {processingPayment === request.id && (
                <div className="processing-overlay">
                  <div className="spinner"></div>
                  <p>Processing response...</p>
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {/* Payment Response Modal */}
      {showResponseModal && selectedRequest && (
        <div className="modal-overlay">
          <div className="modal-container">
            <div className="modal-header">
              <h3>
                {responseForm.response === 'approved' ? '✅ Approve Payment Request' : '❌ Reject Payment Request'}
              </h3>
              <button 
                className="close-btn"
                onClick={() => setShowResponseModal(false)}
              >
                ×
              </button>
            </div>

            <div className="modal-content">
              <div className="request-summary">
                <h4>{selectedRequest.stage_name} Stage Payment</h4>
                <p><strong>Project:</strong> {selectedRequest.project_name}</p>
                <p><strong>Contractor:</strong> {selectedRequest.contractor_name}</p>
                <p><strong>Requested Amount:</strong> {formatCurrency(selectedRequest.requested_amount)}</p>
                <p><strong>Completion:</strong> {selectedRequest.completion_percentage}%</p>
              </div>

              <form onSubmit={handleFormSubmit} className="response-form">
                {responseForm.response === 'approved' && (
                  <div className="form-group">
                    <label htmlFor="approved_amount">Approved Amount *</label>
                    <input
                      type="number"
                      id="approved_amount"
                      value={responseForm.approved_amount}
                      onChange={(e) => setResponseForm(prev => ({ ...prev, approved_amount: e.target.value }))}
                      min="0"
                      max={selectedRequest.requested_amount}
                      step="100"
                      required
                      placeholder="Enter approved amount"
                    />
                    <div className="field-info">
                      Maximum: {formatCurrency(selectedRequest.requested_amount)}
                    </div>
                  </div>
                )}

                <div className="form-group">
                  <label htmlFor="homeowner_notes">
                    {responseForm.response === 'approved' ? 'Approval Notes' : 'Rejection Reason'} *
                  </label>
                  <textarea
                    id="homeowner_notes"
                    value={responseForm.homeowner_notes}
                    onChange={(e) => setResponseForm(prev => ({ ...prev, homeowner_notes: e.target.value }))}
                    placeholder={
                      responseForm.response === 'approved' 
                        ? 'Add any notes about the approval...'
                        : 'Please explain why you are rejecting this request...'
                    }
                    rows="4"
                    required
                    maxLength="1000"
                  />
                  <div className="field-info">
                    {responseForm.homeowner_notes.length}/1000 characters
                  </div>
                </div>

                <div className="modal-actions">
                  <button
                    type="button"
                    className="cancel-btn"
                    onClick={() => setShowResponseModal(false)}
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    className={responseForm.response === 'approved' ? 'approve-btn' : 'reject-btn'}
                    disabled={processingPayment}
                  >
                    {processingPayment ? 'Processing...' : 
                     responseForm.response === 'approved' ? 'Approve Payment' : 'Reject Request'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default HomeownerPaymentDashboard;