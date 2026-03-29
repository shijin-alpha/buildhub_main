import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/ProjectDetailsModal.css';

const Icon = ({ name, size = 20, stroke = 1.8, color = 'currentColor' }) => {
  const common = { width: size, height: size, viewBox: '0 0 24 24', fill: 'none', stroke: color, strokeWidth: stroke, strokeLinecap: 'round', strokeLinejoin: 'round' };
  switch (name) {
    case 'x':
      return (<svg {...common}><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>);
    case 'calendar':
      return (<svg {...common}><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>);
    case 'map-pin':
      return (<svg {...common}><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>);
    case 'user':
      return (<svg {...common}><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>);
    case 'phone':
      return (<svg {...common}><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>);
    case 'mail':
      return (<svg {...common}><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>);
    case 'clipboard':
      return (<svg {...common}><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>);
    case 'camera':
      return (<svg {...common}><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>);
    case 'dollar-sign':
      return (<svg {...common}><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>);
    default:
      return null;
  }
};

const ProjectDetailsModal = ({ project, onClose }) => {
  const toast = useToast();
  const [projectDetails, setProjectDetails] = useState(null);
  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState('overview');

  useEffect(() => {
    if (project) {
      fetchProjectDetails();
    }
  }, [project]);

  const fetchProjectDetails = async () => {
    setLoading(true);
    try {
      const response = await fetch(`/buildhub/backend/api/inspector/get_project_details.php?project_id=${project.project_id}`, {
        credentials: 'include'
      });
      const data = await response.json();

      if (data.success) {
        setProjectDetails(data);
      } else {
        toast.error(data.message);
      }
    } catch (error) {
      console.error('Error fetching project details:', error);
      toast.error('Failed to fetch project details');
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'in_progress': return '#3b82f6';
      case 'completed': return '#10b981';
      case 'on_hold': return '#f59e0b';
      case 'cancelled': return '#ef4444';
      default: return '#6b7280';
    }
  };

  if (!project) return null;

  return (
    <div className="project-details-modal">
      <div className="modal-overlay" onClick={onClose}></div>
      <div className="modal-content">
        <div className="modal-header">
          <div className="header-info">
            <h2>{project.project_name}</h2>
            <div className="project-status" style={{ color: getStatusColor(project.project_status) }}>
              {project.project_status.replace('_', ' ').toUpperCase()}
            </div>
          </div>
          <button className="close-btn" onClick={onClose}>
            <Icon name="x" />
          </button>
        </div>

        <div className="modal-tabs">
          <button 
            className={`tab-btn ${activeTab === 'overview' ? 'active' : ''}`}
            onClick={() => setActiveTab('overview')}
          >
            Overview
          </button>
          <button 
            className={`tab-btn ${activeTab === 'progress' ? 'active' : ''}`}
            onClick={() => setActiveTab('progress')}
          >
            Progress
          </button>
          <button 
            className={`tab-btn ${activeTab === 'inspections' ? 'active' : ''}`}
            onClick={() => setActiveTab('inspections')}
          >
            Inspections
          </button>
          <button 
            className={`tab-btn ${activeTab === 'photos' ? 'active' : ''}`}
            onClick={() => setActiveTab('photos')}
          >
            Photos
          </button>
        </div>

        <div className="modal-body">
          {loading ? (
            <div className="loading-state">
              <div className="spinner"></div>
              <p>Loading project details...</p>
            </div>
          ) : (
            <>
              {activeTab === 'overview' && (
                <div className="overview-tab">
                  <div className="project-info-grid">
                    <div className="info-section">
                      <h3>Project Information</h3>
                      <div className="info-item">
                        <Icon name="map-pin" size={16} />
                        <span><strong>Location:</strong> {project.project_location || 'Not specified'}</span>
                      </div>
                      <div className="info-item">
                        <Icon name="clipboard" size={16} />
                        <span><strong>Current Stage:</strong> {project.current_stage}</span>
                      </div>
                      <div className="info-item">
                        <Icon name="calendar" size={16} />
                        <span><strong>Progress:</strong> {project.completion_percentage}%</span>
                      </div>
                      {project.start_date && (
                        <div className="info-item">
                          <Icon name="calendar" size={16} />
                          <span><strong>Start Date:</strong> {new Date(project.start_date).toLocaleDateString()}</span>
                        </div>
                      )}
                      {project.expected_completion_date && (
                        <div className="info-item">
                          <Icon name="calendar" size={16} />
                          <span><strong>Expected Completion:</strong> {new Date(project.expected_completion_date).toLocaleDateString()}</span>
                        </div>
                      )}
                    </div>

                    <div className="info-section">
                      <h3>Homeowner</h3>
                      <div className="info-item">
                        <Icon name="user" size={16} />
                        <span><strong>Name:</strong> {project.homeowner_first_name} {project.homeowner_last_name}</span>
                      </div>
                      <div className="info-item">
                        <Icon name="mail" size={16} />
                        <span><strong>Email:</strong> {project.homeowner_email}</span>
                      </div>
                      {project.homeowner_phone && (
                        <div className="info-item">
                          <Icon name="phone" size={16} />
                          <span><strong>Phone:</strong> {project.homeowner_phone}</span>
                        </div>
                      )}
                    </div>

                    <div className="info-section">
                      <h3>Contractor</h3>
                      <div className="info-item">
                        <Icon name="user" size={16} />
                        <span><strong>Name:</strong> {project.contractor_first_name} {project.contractor_last_name}</span>
                      </div>
                      <div className="info-item">
                        <Icon name="mail" size={16} />
                        <span><strong>Email:</strong> {project.contractor_email}</span>
                      </div>
                      {project.contractor_phone && (
                        <div className="info-item">
                          <Icon name="phone" size={16} />
                          <span><strong>Phone:</strong> {project.contractor_phone}</span>
                        </div>
                      )}
                    </div>

                    <div className="info-section">
                      <h3>Inspection Summary</h3>
                      <div className="info-item">
                        <Icon name="clipboard" size={16} />
                        <span><strong>Total Inspections:</strong> {project.total_inspections}</span>
                      </div>
                      <div className="info-item">
                        <Icon name="clipboard" size={16} />
                        <span><strong>Pending:</strong> {project.pending_inspections}</span>
                      </div>
                      {project.last_inspection_date && (
                        <div className="info-item">
                          <Icon name="calendar" size={16} />
                          <span><strong>Last Inspection:</strong> {new Date(project.last_inspection_date).toLocaleDateString()}</span>
                        </div>
                      )}
                    </div>
                  </div>

                  {project.project_description && (
                    <div className="description-section">
                      <h3>Project Description</h3>
                      <p>{project.project_description}</p>
                    </div>
                  )}
                </div>
              )}

              {activeTab === 'progress' && projectDetails && (
                <div className="progress-tab">
                  <h3>Recent Progress Updates</h3>
                  {projectDetails.progress_updates && projectDetails.progress_updates.length > 0 ? (
                    <div className="progress-list">
                      {projectDetails.progress_updates.map((update, index) => (
                        <div key={index} className="progress-item">
                          <div className="progress-header">
                            <strong>{update.stage}</strong>
                            <span className="progress-date">{new Date(update.update_date).toLocaleDateString()}</span>
                          </div>
                          <div className="progress-content">
                            <p><strong>Progress:</strong> {update.completion_percentage}%</p>
                            {update.notes && <p><strong>Notes:</strong> {update.notes}</p>}
                            <p><strong>Updated by:</strong> {update.first_name} {update.last_name}</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="empty-state">
                      <Icon name="clipboard" size={48} color="#9ca3af" />
                      <p>No progress updates available</p>
                    </div>
                  )}
                </div>
              )}

              {activeTab === 'inspections' && projectDetails && (
                <div className="inspections-tab">
                  <h3>Inspection History</h3>
                  {projectDetails.inspections && projectDetails.inspections.length > 0 ? (
                    <div className="inspections-list">
                      {projectDetails.inspections.map((inspection, index) => (
                        <div key={index} className="inspection-item">
                          <div className="inspection-header">
                            <strong>{inspection.inspection_stage}</strong>
                            <span className={`status-badge status-${inspection.overall_status}`}>
                              {inspection.overall_status.replace('_', ' ').toUpperCase()}
                            </span>
                          </div>
                          <div className="inspection-content">
                            <p><strong>Date:</strong> {new Date(inspection.inspection_date).toLocaleDateString()}</p>
                            <p><strong>Type:</strong> {inspection.inspection_type}</p>
                            {inspection.quality_score && (
                              <p><strong>Quality Score:</strong> {inspection.quality_score}/10</p>
                            )}
                            <p><strong>Inspector:</strong> {inspection.inspector_first_name} {inspection.inspector_last_name}</p>
                            {inspection.notes && (
                              <div className="inspection-notes">
                                <strong>Notes:</strong>
                                <p>{inspection.notes}</p>
                              </div>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="empty-state">
                      <Icon name="clipboard" size={48} color="#9ca3af" />
                      <p>No inspections recorded yet</p>
                    </div>
                  )}
                </div>
              )}

              {activeTab === 'photos' && projectDetails && (
                <div className="photos-tab">
                  <h3>Project Photos</h3>
                  {projectDetails.geo_photos && projectDetails.geo_photos.length > 0 ? (
                    <div className="photos-grid">
                      {projectDetails.geo_photos.map((photo, index) => (
                        <div key={index} className="photo-item">
                          <img 
                            src={`/buildhub/backend/${photo.file_path}`} 
                            alt={photo.caption || 'Project photo'}
                            onError={(e) => {
                              e.target.style.display = 'none';
                            }}
                          />
                          <div className="photo-info">
                            <p><strong>Date:</strong> {new Date(photo.uploaded_at).toLocaleDateString()}</p>
                            {photo.caption && <p><strong>Caption:</strong> {photo.caption}</p>}
                            {photo.latitude && photo.longitude && (
                              <p><strong>Location:</strong> {photo.latitude.toFixed(6)}, {photo.longitude.toFixed(6)}</p>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="empty-state">
                      <Icon name="camera" size={48} color="#9ca3af" />
                      <p>No photos uploaded yet</p>
                    </div>
                  )}
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default ProjectDetailsModal;