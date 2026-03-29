import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';
import '../styles/ProgressReportGenerator.css';
import '../styles/ProgressReportGeneratorHistory.css';
import '../styles/ProfessionalReportForm.css';

const ProgressReportGenerator = ({ contractorId, onClose }) => {
  const toast = useToast();
  
  const [activeTab, setActiveTab] = useState('generate'); // 'generate' or 'history'
  const [reportType, setReportType] = useState('daily'); // 'daily', 'weekly', 'monthly'
  const [selectedProject, setSelectedProject] = useState('');
  const [dateRange, setDateRange] = useState({
    startDate: new Date().toISOString().split('T')[0],
    endDate: new Date().toISOString().split('T')[0]
  });
  const [projects, setProjects] = useState([]);
  const [reportData, setReportData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [generating, setGenerating] = useState(false);
  
  // History tab state
  const [sentReports, setSentReports] = useState([]);
  const [groupedReports, setGroupedReports] = useState({ daily: [], weekly: [], monthly: [] });
  const [reportStats, setReportStats] = useState({});
  const [historyLoading, setHistoryLoading] = useState(false);
  const [selectedHistoryProject, setSelectedHistoryProject] = useState('');
  const [historyReportType, setHistoryReportType] = useState('');

  useEffect(() => {
    loadProjects();
    setDefaultDateRange();
    if (activeTab === 'history') {
      loadSentReports();
    }
  }, [reportType, activeTab]);

  const loadProjects = async () => {
    try {
      const response = await fetch(
        `http://localhost/buildhub/backend/api/contractor/get_assigned_projects.php?contractor_id=${contractorId}`,
        { credentials: 'include' }
      );
      const data = await response.json();
      
      if (data.success) {
        setProjects(data.data.projects || []);
      }
    } catch (error) {
      console.error('Error loading projects:', error);
    }
  };

  const loadSentReports = async () => {
    setHistoryLoading(true);
    try {
      const params = new URLSearchParams();
      if (selectedHistoryProject) params.append('project_id', selectedHistoryProject);
      if (historyReportType) params.append('report_type', historyReportType);
      params.append('limit', '100');
      
      console.log('Loading sent reports with params:', params.toString());
      
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_sent_reports.php?${params.toString()}`,
        { credentials: 'include' }
      );
      
      console.log('Response status:', response.status);
      console.log('Response headers:', response.headers);
      
      const data = await response.json();
      console.log('API Response:', data);
      
      if (data.success) {
        console.log('Reports loaded successfully:', data.data.reports.length, 'reports');
        setSentReports(data.data.reports);
        setGroupedReports(data.data.grouped_reports);
        setReportStats(data.data.statistics);
      } else {
        console.error('API Error:', data.message);
        toast.error('Failed to load sent reports: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading sent reports:', error);
      toast.error('Error loading sent reports');
    } finally {
      setHistoryLoading(false);
    }
  };

  const setDefaultDateRange = () => {
    const today = new Date();
    let startDate, endDate;

    switch (reportType) {
      case 'daily':
        startDate = endDate = today.toISOString().split('T')[0];
        break;
      case 'weekly':
        const monday = new Date(today.setDate(today.getDate() - today.getDay() + 1));
        const sunday = new Date(monday);
        sunday.setDate(monday.getDate() + 6);
        startDate = monday.toISOString().split('T')[0];
        endDate = sunday.toISOString().split('T')[0];
        break;
      case 'monthly':
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        startDate = firstDay.toISOString().split('T')[0];
        endDate = lastDay.toISOString().split('T')[0];
        break;
    }

    setDateRange({ startDate, endDate });
  };

  const generateReport = async () => {
    if (!selectedProject) {
      toast.error('Please select a project');
      return;
    }

    setLoading(true);
    try {
      const response = await fetch('/buildhub/backend/api/contractor/generate_progress_report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          contractor_id: contractorId,
          project_id: selectedProject,
          report_type: reportType,
          start_date: dateRange.startDate,
          end_date: dateRange.endDate
        })
      });

      const data = await response.json();
      
      if (data.success) {
        setReportData(data.data);
        toast.success('Report generated successfully!');
      } else {
        toast.error('Failed to generate report: ' + data.message);
      }
    } catch (error) {
      console.error('Error generating report:', error);
      toast.error('Error generating report');
    } finally {
      setLoading(false);
    }
  };

  const downloadPDF = async () => {
    if (!reportData) return;

    setGenerating(true);
    try {
      const reportElement = document.getElementById('report-content');
      
      // Create PDF
      const canvas = await html2canvas(reportElement, {
        scale: 2,
        useCORS: true,
        allowTaint: true,
        backgroundColor: '#ffffff'
      });

      const pdf = new jsPDF('p', 'mm', 'a4');
      const imgData = canvas.toDataURL('image/png');
      const imgWidth = 210;
      const pageHeight = 295;
      const imgHeight = (canvas.height * imgWidth) / canvas.width;
      let heightLeft = imgHeight;

      let position = 0;
      pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
      heightLeft -= pageHeight;

      while (heightLeft >= 0) {
        position = heightLeft - imgHeight;
        pdf.addPage();
        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;
      }

      const fileName = `${reportType}_report_${reportData?.project?.name || 'report'}_${dateRange.startDate}_to_${dateRange.endDate}.pdf`;
      pdf.save(fileName);
      
      toast.success('Report downloaded successfully!');
    } catch (error) {
      console.error('Error generating PDF:', error);
      toast.error('Error generating PDF');
    } finally {
      setGenerating(false);
    }
  };

  const sendToHomeowner = async () => {
    if (!reportData) return;

    try {
      const response = await fetch('/buildhub/backend/api/contractor/send_report_to_homeowner.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          contractor_id: contractorId,
          project_id: selectedProject,
          report_type: reportType,
          report_data: reportData,
          date_range: dateRange
        })
      });

      const data = await response.json();
      
      if (data.success) {
        toast.success('Report sent to homeowner successfully!');
      } else {
        toast.error('Failed to send report: ' + data.message);
      }
    } catch (error) {
      console.error('Error sending report:', error);
      toast.error('Error sending report');
    }
  };

  const formatCurrency = (amount) => {
    return `₹${Number(amount || 0).toLocaleString('en-IN')}`;
  };

  const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const getStatusBadge = (report) => {
    if (report.homeowner_acknowledged_at) {
      return <span className="status-badge acknowledged">✅ Acknowledged</span>;
    } else if (report.homeowner_viewed_at) {
      return <span className="status-badge viewed">👁️ Viewed</span>;
    } else if (report.status === 'sent') {
      return <span className="status-badge sent">📤 Sent</span>;
    } else {
      return <span className="status-badge draft">📝 Draft</span>;
    }
  };

  const renderReportCard = (report) => (
    <div key={report.id} className="report-card">
      <div className="report-card-header">
        <div className="report-title">
          <h4>{report.project_name}</h4>
          <span className="report-type-badge">{report.report_type}</span>
        </div>
        {getStatusBadge(report)}
      </div>
      
      <div className="report-card-body">
        <div className="report-meta">
          <div className="meta-item">
            <span className="meta-label">Date:</span>
            <span>{formatDate(report.period_start)}</span>
          </div>
          <div className="meta-item">
            <span className="meta-label">Homeowner:</span>
            <span>{report.homeowner_name}</span>
          </div>
          <div className="meta-item">
            <span className="meta-label">Stage:</span>
            <span>{report.work_details?.construction_stage || 'N/A'}</span>
          </div>
        </div>
        
        {/* Work Details for Daily Progress */}
        {report.work_details && (
          <div className="work-summary">
            <h5>Work Done Today:</h5>
            <p>{report.work_details.work_done_today?.substring(0, 100)}...</p>
            {report.work_details.weather_condition && (
              <div className="weather-info">
                <span>Weather: {report.work_details.weather_condition}</span>
              </div>
            )}
          </div>
        )}
        
        <div className="report-summary">
          <div className="summary-grid">
            <div className="summary-item">
              <span className="summary-value">{report.summary.progress_percentage}%</span>
              <span className="summary-label">Progress</span>
            </div>
            <div className="summary-item">
              <span className="summary-value">{report.summary.total_hours}h</span>
              <span className="summary-label">Hours</span>
            </div>
            <div className="summary-item">
              <span className="summary-value">{report.summary.photos_count}</span>
              <span className="summary-label">Photos</span>
            </div>
            <div className="summary-item">
              <span className="summary-value">{report.summary.cumulative_progress || 0}%</span>
              <span className="summary-label">Total Progress</span>
            </div>
          </div>
        </div>
        
        <div className="report-actions">
          <button 
            className="btn-secondary"
            onClick={() => viewReportDetails(report)}
          >
            �️ Vienw Details
          </button>
          <button 
            className="btn-primary"
            onClick={() => downloadReportPDF(report)}
          >
            💾 Download PDF
          </button>
        </div>
      </div>
    </div>
  );

  const viewReportDetails = (report) => {
    // Set the report data to display in the preview
    setReportData(JSON.parse(report.report_data || '{}'));
    setActiveTab('generate'); // Switch to generate tab to show the report
  };

  const downloadReportPDF = async (report) => {
    // Implementation for downloading existing report as PDF
    toast.info('PDF download feature coming soon!');
  };

  const resendReport = async (report) => {
    try {
      const response = await fetch('/buildhub/backend/api/contractor/resend_report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ report_id: report.id })
      });

      const data = await response.json();
      
      if (data.success) {
        toast.success('Report resent successfully!');
        loadSentReports(); // Refresh the list
      } else {
        toast.error('Failed to resend report: ' + data.message);
      }
    } catch (error) {
      console.error('Error resending report:', error);
      toast.error('Error resending report');
    }
  };

  return (
    <div className="report-generator">
      <div>
        <div className="report-header">
          <h3>
            <span>📊</span>
            Progress Report Generator
          </h3>
          <button className="close-btn" onClick={onClose}>×</button>
        </div>

        {/* Tab Navigation */}
        <div className="tab-navigation">
          <button 
            className={`tab-btn ${activeTab === 'generate' ? 'active' : ''}`}
            onClick={() => setActiveTab('generate')}
          >
            📊 Generate New Report
          </button>
          <button 
            className={`tab-btn ${activeTab === 'history' ? 'active' : ''}`}
            onClick={() => setActiveTab('history')}
          >
            📋 Sent Reports History
          </button>
        </div>

        {activeTab === 'generate' ? (
          <div className="generate-tab">
            {/* Report Configuration */}
            <div className="report-config-professional">
              <div className="config-header">
                <div className="config-icon">📊</div>
                <div className="config-title">
                  <h3>Generate Progress Report</h3>
                  <p>Create comprehensive construction progress reports with detailed analytics and documentation</p>
                </div>
              </div>

              <div className="config-form">
                <div className="form-section">
                  <h4 className="section-title">
                    <span>📋</span>
                    Report Configuration
                  </h4>
                  
                  <div className="form-row">
                    <div className="form-group">
                      <label className="form-label">
                        <span className="label-text">Report Type</span>
                        <span className="label-required">*</span>
                      </label>
                      <div className="select-wrapper">
                        <select 
                          value={reportType} 
                          onChange={(e) => setReportType(e.target.value)}
                          className="form-select"
                        >
                          <option value="daily">📅 Daily Report</option>
                          <option value="weekly">📈 Weekly Summary</option>
                          <option value="monthly">📋 Monthly Report</option>
                        </select>
                      </div>
                      <div className="field-help">
                        {reportType === 'daily' && 'Detailed daily progress with work tracking and photos'}
                        {reportType === 'weekly' && 'Weekly summary with milestone analysis and trends'}
                        {reportType === 'monthly' && 'Comprehensive monthly report with cost analysis'}
                      </div>
                    </div>

                    <div className="form-group">
                      <label className="form-label">
                        <span className="label-text">Project</span>
                        <span className="label-required">*</span>
                      </label>
                      <div className="select-wrapper">
                        <select 
                          value={selectedProject} 
                          onChange={(e) => setSelectedProject(e.target.value)}
                          className="form-select"
                        >
                          <option value="">Choose a project...</option>
                          {projects.map(project => (
                            <option key={project.project_id} value={project.project_id}>
                              {project.project_display_name}
                            </option>
                          ))}
                        </select>
                      </div>
                      <div className="field-help">Select the construction project for this report</div>
                    </div>
                  </div>
                </div>

                <div className="form-section">
                  <h4 className="section-title">
                    <span>📅</span>
                    Report Period
                  </h4>
                  
                  <div className="form-row">
                    <div className="form-group">
                      <label className="form-label">
                        <span className="label-text">Start Date</span>
                        <span className="label-required">*</span>
                      </label>
                      <div className="input-wrapper">
                        <input
                          type="date"
                          value={dateRange.startDate}
                          onChange={(e) => setDateRange(prev => ({ ...prev, startDate: e.target.value }))}
                          className="form-input"
                        />
                      </div>
                    </div>

                    <div className="form-group">
                      <label className="form-label">
                        <span className="label-text">End Date</span>
                        <span className="label-required">*</span>
                      </label>
                      <div className="input-wrapper">
                        <input
                          type="date"
                          value={dateRange.endDate}
                          onChange={(e) => setDateRange(prev => ({ ...prev, endDate: e.target.value }))}
                          className="form-input"
                        />
                      </div>
                    </div>
                  </div>

                  <div className="period-info">
                    <div className="info-card">
                      <div className="info-icon">ℹ️</div>
                      <div className="info-content">
                        <strong>Report Period:</strong> {formatDate(dateRange.startDate)} to {formatDate(dateRange.endDate)}
                        <br />
                        <small>
                          {reportType === 'daily' && 'Daily reports cover a single day of construction activity'}
                          {reportType === 'weekly' && 'Weekly reports summarize 7 days of progress and milestones'}
                          {reportType === 'monthly' && 'Monthly reports provide comprehensive project analysis'}
                        </small>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="form-section">
                  <h4 className="section-title">
                    <span>⚙️</span>
                    Report Options
                  </h4>
                  
                  <div className="options-grid">
                    <div className="option-card">
                      <div className="option-content">
                        <h5>
                          <span>📸</span>
                          Include Photos
                        </h5>
                        <p>Automatically include geo-tagged construction photos</p>
                      </div>
                      <div className="option-toggle">
                        <input type="checkbox" id="includePhotos" defaultChecked />
                        <label htmlFor="includePhotos"></label>
                      </div>
                    </div>

                    <div className="option-card">
                      <div className="option-content">
                        <h5>
                          <span>👷</span>
                          Labour Analysis
                        </h5>
                        <p>Include detailed worker productivity and wage analysis</p>
                      </div>
                      <div className="option-toggle">
                        <input type="checkbox" id="includeLabour" defaultChecked />
                        <label htmlFor="includeLabour"></label>
                      </div>
                    </div>

                    <div className="option-card">
                      <div className="option-content">
                        <h5>
                          <span>💰</span>
                          Cost Breakdown
                        </h5>
                        <p>Include material costs and budget analysis</p>
                      </div>
                      <div className="option-toggle">
                        <input type="checkbox" id="includeCosts" defaultChecked />
                        <label htmlFor="includeCosts"></label>
                      </div>
                    </div>

                    <div className="option-card">
                      <div className="option-content">
                        <h5>
                          <span>🛡️</span>
                          Quality Metrics
                        </h5>
                        <p>Include safety compliance and quality scores</p>
                      </div>
                      <div className="option-toggle">
                        <input type="checkbox" id="includeQuality" defaultChecked />
                        <label htmlFor="includeQuality"></label>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="form-actions">
                  <div className="action-buttons">
                    <button 
                      type="button"
                      className="btn-secondary-outline"
                      onClick={() => setActiveTab('history')}
                    >
                      📋 View History
                    </button>
                    
                    <button 
                      type="button"
                      className="btn-primary-gradient" 
                      onClick={generateReport}
                      disabled={loading || !selectedProject}
                    >
                      {loading ? (
                        <>
                          <div className="btn-spinner"></div>
                          Generating Report...
                        </>
                      ) : (
                        <>
                          <span>📊</span>
                          Generate Report
                        </>
                      )}
                    </button>
                  </div>
                  
                  <div className="action-info">
                    <div className="info-item">
                      <span className="info-label">Estimated time:</span>
                      <span className="info-value">30-60 seconds</span>
                    </div>
                    <div className="info-item">
                      <span className="info-label">Report format:</span>
                      <span className="info-value">PDF & Web View</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Report Content */}
            {reportData && (
              <div className="report-container">
                <div className="report-actions">
                  <button 
                    className="download-btn" 
                    onClick={downloadPDF}
                    disabled={generating}
                  >
                    {generating ? '⏳ Creating PDF...' : '💾 Download PDF'}
                  </button>
                  <button 
                    className="send-btn" 
                    onClick={sendToHomeowner}
                  >
                    📤 Send to Homeowner
                  </button>
                </div>

                <div id="report-content" className="report-content">
                  {/* Report Header */}
                  <div className="report-title-section">
                    <div className="company-header">
                      <div className="company-logo">🏗️</div>
                      <div className="company-info">
                        <h1>BuildHub Construction Report</h1>
                        <p>Professional Construction Progress Tracking</p>
                      </div>
                    </div>
                    
                    <div className="report-meta">
                      <h2>{reportType.charAt(0).toUpperCase() + reportType.slice(1)} Progress Report</h2>
                      <div className="meta-grid">
                        <div><strong>Project:</strong> {reportData?.project?.name || 'Unknown Project'}</div>
                        <div><strong>Contractor:</strong> {reportData?.contractor?.name || 'Unknown Contractor'}</div>
                        <div><strong>Period:</strong> {formatDate(dateRange.startDate)} to {formatDate(dateRange.endDate)}</div>
                        <div><strong>Generated:</strong> {new Date().toLocaleString('en-IN')}</div>
                      </div>
                    </div>
                  </div>

                  {/* Executive Summary */}
                  <div className="report-section">
                    <h3>📋 Executive Summary</h3>
                    <div className="summary-grid">
                      <div className="summary-card">
                        <div className="summary-value">{reportData?.summary?.total_days || 0}</div>
                        <div className="summary-label">Working Days</div>
                      </div>
                      <div className="summary-card">
                        <div className="summary-value">{reportData?.summary?.total_workers || 0}</div>
                        <div className="summary-label">Total Workers</div>
                      </div>
                      <div className="summary-card">
                        <div className="summary-value">{reportData?.summary?.total_hours || 0}h</div>
                        <div className="summary-label">Total Hours</div>
                      </div>
                      <div className="summary-card">
                        <div className="summary-value">{reportData?.summary?.progress_percentage || 0}%</div>
                        <div className="summary-label">Progress Made</div>
                      </div>
                      <div className="summary-card">
                        <div className="summary-value">{formatCurrency(reportData?.summary?.total_wages || 0)}</div>
                        <div className="summary-label">Total Wages</div>
                      </div>
                      <div className="summary-card">
                        <div className="summary-value">{reportData?.summary?.photos_count || 0}</div>
                        <div className="summary-label">Photos Taken</div>
                      </div>
                    </div>
                  </div>

                  {/* Work Progress Details */}
                  <div className="report-section">
                    <h3>🏗️ Work Progress Details</h3>
                    {(reportData?.daily_updates || []).map((update, index) => (
                      <div key={index} className="progress-item">
                        <div className="progress-header">
                          <h4>{formatDate(update.update_date)}</h4>
                          <span className="progress-badge">{update.construction_stage}</span>
                        </div>
                        <div className="progress-details">
                          <p><strong>Work Done:</strong> {update.work_done_today}</p>
                          <div className="progress-meta">
                            <span>Progress: +{update.incremental_completion_percentage}%</span>
                            <span>Hours: {update.working_hours}h</span>
                            <span>Weather: {update.weather_condition}</span>
                          </div>
                          {update.site_issues && (
                            <p><strong>Issues:</strong> {update.site_issues}</p>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>

                  {/* Labour Analysis */}
                  <div className="report-section">
                    <h3>👷 Labour Analysis</h3>
                    <div className="labour-summary">
                      <table className="labour-table">
                        <thead>
                          <tr>
                            <th>Worker Type</th>
                            <th>Total Workers</th>
                            <th>Total Hours</th>
                            <th>Overtime Hours</th>
                            <th>Total Wages</th>
                            <th>Avg Productivity</th>
                          </tr>
                        </thead>
                        <tbody>
                          {Object.entries(reportData?.labour_analysis || {}).map(([workerType, data]) => (
                            <tr key={workerType}>
                              <td>{workerType}</td>
                              <td>{data?.total_workers || 0}</td>
                              <td>{data?.total_hours || 0}h</td>
                              <td>{data?.overtime_hours || 0}h</td>
                              <td>{formatCurrency(data?.total_wages || 0)}</td>
                              <td>{data?.avg_productivity || 0}/5</td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>

                  {/* Materials & Costs */}
                  <div className="report-section">
                    <h3>📦 Materials & Costs Summary</h3>
                    <div className="materials-grid">
                      <div className="cost-breakdown">
                        <h4>Cost Breakdown</h4>
                        <div className="cost-item">
                          <span>Labour Costs:</span>
                          <span>{formatCurrency(reportData?.costs?.labour_cost || 0)}</span>
                        </div>
                        <div className="cost-item">
                          <span>Material Costs:</span>
                          <span>{formatCurrency(reportData?.costs?.material_cost || 0)}</span>
                        </div>
                        <div className="cost-item">
                          <span>Equipment Costs:</span>
                          <span>{formatCurrency(reportData?.costs?.equipment_cost || 0)}</span>
                        </div>
                        <div className="cost-item total">
                          <span>Total Period Cost:</span>
                          <span>{formatCurrency(reportData?.costs?.total_cost || 0)}</span>
                        </div>
                      </div>

                      <div className="materials-used">
                        <h4>Materials Used</h4>
                        {(reportData?.materials || []).map((material, index) => (
                          <div key={index} className="material-item">
                            <span>{material.name}</span>
                            <span>{material.quantity} {material.unit}</span>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>

                  {/* Photos & Documentation */}
                  <div className="report-section">
                    <h3>📸 Photos & Documentation</h3>
                    <div className="photos-summary">
                      <p>Total Photos Captured: <strong>{reportData?.summary?.photos_count || 0}</strong></p>
                      <p>Geo-Located Photos: <strong>{reportData?.summary?.geo_photos_count || 0}</strong></p>
                      <div className="photo-grid">
                        {(reportData?.photos || []).slice(0, 6).map((photo, index) => (
                          <div key={index} className="photo-item">
                            <img src={photo.url} alt={`Progress ${index + 1}`} />
                            <div className="photo-info">
                              <small>{formatDate(photo.date)}</small>
                              <small>{photo.location}</small>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>

                  {/* Quality & Safety */}
                  <div className="report-section">
                    <h3>🛡️ Quality & Safety Metrics</h3>
                    <div className="metrics-grid">
                      <div className="metric-card">
                        <h4>Safety Compliance</h4>
                        <div className="metric-value">{reportData?.quality?.safety_score || 0}/5</div>
                        <div className="metric-status">
                          {(reportData?.quality?.safety_score || 0) >= 4 ? '✅ Excellent' : 
                           (reportData?.quality?.safety_score || 0) >= 3 ? '⚠️ Good' : '❌ Needs Improvement'}
                        </div>
                      </div>
                      <div className="metric-card">
                        <h4>Work Quality</h4>
                        <div className="metric-value">{reportData?.quality?.quality_score || 0}/5</div>
                        <div className="metric-status">
                          {(reportData?.quality?.quality_score || 0) >= 4 ? '✅ High Quality' : 
                           (reportData?.quality?.quality_score || 0) >= 3 ? '⚠️ Acceptable' : '❌ Below Standard'}
                        </div>
                      </div>
                      <div className="metric-card">
                        <h4>Schedule Adherence</h4>
                        <div className="metric-value">{reportData?.quality?.schedule_adherence || 0}%</div>
                        <div className="metric-status">
                          {(reportData?.quality?.schedule_adherence || 0) >= 90 ? '✅ On Track' : 
                           (reportData?.quality?.schedule_adherence || 0) >= 70 ? '⚠️ Minor Delays' : '❌ Behind Schedule'}
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Recommendations & Next Steps */}
                  <div className="report-section">
                    <h3>💡 Recommendations & Next Steps</h3>
                    <div className="recommendations">
                      {(reportData?.recommendations || []).map((rec, index) => (
                        <div key={index} className="recommendation-item">
                          <div className="rec-priority">{rec.priority}</div>
                          <div className="rec-content">
                            <h5>{rec.title}</h5>
                            <p>{rec.description}</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Report Footer */}
                  <div className="report-footer">
                    <div className="signatures">
                      <div className="signature-block">
                        <div className="signature-line"></div>
                        <p>Contractor Signature</p>
                        <p>{reportData?.contractor?.name || 'Unknown Contractor'}</p>
                        <p>Date: {formatDate(new Date().toISOString().split('T')[0])}</p>
                      </div>
                      <div className="signature-block">
                        <div className="signature-line"></div>
                        <p>Homeowner Acknowledgment</p>
                        <p>Date: _______________</p>
                      </div>
                    </div>
                    <div className="report-disclaimer">
                      <p>This report is generated automatically from BuildHub construction tracking system.</p>
                      <p>All data is recorded in real-time and verified through GPS-enabled photo documentation.</p>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
      ) : (
        <div className="history-tab">
          {/* Debug Information */}
          <div style={{ 
            background: '#f8f9fa', 
            padding: '16px', 
            margin: '16px 24px', 
            borderRadius: '8px',
            fontSize: '14px',
            fontFamily: 'monospace'
          }}>
            <strong>Debug Info:</strong><br/>
            Reports loaded: {sentReports.length}<br/>
            Daily reports: {groupedReports.daily?.length || 0}<br/>
            Weekly reports: {groupedReports.weekly?.length || 0}<br/>
            Monthly reports: {groupedReports.monthly?.length || 0}<br/>
            Loading: {historyLoading ? 'Yes' : 'No'}<br/>
            Stats: {JSON.stringify(reportStats)}
          </div>

          {/* History Filters */}
          <div className="history-filters">
            <div className="filter-row">
              <div className="form-group">
                <label>Filter by Project</label>
                <select 
                  value={selectedHistoryProject} 
                  onChange={(e) => setSelectedHistoryProject(e.target.value)}
                >
                  <option value="">All Projects</option>
                  {projects.map(project => (
                    <option key={project.project_id} value={project.project_id}>
                      {project.project_display_name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="form-group">
                <label>Filter by Type</label>
                <select 
                  value={historyReportType} 
                  onChange={(e) => setHistoryReportType(e.target.value)}
                >
                  <option value="">All Types</option>
                  <option value="daily">Daily Reports</option>
                  <option value="weekly">Weekly Reports</option>
                  <option value="monthly">Monthly Reports</option>
                </select>
              </div>

              <div className="form-group">
                <button 
                  className="filter-btn"
                  onClick={loadSentReports}
                  disabled={historyLoading}
                >
                  {historyLoading ? '⏳ Loading...' : '🔍 Apply Filters'}
                </button>
              </div>
            </div>
          </div>

          {/* Statistics Overview */}
          {reportStats && Object.keys(reportStats).length > 0 && (
            <div className="stats-overview">
              <h3>📊 Reports Overview</h3>
              <div className="stats-grid">
                <div className="stat-card">
                  <div className="stat-value">{reportStats.total_reports}</div>
                  <div className="stat-label">Total Reports</div>
                </div>
                <div className="stat-card">
                  <div className="stat-value">{reportStats.sent_count}</div>
                  <div className="stat-label">Sent</div>
                </div>
                <div className="stat-card">
                  <div className="stat-value">{reportStats.viewed_count}</div>
                  <div className="stat-label">Viewed</div>
                </div>
                <div className="stat-card">
                  <div className="stat-value">{reportStats.acknowledged_count}</div>
                  <div className="stat-label">Acknowledged</div>
                </div>
              </div>
            </div>
          )}

          {/* Report Sections */}
          <div className="report-sections">
            {/* Daily Reports Section */}
            <div className="report-section-container">
              <div className="section-header">
                <h3>📅 Daily Reports ({groupedReports.daily?.length || 0})</h3>
                <p>Detailed daily progress updates with work done, labour tracking, and photos</p>
              </div>
              <div className="reports-grid">
                {groupedReports.daily?.length > 0 ? (
                  groupedReports.daily.map(report => renderReportCard(report))
                ) : (
                  <div className="no-reports">
                    <div className="no-reports-icon">📋</div>
                    <p>No daily reports found</p>
                  </div>
                )}
              </div>
            </div>

            {/* Weekly Reports Section */}
            <div className="report-section-container">
              <div className="section-header">
                <h3>📈 Weekly Reports ({groupedReports.weekly?.length || 0})</h3>
                <p>Weekly summaries with progress analysis and milestone tracking</p>
              </div>
              <div className="reports-grid">
                {groupedReports.weekly?.length > 0 ? (
                  groupedReports.weekly.map(report => renderReportCard(report))
                ) : (
                  <div className="no-reports">
                    <div className="no-reports-icon">📊</div>
                    <p>No weekly reports found</p>
                  </div>
                )}
              </div>
            </div>

            {/* Monthly Reports Section */}
            <div className="report-section-container">
              <div className="section-header">
                <h3>📋 Monthly Reports ({groupedReports.monthly?.length || 0})</h3>
                <p>Comprehensive monthly reports with cost analysis and forecasting</p>
              </div>
              <div className="reports-grid">
                {groupedReports.monthly?.length > 0 ? (
                  groupedReports.monthly.map(report => renderReportCard(report))
                ) : (
                  <div className="no-reports">
                    <div className="no-reports-icon">📈</div>
                    <p>No monthly reports found</p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}
      </div>
    </div>
  );
};

export default ProgressReportGenerator;