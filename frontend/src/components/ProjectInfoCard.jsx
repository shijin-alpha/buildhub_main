import React from 'react';
import '../styles/ProjectInfoCard.css';

const ProjectInfoCard = ({ project, onSelect, isSelected = false }) => {
  if (!project) return null;

  const formatCurrency = (amount) => {
    if (!amount) return 'Not specified';
    return `₹${parseFloat(amount).toLocaleString('en-IN')}`;
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'Not specified';
    return new Date(dateString).toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      created: { color: '#17a2b8', bg: '#d1ecf1', text: '🆕 Created', icon: '🏗️' },
      in_progress: { color: '#ffc107', bg: '#fff3cd', text: '🚧 In Progress', icon: '⚡' },
      completed: { color: '#28a745', bg: '#d4edda', text: '✅ Completed', icon: '🎉' },
      on_hold: { color: '#6c757d', bg: '#f8f9fa', text: '⏸️ On Hold', icon: '⏸️' },
      cancelled: { color: '#dc3545', bg: '#f8d7da', text: '❌ Cancelled', icon: '🚫' }
    };
    
    const config = statusConfig[status] || statusConfig.created;
    
    return (
      <span 
        className="status-badge"
        style={{
          backgroundColor: config.bg,
          color: config.color,
          padding: '4px 12px',
          borderRadius: '20px',
          fontSize: '12px',
          fontWeight: '600',
          border: `1px solid ${config.color}20`,
          display: 'inline-flex',
          alignItems: 'center',
          gap: '4px'
        }}
      >
        {config.text}
      </span>
    );
  };

  const getCompletionColor = (percentage) => {
    if (percentage >= 80) return '#28a745';
    if (percentage >= 50) return '#ffc107';
    if (percentage >= 20) return '#fd7e14';
    return '#dc3545';
  };

  return (
    <div 
      className={`project-info-card ${isSelected ? 'selected' : ''}`}
      onClick={() => onSelect && onSelect(project)}
    >
      <div className="project-header">
        <div className="project-title-section">
          <h3 className="project-name">
            🏗️ {project.project_name}
          </h3>
          <div className="project-id">
            ID: #{project.id}
          </div>
        </div>
        <div className="project-status">
          {getStatusBadge(project.status)}
        </div>
      </div>

      <div className="project-details">
        <div className="detail-row">
          <div className="detail-item">
            <span className="detail-icon">👤</span>
            <div className="detail-content">
              <span className="detail-label">Homeowner</span>
              <span className="detail-value">{project.homeowner_name}</span>
            </div>
          </div>
          
          {project.homeowner_email && (
            <div className="detail-item">
              <span className="detail-icon">📧</span>
              <div className="detail-content">
                <span className="detail-label">Email</span>
                <span className="detail-value">{project.homeowner_email}</span>
              </div>
            </div>
          )}
        </div>

        <div className="detail-row">
          {project.estimate_cost && (
            <div className="detail-item">
              <span className="detail-icon">💰</span>
              <div className="detail-content">
                <span className="detail-label">Budget</span>
                <span className="detail-value">{formatCurrency(project.estimate_cost)}</span>
              </div>
            </div>
          )}
          
          {project.timeline && (
            <div className="detail-item">
              <span className="detail-icon">⏱️</span>
              <div className="detail-content">
                <span className="detail-label">Timeline</span>
                <span className="detail-value">{project.timeline}</span>
              </div>
            </div>
          )}
        </div>

        {project.location && (
          <div className="detail-row">
            <div className="detail-item full-width">
              <span className="detail-icon">📍</span>
              <div className="detail-content">
                <span className="detail-label">Location</span>
                <span className="detail-value">{project.location}</span>
              </div>
            </div>
          </div>
        )}

        {project.current_stage && (
          <div className="detail-row">
            <div className="detail-item">
              <span className="detail-icon">🎯</span>
              <div className="detail-content">
                <span className="detail-label">Current Stage</span>
                <span className="detail-value">{project.current_stage}</span>
              </div>
            </div>
            
            {project.completion_percentage !== undefined && (
              <div className="detail-item">
                <span className="detail-icon">📊</span>
                <div className="detail-content">
                  <span className="detail-label">Progress</span>
                  <div className="progress-container">
                    <div className="progress-bar">
                      <div 
                        className="progress-fill"
                        style={{
                          width: `${project.completion_percentage}%`,
                          backgroundColor: getCompletionColor(project.completion_percentage)
                        }}
                      ></div>
                    </div>
                    <span className="progress-text">
                      {project.completion_percentage}%
                    </span>
                  </div>
                </div>
              </div>
            )}
          </div>
        )}

        <div className="detail-row">
          {project.created_at && (
            <div className="detail-item">
              <span className="detail-icon">📅</span>
              <div className="detail-content">
                <span className="detail-label">Created</span>
                <span className="detail-value">{formatDate(project.created_at)}</span>
              </div>
            </div>
          )}
          
          {project.expected_completion_date && (
            <div className="detail-item">
              <span className="detail-icon">🎯</span>
              <div className="detail-content">
                <span className="detail-label">Expected Completion</span>
                <span className="detail-value">{formatDate(project.expected_completion_date)}</span>
              </div>
            </div>
          )}
        </div>
      </div>

      {onSelect && (
        <div className="project-actions">
          <button className="select-project-btn">
            {isSelected ? '✅ Selected' : '👆 Select Project'}
          </button>
        </div>
      )}
    </div>
  );
};

export default ProjectInfoCard;