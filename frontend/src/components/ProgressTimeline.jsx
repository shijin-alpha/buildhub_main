import React, { useState, useEffect } from 'react';
import '../styles/ProgressTimeline.css';

const ProgressTimeline = ({ contractorId, projectId, homeownerId, userRole = 'contractor' }) => {
  const [progressUpdates, setProgressUpdates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedUpdate, setSelectedUpdate] = useState(null);
  const [showPhotoModal, setShowPhotoModal] = useState(false);
  const [selectedPhoto, setSelectedPhoto] = useState(null);

  useEffect(() => {
    loadProgressUpdates();
  }, [contractorId, projectId, homeownerId, userRole]);

  const loadProgressUpdates = async () => {
    try {
      setLoading(true);
      
      let url;
      if (userRole === 'homeowner') {
        url = `http://localhost/buildhub/backend/api/homeowner/get_progress_updates.php?homeowner_id=${homeownerId}`;
        if (projectId) url += `&project_id=${projectId}`;
      } else {
        url = `http://localhost/buildhub/backend/api/contractor/get_progress_updates.php?contractor_id=${contractorId}`;
        if (projectId) url += `&project_id=${projectId}`;
      }

      const response = await fetch(url, { credentials: 'include' });
      const data = await response.json();
      
      if (data.success) {
        setProgressUpdates(data.data.progress_updates || []);
      } else {
        console.error('Failed to load progress updates:', data.message);
      }
    } catch (error) {
      console.error('Error loading progress updates:', error);
    } finally {
      setLoading(false);
    }
  };

  const openPhotoModal = (photoUrl) => {
    setSelectedPhoto(photoUrl);
    setShowPhotoModal(true);
  };

  const closePhotoModal = () => {
    setShowPhotoModal(false);
    setSelectedPhoto(null);
  };

  const getStageIcon = (stageName) => {
    const icons = {
      'Foundation': '🏗️',
      'Structure': '🏢',
      'Brickwork': '🧱',
      'Roofing': '🏠',
      'Electrical': '⚡',
      'Plumbing': '🚿',
      'Finishing': '🎨',
      'Other': '🔧'
    };
    return icons[stageName] || '🔧';
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'Completed': return '#28a745';
      case 'In Progress': return '#ffc107';
      case 'Not Started': return '#6c757d';
      default: return '#6c757d';
    }
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading) {
    return (
      <div className="progress-timeline-loading">
        <div className="loading-spinner"></div>
        <p>Loading progress updates...</p>
      </div>
    );
  }

  if (progressUpdates.length === 0) {
    return (
      <div className="progress-timeline-empty">
        <div className="empty-state">
          <h3>No Progress Updates Yet</h3>
          <p>
            {userRole === 'contractor' 
              ? 'Start submitting progress updates to keep homeowners informed.'
              : 'Your contractor hasn\'t submitted any progress updates yet.'
            }
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="progress-timeline">
      <div className="timeline-header">
        <h3>Construction Progress Timeline</h3>
        <p>{progressUpdates.length} update{progressUpdates.length !== 1 ? 's' : ''}</p>
      </div>

      <div className="timeline-container">
        {progressUpdates.map((update, index) => (
          <div key={update.id} className="timeline-item">
            <div className="timeline-marker">
              <div 
                className="timeline-dot"
                style={{ backgroundColor: getStatusColor(update.stage_status) }}
              >
                {getStageIcon(update.stage_name)}
              </div>
              {index < progressUpdates.length - 1 && <div className="timeline-line"></div>}
            </div>

            <div className="timeline-content">
              <div className="update-card">
                <div className="update-header">
                  <div className="update-title">
                    <h4>{update.stage_name}</h4>
                    <span 
                      className={`status-badge ${update.status_class}`}
                      style={{ backgroundColor: getStatusColor(update.stage_status) }}
                    >
                      {update.stage_status}
                    </span>
                  </div>
                  <div className="update-meta">
                    <span className="update-date">{formatDate(update.created_at)}</span>
                    {userRole === 'homeowner' && (
                      <span className="contractor-name">
                        by {update.contractor_first_name} {update.contractor_last_name}
                      </span>
                    )}
                  </div>
                </div>

                <div className="update-progress">
                  <div className="progress-bar-container">
                    <div className="progress-bar">
                      <div 
                        className="progress-fill"
                        style={{ 
                          width: `${update.completion_percentage}%`,
                          backgroundColor: getStatusColor(update.stage_status)
                        }}
                      ></div>
                    </div>
                    <span className="progress-text">{update.completion_percentage}%</span>
                  </div>
                </div>

                {update.remarks && (
                  <div className="update-remarks">
                    <h5>Work Description:</h5>
                    <p>{update.remarks}</p>
                  </div>
                )}

                {update.delay_reason && (
                  <div className="update-delay">
                    <h5>⚠️ Delay Reported:</h5>
                    <p><strong>Reason:</strong> {update.delay_reason}</p>
                    {update.delay_description && (
                      <p><strong>Details:</strong> {update.delay_description}</p>
                    )}
                  </div>
                )}

                {update.photos && update.photos.length > 0 && (
                  <div className="update-photos">
                    <h5>Progress Photos ({update.photos.length})</h5>
                    <div className="photo-gallery">
                      {update.photo_urls.map((photoUrl, photoIndex) => (
                        <div key={photoIndex} className="photo-thumbnail-container">
                          <img
                            src={photoUrl}
                            alt={`Progress photo ${photoIndex + 1}`}
                            className="photo-thumbnail"
                            onClick={() => openPhotoModal(photoUrl)}
                            loading="lazy"
                          />
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {!update.location_verified && update.latitude && (
                  <div className="location-warning">
                    ⚠️ Location verification pending
                  </div>
                )}
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Photo Modal */}
      {showPhotoModal && selectedPhoto && (
        <div className="photo-modal-overlay" onClick={closePhotoModal}>
          <div className="photo-modal">
            <button className="photo-modal-close" onClick={closePhotoModal}>
              ×
            </button>
            <img src={selectedPhoto} alt="Progress photo" className="photo-modal-image" />
          </div>
        </div>
      )}
    </div>
  );
};

export default ProgressTimeline;