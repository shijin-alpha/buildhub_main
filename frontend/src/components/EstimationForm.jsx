import React, { useState, useEffect, useRef } from 'react';
import '../styles/EstimationForm.css';

const EstimationForm = ({ isOpen, onClose, inboxItem, onSubmit }) => {
  const [activeSection, setActiveSection] = useState('basic');
  const [formData, setFormData] = useState({
    // Basic Info
    project_name: '',
    location: '',
    client_name: '',
    project_type: 'Residential',
    timeline: '90 days',
    
    // Materials
    materials: {
      cement: { qty: '50', rate: '400', amount: '20000' },
      sand: { qty: '5', rate: '2000', amount: '10000' },
      bricks: { qty: '2000', rate: '8', amount: '16000' },
      steel: { qty: '1', rate: '15000', amount: '15000' },
      aggregate: { qty: '3', rate: '1500', amount: '4500' }
    },
    
    // Labor
    labor: {
      masonry: { qty: '1', rate: '15000', amount: '15000' },
      plumbing: { qty: '1', rate: '12000', amount: '12000' },
      electrical: { qty: '1', rate: '10000', amount: '10000' },
      painting: { qty: '1', rate: '8000', amount: '8000' },
      flooring: { qty: '1', rate: '5000', amount: '5000' }
    },
    
    // Utilities
    utilities: {
      sanitary: { qty: '1', rate: '8000', amount: '8000' },
      kitchen: { qty: '1', rate: '12000', amount: '12000' },
      fixtures: { qty: '1', rate: '6000', amount: '6000' }
    },
    
    // Miscellaneous
    misc: {
      transport: { qty: '1', rate: '5000', amount: '5000' },
      contingency: { qty: '1', rate: '4600', amount: '4600' },
      permits: { qty: '1', rate: '2000', amount: '2000' }
    },
    
    // Additional details
    notes: '',
    terms: 'Payment: 30% advance, 40% on foundation completion, 30% on project completion'
  });

  const [totals, setTotals] = useState({
    materials: 0,
    labor: 0,
    utilities: 0,
    misc: 0,
    grand: 0
  });

  const formContentRef = useRef(null);

  // Initialize form data from inbox item
  useEffect(() => {
    if (inboxItem && isOpen) {
      console.log('🔍 EstimationForm: Initializing with inbox item:', inboxItem);
      
      const payload = inboxItem.payload || {};
      const layoutRequestDetails = inboxItem.layout_request_details || {};
      const parsedRequirements = inboxItem.parsed_requirements || {};
      
      console.log('🔍 EstimationForm: Payload:', payload);
      console.log('🔍 EstimationForm: Layout request details:', layoutRequestDetails);
      console.log('🔍 EstimationForm: Parsed requirements:', parsedRequirements);
      
      const newFormData = {
        ...formData,
        project_name: payload.plan_name || `Project for ${inboxItem.homeowner_name}`,
        client_name: inboxItem.homeowner_name || '',
        location: layoutRequestDetails.location || payload.location || '',
        timeline: layoutRequestDetails.timeline || inboxItem.timeline || '90 days',
        // Add site details
        plot_size: inboxItem.plot_size || layoutRequestDetails.plot_size || '',
        building_size: inboxItem.building_size || layoutRequestDetails.building_size || '',
        budget_range: inboxItem.budget_range || layoutRequestDetails.budget_range || '',
        num_floors: inboxItem.num_floors || layoutRequestDetails.num_floors || '',
        orientation: inboxItem.orientation || layoutRequestDetails.orientation || '',
        site_considerations: inboxItem.site_considerations || layoutRequestDetails.site_considerations || '',
        material_preferences: inboxItem.material_preferences || layoutRequestDetails.material_preferences || '',
        budget_allocation: inboxItem.budget_allocation || layoutRequestDetails.budget_allocation || '',
        preferred_style: inboxItem.preferred_style || layoutRequestDetails.preferred_style || '',
        plot_shape: parsedRequirements.plot_shape || '',
        topography: parsedRequirements.topography || '',
        development_laws: parsedRequirements.development_laws || '',
        family_needs: parsedRequirements.family_needs || '',
        rooms: parsedRequirements.rooms || '',
        aesthetic: parsedRequirements.aesthetic || ''
      };
      
      console.log('🔍 EstimationForm: Setting form data:', newFormData);
      setFormData(newFormData);
    }
  }, [inboxItem, isOpen]);

  // Calculate totals whenever form data changes
  useEffect(() => {
    const calculateSectionTotal = (section) => {
      return Object.values(section).reduce((sum, item) => {
        return sum + (parseFloat(item.amount) || 0);
      }, 0);
    };

    const materialsTotal = calculateSectionTotal(formData.materials);
    const laborTotal = calculateSectionTotal(formData.labor);
    const utilitiesTotal = calculateSectionTotal(formData.utilities);
    const miscTotal = calculateSectionTotal(formData.misc);
    const grandTotal = materialsTotal + laborTotal + utilitiesTotal + miscTotal;

    setTotals({
      materials: materialsTotal,
      labor: laborTotal,
      utilities: utilitiesTotal,
      misc: miscTotal,
      grand: grandTotal
    });
  }, [formData]);

  const handleInputChange = (section, item, field, value) => {
    setFormData(prev => {
      const newData = { ...prev };
      if (section && item) {
        if (!newData[section]) newData[section] = {};
        if (!newData[section][item]) newData[section][item] = {};
        newData[section][item][field] = value;
        
        // Auto-calculate amount when qty or rate changes
        if (field === 'qty' || field === 'rate') {
          const qty = parseFloat(newData[section][item].qty) || 0;
          const rate = parseFloat(newData[section][item].rate) || 0;
          newData[section][item].amount = (qty * rate).toString();
        }
      } else {
        newData[field] = value;
      }
      return newData;
    });
  };

  const scrollToSection = (sectionId) => {
    setActiveSection(sectionId);
    const element = document.getElementById(`section-${sectionId}`);
    if (element && formContentRef.current) {
      const container = formContentRef.current;
      const elementRect = element.getBoundingClientRect();
      const containerRect = container.getBoundingClientRect();
      const scrollTop = container.scrollTop;
      const targetScrollTop = scrollTop + elementRect.top - containerRect.top - 20;
      
      container.scrollTo({ 
        top: targetScrollTop, 
        behavior: 'smooth' 
      });
    }
  };

  const scrollToTop = () => {
    if (formContentRef.current) {
      formContentRef.current.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const scrollToBottom = () => {
    if (formContentRef.current) {
      formContentRef.current.scrollTo({ 
        top: formContentRef.current.scrollHeight, 
        behavior: 'smooth' 
      });
    }
  };

  const handleSubmit = () => {
    const estimateData = {
      ...formData,
      totals,
      inbox_item_id: inboxItem?.id,
      homeowner_id: inboxItem?.homeowner_id,
      contractor_id: JSON.parse(sessionStorage.getItem('user') || '{}').id
    };
    
    onSubmit(estimateData);
  };

  const renderFormSection = (sectionId, title, icon, items, unit = '') => (
    <div className="form-section" id={`section-${sectionId}`}>
      <h3 className="section-title">
        <span className="section-icon">{icon}</span>
        {title}
      </h3>
      <div className="section-content">
        <div className="items-grid">
          {Object.entries(items).map(([key, item]) => (
            <div key={key} className="item-row">
              <div className="item-name">{key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</div>
              <div className="item-inputs">
                <input
                  type="number"
                  placeholder="Qty"
                  value={item.qty}
                  onChange={(e) => handleInputChange(sectionId, key, 'qty', e.target.value)}
                  className="qty-input"
                />
                <span className="unit">{unit}</span>
                <input
                  type="number"
                  placeholder="Rate"
                  value={item.rate}
                  onChange={(e) => handleInputChange(sectionId, key, 'rate', e.target.value)}
                  className="rate-input"
                />
                <div className="amount">₹{parseFloat(item.amount || 0).toLocaleString('en-IN')}</div>
              </div>
            </div>
          ))}
        </div>
        <div className="section-total">
          Total {title}: ₹{totals[sectionId]?.toLocaleString('en-IN') || '0'}
        </div>
      </div>
    </div>
  );

  if (!isOpen) return null;

  return (
    <div className="estimation-modal">
      <div className="modal-content">
        <div className="enhanced-estimation-form">
          {/* Floating Close Button */}
          <button className="floating-close-btn" onClick={onClose} title="Close Form">
            ✕
          </button>

          <div className="form-header">
            <div className="form-title">
              <h2>📊 Enhanced Cost Estimation</h2>
              <p>Create detailed and professional cost estimates</p>
            </div>
            
            <div className="form-actions">
              <button className="btn btn-secondary">Show Preview</button>
              <button className="btn btn-secondary">💾 Save Draft</button>
              <button className="btn btn-secondary" onClick={scrollToBottom}>⬇️ Go to Submit</button>
              <button className="btn btn-close" onClick={onClose}>✕ Close</button>
            </div>
          </div>

          <div className="form-layout">
            {/* Navigation */}
            <div className="form-navigation">
              <div className="navigation-header">
                <h4>📋 Form Sections</h4>
                <div className="scroll-controls">
                  <button className="scroll-control-btn" onClick={scrollToTop}>⬆️</button>
                  <button className="scroll-control-btn" onClick={scrollToBottom}>⬇️</button>
                </div>
              </div>
              
              <button 
                className={`nav-item ${activeSection === 'basic' ? 'active' : ''}`} 
                onClick={() => scrollToSection('basic')}
              >
                <span className="nav-icon">📋</span>
                <span className="nav-title">Basic Info</span>
              </button>
              <button 
                className={`nav-item ${activeSection === 'site' ? 'active' : ''}`} 
                onClick={() => scrollToSection('site')}
              >
                <span className="nav-icon">🏗️</span>
                <span className="nav-title">Site Details</span>
              </button>
              <button 
                className={`nav-item ${activeSection === 'materials' ? 'active' : ''}`} 
                onClick={() => scrollToSection('materials')}
              >
                <span className="nav-icon">🧱</span>
                <span className="nav-title">Materials</span>
              </button>
              <button 
                className={`nav-item ${activeSection === 'labor' ? 'active' : ''}`} 
                onClick={() => scrollToSection('labor')}
              >
                <span className="nav-icon">👷</span>
                <span className="nav-title">Labor</span>
              </button>
              <button 
                className={`nav-item ${activeSection === 'utilities' ? 'active' : ''}`} 
                onClick={() => scrollToSection('utilities')}
              >
                <span className="nav-icon">🔧</span>
                <span className="nav-title">Utilities</span>
              </button>
              <button 
                className={`nav-item ${activeSection === 'misc' ? 'active' : ''}`} 
                onClick={() => scrollToSection('misc')}
              >
                <span className="nav-icon">📦</span>
                <span className="nav-title">Miscellaneous</span>
              </button>
              <button 
                className={`nav-item ${activeSection === 'totals' ? 'active' : ''}`} 
                onClick={() => scrollToSection('totals')}
              >
                <span className="nav-icon">💰</span>
                <span className="nav-title">Totals</span>
              </button>
              
              <button className="nav-item submit-nav" onClick={scrollToBottom}>
                <span className="nav-icon">🚀</span>
                <span className="nav-title">Submit Form</span>
              </button>
            </div>

            {/* Form Content */}
            <div className="form-content" ref={formContentRef}>
              {/* Basic Info Section */}
              <div className="form-section" id="section-basic">
                <h3 className="section-title">
                  <span className="section-icon">📋</span>
                  Basic Project Information
                </h3>
                <div className="section-content">
                  <div className="basic-info-grid">
                    <div className="input-group">
                      <label>Project Name</label>
                      <input
                        type="text"
                        value={formData.project_name}
                        onChange={(e) => handleInputChange(null, null, 'project_name', e.target.value)}
                        placeholder="Enter project name"
                      />
                    </div>
                    <div className="input-group">
                      <label>Location</label>
                      <input
                        type="text"
                        value={formData.location}
                        onChange={(e) => handleInputChange(null, null, 'location', e.target.value)}
                        placeholder="Enter project location"
                      />
                    </div>
                    <div className="input-group">
                      <label>Client Name</label>
                      <input
                        type="text"
                        value={formData.client_name}
                        onChange={(e) => handleInputChange(null, null, 'client_name', e.target.value)}
                        placeholder="Enter client name"
                      />
                    </div>
                    <div className="input-group">
                      <label>Project Type</label>
                      <select
                        value={formData.project_type}
                        onChange={(e) => handleInputChange(null, null, 'project_type', e.target.value)}
                      >
                        <option value="Residential">Residential</option>
                        <option value="Commercial">Commercial</option>
                        <option value="Industrial">Industrial</option>
                      </select>
                    </div>
                    <div className="input-group">
                      <label>Timeline</label>
                      <input
                        type="text"
                        value={formData.timeline}
                        onChange={(e) => handleInputChange(null, null, 'timeline', e.target.value)}
                        placeholder="e.g., 90 days"
                      />
                    </div>
                    <div className="input-group">
                      <label>Plot Size</label>
                      <input
                        type="text"
                        value={formData.plot_size}
                        onChange={(e) => handleInputChange(null, null, 'plot_size', e.target.value)}
                        placeholder="e.g., 2500 sq ft"
                      />
                    </div>
                    <div className="input-group">
                      <label>Building Size</label>
                      <input
                        type="text"
                        value={formData.building_size}
                        onChange={(e) => handleInputChange(null, null, 'building_size', e.target.value)}
                        placeholder="e.g., 1800 sq ft"
                      />
                    </div>
                  </div>
                </div>
              </div>

              {/* Site Details Section */}
              <div className="form-section" id="section-site">
                <h3 className="section-title">
                  <span className="section-icon">🏗️</span>
                  Site & Project Details
                </h3>
                <div className="section-content">
                  <div className="site-details-grid">
                    <div className="detail-group">
                      <h4>📐 Site Specifications</h4>
                      <div className="detail-row">
                        <label>Plot Size:</label>
                        <span>{formData.plot_size || 'Not specified'}</span>
                      </div>
                      <div className="detail-row">
                        <label>Building Size:</label>
                        <span>{formData.building_size || 'Not specified'}</span>
                      </div>
                      <div className="detail-row">
                        <label>Number of Floors:</label>
                        <span>{formData.num_floors || 'Not specified'}</span>
                      </div>
                      <div className="detail-row">
                        <label>Plot Shape:</label>
                        <span>{formData.plot_shape || 'Not specified'}</span>
                      </div>
                      <div className="detail-row">
                        <label>Topography:</label>
                        <span>{formData.topography || 'Not specified'}</span>
                      </div>
                      <div className="detail-row">
                        <label>Orientation:</label>
                        <span>{formData.orientation || 'Not specified'}</span>
                      </div>
                    </div>
                    
                    <div className="detail-group">
                      <h4>💰 Budget & Timeline</h4>
                      <div className="detail-row">
                        <label>Budget Range:</label>
                        <span>{formData.budget_range || 'Not specified'}</span>
                      </div>
                      <div className="detail-row">
                        <label>Budget Allocation:</label>
                        <span>{formData.budget_allocation || 'Not specified'}</span>
                      </div>
                      <div className="detail-row">
                        <label>Timeline:</label>
                        <span>{formData.timeline || 'Not specified'}</span>
                      </div>
                    </div>
                    
                    <div className="detail-group">
                      <h4>🎨 Design Preferences</h4>
                      <div className="detail-row">
                        <label>Preferred Style:</label>
                        <span>{formData.preferred_style || 'Not specified'}</span>
                      </div>
                      <div className="detail-row">
                        <label>Aesthetic:</label>
                        <span>{formData.aesthetic || 'Not specified'}</span>
                      </div>
                      <div className="detail-row">
                        <label>Material Preferences:</label>
                        <span>{formData.material_preferences || 'Not specified'}</span>
                      </div>
                    </div>
                    
                    <div className="detail-group">
                      <h4>🏠 Requirements</h4>
                      <div className="detail-row">
                        <label>Family Needs:</label>
                        <span>{formData.family_needs || 'Not specified'}</span>
                      </div>
                      <div className="detail-row">
                        <label>Rooms:</label>
                        <span>{formData.rooms || 'Not specified'}</span>
                      </div>
                      <div className="detail-row">
                        <label>Development Laws:</label>
                        <span>{formData.development_laws || 'Not specified'}</span>
                      </div>
                      {formData.site_considerations && (
                        <div className="detail-row">
                          <label>Site Considerations:</label>
                          <span>{formData.site_considerations}</span>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </div>

              {/* Materials Section */}
              {renderFormSection('materials', 'Material Costs', '🧱', formData.materials, 'units')}

              {/* Labor Section */}
              {renderFormSection('labor', 'Labor Charges', '👷', formData.labor, 'work')}

              {/* Utilities Section */}
              {renderFormSection('utilities', 'Utilities & Fixtures', '🔧', formData.utilities, 'set')}

              {/* Miscellaneous Section */}
              {renderFormSection('misc', 'Miscellaneous Costs', '📦', formData.misc, 'item')}

              {/* Totals Section */}
              <div className="form-section" id="section-totals">
                <h3 className="section-title">
                  <span className="section-icon">💰</span>
                  Cost Summary
                </h3>
                <div className="section-content">
                  <div className="totals-grid">
                    <div className="total-item">
                      <span>Materials Cost:</span>
                      <span>₹{totals.materials.toLocaleString('en-IN')}</span>
                    </div>
                    <div className="total-item">
                      <span>Labor Cost:</span>
                      <span>₹{totals.labor.toLocaleString('en-IN')}</span>
                    </div>
                    <div className="total-item">
                      <span>Utilities Cost:</span>
                      <span>₹{totals.utilities.toLocaleString('en-IN')}</span>
                    </div>
                    <div className="total-item">
                      <span>Miscellaneous Cost:</span>
                      <span>₹{totals.misc.toLocaleString('en-IN')}</span>
                    </div>
                    <div className="total-item grand-total">
                      <span>GRAND TOTAL:</span>
                      <span>₹{totals.grand.toLocaleString('en-IN')}</span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Additional Details Section */}
              <div className="form-section" id="section-details">
                <h3 className="section-title">
                  <span className="section-icon">📝</span>
                  Additional Details
                </h3>
                <div className="section-content">
                  <div className="input-group">
                    <label>Notes & Specifications</label>
                    <textarea
                      value={formData.notes}
                      onChange={(e) => handleInputChange(null, null, 'notes', e.target.value)}
                      placeholder="Add any additional notes, specifications, or requirements..."
                      rows="4"
                    />
                  </div>
                  <div className="input-group">
                    <label>Terms & Conditions</label>
                    <textarea
                      value={formData.terms}
                      onChange={(e) => handleInputChange(null, null, 'terms', e.target.value)}
                      placeholder="Payment terms, warranty, timeline conditions..."
                      rows="3"
                    />
                  </div>
                </div>
              </div>

              {/* Submit Section */}
              <div className="form-footer" id="form-submit-section">
                <div className="form-progress">
                  <div className="progress-info">
                    <span className="progress-text">
                      🎉 Ready to submit your professional estimate
                    </span>
                    <div className="progress-stats">
                      <span>Total: ₹{totals.grand.toLocaleString('en-IN')}</span>
                      <span>•</span>
                      <span>Timeline: {formData.timeline}</span>
                      <span>•</span>
                      <span>✅ All sections complete</span>
                    </div>
                  </div>
                </div>
                
                <div className="form-actions-row">
                  <button className="btn btn-secondary" onClick={onClose}>Cancel</button>
                  <button className="btn btn-secondary">Reset Form</button>
                  <button className="btn btn-secondary" onClick={scrollToTop}>⬆️ Back to Top</button>
                  <button className="submit-btn" onClick={handleSubmit}>🚀 Submit Professional Estimate</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default EstimationForm;