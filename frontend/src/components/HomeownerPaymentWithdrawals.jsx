import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/HomeownerPaymentWithdrawals.css';

const HomeownerPaymentWithdrawals = ({ homeownerId, projectId = null }) => {
  const toast = useToast();
  
  const [paymentRequests, setPaymentRequests] = useState([]);
  const [summary, setSummary] = useState({});
  const [stageBreakdown, setStageBreakdown] = useState([]);
  const [projectSummary, setProjectSummary] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedStatus, setSelectedStatus] = useState('all');
  const [selectedProject, setSelectedProject] = useState(projectId || 'all');
  const [expandedRequest, setExpandedRequest] = useState(null);

  useEffect(() => {
    loadPaymentRequests();
  }, [homeownerId, selectedStatus, selectedProject]);

  const loadPaymentRequests = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        homeowner_id: homeownerId,
        status: selectedStatus
      });
      
      if (selectedProject && selectedProject !== 'all') {
        params.append('project_id', selectedProject);
      }
      
      const response = await fetch(
        `/buildhub/backend/api/homeowner/get_enhanced_payment_requests.php?${params}`,
        { credentials: 'include' }
      );
      
      const data = await response.json();
      
      if (data.success) {
        setPaymentRequests(data.data.requests || []);
        setSummary(data.data.summary || {});
        setStageBreakdown(data.data.stage_breakdown || []);
        setProjectSummary(data.data.project_summary || []);
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

  const handleApprovePayment = async (requestId, approvedAmount = null) => {
    try {
      const response = await fetch(
        '/buildhub/backend/api/homeowner/respond_payment_request.php',
        {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            payment_request_id: requestId,
            action: 'approve',
            approved_amount: approvedAmount,
            homeowner_notes: 'Payment approved'
          })
        }
      );
      
      const data = await response.json();
      
      if (data.success) {
        toast.success('Payment request approved successfully');
        loadPaymentRequests(); // Refresh the list
      } else {
        toast.error('Failed to approve payment: ' + data.message);
      }
    } catch (error) {
      console.error('Error approving payment:', error);
      toast.error('Error approving payment');
    }
  };

  const handleRejectPayment = async (requestId, reason) => {
    try {
      const response = await fetch(
        '/buildhub/backend/api/homeowner/respond_payment_request.php',
        {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            payment_request_id: requestId,
            action: 'reject',
            rejection_reason: reason,
            homeowner_notes: `Payment rejected: ${reason}`
          })
        }
      );
      
      const data = await response.json();
      
      if (data.success) {
        toast.success('Payment request rejected');
        loadPaymentRequests(); // Refresh the list
      } else {
        toast.error('Failed to reject payment: ' + data.message);
      }
    } catch (error) {
      console.error('Error rejecting payment:', error);
      toast.error('Error rejecting payment');
    }
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

  const getUrgencyClass = (urgency) => {
    switch (urgency) {
      case 'high': return 'urgency-high';
      case 'medium': return 'urgency-medium';
      case 'low': return 'urgency-low';
      default: return '';
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
      <div className="payment-withdrawals-loading">
        <div className="loading-spinner"></div>
        <p>Loading payment withdrawal requests...</p>
      </div>
    );
  }

  return (
    <div className="homeowner-payment-withdrawals">
      <div className="withdrawals-header">
        <h3>💰 Payment Withdrawal Requests</h3>
        <p>Review and manage contractor payment requests for construction stages</p>
      </div>

      {/* Summary Cards */}
      <div className="payment-summary-cards">
        <div className="summary-card pending">
          <div className="card-icon">⏳</div>
          <div className="card-content">
            <h4>{summary.pending_requests || 0}</h4>
            <p>Pending Requests</p>
            <span className="amount">{formatCurrency(summary.pending_amount || 0)}</span>
          </div>
        </div>
        
        <div className="summary-card approved">
          <div className="card-icon">✅</div>
          <div className="card-content">
            <h4>{summary.approved_requests || 0}</h4>
            <p>Approved</p>
            <span className="amount">{formatCurrency(summary.approved_amount || 0)}</span>
          </div>
        </div>
        
        <div className="summary-card paid">
          <div className="card-icon">💰</div>
          <div className="card-content">
            <h4>{summary.paid_requests || 0}</h4>
            <p>Paid</p>
            <span className="amount">{formatCurrency(summary.paid_amount || 0)}</span>
          </div>
        </div>
        
        <div className="summary-card rejected">
          <div className="card-icon">❌</div>
          <div className="card-content">
            <h4>{summary.rejected_requests || 0}</h4>
            <p>Rejected</p>
            <span className="amount">-</span>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="payment-filters">
        <div className="filter-group">
          <label htmlFor="status-filter">Status:</label>
          <select
            id="status-filter"
            value={selectedStatus}
            onChange={(e) => setSelectedStatus(e.target.value)}
          >
            <option value="all">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="paid">Paid</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
        
        {!projectId && projectSummary.length > 1 && (
          <div className="filter-group">
            <label htmlFor="project-filter">Project:</label>
            <select
              id="project-filter"
              value={selectedProject}
              onChange={(e) => setSelectedProject(e.target.value)}
            >
              <option value="all">All Projects</option>
              {projectSummary.map(project => (
                <option key={project.project_id} value={project.project_id}>
                  {project.project_display_name}
                </option>
              ))}
            </select>
          </div>
        )}
      </div>

      {/* Stage Breakdown */}
      {stageBreakdown.length > 0 && (
        <div className="stage-breakdown-section">
          <h4>📊 Stage-wise Payment Breakdown</h4>
          <div className="stage-breakdown-grid">
            {stageBreakdown.map(stage => (
              <div key={stage.stage_name} className="stage-breakdown-card">
                <div className="stage-header">
                  <h5>{stage.stage_name}</h5>
                  <span className="request-count">{stage.request_count} requests</span>
                </div>
                <div className="stage-amounts">
                  <div className="amount-item">
                    <span>Requested:</span>
                    <span>{formatCurrency(stage.total_requested)}</span>
                  </div>
                  <div className="amount-item">
                    <span>Paid:</span>
                    <span>{formatCurrency(stage.total_paid)}</span>
                  </div>
                </div>
                <div className="stage-progress">
                  <div className="progress-bar">
                    <div 
                      className="progress-fill"
                      style={{ width: `${stage.avg_completion}%` }}
                    ></div>
                  </div>
                  <span>{stage.avg_completion.toFixed(1)}% avg completion</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Payment Requests List */}
      <div className="payment-requests-section">
        <h4>📋 Payment Requests</h4>
        
        {paymentRequests.length === 0 ? (
          <div className="no-requests">
            <div className="no-requests-icon">💳</div>
            <h4>No Payment Requests</h4>
            <p>No payment withdrawal requests found for the selected filters.</p>
          </div>
        ) : (
          <div className="payment-requests-list">
            {paymentRequests.map(request => (
              <div 
                key={request.id} 
                className={`payment-request-card ${getStatusBadgeClass(request.status)} ${getUrgencyClass(request.urgency)}`}
              >
                <div className="request-header">
                  <div className="request-info">
                    <h5>🏗️ {request.stage_name} Stage</h5>
                    <p className="contractor-name">by {request.contractor_name}</p>
                    <p className="request-date">{request.request_date_formatted}</p>
                  </div>
                  
                  <div className="request-status-amount">
                    <div className={`status-badge ${getStatusBadgeClass(request.status)}`}>
                      {getStatusIcon(request.status)} {request.status.toUpperCase()}
                    </div>
                    <div className="request-amount">
                      {formatCurrency(request.requested_amount)}
                    </div>
                    {request.approved_amount && request.approved_amount !== request.requested_amount && (
                      <div className="approved-amount">
                        Approved: {formatCurrency(request.approved_amount)}
                      </div>
                    )}
                  </div>
                </div>

                <div className="request-summary">
                  <div className="summary-grid">
                    <div className="summary-item">
                      <span>Completion:</span>
                      <span>{request.completion_percentage}%</span>
                    </div>
                    <div className="summary-item">
                      <span>Workers:</span>
                      <span>{request.workers_count}</span>
                    </div>
                    <div className="summary-item">
                      <span>Quality Check:</span>
                      <span>{request.quality_check_status}</span>
                    </div>
                    <div className="summary-item">
                      <span>Safety:</span>
                      <span>{request.safety_compliance ? '✅ Yes' : '❌ No'}</span>
                    </div>
                  </div>
                </div>

                {/* Cost Breakdown */}
                {request.cost_breakdown_total > 0 && (
                  <div className="cost-breakdown">
                    <h6>💰 Cost Breakdown</h6>
                    <div className="breakdown-grid">
                      <div className="breakdown-item">
                        <span>Labor:</span>
                        <span>{formatCurrency(request.labor_cost)}</span>
                      </div>
                      <div className="breakdown-item">
                        <span>Materials:</span>
                        <span>{formatCurrency(request.material_cost)}</span>
                      </div>
                      <div className="breakdown-item">
                        <span>Equipment:</span>
                        <span>{formatCurrency(request.equipment_cost)}</span>
                      </div>
                      <div className="breakdown-item">
                        <span>Other:</span>
                        <span>{formatCurrency(request.other_expenses)}</span>
                      </div>
                    </div>
                  </div>
                )}

                {/* Work Description */}
                <div className="work-description">
                  <h6>🏗️ Work Description</h6>
                  <p>{request.work_description}</p>
                </div>

                {/* Work Timeline */}
                {(request.work_start_date || request.work_end_date) && (
                  <div className="work-timeline">
                    <h6>📅 Work Timeline</h6>
                    <div className="timeline-info">
                      {request.work_start_date && (
                        <span>Start: {request.work_start_date_formatted}</span>
                      )}
                      {request.work_end_date && (
                        <span>End: {request.work_end_date_formatted}</span>
                      )}
                      {request.weather_delays > 0 && (
                        <span>Weather Delays: {request.weather_delays} days</span>
                      )}
                    </div>
                  </div>
                )}

                {/* Contractor Notes */}
                {request.contractor_notes && (
                  <div className="contractor-notes">
                    <h6>📝 Contractor Notes</h6>
                    <p>{request.contractor_notes}</p>
                  </div>
                )}

                {/* Actions for Pending Requests */}
                {request.status === 'pending' && (
                  <div className="request-actions">
                    <button
                      onClick={() => handleApprovePayment(request.id)}
                      className="approve-btn"
                    >
                      ✅ Approve Payment
                    </button>
                    <button
                      onClick={() => {
                        const reason = prompt('Please provide a reason for rejection:');
                        if (reason) {
                          handleRejectPayment(request.id, reason);
                        }
                      }}
                      className="reject-btn"
                    >
                      ❌ Reject
                    </button>
                    <button
                      onClick={() => setExpandedRequest(expandedRequest === request.id ? null : request.id)}
                      className="details-btn"
                    >
                      {expandedRequest === request.id ? '🔼 Less Details' : '🔽 More Details'}
                    </button>
                  </div>
                )}

                {/* Expanded Details */}
                {expandedRequest === request.id && (
                  <div className="expanded-details">
                    <div className="details-grid">
                      <div className="detail-section">
                        <h6>👥 Team Information</h6>
                        <p><strong>Supervisor:</strong> {request.supervisor_name || 'Not specified'}</p>
                        <p><strong>Workers:</strong> {request.workers_count}</p>
                        <p><strong>Next Stage:</strong> {request.next_stage_readiness}</p>
                      </div>
                      
                      <div className="detail-section">
                        <h6>📊 Project Progress</h6>
                        <p><strong>Overall Progress:</strong> {request.overall_project_progress.toFixed(1)}%</p>
                        <p><strong>Stage Progress:</strong> {request.stage_progress_status}</p>
                        <p><strong>Days Since Request:</strong> {request.days_since_request}</p>
                      </div>
                    </div>
                    
                    {request.homeowner_notes && (
                      <div className="homeowner-response">
                        <h6>💬 Your Response</h6>
                        <p>{request.homeowner_notes}</p>
                        {request.homeowner_response_date && (
                          <small>Responded on: {request.homeowner_response_date_formatted}</small>
                        )}
                      </div>
                    )}
                  </div>
                )}

                {/* Urgency Indicator */}
                {request.urgency !== 'none' && request.urgency !== 'low' && (
                  <div className={`urgency-indicator ${getUrgencyClass(request.urgency)}`}>
                    {request.urgency === 'high' ? '🔴 High Priority' : '🟡 Medium Priority'}
                    {request.is_overdue && ' - Overdue'}
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default HomeownerPaymentWithdrawals;