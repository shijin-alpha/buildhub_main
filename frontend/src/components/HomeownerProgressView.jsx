import React, { useState, useEffect } from 'react';
import ProgressTimeline from './ProgressTimeline';
import '../styles/HomeownerProgress.css';

const HomeownerProgressView = ({ homeownerId }) => {
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState('');
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [unreadCount, setUnreadCount] = useState(0);

  useEffect(() => {
    loadProgressData();
  }, [homeownerId]);

  const loadProgressData = async () => {
    try {
      setLoading(true);
      const response = await fetch(
        `http://localhost/buildhub/backend/api/homeowner/get_progress_updates.php?homeowner_id=${homeownerId}`,
        { credentials: 'include' }
      );
      const data = await response.json();
      
      if (data.success) {
        setProjects(data.data.projects || []);
        setUnreadCount(data.data.unread_notifications || 0);
        
        // Auto-select first project if available
        if (data.data.projects && data.data.projects.length > 0 && !selectedProject) {
          setSelectedProject(data.data.projects[0].project_id.toString());
        }
      } else {
        console.error('Failed to load progress data:', data.message);
      }
    } catch (error) {
      console.error('Error loading progress data:', error);
    } finally {
      setLoading(false);
    }
  };

  const markNotificationsAsRead = async () => {
    try {
      await fetch(
        `http://localhost/buildhub/backend/api/homeowner/mark_notifications_read.php`,
        {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ homeowner_id: homeownerId })
        }
      );
      setUnreadCount(0);
    } catch (error) {
      console.error('Error marking notifications as read:', error);
    }
  };

  const getProjectStatusColor = (progress) => {
    if (progress >= 100) return '#28a745';
    if (progress >= 75) return '#17a2b8';
    if (progress >= 50) return '#ffc107';
    if (progress >= 25) return '#fd7e14';
    return '#6c757d';
  };

  const getProjectStatusText = (progress, completedStages) => {
    if (progress >= 100) return 'Completed';
    if (progress >= 75) return 'Near Completion';
    if (progress >= 50) return 'In Progress';
    if (progress >= 25) return 'Started';
    if (completedStages > 0) return 'Initiated';
    return 'Not Started';
  };

  if (loading) {
    return (
      <div className="homeowner-progress-loading">
        <div className="loading-spinner"></div>
        <p>Loading your construction projects...</p>
      </div>
    );
  }

  return (
    <div className="homeowner-progress-view">
      <div className="progress-header">
        <h2>Construction Progress</h2>
        <p>Track the progress of your construction projects in real-time</p>
        
        {unreadCount > 0 && (
          <div className="notification-alert">
            <span>You have {unreadCount} new progress update{unreadCount !== 1 ? 's' : ''}</span>
            <button onClick={markNotificationsAsRead} className="mark-read-btn">
              Mark as Read
            </button>
          </div>
        )}
      </div>

      {projects.length === 0 ? (
        <div className="no-projects">
          <div className="empty-state">
            <h3>No Construction Projects</h3>
            <p>You don't have any active construction projects yet. Once a contractor is assigned and construction begins, you'll see progress updates here.</p>
          </div>
        </div>
      ) : (
        <div className="progress-content">
          {/* Project Selection */}
          <div className="project-selector">
            <label htmlFor="project-select">Select Project:</label>
            <select
              id="project-select"
              value={selectedProject}
              onChange={(e) => setSelectedProject(e.target.value)}
            >
              <option value="">Choose a project...</option>
              {projects.map(project => (
                <option key={project.project_id} value={project.project_id}>
                  {project.contractor_first_name} {project.contractor_last_name} - 
                  {project.plot_size} ({project.budget_range})
                </option>
              ))}
            </select>
          </div>

          {/* Projects Overview Grid */}
          <div className="projects-grid">
            {projects.map(project => (
              <div 
                key={project.project_id} 
                className={`project-card ${selectedProject == project.project_id ? 'selected' : ''}`}
                onClick={() => setSelectedProject(project.project_id.toString())}
              >
                <div className="project-header">
                  <h4>Project with {project.contractor_first_name} {project.contractor_last_name}</h4>
                  <span 
                    className="project-status"
                    style={{ 
                      backgroundColor: getProjectStatusColor(project.latest_progress),
                      color: 'white'
                    }}
                  >
                    {getProjectStatusText(project.latest_progress, project.completed_stages)}
                  </span>
                </div>
                
                <div className="project-details">
                  <div className="detail-row">
                    <span>Plot Size:</span>
                    <span>{project.plot_size}</span>
                  </div>
                  <div className="detail-row">
                    <span>Budget:</span>
                    <span>{project.budget_range}</span>
                  </div>
                  <div className="detail-row">
                    <span>Progress:</span>
                    <span>{project.latest_progress}%</span>
                  </div>
                  <div className="detail-row">
                    <span>Completed Stages:</span>
                    <span>{project.completed_stages}</span>
                  </div>
                  <div className="detail-row">
                    <span>Total Updates:</span>
                    <span>{project.total_updates}</span>
                  </div>
                  <div className="detail-row">
                    <span>Last Update:</span>
                    <span>{project.last_update_formatted}</span>
                  </div>
                </div>

                <div className="project-progress-bar">
                  <div className="progress-bar">
                    <div 
                      className="progress-fill"
                      style={{ 
                        width: `${project.latest_progress}%`,
                        backgroundColor: getProjectStatusColor(project.latest_progress)
                      }}
                    ></div>
                  </div>
                  <span className="progress-text">{project.latest_progress}%</span>
                </div>
              </div>
            ))}
          </div>

          {/* Progress Timeline */}
          {selectedProject && (
            <div className="timeline-section">
              <ProgressTimeline 
                homeownerId={homeownerId}
                projectId={selectedProject}
                userRole="homeowner"
              />
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default HomeownerProgressView;