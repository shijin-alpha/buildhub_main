import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/StagePaymentRequest.css';

const StagePaymentRequest = ({ 
  projectId, 
  stageName, 
  contractorId, 
  completionPercentage = 0, 
  workDescription = '',
  onPaymentRequested,
  showStageSelector = false
}) => {
  const toast = useToast();
  
  const [paymentInfo, setPaymentInfo] = useState(null);
  const [loading, setLoading] = useState(false);
  const [loadingInfo, setLoadingInfo] = useState(true);
  const [showRequestForm, setShowRequestForm] = useState(false);
  const [selectedStage, setSelectedStage] = useState(stageName || '');
  const [availableStages, setAvailableStages] = useState([]);
  
  const [requestForm, setRequestForm] = useState({
    requested_amount: '',
    work_description: '',
    contractor_notes: '',
    completion_percentage: completionPercentage
  });
  
  const [formErrors, setFormErrors] = useState({});

  // Load available stages when showStageSelector is true
  useEffect(() => {
    if (showStageSelector && projectId) {
      loadAvailableStages();
    }
  }, [showStageSelector, projectId]);

  // Load payment information when component mounts or dependencies change
  useEffect(() => {
    if (projectId && (selectedStage || stageName)) {
      loadPaymentInfo();
    }
  }, [projectId, selectedStage, stageName]);

  // Update form when props change
  useEffect(() => {
    setRequestForm(prev => ({
      ...prev,
      work_description: workDescription,
      completion_percentage: completionPercentage
    }));
  }, [workDescription, completionPercentage]);

  const loadAvailableStages = async () => {
    try {
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_available_stages.php?project_id=${projectId}`,
        { credentials: 'include' }
      );
      
      const data = await response.json();
      
      if (data.success) {
        setAvailableStages(data.data.stages || []);
      } else {
        console.warn('Failed to load available stages:', data.message);
        // Fallback to default stages
        setAvailableStages([
          { stage_name: 'Foundation', typical_percentage: 20 },
          { stage_name: 'Structure', typical_percentage: 25 },
          { stage_name: 'Brickwork', typical_percentage: 15 },
          { stage_name: 'Roofing', typical_percentage: 15 },
          { stage_name: 'Electrical', typical_percentage: 8 },
          { stage_name: 'Plumbing', typical_percentage: 7 },
          { stage_name: 'Finishing', typical_percentage: 10 }
        ]);
      }
    } catch (error) {
      console.error('Error loading available stages:', error);
      // Fallback to default stages
      setAvailableStages([
        { stage_name: 'Foundation', typical_percentage: 20 },
        { stage_name: 'Structure', typical_percentage: 25 },
        { stage_name: 'Brickwork', typical_percentage: 15 },
        { stage_name: 'Roofing', typical_percentage: 15 },
        { stage_name: 'Electrical', typical_percentage: 8 },
        { stage_name: 'Plumbing', typical_percentage: 7 },
        { stage_name: 'Finishing', typical_percentage: 10 }
      ]);
    }
  };

  const loadPaymentInfo = async () => {
    const currentStage = selectedStage || stageName;
    if (!currentStage) return;
    
    try {
      setLoadingInfo(true);
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_stage_payment_info.php?project_id=${projectId}&stage_name=${encodeURIComponent(currentStage)}`,
        { credentials: 'include' }
      );
      
      const data = await response.json();
      
      if (data.success) {
        setPaymentInfo(data.data);
        
        // Set suggested amount if no existing requests
        if (data.data.can_request && data.data.suggested_amount > 0) {
          setRequestForm(prev => ({
            ...prev,
            requested_amount: data.data.suggested_amount.toString()
          }));
        }
      } else {
        toast.error('Failed to load payment information: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading payment info:', error);
      toast.error('Error loading payment information');
    } finally {
      setLoadingInfo(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setRequestForm(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Clear error when user starts typing
    if (formErrors[name]) {
      setFormErrors(prev => ({
        ...prev,
        [name]: null
      }));
    }
  };

  const validateForm = () => {
    const errors = {};
    
    if (!requestForm.requested_amount || parseFloat(requestForm.requested_amount) <= 0) {
      errors.requested_amount = 'Please enter a valid amount';
    }
    
    if (parseFloat(requestForm.requested_amount) > paymentInfo?.project_info?.remaining_amount) {
      errors.requested_amount = 'Amount exceeds remaining project budget';
    }
    
    if (!requestForm.work_description || requestForm.work_description.trim().length < 20) {
      errors.work_description = 'Work description must be at least 20 characters';
    }
    
    if (requestForm.completion_percentage < 0 || requestForm.completion_percentage > 100) {
      errors.completion_percentage = 'Completion percentage must be between 0-100';
    }
    
    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmitRequest = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }
    
    setLoading(true);
    
    try {
      const response = await fetch(
        '/buildhub/backend/api/contractor/submit_stage_payment_request.php',
        {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            project_id: projectId,
            stage_name: stageName,
            requested_amount: parseFloat(requestForm.requested_amount),
            work_description: requestForm.work_description,
            completion_percentage: parseFloat(requestForm.completion_percentage),
            contractor_notes: requestForm.contractor_notes
          })
        }
      );
      
      const data = await response.json();
      
      if (data.success) {
        toast.success('Payment request submitted successfully!');
        
        // Reset form
        setRequestForm({
          requested_amount: '',
          work_description: '',
          contractor_notes: '',
          completion_percentage: 0
        });
        setShowRequestForm(false);
        
        // Reload payment info
        await loadPaymentInfo();
        
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

  if (loadingInfo) {
    return (
      <div className="stage-payment-loading">
        <div className="loading-spinner">🔄 Loading payment information...</div>
      </div>
    );
  }

  if (!paymentInfo) {
    return (
      <div className="stage-payment-error">
        <div className="error-message">Unable to load payment information</div>
      </div>
    );
  }

  return (
    <div className="stage-payment-request">
      {/* Stage Selector (when showStageSelector is true) */}
      {showStageSelector && (
        <div className="stage-selector-section">
          <h6>🏗️ Select Construction Stage</h6>
          <div className="stage-selector-container">
            <select
              value={selectedStage}
              onChange={(e) => setSelectedStage(e.target.value)}
              className="stage-select"
            >
              <option value="">Choose a construction stage...</option>
              {availableStages.map(stage => (
                <option 
                  key={stage.id} 
                  value={stage.stage_name}
                  disabled={stage.has_existing_request && stage.existing_status !== 'rejected'}
                >
                  {stage.stage_name} ({stage.typical_percentage}%)
                  {stage.has_existing_request && ` - ${stage.existing_status.toUpperCase()}`}
                </option>
              ))}
            </select>
            
            {selectedStage && (
              <div className="selected-stage-info">
                {(() => {
                  const stageInfo = availableStages.find(s => s.stage_name === selectedStage);
                  return stageInfo ? (
                    <div className="stage-details">
                      <span><strong>Stage:</strong> {stageInfo.stage_name}</span>
                      <span><strong>Typical %:</strong> {stageInfo.typical_percentage}%</span>
                      {stageInfo.description && (
                        <span><strong>Description:</strong> {stageInfo.description}</span>
                      )}
                      {stageInfo.has_existing_request && (
                        <span className={`existing-status ${stageInfo.existing_status}`}>
                          <strong>Status:</strong> {stageInfo.existing_status.toUpperCase()}
                          {stageInfo.existing_status === 'rejected' && ' (Can request again)'}
                        </span>
                      )}
                    </div>
                  ) : null;
                })()}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Only show payment info if stage is selected (when using stage selector) or stageName is provided */}
      {(selectedStage || (!showStageSelector && stageName)) && (
        <>
          {/* Payment Summary */}
          <div className="payment-summary">
        <h6>💼 Project Payment Summary</h6>
        <div className="summary-grid">
          <div className="summary-item">
            <span className="label">Total Project Cost:</span>
            <span className="value">₹{paymentInfo.project_info.total_cost.toLocaleString()}</span>
          </div>
          <div className="summary-item">
            <span className="label">Amount Paid:</span>
            <span className="value paid">₹{paymentInfo.project_info.total_paid.toLocaleString()}</span>
          </div>
          <div className="summary-item">
            <span className="label">Pending Approval:</span>
            <span className="value pending">₹{paymentInfo.project_info.total_pending.toLocaleString()}</span>
          </div>
          <div className="summary-item">
            <span className="label">Remaining Budget:</span>
            <span className="value remaining">₹{paymentInfo.project_info.remaining_amount.toLocaleString()}</span>
          </div>
        </div>
        
        <div className="payment-progress-bar">
          <div className="progress-segments">
            <div 
              className="progress-segment paid"
              style={{ width: `${paymentInfo.payment_summary.paid_percentage}%` }}
              title={`Paid: ${paymentInfo.payment_summary.paid_percentage.toFixed(1)}%`}
            ></div>
            <div 
              className="progress-segment pending"
              style={{ width: `${paymentInfo.payment_summary.pending_percentage}%` }}
              title={`Pending: ${paymentInfo.payment_summary.pending_percentage.toFixed(1)}%`}
            ></div>
            <div 
              className="progress-segment remaining"
              style={{ width: `${paymentInfo.payment_summary.remaining_percentage}%` }}
              title={`Remaining: ${paymentInfo.payment_summary.remaining_percentage.toFixed(1)}%`}
            ></div>
          </div>
          <div className="progress-labels">
            <span className="paid-label">Paid ({paymentInfo.payment_summary.paid_percentage.toFixed(1)}%)</span>
            <span className="pending-label">Pending ({paymentInfo.payment_summary.pending_percentage.toFixed(1)}%)</span>
            <span className="remaining-label">Remaining ({paymentInfo.payment_summary.remaining_percentage.toFixed(1)}%)</span>
          </div>
        </div>
      </div>

      {/* Stage Information */}
      {paymentInfo.stage_info && (
        <div className="stage-info">
          <h6>🏗️ {stageName} Stage Payment</h6>
          <div className="stage-details">
            <div className="stage-description">{paymentInfo.stage_info.description}</div>
            <div className="stage-typical">
              Typical percentage for this stage: <strong>{paymentInfo.stage_info.typical_percentage}%</strong>
              (₹{paymentInfo.suggested_amount.toLocaleString()})
            </div>
          </div>
        </div>
      )}

      {/* Existing Requests */}
      {paymentInfo.existing_requests.length > 0 && (
        <div className="existing-requests">
          <h6>📋 Previous Requests for {stageName}</h6>
          <div className="requests-list">
            {paymentInfo.existing_requests.map(request => (
              <div key={request.id} className={`request-item ${request.status}`}>
                <div className="request-header">
                  <span className="request-amount">₹{parseFloat(request.requested_amount).toLocaleString()}</span>
                  <span className={`request-status ${request.status}`}>
                    {request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                  </span>
                </div>
                <div className="request-details">
                  <div className="request-date">
                    Requested: {new Date(request.request_date).toLocaleDateString()}
                  </div>
                  <div className="request-percentage">
                    {request.completion_percentage}% complete, {request.percentage_of_total.toFixed(1)}% of total cost
                  </div>
                </div>
                {request.rejection_reason && (
                  <div className="rejection-reason">
                    <strong>Rejection reason:</strong> {request.rejection_reason}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Request Form */}
      {paymentInfo.can_request ? (
        <div className="payment-request-form">
          {!showRequestForm ? (
            <div className="request-prompt">
              <button 
                type="button"
                onClick={() => setShowRequestForm(true)}
                className="request-payment-btn"
              >
                💰 Request Payment for {stageName} Stage
              </button>
              <div className="request-info">
                Complete the {stageName} stage work and request payment from the homeowner
              </div>
            </div>
          ) : (
            <form onSubmit={handleSubmitRequest} className="payment-form">
              <h6>💰 Request Payment for {stageName} Stage</h6>
              
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
                  placeholder="Enter amount"
                  required
                />
                {paymentInfo.suggested_amount > 0 && (
                  <div className="field-info">
                    Suggested: ₹{paymentInfo.suggested_amount.toLocaleString()} 
                    ({paymentInfo.suggested_percentage}% of total cost)
                  </div>
                )}
                {formErrors.requested_amount && (
                  <div className="field-error">{formErrors.requested_amount}</div>
                )}
              </div>
              
              <div className={`form-group ${formErrors.completion_percentage ? 'error' : ''}`}>
                <label htmlFor="completion_percentage">Stage Completion Percentage *</label>
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
                <div className="field-info">Percentage of {stageName} stage completed</div>
                {formErrors.completion_percentage && (
                  <div className="field-error">{formErrors.completion_percentage}</div>
                )}
              </div>
              
              <div className={`form-group ${formErrors.work_description ? 'error' : ''}`}>
                <label htmlFor="work_description">Work Description *</label>
                <textarea
                  id="work_description"
                  name="work_description"
                  value={requestForm.work_description}
                  onChange={handleInputChange}
                  placeholder="Describe the work completed for this stage..."
                  rows="4"
                  required
                />
                <div className="field-info">
                  {requestForm.work_description.length}/1000 characters (minimum 20 required)
                </div>
                {formErrors.work_description && (
                  <div className="field-error">{formErrors.work_description}</div>
                )}
              </div>
              
              <div className="form-group">
                <label htmlFor="contractor_notes">Additional Notes</label>
                <textarea
                  id="contractor_notes"
                  name="contractor_notes"
                  value={requestForm.contractor_notes}
                  onChange={handleInputChange}
                  placeholder="Any additional notes for the homeowner..."
                  rows="2"
                />
                <div className="field-info">Optional notes about the payment request</div>
              </div>
              
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
                  {loading ? 'Submitting...' : 'Submit Payment Request'}
                </button>
              </div>
            </form>
          )}
        </div>
      ) : (
        <div className="cannot-request">
          <div className="info-message">
            ℹ️ Cannot request payment for {selectedStage || stageName} stage. 
            {paymentInfo.existing_requests.some(r => r.status === 'pending') 
              ? 'There is already a pending request for this stage.'
              : 'Please complete the stage work first.'
            }
          </div>
        </div>
      )}
      
      {/* End of conditional rendering for stage selection */}
      </>
      )}
      
      {/* Show message when no stage is selected in stage selector mode */}
      {showStageSelector && !selectedStage && (
        <div className="no-stage-selected">
          <div className="info-message">
            📋 Please select a construction stage to request payment
          </div>
        </div>
      )}
    </div>
  );
};

export default StagePaymentRequest;