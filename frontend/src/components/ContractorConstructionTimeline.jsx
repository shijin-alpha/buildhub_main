import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/ConstructionTimeline.css';

const ContractorConstructionTimeline = ({ contractorId, projectId = null, homeownerId = null }) => {
  const toast = useToast();
  const [timelineData, setTimelineData] = useState([]);
  const [milestones, setMilestones] = useState([]);
  const [projectsInfo, setProjectsInfo] = useState({});
  const [statistics, setStatistics] = useState({});
  const [loading, setLoading] = useState(true);
  const [selectedEntry, setSelectedEntry] = useState(null);
  const [selectedProject, setSelectedProject] = useState(projectId);

  useEffect(() => {
    loadTimelineData();
  }, [contractorId, projectId, homeownerId]);

  // Auto-select project if only one exists and none is selected
  useEffect(() => {
    const uniqueProjects = getUniqueProjects();
    if (uniqueProjects.length === 1 && !selectedProject && !projectId) {
      setSelectedProject(uniqueProjects[0].id);
    }
  }, [timelineData, selectedProject, projectId]);

  const loadTimelineData = async () => {
    try {
      setLoading(true);
      
      let url = '/buildhub/backend/api/contractor/get_construction_timeline.php';
      const params = new URLSearchParams();
      
      if (projectId) params.append('project_id', projectId);
      if (homeownerId) params.append('homeowner_id', homeownerId);
      
      if (params.toString()) {
        url += '?' + params.toString();
      }
      
      const response = await fetch(url, {
        method: 'GET',
        credentials: 'include'
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setTimelineData(data.data.timeline);
          setMilestones(data.data.milestones);
          setProjectsInfo(data.data.projects_info);
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

  const getProjectName = (projectId) => {
    return projectsInfo[projectId]?.project_name || `Project ${projectId}`;
  };

  const getProjectDisplayName = (project) => {
    const projectName = project.name || `Project ${project.id}`;
    
    // Get project info from projectsInfo if available
    const projectInfo = projectsInfo[project.id];
    
    // Format budget display
    let budgetDisplay = '';
    if (projectInfo?.estimate_cost) {
      budgetDisplay = ` (₹${projectInfo.estimate_cost.toLocaleString('en-IN')})`;
    } else if (projectInfo?.budget_range) {
      budgetDisplay = ` (${projectInfo.budget_range})`;
    }
    
    return projectName + budgetDisplay;
  };

  const filterTimelineByProject = (projectId) => {
    if (!projectId) return timelineData;
    return timelineData.filter(entry => entry.project_id === parseInt(projectId));
  };

  const getUniqueProjects = () => {
    const projects = {};
    timelineData.forEach(entry => {
      if (!projects[entry.project_id]) {
        projects[entry.project_id] = {
          id: entry.project_id,
          name: getProjectName(entry.project_id),
          homeowner_id: entry.homeowner_id,
          latest_progress: 0,
          updates_count: 0
        };
      }
      projects[entry.project_id].latest_progress = Math.max(
        projects[entry.project_id].latest_progress, 
        entry.total_progress
      );
      projects[entry.project_id].updates_count++;
    });
    return Object.values(projects);
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
    return (
      <div className="construction-timeline empty">
        <div className="empty-state">
          <div className="empty-icon">📅</div>
          <h3>No Construction Timeline</h3>
          <p>No daily progress updates found. Start by submitting your first progress update.</p>
        </div>
      </div>
    );
  }

  const filteredTimeline = selectedProject ? filterTimelineByProject(selectedProject) : timelineData;
  const uniqueProjects = getUniqueProjects();

  return (
    <div className="construction-timeline contractor-timeline">
      {/* Timeline Header */}
      <div className="timeline-header">
        <div className="timeline-title">
          <h2>🏗️ Construction Timeline</h2>
          <p className="contractor-subtitle">Track progress across all your construction projects</p>
        </div>
        
        <div className="timeline-stats">
          <div className="stat-item">
            <span className="stat-value">{statistics.total_projects}</span>
            <span className="stat-label">Projects</span>
          </div>
          <div className="stat-item">
            <span className="stat-value">{statistics.total_updates}</span>
            <span className="stat-label">Updates</span>
          </div>
          <div className="stat-item">
            <span className="stat-value">{Math.round(statistics.total_working_hours || 0)}h</span>
            <span className="stat-label">Total Hours</span>
          </div>
        </div>
      </div>

      {/* Project Filter - Always show when projects exist */}
      {uniqueProjects.length > 0 && (
        <div className="project-filter">
          <label htmlFor="project-select">
            🏗️ Select Project to View Timeline:
          </label>
          <select 
            id="project-select"
            value={selectedProject || ''}
            onChange={(e) => setSelectedProject(e.target.value || null)}
            className="project-select"
          >
            <option value="">
              {uniqueProjects.length === 1 
                ? `View: ${getProjectDisplayName(uniqueProjects[0])}` 
                : `All Projects (${uniqueProjects.length} total)`
              }
            </option>
            {uniqueProjects.map(project => (
              <option key={project.id} value={project.id}>
                📋 {getProjectDisplayName(project)} • {project.updates_count} updates • {project.latest_progress}% complete
              </option>
            ))}
          </select>
        </div>
      )}

      {/* Projects Overview */}
      {!selectedProject && uniqueProjects.length > 1 && (
        <div className="projects-overview">
          <h3>📊 Projects Overview</h3>
          <p style={{ color: '#6b7280', marginBottom: '1rem', fontSize: '0.875rem' }}>
            Select a specific project above to view its detailed timeline, or browse all projects below:
          </p>
          <div className="projects-grid">
            {uniqueProjects.map(project => (
              <div key={project.id} className="project-card" onClick={() => setSelectedProject(project.id)}>
                <div className="project-header">
                  <h4>{getProjectDisplayName(project)}</h4>
                  <span className="progress-badge">{project.latest_progress}%</span>
                </div>
                <div className="project-details">
                  <p>{project.updates_count} progress updates</p>
                  <div className="mini-progress-bar">
                    <div 
                      className="mini-progress-fill"
                      style={{ 
                        width: `${project.latest_progress}%`,
                        backgroundColor: getProgressColor(project.latest_progress)
                      }}
                    ></div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Overall Progress for Selected Project */}
      {selectedProject && filteredTimeline.length > 0 && (
        <div className="overall-progress">
          <div className="progress-header">
            <span>{getProjectDisplayName(uniqueProjects.find(p => p.id == selectedProject))} Progress</span>
            <span className="progress-percentage">
              {filteredTimeline[filteredTimeline.length - 1]?.total_progress || 0}%
            </span>
          </div>
          <div className="progress-bar">
            <div 
              className="progress-fill"
              style={{ 
                width: `${filteredTimeline[filteredTimeline.length - 1]?.total_progress || 0}%`,
                backgroundColor: getProgressColor(filteredTimeline[filteredTimeline.length - 1]?.total_progress || 0)
              }}
            ></div>
          </div>
        </div>
      )}

      {/* Timeline Visualization */}
      <div className="timeline-container">
        <div className="timeline-axis">
          {/* Timeline entries */}
          <div className="timeline-entries">
            {filteredTimeline.map((entry, index) => (
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
                      {!selectedProject && (
                        <p className="project-name">📋 {getProjectName(entry.project_id)}</p>
                      )}
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
                        {entry.photos && entry.photos.length > 0 && (
                          <span className="detail-item">
                            📸 {entry.photos.length} photos
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
                <h4>📋 Project & Date</h4>
                <p><strong>Project:</strong> {getProjectName(selectedEntry.project_id)}</p>
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

export default ContractorConstructionTimeline;