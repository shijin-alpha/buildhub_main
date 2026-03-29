import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/ContractorSubmittedReports.css';

const ContractorSubmittedReports = ({ contractorId, selectedProject }) => {
  const toast = useToast();
  const [submittedReports, setSubmittedReports] = useState([]);
  const [projects, setProjects] = useState([]);
  const [loading, setLoading] = useState(false);
  const [expandedReport, setExpandedReport] = useState(null);
  const [pagination, setPagination] = useState({
    total: 0,
    limit: 20,
    offset: 0,
    has_more: false
  });

  // Load submitted reports
  useEffect(() => {
    if (contractorId) {
      loadSubmittedReports();
    }
  }, [contractorId, selectedProject]);

  const loadSubmittedReports = async (offset = 0) => {
    try {
      setLoading(true);
      
      let url = `/buildhub/backend/api/contractor/get_submitted_daily_reports.php?contractor_id=${contractorId}&limit=${pagination.limit}&offset=${offset}`;
      
      if (selectedProject) {
        url += `&project_id=${selectedProject}`;
      }

      const response = await fetch(url, {
        credentials: 'include'
      });

      const data = await response.json();
      
      if (data.success) {
        if (offset === 0) {
          // Fresh load
          setSubmittedReports(data.data.submitted_reports || []);
        } else {
          // Load more
          setSubmittedReports(prev => [...prev, ...(data.data.submitted_reports || [])]);
        }
        
        setProjects(data.data.projects || []);
        setPagination(data.data.pagination || pagination);
      } else {
        toast.error('Failed to load submitted reports: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading submitted reports:', error);
      toast.error('Error loading submitted reports');
    } finally {
      setLoading(false);
    }
  };

  const loadMoreReports = () => {
    if (pagination.has_more && !loading) {
      loadSubmittedReports(pagination.offset + pagination.limit);
    }
  };

  const toggleReportExpansion = (reportId) => {
    setExpandedReport(expandedReport === reportId ? null : reportId);
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'Unknown date';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const formatDateTime = (dateString) => {
    if (!dateString) return 'Unknown time';
    const date = new Date(dateString);
    return date.toLocaleString('en-IN', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading && submittedReports.length === 0) {
    return (
      <div className="submitted-reports-loading">
        <div className="loading-spinner">Loading submitted reports...</div>
      </div>
    );
  }

  return (
    <div className="contractor-submitted-reports">
      <div className="submitted-reports-header">
        <h4>📋 Your Submitted Daily Reports</h4>
        <p>View and verify your submitted progress reports</p>
        
        {projects.length > 0 && (
          <div className="projects-summary">
            <div className="summary-stats">
              <div className="stat-item">
                <span className="stat-value">{pagination.total}</span>
                <span className="stat-label">Total Reports</span>
              </div>
              <div className="stat-item">
                <span className="stat-value">{projects.length}</span>
                <span className="stat-label">Projects</span>
              </div>
            </div>
          </div>
        )}
      </div>

      {submittedReports.length === 0 ? (
        <div className="no-reports-message">
          <div className="empty-state">
            <div className="empty-icon">📝</div>
            <h3>No Reports Submitted Yet</h3>
            <p>
              {selectedProject 
                ? 'No daily reports have been submitted for this project yet.'
                : 'You haven\'t submitted any daily progress reports yet.'
              }
            </p>
            <p>Submit your first daily report using the form above to see it here.</p>
          </div>
        </div>
      ) : (
        <div className="submitted-reports-list">
          {submittedReports.map(report => (
            <div key={report.id} className="submitted-report-card">
              <div className="report-card-header">
                <div className="report-basic-info">
                  <div className="report-date-stage">
                    <h5 className="report-date">{formatDate(report.update_date)}</h5>
                    <div className="report-stage">
                      <span className="stage-badge">{report.construction_stage}</span>
                    </div>
                  </div>
                  
                  <div className="report-progress-info">
                    <div className="progress-numbers">
                      <span className="incremental-progress">
                        +{report.incremental_completion_percentage}%
                      </span>
                      <span className="cumulative-progress">
                        Total: {report.cumulative_completion_percentage}%
                      </span>
                    </div>
                    
                    <div className="progress-bar-small">
                      <div 
                        className={`progress-fill-small ${report.progress_class}`}
                        style={{ width: `${report.cumulative_completion_percentage}%` }}
                      ></div>
                    </div>
                  </div>
                </div>

                <div className="report-actions">
                  <div className="report-meta">
                    <span className="time-ago">{report.time_ago}</span>
                    <span className={`location-status ${report.location_class}`}>
                      {report.location_status}
                    </span>
                  </div>
                  
                  <button 
                    className="expand-report-btn"
                    onClick={() => toggleReportExpansion(report.id)}
                  >
                    {expandedReport === report.id ? '👁️ Hide Details' : '👁️ View Details'}
                  </button>
                </div>
              </div>

              <div className="report-summary">
                <div className="project-info">
                  <strong>{report.project_name}</strong>
                  {report.homeowner_name && (
                    <span className="homeowner-name"> • {report.homeowner_name}</span>
                  )}
                </div>
                
                <div className="work-summary">
                  <p className="work-done-preview">
                    {report.work_done_today.length > 100 
                      ? report.work_done_today.substring(0, 100) + '...'
                      : report.work_done_today
                    }
                  </p>
                </div>

                <div className="report-quick-stats">
                  <div className="quick-stat">
                    <span className="stat-icon">⏰</span>
                    <span className="stat-text">{report.working_hours}h</span>
                  </div>
                  <div className="quick-stat">
                    <span className="stat-icon">🌤️</span>
                    <span className="stat-text">{report.weather_condition}</span>
                  </div>
                  {report.total_workers > 0 && (
                    <div className="quick-stat">
                      <span className="stat-icon">👷</span>
                      <span className="stat-text">{report.total_workers} workers</span>
                    </div>
                  )}
                  {report.photos.length > 0 && (
                    <div className="quick-stat">
                      <span className="stat-icon">📸</span>
                      <span className="stat-text">{report.photos.length} photos</span>
                    </div>
                  )}
                </div>
              </div>

              {expandedReport === report.id && (
                <div className="report-expanded-details">
                  <div className="expanded-content">
                    <div className="detail-section">
                      <h6>Work Done Today</h6>
                      <p className="work-done-full">{report.work_done_today}</p>
                    </div>

                    {report.site_issues && (
                      <div className="detail-section">
                        <h6>Site Issues</h6>
                        <p className="site-issues">{report.site_issues}</p>
                      </div>
                    )}

                    {report.worker_types_array.length > 0 && (
                      <div className="detail-section">
                        <h6>Labour Details</h6>
                        <div className="labour-summary">
                          <div className="labour-stats">
                            <span>Total Workers: {report.total_workers}</span>
                            <span>Types: {report.worker_types_array.join(', ')}</span>
                            {report.avg_productivity && (
                              <span>Avg Productivity: {parseFloat(report.avg_productivity).toFixed(1)}/5</span>
                            )}
                          </div>
                        </div>
                      </div>
                    )}

                    {report.photos.length > 0 && (
                      <div className="detail-section">
                        <h6>Progress Photos ({report.photos.length})</h6>
                        <div className="photos-grid">
                          {report.photo_urls.map((photoUrl, index) => (
                            <div key={index} className="photo-thumbnail">
                              <img 
                                src={photoUrl} 
                                alt={`Progress photo ${index + 1}`}
                                onClick={() => window.open(photoUrl, '_blank')}
                              />
                            </div>
                          ))}
                        </div>
                      </div>
                    )}

                    <div className="detail-section">
                      <h6>Report Details</h6>
                      <div className="report-metadata">
                        <div className="metadata-row">
                          <span className="metadata-label">Submitted:</span>
                          <span className="metadata-value">{formatDateTime(report.created_at)}</span>
                        </div>
                        <div className="metadata-row">
                          <span className="metadata-label">Report ID:</span>
                          <span className="metadata-value">#{report.id}</span>
                        </div>
                        {report.latitude && report.longitude && (
                          <div className="metadata-row">
                            <span className="metadata-label">Location:</span>
                            <span className="metadata-value">
                              {report.latitude.toFixed(6)}, {report.longitude.toFixed(6)}
                              <span className={`location-badge ${report.location_class}`}>
                                {report.location_verified ? '✅ Verified' : '⚠️ Not Verified'}
                              </span>
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              )}
            </div>
          ))}

          {pagination.has_more && (
            <div className="load-more-section">
              <button 
                className="load-more-btn"
                onClick={loadMoreReports}
                disabled={loading}
              >
                {loading ? 'Loading...' : `Load More Reports (${pagination.total - submittedReports.length} remaining)`}
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default ContractorSubmittedReports;