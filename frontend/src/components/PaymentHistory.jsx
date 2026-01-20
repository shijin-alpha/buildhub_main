import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import ProjectInfoCard from './ProjectInfoCard.jsx';
import '../styles/PaymentHistory.css';

const PaymentHistory = ({ contractorId }) => {
  const toast = useToast();
  
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState('');
  const [paymentHistory, setPaymentHistory] = useState([]);
  const [loading, setLoading] = useState(false);
  const [loadingProjects, setLoadingProjects] = useState(true);
  const [verifyingPayment, setVerifyingPayment] = useState(null);
  const [showVerifyModal, setShowVerifyModal] = useState(false);
  const [selectedPaymentForVerify, setSelectedPaymentForVerify] = useState(null);
  const [verificationNotes, setVerificationNotes] = useState('');
  const [summary, setSummary] = useState({
    total_requests: 0,
    total_requested: 0,
    total_approved: 0,
    total_paid: 0,
    pending_count: 0,
    approved_count: 0,
    rejected_count: 0
  });

  // Load contractor's projects on component mount
  useEffect(() => {
    loadContractorProjects();
  }, [contractorId]);

  // Load payment history when project is selected
  useEffect(() => {
    if (selectedProject) {
      loadPaymentHistory();
    }
  }, [selectedProject]);

  const loadContractorProjects = async () => {
    try {
      setLoadingProjects(true);
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id=${contractorId}`,
        { credentials: 'include' }
      );
      
      const data = await response.json();
      
      if (data.success) {
        const projects = data.data.projects || [];
        setProjects(projects);
        
        // Auto-select first project if available
        if (projects.length > 0) {
          setSelectedProject(projects[0].id);
        }
      } else {
        toast.error('Failed to load projects: ' + (data.message || 'Unknown error'));
        setProjects([]);
      }
    } catch (error) {
      console.error('Error loading projects:', error);
      toast.error('Failed to load projects. Please try again.');
      setProjects([]);
    } finally {
      setLoadingProjects(false);
    }
  };

  const loadPaymentHistory = async () => {
    if (!selectedProject) return;
    
    try {
      setLoading(true);
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_payment_history.php?project_id=${selectedProject}`,
        { credentials: 'include' }
      );
      
      const data = await response.json();
      
      if (data.success) {
        setPaymentHistory(data.data.payment_requests || []);
        setSummary(data.data.summary || {});
      } else {
        toast.error('Failed to load payment history: ' + (data.message || 'Unknown error'));
        setPaymentHistory([]);
        setSummary({});
      }
    } catch (error) {
      console.error('Error loading payment history:', error);
      toast.error('Failed to load payment history. Please try again.');
      setPaymentHistory([]);
      setSummary({});
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      pending: { color: '#ffc107', bg: '#fff3cd', text: '⏳ Pending Review' },
      approved: { color: '#28a745', bg: '#d4edda', text: '✅ Approved' },
      rejected: { color: '#dc3545', bg: '#f8d7da', text: '❌ Rejected' },
      paid: { color: '#17a2b8', bg: '#d1ecf1', text: '💰 Paid' }
    };
    
    const config = statusConfig[status] || statusConfig.pending;
    
    return (
      <span 
        className="status-badge"
        style={{
          backgroundColor: config.bg,
          color: config.color,
          padding: '4px 12px',
          borderRadius: '20px',
          fontSize: '12px',
          fontWeight: '600',
          border: `1px solid ${config.color}20`
        }}
      >
        {config.text}
      </span>
    );
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

  const handleUploadReceipt = (request) => {
    // Create a modal or redirect to upload receipt page
    toast.info(`Upload receipt for ${request.stage_name} stage payment of ${formatCurrency(request.approved_amount || request.requested_amount)}`);
    
    // You can implement one of these approaches:
    // 1. Open a modal for receipt upload
    // 2. Navigate to a dedicated upload page
    // 3. Show an inline upload form
    
    // For now, we'll show a simple alert - you can replace this with your preferred implementation
    const uploadUrl = `/buildhub/upload-receipt?payment_id=${request.id}&stage=${request.stage_name}&amount=${request.approved_amount || request.requested_amount}`;
    
    // Option 1: Open in new tab/window
    window.open(uploadUrl, '_blank');
    
    // Option 2: Navigate in same window (uncomment if preferred)
    // window.location.href = uploadUrl;
    
    // Option 3: You could also trigger a modal here
    // setShowUploadModal(true);
    // setSelectedPaymentForUpload(request);
  };

  const handleVerifyPayment = async (paymentId, verificationStatus) => {
    try {
      setVerifyingPayment(paymentId);
      
      console.log('Verifying payment:', { paymentId, verificationStatus, notes: verificationNotes });
      
      const response = await fetch('/buildhub/backend/api/contractor/verify_payment_receipt.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify({
          payment_id: paymentId,
          verification_status: verificationStatus,
          verification_notes: verificationNotes
        })
      });
      
      console.log('Response status:', response.status);
      console.log('Response headers:', Object.fromEntries(response.headers.entries()));
      
      // Check if response is JSON
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        const text = await response.text();
        console.error('Non-JSON response:', text);
        throw new Error('Server returned an error. Please check if you are logged in and try again.');
      }
      
      const data = await response.json();
      console.log('Response data:', data);
      
      if (data.success) {
        toast.success(data.message);
        // Reload payment history to show updated status
        await loadPaymentHistory();
        // Close modal and reset
        setShowVerifyModal(false);
        setSelectedPaymentForVerify(null);
        setVerificationNotes('');
      } else {
        console.error('Verification failed:', data.message);
        toast.error(data.message || 'Failed to verify payment');
      }
    } catch (error) {
      console.error('Error verifying payment:', error);
      toast.error(error.message || 'Failed to verify payment. Please try again.');
    } finally {
      setVerifyingPayment(null);
    }
  };

  const openVerifyModal = (request, status) => {
    setSelectedPaymentForVerify({ ...request, verificationStatus: status });
    setVerificationNotes('');
    setShowVerifyModal(true);
  };

  const selectedProjectInfo = projects.find(p => p.id == selectedProject);

  if (loadingProjects) {
    return (
      <div className="payment-history">
        <div className="loading-projects">
          <div className="loading-spinner">🔄</div>
          <p>Loading your projects...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="payment-history">
      <div className="payment-history-header">
        <h3>💰 Payment Request History</h3>
        <p>Track all your payment requests and homeowner responses</p>
        
        {/* Project Selection */}
        <div className="project-selection">
          <label htmlFor="project-select">Select Project:</label>
          <select 
            id="project-select"
            value={selectedProject}
            onChange={(e) => setSelectedProject(e.target.value)}
            className="project-select"
          >
            <option value="">Choose a project...</option>
            {projects.map(project => (
              <option key={project.id} value={project.id}>
                {project.project_name} - {project.homeowner_name}
                {project.estimate_cost ? ` (₹${project.estimate_cost.toLocaleString()})` : ''}
              </option>
            ))}
          </select>
        </div>

        {/* Project Info */}
        {selectedProjectInfo && (
          <ProjectInfoCard 
            project={selectedProjectInfo}
            isSelected={true}
          />
        )}
      </div>

      {/* Payment Summary */}
      {selectedProject && Object.keys(summary).length > 0 && (
        <div className="payment-summary">
          <h4>📊 Payment Summary</h4>
          <div className="summary-grid">
            <div className="summary-card">
              <div className="summary-value">{summary.total_requests || 0}</div>
              <div className="summary-label">Total Requests</div>
            </div>
            <div className="summary-card">
              <div className="summary-value">{formatCurrency(summary.total_requested)}</div>
              <div className="summary-label">Total Requested</div>
            </div>
            <div className="summary-card">
              <div className="summary-value">{formatCurrency(summary.total_approved)}</div>
              <div className="summary-label">Total Approved</div>
            </div>
            <div className="summary-card">
              <div className="summary-value">{formatCurrency(summary.total_paid)}</div>
              <div className="summary-label">Total Paid</div>
            </div>
          </div>
          
          <div className="status-summary">
            <div className="status-item">
              <span className="status-count pending">{summary.pending_count || 0}</span>
              <span className="status-text">Pending</span>
            </div>
            <div className="status-item">
              <span className="status-count approved">{summary.approved_count || 0}</span>
              <span className="status-text">Approved</span>
            </div>
            <div className="status-item">
              <span className="status-count rejected">{summary.rejected_count || 0}</span>
              <span className="status-text">Rejected</span>
            </div>
          </div>
        </div>
      )}

      {/* Payment History List */}
      {selectedProject && (
        <div className="payment-history-list">
          <div className="list-header">
            <h4>📋 Payment Request History</h4>
            <button 
              onClick={loadPaymentHistory}
              className="refresh-btn"
              disabled={loading}
            >
              {loading ? '🔄' : '🔄'} Refresh
            </button>
          </div>

          {loading ? (
            <div className="loading-history">
              <div className="loading-spinner">🔄</div>
              <p>Loading payment history...</p>
            </div>
          ) : paymentHistory.length === 0 ? (
            <div className="no-history">
              <div className="no-history-icon">📝</div>
              <h4>No Payment Requests Yet</h4>
              <p>You haven't submitted any payment requests for this project yet.</p>
              <p>Use the "Request Payment" tab to submit your first payment request.</p>
            </div>
          ) : (
            <div className="history-items">
              {paymentHistory.map((request, index) => (
                <div key={request.id} className="history-item">
                  <div className="history-item-header">
                    <div className="stage-info">
                      <h5 className="stage-name">🏗️ {request.stage_name} Stage</h5>
                      <span className="completion-percentage">
                        {request.completion_percentage}% Complete
                      </span>
                    </div>
                    <div className="status-info">
                      {getStatusBadge(request.status)}
                      <span className="request-date">
                        Requested: {formatDate(request.request_date)}
                      </span>
                    </div>
                  </div>

                  <div className="history-item-content">
                    <div className="payment-amounts">
                      <div className="amount-item">
                        <span className="amount-label">Requested:</span>
                        <span className="amount-value requested">
                          {formatCurrency(request.requested_amount)}
                        </span>
                      </div>
                      {request.approved_amount && (
                        <div className="amount-item">
                          <span className="amount-label">Approved:</span>
                          <span className="amount-value approved">
                            {formatCurrency(request.approved_amount)}
                          </span>
                        </div>
                      )}
                      {request.status === 'paid' && (
                        <div className="amount-item">
                          <span className="amount-label">Paid:</span>
                          <span className="amount-value paid">
                            {formatCurrency(request.approved_amount || request.requested_amount)}
                          </span>
                        </div>
                      )}
                    </div>

                    <div className="work-description">
                      <h6>Work Description:</h6>
                      <p>{request.work_description}</p>
                    </div>

                    {request.contractor_notes && (
                      <div className="contractor-notes">
                        <h6>Your Notes:</h6>
                        <p>{request.contractor_notes}</p>
                      </div>
                    )}

                    {/* Homeowner Response */}
                    {request.status !== 'pending' && (
                      <div className="homeowner-response">
                        <h6>🏠 Homeowner Response:</h6>
                        <div className="response-details">
                          <div className="response-status">
                            <strong>Decision:</strong> {getStatusBadge(request.status)}
                            {request.response_date && (
                              <span className="response-date">
                                on {formatDate(request.response_date)}
                              </span>
                            )}
                          </div>
                          
                          {request.homeowner_notes && (
                            <div className="response-notes">
                              <strong>Homeowner Notes:</strong>
                              <p className="homeowner-notes-text">{request.homeowner_notes}</p>
                            </div>
                          )}

                          {request.status === 'rejected' && (
                            <div className="rejection-reason">
                              <strong>Reason for Rejection:</strong>
                              <p className="rejection-text">
                                {request.homeowner_notes || 'No specific reason provided'}
                              </p>
                            </div>
                          )}

                          {request.status === 'approved' && request.approved_amount !== request.requested_amount && (
                            <div className="amount-adjustment">
                              <strong>Amount Adjustment:</strong>
                              <p className="adjustment-text">
                                Approved amount differs from requested amount. 
                                {request.homeowner_notes && ` Reason: ${request.homeowner_notes}`}
                              </p>
                            </div>
                          )}
                        </div>
                      </div>
                    )}

                    {/* Receipt Information */}
                    {(request.status === 'approved' || request.status === 'paid') && request.receipt_file_path && (
                      <div className="receipt-information">
                        <h6>📄 Payment Receipt Information:</h6>
                        <div className="receipt-details">
                          <div className="receipt-summary">
                            <div className="receipt-item">
                              <strong>Payment Method:</strong>
                              <span className="payment-method">
                                {request.payment_method === 'bank_transfer' && '🏦 Bank Transfer'}
                                {request.payment_method === 'upi' && '📱 UPI Payment'}
                                {request.payment_method === 'cash' && '💵 Cash Payment'}
                                {request.payment_method === 'cheque' && '📝 Cheque Payment'}
                                {request.payment_method === 'other' && '💳 Other'}
                                {!request.payment_method && '💳 Not specified'}
                              </span>
                            </div>
                            
                            {request.transaction_reference && (
                              <div className="receipt-item">
                                <strong>Transaction Reference:</strong>
                                <span className="transaction-ref">{request.transaction_reference}</span>
                              </div>
                            )}
                            
                            {request.payment_date && (
                              <div className="receipt-item">
                                <strong>Payment Date:</strong>
                                <span className="payment-date">
                                  {new Date(request.payment_date).toLocaleDateString('en-IN')}
                                </span>
                              </div>
                            )}
                            
                            {request.verification_status && (
                              <div className="receipt-item">
                                <strong>Verification Status:</strong>
                                <span className={`verification-status ${request.verification_status}`}>
                                  {request.verification_status === 'pending' && '⏳ Pending Verification'}
                                  {request.verification_status === 'verified' && '✅ Verified'}
                                  {request.verification_status === 'rejected' && '❌ Rejected'}
                                </span>
                              </div>
                            )}
                          </div>

                          {/* Receipt Files */}
                          {request.receipt_file_path && request.receipt_file_path.length > 0 && (
                            <div className="receipt-files">
                              <strong>Uploaded Files:</strong>
                              <div className="files-list">
                                {request.receipt_file_path.map((file, fileIndex) => (
                                  <div key={fileIndex} className="file-item">
                                    <span className="file-icon">
                                      {file.file_type && file.file_type.startsWith('image/') ? '🖼️' : '📄'}
                                    </span>
                                    <div className="file-details">
                                      <span className="file-name">{file.original_name}</span>
                                      <span className="file-size">
                                        {file.file_size ? `${(file.file_size / 1024 / 1024).toFixed(2)} MB` : ''}
                                      </span>
                                    </div>
                                    <a 
                                      href={`/buildhub/backend/${file.file_path}`}
                                      target="_blank"
                                      rel="noopener noreferrer"
                                      className="view-file-btn"
                                      title="View file"
                                    >
                                      👁️ View
                                    </a>
                                  </div>
                                ))}
                              </div>
                            </div>
                          )}

                          {/* Verification Notes */}
                          {request.verification_notes && (
                            <div className="verification-notes">
                              <strong>Verification Notes:</strong>
                              <p className="verification-notes-text">{request.verification_notes}</p>
                            </div>
                          )}

                          {request.verified_at && (
                            <div className="verification-timestamp">
                              <small>
                                Verified on {formatDate(request.verified_at)}
                              </small>
                            </div>
                          )}

                          {/* Verification Actions for Pending Receipts */}
                          {request.verification_status === 'pending' && (
                            <div className="verification-actions">
                              <button
                                className="btn btn-verify"
                                onClick={() => openVerifyModal(request, 'verified')}
                                disabled={verifyingPayment === request.id}
                              >
                                ✅ Verify Payment
                              </button>
                              <button
                                className="btn btn-reject"
                                onClick={() => openVerifyModal(request, 'rejected')}
                                disabled={verifyingPayment === request.id}
                              >
                                ❌ Request Correction
                              </button>
                            </div>
                          )}
                        </div>
                      </div>
                    )}

                    {/* Receipt Upload Status for Approved Payments */}
                    {request.status === 'approved' && !request.receipt_file_path && (
                      <div className="receipt-pending">
                        <div className="receipt-pending-message">
                          <span className="pending-icon">⏳</span>
                          <div className="pending-text">
                            <strong>Awaiting Payment Receipt</strong>
                            <p>Homeowner needs to upload payment receipt for verification</p>
                          </div>
                        </div>
                        <div className="receipt-upload-action">
                          <button 
                            className="btn btn-upload-receipt"
                            onClick={() => handleUploadReceipt(request)}
                          >
                            📤 Upload Receipt
                          </button>
                        </div>
                      </div>
                    )}
                  </div>

                  <div className="history-item-footer">
                    <div className="timeline-info">
                      <span className="timeline-item">
                        📅 Requested: {formatDate(request.request_date)}
                      </span>
                      {request.response_date && (
                        <span className="timeline-item">
                          📋 Responded: {formatDate(request.response_date)}
                        </span>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* No Project Selected */}
      {!selectedProject && projects.length > 0 && (
        <div className="no-project-selected">
          <div className="info-message">
            <div className="info-icon">📋</div>
            <h4>Select a Project</h4>
            <p>Choose a project above to view its payment request history.</p>
          </div>
        </div>
      )}

      {/* No Projects Available */}
      {projects.length === 0 && (
        <div className="no-projects-available">
          <div className="info-message">
            <div className="info-icon">🏗️</div>
            <h4>No Projects Available</h4>
            <p>You don't have any construction projects yet.</p>
            <p>Projects become available when homeowners accept your estimates.</p>
          </div>
        </div>
      )}

      {/* Verification Modal */}
      {showVerifyModal && selectedPaymentForVerify && (
        <div className="modal-overlay" onClick={() => setShowVerifyModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3>
                {selectedPaymentForVerify.verificationStatus === 'verified' 
                  ? '✅ Verify Payment Receipt' 
                  : '❌ Request Receipt Correction'}
              </h3>
              <button 
                className="modal-close"
                onClick={() => setShowVerifyModal(false)}
              >
                ✕
              </button>
            </div>
            
            <div className="modal-body">
              <div className="payment-summary-modal">
                <h4>Payment Details:</h4>
                <p><strong>Stage:</strong> {selectedPaymentForVerify.stage_name}</p>
                <p><strong>Amount:</strong> {formatCurrency(selectedPaymentForVerify.approved_amount || selectedPaymentForVerify.requested_amount)}</p>
                <p><strong>Payment Method:</strong> {selectedPaymentForVerify.payment_method || 'Not specified'}</p>
                {selectedPaymentForVerify.transaction_reference && (
                  <p><strong>Transaction Ref:</strong> {selectedPaymentForVerify.transaction_reference}</p>
                )}
              </div>

              <div className="form-group">
                <label htmlFor="verification-notes">
                  {selectedPaymentForVerify.verificationStatus === 'verified' 
                    ? 'Verification Notes (Optional):' 
                    : 'Reason for Correction Request:'}
                </label>
                <textarea
                  id="verification-notes"
                  value={verificationNotes}
                  onChange={(e) => setVerificationNotes(e.target.value)}
                  placeholder={selectedPaymentForVerify.verificationStatus === 'verified' 
                    ? 'Add any notes about the verification...' 
                    : 'Please explain what needs to be corrected...'}
                  rows="4"
                  className="verification-notes-input"
                />
              </div>

              {selectedPaymentForVerify.verificationStatus === 'rejected' && !verificationNotes && (
                <p className="warning-text">⚠️ Please provide a reason for requesting correction</p>
              )}
            </div>

            <div className="modal-footer">
              <button
                className="btn btn-cancel"
                onClick={() => setShowVerifyModal(false)}
                disabled={verifyingPayment === selectedPaymentForVerify.id}
              >
                Cancel
              </button>
              <button
                className={`btn ${selectedPaymentForVerify.verificationStatus === 'verified' ? 'btn-verify' : 'btn-reject'}`}
                onClick={() => handleVerifyPayment(selectedPaymentForVerify.id, selectedPaymentForVerify.verificationStatus)}
                disabled={verifyingPayment === selectedPaymentForVerify.id || (selectedPaymentForVerify.verificationStatus === 'rejected' && !verificationNotes)}
              >
                {verifyingPayment === selectedPaymentForVerify.id ? '⏳ Processing...' : 
                  selectedPaymentForVerify.verificationStatus === 'verified' ? '✅ Confirm Verification' : '❌ Request Correction'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default PaymentHistory;