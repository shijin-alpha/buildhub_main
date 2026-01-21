import React, { useState, useEffect } from 'react';
import '../styles/EnhancedProgressTimeline.css';

const EnhancedProgressTimeline = ({ contractorId, projectId, homeownerId, userRole = 'contractor' }) => {
  const [timelineData, setTimelineData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [selectedUpdate, setSelectedUpdate] = useState(null);
  const [showPhotoModal, setShowPhotoModal] = useState(false);
  const [selectedPhoto, setSelectedPhoto] = useState(null);
  const [viewMode, setViewMode] = useState('tree'); // 'tree' or 'list'

  useEffect(() => {
    loadTimelineData();
  }, [contractorId, projectId, homeownerId, userRole]);

  const loadTimelineData = async () => {
    try {
      setLoading(true);
      
      // Load progress updates
      let progressUrl;
      if (userRole === 'homeowner') {
        progressUrl = `http://localhost/buildhub/backend/api/homeowner/get_progress_updates.php?homeowner_id=${homeownerId}`;
        if (projectId) progressUrl += `&project_id=${projectId}`;
      } else {
        progressUrl = `http://localhost/buildhub/backend/api/contractor/get_progress_updates.php?contractor_id=${contractorId}`;
        if (projectId) progressUrl += `&project_id=${projectId}`;
      }

      const progressResponse = await fetch(progressUrl, { credentials: 'include' });
      const progressData = await progressResponse.json();
      
      if (progressData.success) {
        const progressUpdates = progressData.data.progress_updates || [];
        const projectSummary = progressData.data.project_summary;
        
        // Create comprehensive timeline
        const timeline = await createComprehensiveTimeline(progressUpdates, projectSummary);
        setTimelineData(timeline);
      } else {
        console.error('Failed to load progress updates:', progressData.message);
        setTimelineData({ events: [], project: null, stats: {} });
      }
    } catch (error) {
      console.error('Error loading timeline data:', error);
      setTimelineData({ events: [], project: null, stats: {} });
    } finally {
      setLoading(false);
    }
  };

  const createComprehensiveTimeline = async (progressUpdates, projectSummary) => {
    const events = [];
    
    // 1. Project Start Event (from estimate acceptance)
    if (projectSummary && projectSummary.id) {
      events.push({
        id: `project-start-${projectSummary.id}`,
        type: 'project_start',
        title: 'Project Started',
        description: 'Construction project officially began',
        date: projectSummary.created_at || new Date().toISOString(),
        icon: '🏗️',
        status: 'completed',
        details: {
          timeline: projectSummary.timeline,
          total_cost: projectSummary.total_cost,
          homeowner: `${projectSummary.homeowner_first_name} ${projectSummary.homeowner_last_name}`
        }
      });
    }

    // 2. Add Progress Updates as Timeline Events
    progressUpdates.forEach(update => {
      events.push({
        id: `progress-${update.id}`,
        type: 'progress_update',
        title: update.stage_name,
        description: update.remarks || `${update.stage_name} work in progress`,
        date: update.created_at,
        icon: getStageIcon(update.stage_name),
        status: update.stage_status.toLowerCase().replace(' ', '_'),
        progress: update.completion_percentage,
        details: {
          stage_status: update.stage_status,
          completion_percentage: update.completion_percentage,
          delay_reason: update.delay_reason,
          delay_description: update.delay_description,
          photos: update.photos || [],
          photo_urls: update.photo_urls || [],
          location_verified: update.location_verified,
          contractor: userRole === 'homeowner' ? `${update.contractor_first_name} ${update.contractor_last_name}` : null
        }
      });
    });

    // 3. Add Milestone Events (based on progress stages)
    const milestones = generateMilestones(progressUpdates);
    events.push(...milestones);

    // 4. Sort events by date
    events.sort((a, b) => new Date(a.date) - new Date(b.date));

    // 5. Calculate project statistics
    const stats = calculateProjectStats(events, progressUpdates);

    return {
      events,
      project: projectSummary,
      stats
    };
  };

  const generateMilestones = (progressUpdates) => {
    const milestones = [];
    const stageCompletions = {};

    // Track when each stage was completed
    progressUpdates.forEach(update => {
      if (update.completion_percentage === 100 && update.stage_status === 'Completed') {
        if (!stageCompletions[update.stage_name] || new Date(update.created_at) > new Date(stageCompletions[update.stage_name].date)) {
          stageCompletions[update.stage_name] = {
            date: update.created_at,
            update: update
          };
        }
      }
    });

    // Create milestone events for completed stages
    Object.entries(stageCompletions).forEach(([stageName, completion]) => {
      milestones.push({
        id: `milestone-${stageName.toLowerCase()}`,
        type: 'milestone',
        title: `${stageName} Completed`,
        description: `${stageName} phase successfully completed`,
        date: completion.date,
        icon: '🎯',
        status: 'completed',
        details: {
          stage_name: stageName,
          completion_date: completion.date
        }
      });
    });

    return milestones;
  };

  const calculateProjectStats = (events, progressUpdates) => {
    const totalEvents = events.length;
    const completedEvents = events.filter(e => e.status === 'completed').length;
    const inProgressEvents = events.filter(e => e.status === 'in_progress').length;
    
    const latestProgress = progressUpdates.length > 0 
      ? Math.max(...progressUpdates.map(u => u.completion_percentage))
      : 0;

    const totalStages = [...new Set(progressUpdates.map(u => u.stage_name))].length;
    const completedStages = [...new Set(
      progressUpdates
        .filter(u => u.completion_percentage === 100 && u.stage_status === 'Completed')
        .map(u => u.stage_name)
    )].length;

    return {
      totalEvents,
      completedEvents,
      inProgressEvents,
      latestProgress,
      totalStages,
      completedStages,
      projectDuration: calculateProjectDuration(events)
    };
  };

  const calculateProjectDuration = (events) => {
    if (events.length === 0) return 0;
    
    const startDate = new Date(events[0].date);
    const endDate = new Date(events[events.length - 1].date);
    const diffTime = Math.abs(endDate - startDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    return diffDays;
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
      'Painting': '🎨',
      'Flooring': '🔲',
      'Other': '🔧'
    };
    return icons[stageName] || '🔧';
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'completed': return '#28a745';
      case 'in_progress': return '#ffc107';
      case 'not_started': return '#6c757d';
      case 'delayed': return '#dc3545';
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

  const openPhotoModal = (photoUrl) => {
    setSelectedPhoto(photoUrl);
    setShowPhotoModal(true);
  };

  const closePhotoModal = () => {
    setShowPhotoModal(false);
    setSelectedPhoto(null);
  };

  if (loading) {
    return (
      <div className="enhanced-timeline-loading">
        <div className="loading-spinner"></div>
        <p>Loading project timeline...</p>
      </div>
    );
  }

  if (!timelineData || timelineData.events.length === 0) {
    return (
      <div className="enhanced-timeline-empty">
        <div className="empty-state">
          <div className="empty-icon">📋</div>
          <h3>No Timeline Data Available</h3>
          <p>
            {userRole === 'contractor' 
              ? 'Start submitting progress updates to build your project timeline.'
              : 'Your contractor hasn\'t submitted any progress updates yet.'
            }
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="enhanced-progress-timeline">
      {/* Timeline Header */}
      <div className="timeline-header">
        <div className="header-content">
          <h3>🏗️ Construction Timeline</h3>
          <div className="timeline-stats">
            <div className="stat-item">
              <span className="stat-value">{timelineData.stats.latestProgress}%</span>
              <span className="stat-label">Overall Progress</span>
            </div>
            <div className="stat-item">
              <span className="stat-value">{timelineData.stats.completedStages}/{timelineData.stats.totalStages}</span>
              <span className="stat-label">Stages Complete</span>
            </div>
            <div className="stat-item">
              <span className="stat-value">{timelineData.stats.projectDuration}</span>
              <span className="stat-label">Days Active</span>
            </div>
          </div>
        </div>
        
        <div className="view-controls">
          <button 
            className={`view-btn ${viewMode === 'tree' ? 'active' : ''}`}
            onClick={() => setViewMode('tree')}
          >
            🌳 Tree View
          </button>
          <button 
            className={`view-btn ${viewMode === 'list' ? 'active' : ''}`}
            onClick={() => setViewMode('list')}
          >
            📋 List View
          </button>
        </div>
      </div>

      {/* Progress Overview */}
      <div className="progress-overview">
        <div className="progress-bar-container">
          <div className="progress-bar">
            <div 
              className="progress-fill"
              style={{ width: `${timelineData.stats.latestProgress}%` }}
            ></div>
          </div>
          <span className="progress-text">{timelineData.stats.latestProgress}% Complete</span>
        </div>
      </div>

      {/* Timeline Content */}
      <div className={`timeline-container ${viewMode}`}>
        {viewMode === 'tree' ? (
          <div className="tree-timeline">
            {timelineData.events.map((event, index) => (
              <div key={event.id} className={`timeline-node ${event.type}`}>
                <div className="node-connector">
                  {index > 0 && <div className="connector-line"></div>}
                  <div 
                    className={`node-dot ${event.status}`}
                    style={{ backgroundColor: getStatusColor(event.status) }}
                  >
                    <span className="node-icon">{event.icon}</span>
                  </div>
                  {index < timelineData.events.length - 1 && <div className="connector-line-after"></div>}
                </div>

                <div className="node-content">
                  <div className="event-card">
                    <div className="event-header">
                      <div className="event-title">
                        <h4>{event.title}</h4>
                        <span className={`event-type-badge ${event.type}`}>
                          {event.type.replace('_', ' ').toUpperCase()}
                        </span>
                      </div>
                      <div className="event-date">
                        {formatDate(event.date)}
                      </div>
                    </div>

                    <div className="event-body">
                      <p className="event-description">{event.description}</p>
                      
                      {event.progress !== undefined && (
                        <div className="event-progress">
                          <div className="mini-progress-bar">
                            <div 
                              className="mini-progress-fill"
                              style={{ 
                                width: `${event.progress}%`,
                                backgroundColor: getStatusColor(event.status)
                              }}
                            ></div>
                          </div>
                          <span className="progress-label">{event.progress}%</span>
                        </div>
                      )}

                      {event.details && (
                        <div className="event-details">
                          {event.details.stage_status && (
                            <div className="detail-item">
                              <span className="detail-label">Status:</span>
                              <span className={`status-badge ${event.details.stage_status.toLowerCase().replace(' ', '_')}`}>
                                {event.details.stage_status}
                              </span>
                            </div>
                          )}

                          {event.details.contractor && (
                            <div className="detail-item">
                              <span className="detail-label">Contractor:</span>
                              <span className="detail-value">{event.details.contractor}</span>
                            </div>
                          )}

                          {event.details.timeline && (
                            <div className="detail-item">
                              <span className="detail-label">Timeline:</span>
                              <span className="detail-value">{event.details.timeline}</span>
                            </div>
                          )}

                          {event.details.total_cost && (
                            <div className="detail-item">
                              <span className="detail-label">Project Cost:</span>
                              <span className="detail-value">₹{Number(event.details.total_cost).toLocaleString('en-IN')}</span>
                            </div>
                          )}

                          {event.details.delay_reason && (
                            <div className="detail-item delay-info">
                              <span className="detail-label">⚠️ Delay:</span>
                              <span className="detail-value">{event.details.delay_reason}</span>
                              {event.details.delay_description && (
                                <p className="delay-description">{event.details.delay_description}</p>
                              )}
                            </div>
                          )}

                          {event.details.photos && event.details.photos.length > 0 && (
                            <div className="event-photos">
                              <span className="detail-label">📸 Photos ({event.details.photos.length}):</span>
                              <div className="photo-thumbnails">
                                {event.details.photo_urls.slice(0, 3).map((photoUrl, photoIndex) => (
                                  <img
                                    key={photoIndex}
                                    src={photoUrl}
                                    alt={`Progress photo ${photoIndex + 1}`}
                                    className="photo-thumbnail"
                                    onClick={() => openPhotoModal(photoUrl)}
                                    loading="lazy"
                                  />
                                ))}
                                {event.details.photos.length > 3 && (
                                  <div className="photo-count-badge">
                                    +{event.details.photos.length - 3}
                                  </div>
                                )}
                              </div>
                            </div>
                          )}
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="list-timeline">
            {timelineData.events.map((event, index) => (
              <div key={event.id} className={`timeline-item ${event.type}`}>
                <div className="item-marker">
                  <div 
                    className={`item-dot ${event.status}`}
                    style={{ backgroundColor: getStatusColor(event.status) }}
                  >
                    {event.icon}
                  </div>
                  {index < timelineData.events.length - 1 && <div className="item-line"></div>}
                </div>

                <div className="item-content">
                  <div className="item-header">
                    <h4>{event.title}</h4>
                    <span className="item-date">{formatDate(event.date)}</span>
                  </div>
                  <p className="item-description">{event.description}</p>
                  
                  {event.progress !== undefined && (
                    <div className="item-progress">
                      <span>{event.progress}% Complete</span>
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
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

export default EnhancedProgressTimeline;