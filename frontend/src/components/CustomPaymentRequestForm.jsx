import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/CustomPaymentRequestForm.css';

const CustomPaymentRequestForm = ({ 
  contractorId, 
  onPaymentRequested 
}) => {
  const toast = useToast();
  
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState('');
  const [projectDetails, setProjectDetails] = useState(null);
  const [budgetSummary, setBudgetSummary] = useState(null);
  const [loading, setLoading] = useState(false);
  const [loadingProjects, setLoadingProjects] = useState(true);
  
  const [customForm, setCustomForm] = useState({
    request_title: '',
    request_reason: '',
    requested_amount: '',
    work_description: '',
    urgency_level: 'medium',
    category: '',
    contractor_notes: ''
  });
  
  const [errors, setErrors] = useState({});

  // Urgency levels
  const urgencyLevels = [
    { value: 'low', label: 'Low Priority', color: '#28a745', icon: '🟢' },
    { value: 'medium', label: 'Medium Priority', color: '#ffc107', icon: '🟡' },
    { value: 'high', label: 'High Priority', color: '#fd7e14', icon: '🟠' },
    { value: 'urgent', label: 'Urgent', color: '#dc3545', icon: '🔴' }
  ];

  // Common categories
  const categories = [
    'Material Cost Increase',
    'Additional Work Required',
    'Site Conditions',
    'Design Changes',
    'Unforeseen Issues',
    'Equipment Rental',
    'Labor Overtime',
    'Permit & Fees',
    'Quality Upgrades',
    'Other'
  ];

  // Load contractor's projects on component mount
  useEffect(() => {
    loadContractorProjects();
  }, [contractorId]);

  const loadContractorProjects = async () => {
    try {
      setLoadingProjects(true);
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id=${contractorId}`,
        { credentials: 'include' }
      );
      
      const data = await response.json();
      console.log('Projects API response:', data); // Debug log
      
      if (data.success) {
        const projects = data.data.projects || [];
        setProjects(projects);
        
        if (projects.length === 0) {
          console.log('No projects found for contractor:', contractorId);
          toast.info('No construction projects available yet. Complete some estimates first to create projects.');
        } else {
          console.log(`Loaded ${projects.length} projects for contractor:`, contractorId);
        }
      } else {
        console.error('API returned error:', data);
        toast.error('Failed to load projects: ' + (data.message || 'Unknown error'));
        setProjects([]);
      }
    } catch (error) {
      console.error('Error loading projects:', error);
      toast.error('Failed to load projects. Please check your connection and try again.');
      setProjects([]);
    } finally {
      setLoadingProjects(false);
    }
  };

  const handleProjectSelect = async (projectId) => {
    const project = projects.find(p => p.id == projectId);
    if (project) {
      setSelectedProject(projectId);
      setProjectDetails(project);
      
      // Load budget summary
      await loadBudgetSummary(projectId);
      
      // Reset form when project changes
      setCustomForm({
        request_title: '',
        request_reason: '',
        requested_amount: '',
        work_description: '',
        urgency_level: 'medium',
        category: '',
        contractor_notes: ''
      });
      setErrors({});
    }
  };

  const loadBudgetSummary = async (projectId) => {
    try {
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_project_budget_summary.php?contractor_id=${contractorId}&project_id=${projectId}`,
        { credentials: 'include' }
      );
      
      const data = await response.json();
      
      if (data.success) {
        setBudgetSummary(data.data);
      } else {
        console.error('Failed to load budget summary:', data.message);
        setBudgetSummary(null);
      }
    } catch (error) {
      console.error('Error loading budget summary:', error);
      setBudgetSummary(null);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setCustomForm(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const validateForm = () => {
    const newErrors = {};
    
    if (!selectedProject) {
      newErrors.project = 'Please select a project';
    }
    
    if (!customForm.request_title || customForm.request_title.length < 5) {
      newErrors.request_title = 'Request title must be at least 5 characters';
    }
    
    if (!customForm.request_reason || customForm.request_reason.length < 20) {
      newErrors.request_reason = 'Request reason must be at least 20 characters';
    }
    
    if (!customForm.requested_amount || parseFloat(customForm.requested_amount) <= 0) {
      newErrors.requested_amount = 'Please enter a valid amount';
    }
    
    if (!customForm.work_description || customForm.work_description.length < 20) {
      newErrors.work_description = 'Work description must be at least 20 characters';
    }
    
    if (!customForm.urgency_level) {
      newErrors.urgency_level = 'Please select urgency level';
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      toast.error('Please fix the form errors before submitting');
      return;
    }
    
    setLoading(true);
    
    try {
      const response = await fetch('/buildhub/backend/api/contractor/submit_custom_payment_request.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          project_id: selectedProject,
          contractor_id: contractorId,
          homeowner_id: projectDetails.homeowner_id,
          ...customForm,
          requested_amount: parseFloat(customForm.requested_amount)
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        toast.success(`Custom payment request submitted successfully! ₹${parseFloat(customForm.requested_amount).toLocaleString()} for "${customForm.request_title}" to ${data.data.homeowner_name}`);
        
        // Reset form
        setCustomForm({
          request_title: '',
          request_reason: '',
          requested_amount: '',
          work_description: '',
          urgency_level: 'medium',
          category: '',
          contractor_notes: ''
        });
        
        // Reload budget summary
        if (selectedProject) {
          await loadBudgetSummary(selectedProject);
        }
        
        if (onPaymentRequested) {
          onPaymentRequested(data.data);
        }
      } else {
        toast.error('Failed to submit custom payment request: ' + (data.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('Error submitting custom payment request:', error);
      toast.error('Error submitting custom payment request. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  if (loadingProjects) {
    return (
      <div className="custom-payment-request-form">
        <div className="form-header">
          <h3>💰 Request Custom Payment</h3>
          <p>Loading your projects...</p>
        </div>
        <div className="loading-projects">
          <div className="loading-spinner">🔄</div>
          <p>Loading contractor projects...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="custom-payment-request-form">
      <div className="form-header">
        <h3>💰 Request Custom Payment</h3>
        <p>Request additional payment for extra work, material cost increases, or unforeseen expenses</p>
        
        {/* Project Selection */}
        <div className="project-selection">
          <label htmlFor="project-select">Select Project:</label>
          <select 
            id="project-select"
            value={selectedProject}
            onChange={(e) => handleProjectSelect(e.target.value)}
            className="project-select"
          >
            <option value="">Choose a project...</option>
            {projects.map(project => (
              <option key={project.id} value={project.id}>
                {project.project_name} - {project.homeowner_name}
                {project.estimate_cost ? ` (₹${project.estimate_cost.toLocaleString()})` : ''}
                {project.location ? ` - ${project.location}` : ''}
              </option>
            ))}
          </select>
          {errors.project && <span className="error-text">{errors.project}</span>}
          
          {projects.length === 0 && (
            <div className="no-projects-message">
              <div className="no-projects-icon">📋</div>
              <h4>No Construction Projects Available</h4>
              <p>To create custom payment requests, you need active construction projects.</p>
              <div className="no-projects-help">
                <h5>How to get projects:</h5>
                <ul>
                  <li>✅ Submit estimates to homeowners</li>
                  <li>✅ Wait for homeowner approval</li>
                  <li>✅ Approved estimates become construction projects</li>
                  <li>✅ Then you can request custom payments</li>
                </ul>
              </div>
              <div className="no-projects-actions">
                <button 
                  className="btn btn-primary"
                  onClick={() => window.location.reload()}
                >
                  🔄 Refresh Projects
                </button>
              </div>
            </div>
          )}
        </div>

        {/* Budget Summary */}
        {budgetSummary && (
          <div className="budget-summary">
            <div className="budget-header">
              <h4>📊 Project Budget Summary</h4>
            </div>
            <div className="budget-cards">
              <div className="budget-card original">
                <div className="budget-label">Original Estimate</div>
                <div className="budget-amount">₹{budgetSummary.budget_summary.original_estimate.toLocaleString()}</div>
              </div>
              <div className="budget-card current">
                <div className="budget-label">Current Total Cost</div>
                <div className="budget-amount">₹{budgetSummary.budget_summary.total_project_cost.toLocaleString()}</div>
              </div>
              <div className={`budget-card difference ${budgetSummary.budget_summary.budget_difference >= 0 ? 'overrun' : 'underrun'}`}>
                <div className="budget-label">
                  {budgetSummary.budget_summary.budget_difference >= 0 ? 'Budget Overrun' : 'Budget Underrun'}
                </div>
                <div className="budget-amount">
                  {budgetSummary.budget_summary.budget_difference >= 0 ? '+' : ''}₹{Math.abs(budgetSummary.budget_summary.budget_difference).toLocaleString()}
                  {budgetSummary.budget_summary.overrun_percentage > 0 && (
                    <span className="percentage"> ({budgetSummary.budget_summary.overrun_percentage.toFixed(1)}%)</span>
                  )}
                </div>
              </div>
            </div>
            
            <div className="payment-breakdown">
              <div className="breakdown-section">
                <h5>Stage Payments</h5>
                <div className="breakdown-item">
                  <span>Total: ₹{budgetSummary.payment_breakdown.stage_payments.total_amount.toLocaleString()}</span>
                  <span>Paid: ₹{budgetSummary.payment_breakdown.stage_payments.paid_amount.toLocaleString()}</span>
                </div>
              </div>
              <div className="breakdown-section">
                <h5>Custom Payments</h5>
                <div className="breakdown-item">
                  <span>Total: ₹{budgetSummary.payment_breakdown.custom_payments.total_amount.toLocaleString()}</span>
                  <span>Paid: ₹{budgetSummary.payment_breakdown.custom_payments.paid_amount.toLocaleString()}</span>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Custom Payment Request Form */}
      {selectedProject && (
        <div className="custom-form-section">
          <div className="form-title">
            <h4>📝 Custom Payment Request Details</h4>
            <p>Provide detailed information about your additional payment request</p>
          </div>

          <form onSubmit={handleSubmit} className="custom-form">
            {/* Basic Information */}
            <div className="form-section">
              <h5>📋 Request Information</h5>
              
              <div className="form-row">
                <div className={`form-group ${errors.request_title ? 'error' : ''}`}>
                  <label htmlFor="request_title">Request Title *</label>
                  <input
                    type="text"
                    id="request_title"
                    name="request_title"
                    value={customForm.request_title}
                    onChange={handleInputChange}
                    placeholder="e.g., Additional Electrical Work"
                    maxLength="255"
                    required
                  />
                  <small>{customForm.request_title.length}/255 characters (minimum 5 required)</small>
                  {errors.request_title && <span className="error-text">{errors.request_title}</span>}
                </div>
                
                <div className={`form-group ${errors.requested_amount ? 'error' : ''}`}>
                  <label htmlFor="requested_amount">Requested Amount (₹) *</label>
                  <input
                    type="number"
                    id="requested_amount"
                    name="requested_amount"
                    value={customForm.requested_amount}
                    onChange={handleInputChange}
                    min="1"
                    step="0.01"
                    placeholder="Enter amount"
                    required
                  />
                  {errors.requested_amount && <span className="error-text">{errors.requested_amount}</span>}
                </div>
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label htmlFor="category">Category</label>
                  <select
                    id="category"
                    name="category"
                    value={customForm.category}
                    onChange={handleInputChange}
                  >
                    <option value="">Select category...</option>
                    {categories.map(category => (
                      <option key={category} value={category}>{category}</option>
                    ))}
                  </select>
                </div>
                
                <div className={`form-group ${errors.urgency_level ? 'error' : ''}`}>
                  <label htmlFor="urgency_level">Urgency Level *</label>
                  <select
                    id="urgency_level"
                    name="urgency_level"
                    value={customForm.urgency_level}
                    onChange={handleInputChange}
                    required
                  >
                    {urgencyLevels.map(level => (
                      <option key={level.value} value={level.value}>
                        {level.icon} {level.label}
                      </option>
                    ))}
                  </select>
                  {errors.urgency_level && <span className="error-text">{errors.urgency_level}</span>}
                </div>
              </div>
            </div>

            {/* Detailed Description */}
            <div className="form-section">
              <h5>📝 Detailed Information</h5>
              
              <div className={`form-group ${errors.request_reason ? 'error' : ''}`}>
                <label htmlFor="request_reason">Reason for Additional Payment *</label>
                <textarea
                  id="request_reason"
                  name="request_reason"
                  value={customForm.request_reason}
                  onChange={handleInputChange}
                  placeholder="Explain why this additional payment is needed..."
                  rows="4"
                  maxLength="1000"
                  required
                />
                <small>{customForm.request_reason.length}/1000 characters (minimum 20 required)</small>
                {errors.request_reason && <span className="error-text">{errors.request_reason}</span>}
              </div>
              
              <div className={`form-group ${errors.work_description ? 'error' : ''}`}>
                <label htmlFor="work_description">Work Description *</label>
                <textarea
                  id="work_description"
                  name="work_description"
                  value={customForm.work_description}
                  onChange={handleInputChange}
                  placeholder="Describe the additional work or expenses in detail..."
                  rows="4"
                  maxLength="1000"
                  required
                />
                <small>{customForm.work_description.length}/1000 characters (minimum 20 required)</small>
                {errors.work_description && <span className="error-text">{errors.work_description}</span>}
              </div>
              
              <div className="form-group">
                <label htmlFor="contractor_notes">Additional Notes</label>
                <textarea
                  id="contractor_notes"
                  name="contractor_notes"
                  value={customForm.contractor_notes}
                  onChange={handleInputChange}
                  placeholder="Any additional notes for the homeowner..."
                  rows="3"
                  maxLength="500"
                />
                <small>{customForm.contractor_notes.length}/500 characters</small>
              </div>
            </div>

            {/* Form Actions */}
            <div className="form-actions">
              <button
                type="button"
                onClick={() => {
                  setCustomForm({
                    request_title: '',
                    request_reason: '',
                    requested_amount: '',
                    work_description: '',
                    urgency_level: 'medium',
                    category: '',
                    contractor_notes: ''
                  });
                  setErrors({});
                }}
                className="cancel-btn"
              >
                Clear Form
              </button>
              <button
                type="submit"
                disabled={loading}
                className="submit-btn"
              >
                {loading ? 'Submitting...' : `Submit Custom Payment Request`}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* No Project Selected */}
      {!selectedProject && projects.length > 0 && (
        <div className="no-project-selected">
          <div className="info-message">
            <div className="info-icon">💰</div>
            <h4>Select a Project</h4>
            <p>Choose a project above to create custom payment requests for additional work or expenses.</p>
            <div className="project-help">
              <h5>Custom payments are for:</h5>
              <ul>
                <li>🔧 Additional work not in original estimate</li>
                <li>📈 Material cost increases</li>
                <li>⚠️ Unforeseen site conditions</li>
                <li>🎨 Design changes requested by homeowner</li>
                <li>🛠️ Equipment rental needs</li>
              </ul>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default CustomPaymentRequestForm;