import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/StagePaymentWithdrawals.css';

const StagePaymentWithdrawals = ({ 
  projectId, 
  contractorId, 
  totalProjectCost = 0,
  onWithdrawalRequested 
}) => {
  const toast = useToast();
  
  const [stagePayments, setStagePayments] = useState([]);
  const [paymentSummary, setPaymentSummary] = useState({});
  const [loading, setLoading] = useState(true);
  const [selectedStage, setSelectedStage] = useState(null);
  const [showWithdrawalForm, setShowWithdrawalForm] = useState(false);
  
  const [withdrawalForm, setWithdrawalForm] = useState({
    stage_name: '',
    withdrawal_amount: '',
    work_completed_percentage: '',
    work_description: '',
    materials_used: '',
    labor_details: '',
    quality_assurance: false,
    safety_compliance: false,
    photos_uploaded: false,
    contractor_notes: ''
  });

  // Standard construction stages with typical payment percentages
  const constructionStages = [
    {
      name: 'Foundation',
      typical_percentage: 20,
      description: 'Site preparation, excavation, and foundation work',
      key_deliverables: ['Site clearance', 'Excavation', 'Foundation laying', 'Plinth beam'],
      typical_duration: '15-20 days'
    },
    {
      name: 'Structure',
      typical_percentage: 25,
      description: 'Column, beam, and slab construction',
      key_deliverables: ['Column construction', 'Beam work', 'Slab casting', 'Structural framework'],
      typical_duration: '25-30 days'
    },
    {
      name: 'Brickwork',
      typical_percentage: 15,
      description: 'Wall construction and masonry work',
      key_deliverables: ['Wall construction', 'Door/window frames', 'Plastering base'],
      typical_duration: '20-25 days'
    },
    {
      name: 'Roofing',
      typical_percentage: 15,
      description: 'Roof construction and waterproofing',
      key_deliverables: ['Roof structure', 'Waterproofing', 'Insulation', 'Drainage'],
      typical_duration: '10-15 days'
    },
    {
      name: 'Electrical',
      typical_percentage: 8,
      description: 'Electrical wiring and connections',
      key_deliverables: ['Wiring installation', 'Switch boards', 'Light fittings', 'Power connections'],
      typical_duration: '10-12 days'
    },
    {
      name: 'Plumbing',
      typical_percentage: 7,
      description: 'Plumbing installation and testing',
      key_deliverables: ['Pipe installation', 'Fixtures', 'Water connections', 'Drainage system'],
      typical_duration: '8-10 days'
    },
    {
      name: 'Finishing',
      typical_percentage: 10,
      description: 'Final finishing and handover',
      key_deliverables: ['Painting', 'Flooring', 'Final fixtures', 'Cleanup'],
      typical_duration: '15-20 days'
    }
  ];

  useEffect(() => {
    if (projectId) {
      loadStagePayments();
    }
  }, [projectId]);

  const loadStagePayments = async () => {
    try {
      setLoading(true);
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_stage_payment_breakdown.php?project_id=${projectId}`,
        { credentials: 'include' }
      );
      
      const data = await response.json();
      
      if (data.success) {
        setStagePayments(data.data.stages || []);
        setPaymentSummary(data.data.summary || {});
      } else {
        // If API doesn't exist, create mock data based on construction stages
        const mockStages = constructionStages.map(stage => ({
          stage_name: stage.name,
          typical_percentage: stage.typical_percentage,
          typical_amount: Math.round((totalProjectCost * stage.typical_percentage) / 100),
          description: stage.description,
          key_deliverables: stage.key_deliverables,
          typical_duration: stage.typical_duration,
          status: 'not_started', // not_started, in_progress, completed, payment_requested, paid
          completion_percentage: 0,
          amount_requested: 0,
          amount_paid: 0,
          can_request_payment: false,
          last_payment_date: null,
          pending_amount: 0
        }));
        
        setStagePayments(mockStages);
        setPaymentSummary({
          total_project_cost: totalProjectCost,
          total_paid: 0,
          total_pending: 0,
          total_available: totalProjectCost,
          stages_completed: 0,
          stages_in_progress: 0
        });
      }
    } catch (error) {
      console.error('Error loading stage payments:', error);
      toast.error('Error loading payment information');
    } finally {
      setLoading(false);
    }
  };

  const handleStageSelect = (stage) => {
    setSelectedStage(stage);
    setWithdrawalForm({
      stage_name: stage.stage_name,
      withdrawal_amount: stage.typical_amount.toString(),
      work_completed_percentage: stage.completion_percentage.toString(),
      work_description: '',
      materials_used: '',
      labor_details: '',
      quality_assurance: false,
      safety_compliance: false,
      photos_uploaded: false,
      contractor_notes: ''
    });
  };

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setWithdrawalForm(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleSubmitWithdrawal = async (e) => {
    e.preventDefault();
    
    if (!selectedStage) return;
    
    // Validation
    if (!withdrawalForm.work_description || withdrawalForm.work_description.length < 50) {
      toast.error('Work description must be at least 50 characters');
      return;
    }
    
    if (!withdrawalForm.quality_assurance || !withdrawalForm.safety_compliance) {
      toast.error('Quality assurance and safety compliance must be confirmed');
      return;
    }
    
    try {
      setLoading(true);
      const response = await fetch(
        '/buildhub/backend/api/contractor/submit_stage_withdrawal_request.php',
        {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            project_id: projectId,
            contractor_id: contractorId,
            ...withdrawalForm
          })
        }
      );
      
      const data = await response.json();
      
      if (data.success) {
        toast.success(`Payment withdrawal request submitted for ${selectedStage.stage_name} stage!`);
        setShowWithdrawalForm(false);
        setSelectedStage(null);
        loadStagePayments(); // Refresh data
        
        if (onWithdrawalRequested) {
          onWithdrawalRequested(data.data);
        }
      } else {
        toast.error('Failed to submit withdrawal request: ' + data.message);
      }
    } catch (error) {
      console.error('Error submitting withdrawal request:', error);
      toast.error('Error submitting withdrawal request');
    } finally {
      setLoading(false);
    }
  };

  const getStageStatusIcon = (status) => {
    switch (status) {
      case 'not_started': return '⚪';
      case 'in_progress': return '🟡';
      case 'completed': return '🟢';
      case 'payment_requested': return '💰';
      case 'paid': return '✅';
      default: return '❓';
    }
  };

  const getStageStatusText = (status) => {
    switch (status) {
      case 'not_started': return 'Not Started';
      case 'in_progress': return 'In Progress';
      case 'completed': return 'Completed';
      case 'payment_requested': return 'Payment Requested';
      case 'paid': return 'Paid';
      default: return 'Unknown';
    }
  };

  const canRequestPayment = (stage) => {
    return stage.status === 'completed' && stage.amount_paid < stage.typical_amount;
  };

  if (loading) {
    return (
      <div className="stage-payment-loading">
        <div className="loading-spinner">🔄 Loading stage payment information...</div>
      </div>
    );
  }

  return (
    <div className="stage-payment-withdrawals">
      <div className="withdrawals-header">
        <h3>💰 Stage Payment Withdrawals</h3>
        <p>Request payments for completed construction stages</p>
      </div>

      {/* Payment Summary */}
      <div className="payment-summary-section">
        <div className="summary-cards">
          <div className="summary-card total">
            <div className="card-icon">💼</div>
            <div className="card-content">
              <h4>₹{paymentSummary.total_project_cost?.toLocaleString() || totalProjectCost.toLocaleString()}</h4>
              <p>Total Project Cost</p>
            </div>
          </div>
          
          <div className="summary-card paid">
            <div className="card-icon">✅</div>
            <div className="card-content">
              <h4>₹{paymentSummary.total_paid?.toLocaleString() || '0'}</h4>
              <p>Amount Paid</p>
              <span className="percentage">
                {paymentSummary.total_project_cost ? 
                  ((paymentSummary.total_paid / paymentSummary.total_project_cost) * 100).toFixed(1) : 0}%
              </span>
            </div>
          </div>
          
          <div className="summary-card pending">
            <div className="card-icon">⏳</div>
            <div className="card-content">
              <h4>₹{paymentSummary.total_pending?.toLocaleString() || '0'}</h4>
              <p>Pending Approval</p>
            </div>
          </div>
          
          <div className="summary-card available">
            <div className="card-icon">💰</div>
            <div className="card-content">
              <h4>₹{paymentSummary.total_available?.toLocaleString() || totalProjectCost.toLocaleString()}</h4>
              <p>Available to Request</p>
            </div>
          </div>
        </div>
      </div>

      {/* Construction Stages */}
      <div className="construction-stages-section">
        <h4>🏗️ Construction Stages & Payments</h4>
        
        <div className="stages-grid">
          {stagePayments.map((stage, index) => (
            <div 
              key={stage.stage_name} 
              className={`stage-card ${stage.status} ${selectedStage?.stage_name === stage.stage_name ? 'selected' : ''}`}
            >
              <div className="stage-header">
                <div className="stage-info">
                  <h5>
                    {getStageStatusIcon(stage.status)} {stage.stage_name}
                  </h5>
                  <span className="stage-status">{getStageStatusText(stage.status)}</span>
                </div>
                <div className="stage-percentage">
                  {stage.typical_percentage}%
                </div>
              </div>

              <div className="stage-description">
                <p>{stage.description}</p>
              </div>

              <div className="stage-deliverables">
                <h6>Key Deliverables:</h6>
                <ul>
                  {stage.key_deliverables?.map((deliverable, idx) => (
                    <li key={idx}>{deliverable}</li>
                  ))}
                </ul>
              </div>

              <div className="stage-payment-info">
                <div className="payment-row">
                  <span>Typical Amount:</span>
                  <span className="amount">₹{stage.typical_amount?.toLocaleString()}</span>
                </div>
                <div className="payment-row">
                  <span>Amount Paid:</span>
                  <span className="amount paid">₹{stage.amount_paid?.toLocaleString() || '0'}</span>
                </div>
                {stage.pending_amount > 0 && (
                  <div className="payment-row">
                    <span>Pending:</span>
                    <span className="amount pending">₹{stage.pending_amount.toLocaleString()}</span>
                  </div>
                )}
              </div>

              <div className="stage-progress">
                <div className="progress-info">
                  <span>Completion: {stage.completion_percentage || 0}%</span>
                  <span>Duration: {stage.typical_duration}</span>
                </div>
                <div className="progress-bar">
                  <div 
                    className="progress-fill"
                    style={{ width: `${stage.completion_percentage || 0}%` }}
                  ></div>
                </div>
              </div>

              <div className="stage-actions">
                {canRequestPayment(stage) ? (
                  <button
                    onClick={() => {
                      handleStageSelect(stage);
                      setShowWithdrawalForm(true);
                    }}
                    className="request-payment-btn"
                  >
                    💰 Request Payment
                  </button>
                ) : stage.status === 'payment_requested' ? (
                  <button className="payment-requested-btn" disabled>
                    ⏳ Payment Requested
                  </button>
                ) : stage.status === 'paid' ? (
                  <button className="payment-completed-btn" disabled>
                    ✅ Payment Completed
                  </button>
                ) : (
                  <button 
                    className="stage-info-btn"
                    onClick={() => handleStageSelect(stage)}
                  >
                    📋 View Details
                  </button>
                )}
              </div>

              {stage.last_payment_date && (
                <div className="last-payment">
                  <small>Last payment: {new Date(stage.last_payment_date).toLocaleDateString()}</small>
                </div>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Payment Withdrawal Form Modal */}
      {showWithdrawalForm && selectedStage && (
        <div className="withdrawal-form-modal">
          <div className="modal-overlay" onClick={() => setShowWithdrawalForm(false)}></div>
          <div className="modal-content">
            <div className="modal-header">
              <h4>💰 Request Payment: {selectedStage.stage_name}</h4>
              <button 
                className="close-btn"
                onClick={() => setShowWithdrawalForm(false)}
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleSubmitWithdrawal} className="withdrawal-form">
              <div className="form-section">
                <h5>💵 Payment Details</h5>
                
                <div className="form-row">
                  <div className="form-group">
                    <label htmlFor="withdrawal_amount">Withdrawal Amount (₹) *</label>
                    <input
                      type="number"
                      id="withdrawal_amount"
                      name="withdrawal_amount"
                      value={withdrawalForm.withdrawal_amount}
                      onChange={handleInputChange}
                      min="1"
                      max={selectedStage.typical_amount}
                      step="0.01"
                      required
                    />
                    <small>Maximum: ₹{selectedStage.typical_amount?.toLocaleString()}</small>
                  </div>
                  
                  <div className="form-group">
                    <label htmlFor="work_completed_percentage">Work Completed (%) *</label>
                    <input
                      type="number"
                      id="work_completed_percentage"
                      name="work_completed_percentage"
                      value={withdrawalForm.work_completed_percentage}
                      onChange={handleInputChange}
                      min="0"
                      max="100"
                      step="0.1"
                      required
                    />
                  </div>
                </div>
              </div>

              <div className="form-section">
                <h5>🏗️ Work Details</h5>
                
                <div className="form-group">
                  <label htmlFor="work_description">Work Description *</label>
                  <textarea
                    id="work_description"
                    name="work_description"
                    value={withdrawalForm.work_description}
                    onChange={handleInputChange}
                    placeholder="Describe the work completed for this stage in detail..."
                    rows="4"
                    required
                  />
                  <small>{withdrawalForm.work_description.length}/500 characters (minimum 50 required)</small>
                </div>
                
                <div className="form-group">
                  <label htmlFor="materials_used">Materials Used</label>
                  <textarea
                    id="materials_used"
                    name="materials_used"
                    value={withdrawalForm.materials_used}
                    onChange={handleInputChange}
                    placeholder="List the materials used in this stage..."
                    rows="3"
                  />
                </div>
                
                <div className="form-group">
                  <label htmlFor="labor_details">Labor Details</label>
                  <textarea
                    id="labor_details"
                    name="labor_details"
                    value={withdrawalForm.labor_details}
                    onChange={handleInputChange}
                    placeholder="Details about labor, workers, and supervision..."
                    rows="3"
                  />
                </div>
              </div>

              <div className="form-section">
                <h5>✅ Quality & Compliance</h5>
                
                <div className="checkbox-group">
                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      name="quality_assurance"
                      checked={withdrawalForm.quality_assurance}
                      onChange={handleInputChange}
                      required
                    />
                    Quality assurance checks completed *
                  </label>
                  
                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      name="safety_compliance"
                      checked={withdrawalForm.safety_compliance}
                      onChange={handleInputChange}
                      required
                    />
                    Safety compliance verified *
                  </label>
                  
                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      name="photos_uploaded"
                      checked={withdrawalForm.photos_uploaded}
                      onChange={handleInputChange}
                    />
                    Progress photos uploaded
                  </label>
                </div>
              </div>

              <div className="form-section">
                <h5>📝 Additional Notes</h5>
                
                <div className="form-group">
                  <label htmlFor="contractor_notes">Contractor Notes</label>
                  <textarea
                    id="contractor_notes"
                    name="contractor_notes"
                    value={withdrawalForm.contractor_notes}
                    onChange={handleInputChange}
                    placeholder="Any additional notes for the homeowner..."
                    rows="3"
                  />
                </div>
              </div>

              <div className="form-actions">
                <button
                  type="button"
                  onClick={() => setShowWithdrawalForm(false)}
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
        </div>
      )}

      {/* Selected Stage Details (when not in form mode) */}
      {selectedStage && !showWithdrawalForm && (
        <div className="selected-stage-details">
          <div className="details-header">
            <h4>📋 {selectedStage.stage_name} Stage Details</h4>
            <button 
              className="close-details-btn"
              onClick={() => setSelectedStage(null)}
            >
              ✕
            </button>
          </div>
          
          <div className="details-content">
            <div className="details-grid">
              <div className="detail-section">
                <h5>🏗️ Stage Information</h5>
                <p><strong>Description:</strong> {selectedStage.description}</p>
                <p><strong>Typical Duration:</strong> {selectedStage.typical_duration}</p>
                <p><strong>Typical Cost:</strong> ₹{selectedStage.typical_amount?.toLocaleString()}</p>
                <p><strong>Percentage of Total:</strong> {selectedStage.typical_percentage}%</p>
              </div>
              
              <div className="detail-section">
                <h5>📊 Payment Status</h5>
                <p><strong>Current Status:</strong> {getStageStatusText(selectedStage.status)}</p>
                <p><strong>Completion:</strong> {selectedStage.completion_percentage || 0}%</p>
                <p><strong>Amount Paid:</strong> ₹{selectedStage.amount_paid?.toLocaleString() || '0'}</p>
                {selectedStage.pending_amount > 0 && (
                  <p><strong>Pending Amount:</strong> ₹{selectedStage.pending_amount.toLocaleString()}</p>
                )}
              </div>
            </div>
            
            <div className="deliverables-section">
              <h5>✅ Key Deliverables</h5>
              <ul>
                {selectedStage.key_deliverables?.map((deliverable, idx) => (
                  <li key={idx}>{deliverable}</li>
                ))}
              </ul>
            </div>
            
            {canRequestPayment(selectedStage) && (
              <div className="action-section">
                <button
                  onClick={() => setShowWithdrawalForm(true)}
                  className="request-payment-btn large"
                >
                  💰 Request Payment for {selectedStage.stage_name}
                </button>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default StagePaymentWithdrawals;