import React, { useState, useEffect } from 'react';
import { useNotifications } from '../hooks/useNotifications';
import NotificationToast from './NotificationToast';
import GeoPhotoCapture from './GeoPhotoCapture';
import '../styles/EnhancedConstructionProgress.css';

const EnhancedConstructionProgressUpdate = ({ projectId, onClose, onSubmit }) => {
  const { notifications, removeNotification, showSuccess, showError, showInfo } = useNotifications();
  
  // Form state
  const [formData, setFormData] = useState({
    report_title: '',
    report_description: '',
    work_completed: '',
    work_planned: '',
    completion_percentage: 0,
    milestone_id: '',
    
    // Integration data
    house_plan_updates: {},
    material_usage: {},
    labor_details: {},
    quality_checks: {},
    
    // Geo photos
    geo_photo_ids: [],
    new_geo_photos: []
  });
  
  const [projectData, setProjectData] = useState(null);
  const [milestones, setMilestones] = useState([]);
  const [housePlans, setHousePlans] = useState([]);
  const [loading, setLoading] = useState(false);
  const [showGeoCapture, setShowGeoCapture] = useState(false);
  const [currentStep, setCurrentStep] = useState(1);
  
  // Load project data on mount
  useEffect(() => {
    if (projectId) {
      fetchProjectData();
    }
  }, [projectId]);
  
  const fetchProjectData = async () => {
    try {
      const response = await fetch(`/buildhub/backend/api/project/get_project_overview.php?project_id=${projectId}`);
      const data = await response.json();
      
      if (data.success) {
        setProjectData(data.project);
        setMilestones(data.milestones || []);
        setHousePlans(data.house_plans || []);
        
        // Pre-fill report title with project info
        setFormData(prev => ({
          ...prev,
          report_title: `Progress Report - ${data.project.name} - ${new Date().toLocaleDateString()}`
        }));
      } else {
        showError('Error', 'Failed to load project data');
      }
    } catch (error) {
      showError('Error', 'Failed to fetch project information');
    }
  };
  
  const handleInputChange = (field, value) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));
  };
  
  const handleNestedInputChange = (section, field, value) => {
    setFormData(prev => ({
      ...prev,
      [section]: {
        ...prev[section],
        [field]: value
      }
    }));
  };
  
  const handleGeoPhotoCapture = (photoData) => {
    setFormData(prev => ({
      ...prev,
      new_geo_photos: [...prev.new_geo_photos, photoData]
    }));
    setShowGeoCapture(false);
    showSuccess('Photo Captured', 'Geo-tagged photo added to progress report');
  };
  
  const removeGeoPhoto = (index) => {
    setFormData(prev => ({
      ...prev,
      new_geo_photos: prev.new_geo_photos.filter((_, i) => i !== index)
    }));
  };
  
  const validateStep = (step) => {
    switch (step) {
      case 1:
        return formData.report_title && formData.work_completed;
      case 2:
        return formData.completion_percentage >= 0 && formData.completion_percentage <= 100;
      case 3:
        return true; // Integration data is optional
      default:
        return true;
    }
  };
  
  const nextStep = () => {
    if (validateStep(currentStep)) {
      setCurrentStep(prev => Math.min(prev + 1, 3));
    } else {
      showError('Validation Error', 'Please fill in all required fields');
    }
  };
  
  const prevStep = () => {
    setCurrentStep(prev => Math.max(prev - 1, 1));
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateStep(3)) {
      showError('Validation Error', 'Please complete all required fields');
      return;
    }
    
    setLoading(true);
    
    try {
      // First upload any new geo photos
      const uploadedPhotoIds = [];
      
      for (const photo of formData.new_geo_photos) {
        const photoFormData = new FormData();
        photoFormData.append('photo', photo.file);
        photoFormData.append('project_id', projectId);
        photoFormData.append('milestone_id', formData.milestone_id || '');
        photoFormData.append('latitude', photo.coordinates.latitude);
        photoFormData.append('longitude', photo.coordinates.longitude);
        photoFormData.append('address', photo.address || '');
        photoFormData.append('workflow_context', getCurrentWorkflowContext());
        
        const photoResponse = await fetch('/buildhub/backend/api/contractor/upload_geo_photo.php', {
          method: 'POST',
          body: photoFormData
        });
        
        const photoResult = await photoResponse.json();
        if (photoResult.success) {
          uploadedPhotoIds.push(photoResult.photo_id);
        }
      }
      
      // Combine existing and new photo IDs
      const allGeoPhotoIds = [...formData.geo_photo_ids, ...uploadedPhotoIds];
      
      // Prepare integrated progress report data
      const reportData = {
        project_id: projectId,
        milestone_id: formData.milestone_id || null,
        report_title: formData.report_title,
        report_description: formData.report_description,
        work_completed: formData.work_completed,
        work_planned: formData.work_planned,
        completion_percentage: formData.completion_percentage,
        
        // Integration data
        house_plan_updates: formData.house_plan_updates,
        geo_photo_ids: allGeoPhotoIds,
        material_usage: formData.material_usage,
        labor_details: formData.labor_details,
        quality_checks: formData.quality_checks,
        
        // Metadata
        report_metadata: {
          submission_method: 'enhanced_form',
          integration_features: {
            geo_photos_count: allGeoPhotoIds.length,
            house_plan_referenced: Object.keys(formData.house_plan_updates).length > 0,
            milestone_linked: !!formData.milestone_id
          },
          submission_timestamp: new Date().toISOString()
        }
      };
      
      const response = await fetch('/buildhub/backend/api/contractor/submit_integrated_progress_report.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(reportData)
      });
      
      const result = await response.json();
      
      if (result.success) {
        showSuccess('Success!', 'Integrated progress report submitted successfully!');
        
        // Show integration info
        const integrationInfo = [];
        if (result.integration_features.geo_photos_linked > 0) {
          integrationInfo.push(`${result.integration_features.geo_photos_linked} geo-tagged photos`);
        }
        if (result.integration_features.house_plan_referenced) {
          integrationInfo.push('house plan references');
        }
        if (result.integration_features.milestone_updated) {
          integrationInfo.push('milestone progress updated');
        }
        
        if (integrationInfo.length > 0) {
          showInfo('Integration Features', `Report includes: ${integrationInfo.join(', ')}`);
        }
        
        // Call parent callback
        if (onSubmit) {
          onSubmit(result);
        }
        
        // Close form after delay
        setTimeout(() => {
          if (onClose) onClose();
        }, 3000);
        
      } else {
        showError('Submission Failed', result.message || 'Failed to submit progress report');
      }
      
    } catch (error) {
      showError('Error', 'Network error occurred while submitting report');
    } finally {
      setLoading(false);
    }
  };
  
  const getCurrentWorkflowContext = () => {
    const percentage = formData.completion_percentage;
    if (percentage < 25) return 'foundation';
    if (percentage < 50) return 'structure';
    if (percentage < 75) return 'finishing';
    if (percentage < 100) return 'completion';
    return 'site_survey';
  };
  
  const renderStep1 = () => (
    <div className="form-step">
      <h3>📋 Progress Report Details</h3>
      <p>Document the work completed and planned for this project</p>
      
      <div className="form-grid">
        <div className="form-group full-width">
          <label>Report Title *</label>
          <input
            type="text"
            value={formData.report_title}
            onChange={(e) => handleInputChange('report_title', e.target.value)}
            placeholder="e.g., Weekly Progress Report - Foundation Work"
            required
          />
        </div>
        
        <div className="form-group full-width">
          <label>Report Description</label>
          <textarea
            value={formData.report_description}
            onChange={(e) => handleInputChange('report_description', e.target.value)}
            placeholder="Brief overview of this progress report..."
            rows="3"
          />
        </div>
        
        <div className="form-group full-width">
          <label>Work Completed *</label>
          <textarea
            value={formData.work_completed}
            onChange={(e) => handleInputChange('work_completed', e.target.value)}
            placeholder="Describe the work that has been completed..."
            rows="4"
            required
          />
        </div>
        
        <div className="form-group full-width">
          <label>Work Planned</label>
          <textarea
            value={formData.work_planned}
            onChange={(e) => handleInputChange('work_planned', e.target.value)}
            placeholder="Describe the work planned for the next period..."
            rows="4"
          />
        </div>
        
        <div className="form-group">
          <label>Milestone</label>
          <select
            value={formData.milestone_id}
            onChange={(e) => handleInputChange('milestone_id', e.target.value)}
          >
            <option value="">Select Milestone (Optional)</option>
            {milestones.map(milestone => (
              <option key={milestone.id} value={milestone.id}>
                {milestone.name} ({milestone.phase}) - {milestone.status}
              </option>
            ))}
          </select>
        </div>
        
        <div className="form-group">
          <label>Completion Percentage *</label>
          <div className="percentage-input">
            <input
              type="range"
              min="0"
              max="100"
              value={formData.completion_percentage}
              onChange={(e) => handleInputChange('completion_percentage', parseInt(e.target.value))}
              className="percentage-slider"
            />
            <span className="percentage-display">{formData.completion_percentage}%</span>
          </div>
        </div>
      </div>
    </div>
  );
  
  const renderStep2 = () => (
    <div className="form-step">
      <h3>📍 Geo-Tagged Documentation</h3>
      <p>Add GPS-tagged photos to verify work completion and location</p>
      
      <div className="geo-photo-section">
        <div className="section-header">
          <h4>📸 Construction Photos with GPS</h4>
          <button
            type="button"
            className="btn-capture-photo"
            onClick={() => setShowGeoCapture(true)}
          >
            📍 Capture Geo Photo
          </button>
        </div>
        
        {formData.new_geo_photos.length > 0 && (
          <div className="geo-photos-grid">
            {formData.new_geo_photos.map((photo, index) => (
              <div key={index} className="geo-photo-card">
                <div className="photo-preview">
                  <img 
                    src={photo.preview} 
                    alt={`Geo photo ${index + 1}`}
                    className="photo-thumbnail"
                  />
                  <button
                    type="button"
                    className="remove-photo"
                    onClick={() => removeGeoPhoto(index)}
                  >
                    ×
                  </button>
                </div>
                <div className="photo-info">
                  <div className="coordinates">
                    <span className="lat">📍 {photo.coordinates.latitude.toFixed(6)}</span>
                    <span className="lng">📍 {photo.coordinates.longitude.toFixed(6)}</span>
                  </div>
                  <div className="address">{photo.address || 'Address not available'}</div>
                  <div className="context">Context: {getCurrentWorkflowContext()}</div>
                </div>
              </div>
            ))}
          </div>
        )}
        
        {formData.new_geo_photos.length === 0 && (
          <div className="no-photos">
            <div className="no-photos-icon">📷</div>
            <p>No geo-tagged photos added yet</p>
            <p className="help-text">
              Capture photos with GPS coordinates to document work progress and verify location
            </p>
          </div>
        )}
        
        <div className="integration-info">
          <h4>🔗 Integration Benefits</h4>
          <ul>
            <li>✅ Photos automatically tagged with GPS coordinates</li>
            <li>✅ Linked to project milestones and progress reports</li>
            <li>✅ Provides proof of work completion at correct location</li>
            <li>✅ Enables remote monitoring for homeowners</li>
          </ul>
        </div>
      </div>
    </div>
  );
  
  const renderStep3 = () => (
    <div className="form-step">
      <h3>🔧 Technical Details & Integration</h3>
      <p>Add technical details and link to house plans and materials</p>
      
      <div className="integration-sections">
        {housePlans.length > 0 && (
          <div className="integration-section">
            <h4>🏠 House Plan Updates</h4>
            <p>Reference approved house plans and note any modifications</p>
            
            <div className="house-plans-list">
              {housePlans.map(plan => (
                <div key={plan.id} className="house-plan-item">
                  <div className="plan-info">
                    <h5>{plan.title}</h5>
                    <span className="plan-status">{plan.status}</span>
                  </div>
                  <textarea
                    placeholder={`Notes about ${plan.title}...`}
                    value={formData.house_plan_updates[plan.id] || ''}
                    onChange={(e) => handleNestedInputChange('house_plan_updates', plan.id, e.target.value)}
                    rows="2"
                  />
                </div>
              ))}
            </div>
          </div>
        )}
        
        <div className="integration-section">
          <h4>🧱 Material Usage</h4>
          <div className="material-grid">
            <div className="material-item">
              <label>Cement (bags)</label>
              <input
                type="number"
                placeholder="0"
                value={formData.material_usage.cement || ''}
                onChange={(e) => handleNestedInputChange('material_usage', 'cement', e.target.value)}
              />
            </div>
            <div className="material-item">
              <label>Steel (kg)</label>
              <input
                type="number"
                placeholder="0"
                value={formData.material_usage.steel || ''}
                onChange={(e) => handleNestedInputChange('material_usage', 'steel', e.target.value)}
              />
            </div>
            <div className="material-item">
              <label>Bricks (nos)</label>
              <input
                type="number"
                placeholder="0"
                value={formData.material_usage.bricks || ''}
                onChange={(e) => handleNestedInputChange('material_usage', 'bricks', e.target.value)}
              />
            </div>
            <div className="material-item">
              <label>Sand (cubic ft)</label>
              <input
                type="number"
                placeholder="0"
                value={formData.material_usage.sand || ''}
                onChange={(e) => handleNestedInputChange('material_usage', 'sand', e.target.value)}
              />
            </div>
          </div>
        </div>
        
        <div className="integration-section">
          <h4>👷 Labor Details</h4>
          <div className="labor-grid">
            <div className="labor-item">
              <label>Skilled Workers</label>
              <input
                type="number"
                placeholder="0"
                value={formData.labor_details.skilled_workers || ''}
                onChange={(e) => handleNestedInputChange('labor_details', 'skilled_workers', e.target.value)}
              />
            </div>
            <div className="labor-item">
              <label>Unskilled Workers</label>
              <input
                type="number"
                placeholder="0"
                value={formData.labor_details.unskilled_workers || ''}
                onChange={(e) => handleNestedInputChange('labor_details', 'unskilled_workers', e.target.value)}
              />
            </div>
            <div className="labor-item">
              <label>Work Hours</label>
              <input
                type="number"
                placeholder="0"
                value={formData.labor_details.work_hours || ''}
                onChange={(e) => handleNestedInputChange('labor_details', 'work_hours', e.target.value)}
              />
            </div>
            <div className="labor-item">
              <label>Overtime Hours</label>
              <input
                type="number"
                placeholder="0"
                value={formData.labor_details.overtime_hours || ''}
                onChange={(e) => handleNestedInputChange('labor_details', 'overtime_hours', e.target.value)}
              />
            </div>
          </div>
        </div>
        
        <div className="integration-section">
          <h4>✅ Quality Checks</h4>
          <div className="quality-checks">
            {[
              'Foundation Level Check',
              'Material Quality Verification',
              'Structural Alignment',
              'Safety Compliance',
              'Building Code Adherence'
            ].map(check => (
              <label key={check} className="quality-check-item">
                <input
                  type="checkbox"
                  checked={formData.quality_checks[check] || false}
                  onChange={(e) => handleNestedInputChange('quality_checks', check, e.target.checked)}
                />
                {check}
              </label>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
  
  if (!projectData) {
    return (
      <div className="enhanced-progress-form-overlay">
        <div className="loading-container">
          <div className="loading-spinner"></div>
          <p>Loading project data...</p>
        </div>
      </div>
    );
  }
  
  return (
    <div className="enhanced-progress-form-overlay">
      <div className="enhanced-progress-form">
        <div className="form-header">
          <h2>📊 Enhanced Progress Report</h2>
          <div className="project-info">
            <span className="project-name">{projectData.name}</span>
            <span className="project-progress">{projectData.overall_progress.toFixed(1)}% Complete</span>
          </div>
          <button className="close-button" onClick={onClose}>×</button>
        </div>
        
        <div className="form-progress">
          <div className="progress-steps">
            {[1, 2, 3].map(step => (
              <div 
                key={step} 
                className={`progress-step ${currentStep >= step ? 'active' : ''} ${currentStep === step ? 'current' : ''}`}
              >
                <span className="step-number">{step}</span>
                <span className="step-label">
                  {step === 1 && 'Report Details'}
                  {step === 2 && 'Geo Photos'}
                  {step === 3 && 'Integration'}
                </span>
              </div>
            ))}
          </div>
          <div className="progress-bar">
            <div 
              className="progress-fill" 
              style={{ width: `${(currentStep / 3) * 100}%` }}
            />
          </div>
        </div>
        
        <form onSubmit={handleSubmit}>
          <div className="form-content">
            {currentStep === 1 && renderStep1()}
            {currentStep === 2 && renderStep2()}
            {currentStep === 3 && renderStep3()}
          </div>
          
          <div className="form-actions">
            {currentStep > 1 && (
              <button type="button" className="btn-secondary" onClick={prevStep}>
                ← Previous
              </button>
            )}
            
            {currentStep < 3 ? (
              <button type="button" className="btn-primary" onClick={nextStep}>
                Next →
              </button>
            ) : (
              <button type="submit" className="btn-primary" disabled={loading}>
                {loading ? '🔄 Submitting...' : '📊 Submit Progress Report'}
              </button>
            )}
          </div>
        </form>
        
        {showGeoCapture && (
          <GeoPhotoCapture
            onCapture={handleGeoPhotoCapture}
            onClose={() => setShowGeoCapture(false)}
            projectId={projectId}
            milestoneId={formData.milestone_id}
            workflowContext={getCurrentWorkflowContext()}
          />
        )}
        
        <NotificationToast 
          notifications={notifications}
          onRemove={removeNotification}
        />
      </div>
    </div>
  );
};

export default EnhancedConstructionProgressUpdate;