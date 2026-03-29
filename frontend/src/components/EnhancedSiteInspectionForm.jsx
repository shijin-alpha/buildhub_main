import React, { useState, useEffect, useCallback } from 'react';
import '../styles/EnhancedSiteInspectionForm.css';

// Lightweight icon component
const Icon = ({ name, size = 20, stroke = 1.8, color = 'currentColor' }) => {
  const common = { width: size, height: size, viewBox: '0 0 24 24', fill: 'none', stroke: color, strokeWidth: stroke, strokeLinecap: 'round', strokeLinejoin: 'round' };
  switch (name) {
    case 'check-circle':
      return (<svg {...common}><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>);
    case 'x-circle':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>);
    case 'alert-triangle':
      return (<svg {...common}><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>);
    case 'camera':
      return (<svg {...common}><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>);
    case 'map-pin':
      return (<svg {...common}><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>);
    case 'save':
      return (<svg {...common}><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17,21 17,13 7,13 7,21"/><polyline points="7,3 7,8 15,8"/></svg>);
    case 'upload':
      return (<svg {...common}><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,15 17,10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>);
    case 'plus':
      return (<svg {...common}><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>);
    case 'minus':
      return (<svg {...common}><line x1="5" y1="12" x2="19" y2="12"/></svg>);
    case 'info':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>);
    default:
      return null;
  }
};

