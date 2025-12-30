import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/HomeownerProgressReports.css';

const HomeownerProgressReports = ({ activeTab, isVisible = true }) => {
  const toast = useToast();
  
  const [progressReports, setProgressReports] = useState([]);
  const [selectedReport, setSelectedReport] = useState(null);
  const [showReportDetails, setShowReportDetails] = useState(false);
  const [progressLoading, setProgressLoading] = useState(false);
  const [reportFilter, setReportFilter] = useState('all');

  const fetchProgressReports = async () => {
    setProgressLoading(true);
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/get_progress_reports.php', {
        credentials: 'include'
      });
      const data = await response.json();
      
      if (data.success) {
        setProgressReports(data.data.reports || []);
      } else {
        toast.error('Failed to load progress reports: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading progress reports:', error);
      toast.error('Error loading progress reports');
    } finally {
      setProgressLoading(false);
    }
  };

  const viewReportDetails = async (reportId) => {
    try {
      const response = await fetch(`/buildhub/backend/api/homeowner/view_progress_report.php?report_id=${reportId}`, {
        credentials: 'include'
      });
      const data = await response.json();
      
      if (data.success) {
        setSelectedReport(data.data);
        setShowReportDetails(true);
      } else {
        toast.error('Failed to load report details: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading report details:', error);
      toast.error('Error loading report details');
    }
  };

  const acknowledgeReport = async (reportId, notes = '') => {
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/view_progress_report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          report_id: reportId,
          notes: notes
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        toast.success('Report acknowledged successfully');
        fetchProgressReports();
        setShowReportDetails(false);
      } else {
        toast.error('Failed to acknowledge report: ' + data.message);
      }
    } catch (error) {
      console.error('Error acknowledging report:', error);
      toast.error('Error acknowledging report');
    }
  };

  // Load progress reports when component becomes active
  useEffect(() => {
    if (activeTab === 'progress' && isVisible) {
      fetchProgressReports();
    }
  }, [activeTab, isVisible]);

  // Don't render anything if not visible
  if (!isVisible) {
    return null;
  }

  const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      'sent': { color: '#007bff', bg: '#e3f2fd', text: '📤 Sent' },
      'viewed': { color: '#28a745', bg: '#e8f5e9', text: '👀 Viewed' },
      'acknowledged': { color: '#6f42c1', bg: '#f3e5f5', text: '✅ Acknowledged' }
    };
    
    const config = statusConfig[status] || statusConfig['sent'];
    
    return (
      <span style={{
        background: config.bg,
        color: config.color,
        padding: '4px 12px',
        borderRadius: '20px',
        fontSize: '12px',
        fontWeight: '600',
        border: `1px solid ${config.color}20`
      }}>
        {config.text}
      </span>
    );
  };

  const getReportTypeIcon = (type) => {
    const icons = {
      'daily': '📅',
      'weekly': '📊',
      'monthly': '📈'
    };
    return icons[type] || '📋';
  };

  const filteredReports = progressReports.filter(report => {
    if (reportFilter === 'all') return true;
    return report.report_type === reportFilter;
  });

  return (
    <div className="section-card" style={{ marginTop: '1rem' }}>
      <div className="section-header">
        <div className="header-content">
          <div>
            <h2>📊 Construction Progress Reports</h2>
            <p>View detailed progress reports submitted by your contractor with comprehensive project updates</p>
          </div>
          <button 
            className="btn btn-primary" 
            onClick={fetchProgressReports}
            disabled={progressLoading}
          >
            {progressLoading ? '⏳ Loading...' : '↻ Refresh Reports'}
          </button>
        </div>
      </div>

      {/* Filter Tabs */}
      <div className="progress-filters" style={{ 
        display: 'flex', 
        gap: '10px', 
        marginBottom: '20px',
        borderBottom: '1px solid #e9ecef',
        paddingBottom: '15px'
      }}>
        {['all', 'daily', 'weekly', 'monthly'].map(filter => (
          <button
            key={filter}
            className={`filter-btn ${reportFilter === filter ? 'active' : ''}`}
            onClick={() => setReportFilter(filter)}
            style={{
              padding: '8px 16px',
              border: '2px solid #007bff',
              background: reportFilter === filter ? '#007bff' : 'transparent',
              color: reportFilter === filter ? 'white' : '#007bff',
              borderRadius: '6px',
              fontWeight: '600',
              cursor: 'pointer',
              transition: 'all 0.3s ease',
              textTransform: 'capitalize'
            }}
          >
            {filter === 'all' ? '📋 All Reports' : `${getReportTypeIcon(filter)} ${filter} Reports`}
          </button>
        ))}
      </div>

      <div className="section-content">
        {progressLoading ? (
          <div style={{ textAlign: 'center', padding: '40px' }}>
            <div style={{ fontSize: '48px', marginBottom: '16px' }}>⏳</div>
            <h3>Loading Progress Reports...</h3>
            <p>Fetching your latest construction progress updates</p>
          </div>
        ) : filteredReports.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '40px' }}>
            <div style={{ fontSize: '48px', marginBottom: '16px' }}>📋</div>
            <h3>No Progress Reports Yet</h3>
            <p>Your contractor will submit progress reports here as work progresses on your project.</p>
          </div>
        ) : (
          <div className="reports-grid" style={{ 
            display: 'grid', 
            gap: '20px',
            gridTemplateColumns: 'repeat(auto-fill, minmax(400px, 1fr))'
          }}>
            {filteredReports.map(report => (
              <div 
                key={report.id} 
                className="report-card"
                style={{
                  background: 'white',
                  border: '1px solid #e9ecef',
                  borderRadius: '12px',
                  padding: '20px',
                  boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                  transition: 'all 0.3s ease',
                  cursor: 'pointer',
                  position: 'relative'
                }}
                onClick={() => viewReportDetails(report.id)}
                onMouseOver={(e) => {
                  e.currentTarget.style.transform = 'translateY(-2px)';
                  e.currentTarget.style.boxShadow = '0 4px 16px rgba(0,0,0,0.15)';
                }}
                onMouseOut={(e) => {
                  e.currentTarget.style.transform = 'translateY(0)';
                  e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                }}
              >
                <div style={{
                  position: 'absolute',
                  top: 0,
                  left: 0,
                  right: 0,
                  height: '4px',
                  background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                  borderRadius: '12px 12px 0 0'
                }}></div>
                
                <div className="report-header" style={{ 
                  display: 'flex', 
                  justifyContent: 'space-between', 
                  alignItems: 'flex-start',
                  marginBottom: '15px',
                  marginTop: '10px'
                }}>
                  <div>
                    <h3 style={{ 
                      margin: '0 0 8px 0', 
                      color: '#2c3e50',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px'
                    }}>
                      {getReportTypeIcon(report.report_type)} {report.report_type.charAt(0).toUpperCase() + report.report_type.slice(1)} Report
                    </h3>
                    <p style={{ 
                      margin: '0', 
                      color: '#6c757d', 
                      fontSize: '14px',
                      fontWeight: '500'
                    }}>
                      {report.project_name}
                    </p>
                  </div>
                  {getStatusBadge(report.status)}
                </div>

                <div className="report-meta" style={{ marginBottom: '15px' }}>
                  <div style={{ 
                    display: 'grid', 
                    gridTemplateColumns: '1fr 1fr', 
                    gap: '10px',
                    fontSize: '13px',
                    color: '#6c757d'
                  }}>
                    <div><strong>Contractor:</strong> {report.contractor_name}</div>
                    <div><strong>Submitted:</strong> {formatDate(report.created_at)}</div>
                    {report.period_start && (
                      <>
                        <div><strong>Period Start:</strong> {new Date(report.period_start).toLocaleDateString('en-IN')}</div>
                        <div><strong>Period End:</strong> {new Date(report.period_end).toLocaleDateString('en-IN')}</div>
                      </>
                    )}
                  </div>
                </div>

                {report.summary && (
                  <div className="report-summary" style={{
                    background: '#f8f9fa',
                    padding: '15px',
                    borderRadius: '8px',
                    marginBottom: '15px'
                  }}>
                    <h4 style={{ margin: '0 0 10px 0', color: '#2c3e50', fontSize: '14px' }}>📋 Summary</h4>
                    <div style={{ 
                      display: 'grid', 
                      gridTemplateColumns: 'repeat(2, 1fr)', 
                      gap: '8px',
                      fontSize: '12px'
                    }}>
                      {report.summary.total_days > 0 && (
                        <div><strong>Working Days:</strong> {report.summary.total_days}</div>
                      )}
                      {report.summary.total_workers > 0 && (
                        <div><strong>Workers:</strong> {report.summary.total_workers}</div>
                      )}
                      {report.summary.total_hours > 0 && (
                        <div><strong>Hours:</strong> {report.summary.total_hours}h</div>
                      )}
                      {report.summary.progress_percentage > 0 && (
                        <div><strong>Progress:</strong> {report.summary.progress_percentage}%</div>
                      )}
                      {report.summary.photos_count > 0 && (
                        <div><strong>Photos:</strong> {report.summary.photos_count}</div>
                      )}
                    </div>
                  </div>
                )}

                <div className="report-actions" style={{ 
                  display: 'flex', 
                  justifyContent: 'space-between',
                  alignItems: 'center'
                }}>
                  <div style={{ fontSize: '12px', color: '#6c757d' }}>
                    {report.has_photos && '📸 Contains Photos'}
                  </div>
                  <div style={{ 
                    color: '#007bff', 
                    fontSize: '14px', 
                    fontWeight: '600',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '4px'
                  }}>
                    👁️ View Details
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Report Details Modal */}
      {showReportDetails && selectedReport && (
        <div className="modal-overlay" style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'rgba(0,0,0,0.8)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 1000,
          padding: '20px'
        }}>
          <div className="modal-content" style={{
            background: 'white',
            borderRadius: '16px',
            maxWidth: '1000px',
            width: '100%',
            maxHeight: '90vh',
            overflow: 'auto',
            boxShadow: '0 20px 60px rgba(0,0,0,0.3)'
          }}>
            <div className="modal-header" style={{
              padding: '24px',
              background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
              color: 'white',
              borderRadius: '16px 16px 0 0',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center'
            }}>
              <div>
                <h2 style={{ margin: '0 0 8px 0' }}>
                  {getReportTypeIcon(selectedReport.report_type)} {selectedReport.report_type.charAt(0).toUpperCase() + selectedReport.report_type.slice(1)} Progress Report
                </h2>
                <p style={{ margin: '0', opacity: '0.9' }}>
                  Submitted by {selectedReport.contractor.name} on {formatDate(selectedReport.created_at)}
                </p>
              </div>
              <button 
                onClick={() => setShowReportDetails(false)}
                style={{
                  background: 'rgba(255,255,255,0.2)',
                  border: 'none',
                  color: 'white',
                  fontSize: '24px',
                  width: '40px',
                  height: '40px',
                  borderRadius: '50%',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center'
                }}
              >
                ×
              </button>
            </div>

            <div className="modal-body" style={{ padding: '24px' }}>
              {selectedReport.report_data && (
                <div>
                  {/* Executive Summary */}
                  {selectedReport.report_data.summary && (
                    <div className="report-section" style={{ marginBottom: '30px' }}>
                      <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                        📋 Executive Summary
                      </h3>
                      <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', 
                        gap: '15px',
                        marginTop: '15px'
                      }}>
                        {Object.entries(selectedReport.report_data.summary).map(([key, value]) => (
                          <div key={key} style={{
                            textAlign: 'center',
                            padding: '15px',
                            background: '#f8f9fa',
                            borderRadius: '8px',
                            border: '1px solid #dee2e6'
                          }}>
                            <div style={{ fontSize: '1.5rem', fontWeight: '700', color: '#667eea' }}>
                              {value}
                            </div>
                            <div style={{ fontSize: '0.9rem', color: '#6c757d', textTransform: 'capitalize' }}>
                              {key.replace(/_/g, ' ')}
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Daily Updates */}
                  {selectedReport.report_data.daily_updates && selectedReport.report_data.daily_updates.length > 0 && (
                    <div className="report-section" style={{ marginBottom: '30px' }}>
                      <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                        🏗️ Work Progress Details
                      </h3>
                      {selectedReport.report_data.daily_updates.map((update, index) => (
                        <div key={index} style={{
                          marginTop: '15px',
                          padding: '15px',
                          background: '#f8f9fa',
                          borderRadius: '8px',
                          borderLeft: '4px solid #667eea'
                        }}>
                          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '10px' }}>
                            <h4 style={{ margin: '0', color: '#2c3e50' }}>
                              {formatDate(update.update_date)}
                            </h4>
                            <span style={{
                              background: '#667eea',
                              color: 'white',
                              padding: '4px 12px',
                              borderRadius: '20px',
                              fontSize: '12px'
                            }}>
                              {update.construction_stage}
                            </span>
                          </div>
                          <p><strong>Work Done:</strong> {update.work_done_today}</p>
                          <div style={{ display: 'flex', gap: '20px', fontSize: '14px', color: '#6c757d' }}>
                            <span>Progress: +{update.incremental_completion_percentage}%</span>
                            <span>Hours: {update.working_hours}h</span>
                            <span>Weather: {update.weather_condition}</span>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}

                  {/* Photos */}
                  {selectedReport.report_data.photos && selectedReport.report_data.photos.length > 0 && (
                    <div className="report-section" style={{ marginBottom: '30px' }}>
                      <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                        📸 Progress Photos
                      </h3>
                      <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', 
                        gap: '15px',
                        marginTop: '15px'
                      }}>
                        {selectedReport.report_data.photos.slice(0, 6).map((photo, index) => (
                          <div key={index} style={{ textAlign: 'center' }}>
                            <img 
                              src={photo.url} 
                              alt={`Progress ${index + 1}`}
                              style={{
                                width: '100%',
                                height: '150px',
                                objectFit: 'cover',
                                borderRadius: '8px',
                                border: '1px solid #dee2e6'
                              }}
                            />
                            <div style={{ marginTop: '8px', fontSize: '12px', color: '#6c757d' }}>
                              <div>{formatDate(photo.date)}</div>
                              <div>{photo.location}</div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              )}

              {/* Acknowledgment Section */}
              {selectedReport.status !== 'acknowledged' && (
                <div className="acknowledgment-section" style={{
                  marginTop: '30px',
                  padding: '20px',
                  background: '#e3f2fd',
                  borderRadius: '8px',
                  border: '1px solid #bbdefb'
                }}>
                  <h4 style={{ margin: '0 0 15px 0', color: '#1976d2' }}>✅ Acknowledge This Report</h4>
                  <p style={{ margin: '0 0 15px 0', fontSize: '14px', color: '#424242' }}>
                    Acknowledge that you have reviewed this progress report. You can optionally add feedback or comments.
                  </p>
                  <div style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
                    <button
                      onClick={() => acknowledgeReport(selectedReport.id)}
                      style={{
                        background: '#28a745',
                        color: 'white',
                        border: 'none',
                        padding: '10px 20px',
                        borderRadius: '6px',
                        fontWeight: '600',
                        cursor: 'pointer'
                      }}
                    >
                      ✅ Acknowledge Report
                    </button>
                    <span style={{ fontSize: '12px', color: '#6c757d' }}>
                      This will notify your contractor that you've reviewed the report
                    </span>
                  </div>
                </div>
              )}

              {selectedReport.status === 'acknowledged' && (
                <div style={{
                  marginTop: '20px',
                  padding: '15px',
                  background: '#d4edda',
                  borderRadius: '8px',
                  border: '1px solid #c3e6cb',
                  textAlign: 'center'
                }}>
                  <span style={{ color: '#155724', fontWeight: '600' }}>
                    ✅ Report Acknowledged on {formatDate(selectedReport.acknowledged_at)}
                  </span>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default HomeownerProgressReports;