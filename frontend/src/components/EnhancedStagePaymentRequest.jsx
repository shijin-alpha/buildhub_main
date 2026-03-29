import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/StagePaymentRequest.css';

const EnhancedStagePaymentRequest = ({ 
  projectId, 
  contractorId, 
  onPaymentRequested,
  showStageSelector = true
}) => {
  const toast = useToast();
  
  const [selectedStage, setSelectedStage] = useState('');
  const [loading, setLoading] = useState(false);
  const [showRequestForm, setShowRequestForm] = useState(false);
  
  const [requestForm, setRequestForm] = useState({
    requested_amount: '',
    work_description: '',
    completion_percentage: '',
    contractor_notes: '',
    
    // Enhanced fields
    materials_used: '',
    labor_cost: '',
    material_cost: '',
    equipment_cost: '',
    other_expenses: '',
    work_start_date: '',
    work_end_date: '',
    quality_check_status: 'pending',
    safety_compliance: false,
    weather_delays: 0,
    workers_count: '',
    supervisor_name: '',
    next_stage_readiness: 'not_ready'
  });
  
  const [formErrors, setFormErrors] = useState({});

  const constructionStages = [
    { 
      name: 'Foundation', 
      typical_percentage: 20, 
      description: 'Excavation, foundation laying, and base preparation',
      required_fields: ['materials_used', 'quality_check_status', 'safety_compliance'],
      typical_materials: 'Cement, Steel bars, Sand, Aggregate, Water'
    },
    { 
      name: 'Structure', 
      typical_percentage: 25, 
      description: 'Column, beam, and slab construction',
      required_fields: ['materials_used', 'quality_check_status', 'safety_compliance'],
      typical_materials: 'Cement, Steel bars, Bricks, Sand, Aggregate'
    },
    { 
      name: 'Brickwork', 
      typical_percentage: 15, 
      description: 'Wall construction and masonry work',
      required_fields: ['materials_used'],
      typical_materials: 'Bricks, Cement, Sand, Mortar'
    },
    { 
      name: 'Roofing', 
      typical_percentage: 15, 
      description: 'Roof construction and waterproofing',
      required_fields: ['materials_used', 'weather_delays'],
      typical_materials: 'Roofing sheets, Tiles, Waterproofing, Insulation'
    },
    { 
      name: 'Electrical', 
      typical_percentage: 8, 
      description: 'Electrical wiring and fixture installation',
      required_fields: ['materials_used', 'safety_compliance'],
      typical_materials: 'Wires, Switches, Sockets, MCB, Conduits'
    },
    { 
      name: 'Plumbing', 
      typical_percentage: 7, 
      description: 'Plumbing installation and testing',
      required_fields: ['materials_used', 'quality_check_status'],
      typical_materials: 'Pipes, Fittings, Valves, Fixtures, Sealants'
    },
    { 
      name: 'Finishing', 
      typical_percentage: 10, 
      description: 'Painting, flooring, and final touches',
      required_fields: ['materials_used', 'quality_check_status'],
      typical_materials: 'Paint, Tiles, Fixtures, Hardware, Sealants'
    }
  ];

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    const newValue = type === 'checkbox' ? checked : value;
    
    setRequestForm(prev => ({
      ...prev,
      [name]: newValue
    }));
    
    // Auto-calculate total amount when cost breakdown changes
    if (['labor_cost', 'material_cost', 'equipment_cost', 'other_expenses'].includes(name)) {
      const updatedForm = { ...requestForm, [name]: parseFloat(newValue) || 0 };
      const total = (updatedForm.labor_cost || 0) + (updatedForm.material_cost || 0) + 
                   (updatedForm.equipment_cost || 0) + (updatedForm.other_expenses || 0);
      if (total > 0) {
        setRequestForm(prev => ({ ...prev, [name]: newValue, requested_amount: total.toString() }));
      }
    }
    
    // Clear error when user starts typing
    if (formErrors[name]) {
      setFormErrors(prev => ({
        ...prev,
        [name]: null
      }));
    }
  };

  const handleStageChange = (stageName) => {
    setSelectedStage(stageName);
    const stage = constructionStages.find(s => s.name === stageName);
    if (stage) {
      setRequestForm(prev => ({
        ...prev,
        materials_used: stage.typical_materials,
        completion_percentage: stage.typical_percentage.toString()
      }));
    }
  };

  const validateForm = () => {
    const errors = {};
    const stage = constructionStages.find(s => s.name === selectedStage);
    
    // Basic validation
    if (!requestForm.requested_amount || parseFloat(requestForm.requested_amount) <= 0) {
      errors.requested_amount = 'Please enter a valid amount';
    }
    
    if (!requestForm.work_description || requestForm.work_description.trim().length < 50) {
      errors.work_description = 'Work description must be at least 50 characters';
    }
    
    if (!requestForm.completion_percentage || requestForm.completion_percentage < 0 || requestForm.completion_percentage > 100) {
      errors.completion_percentage = 'Completion percentage must be between 0-100';
    }
    
    if (!requestForm.workers_count || parseInt(requestForm.workers_count) <= 0) {
      errors.workers_count = 'Number of workers is required';
    }
    
    // Stage-specific validation
    if (stage && stage.required_fields) {
      stage.required_fields.forEach(field => {
        if (field === 'safety_compliance' && !requestForm[field]) {
          errors[field] = `Safety compliance is required for ${selectedStage} stage`;
        } else if (field !== 'safety_compliance' && !requestForm[field]) {
          errors[field] = `${field.replace('_', ' ')} is required for ${selectedStage} stage`;
        }
      });
    }
    
    // Cost breakdown validation
    const totalBreakdown = (parseFloat(requestForm.labor_cost) || 0) + 
                          (parseFloat(requestForm.material_cost) || 0) + 
                          (parseFloat(requestForm.equipment_cost) || 0) + 
                          (parseFloat(requestForm.other_expenses) || 0);
    
    if (totalBreakdown > 0 && Math.abs(totalBreakdown - parseFloat(requestForm.requested_amount)) > 1) {
      errors.cost_breakdown = 'Cost breakdown total must match requested amount';
    }
    
    // Date validation
    if (requestForm.work_start_date && requestForm.work_end_date) {
      if (new Date(requestForm.work_start_date) > new Date(requestForm.work_end_date)) {
        errors.work_end_date = 'End date must be after start date';
      }
    }
    
    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmitRequest = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      toast.error('Please fix the form errors before submitting');
      return;
    }
    
    setLoading(true);
    
    try {
      const response = await fetch(
        '/buildhub/backend/api/contractor/submit_enhanced_stage_payment_request.php',
        {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            project_id: projectId,
            contractor_id: contractorId,
            stage_name: selectedStage,
            ...requestForm
          })
        }
      );
      
      const data = await response.json();
      
      if (data.success) {
        toast.success(`Enhanced payment request submitted successfully! ₹${data.data.requested_amount} for ${selectedStage} stage`);
        
        // Reset form
        setRequestForm({
          requested_amount: '',
          work_description: '',
          completion_percentage: '',
          contractor_notes: '',
          materials_used: '',
          labor_cost: '',
          material_cost: '',
          equipment_cost: '',
          other_expenses: '',
          work_start_date: '',
          work_end_date: '',
          quality_check_status: 'pending',
          safety_compliance: false,
          weather_delays: 0,
          workers_count: '',
          supervisor_name: '',
          next_stage_readiness: 'not_ready'
        });
        setShowRequestForm(false);
        setSelectedStage('');
        
        // Notify parent component
        if (onPaymentRequested) {
          onPaymentRequested(data.data);
        }
      } else {
        toast.error('Failed to submit payment request: ' + data.message);
      }
    } catch (error) {
      console.error('Error submitting payment request:', error);
      toast.error('Error submitting payment request');
    } finally {
      setLoading(false);
    }
  };

  const selectedStageInfo = constructionStages.find(s => s.name === selectedStage);

  return (
    <div className="enhanced-stage-payment-request">
      <div className="payment-request-header">
        <h3>💰 Enhanced Stage Payment Request</h3>
        <p>Submit detailed payment requests with comprehensive stage information</p>
      </div>

      {/* Stage Selection */}
      {showStageSelector && (
        <div className="stage-selector-section">
          <h4>🏗️ Select Construction Stage</h4>
          <div className="stages-grid">
            {constructionStages.map(stage => (
              <div 
                key={stage.name}
                className={`stage-card ${selectedStage === stage.name ? 'selected' : ''}`}
                onClick={() => handleStageChange(stage.name)}
              >
                <div className="stage-header">
                  <h5>{stage.name}</h5>
                  <span className="stage-percentage">{stage.typical_percentage}%</span>
                </div>
                <p className="stage-description">{stage.description}</p>
                <div className="stage-materials">
                  <strong>Typical Materials:</strong> {stage.typical_materials}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Payment Request Form */}
      {selectedStage && (
        <div className="payment-request-form-section">
          {!showRequestForm ? (
            <div className="request-prompt">
              <div className="selected-stage-info">
                <h4>Selected Stage: {selectedStage}</h4>
                <p>{selectedStageInfo?.description}</p>
                <p><strong>Typical Percentage:</strong> {selectedStageInfo?.typical_percentage}%</p>
              </div>
              <button 
                type="button"
                onClick={() => setShowRequestForm(true)}
                className="start-request-btn"
              >
                💰 Create Payment Request for {selectedStage}
              </button>
            </div>
          ) : (
            <form onSubmit={handleSubmitRequest} className="enhanced-payment-form">
              <h4>💰 Payment Request: {selectedStage} Stage</h4>
              
              {/* Basic Information */}
              <div className="form-section">
                <h5>📋 Basic Information</h5>
                
                <div className="form-row">
                  <div className={`form-group ${formErrors.requested_amount ? 'error' : ''}`}>
                    <label htmlFor="requested_amount">Requested Amount (₹) *</label>
                    <input
                      type="number"
                      id="requested_amount"
                      name="requested_amount"
                      value={requestForm.requested_amount}
                      onChange={handleInputChange}
                      min="1"
                      step="0.01"
                      placeholder="Enter total amount"
                      required
                    />
                    {formErrors.requested_amount && (
                      <div className="field-error">{formErrors.requested_amount}</div>
                    )}
                  </div>
                  
                  <div className={`form-group ${formErrors.completion_percentage ? 'error' : ''}`}>
                    <label htmlFor="completion_percentage">Stage Completion (%) *</label>
                    <input
                      type="number"
                      id="completion_percentage"
                      name="completion_percentage"
                      value={requestForm.completion_percentage}
                      onChange={handleInputChange}
                      min="0"
                      max="100"
                      step="0.1"
                      required
                    />
                    {formErrors.completion_percentage && (
                      <div className="field-error">{formErrors.completion_percentage}</div>
                    )}
                  </div>
                </div>
              </div>

              {/* Cost Breakdown */}
              <div className="form-section">
                <h5>💰 Cost Breakdown</h5>
                <div className="cost-breakdown-grid">
                  <div className="form-group">
                    <label htmlFor="labor_cost">Labor Cost (₹)</label>
                    <input
                      type="number"
                      id="labor_cost"
                      name="labor_cost"
                      value={requestForm.labor_cost}
                      onChange={handleInputChange}
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                    />
                  </div>
                  
                  <div className="form-group">
                    <label htmlFor="material_cost">Material Cost (₹)</label>
                    <input
                      type="number"
                      id="material_cost"
                      name="material_cost"
                      value={requestForm.material_cost}
                      onChange={handleInputChange}
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                    />
                  </div>
                  
                  <div className="form-group">
                    <label htmlFor="equipment_cost">Equipment Cost (₹)</label>
                    <input
                      type="number"
                      id="equipment_cost"
                      name="equipment_cost"
                      value={requestForm.equipment_cost}
                      onChange={handleInputChange}
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                    />
                  </div>
                  
                  <div className="form-group">
                    <label htmlFor="other_expenses">Other Expenses (₹)</label>
                    <input
                      type="number"
                      id="other_expenses"
                      name="other_expenses"
                      value={requestForm.other_expenses}
                      onChange={handleInputChange}
                      min="0"
                      step="0.01"
                      placeholder="0.00"
                    />
                  </div>
                </div>
                
                {formErrors.cost_breakdown && (
                  <div className="field-error">{formErrors.cost_breakdown}</div>
                )}
                
                <div className="cost-total">
                  <strong>Total: ₹{(
                    (parseFloat(requestForm.labor_cost) || 0) + 
                    (parseFloat(requestForm.material_cost) || 0) + 
                    (parseFloat(requestForm.equipment_cost) || 0) + 
                    (parseFloat(requestForm.other_expenses) || 0)
                  ).toLocaleString()}</strong>
                </div>
              </div>

              {/* Work Details */}
              <div className="form-section">
                <h5>🏗️ Work Details</h5>
                
                <div className={`form-group ${formErrors.work_description ? 'error' : ''}`}>
                  <label htmlFor="work_description">Work Description *</label>
                  <textarea
                    id="work_description"
                    name="work_description"
                    value={requestForm.work_description}
                    onChange={handleInputChange}
                    placeholder="Describe the work completed for this stage in detail..."
                    rows="4"
                    required
                  />
                  <div className="field-info">
                    {requestForm.work_description.length}/1000 characters (minimum 50 required)
                  </div>
                  {formErrors.work_description && (
                    <div className="field-error">{formErrors.work_description}</div>
                  )}
                </div>
                
                <div className={`form-group ${formErrors.materials_used ? 'error' : ''}`}>
                  <label htmlFor="materials_used">Materials Used *</label>
                  <textarea
                    id="materials_used"
                    name="materials_used"
                    value={requestForm.materials_used}
                    onChange={handleInputChange}
                    placeholder="List all materials used in this stage..."
                    rows="3"
                    required={selectedStageInfo?.required_fields?.includes('materials_used')}
                  />
                  {formErrors.materials_used && (
                    <div className="field-error">{formErrors.materials_used}</div>
                  )}
                </div>
                
                <div className="form-row">
                  <div className="form-group">
                    <label htmlFor="work_start_date">Work Start Date</label>
                    <input
                      type="date"
                      id="work_start_date"
                      name="work_start_date"
                      value={requestForm.work_start_date}
                      onChange={handleInputChange}
                    />
                  </div>
                  
                  <div className={`form-group ${formErrors.work_end_date ? 'error' : ''}`}>
                    <label htmlFor="work_end_date">Work End Date</label>
                    <input
                      type="date"
                      id="work_end_date"
                      name="work_end_date"
                      value={requestForm.work_end_date}
                      onChange={handleInputChange}
                    />
                    {formErrors.work_end_date && (
                      <div className="field-error">{formErrors.work_end_date}</div>
                    )}
                  </div>
                </div>
              </div>

              {/* Quality and Safety */}
              <div className="form-section">
                <h5>✅ Quality & Safety</h5>
                
                <div className="form-row">
                  <div className={`form-group ${formErrors.quality_check_status ? 'error' : ''}`}>
                    <label htmlFor="quality_check_status">Quality Check Status *</label>
                    <select
                      id="quality_check_status"
                      name="quality_check_status"
                      value={requestForm.quality_check_status}
                      onChange={handleInputChange}
                      required={selectedStageInfo?.required_fields?.includes('quality_check_status')}
                    >
                      <option value="pending">Pending</option>
                      <option value="passed">Passed</option>
                      <option value="failed">Failed</option>
                      <option value="not_applicable">Not Applicable</option>
                    </select>
                    {formErrors.quality_check_status && (
                      <div className="field-error">{formErrors.quality_check_status}</div>
                    )}
                  </div>
                  
                  <div className="form-group">
                    <label htmlFor="next_stage_readiness">Next Stage Readiness</label>
                    <select
                      id="next_stage_readiness"
                      name="next_stage_readiness"
                      value={requestForm.next_stage_readiness}
                      onChange={handleInputChange}
                    >
                      <option value="ready">Ready</option>
                      <option value="not_ready">Not Ready</option>
                      <option value="partial">Partially Ready</option>
                    </select>
                  </div>
                </div>
                
                {selectedStageInfo?.required_fields?.includes('safety_compliance') && (
                  <div className={`form-group checkbox-group ${formErrors.safety_compliance ? 'error' : ''}`}>
                    <label>
                      <input
                        type="checkbox"
                        name="safety_compliance"
                        checked={requestForm.safety_compliance}
                        onChange={handleInputChange}
                      />
                      Safety compliance confirmed for {selectedStage} stage *
                    </label>
                    {formErrors.safety_compliance && (
                      <div className="field-error">{formErrors.safety_compliance}</div>
                    )}
                  </div>
                )}
              </div>

              {/* Team and Timeline */}
              <div className="form-section">
                <h5>👥 Team & Timeline</h5>
                
                <div className="form-row">
                  <div className={`form-group ${formErrors.workers_count ? 'error' : ''}`}>
                    <label htmlFor="workers_count">Number of Workers *</label>
                    <input
                      type="number"
                      id="workers_count"
                      name="workers_count"
                      value={requestForm.workers_count}
                      onChange={handleInputChange}
                      min="1"
                      placeholder="Enter number of workers"
                      required
                    />
                    {formErrors.workers_count && (
                      <div className="field-error">{formErrors.workers_count}</div>
                    )}
                  </div>
                  
                  <div className="form-group">
                    <label htmlFor="supervisor_name">Supervisor Name</label>
                    <input
                      type="text"
                      id="supervisor_name"
                      name="supervisor_name"
                      value={requestForm.supervisor_name}
                      onChange={handleInputChange}
                      placeholder="Name of supervising person"
                    />
                  </div>
                </div>
                
                <div className="form-group">
                  <label htmlFor="weather_delays">Weather Delays (days)</label>
                  <input
                    type="number"
                    id="weather_delays"
                    name="weather_delays"
                    value={requestForm.weather_delays}
                    onChange={handleInputChange}
                    min="0"
                    placeholder="0"
                  />
                  <div className="field-info">Number of days work was delayed due to weather</div>
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
                    value={requestForm.contractor_notes}
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
                  onClick={() => setShowRequestForm(false)}
                  className="cancel-btn"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={loading}
                  className="submit-btn"
                >
                  {loading ? 'Submitting...' : `Submit Payment Request for ${selectedStage}`}
                </button>
              </div>
            </form>
          )}
        </div>
      )}

      {/* Show message when no stage is selected */}
      {showStageSelector && !selectedStage && (
        <div className="no-stage-selected">
          <div className="info-message">
            <div className="info-icon">🏗️</div>
            <h4>Select Construction Stage</h4>
            <p>Choose a construction stage above to create a detailed payment request with stage-specific information.</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default EnhancedStagePaymentRequest;