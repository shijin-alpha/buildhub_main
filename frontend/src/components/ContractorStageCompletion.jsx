import React, { useState, useEffect } from 'react';
import '../styles/ContractorStageCompletion.css';

// Lightweight icon component
const Icon = ({ name, size = 20, stroke = 1.8, color = 'currentColor' }) => {
  const common = { width: size, height: size, viewBox: '0 0 24 24', fill: 'none', stroke: color, strokeWidth: stroke, strokeLinecap: 'round', strokeLinejoin: 'round' };
  switch (name) {
    case 'check-circle':
      return (<svg {...common}><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>);
    case 'clock':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>);
    case 'alert-circle':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>);
    case 'upload':
      return (<svg {...common}><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7,10 12,5 17,10"/><line x1="12" y1="5" x2="12" y2="15"/></svg>);
    case 'camera':
      return (<svg {...common}><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>);
    case 'file-text':
      return (<svg {...common}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>);
    case 'send':
      return (<svg {...common}><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22,2 15,22 11,13 2,9 22,2"/></svg>);
    case 'eye':
      return (<svg {...common}><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>);
    case 'x-circle':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>);
    default:
      return null;
  }
};

const ContractorStageCompletion = ({ contractorId }) => {
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState(null);
  const [stageWorkflow, setStageWorkflow] = useState([]);
  const [selectedStage, setSelectedStage] = useState(null);
  const [submissionType, setSubmissionType] = useState('daily_report');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  // Form data for stage submission
  const [formData, setFormData] = useState({
    work_description: '',
    completion_percentage: '',
    materials_used: '',
    labor_details: '',
    challenges_faced: '',
    next_day_plan: '',
    quality_notes: '',
    safety_notes: '',
    weather_conditions: '',
    worker_count: '',
    hours_worked: ''
  });

  // File uploads
  const [photos, setPhotos] = useState([]);
  const [documents, setDocuments] = useState([]);
  const [location, setLocation] = useState({ latitude: null, longitude: null });

  useEffect(() => {
    if (contractorId) {
      fetchContractorProjects();
    }
  }, [contractorId]);

  useEffect(() => {
    if (selectedProject) {
      fetchProjectStageWorkflow(selectedProject.id);
    }
  }, [selectedProject]);

  useEffect(() => {
    getCurrentLocation();
  }, []);

  const fetchContractorProjects = async () => {
    try {
      setLoading(true);
      const response = await fetch(`/buildhub/backend/api/contractor/get_stage_workflow_projects.php?contractor_id=${contractorId}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setProjects(result.projects || []);
      } else {
        setError(result.message || 'Failed to fetch projects');
      }
    } catch (error) {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const fetchProjectStageWorkflow = async (projectId) => {
    try {
      setLoading(true);
      const response = await fetch(`/buildhub/backend/api/contractor/get_project_stage_workflow.php?project_id=${projectId}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setStageWorkflow(result.stages || []);
      } else {
        setError(result.message || 'Failed to fetch stage workflow');
      }
    } catch (error) {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const getCurrentLocation = () => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setLocation({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
          });
        },
        (error) => {
          console.warn('Geolocation error:', error);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
      );
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handlePhotoUpload = (e) => {
    const files = Array.from(e.target.files);
    const validFiles = files.filter(file => {
      const isValidType = ['image/jpeg', 'image/jpg', 'image/png'].includes(file.type);
      const isValidSize = file.size <= 5 * 1024 * 1024; // 5MB
      return isValidType && isValidSize;
    });

    if (validFiles.length !== files.length) {
      setError('Some files were rejected. Only JPG, JPEG, PNG files under 5MB are allowed.');
    }

    setPhotos(prev => [...prev, ...validFiles].slice(0, 10)); // Max 10 photos
  };

  const handleDocumentUpload = (e) => {
    const files = Array.from(e.target.files);
    const validFiles = files.filter(file => {
      const isValidType = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'].includes(file.type);
      const isValidSize = file.size <= 10 * 1024 * 1024; // 10MB
      return isValidType && isValidSize;
    });

    if (validFiles.length !== files.length) {
      setError('Some files were rejected. Only PDF, DOC, DOCX files under 10MB are allowed.');
    }

    setDocuments(prev => [...prev, ...validFiles].slice(0, 5)); // Max 5 documents
  };

  const removePhoto = (index) => {
    setPhotos(prev => prev.filter((_, i) => i !== index));
  };

  const removeDocument = (index) => {
    setDocuments(prev => prev.filter((_, i) => i !== index));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!selectedProject || !selectedStage) {
      setError('Please select a project and stage');
      return;
    }

    if (!formData.work_description.trim()) {
      setError('Work description is required');
      return;
    }

    if (submissionType === 'stage_completion' && (!formData.completion_percentage || formData.completion_percentage < 100)) {
      setError('Stage completion requires 100% completion percentage');
      return;
    }

    try {
      setLoading(true);
      setError('');
      setSuccess('');

      const submitFormData = new FormData();
      
      // Add form fields
      submitFormData.append('project_id', selectedProject.id);
      submitFormData.append('stage_name', selectedStage.stage_name);
      submitFormData.append('submission_type', submissionType);
      
      Object.keys(formData).forEach(key => {
        if (formData[key]) {
          submitFormData.append(key, formData[key]);
        }
      });

      // Add location if available
      if (location.latitude && location.longitude) {
        submitFormData.append('latitude', location.latitude);
        submitFormData.append('longitude', location.longitude);
      }

      // Add photos
      photos.forEach((photo, index) => {
        submitFormData.append(`photos[${index}]`, photo);
      });

      // Add documents
      documents.forEach((doc, index) => {
        submitFormData.append(`documents[${index}]`, doc);
      });

      const response = await fetch('/buildhub/backend/api/contractor/submit_stage_completion.php', {
        method: 'POST',
        credentials: 'include',
        body: submitFormData
      });

      const result = await response.json();
      
      if (result.success) {
        setSuccess(submissionType === 'stage_completion' ? 
          'Stage completion submitted successfully! Awaiting inspection approval.' : 
          'Daily report submitted successfully!'
        );
        
        // Reset form
        setFormData({
          work_description: '',
          completion_percentage: '',
          materials_used: '',
          labor_details: '',
          challenges_faced: '',
          next_day_plan: '',
          quality_notes: '',
          safety_notes: '',
          weather_conditions: '',
          worker_count: '',
          hours_worked: ''
        });
        setPhotos([]);
        setDocuments([]);
        
        // Refresh stage workflow
        fetchProjectStageWorkflow(selectedProject.id);
      } else {
        setError(result.message || 'Failed to submit stage report');
      }
    } catch (error) {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const getStageStatusIcon = (stage) => {
    switch (stage.contractor_status) {
      case 'completed':
        return <Icon name="check-circle" color="#10b981" />;
      case 'submitted_for_inspection':
        return <Icon name="clock" color="#f59e0b" />;
      case 'in_progress':
        return <Icon name="clock" color="#3b82f6" />;
      default:
        return <Icon name="alert-circle" color="#6b7280" />;
    }
  };

  const getStageStatusText = (stage) => {
    switch (stage.contractor_status) {
      case 'completed':
        return 'Completed & Approved';
      case 'submitted_for_inspection':
        return 'Awaiting Inspection';
      case 'in_progress':
        return 'In Progress';
      default:
        return 'Not Started';
    }
  };

  const canSubmitForStage = (stage) => {
    // Can only work on current stage (sequential progression)
    const currentStageIndex = stageWorkflow.findIndex(s => s.contractor_status !== 'completed');
    const stageIndex = stageWorkflow.findIndex(s => s.id === stage.id);
    
    return stageIndex === currentStageIndex && stage.contractor_status !== 'submitted_for_inspection';
  };

  if (loading && !selectedProject) {
    return (
      <div className="stage-completion-container">
        <div className="loading-state">
          <div className="loading-spinner"></div>
          <p>Loading projects...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="stage-completion-container">
      <div className="stage-completion-header">
        <h2>Construction Stage Management</h2>
        <p>Submit daily reports and stage completion requests for your assigned projects</p>
      </div>

      {error && (
        <div className="error-message">
          <Icon name="alert-circle" color="#ef4444" />
          <span>{error}</span>
        </div>
      )}

      {success && (
        <div className="success-message">
          <Icon name="check-circle" color="#10b981" />
          <span>{success}</span>
        </div>
      )}

      {/* Project Selection */}
      <div className="project-selection">
        <h3>Select Project</h3>
        <div className="projects-grid">
          {projects.map((project) => (
            <div 
              key={project.id} 
              className={`project-card ${selectedProject?.id === project.id ? 'selected' : ''}`}
              onClick={() => setSelectedProject(project)}
            >
              <h4>{project.project_name}</h4>
              <p>{project.project_location}</p>
              <div className="project-progress">
                <span>Overall Progress: {project.completion_percentage || 0}%</span>
                <div className="progress-bar">
                  <div 
                    className="progress-fill" 
                    style={{ width: `${project.completion_percentage || 0}%` }}
                  ></div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Stage Workflow */}
      {selectedProject && (
        <div className="stage-workflow">
          <h3>Construction Stages - {selectedProject.project_name}</h3>
          <div className="stages-timeline">
            {stageWorkflow.map((stage, index) => (
              <div 
                key={stage.id} 
                className={`stage-item ${selectedStage?.id === stage.id ? 'selected' : ''} ${canSubmitForStage(stage) ? 'available' : 'locked'}`}
                onClick={() => canSubmitForStage(stage) && setSelectedStage(stage)}
              >
                <div className="stage-number">{index + 1}</div>
                <div className="stage-content">
                  <div className="stage-header">
                    <h4>{stage.stage_name}</h4>
                    <div className="stage-status">
                      {getStageStatusIcon(stage)}
                      <span>{getStageStatusText(stage)}</span>
                    </div>
                  </div>
                  <div className="stage-progress">
                    <span>{stage.stage_completion_percentage || 0}% Complete</span>
                    <div className="progress-bar">
                      <div 
                        className="progress-fill" 
                        style={{ width: `${stage.stage_completion_percentage || 0}%` }}
                      ></div>
                    </div>
                  </div>
                  {stage.contractor_status === 'submitted_for_inspection' && (
                    <div className="inspection-status">
                      <Icon name="clock" color="#f59e0b" size={16} />
                      <span>Inspection Status: {stage.inspection_status}</span>
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Stage Submission Form */}
      {selectedStage && canSubmitForStage(selectedStage) && (
        <div className="stage-submission-form">
          <h3>Submit Report for {selectedStage.stage_name}</h3>
          
          <div className="submission-type-selector">
            <label>
              <input
                type="radio"
                name="submission_type"
                value="daily_report"
                checked={submissionType === 'daily_report'}
                onChange={(e) => setSubmissionType(e.target.value)}
              />
              Daily Progress Report
            </label>
            <label>
              <input
                type="radio"
                name="submission_type"
                value="stage_completion"
                checked={submissionType === 'stage_completion'}
                onChange={(e) => setSubmissionType(e.target.value)}
              />
              Stage Completion Request
            </label>
          </div>

          <form onSubmit={handleSubmit} className="submission-form">
            <div className="form-grid">
              <div className="form-group full-width">
                <label htmlFor="work_description">Work Description *</label>
                <textarea
                  id="work_description"
                  name="work_description"
                  value={formData.work_description}
                  onChange={handleInputChange}
                  placeholder="Describe the work completed today..."
                  rows="4"
                  required
                />
              </div>

              <div className="form-group">
                <label htmlFor="completion_percentage">Completion Percentage *</label>
                <input
                  type="number"
                  id="completion_percentage"
                  name="completion_percentage"
                  value={formData.completion_percentage}
                  onChange={handleInputChange}
                  min="0"
                  max="100"
                  step="0.1"
                  placeholder="0-100"
                  required
                />
              </div>

              <div className="form-group">
                <label htmlFor="worker_count">Number of Workers</label>
                <input
                  type="number"
                  id="worker_count"
                  name="worker_count"
                  value={formData.worker_count}
                  onChange={handleInputChange}
                  min="0"
                  placeholder="0"
                />
              </div>

              <div className="form-group">
                <label htmlFor="hours_worked">Hours Worked</label>
                <input
                  type="number"
                  id="hours_worked"
                  name="hours_worked"
                  value={formData.hours_worked}
                  onChange={handleInputChange}
                  min="0"
                  max="24"
                  step="0.5"
                  placeholder="8.0"
                />
              </div>

              <div className="form-group">
                <label htmlFor="weather_conditions">Weather Conditions</label>
                <select
                  id="weather_conditions"
                  name="weather_conditions"
                  value={formData.weather_conditions}
                  onChange={handleInputChange}
                >
                  <option value="">Select weather</option>
                  <option value="Clear">Clear</option>
                  <option value="Partly Cloudy">Partly Cloudy</option>
                  <option value="Cloudy">Cloudy</option>
                  <option value="Light Rain">Light Rain</option>
                  <option value="Heavy Rain">Heavy Rain</option>
                  <option value="Windy">Windy</option>
                  <option value="Hot">Hot</option>
                  <option value="Cold">Cold</option>
                </select>
              </div>

              <div className="form-group full-width">
                <label htmlFor="materials_used">Materials Used</label>
                <textarea
                  id="materials_used"
                  name="materials_used"
                  value={formData.materials_used}
                  onChange={handleInputChange}
                  placeholder="List materials used today..."
                  rows="3"
                />
              </div>

              <div className="form-group full-width">
                <label htmlFor="labor_details">Labor Details</label>
                <textarea
                  id="labor_details"
                  name="labor_details"
                  value={formData.labor_details}
                  onChange={handleInputChange}
                  placeholder="Describe labor activities and assignments..."
                  rows="3"
                />
              </div>

              {submissionType === 'daily_report' && (
                <div className="form-group full-width">
                  <label htmlFor="next_day_plan">Next Day Plan</label>
                  <textarea
                    id="next_day_plan"
                    name="next_day_plan"
                    value={formData.next_day_plan}
                    onChange={handleInputChange}
                    placeholder="Plan for tomorrow's work..."
                    rows="3"
                  />
                </div>
              )}

              <div className="form-group full-width">
                <label htmlFor="challenges_faced">Challenges Faced</label>
                <textarea
                  id="challenges_faced"
                  name="challenges_faced"
                  value={formData.challenges_faced}
                  onChange={handleInputChange}
                  placeholder="Any challenges or issues encountered..."
                  rows="3"
                />
              </div>

              <div className="form-group full-width">
                <label htmlFor="quality_notes">Quality Notes</label>
                <textarea
                  id="quality_notes"
                  name="quality_notes"
                  value={formData.quality_notes}
                  onChange={handleInputChange}
                  placeholder="Quality observations and notes..."
                  rows="2"
                />
              </div>

              <div className="form-group full-width">
                <label htmlFor="safety_notes">Safety Notes</label>
                <textarea
                  id="safety_notes"
                  name="safety_notes"
                  value={formData.safety_notes}
                  onChange={handleInputChange}
                  placeholder="Safety observations and measures taken..."
                  rows="2"
                />
              </div>
            </div>

            {/* Photo Upload */}
            <div className="file-upload-section">
              <h4>Progress Photos</h4>
              <div className="file-upload">
                <input
                  type="file"
                  id="photos"
                  multiple
                  accept="image/jpeg,image/jpg,image/png"
                  onChange={handlePhotoUpload}
                  style={{ display: 'none' }}
                />
                <label htmlFor="photos" className="upload-button">
                  <Icon name="camera" />
                  Upload Photos (Max 10, 5MB each)
                </label>
              </div>
              {photos.length > 0 && (
                <div className="uploaded-files">
                  {photos.map((photo, index) => (
                    <div key={index} className="file-item">
                      <span>{photo.name}</span>
                      <button type="button" onClick={() => removePhoto(index)}>
                        <Icon name="x-circle" size={16} />
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Document Upload */}
            <div className="file-upload-section">
              <h4>Supporting Documents</h4>
              <div className="file-upload">
                <input
                  type="file"
                  id="documents"
                  multiple
                  accept=".pdf,.doc,.docx"
                  onChange={handleDocumentUpload}
                  style={{ display: 'none' }}
                />
                <label htmlFor="documents" className="upload-button">
                  <Icon name="file-text" />
                  Upload Documents (Max 5, 10MB each)
                </label>
              </div>
              {documents.length > 0 && (
                <div className="uploaded-files">
                  {documents.map((doc, index) => (
                    <div key={index} className="file-item">
                      <span>{doc.name}</span>
                      <button type="button" onClick={() => removeDocument(index)}>
                        <Icon name="x-circle" size={16} />
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="form-actions">
              <button type="submit" className="submit-button" disabled={loading}>
                {loading ? (
                  <>
                    <div className="loading-spinner small"></div>
                    Submitting...
                  </>
                ) : (
                  <>
                    <Icon name="send" />
                    {submissionType === 'stage_completion' ? 'Submit for Inspection' : 'Submit Daily Report'}
                  </>
                )}
              </button>
            </div>
          </form>
        </div>
      )}
    </div>
  );
};

export default ContractorStageCompletion;