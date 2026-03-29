import React, { useState, useRef } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/InspectionReportForm.css';

const Icon = ({ name, size = 20, stroke = 1.8, color = 'currentColor' }) => {
  const common = { width: size, height: size, viewBox: '0 0 24 24', fill: 'none', stroke: color, strokeWidth: stroke, strokeLinecap: 'round', strokeLinejoin: 'round' };
  switch (name) {
    case 'x':
      return (<svg {...common}><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>);
    case 'camera':
      return (<svg {...common}><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>);
    case 'upload':
      return (<svg {...common}><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>);
    case 'check':
      return (<svg {...common}><path d="M20 6L9 17l-5-5"/></svg>);
    case 'plus':
      return (<svg {...common}><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>);
    case 'trash':
      return (<svg {...common}><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>);
    default:
      return null;
  }
};

const InspectionReportForm = ({ project, onClose, onSubmitted }) => {
  const toast = useToast();
  const fileInputRef = useRef(null);
  
  const [formData, setFormData] = useState({
    inspection_date: new Date().toISOString().split('T')[0],
    inspection_stage: project.current_stage || '',
    inspection_type: 'routine',
    overall_status: 'pending',
    quality_score: '',
    safety_compliance: 'compliant',
    notes: '',
    recommendations: '',
    next_inspection_date: ''
  });

  const [checklistItems, setChecklistItems] = useState([
    { category: 'Foundation', item_description: 'Foundation depth as per approved plans', status: 'pending', notes: '', priority: 'medium' },
    { category: 'Foundation', item_description: 'Concrete quality and curing', status: 'pending', notes: '', priority: 'medium' },
    { category: 'Structure', item_description: 'Column alignment and dimensions', status: 'pending', notes: '', priority: 'high' },
    { category: 'Structure', item_description: 'Beam reinforcement and concrete quality', status: 'pending', notes: '', priority: 'high' },
    { category: 'Electrical', item_description: 'Conduit installation as per code', status: 'pending', notes: '', priority: 'medium' },
    { category: 'Plumbing', item_description: 'Pipe installation and testing', status: 'pending', notes: '', priority: 'medium' },
    { category: 'Safety', item_description: 'Safety equipment availability', status: 'pending', notes: '', priority: 'high' },
    { category: 'Quality', item_description: 'Material quality as per specifications', status: 'pending', notes: '', priority: 'medium' }
  ]);

  const [selectedFiles, setSelectedFiles] = useState([]);
  const [uploading, setUploading] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleChecklistChange = (index, field, value) => {
    setChecklistItems(prev => prev.map((item, i) => 
      i === index ? { ...item, [field]: value } : item
    ));
  };

  const addChecklistItem = () => {
    setChecklistItems(prev => [...prev, {
      category: 'General',
      item_description: '',
      status: 'pending',
      notes: '',
      priority: 'medium'
    }]);
  };

  const removeChecklistItem = (index) => {
    setChecklistItems(prev => prev.filter((_, i) => i !== index));
  };

  const handleFileSelect = (e) => {
    const files = Array.from(e.target.files);
    const validFiles = files.filter(file => {
      const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
      const maxSize = 10 * 1024 * 1024; // 10MB
      
      if (!validTypes.includes(file.type)) {
        toast.error(`Invalid file type: ${file.name}`);
        return false;
      }
      
      if (file.size > maxSize) {
        toast.error(`File too large: ${file.name}`);
        return false;
      }
      
      return true;
    });

    setSelectedFiles(prev => [...prev, ...validFiles.map(file => ({
      file,
      caption: '',
      photo_type: 'progress'
    }))]);
  };

  const removeFile = (index) => {
    setSelectedFiles(prev => prev.filter((_, i) => i !== index));
  };

  const updateFileMetadata = (index, field, value) => {
    setSelectedFiles(prev => prev.map((item, i) => 
      i === index ? { ...item, [field]: value } : item
    ));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);

    try {
      // First, create the inspection report
      const reportResponse = await fetch('/buildhub/backend/api/inspector/create_inspection_report.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          project_id: project.project_id,
          ...formData,
          checklist_items: checklistItems.filter(item => item.item_description.trim() !== '')
        })
      });

      const reportData = await reportResponse.json();

      if (!reportData.success) {
        throw new Error(reportData.message);
      }

      // If there are files to upload, upload them
      if (selectedFiles.length > 0) {
        setUploading(true);
        
        const uploadFormData = new FormData();
        uploadFormData.append('inspection_report_id', reportData.inspection_report_id);
        
        selectedFiles.forEach((fileItem, index) => {
          uploadFormData.append('photos[]', fileItem.file);
          uploadFormData.append('captions[]', fileItem.caption);
          uploadFormData.append('photo_types[]', fileItem.photo_type);
          
          // Add GPS coordinates if available
          if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((position) => {
              uploadFormData.append('latitude[]', position.coords.latitude);
              uploadFormData.append('longitude[]', position.coords.longitude);
              uploadFormData.append('location_accuracy[]', position.coords.accuracy);
            });
          } else {
            uploadFormData.append('latitude[]', '');
            uploadFormData.append('longitude[]', '');
            uploadFormData.append('location_accuracy[]', '');
          }
        });

        const uploadResponse = await fetch('/buildhub/backend/api/inspector/upload_inspection_photos.php', {
          method: 'POST',
          credentials: 'include',
          body: uploadFormData
        });

        const uploadData = await uploadResponse.json();
        
        if (!uploadData.success) {
          console.warn('File upload failed:', uploadData.message);
          toast.warning('Report created but some files failed to upload');
        }
      }

      toast.success('Inspection report submitted successfully');
      onSubmitted();
      
    } catch (error) {
      console.error('Error submitting inspection report:', error);
      toast.error(error.message || 'Failed to submit inspection report');
    } finally {
      setSubmitting(false);
      setUploading(false);
    }
  };

  return (
    <div className="inspection-report-modal">
      <div className="modal-overlay" onClick={onClose}></div>
      <div className="modal-content">
        <div className="modal-header">
          <h2>Create Inspection Report</h2>
          <p>{project.project_name}</p>
          <button className="close-btn" onClick={onClose}>
            <Icon name="x" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="inspection-form">
          <div className="form-sections">
            {/* Basic Information */}
            <section className="form-section">
              <h3>Inspection Details</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label htmlFor="inspection_date">Inspection Date</label>
                  <input
                    type="date"
                    id="inspection_date"
                    name="inspection_date"
                    value={formData.inspection_date}
                    onChange={handleInputChange}
                    required
                  />
                </div>
                
                <div className="form-group">
                  <label htmlFor="inspection_stage">Construction Stage</label>
                  <select
                    id="inspection_stage"
                    name="inspection_stage"
                    value={formData.inspection_stage}
                    onChange={handleInputChange}
                    required
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
                </div>

                <div className="form-group">
                  <label htmlFor="inspection_type">Inspection Type</label>
                  <select
                    id="inspection_type"
                    name="inspection_type"
                    value={formData.inspection_type}
                    onChange={handleInputChange}
                    required
                  >
                    <option value="routine">Routine</option>
                    <option value="milestone">Milestone</option>
                    <option value="quality">Quality Check</option>
                    <option value="safety">Safety Inspection</option>
                    <option value="final">Final Inspection</option>
                  </select>
                </div>

                <div className="form-group">
                  <label htmlFor="overall_status">Overall Status</label>
                  <select
                    id="overall_status"
                    name="overall_status"
                    value={formData.overall_status}
                    onChange={handleInputChange}
                    required
                  >
                    <option value="pending">Pending Review</option>
                    <option value="approved">Approved</option>
                    <option value="needs_attention">Needs Attention</option>
                    <option value="rejected">Rejected</option>
                  </select>
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
                    value={formData.quality_score}
                    onChange={handleInputChange}
                    placeholder="Rate quality out of 10"
                  />
                </div>

                <div className="form-group">
                  <label htmlFor="safety_compliance">Safety Compliance</label>
                  <select
                    id="safety_compliance"
                    name="safety_compliance"
                    value={formData.safety_compliance}
                    onChange={handleInputChange}
                  >
                    <option value="compliant">Compliant</option>
                    <option value="partial">Partially Compliant</option>
                    <option value="non_compliant">Non-Compliant</option>
                  </select>
                </div>
              </div>
            </section>

            {/* Inspection Checklist */}
            <section className="form-section">
              <div className="section-header">
                <h3>Inspection Checklist</h3>
                <button type="button" onClick={addChecklistItem} className="add-item-btn">
                  <Icon name="plus" size={16} />
                  Add Item
                </button>
              </div>
              
              <div className="checklist-items">
                {checklistItems.map((item, index) => (
                  <div key={index} className="checklist-item">
                    <div className="item-header">
                      <select
                        value={item.category}
                        onChange={(e) => handleChecklistChange(index, 'category', e.target.value)}
                        className="category-select"
                      >
                        <option value="Foundation">Foundation</option>
                        <option value="Structure">Structure</option>
                        <option value="Electrical">Electrical</option>
                        <option value="Plumbing">Plumbing</option>
                        <option value="Safety">Safety</option>
                        <option value="Quality">Quality</option>
                        <option value="General">General</option>
                      </select>
                      
                      <button 
                        type="button" 
                        onClick={() => removeChecklistItem(index)}
                        className="remove-item-btn"
                      >
                        <Icon name="trash" size={16} />
                      </button>
                    </div>
                    
                    <input
                      type="text"
                      placeholder="Item description"
                      value={item.item_description}
                      onChange={(e) => handleChecklistChange(index, 'item_description', e.target.value)}
                      className="item-description"
                    />
                    
                    <div className="item-controls">
                      <select
                        value={item.status}
                        onChange={(e) => handleChecklistChange(index, 'status', e.target.value)}
                        className="status-select"
                      >
                        <option value="pending">Pending</option>
                        <option value="pass">Pass</option>
                        <option value="fail">Fail</option>
                        <option value="na">N/A</option>
                      </select>
                      
                      <select
                        value={item.priority}
                        onChange={(e) => handleChecklistChange(index, 'priority', e.target.value)}
                        className="priority-select"
                      >
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                      </select>
                    </div>
                    
                    <textarea
                      placeholder="Notes (optional)"
                      value={item.notes}
                      onChange={(e) => handleChecklistChange(index, 'notes', e.target.value)}
                      className="item-notes"
                      rows="2"
                    />
                  </div>
                ))}
              </div>
            </section>

            {/* Notes and Recommendations */}
            <section className="form-section">
              <h3>Notes & Recommendations</h3>
              <div className="form-group">
                <label htmlFor="notes">Inspection Notes</label>
                <textarea
                  id="notes"
                  name="notes"
                  value={formData.notes}
                  onChange={handleInputChange}
                  rows="4"
                  placeholder="Detailed notes about the inspection..."
                />
              </div>
              
              <div className="form-group">
                <label htmlFor="recommendations">Recommendations</label>
                <textarea
                  id="recommendations"
                  name="recommendations"
                  value={formData.recommendations}
                  onChange={handleInputChange}
                  rows="3"
                  placeholder="Recommendations for improvement or next steps..."
                />
              </div>
              
              <div className="form-group">
                <label htmlFor="next_inspection_date">Next Inspection Date</label>
                <input
                  type="date"
                  id="next_inspection_date"
                  name="next_inspection_date"
                  value={formData.next_inspection_date}
                  onChange={handleInputChange}
                />
              </div>
            </section>

            {/* Photo Upload */}
            <section className="form-section">
              <h3>Photos & Documents</h3>
              <div className="upload-area">
                <input
                  type="file"
                  ref={fileInputRef}
                  onChange={handleFileSelect}
                  multiple
                  accept="image/*,.pdf"
                  style={{ display: 'none' }}
                />
                
                <button 
                  type="button" 
                  onClick={() => fileInputRef.current?.click()}
                  className="upload-btn"
                >
                  <Icon name="camera" />
                  Add Photos/Documents
                </button>
                
                <p className="upload-help">
                  Upload inspection photos and documents (JPG, PNG, PDF - Max 10MB each)
                </p>
              </div>
              
              {selectedFiles.length > 0 && (
                <div className="selected-files">
                  {selectedFiles.map((fileItem, index) => (
                    <div key={index} className="file-item">
                      <div className="file-info">
                        <strong>{fileItem.file.name}</strong>
                        <span>{(fileItem.file.size / 1024 / 1024).toFixed(2)} MB</span>
                      </div>
                      
                      <div className="file-metadata">
                        <input
                          type="text"
                          placeholder="Caption (optional)"
                          value={fileItem.caption}
                          onChange={(e) => updateFileMetadata(index, 'caption', e.target.value)}
                        />
                        
                        <select
                          value={fileItem.photo_type}
                          onChange={(e) => updateFileMetadata(index, 'photo_type', e.target.value)}
                        >
                          <option value="progress">Progress</option>
                          <option value="issue">Issue</option>
                          <option value="quality">Quality</option>
                          <option value="safety">Safety</option>
                          <option value="completion">Completion</option>
                        </select>
                        
                        <button 
                          type="button" 
                          onClick={() => removeFile(index)}
                          className="remove-file-btn"
                        >
                          <Icon name="trash" size={16} />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </section>
          </div>

          <div className="form-actions">
            <button type="button" onClick={onClose} className="btn-secondary">
              Cancel
            </button>
            <button 
              type="submit" 
              className="btn-primary"
              disabled={submitting || uploading}
            >
              {submitting ? (
                <>
                  <div className="spinner"></div>
                  {uploading ? 'Uploading Files...' : 'Submitting Report...'}
                </>
              ) : (
                <>
                  <Icon name="check" size={16} />
                  Submit Report
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default InspectionReportForm;