import React, { useState, useEffect } from 'react';
import HousePlanDrawer from './HousePlanDrawer';
import '../styles/HousePlanManager.css';

const HousePlanManager = ({ layoutRequestId = null, onClose }) => {
  const [view, setView] = useState('list'); // 'list', 'create', 'edit'
  const [plans, setPlans] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selectedPlan, setSelectedPlan] = useState(null);
  const [requestInfo, setRequestInfo] = useState(null);

  useEffect(() => {
    loadPlans();
    if (layoutRequestId) {
      loadRequestInfo();
    }
  }, [layoutRequestId]);

  const loadPlans = async () => {
    setLoading(true);
    try {
      const url = layoutRequestId 
        ? `/buildhub/backend/api/architect/get_house_plans.php?layout_request_id=${layoutRequestId}`
        : '/buildhub/backend/api/architect/get_house_plans.php';
      
      const response = await fetch(url);
      const result = await response.json();
      
      if (result.success) {
        setPlans(result.plans);
      } else {
        console.error('Failed to load plans:', result.message);
      }
    } catch (error) {
      console.error('Error loading plans:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadRequestInfo = async () => {
    try {
      const response = await fetch(`/buildhub/backend/api/architect/get_assigned_requests.php`);
      const result = await response.json();
      
      if (result.success) {
        const request = result.assignments?.find(a => a.layout_request_id === layoutRequestId);
        if (request) {
          setRequestInfo(request);
        }
      }
    } catch (error) {
      console.error('Error loading request info:', error);
    }
  };

  const handleCreateNew = () => {
    setSelectedPlan(null);
    setView('create');
  };

  const handleEditPlan = (plan) => {
    setSelectedPlan(plan);
    setView('edit');
  };

  const handlePlanSaved = (result) => {
    setView('list');
    loadPlans();
    // Show success message
    alert('Plan saved successfully!');
  };

  const handleSubmitPlan = async (planId) => {
    if (!confirm('Are you sure you want to submit this plan to the homeowner? You won\'t be able to edit it after submission.')) {
      return;
    }

    try {
      const response = await fetch('/buildhub/backend/api/architect/submit_house_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ plan_id: planId })
      });

      const result = await response.json();
      
      if (result.success) {
        alert('Plan submitted successfully!');
        loadPlans();
      } else {
        alert(result.message || 'Failed to submit plan');
      }
    } catch (error) {
      console.error('Error submitting plan:', error);
      alert('Error submitting plan');
    }
  };

  const handleDeletePlan = async (planId) => {
    if (!confirm('Are you sure you want to delete this plan? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await fetch('/buildhub/backend/api/architect/delete_house_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ plan_id: planId })
      });

      const result = await response.json();
      
      if (result.success) {
        loadPlans();
      } else {
        alert(result.message || 'Failed to delete plan');
      }
    } catch (error) {
      console.error('Error deleting plan:', error);
      alert('Error deleting plan');
    }
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      draft: { class: 'status-draft', text: 'Draft' },
      submitted: { class: 'status-submitted', text: 'Submitted' },
      approved: { class: 'status-approved', text: 'Approved' },
      rejected: { class: 'status-rejected', text: 'Rejected' }
    };
    
    const config = statusConfig[status] || statusConfig.draft;
    return <span className={`status-badge ${config.class}`}>{config.text}</span>;
  };

  if (view === 'create' || view === 'edit') {
    return (
      <HousePlanDrawer
        layoutRequestId={layoutRequestId}
        existingPlan={selectedPlan}
        onSave={handlePlanSaved}
        onCancel={() => setView('list')}
      />
    );
  }

  return (
    <div className="house-plan-manager">
      <div className="manager-header">
        <div className="header-content">
          <h2>House Plans</h2>
          {requestInfo && (
            <div className="request-info">
              <h3>For: {requestInfo.homeowner_name}</h3>
              <p>{requestInfo.plot_size} • {requestInfo.budget_range}</p>
            </div>
          )}
        </div>
        <div className="header-actions">
          <button onClick={handleCreateNew} className="create-btn">
            Create New Plan
          </button>
          {onClose && (
            <button onClick={onClose} className="close-btn">
              Close
            </button>
          )}
        </div>
      </div>

      <div className="plans-content">
        {loading ? (
          <div className="loading">Loading plans...</div>
        ) : plans.length === 0 ? (
          <div className="empty-state">
            <div className="empty-icon">📐</div>
            <h3>No Plans Yet</h3>
            <p>Create your first custom house plan for this project</p>
            <button onClick={handleCreateNew} className="create-first-btn">
              Create First Plan
            </button>
          </div>
        ) : (
          <div className="plans-grid">
            {plans.map(plan => (
              <div key={plan.id} className="plan-card">
                <div className="plan-header">
                  <h4>{plan.plan_name}</h4>
                  {getStatusBadge(plan.status)}
                </div>
                
                <div className="plan-preview">
                  <div className="plan-stats">
                    <div className="stat">
                      <span className="stat-label">Plot Size:</span>
                      <span className="stat-value">{plan.plot_width}' × {plan.plot_height}'</span>
                    </div>
                    <div className="stat">
                      <span className="stat-label">Total Area:</span>
                      <span className="stat-value">{plan.total_area} sq ft</span>
                    </div>
                    <div className="stat">
                      <span className="stat-label">Rooms:</span>
                      <span className="stat-value">{plan.plan_data?.rooms?.length || 0}</span>
                    </div>
                  </div>
                  
                  {plan.notes && (
                    <div className="plan-notes">
                      <p>{plan.notes}</p>
                    </div>
                  )}
                </div>

                <div className="plan-meta">
                  <div className="plan-dates">
                    <small>Created: {new Date(plan.created_at).toLocaleDateString()}</small>
                    {plan.updated_at !== plan.created_at && (
                      <small>Updated: {new Date(plan.updated_at).toLocaleDateString()}</small>
                    )}
                  </div>
                </div>

                <div className="plan-actions">
                  {plan.status === 'draft' && (
                    <>
                      <button 
                        onClick={() => handleEditPlan(plan)}
                        className="edit-btn"
                      >
                        Edit
                      </button>
                      <button 
                        onClick={() => handleSubmitPlan(plan.id)}
                        className="submit-btn"
                      >
                        Submit
                      </button>
                      <button 
                        onClick={() => handleDeletePlan(plan.id)}
                        className="delete-btn"
                      >
                        Delete
                      </button>
                    </>
                  )}
                  
                  {plan.status === 'submitted' && (
                    <div className="submitted-info">
                      <span>Waiting for homeowner review</span>
                    </div>
                  )}
                  
                  {plan.status === 'approved' && (
                    <div className="approved-info">
                      <span>✓ Approved by homeowner</span>
                    </div>
                  )}
                  
                  {plan.status === 'rejected' && (
                    <button 
                      onClick={() => handleEditPlan(plan)}
                      className="revise-btn"
                    >
                      Revise Plan
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default HousePlanManager;