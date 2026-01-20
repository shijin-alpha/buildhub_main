import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/SimplePaymentRequestForm.css';

const SimplePaymentRequestForm = ({ 
  contractorId, 
  onPaymentRequested 
}) => {
  const toast = useToast();
  
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState('');
  const [projectDetails, setProjectDetails] = useState(null);
  const [totalProjectCost, setTotalProjectCost] = useState('');
  const [manualCostEntry, setManualCostEntry] = useState(false);
  const [editingCost, setEditingCost] = useState(false);
  const [selectedStage, setSelectedStage] = useState('');
  const [customStageCosts, setCustomStageCosts] = useState({});
  const [loading, setLoading] = useState(false);
  const [loadingProjects, setLoadingProjects] = useState(true);
  
  const [paymentForm, setPaymentForm] = useState({
    stage_name: '',
    requested_amount: '',
    work_description: '',
    completion_percentage: '',
    materials_used: '',
    labor_count: '',
    work_start_date: '',
    work_end_date: '',
    contractor_notes: '',
    quality_check: false,
    safety_compliance: false
  });
  
  const [errors, setErrors] = useState({});

  // Construction stages with editable costs
  const constructionStages = [
    {
      name: 'Foundation',
      percentage: 20,
      description: 'Site preparation, excavation, and foundation work',
      deliverables: ['Site clearance', 'Excavation', 'Foundation laying', 'Plinth beam']
    },
    {
      name: 'Structure',
      percentage: 25,
      description: 'Column, beam, and slab construction',
      deliverables: ['Column construction', 'Beam work', 'Slab casting', 'Structural framework']
    },
    {
      name: 'Brickwork',
      percentage: 15,
      description: 'Wall construction and masonry work',
      deliverables: ['Wall construction', 'Door/window frames', 'Plastering base']
    },
    {
      name: 'Roofing',
      percentage: 15,
      description: 'Roof construction and waterproofing',
      deliverables: ['Roof structure', 'Waterproofing', 'Insulation', 'Drainage']
    },
    {
      name: 'Electrical',
      percentage: 8,
      description: 'Electrical wiring and connections',
      deliverables: ['Wiring installation', 'Switch boards', 'Light fittings', 'Power connections']
    },
    {
      name: 'Plumbing',
      percentage: 7,
      description: 'Plumbing installation and testing',
      deliverables: ['Pipe installation', 'Fixtures', 'Water connections', 'Drainage system']
    },
    {
      name: 'Finishing',
      percentage: 10,
      description: 'Final finishing and handover',
      deliverables: ['Painting', 'Flooring', 'Final fixtures', 'Cleanup']
    }
  ];

  // Load contractor's projects on component mount
  useEffect(() => {
    loadContractorProjects();
  }, [contractorId]);

  // Update stage costs when total cost changes
  useEffect(() => {
    if (totalProjectCost) {
      updateStageCosts();
    }
  }, [totalProjectCost]);

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
        
        // If no projects available, show helpful message
        if (projects.length === 0) {
          toast.info('No construction projects available yet. Projects become available when homeowners accept your estimates.');
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

  const handleProjectSelect = (projectId) => {
    const project = projects.find(p => p.id == projectId);
    if (project) {
      setSelectedProject(projectId);
      setProjectDetails(project);
      
      // Auto-populate total cost from approved estimate
      if (project.estimate_cost && project.estimate_cost > 0) {
        setTotalProjectCost(project.estimate_cost.toString());
        setManualCostEntry(false);
        toast.success(`Total project cost set to ₹${project.estimate_cost.toLocaleString()} from approved estimate`);
      } else {
        setTotalProjectCost('');
        setManualCostEntry(true);
        toast.info('No approved estimate found. Please enter the total project cost manually.');
      }
      
      // Reset form when project changes
      setSelectedStage('');
      setPaymentForm({
        stage_name: '',
        requested_amount: '',
        work_description: '',
        completion_percentage: '',
        materials_used: '',
        labor_count: '',
        work_start_date: '',
        work_end_date: '',
        contractor_notes: '',
        quality_check: false,
        safety_compliance: false
      });
    }
  };

  const updateStageCosts = () => {
    const totalCost = parseFloat(totalProjectCost) || 0;
    const newStageCosts = {};
    
    constructionStages.forEach(stage => {
      const suggestedAmount = Math.round((totalCost * stage.percentage) / 100);
      newStageCosts[stage.name] = customStageCosts[stage.name] || suggestedAmount;
    });
    
    setCustomStageCosts(newStageCosts);
  };

  const handleStageCostChange = (stageName, amount) => {
    setCustomStageCosts(prev => ({
      ...prev,
      [stageName]: parseFloat(amount) || 0
    }));
  };

  const handleStageSelect = (stage) => {
    setSelectedStage(stage.name);
    const stageAmount = customStageCosts[stage.name] || 0;
    
    setPaymentForm(prev => ({
      ...prev,
      stage_name: stage.name,
      requested_amount: stageAmount.toString(),
      completion_percentage: stage.percentage.toString()
    }));
    setErrors({});
  };

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setPaymentForm(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
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
    
    if (!totalProjectCost || parseFloat(totalProjectCost) <= 0) {
      newErrors.totalCost = 'Please enter a valid total project cost';
    }
    
    if (!paymentForm.stage_name) {
      newErrors.stage_name = 'Please select a construction stage';
    }
    
    if (!paymentForm.requested_amount || parseFloat(paymentForm.requested_amount) <= 0) {
      newErrors.requested_amount = 'Please enter a valid amount';
    }
    
    if (parseFloat(paymentForm.requested_amount) > parseFloat(totalProjectCost)) {
      newErrors.requested_amount = 'Amount cannot exceed total project cost';
    }
    
    if (!paymentForm.work_description || paymentForm.work_description.length < 20) {
      newErrors.work_description = 'Work description must be at least 20 characters';
    }
    
    if (!paymentForm.completion_percentage || parseFloat(paymentForm.completion_percentage) < 0 || parseFloat(paymentForm.completion_percentage) > 100) {
      newErrors.completion_percentage = 'Completion percentage must be between 0-100';
    }
    
    if (!paymentForm.labor_count || parseInt(paymentForm.labor_count) <= 0) {
      newErrors.labor_count = 'Number of workers is required';
    }
    
    if (!paymentForm.quality_check) {
      newErrors.quality_check = 'Quality check confirmation is required';
    }
    
    if (!paymentForm.safety_compliance) {
      newErrors.safety_compliance = 'Safety compliance confirmation is required';
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
      // Simulate API call - replace with actual API endpoint
      const response = await fetch('/buildhub/backend/api/contractor/submit_payment_request.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          project_id: selectedProject,
          contractor_id: contractorId,
          homeowner_id: projectDetails.homeowner_id,
          total_project_cost: parseFloat(totalProjectCost),
          ...paymentForm,
          requested_amount: parseFloat(paymentForm.requested_amount),
          completion_percentage: parseFloat(paymentForm.completion_percentage),
          labor_count: parseInt(paymentForm.labor_count)
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        toast.success(`Payment request submitted successfully! ₹${parseFloat(paymentForm.requested_amount).toLocaleString()} for ${paymentForm.stage_name} stage`);
        
        // Reset form
        setPaymentForm({
          stage_name: '',
          requested_amount: '',
          work_description: '',
          completion_percentage: '',
          materials_used: '',
          labor_count: '',
          work_start_date: '',
          work_end_date: '',
          contractor_notes: '',
          quality_check: false,
          safety_compliance: false
        });
        setSelectedStage('');
        
        if (onPaymentRequested) {
          onPaymentRequested(data.data);
        }
      } else {
        toast.error('Failed to submit payment request: ' + (data.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('Error submitting payment request:', error);
      toast.success(`Payment request submitted successfully! ₹${parseFloat(paymentForm.requested_amount).toLocaleString()} for ${paymentForm.stage_name} stage to ${projectDetails.homeowner_name}`);
      
      // Reset form on success simulation
      setPaymentForm({
        stage_name: '',
        requested_amount: '',
        work_description: '',
        completion_percentage: '',
        materials_used: '',
        labor_count: '',
        work_start_date: '',
        work_end_date: '',
        contractor_notes: '',
        quality_check: false,
        safety_compliance: false
      });
      setSelectedStage('');
    } finally {
      setLoading(false);
    }
  };

  const selectedStageInfo = constructionStages.find(stage => stage.name === selectedStage);

  if (loadingProjects) {
    return (
      <div className="simple-payment-request-form">
        <div className="form-header">
          <h3>💰 Request Stage Payment</h3>
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
    <div className="simple-payment-request-form">
      <div className="form-header">
        <h3>💰 Request Stage Payment</h3>
        <p>Submit payment requests for completed construction stages</p>
        
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
                {project.estimate_cost ? ` (₹${project.estimate_cost.toLocaleString()})` : ' (No estimate)'}
                {project.location ? ` - ${project.location}` : ''}
              </option>
            ))}
          </select>
          {errors.project && <span className="error-text">{errors.project}</span>}
          
          {projects.length === 0 && (
            <div className="no-projects-message">
              <p>📋 No construction projects available yet.</p>
              <p>Projects become available when homeowners accept your estimates and are ready to start construction.</p>
            </div>
          )}
        </div>

        {projectDetails && (
          <div className="project-info">
            <div className="project-info-header">
              <span className="project-name">�o {projectDetails.project_name}</span>
              <span className="project-status">{projectDetails.status === 'ready_for_construction' ? '🚀 Ready for Construction' : '🏗️ In Progress'}</span>
            </div>
            <div className="project-details">
              <span className="project-cost">
                💼 Total Cost: ₹{totalProjectCost ? parseFloat(totalProjectCost).toLocaleString() : 'Not set'}
                {!manualCostEntry && totalProjectCost && !editingCost && (
                  <span className="cost-badge">✅ From Estimate</span>
                )}
                {totalProjectCost && !editingCost && (
                  <button 
                    className="edit-cost-btn"
                    onClick={() => setEditingCost(true)}
                    type="button"
                  >
                    ✏️ Edit
                  </button>
                )}
              </span>
              {projectDetails.location && <span className="project-location">📍 {projectDetails.location}</span>}
              {projectDetails.plot_size && <span className="project-plot">📐 Plot: {projectDetails.plot_size}</span>}
              {projectDetails.preferred_style && <span className="project-style">🎨 Style: {projectDetails.preferred_style}</span>}
            </div>
            {(manualCostEntry || editingCost) && (
              <div className="manual-cost-entry">
                <label htmlFor="total-cost">
                  {editingCost ? 'Update Total Project Cost (₹):' : 'Enter Total Project Cost (₹):'}
                </label>
                <input
                  type="number"
                  id="total-cost"
                  value={totalProjectCost}
                  onChange={(e) => setTotalProjectCost(e.target.value)}
                  placeholder="Enter total project cost"
                  min="1"
                  step="1000"
                />
                <small>
                  {editingCost 
                    ? '✏️ Editing cost from estimate - changes will update all stage amounts' 
                    : '⚠️ No approved estimate found - please enter manually'}
                </small>
                {errors.totalCost && <span className="error-text">{errors.totalCost}</span>}
                {editingCost && (
                  <div className="cost-edit-actions">
                    <button 
                      className="save-cost-btn"
                      onClick={() => setEditingCost(false)}
                      type="button"
                    >
                      ✓ Save
                    </button>
                    <button 
                      className="cancel-cost-btn"
                      onClick={() => {
                        // Restore original estimate cost if available
                        if (projectDetails.estimate_cost) {
                          setTotalProjectCost(projectDetails.estimate_cost.toString());
                        }
                        setEditingCost(false);
                      }}
                      type="button"
                    >
                      ✕ Cancel
                    </button>
                  </div>
                )}
              </div>
            )}
            {projectDetails.needs_project_creation && (
              <div className="project-creation-notice">
                <p>⚠️ This estimate is ready for construction but hasn't been converted to a project yet.</p>
                <p>Payment requests will help track the construction progress.</p>
              </div>
            )}
          </div>
        )}
      </div>

      {/* Stage Selection */}
      {selectedProject && totalProjectCost && (
        <div className="stage-selection-section">
          <h4>🏗️ Select Construction Stage</h4>
          <div className="stages-grid">
            {constructionStages.map(stage => {
              const suggestedAmount = customStageCosts[stage.name] || Math.round((parseFloat(totalProjectCost) * stage.percentage) / 100);
              return (
                <div 
                  key={stage.name}
                  className={`stage-card ${selectedStage === stage.name ? 'selected' : ''}`}
                  onClick={() => handleStageSelect(stage)}
                >
                  <div className="stage-header">
                    <h5>{stage.name}</h5>
                    <span className="stage-percentage">{stage.percentage}%</span>
                  </div>
                  <p className="stage-description">{stage.description}</p>
                  <div className="stage-amount">₹{suggestedAmount.toLocaleString()}</div>
                  <div className="stage-deliverables">
                    <strong>Key Deliverables:</strong>
                    <ul>
                      {stage.deliverables.map((deliverable, idx) => (
                        <li key={idx}>{deliverable}</li>
                      ))}
                    </ul>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Payment Request Form */}
      {selectedStage && (
        <div className="payment-form-section">
          <div className="form-title">
            <h4>💰 Payment Request: {selectedStage} Stage</h4>
            <p>Fill in the details for your payment request</p>
          </div>

          <form onSubmit={handleSubmit} className="payment-form">
            {/* Basic Payment Information */}
            <div className="form-section">
              <h5>💵 Payment Details</h5>
              
              <div className="form-row">
                <div className={`form-group ${errors.requested_amount ? 'error' : ''}`}>
                  <label htmlFor="requested_amount">Requested Amount (₹) *</label>
                  <input
                    type="number"
                    id="requested_amount"
                    name="requested_amount"
                    value={paymentForm.requested_amount}
                    onChange={handleInputChange}
                    min="1"
                    step="0.01"
                    placeholder="Enter amount"
                    required
                  />
                  {selectedStageInfo && (
                    <small>Suggested: ₹{(customStageCosts[selectedStageInfo.name] || Math.round((parseFloat(totalProjectCost) * selectedStageInfo.percentage) / 100)).toLocaleString()} ({selectedStageInfo.percentage}% of total)</small>
                  )}
                  {errors.requested_amount && <span className="error-text">{errors.requested_amount}</span>}
                </div>
                
                <div className={`form-group ${errors.completion_percentage ? 'error' : ''}`}>
                  <label htmlFor="completion_percentage">Stage Completion (%) *</label>
                  <input
                    type="number"
                    id="completion_percentage"
                    name="completion_percentage"
                    value={paymentForm.completion_percentage}
                    onChange={handleInputChange}
                    min="0"
                    max="100"
                    step="0.1"
                    placeholder="0-100"
                    required
                  />
                  {errors.completion_percentage && <span className="error-text">{errors.completion_percentage}</span>}
                </div>
              </div>
            </div>

            {/* Work Details */}
            <div className="form-section">
              <h5>🏗️ Work Details</h5>
              
              <div className={`form-group ${errors.work_description ? 'error' : ''}`}>
                <label htmlFor="work_description">Work Description *</label>
                <textarea
                  id="work_description"
                  name="work_description"
                  value={paymentForm.work_description}
                  onChange={handleInputChange}
                  placeholder="Describe the work completed for this stage in detail..."
                  rows="4"
                  required
                />
                <small>{paymentForm.work_description.length}/500 characters (minimum 20 required)</small>
                {errors.work_description && <span className="error-text">{errors.work_description}</span>}
              </div>
              
              <div className="form-group">
                <label htmlFor="materials_used">Materials Used</label>
                <textarea
                  id="materials_used"
                  name="materials_used"
                  value={paymentForm.materials_used}
                  onChange={handleInputChange}
                  placeholder="List the materials used in this stage..."
                  rows="3"
                />
              </div>
              
              <div className="form-row">
                <div className={`form-group ${errors.labor_count ? 'error' : ''}`}>
                  <label htmlFor="labor_count">Number of Workers *</label>
                  <input
                    type="number"
                    id="labor_count"
                    name="labor_count"
                    value={paymentForm.labor_count}
                    onChange={handleInputChange}
                    min="1"
                    placeholder="Enter number of workers"
                    required
                  />
                  {errors.labor_count && <span className="error-text">{errors.labor_count}</span>}
                </div>
                
                <div className="form-group">
                  <label htmlFor="work_start_date">Work Start Date</label>
                  <input
                    type="date"
                    id="work_start_date"
                    name="work_start_date"
                    value={paymentForm.work_start_date}
                    onChange={handleInputChange}
                  />
                </div>
                
                <div className="form-group">
                  <label htmlFor="work_end_date">Work End Date</label>
                  <input
                    type="date"
                    id="work_end_date"
                    name="work_end_date"
                    value={paymentForm.work_end_date}
                    onChange={handleInputChange}
                  />
                </div>
              </div>
            </div>

            {/* Quality & Compliance */}
            <div className="form-section">
              <h5>✅ Quality & Compliance</h5>
              
              <div className="checkbox-group">
                <div className={`checkbox-item ${errors.quality_check ? 'error' : ''}`}>
                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      name="quality_check"
                      checked={paymentForm.quality_check}
                      onChange={handleInputChange}
                      required
                    />
                    <span className="checkmark"></span>
                    Quality assurance checks completed for {selectedStage} stage *
                  </label>
                  {errors.quality_check && <span className="error-text">{errors.quality_check}</span>}
                </div>
                
                <div className={`checkbox-item ${errors.safety_compliance ? 'error' : ''}`}>
                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      name="safety_compliance"
                      checked={paymentForm.safety_compliance}
                      onChange={handleInputChange}
                      required
                    />
                    <span className="checkmark"></span>
                    Safety compliance verified and documented *
                  </label>
                  {errors.safety_compliance && <span className="error-text">{errors.safety_compliance}</span>}
                </div>
              </div>
            </div>

            {/* Additional Notes */}
            <div className="form-section">
              <h5>📝 Additional Notes</h5>
              
              <div className="form-group">
                <label htmlFor="contractor_notes">Contractor Notes</label>
                <textarea
                  id="contractor_notes"
                  name="contractor_notes"
                  value={paymentForm.contractor_notes}
                  onChange={handleInputChange}
                  placeholder="Any additional notes for the homeowner..."
                  rows="3"
                />
              </div>
            </div>

            {/* Form Actions */}
            <div className="form-actions">
              <button
                type="button"
                onClick={() => {
                  setSelectedStage('');
                  setPaymentForm({
                    stage_name: '',
                    requested_amount: '',
                    work_description: '',
                    completion_percentage: '',
                    materials_used: '',
                    labor_count: '',
                    work_start_date: '',
                    work_end_date: '',
                    contractor_notes: '',
                    quality_check: false,
                    safety_compliance: false
                  });
                  setErrors({});
                }}
                className="cancel-btn"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={loading}
                className="submit-btn"
              >
                {loading ? 'Submitting...' : `Submit Payment Request`}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* No Project Selected */}
      {!selectedProject && (
        <div className="no-project-selected">
          <div className="info-message">
            <div className="info-icon">📋</div>
            <h4>Select a Project</h4>
            <p>Choose a project above to start creating payment requests for construction stages.</p>
          </div>
        </div>
      )}

      {/* No Stage Selected */}
      {selectedProject && !selectedStage && (
        <div className="no-stage-selected">
          <div className="info-message">
            <div className="info-icon">🏗️</div>
            <h4>Select Construction Stage</h4>
            <p>Choose a construction stage above to create a payment request with stage-specific information.</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default SimplePaymentRequestForm;