import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import StagePaymentRequest from './StagePaymentRequest.jsx';
import '../styles/ContractorPaymentManager.css';

const ContractorPaymentManager = ({ contractorId, onPaymentRequested }) => {
  const toast = useToast();
  
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState('');
  const [paymentRequests, setPaymentRequests] = useState([]);
  const [loading, setLoading] = useState(false);
  const [loadingProjects, setLoadingProjects] = useState(true);
  const [activeTab, setActiveTab] = useState('request'); // 'request' or 'history'

  useEffect(() => {
    loadAssignedProjects();
  }, [contractorId]);

  useEffect(() => {
    if (selectedProject) {
      loadPaymentHistory();
    }
  }, [selectedProject]);

  const loadAssignedProjects = async () => {
    try {
      setLoadingProjects(true);
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_assigned_projects.php?contractor_id=${contractorId}`,
        { credentials: 'include' }
      );
      const data = await response.json();
      
      if (data.success) {
        setProjects(data.data.projects || []);
      } else {
        toast.error('Failed to load projects: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading projects:', error);
      toast.error('Error loading projects');
    } finally {
      setLoadingProjects(false);
    }
  };

  const loadPaymentHistory = async () => {
    try {
      setLoading(true);
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_payment_history.php?project_id=${selectedProject}&contractor_id=${contractorId}`,
        { credentials: 'include' }
      );
      const data = await response.json();
      
      if (data.success) {
        setPaymentRequests(data.data.payment_requests || []);
      } else {
        console.warn('Failed to load payment history:', data.message);
        setPaymentRequests([]);
      }
    } catch (error) {
      console.error('Error loading payment history:', error);
      setPaymentRequests([]);
    } finally {
      setLoading(false);
    }
  };

  const handlePaymentRequested = (data) => {
    toast.success(`Payment request submitted: ₹${data.requested_amount} for ${data.stage_name} stage`);
    loadPaymentHistory(); // Refresh payment history
    if (onPaymentRequested) {
      onPaymentRequested(data);
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

  const getSelectedProjectInfo = () => {
    const project = projects.find(p => p.project_id == selectedProject);
    return project || null;
  };

  if (loadingProjects) {
    return (
      <div className="payment-manager">
        <div className="loading-spinner">
          <div className="spinner"></div>
          <p>Loading projects...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="payment-manager">
      {/* Project Selection */}
      <div className="project-selection-section">
        <h3>Select Project</h3>
        <div className="project-selection-container">
          <select
            value={selectedProject}
            onChange={(e) => setSelectedProject(e.target.value)}
            className="project-select"
          >
            <option value="">Choose a project...</option>
            {projects.map(project => (
              <option key={project.project_id} value={project.project_id}>
                {project.project_display_name}
              </option>
            ))}
          </select>
          
          {selectedProject && (
            <div className="selected-project-info">
              {(() => {
                const projectInfo = getSelectedProjectInfo();
                return projectInfo ? (
                  <div className="project-summary">
                    <div className="project-header">
                      <h4>{projectInfo.project_summary.homeowner_name}</h4>
                      <span className="project-status">{projectInfo.project_summary.status_display}</span>
                    </div>
                    <div className="project-details">
                      <span><strong>Budget:</strong> {projectInfo.project_summary.total_cost_formatted}</span>
                      <span><strong>Progress:</strong> {projectInfo.latest_progress}%</span>
                      <span><strong>Location:</strong> {projectInfo.project_summary.location}</span>
                    </div>
                  </div>
                ) : null;
              })()}
            </div>
          )}
        </div>
      </div>

      {/* Show payment management only after project is selected */}
      {selectedProject && (
        <>
          {/* Tab Navigation */}
          <div className="payment-tabs">
            <button 
              className={`tab-btn ${activeTab === 'request' ? 'active' : ''}`}
              onClick={() => setActiveTab('request')}
            >
              💰 Request Payment
            </button>
            <button 
              className={`tab-btn ${activeTab === 'history' ? 'active' : ''}`}
              onClick={() => setActiveTab('history')}
            >
              📋 Payment History
            </button>
          </div>

          {/* Tab Content */}
          {activeTab === 'request' ? (
            <div className="payment-request-tab">
              <div className="tab-header">
                <h3>Request Stage Payment</h3>
                <p>Submit payment requests for completed construction stages</p>
              </div>
              
              <StagePaymentRequest 
                projectId={selectedProject}
                contractorId={contractorId}
                onPaymentRequested={handlePaymentRequested}
                showStageSelector={true}
              />
            </div>
          ) : (
            <div className="payment-history-tab">
              <div className="tab-header">
                <h3>Payment History</h3>
                <p>Track all payment requests and their status</p>
              </div>

              {loading ? (
                <div className="loading-spinner">
                  <div className="spinner"></div>
                  <p>Loading payment history...</p>
                </div>
              ) : paymentRequests.length === 0 ? (
                <div className="no-payments">
                  <div className="no-payments-icon">💳</div>
                  <h4>No Payment Requests</h4>
                  <p>You haven't submitted any payment requests for this project yet.</p>
                  <p>Use the "Request Payment" tab to submit your first payment request.</p>
                </div>
              ) : (
                <div className="payment-history-grid">
                  {paymentRequests.map(request => (
                    <div key={request.id} className={`payment-history-card ${getStatusBadgeClass(request.status)}`}>
                      <div className="card-header">
                        <div className="stage-info">
                          <h4>🏗️ {request.stage_name} Stage</h4>
                          <p>Progress: {request.completion_percentage}%</p>
                        </div>
                        <div className={`status-badge ${getStatusBadgeClass(request.status)}`}>
                          {getStatusIcon(request.status)} {request.status.toUpperCase()}
                        </div>
                      </div>

                      <div className="payment-details">
                        <div className="amount-section">
                          <div className="requested-amount">
                            <span className="label">Requested:</span>
                            <span className="amount">{formatCurrency(request.requested_amount)}</span>
                          </div>
                          {request.approved_amount && request.approved_amount !== request.requested_amount && (
                            <div className="approved-amount">
                              <span className="label">Approved:</span>
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
                            <strong>Your Notes:</strong>
                            <p>{request.contractor_notes}</p>
                          </div>
                        )}

                        {request.homeowner_notes && (
                          <div className="homeowner-response">
                            <strong>Homeowner Response:</strong>
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
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </>
      )}

      {/* Show message when no project is selected */}
      {!selectedProject && (
        <div className="no-project-selected">
          <div className="info-message">
            <div className="info-icon">📋</div>
            <h4>Select a Project</h4>
            <p>Please select a project from the dropdown above to manage payment requests.</p>
            <p>You can then request payments for construction stages and track their status.</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default ContractorPaymentManager;