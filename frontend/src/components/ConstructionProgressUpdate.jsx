import React, { useState, useEffect, useRef } from 'react';
import { useToast } from './ToastProvider.jsx';
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
  const [location, setLocation] = useState({ latitude: null, longitude: null });
  const [loading, setLoading] = useState(false);
  const [loadingProjects, setLoadingProjects] = useState(true);
  const [gettingLocation, setGettingLocation] = useState(false);

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

  // Get current location
  useEffect(() => {
    getCurrentLocation();
  }, []);

  const loadAssignedProjects = async () => {
    try {
      setLoadingProjects(true);
      const response = await fetch(
        `http://localhost/buildhub/backend/api/contractor/get_assigned_projects.php?contractor_id=${contractorId}`,
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
    if (formData.stage_status === 'Completed' && photos.length === 0) {
      toast.error('At least one photo is required for completed stages');
      return false;
    }
    if (formData.delay_reason && !formData.delay_description) {
      toast.error('Please provide delay description when delay reason is selected');
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

      // Append photos
      photos.forEach((photo, index) => {
        submitData.append('photos[]', photo);
      });

      const response = await fetch(
        'http://localhost/buildhub/backend/api/contractor/submit_progress_update.php',
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
          <input
            type="file"
            id="photos"
            ref={fileInputRef}
            onChange={handlePhotoChange}
            accept="image/*"
            multiple
            className="file-input"
          />
          <div className="file-input-help">
            Upload up to 5 photos (JPG, PNG). Maximum 5MB per photo.
            {formData.stage_status === 'Completed' && (
              <strong> At least one photo is required for completed stages.</strong>
            )}
          </div>
        </div>

        {/* Photo Preview */}
        {photos.length > 0 && (
          <div className="photo-preview">
            <h4>Selected Photos ({photos.length}/5)</h4>
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
    </div>
  );
};

export default ConstructionProgressUpdate;