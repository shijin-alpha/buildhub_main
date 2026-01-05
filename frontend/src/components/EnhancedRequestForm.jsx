import React, { useState, useEffect } from 'react';
import { useNotifications } from '../hooks/useNotifications';
import NotificationToast from './NotificationToast';
import '../styles/EnhancedRequestForm.css';

const EnhancedRequestForm = ({ onClose, onSubmit }) => {
  const { notifications, removeNotification, showSuccess, showError, showInfo } = useNotifications();
  
  // Form state
  const [formData, setFormData] = useState({
    // Basic information
    plot_size: '',
    building_size: '',
    budget_range: '',
    location: '',
    timeline: '',
    requirements: '',
    
    // Integration features
    requires_house_plan: true,
    enable_progress_tracking: true,
    enable_geo_photos: true,
    
    // House plan requirements
    plot_shape: 'rectangular',
    topography: 'flat',
    num_floors: 1,
    preferred_style: 'modern',
    vastu_compliance: false,
    parking_requirements: 'one_car',
    
    // Room requirements
    bedrooms: 2,
    bathrooms: 2,
    kitchen_type: 'closed',
    living_areas: ['living_room', 'dining_room'],
    special_rooms: [],
    
    // Selected architects
    selected_architect_ids: []
  });
  
  const [architects, setArchitects] = useState([]);
  const [loading, setLoading] = useState(false);
  const [currentStep, setCurrentStep] = useState(1);
  const [showFeatureInfo, setShowFeatureInfo] = useState(false);
  
  // Load architects on component mount
  useEffect(() => {
    fetchArchitects();
  }, []);
  
  const fetchArchitects = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/get_architects.php');
      const data = await response.json();
      if (data.success) {
        setArchitects(data.architects || []);
      }
    } catch (error) {
      showError('Error', 'Failed to load architects');
    }
  };
  
  const handleInputChange = (field, value) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));
  };
  
  const handleArrayToggle = (field, value) => {
    setFormData(prev => ({
      ...prev,
      [field]: prev[field].includes(value)
        ? prev[field].filter(item => item !== value)
        : [...prev[field], value]
    }));
  };
  
  const handleArchitectToggle = (architectId) => {
    setFormData(prev => ({
      ...prev,
      selected_architect_ids: prev.selected_architect_ids.includes(architectId)
        ? prev.selected_architect_ids.filter(id => id !== architectId)
        : [...prev.selected_architect_ids, architectId]
    }));
  };
  
  const validateStep = (step) => {
    switch (step) {
      case 1:
        return formData.plot_size && formData.budget_range && formData.location;
      case 2:
        return formData.bedrooms && formData.bathrooms;
      case 3:
        return true; // Feature selection is optional
      case 4:
        return formData.selected_architect_ids.length > 0;
      default:
        return true;
    }
  };
  
  const nextStep = () => {
    if (validateStep(currentStep)) {
      setCurrentStep(prev => Math.min(prev + 1, 4));
    } else {
      showError('Validation Error', 'Please fill in all required fields');
    }
  };
  
  const prevStep = () => {
    setCurrentStep(prev => Math.max(prev - 1, 1));
  };
  
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateStep(4)) {
      showError('Validation Error', 'Please select at least one architect');
      return;
    }
    
    setLoading(true);
    
    try {
      // Prepare enhanced request data
      const requestData = {
        ...formData,
        special_requirements: [
          ...(formData.vastu_compliance ? ['Vastu compliant design'] : []),
          ...(formData.parking_requirements !== 'none' ? [`${formData.parking_requirements.replace('_', ' ')} parking`] : []),
          ...formData.special_rooms.map(room => `${room.replace('_', ' ')} required`)
        ],
        house_plan_requirements: {
          plot_shape: formData.plot_shape,
          topography: formData.topography,
          rooms: {
            bedrooms: formData.bedrooms,
            bathrooms: formData.bathrooms,
            kitchen_type: formData.kitchen_type,
            living_areas: formData.living_areas,
            special_rooms: formData.special_rooms
          },
          floors: formData.num_floors,
          style_preference: formData.preferred_style,
          vastu_compliance: formData.vastu_compliance,
          parking_requirements: formData.parking_requirements
        }
      };
      
      const response = await fetch('/buildhub/backend/api/homeowner/submit_enhanced_request.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
      });
      
      const result = await response.json();
      
      if (result.success) {
        showSuccess('Success!', 'Your enhanced construction request has been submitted successfully!');
        
        // Show feature integration info
        showInfo('Features Enabled', 
          `Your project includes: ${Object.entries(result.features_enabled)
            .filter(([key, enabled]) => enabled)
            .map(([key]) => key.replace('_', ' '))
            .join(', ')}`
        );
        
        // Call parent callback
        if (onSubmit) {
          onSubmit(result);
        }
        
        // Close form after delay
        setTimeout(() => {
          if (onClose) onClose();
        }, 3000);
        
      } else {
        showError('Submission Failed', result.message || 'Failed to submit request');
      }
      
    } catch (error) {
      showError('Error', 'Network error occurred while submitting request');
    } finally {
      setLoading(false);
    }
  };
  
  const renderStep1 = () => (
    <div className="form-step">
      <h3>📋 Basic Project Information</h3>
      <p>Tell us about your construction project requirements</p>
      
      <div className="form-grid">
        <div className="form-group">
          <label>Plot Size *</label>
          <input
            type="text"
            placeholder="e.g., 30x40 feet or 1200 sqft"
            value={formData.plot_size}
            onChange={(e) => handleInputChange('plot_size', e.target.value)}
            required
          />
        </div>
        
        <div className="form-group">
          <label>Building Size</label>
          <input
            type="text"
            placeholder="e.g., 1500 sqft"
            value={formData.building_size}
            onChange={(e) => handleInputChange('building_size', e.target.value)}
          />
        </div>
        
        <div className="form-group">
          <label>Budget Range *</label>
          <select
            value={formData.budget_range}
            onChange={(e) => handleInputChange('budget_range', e.target.value)}
            required
          >
            <option value="">Select Budget Range</option>
            <option value="5-10 Lakhs">₹5-10 Lakhs</option>
            <option value="10-20 Lakhs">₹10-20 Lakhs</option>
            <option value="20-30 Lakhs">₹20-30 Lakhs</option>
            <option value="30-50 Lakhs">₹30-50 Lakhs</option>
            <option value="50+ Lakhs">₹50+ Lakhs</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Location *</label>
          <input
            type="text"
            placeholder="City, State"
            value={formData.location}
            onChange={(e) => handleInputChange('location', e.target.value)}
            required
          />
        </div>
        
        <div className="form-group">
          <label>Timeline</label>
          <select
            value={formData.timeline}
            onChange={(e) => handleInputChange('timeline', e.target.value)}
          >
            <option value="">Select Timeline</option>
            <option value="3-6 months">3-6 months</option>
            <option value="6-12 months">6-12 months</option>
            <option value="1-2 years">1-2 years</option>
            <option value="Flexible">Flexible</option>
          </select>
        </div>
        
        <div className="form-group full-width">
          <label>Additional Requirements</label>
          <textarea
            placeholder="Any specific requirements or preferences..."
            value={formData.requirements}
            onChange={(e) => handleInputChange('requirements', e.target.value)}
            rows="3"
          />
        </div>
      </div>
    </div>
  );
  
  const renderStep2 = () => (
    <div className="form-step">
      <h3>🏠 House Design Requirements</h3>
      <p>Specify your house layout and room requirements</p>
      
      <div className="form-grid">
        <div className="form-group">
          <label>Plot Shape</label>
          <select
            value={formData.plot_shape}
            onChange={(e) => handleInputChange('plot_shape', e.target.value)}
          >
            <option value="rectangular">Rectangular</option>
            <option value="square">Square</option>
            <option value="l_shaped">L-Shaped</option>
            <option value="irregular">Irregular</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Topography</label>
          <select
            value={formData.topography}
            onChange={(e) => handleInputChange('topography', e.target.value)}
          >
            <option value="flat">Flat Land</option>
            <option value="sloping">Sloping Land</option>
            <option value="hilly">Hilly Terrain</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Number of Floors</label>
          <select
            value={formData.num_floors}
            onChange={(e) => handleInputChange('num_floors', parseInt(e.target.value))}
          >
            <option value={1}>1 Floor (Ground)</option>
            <option value={2}>2 Floors (Ground + 1st)</option>
            <option value={3}>3 Floors (Ground + 1st + 2nd)</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Preferred Style</label>
          <select
            value={formData.preferred_style}
            onChange={(e) => handleInputChange('preferred_style', e.target.value)}
          >
            <option value="modern">Modern</option>
            <option value="traditional">Traditional</option>
            <option value="contemporary">Contemporary</option>
            <option value="kerala">Kerala Style</option>
            <option value="minimalist">Minimalist</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Bedrooms *</label>
          <select
            value={formData.bedrooms}
            onChange={(e) => handleInputChange('bedrooms', parseInt(e.target.value))}
            required
          >
            <option value="">Select</option>
            <option value={1}>1 Bedroom</option>
            <option value={2}>2 Bedrooms</option>
            <option value={3}>3 Bedrooms</option>
            <option value={4}>4 Bedrooms</option>
            <option value={5}>5+ Bedrooms</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Bathrooms *</label>
          <select
            value={formData.bathrooms}
            onChange={(e) => handleInputChange('bathrooms', parseInt(e.target.value))}
            required
          >
            <option value="">Select</option>
            <option value={1}>1 Bathroom</option>
            <option value={2}>2 Bathrooms</option>
            <option value={3}>3 Bathrooms</option>
            <option value={4}>4+ Bathrooms</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Kitchen Type</label>
          <select
            value={formData.kitchen_type}
            onChange={(e) => handleInputChange('kitchen_type', e.target.value)}
          >
            <option value="closed">Closed Kitchen</option>
            <option value="open">Open Kitchen</option>
            <option value="semi_open">Semi-Open Kitchen</option>
          </select>
        </div>
        
        <div className="form-group">
          <label>Parking Requirements</label>
          <select
            value={formData.parking_requirements}
            onChange={(e) => handleInputChange('parking_requirements', e.target.value)}
          >
            <option value="none">No Parking</option>
            <option value="one_car">1 Car Parking</option>
            <option value="two_car">2 Car Parking</option>
            <option value="covered_garage">Covered Garage</option>
          </select>
        </div>
      </div>
      
      <div className="checkbox-groups">
        <div className="checkbox-group">
          <label>Living Areas</label>
          <div className="checkbox-grid">
            {[
              { value: 'living_room', label: '🛋️ Living Room' },
              { value: 'dining_room', label: '🍽️ Dining Room' },
              { value: 'family_room', label: '👨‍👩‍👧‍👦 Family Room' },
              { value: 'drawing_room', label: '🪑 Drawing Room' }
            ].map(area => (
              <label key={area.value} className="checkbox-item">
                <input
                  type="checkbox"
                  checked={formData.living_areas.includes(area.value)}
                  onChange={() => handleArrayToggle('living_areas', area.value)}
                />
                {area.label}
              </label>
            ))}
          </div>
        </div>
        
        <div className="checkbox-group">
          <label>Special Rooms</label>
          <div className="checkbox-grid">
            {[
              { value: 'study_room', label: '📚 Study Room' },
              { value: 'pooja_room', label: '🙏 Pooja Room' },
              { value: 'guest_room', label: '🛏️ Guest Room' },
              { value: 'store_room', label: '📦 Store Room' },
              { value: 'utility_room', label: '🏠 Utility Room' },
              { value: 'home_theater', label: '🎬 Home Theater' }
            ].map(room => (
              <label key={room.value} className="checkbox-item">
                <input
                  type="checkbox"
                  checked={formData.special_rooms.includes(room.value)}
                  onChange={() => handleArrayToggle('special_rooms', room.value)}
                />
                {room.label}
              </label>
            ))}
          </div>
        </div>
      </div>
      
      <div className="form-group">
        <label className="checkbox-item">
          <input
            type="checkbox"
            checked={formData.vastu_compliance}
            onChange={(e) => handleInputChange('vastu_compliance', e.target.checked)}
          />
          🧭 Vastu Compliant Design Required
        </label>
      </div>
    </div>
  );
  
  const renderStep3 = () => (
    <div className="form-step">
      <h3>⚡ Enhanced Features</h3>
      <p>Enable advanced features for your construction project</p>
      
      <div className="feature-cards">
        <div className={`feature-card ${formData.requires_house_plan ? 'enabled' : ''}`}>
          <div className="feature-header">
            <span className="feature-icon">🏠</span>
            <h4>House Plan Designer</h4>
            <label className="toggle-switch">
              <input
                type="checkbox"
                checked={formData.requires_house_plan}
                onChange={(e) => handleInputChange('requires_house_plan', e.target.checked)}
              />
              <span className="slider"></span>
            </label>
          </div>
          <p>Interactive drag-and-drop house plan creation with 14 room templates, real-time measurements, and professional visualization.</p>
          <ul>
            <li>✅ Custom floor plan design</li>
            <li>✅ Room templates and layouts</li>
            <li>✅ Real-time area calculations</li>
            <li>✅ Professional architectural output</li>
          </ul>
        </div>
        
        <div className={`feature-card ${formData.enable_geo_photos ? 'enabled' : ''}`}>
          <div className="feature-header">
            <span className="feature-icon">📍</span>
            <h4>Geo-Tagged Photos</h4>
            <label className="toggle-switch">
              <input
                type="checkbox"
                checked={formData.enable_geo_photos}
                onChange={(e) => handleInputChange('enable_geo_photos', e.target.checked)}
              />
              <span className="slider"></span>
            </label>
          </div>
          <p>GPS-enabled construction documentation with automatic location tagging and coordinate display on photos.</p>
          <ul>
            <li>✅ Automatic GPS coordinate tagging</li>
            <li>✅ Visual location overlay on photos</li>
            <li>✅ Secure photo sharing</li>
            <li>✅ Construction progress documentation</li>
          </ul>
        </div>
        
        <div className={`feature-card ${formData.enable_progress_tracking ? 'enabled' : ''}`}>
          <div className="feature-header">
            <span className="feature-icon">📊</span>
            <h4>Progress Reports</h4>
            <label className="toggle-switch">
              <input
                type="checkbox"
                checked={formData.enable_progress_tracking}
                onChange={(e) => handleInputChange('enable_progress_tracking', e.target.checked)}
              />
              <span className="slider"></span>
            </label>
          </div>
          <p>Comprehensive construction tracking with photo-rich reports, milestone management, and real-time updates.</p>
          <ul>
            <li>✅ Detailed progress documentation</li>
            <li>✅ Milestone tracking and alerts</li>
            <li>✅ Photo-verified work completion</li>
            <li>✅ Timeline and budget monitoring</li>
          </ul>
        </div>
      </div>
      
      <div className="feature-info">
        <button 
          type="button" 
          className="info-button"
          onClick={() => setShowFeatureInfo(!showFeatureInfo)}
        >
          ℹ️ Learn More About Integration
        </button>
        
        {showFeatureInfo && (
          <div className="info-panel">
            <h4>🔗 How Features Work Together</h4>
            <div className="integration-flow">
              <div className="flow-step">
                <span className="step-number">1</span>
                <div className="step-content">
                  <h5>Request Submission</h5>
                  <p>Your requirements are sent to selected architects with feature integration instructions</p>
                </div>
              </div>
              <div className="flow-arrow">→</div>
              <div className="flow-step">
                <span className="step-number">2</span>
                <div className="step-content">
                  <h5>House Plan Creation</h5>
                  <p>Architects use the interactive designer to create custom floor plans based on your requirements</p>
                </div>
              </div>
              <div className="flow-arrow">→</div>
              <div className="flow-step">
                <span className="step-number">3</span>
                <div className="step-content">
                  <h5>Construction Tracking</h5>
                  <p>Contractors document progress with geo-tagged photos and detailed reports</p>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
  
  const renderStep4 = () => (
    <div className="form-step">
      <h3>👨‍💼 Select Architects</h3>
      <p>Choose architects to receive your integrated construction request</p>
      
      <div className="architect-selection">
        {architects.length === 0 ? (
          <div className="no-architects">
            <p>Loading architects...</p>
          </div>
        ) : (
          <div className="architect-grid">
            {architects.map(architect => (
              <div 
                key={architect.id} 
                className={`architect-card ${formData.selected_architect_ids.includes(architect.id) ? 'selected' : ''}`}
                onClick={() => handleArchitectToggle(architect.id)}
              >
                <div className="architect-header">
                  <div className="architect-avatar">
                    {architect.name ? architect.name.charAt(0).toUpperCase() : '👨‍💼'}
                  </div>
                  <div className="architect-info">
                    <h4>{architect.name || 'Architect'}</h4>
                    <p>{architect.specialization || 'Residential Architecture'}</p>
                  </div>
                  <div className="selection-indicator">
                    {formData.selected_architect_ids.includes(architect.id) ? '✅' : '⭕'}
                  </div>
                </div>
                <div className="architect-details">
                  <div className="detail-item">
                    <span>📍 Location:</span>
                    <span>{architect.location || 'Not specified'}</span>
                  </div>
                  <div className="detail-item">
                    <span>⭐ Rating:</span>
                    <span>{architect.rating || 'New'}</span>
                  </div>
                  <div className="detail-item">
                    <span>🏗️ Projects:</span>
                    <span>{architect.project_count || 0}</span>
                  </div>
                </div>
                <div className="architect-features">
                  <span className="feature-badge">🏠 House Plans</span>
                  <span className="feature-badge">📍 Geo Photos</span>
                  <span className="feature-badge">📊 Progress Reports</span>
                </div>
              </div>
            ))}
          </div>
        )}
        
        <div className="selection-summary">
          <p>
            Selected: <strong>{formData.selected_architect_ids.length}</strong> architect(s)
            {formData.selected_architect_ids.length === 0 && (
              <span className="error-text"> - Please select at least one architect</span>
            )}
          </p>
        </div>
      </div>
    </div>
  );
  
  return (
    <div className="enhanced-request-form-overlay">
      <div className="enhanced-request-form">
        <div className="form-header">
          <h2>🚀 Create Enhanced Construction Request</h2>
          <button className="close-button" onClick={onClose}>×</button>
        </div>
        
        <div className="form-progress">
          <div className="progress-steps">
            {[1, 2, 3, 4].map(step => (
              <div 
                key={step} 
                className={`progress-step ${currentStep >= step ? 'active' : ''} ${currentStep === step ? 'current' : ''}`}
              >
                <span className="step-number">{step}</span>
                <span className="step-label">
                  {step === 1 && 'Basic Info'}
                  {step === 2 && 'House Design'}
                  {step === 3 && 'Features'}
                  {step === 4 && 'Architects'}
                </span>
              </div>
            ))}
          </div>
          <div className="progress-bar">
            <div 
              className="progress-fill" 
              style={{ width: `${(currentStep / 4) * 100}%` }}
            />
          </div>
        </div>
        
        <form onSubmit={handleSubmit}>
          <div className="form-content">
            {currentStep === 1 && renderStep1()}
            {currentStep === 2 && renderStep2()}
            {currentStep === 3 && renderStep3()}
            {currentStep === 4 && renderStep4()}
          </div>
          
          <div className="form-actions">
            {currentStep > 1 && (
              <button type="button" className="btn-secondary" onClick={prevStep}>
                ← Previous
              </button>
            )}
            
            {currentStep < 4 ? (
              <button type="button" className="btn-primary" onClick={nextStep}>
                Next →
              </button>
            ) : (
              <button type="submit" className="btn-primary" disabled={loading}>
                {loading ? '🔄 Submitting...' : '🚀 Submit Enhanced Request'}
              </button>
            )}
          </div>
        </form>
        
        <NotificationToast 
          notifications={notifications}
          onRemove={removeNotification}
        />
      </div>
    </div>
  );
};

export default EnhancedRequestForm;