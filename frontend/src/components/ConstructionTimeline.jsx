import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/ConstructionTimeline.css';

const ConstructionTimeline = ({ projectId = null }) => {
  const toast = useToast();
  const [timelineData, setTimelineData] = useState([]);
  const [milestones, setMilestones] = useState([]);
  const [projectInfo, setProjectInfo] = useState(null);
  const [allProjects, setAllProjects] = useState([]);
  const [statistics, setStatistics] = useState({});
  const [loading, setLoading] = useState(true);
  const [selectedEntry, setSelectedEntry] = useState(null);

  useEffect(() => {
    loadTimelineData();
  }, [projectId]);

  const loadTimelineData = async () => {
    try {
      setLoading(true);
      
      const url = projectId 
        ? `/buildhub/backend/api/homeowner/get_construction_timeline.php?project_id=${projectId}`
        : '/buildhub/backend/api/homeowner/get_construction_timeline.php';
      
      const response = await fetch(url, {
        method: 'GET',
        credentials: 'include'
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setTimelineData(data.data.timeline);
          setMilestones(data.data.milestones);
          setProjectInfo(data.data.project_info);
          setAllProjects(data.data.all_projects || []);
          setStatistics(data.data.statistics);
        } else {
          toast.error('Failed to load timeline: ' + data.message);
        }
      } else {
        toast.error('Failed to load construction timeline');
      }
    } catch (error) {
      console.error('Timeline loading error:', error);
      toast.error('Error loading timeline data');
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const getStageIcon = (stage) => {
    const icons = {
      'Foundation': '🏗️',
      'Framing': '🔨',
      'Roofing': '🏠',
      'Electrical': '⚡',
      'Plumbing': '🚿',
      'Flooring': '🪜',
      'Painting': '🎨',
      'Finishing': '✨',
      'Landscaping': '🌿'
    };
    return icons[stage] || '🔧';
  };

  const getProgressColor = (progress) => {
    if (progress < 25) return '#ef4444'; // Red
    if (progress < 50) return '#f59e0b'; // Orange
    if (progress < 75) return '#3b82f6'; // Blue
    return '#10b981'; // Green
  };

  if (loading) {
    return (
      <div className="construction-timeline loading">
        <div className="loading-spinner"></div>
        <p>Loading construction timeline...</p>
      </div>
    );
  }

  if (timelineData.length === 0) {
    if (allProjects.length > 0) {
      return (
        <div className="construction-timeline">
          <div className="timeline-header">
            <div className="timeline-title">
              <h2>🏗️ Construction Timeline</h2>
            </div>
          </div>
          <div className="projects-overview">
            <h3>📊 Your Construction Projects</h3>
            <p style={{ color: '#6b7280', marginBottom: '1rem', fontSize: '0.875rem' }}>
              Your project is set up and ready. The timeline will appear once your contractor submits the first progress update.
            </p>
            <div className="projects-grid">
              {allProjects.map((project) => (
                <div key={project.id} className="project-card">
                  <div className="project-header">
                    <h4>{project.project_name || `Project ${project.id}`}</h4>
                    <span className="progress-badge" style={{ backgroundColor: '#6b7280' }}>
                      {project.status === 'ready_for_construction' ? 'Ready' : project.status || 'New'}
                    </span>
                  </div>
                  <div className="project-details">
                    <p>Stage: {project.current_stage || 'Planning'}</p>
                    {project.estimate_cost && (
                      <p>Budget: ₹{Number(project.estimate_cost).toLocaleString('en-IN')}</p>
                    )}
                    {project.actual_start_date && (
                      <p>Start date: {formatDate(project.actual_start_date)}</p>
                    )}
                    <div className="mini-progress-bar">
                      <div className="mini-progress-fill" style={{ width: `${project.completion_percentage || 0}%`, backgroundColor: '#6b7280' }}></div>
                    </div>
                    <p style={{ fontSize: '0.75rem', color: '#9ca3af', marginTop: '0.5rem' }}>
                      Awaiting first progress update from contractor
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      );
    }

    return (
      <div className="construction-timeline empty">
        <div className="empty-state">
          <div className="empty-icon">📅</div>
          <h3>No Construction Timeline</h3>
          <p>No daily progress updates found for this project.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="construction-timeline">
      {/* Timeline Header */}
      <div className="timeline-header">
        <div className="timeline-title">
          <h2>🏗️ Construction Timeline</h2>
          {projectInfo && (
            <p className="project-name">{projectInfo.project_name}</p>
          )}
        </div>
        
        <div className="timeline-stats">
          <div className="stat-item">
            <span className="stat-value">{statistics.current_progress}%</span>
            <span className="stat-label">Complete</span>
          </div>
          <div className="stat-item">
            <span className="stat-value">{statistics.total_stages}</span>
            <span className="stat-label">Stages</span>
          </div>
          <div className="stat-item">
            <span className="stat-value">{statistics.total_updates}</span>
            <span className="stat-label">Updates</span>
          </div>
        </div>
      </div>

      {/* Progress Bar */}
      <div className="overall-progress">
        <div className="progress-header">
          <span>Overall Progress</span>
          <span className="progress-percentage">{statistics.current_progress}%</span>
        </div>
        <div className="progress-bar">
          <div 
            className="progress-fill"
            style={{ 
              width: `${statistics.current_progress}%`,
              backgroundColor: getProgressColor(statistics.current_progress)
            }}
          ></div>
        </div>
      </div>

      {/* Timeline Visualization */}
      <div className="timeline-container">
        <div className="timeline-axis">
          {/* Year markers */}
          <div className="year-markers">
            {Array.from(new Set(timelineData.map(entry => new Date(entry.date).getFullYear())))
              .sort()
              .map(year => (
                <div key={year} className="year-marker">
                  <span className="year-label">{year}</span>
                </div>
              ))
            }
          </div>

          {/* Timeline entries */}
          <div className="timeline-entries">
            {timelineData.map((entry, index) => (
              <div 
                key={entry.id} 
                className={`timeline-entry ${selectedEntry?.id === entry.id ? 'selected' : ''}`}
                onClick={() => setSelectedEntry(entry)}
              >
                <div className="timeline-dot" style={{ backgroundColor: getProgressColor(entry.total_progress) }}>
                  <span className="stage-icon">{getStageIcon(entry.stage)}</span>
                </div>
                
                <div className={`timeline-content ${index % 2 === 0 ? 'left' : 'right'}`}>
                  <div className="timeline-card">
                    <div className="card-header">
                      <h4>{entry.stage}</h4>
                      <span className="progress-badge">{entry.total_progress}%</span>
                    </div>
                    
                    <div className="card-content">
                      <p className="date">{formatDate(entry.date)}</p>
                      <p className="work-description">{entry.work_description}</p>
                      
                      <div className="entry-details">
                        <span className="detail-item">
                          ⏱️ {entry.working_hours}h
                        </span>
                        <span className="detail-item">
                          🌤️ {entry.weather}
                        </span>
                        {entry.daily_progress > 0 && (
                          <span className="detail-item">
                            📈 +{entry.daily_progress}%
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Milestones Summary */}
      <div className="milestones-summary">
        <h3>🎯 Construction Milestones</h3>
        <div className="milestones-grid">
          {milestones.map((milestone, index) => (
            <div key={index} className="milestone-card">
              <div className="milestone-icon">
                {getStageIcon(milestone.stage)}
              </div>
              <div className="milestone-info">
                <h4>{milestone.stage}</h4>
                <p className="milestone-date">{formatDate(milestone.date)}</p>
                <div className="milestone-progress">
                  <span className="progress-text">{milestone.progress}% Complete</span>
                  <div className="mini-progress-bar">
                    <div 
                      className="mini-progress-fill"
                      style={{ 
                        width: `${milestone.progress}%`,
                        backgroundColor: getProgressColor(milestone.progress)
                      }}
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Selected Entry Details Modal */}
      {selectedEntry && (
        <div className="entry-modal-overlay" onClick={() => setSelectedEntry(null)}>
          <div className="entry-modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3>{getStageIcon(selectedEntry.stage)} {selectedEntry.stage}</h3>
              <button 
                className="close-btn"
                onClick={() => setSelectedEntry(null)}
              >
                ×
              </button>
            </div>
            
            <div className="modal-content">
              <div className="modal-section">
                <h4>📅 Date & Progress</h4>
                <p><strong>Date:</strong> {formatDate(selectedEntry.date)}</p>
                <p><strong>Total Progress:</strong> {selectedEntry.total_progress}%</p>
                <p><strong>Daily Progress:</strong> +{selectedEntry.daily_progress}%</p>
              </div>
              
              <div className="modal-section">
                <h4>🔨 Work Details</h4>
                <p><strong>Work Done:</strong> {selectedEntry.work_description}</p>
                <p><strong>Working Hours:</strong> {selectedEntry.working_hours} hours</p>
                <p><strong>Weather:</strong> {selectedEntry.weather}</p>
              </div>
              
              {selectedEntry.issues && (
                <div className="modal-section">
                  <h4>⚠️ Issues</h4>
                  <p>{selectedEntry.issues}</p>
                </div>
              )}
              
              {selectedEntry.photos && selectedEntry.photos.length > 0 && (
                <div className="modal-section">
                  <h4>📸 Progress Photos</h4>
                  <div className="photos-grid">
                    {selectedEntry.photos.map((photo, index) => (
                      <img 
                        key={index}
                        src={photo}
                        alt={`Progress photo ${index + 1}`}
                        className="progress-photo"
                      />
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ConstructionTimeline;