const EnhancedSiteInspectionForm = ({ project, onSubmit, onCancel }) => {
  // Form state with auto-population
  const [inspectionForm, setInspectionForm] = useState({
    // Auto-populated fields
    inspection_date: new Date().toISOString().split('T')[0],
    inspection_time: new Date().toTimeString().slice(0, 5),
    inspection_stage: project?.actual_current_stage || '',
    project_id: project?.id || '',
    
    // Core inspection fields
    inspection_type: 'routine',
    stage_approval_decision: 'pending',
    stage_approval_notes: '',
    overall_status: 'pending',
    quality_score: '',
    
    // Site conditions (minimal required)
    weather_conditions: '',
    temperature: '',
    site_accessibility: 'good',
    
    // Safety assessment (critical for approval)
    safety_compliance: 'compliant',
    safety_equipment_available: 'yes',
    safety_violations_found: 'no',
    
    // Quality assessment
    structural_integrity: 'satisfactory',
    workmanship_quality: 'good',
    code_compliance: 'compliant',
    
    // Issues and actions
    issues_identified: '',
    corrective_actions_required: '',
    corrective_actions_deadline: '',
    
    // Follow-up
    follow_up_required: 'no',
    reinspection_required: false,
    reinspection_date: '',
    next_inspection_date: '',
    
    // Documentation
    notes: '',
    recommendations: '',
    inspector_signature: '',
    homeowner_notified: 'pending'
  });

  // Stage-specific checklist items
  const [checklistItems, setChecklistItems] = useState([]);
  const [stageTemplates, setStageTemplates] = useState([]);
  
  // Form state management
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [isDraft, setIsDraft] = useState(false);
  const [criticalFailures, setCriticalFailures] = useState([]);
  const [photosRequired, setPhotosRequired] = useState(false);
  const [uploadedPhotos, setUploadedPhotos] = useState([]);
  const [validationErrors, setValidationErrors] = useState({});

  // Stage-aware form sections
  const getVisibleSections = useCallback((stage) => {
    const stageSections = {
      'Site Preparation': ['basic', 'site_conditions', 'safety', 'environmental'],
      'Foundation': ['basic', 'site_conditions', 'foundation', 'safety', 'quality'],
      'Structure': ['basic', 'structural', 'safety', 'quality', 'progress'],
      'Brickwork': ['basic', 'construction', 'safety', 'quality'],
      'Roofing': ['basic', 'roofing', 'safety', 'quality', 'waterproofing'],
      'Electrical': ['basic', 'electrical', 'safety', 'code_compliance'],
      'Plumbing': ['basic', 'plumbing', 'safety', 'code_compliance'],
      'Finishing': ['basic', 'finishing', 'quality', 'final_checks'],
      'Final Inspection': ['basic', 'overall_quality', 'code_compliance', 'documentation']
    };
    return stageSections[stage] || ['basic'];
  }, []);

  // Load stage-specific checklist templates
  useEffect(() => {
    const loadStageTemplates = async () => {
      if (!inspectionForm.inspection_stage) return;
      
      try {
        const response = await fetch(`/buildhub/backend/api/inspector/get_stage_templates.php?stage=${encodeURIComponent(inspectionForm.inspection_stage)}`, {
          method: 'GET',
          credentials: 'include'
        });
        
        const result = await response.json();
        if (result.success) {
          setStageTemplates(result.templates);
          // Initialize checklist items from templates
          const initialItems = result.templates.map(template => ({
            id: `template_${template.id}`,
            category: template.category,
            item_description: template.item_description,
            status: 'pending',
            notes: '',
            priority: template.priority,
            is_mandatory: template.is_mandatory,
            order_sequence: template.order_sequence
          }));
          setChecklistItems(initialItems.sort((a, b) => a.order_sequence - b.order_sequence));
        }
      } catch (error) {
        console.error('Error loading stage templates:', error);
      }
    };

    loadStageTemplates();
  }, [inspectionForm.inspection_stage]);

  // Monitor critical failures and photo requirements
  useEffect(() => {
    const failures = checklistItems.filter(item => 
      item.priority === 'critical' && item.status === 'fail'
    );
    setCriticalFailures(failures);

    // Determine if photos are required
    const requiresPhotos = 
      inspectionForm.stage_approval_decision === 'rejected' ||
      failures.length > 0 ||
      inspectionForm.issues_identified.trim().length > 0 ||
      inspectionForm.safety_violations_found !== 'no';
    
    setPhotosRequired(requiresPhotos);
  }, [checklistItems, inspectionForm.stage_approval_decision, inspectionForm.issues_identified, inspectionForm.safety_violations_found]);

  // Auto-save draft functionality
  useEffect(() => {
    const autoSave = setTimeout(() => {
      if (isDraft) {
        saveDraft();
      }
    }, 30000); // Auto-save every 30 seconds

    return () => clearTimeout(autoSave);
  }, [inspectionForm, checklistItems, isDraft]);

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setInspectionForm(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
    setIsDraft(true);
    
    // Clear validation error for this field
    if (validationErrors[name]) {
      setValidationErrors(prev => ({ ...prev, [name]: null }));
    }
  };

  const handleChecklistChange = (index, field, value) => {
    setChecklistItems(prev => {
      const updated = [...prev];
      updated[index] = { ...updated[index], [field]: value };
      return updated;
    });
    setIsDraft(true);
  };

  const addCustomChecklistItem = () => {
    const newItem = {
      id: `custom_${Date.now()}`,
      category: 'Other',
      item_description: '',
      status: 'pending',
      notes: '',
      priority: 'medium',
      is_mandatory: false,
      order_sequence: checklistItems.length + 1
    };
    setChecklistItems(prev => [...prev, newItem]);
  };

  const removeChecklistItem = (index) => {
    setChecklistItems(prev => prev.filter((_, i) => i !== index));
  };

  // Validation logic
  const validateForm = () => {
    const errors = {};
    
    // Required fields validation
    if (!inspectionForm.inspection_date) errors.inspection_date = 'Inspection date is required';
    if (!inspectionForm.inspection_stage) errors.inspection_stage = 'Construction stage is required';
    if (!inspectionForm.stage_approval_decision || inspectionForm.stage_approval_decision === 'pending') {
      errors.stage_approval_decision = 'Stage approval decision is required';
    }
    
    // Critical failure validation
    if (criticalFailures.length > 0 && inspectionForm.stage_approval_decision === 'approved') {
      errors.stage_approval_decision = 'Cannot approve stage with critical failures';
    }
    
    // Corrective actions validation
    if (inspectionForm.stage_approval_decision === 'rejected' && !inspectionForm.corrective_actions_required.trim()) {
      errors.corrective_actions_required = 'Corrective actions required for rejected inspections';
    }
    
    // Photo validation
    if (photosRequired && uploadedPhotos.length === 0) {
      errors.photos = 'Photos are required for this inspection due to issues identified';
    }
    
    // Reinspection date validation
    if (inspectionForm.reinspection_required && !inspectionForm.reinspection_date) {
      errors.reinspection_date = 'Reinspection date required when reinspection is needed';
    }

    setValidationErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const saveDraft = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/inspector/save_inspection_draft.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ...inspectionForm,
          checklist_items: checklistItems,
          is_draft: true
        })
      });
      
      const result = await response.json();
      if (result.success) {
        setIsDraft(false);
      }
    } catch (error) {
      console.error('Error saving draft:', error);
    }
  };

  const handlePhotoUpload = async (files, linkedItemId = null) => {
    const formData = new FormData();
    Array.from(files).forEach(file => {
      formData.append('photos[]', file);
    });
    
    if (linkedItemId) {
      formData.append('linked_item_id', linkedItemId);
    }
    
    // Get GPS location if available
    if (navigator.geolocation) {
      try {
        const position = await new Promise((resolve, reject) => {
          navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
          });
        });
        
        formData.append('latitude', position.coords.latitude);
        formData.append('longitude', position.coords.longitude);
        formData.append('accuracy', position.coords.accuracy);
      } catch (error) {
        console.warn('Could not get GPS location:', error);
      }
    }

    try {
      const response = await fetch('/buildhub/backend/api/inspector/upload_inspection_photos.php', {
        method: 'POST',
        credentials: 'include',
        body: formData
      });
      
      const result = await response.json();
      if (result.success) {
        setUploadedPhotos(prev => [...prev, ...result.photos]);
      } else {
        setError('Failed to upload photos: ' + result.message);
      }
    } catch (error) {
      setError('Error uploading photos: ' + error.message);
    }
  };

  const calculateCompletenessScore = () => {
    let score = 0;
    let maxScore = 100;
    
    // Basic information (20%)
    if (inspectionForm.inspection_date) score += 5;
    if (inspectionForm.inspection_stage) score += 5;
    if (inspectionForm.stage_approval_decision !== 'pending') score += 10;
    
    // Stage-specific checks (40%)
    const completedChecks = checklistItems.filter(item => item.status !== 'pending').length;
    const totalChecks = checklistItems.length;
    if (totalChecks > 0) {
      score += (completedChecks / totalChecks) * 40;
    }
    
    // Safety compliance (20%)
    if (inspectionForm.safety_compliance === 'compliant') score += 20;
    else if (inspectionForm.safety_compliance === 'partial') score += 10;
    
    // Quality assessment (15%)
    if (inspectionForm.quality_score) score += 10;
    if (inspectionForm.workmanship_quality !== 'poor') score += 5;
    
    // Documentation (5%)
    if (inspectionForm.notes.trim()) score += 2.5;
    if (inspectionForm.recommendations.trim()) score += 2.5;
    
    return Math.round(score);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) {
      setError('Please correct the validation errors before submitting');
      return;
    }
    
    setLoading(true);
    setError('');
    setSuccess('');

    try {
      const completenessScore = calculateCompletenessScore();
      
      const submissionData = {
        ...inspectionForm,
        checklist_items: checklistItems.filter(item => 
          item.item_description.trim() && item.category
        ),
        critical_failures_count: criticalFailures.length,
        requires_photos: photosRequired,
        photos_uploaded: uploadedPhotos.length > 0,
        inspection_completeness_score: completenessScore,
        uploaded_photos: uploadedPhotos
      };

      const response = await fetch('/buildhub/backend/api/inspector/create_enhanced_inspection_report.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(submissionData)
      });

      const result = await response.json();
      
      if (result.success) {
        setSuccess('Enhanced inspection report created successfully!');
        setIsDraft(false);
        
        // Call parent callback
        if (onSubmit) {
          onSubmit(result.inspection_report_id);
        }
        
        // Reset form after successful submission
        setTimeout(() => {
          if (onCancel) onCancel();
        }, 2000);
      } else {
        setError(result.message || 'Failed to create inspection report');
      }
    } catch (error) {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const visibleSections = getVisibleSections(inspectionForm.inspection_stage);

  return (
    <div className="enhanced-inspection-form">
      <div className="form-header">
        <h2>Enhanced Site Inspection Report</h2>
        <div className="form-meta">
          <span className="project-name">{project?.project_name}</span>
          <span className="stage-indicator">Stage: {inspectionForm.inspection_stage}</span>
          <span className="completeness-score">
            Completeness: {calculateCompletenessScore()}%
          </span>
        </div>
      </div>

      {/* Critical Failures Alert */}
      {criticalFailures.length > 0 && (
        <div className="critical-alert">
          <Icon name="alert-triangle" color="#ef4444" />
          <div>
            <strong>Critical Failures Detected</strong>
            <p>{criticalFailures.length} critical checklist items have failed. Stage approval is blocked until resolved.</p>
          </div>
        </div>
      )}

      {/* Photo Requirement Alert */}
      {photosRequired && (
        <div className="photo-requirement-alert">
          <Icon name="camera" color="#f59e0b" />
          <div>
            <strong>Photo Evidence Required</strong>
            <p>This inspection requires photo documentation due to identified issues or failures.</p>
          </div>
        </div>
      )}

      {error && (
        <div className="error-message">
          <Icon name="x-circle" color="#ef4444" />
          <span>{error}</span>
        </div>
      )}

      {success && (
        <div className="success-message">
          <Icon name="check-circle" color="#10b981" />
          <span>{success}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className="inspection-form">
        {/* Basic Inspection Information - Always Visible */}
        <div className="form-section">
          <h3>
            <Icon name="info" size={18} />
            Basic Inspection Information
          </h3>
          <div className="form-grid">
            <div className="form-group">
              <label htmlFor="inspection_date">Inspection Date *</label>
              <input
                type="date"
                id="inspection_date"
                name="inspection_date"
                value={inspectionForm.inspection_date}
                onChange={handleInputChange}
                required
                className={validationErrors.inspection_date ? 'error' : ''}
              />
              {validationErrors.inspection_date && (
                <span className="error-text">{validationErrors.inspection_date}</span>
              )}
            </div>

            <div className="form-group">
              <label htmlFor="inspection_time">Inspection Time</label>
              <input
                type="time"
                id="inspection_time"
                name="inspection_time"
                value={inspectionForm.inspection_time}
                onChange={handleInputChange}
              />
            </div>

            <div className="form-group">
              <label htmlFor="inspection_stage">Construction Stage *</label>
              <select
                id="inspection_stage"
                name="inspection_stage"
                value={inspectionForm.inspection_stage}
                onChange={handleInputChange}
                required
                className={validationErrors.inspection_stage ? 'error' : ''}
              >
                <option value="">Select Stage</option>
                <option value="Site Preparation">Site Preparation</option>
                <option value="Foundation">Foundation</option>
                <option value="Structure">Structure</option>
                <option value="Brickwork">Brickwork</option>
                <option value="Roofing">Roofing</option>
                <option value="Electrical">Electrical</option>
                <option value="Plumbing">Plumbing</option>
                <option value="Finishing">Finishing</option>
                <option value="Final Inspection">Final Inspection</option>
              </select>
              {validationErrors.inspection_stage && (
                <span className="error-text">{validationErrors.inspection_stage}</span>
              )}
            </div>

            <div className="form-group">
              <label htmlFor="inspection_type">Inspection Type</label>
              <select
                id="inspection_type"
                name="inspection_type"
                value={inspectionForm.inspection_type}
                onChange={handleInputChange}
              >
                <option value="routine">Routine</option>
                <option value="milestone">Milestone</option>
                <option value="quality">Quality</option>
                <option value="safety">Safety</option>
                <option value="final">Final</option>
              </select>
            </div>
          </div>
        </div>

        {/* Stage Approval Decision - Critical Section */}
        <div className="form-section critical-section">
          <h3>
            <Icon name="check-circle" size={18} />
            Stage Approval Decision *
          </h3>
          <div className="approval-section">
            <div className="form-group">
              <label htmlFor="stage_approval_decision">Approval Decision *</label>
              <select
                id="stage_approval_decision"
                name="stage_approval_decision"
                value={inspectionForm.stage_approval_decision}
                onChange={handleInputChange}
                required
                className={validationErrors.stage_approval_decision ? 'error' : ''}
                disabled={criticalFailures.length > 0}
              >
                <option value="pending">Pending Decision</option>
                <option value="approved">Approved - Stage Complete</option>
                <option value="rejected">Rejected - Major Issues</option>
                <option value="requires_reinspection">Requires Re-inspection</option>
              </select>
              {validationErrors.stage_approval_decision && (
                <span className="error-text">{validationErrors.stage_approval_decision}</span>
              )}
              {criticalFailures.length > 0 && (
                <span className="warning-text">
                  Approval blocked due to {criticalFailures.length} critical failure(s)
                </span>
              )}
            </div>

            <div className="form-group full-width">
              <label htmlFor="stage_approval_notes">Approval Notes</label>
              <textarea
                id="stage_approval_notes"
                name="stage_approval_notes"
                rows="3"
                value={inspectionForm.stage_approval_notes}
                onChange={handleInputChange}
                placeholder="Provide detailed notes for your approval decision..."
              />
            </div>

            <div className="form-group">
              <label htmlFor="quality_score">Quality Score (1-10)</label>
              <input
                type="number"
                id="quality_score"
                name="quality_score"
                min="1"
                max="10"
                step="0.1"
                value={inspectionForm.quality_score}
                onChange={handleInputChange}
                placeholder="Rate overall quality"
              />
            </div>
          </div>
        </div>

        {/* Stage-Specific Checklist */}
        <div className="form-section">
          <h3>
            <Icon name="check-circle" size={18} />
            Stage-Specific Inspection Checklist
          </h3>
          <div className="checklist-container">
            {checklistItems.map((item, index) => (
              <div key={item.id} className={`checklist-item priority-${item.priority}`}>
                <div className="checklist-header">
                  <div className="item-info">
                    <span className="category-badge">{item.category}</span>
                    <span className={`priority-badge priority-${item.priority}`}>
                      {item.priority.toUpperCase()}
                    </span>
                    {item.is_mandatory && <span className="mandatory-badge">MANDATORY</span>}
                  </div>
                  <div className="item-controls">
                    <select
                      value={item.status}
                      onChange={(e) => handleChecklistChange(index, 'status', e.target.value)}
                      className={`status-select status-${item.status}`}
                    >
                      <option value="pending">Pending</option>
                      <option value="pass">Pass</option>
                      <option value="fail">Fail</option>
                      <option value="na">N/A</option>
                    </select>
                    {!item.is_mandatory && (
                      <button
                        type="button"
                        onClick={() => removeChecklistItem(index)}
                        className="remove-item-btn"
                        title="Remove item"
                      >
                        <Icon name="minus" size={16} />
                      </button>
                    )}
                  </div>
                </div>
                
                <div className="item-description">
                  {item.is_mandatory ? (
                    <span>{item.item_description}</span>
                  ) : (
                    <input
                      type="text"
                      value={item.item_description}
                      onChange={(e) => handleChecklistChange(index, 'item_description', e.target.value)}
                      placeholder="Item description..."
                    />
                  )}
                </div>
                
                <textarea
                  value={item.notes}
                  onChange={(e) => handleChecklistChange(index, 'notes', e.target.value)}
                  placeholder="Notes and observations..."
                  rows="2"
                  className="item-notes"
                />
                
                {/* Photo upload for failed items */}
                {item.status === 'fail' && (
                  <div className="item-photo-upload">
                    <label>Evidence Photo Required:</label>
                    <input
                      type="file"
                      accept="image/*"
                      multiple
                      onChange={(e) => handlePhotoUpload(e.target.files, item.id)}
                      className="photo-input"
                    />
                  </div>
                )}
              </div>
            ))}
            
            <button
              type="button"
              onClick={addCustomChecklistItem}
              className="add-checklist-item-btn"
            >
              <Icon name="plus" size={16} />
              Add Custom Checklist Item
            </button>
          </div>
        </div>

        {/* Conditional Sections Based on Stage */}
        {visibleSections.includes('safety') && (
          <div className="form-section">
            <h3>Safety Assessment</h3>
            <div className="form-grid">
              <div className="form-group">
                <label htmlFor="safety_compliance">Safety Compliance *</label>
                <select
                  id="safety_compliance"
                  name="safety_compliance"
                  value={inspectionForm.safety_compliance}
                  onChange={handleInputChange}
                  required
                >
                  <option value="compliant">Compliant</option>
                  <option value="partial">Partial Compliance</option>
                  <option value="non_compliant">Non-Compliant</option>
                </select>
              </div>

              <div className="form-group">
                <label htmlFor="safety_violations_found">Safety Violations</label>
                <select
                  id="safety_violations_found"
                  name="safety_violations_found"
                  value={inspectionForm.safety_violations_found}
                  onChange={handleInputChange}
                >
                  <option value="no">No Violations</option>
                  <option value="minor">Minor Violations</option>
                  <option value="major">Major Violations</option>
                </select>
              </div>
            </div>
          </div>
        )}

        {visibleSections.includes('quality') && (
          <div className="form-section">
            <h3>Quality Assessment</h3>
            <div className="form-grid">
              <div className="form-group">
                <label htmlFor="workmanship_quality">Workmanship Quality</label>
                <select
                  id="workmanship_quality"
                  name="workmanship_quality"
                  value={inspectionForm.workmanship_quality}
                  onChange={handleInputChange}
                >
                  <option value="excellent">Excellent</option>
                  <option value="good">Good</option>
                  <option value="fair">Fair</option>
                  <option value="poor">Poor</option>
                </select>
              </div>

              <div className="form-group">
                <label htmlFor="code_compliance">Code Compliance</label>
                <select
                  id="code_compliance"
                  name="code_compliance"
                  value={inspectionForm.code_compliance}
                  onChange={handleInputChange}
                >
                  <option value="compliant">Compliant</option>
                  <option value="partial">Partial</option>
                  <option value="non_compliant">Non-Compliant</option>
                  <option value="pending_verification">Pending Verification</option>
                </select>
              </div>
            </div>
          </div>
        )}

        {/* Issues and Corrective Actions */}
        <div className="form-section">
          <h3>Issues and Corrective Actions</h3>
          <div className="form-grid">
            <div className="form-group full-width">
              <label htmlFor="issues_identified">Issues Identified</label>
              <textarea
                id="issues_identified"
                name="issues_identified"
                rows="4"
                value={inspectionForm.issues_identified}
                onChange={handleInputChange}
                placeholder="Detail any issues, defects, or concerns identified..."
              />
            </div>

            {(inspectionForm.stage_approval_decision === 'rejected' || 
              inspectionForm.issues_identified.trim()) && (
              <>
                <div className="form-group full-width">
                  <label htmlFor="corrective_actions_required">
                    Corrective Actions Required *
                  </label>
                  <textarea
                    id="corrective_actions_required"
                    name="corrective_actions_required"
                    rows="4"
                    value={inspectionForm.corrective_actions_required}
                    onChange={handleInputChange}
                    placeholder="Specify required corrective actions..."
                    required={inspectionForm.stage_approval_decision === 'rejected'}
                    className={validationErrors.corrective_actions_required ? 'error' : ''}
                  />
                  {validationErrors.corrective_actions_required && (
                    <span className="error-text">{validationErrors.corrective_actions_required}</span>
                  )}
                </div>

                <div className="form-group">
                  <label htmlFor="corrective_actions_deadline">Deadline for Corrections</label>
                  <input
                    type="date"
                    id="corrective_actions_deadline"
                    name="corrective_actions_deadline"
                    value={inspectionForm.corrective_actions_deadline}
                    onChange={handleInputChange}
                    min={new Date().toISOString().split('T')[0]}
                  />
                </div>
              </>
            )}
          </div>
        </div>

        {/* Photo Evidence Section */}
        {photosRequired && (
          <div className="form-section photo-section">
            <h3>
              <Icon name="camera" size={18} />
              Photo Evidence Required
            </h3>
            <div className="photo-upload-area">
              <input
                type="file"
                accept="image/*"
                multiple
                onChange={(e) => handlePhotoUpload(e.target.files)}
                className="photo-input"
                id="evidence-photos"
              />
              <label htmlFor="evidence-photos" className="photo-upload-label">
                <Icon name="upload" size={24} />
                <span>Upload Evidence Photos</span>
                <small>GPS location will be automatically captured</small>
              </label>
              
              {uploadedPhotos.length > 0 && (
                <div className="uploaded-photos">
                  <h4>Uploaded Photos ({uploadedPhotos.length})</h4>
                  <div className="photo-grid">
                    {uploadedPhotos.map((photo, index) => (
                      <div key={index} className="photo-thumbnail">
                        <img src={photo.url} alt={`Evidence ${index + 1}`} />
                        <div className="photo-info">
                          <small>{photo.caption || 'Evidence photo'}</small>
                          {photo.latitude && (
                            <small>
                              <Icon name="map-pin" size={12} />
                              GPS: {photo.latitude.toFixed(6)}, {photo.longitude.toFixed(6)}
                            </small>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
              
              {validationErrors.photos && (
                <span className="error-text">{validationErrors.photos}</span>
              )}
            </div>
          </div>
        )}

        {/* Follow-up and Reinspection */}
        <div className="form-section">
          <h3>Follow-up and Reinspection</h3>
          <div className="form-grid">
            <div className="form-group">
              <label htmlFor="follow_up_required">Follow-up Required</label>
              <select
                id="follow_up_required"
                name="follow_up_required"
                value={inspectionForm.follow_up_required}
                onChange={handleInputChange}
              >
                <option value="no">No</option>
                <option value="yes">Yes</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>

            <div className="form-group">
              <label>
                <input
                  type="checkbox"
                  name="reinspection_required"
                  checked={inspectionForm.reinspection_required}
                  onChange={handleInputChange}
                />
                Reinspection Required
              </label>
            </div>

            {inspectionForm.reinspection_required && (
              <div className="form-group">
                <label htmlFor="reinspection_date">Reinspection Date *</label>
                <input
                  type="date"
                  id="reinspection_date"
                  name="reinspection_date"
                  value={inspectionForm.reinspection_date}
                  onChange={handleInputChange}
                  min={new Date().toISOString().split('T')[0]}
                  required={inspectionForm.reinspection_required}
                  className={validationErrors.reinspection_date ? 'error' : ''}
                />
                {validationErrors.reinspection_date && (
                  <span className="error-text">{validationErrors.reinspection_date}</span>
                )}
              </div>
            )}

            <div className="form-group">
              <label htmlFor="next_inspection_date">Next Scheduled Inspection</label>
              <input
                type="date"
                id="next_inspection_date"
                name="next_inspection_date"
                value={inspectionForm.next_inspection_date}
                onChange={handleInputChange}
                min={new Date().toISOString().split('T')[0]}
              />
            </div>
          </div>
        </div>

        {/* Documentation and Notes */}
        <div className="form-section">
          <h3>Documentation and Notes</h3>
          <div className="form-grid">
            <div className="form-group full-width">
              <label htmlFor="notes">Inspector Notes</label>
              <textarea
                id="notes"
                name="notes"
                rows="4"
                value={inspectionForm.notes}
                onChange={handleInputChange}
                placeholder="Additional observations, notes, and comments..."
              />
            </div>

            <div className="form-group full-width">
              <label htmlFor="recommendations">Recommendations</label>
              <textarea
                id="recommendations"
                name="recommendations"
                rows="3"
                value={inspectionForm.recommendations}
                onChange={handleInputChange}
                placeholder="Recommendations for improvements or next steps..."
              />
            </div>

            <div className="form-group">
              <label htmlFor="inspector_signature">Inspector Signature/ID</label>
              <input
                type="text"
                id="inspector_signature"
                name="inspector_signature"
                value={inspectionForm.inspector_signature}
                onChange={handleInputChange}
                placeholder="Digital signature or inspector ID"
              />
            </div>

            <div className="form-group">
              <label htmlFor="homeowner_notified">Homeowner Notification</label>
              <select
                id="homeowner_notified"
                name="homeowner_notified"
                value={inspectionForm.homeowner_notified}
                onChange={handleInputChange}
              >
                <option value="pending">Pending</option>
                <option value="yes">Notified</option>
                <option value="no">Not Notified</option>
              </select>
            </div>
          </div>
        </div>

        {/* Form Actions */}
        <div className="form-actions">
          <button
            type="button"
            onClick={saveDraft}
            className="draft-btn"
            disabled={loading}
          >
            <Icon name="save" size={16} />
            Save Draft
          </button>
          
          <button
            type="button"
            onClick={onCancel}
            className="cancel-btn"
            disabled={loading}
          >
            Cancel
          </button>
          
          <button
            type="submit"
            className="submit-btn"
            disabled={loading || (photosRequired && uploadedPhotos.length === 0)}
          >
            {loading ? 'Creating Report...' : 'Submit Inspection Report'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default EnhancedSiteInspectionForm;