import React, { useState, useEffect, useRef } from 'react';
import { useToast } from './ToastProvider.jsx';
import GeoPhotoCapture from './GeoPhotoCapture.jsx';
import '../styles/ConstructionProgress.css';

const ConstructionProgressUpdate = ({ contractorId, onUpdateSubmitted }) => {
  const toast = useToast();
  const fileInputRef = useRef(null);
  
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState('');
  const [formData, setFormData] = useState({
    stage_name: '',
    stage_status: '',
    completion_percentage: '',
    remarks: '',
    delay_reason: '',
    delay_description: ''
  });
  const [photos, setPhotos] = useState([]);
  const [geoPhotos, setGeoPhotos] = useState([]);
  const [showGeoCapture, setShowGeoCapture] = useState(false);
  const [location, setLocation] = useState({ latitude: null, longitude: null });
  const [loading, setLoading] = useState(false);
  const [loadingProjects, setLoadingProjects] = useState(true);
  const [gettingLocation, setGettingLocation] = useState(false);
  
  // Worker selection states
  const [phaseWorkers, setPhaseWorkers] = useState(null);
  const [selectedWorkers, setSelectedWorkers] = useState([]);
  const [loadingWorkers, setLoadingWorkers] = useState(false);
  const [showWorkerSelection, setShowWorkerSelection] = useState(false);

  const stages = [
    'Foundation', 'Structure', 'Brickwork', 'Roofing', 
    'Electrical', 'Plumbing', 'Finishing', 'Other'
  ];

  const statuses = ['Not Started', 'In Progress', 'Completed'];

  const delayReasons = [
    'Weather', 'Material Delay', 'Labor Shortage', 
    'Design Change', 'Client Request', 'Other'
  ];

  // Load assigned projects
  useEffect(() => {
    loadAssignedProjects();
  }, [contractorId]);

  // Load phase workers when stage is selected
  useEffect(() => {
    if (formData.stage_name) {
      loadPhaseWorkers(formData.stage_name);
    } else {
      setPhaseWorkers(null);
      setSelectedWorkers([]);
      setShowWorkerSelection(false);
    }
  }, [formData.stage_name]);

  const loadPhaseWorkers = async (phaseName) => {
    try {
      setLoadingWorkers(true);
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_phase_workers.php?phase=${encodeURIComponent(phaseName)}`,
        { credentials: 'include' }
      );
      const data = await response.json();
      
      if (data.success) {
        setPhaseWorkers(data.data);
        setShowWorkerSelection(true);
        
        // Auto-select main workers if available
        const autoSelectedWorkers = [];
        Object.values(data.data.available_workers).forEach(workerGroup => {
          const mainWorker = workerGroup.workers.find(w => w.is_main_worker);
          if (mainWorker && workerGroup.requirement.is_required) {
            autoSelectedWorkers.push({
              worker_id: mainWorker.id,
              worker_name: mainWorker.worker_name,
              worker_type: mainWorker.type_name,
              skill_level: mainWorker.skill_level,
              hours_worked: 8,
              overtime_hours: 0,
              is_main_worker: mainWorker.is_main_worker
            });
          }
        });
        setSelectedWorkers(autoSelectedWorkers);
        
        if (autoSelectedWorkers.length > 0) {
          toast.success(`Auto-selected ${autoSelectedWorkers.length} main workers for ${phaseName} phase`);
        }
      } else {
        toast.error('Failed to load workers for this phase: ' + data.message);
        setPhaseWorkers(null);
        setShowWorkerSelection(false);
      }
    } catch (error) {
      console.error('Error loading phase workers:', error);
      toast.error('Error loading workers for this phase');
      setPhaseWorkers(null);
      setShowWorkerSelection(false);
    } finally {
      setLoadingWorkers(false);
    }
  };

  // Get current location
  useEffect(() => {
    getCurrentLocation();
  }, []);

  const addWorkerToSelection = (worker, workerType) => {
    const workerId = `${worker.id}_${Date.now()}`;
    const newWorker = {
      worker_id: worker.id,
      worker_name: worker.worker_name,
      worker_type: workerType,
      skill_level: worker.skill_level,
      hours_worked: 8,
      overtime_hours: 0,
      is_main_worker: worker.is_main_worker,
      selection_id: workerId
    };
    
    setSelectedWorkers(prev => [...prev, newWorker]);
    toast.success(`Added ${worker.worker_name} (${workerType}) to work team`);
  };

  const removeWorkerFromSelection = (selectionId) => {
    setSelectedWorkers(prev => prev.filter(w => w.selection_id !== selectionId));
  };

  const updateWorkerHours = (selectionId, field, value) => {
    setSelectedWorkers(prev => prev.map(worker => 
      worker.selection_id === selectionId 
        ? { ...worker, [field]: parseFloat(value) || 0 }
        : worker
    ));
  };

  const calculateWorkerPayment = (worker) => {
    // Basic calculation for display purposes (actual payment is handled by stage-based system)
    const regularHours = parseFloat(worker.hours_worked) || 0;
    const overtimeHours = parseFloat(worker.overtime_hours) || 0;
    const baseRate = worker.is_main_worker ? 500 : 300; // Base daily rate for display
    
    const regularPay = regularHours * (baseRate / 8); // Convert daily to hourly
    const overtimePay = overtimeHours * (baseRate / 8) * 1.5; // 1.5x for overtime
    
    return regularPay + overtimePay;
  };



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

  const getCurrentLocation = () => {
    if (navigator.geolocation) {
      setGettingLocation(true);
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setLocation({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
          });
          setGettingLocation(false);
        },
        (error) => {
          console.warn('Geolocation error:', error);
          setGettingLocation(false);
          // Continue without location - it's optional
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
      );
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handlePhotoChange = (e) => {
    const files = Array.from(e.target.files);
    const maxFiles = 5;
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (files.length > maxFiles) {
      toast.error(`Maximum ${maxFiles} photos allowed`);
      return;
    }

    const validFiles = files.filter(file => {
      if (!file.type.startsWith('image/')) {
        toast.error(`${file.name} is not a valid image file`);
        return false;
      }
      if (file.size > maxSize) {
        toast.error(`${file.name} is too large. Maximum 5MB allowed`);
        return false;
      }
      return true;
    });

    setPhotos(validFiles);
  };

  const removePhoto = (index) => {
    setPhotos(prev => prev.filter((_, i) => i !== index));
  };

  const removeGeoPhoto = (photoId) => {
    setGeoPhotos(prev => prev.filter(photo => photo.id !== photoId));
  };

  const handleGeoPhotosCaptured = (capturedPhotos) => {
    // Add captured geo photos to the form
    const newGeoPhotos = capturedPhotos.map(result => ({
      id: result.data.id,
      filename: result.data.filename,
      original_filename: result.data.original_filename,
      location: result.data.location,
      upload_timestamp: result.data.upload_timestamp,
      isGeoTagged: true,
      isUploaded: true // These are already uploaded to server
    }));
    
    setGeoPhotos(prev => [...prev, ...newGeoPhotos]);
    setShowGeoCapture(false);
    
    toast.success(`${newGeoPhotos.length} geo-tagged photo(s) added to your progress update`);
  };

  const validateForm = () => {
    if (!selectedProject) {
      toast.error('Please select a project');
      return false;
    }
    if (!formData.stage_name) {
      toast.error('Please select a construction stage');
      return false;
    }
    if (!formData.stage_status) {
      toast.error('Please select stage status');
      return false;
    }
    if (formData.completion_percentage === '' || formData.completion_percentage < 0 || formData.completion_percentage > 100) {
      toast.error('Please enter a valid completion percentage (0-100)');
      return false;
    }
    if (formData.stage_status === 'Completed' && photos.length === 0 && geoPhotos.length === 0) {
      toast.error('At least one photo is required for completed stages');
      return false;
    }
    if (formData.delay_reason && !formData.delay_description) {
      toast.error('Please provide delay description when delay reason is selected');
      return false;
    }
    
    // Worker validation
    if (phaseWorkers && selectedWorkers.length === 0) {
      toast.error('Please select at least one worker for this construction phase');
      return false;
    }
    
    // Check if essential workers are selected
    if (phaseWorkers && phaseWorkers.phase_readiness.missing_essential.length > 0) {
      const missingTypes = phaseWorkers.phase_readiness.missing_essential.map(m => m.worker_type).join(', ');
      toast.error(`Essential workers missing: ${missingTypes}. Please add these workers to proceed.`);
      return false;
    }
    
    // Validate worker hours
    const invalidHours = selectedWorkers.find(w => w.hours_worked <= 0 || w.hours_worked > 16);
    if (invalidHours) {
      toast.error(`Invalid work hours for ${invalidHours.worker_name}. Hours must be between 1-16.`);
      return false;
    }
    
    const invalidOvertime = selectedWorkers.find(w => w.overtime_hours < 0 || w.overtime_hours > 8);
    if (invalidOvertime) {
      toast.error(`Invalid overtime hours for ${invalidOvertime.worker_name}. Overtime must be between 0-8 hours.`);
      return false;
    }
    
    return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) return;

    setLoading(true);
    
    try {
      const submitData = new FormData();
      submitData.append('project_id', selectedProject);
      submitData.append('contractor_id', contractorId);
      submitData.append('stage_name', formData.stage_name);
      submitData.append('stage_status', formData.stage_status);
      submitData.append('completion_percentage', formData.completion_percentage);
      submitData.append('remarks', formData.remarks);
      
      if (formData.delay_reason) {
        submitData.append('delay_reason', formData.delay_reason);
        submitData.append('delay_description', formData.delay_description);
      }
      
      if (location.latitude && location.longitude) {
        submitData.append('latitude', location.latitude);
        submitData.append('longitude', location.longitude);
      }

      // Append regular photos
      photos.forEach((photo, index) => {
        submitData.append('photos[]', photo);
      });

      // Append geo photo IDs (these are already uploaded)
      geoPhotos.forEach((geoPhoto) => {
        submitData.append('geo_photo_ids[]', geoPhoto.id);
      });

      // Append worker assignments
      selectedWorkers.forEach((worker, index) => {
        submitData.append(`workers[${index}][worker_id]`, worker.worker_id);
        submitData.append(`workers[${index}][hours_worked]`, worker.hours_worked);
        submitData.append(`workers[${index}][overtime_hours]`, worker.overtime_hours);
        submitData.append(`workers[${index}][work_description]`, formData.remarks || '');
      });

      const response = await fetch(
        '/buildhub/backend/api/contractor/submit_progress_update.php',
        {
          method: 'POST',
          credentials: 'include',
          body: submitData
        }
      );

      const data = await response.json();
      
      if (data.success) {
        toast.success('Progress update submitted successfully!');
        
        // Reset form
        setFormData({
          stage_name: '',
          stage_status: '',
          completion_percentage: '',
          remarks: '',
          delay_reason: '',
          delay_description: ''
        });
        setPhotos([]);
        setGeoPhotos([]);
        setSelectedWorkers([]);
        setPhaseWorkers(null);
        setShowWorkerSelection(false);
        setSelectedProject('');
        
        // Reset file input
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
        
        // Notify parent component
        if (onUpdateSubmitted) {
          onUpdateSubmitted(data.data);
        }
      } else {
        toast.error('Failed to submit update: ' + data.message);
      }
    } catch (error) {
      console.error('Error submitting progress update:', error);
      toast.error('Error submitting progress update');
    } finally {
      setLoading(false);
    }
  };

  const getSelectedProjectInfo = () => {
    const project = projects.find(p => p.project_id == selectedProject);
    return project || null;
  };

  return (
    <div className="construction-progress-update">
      <div className="progress-update-header">
        <h3>Submit Construction Progress Update</h3>
        <p>Update homeowners on your construction progress with photos and details</p>
      </div>

      <form onSubmit={handleSubmit} className="progress-update-form">
        {/* Project Selection */}
        <div className="form-group">
          <label htmlFor="project">Select Project *</label>
          {loadingProjects ? (
            <div className="loading-spinner">Loading projects...</div>
          ) : (
            <select
              id="project"
              value={selectedProject}
              onChange={(e) => setSelectedProject(e.target.value)}
              required
            >
              <option value="">Choose a project...</option>
              {projects.map(project => (
                <option key={project.project_id} value={project.project_id}>
                  {project.project_display_name || 
                   `${project.homeowner_first_name} ${project.homeowner_last_name} - ${project.plot_size} (${project.budget_range}) - ${project.latest_progress}% Complete`
                  }
                </option>
              ))}
            </select>
          )}
        </div>

        {/* Project Info Display */}
        {selectedProject && (
          <div className="project-info-card">
            {(() => {
              const projectInfo = getSelectedProjectInfo();
              return projectInfo ? (
                <div className="project-details">
                  <h4>Project Details</h4>
                  <div className="project-info-grid">
                    <div><strong>Homeowner:</strong> {projectInfo.homeowner_first_name} {projectInfo.homeowner_last_name}</div>
                    <div><strong>Plot Size:</strong> {projectInfo.plot_size}</div>
                    <div><strong>Budget:</strong> {projectInfo.budget_range}</div>
                    <div><strong>Current Progress:</strong> {projectInfo.latest_progress}%</div>
                  </div>
                </div>
              ) : null;
            })()}
          </div>
        )}

        {/* Construction Stage */}
        <div className="form-group">
          <label htmlFor="stage_name">Construction Stage *</label>
          <select
            id="stage_name"
            name="stage_name"
            value={formData.stage_name}
            onChange={handleInputChange}
            required
          >
            <option value="">Select stage...</option>
            {stages.map(stage => (
              <option key={stage} value={stage}>{stage}</option>
            ))}
          </select>
        </div>

        {/* Stage Status */}
        <div className="form-group">
          <label htmlFor="stage_status">Stage Status *</label>
          <select
            id="stage_status"
            name="stage_status"
            value={formData.stage_status}
            onChange={handleInputChange}
            required
          >
            <option value="">Select status...</option>
            {statuses.map(status => (
              <option key={status} value={status}>{status}</option>
            ))}
          </select>
        </div>

        {/* Completion Percentage */}
        <div className="form-group">
          <label htmlFor="completion_percentage">Overall Completion Percentage *</label>
          <div className="percentage-input">
            <input
              type="number"
              id="completion_percentage"
              name="completion_percentage"
              value={formData.completion_percentage}
              onChange={handleInputChange}
              min="0"
              max="100"
              step="1"
              required
            />
            <span className="percentage-symbol">%</span>
          </div>
        </div>

        {/* Worker Selection */}
        {showWorkerSelection && (
          <div className="worker-selection-section">
            <div className="worker-section-header">
              <h4>👷 Select Workers for {formData.stage_name} Phase</h4>
              {loadingWorkers ? (
                <div className="loading-spinner">Loading workers...</div>
              ) : (
                <div className="phase-info">
                  <span className="phase-description">{phaseWorkers?.phase_info?.description}</span>
                </div>
              )}
            </div>

            {phaseWorkers && (
              <>
                {/* Available Workers by Type */}
                <div className="available-workers">
                  {Object.entries(phaseWorkers.available_workers).map(([workerTypeId, workerGroup]) => (
                    <div key={workerTypeId} className="worker-type-group">
                      <div className="worker-type-header">
                        <h5>
                          {workerGroup.requirement.type_name}
                          <span className={`priority-badge ${workerGroup.requirement.priority_level}`}>
                            {workerGroup.requirement.priority_level}
                          </span>
                        </h5>
                        <div className="requirement-info">
                          Required: {workerGroup.requirement.min_workers}-{workerGroup.requirement.max_workers} workers
                        </div>
                      </div>
                      
                      {workerGroup.workers.length > 0 ? (
                        <div className="workers-grid">
                          {workerGroup.workers.map(worker => (
                            <div key={worker.id} className="worker-card">
                              <div className="worker-info">
                                <div className="worker-name">
                                  {worker.worker_name}
                                  {worker.is_main_worker && <span className="main-worker-badge">👑 Main</span>}
                                </div>
                                <div className="worker-details">
                                  <span className="skill-level">{worker.worker_role}</span>
                                  <span className="experience">{worker.experience_years}y exp</span>
                                </div>
                              </div>
                              <button
                                type="button"
                                onClick={() => addWorkerToSelection(worker, workerGroup.requirement.type_name)}
                                className="add-worker-btn"
                                disabled={selectedWorkers.some(w => w.worker_id === worker.id)}
                              >
                                {selectedWorkers.some(w => w.worker_id === worker.id) ? '✅ Added' : '+ Add'}
                              </button>
                            </div>
                          ))}
                        </div>
                      ) : (
                        <div className="no-workers">
                          No {workerGroup.requirement.type_name}s available. 
                          <a href="#" className="add-worker-link">Add workers to your team</a>
                        </div>
                      )}
                    </div>
                  ))}
                </div>

                {/* Selected Workers */}
                {selectedWorkers.length > 0 && (
                  <div className="selected-workers">
                    <h5>👥 Selected Work Team ({selectedWorkers.length} workers)</h5>
                    <div className="selected-workers-list">
                      {selectedWorkers.map(worker => (
                        <div key={worker.selection_id} className="selected-worker">
                          <div className="worker-summary">
                            <div className="worker-name-type">
                              <strong>{worker.worker_name}</strong>
                              <span className="worker-type">({worker.worker_type})</span>
                              {worker.is_main_worker && <span className="main-badge">👑</span>}
                            </div>
                          </div>
                          
                          <div className="worker-hours">
                            <div className="hours-input">
                              <label>Work Hours:</label>
                              <input
                                type="number"
                                min="1"
                                max="16"
                                step="0.5"
                                value={worker.hours_worked}
                                onChange={(e) => updateWorkerHours(worker.selection_id, 'hours_worked', e.target.value)}
                              />
                            </div>
                            <div className="hours-input">
                              <label>Overtime:</label>
                              <input
                                type="number"
                                min="0"
                                max="8"
                                step="0.5"
                                value={worker.overtime_hours}
                                onChange={(e) => updateWorkerHours(worker.selection_id, 'overtime_hours', e.target.value)}
                              />
                            </div>
                            <div className="worker-payment">
                              <strong>₹{calculateWorkerPayment(worker).toFixed(2)}</strong>
                            </div>
                          </div>
                          
                          <button
                            type="button"
                            onClick={() => removeWorkerFromSelection(worker.selection_id)}
                            className="remove-worker-btn"
                            title="Remove worker"
                          >
                            ×
                          </button>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Recommendations */}
                {phaseWorkers.phase_readiness.recommendations.length > 0 && (
                  <div className="worker-recommendations">
                    <h6>💡 Recommendations:</h6>
                    {phaseWorkers.phase_readiness.recommendations.slice(0, 3).map((rec, index) => (
                      <div key={index} className={`recommendation ${rec.type}`}>
                        {rec.message}
                      </div>
                    ))}
                  </div>
                )}
              </>
            )}
          </div>
        )}

        {/* Remarks */}
        <div className="form-group">
          <label htmlFor="remarks">Work Description & Remarks</label>
          <textarea
            id="remarks"
            name="remarks"
            value={formData.remarks}
            onChange={handleInputChange}
            placeholder="Describe the work completed, materials used, or any important notes..."
            rows="4"
          />
        </div>

        {/* Delay Information */}
        <div className="form-group">
          <label htmlFor="delay_reason">Delay Reason (if any)</label>
          <select
            id="delay_reason"
            name="delay_reason"
            value={formData.delay_reason}
            onChange={handleInputChange}
          >
            <option value="">No delays</option>
            {delayReasons.map(reason => (
              <option key={reason} value={reason}>{reason}</option>
            ))}
          </select>
        </div>

        {formData.delay_reason && (
          <div className="form-group">
            <label htmlFor="delay_description">Delay Description *</label>
            <textarea
              id="delay_description"
              name="delay_description"
              value={formData.delay_description}
              onChange={handleInputChange}
              placeholder="Explain the delay and expected resolution..."
              rows="3"
              required
            />
          </div>
        )}

        {/* Photo Upload */}
        <div className="form-group">
          <label htmlFor="photos">
            Progress Photos {formData.stage_status === 'Completed' && <span className="required">*</span>}
          </label>
          
          {/* Photo Upload Options */}
          <div className="photo-upload-options">
            <input
              type="file"
              id="photos"
              ref={fileInputRef}
              onChange={handlePhotoChange}
              accept="image/*"
              multiple
              className="file-input"
            />
            
            <button
              type="button"
              onClick={() => setShowGeoCapture(true)}
              className="geo-capture-btn"
            >
              📸 Capture Geo-Tagged Photo
            </button>
          </div>
          
          <div className="file-input-help">
            Upload up to 5 photos (JPG, PNG) or capture geo-tagged photos with GPS location. Maximum 5MB per photo.
            {formData.stage_status === 'Completed' && (
              <strong> At least one photo is required for completed stages.</strong>
            )}
          </div>
        </div>

        {/* Geo Photos Display */}
        {geoPhotos.length > 0 && (
          <div className="geo-photos-section">
            <h4>📍 Geo-Tagged Photos ({geoPhotos.length})</h4>
            <div className="geo-photos-grid">
              {geoPhotos.map((geoPhoto) => (
                <div key={geoPhoto.id} className="geo-photo-item">
                  <div className="geo-photo-preview">
                    <div className="geo-photo-placeholder">
                      📸 {geoPhoto.original_filename}
                    </div>
                    <button
                      type="button"
                      onClick={() => removeGeoPhoto(geoPhoto.id)}
                      className="remove-photo-btn"
                      title="Remove geo photo"
                    >
                      ×
                    </button>
                  </div>
                  <div className="geo-photo-info">
                    <div className="geo-location">
                      📍 {geoPhoto.location?.place_name || 'GPS Location Captured'}
                    </div>
                    {geoPhoto.location?.latitude && geoPhoto.location?.longitude && (
                      <div className="geo-coordinates">
                        🌐 {geoPhoto.location.latitude.toFixed(6)}, {geoPhoto.location.longitude.toFixed(6)}
                      </div>
                    )}
                    <div className="geo-timestamp">
                      🕒 {new Date(geoPhoto.upload_timestamp).toLocaleString()}
                    </div>
                    <div className="geo-status">
                      ✅ Uploaded with GPS data
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Photo Preview */}
        {photos.length > 0 && (
          <div className="photo-preview">
            <h4>📷 Regular Photos ({photos.length}/5)</h4>
            <div className="photo-grid">
              {photos.map((photo, index) => (
                <div key={index} className="photo-item">
                  <img
                    src={URL.createObjectURL(photo)}
                    alt={`Preview ${index + 1}`}
                    className="photo-thumbnail"
                  />
                  <button
                    type="button"
                    onClick={() => removePhoto(index)}
                    className="remove-photo-btn"
                    title="Remove photo"
                  >
                    ×
                  </button>
                  <div className="photo-name">{photo.name}</div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Location Status */}
        <div className="location-status">
          {gettingLocation ? (
            <div className="location-info">📍 Getting your location...</div>
          ) : location.latitude ? (
            <div className="location-info success">📍 Location captured for verification</div>
          ) : (
            <div className="location-info warning">📍 Location not available (optional)</div>
          )}
        </div>

        {/* Submit Button */}
        <div className="form-actions">
          <button
            type="submit"
            disabled={loading || loadingProjects}
            className="submit-btn"
          >
            {loading ? 'Submitting...' : 'Submit Progress Update'}
          </button>
        </div>
      </form>

      {/* Geo Photo Capture Modal */}
      {showGeoCapture && (
        <div className="geo-capture-modal">
          <div className="geo-capture-overlay" onClick={() => setShowGeoCapture(false)} />
          <div className="geo-capture-content">
            <GeoPhotoCapture
              projectId={selectedProject}
              contractorId={contractorId}
              onPhotosCaptured={handleGeoPhotosCaptured}
              onClose={() => setShowGeoCapture(false)}
            />
          </div>
        </div>
      )}
    </div>
  );
};

export default ConstructionProgressUpdate;