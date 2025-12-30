import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';
import '../styles/ProgressReportGenerator.css';

const ProgressReportGenerator = ({ contractorId, onClose }) => {
  const toast = useToast();
  
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

  useEffect(() => {
    loadProjects();
    setDefaultDateRange();
  }, [reportType]);

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

      const fileName = `${reportType}_report_${reportData.project.name}_${dateRange.startDate}_to_${dateRange.endDate}.pdf`;
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

  return (
    <div className="report-generator">
      <div className="report-header">
        <h3>📊 Progress Report Generator</h3>
        <button className="close-btn" onClick={onClose}>×</button>
      </div>

      {/* Report Configuration */}
      <div className="report-config">
        <div className="config-row">
          <div className="form-group">
            <label>Report Type</label>
            <select value={reportType} onChange={(e) => setReportType(e.target.value)}>
              <option value="daily">Daily Report</option>
              <option value="weekly">Weekly Summary</option>
              <option value="monthly">Monthly Report</option>
            </select>
          </div>

          <div className="form-group">
            <label>Project</label>
            <select value={selectedProject} onChange={(e) => setSelectedProject(e.target.value)}>
              <option value="">Select Project...</option>
              {projects.map(project => (
                <option key={project.project_id} value={project.project_id}>
                  {project.project_display_name}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div className="config-row">
          <div className="form-group">
            <label>Start Date</label>
            <input
              type="date"
              value={dateRange.startDate}
              onChange={(e) => setDateRange(prev => ({ ...prev, startDate: e.target.value }))}
            />
          </div>

          <div className="form-group">
            <label>End Date</label>
            <input
              type="date"
              value={dateRange.endDate}
              onChange={(e) => setDateRange(prev => ({ ...prev, endDate: e.target.value }))}
            />
          </div>
        </div>

        <div className="config-actions">
          <button 
            className="generate-btn" 
            onClick={generateReport}
            disabled={loading}
          >
            {loading ? '⏳ Generating...' : '📊 Generate Report'}
          </button>
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
                  <div><strong>Project:</strong> {reportData.project.name}</div>
                  <div><strong>Contractor:</strong> {reportData.contractor.name}</div>
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
                  <div className="summary-value">{reportData.summary.total_days}</div>
                  <div className="summary-label">Working Days</div>
                </div>
                <div className="summary-card">
                  <div className="summary-value">{reportData.summary.total_workers}</div>
                  <div className="summary-label">Total Workers</div>
                </div>
                <div className="summary-card">
                  <div className="summary-value">{reportData.summary.total_hours}h</div>
                  <div className="summary-label">Total Hours</div>
                </div>
                <div className="summary-card">
                  <div className="summary-value">{reportData.summary.progress_percentage}%</div>
                  <div className="summary-label">Progress Made</div>
                </div>
                <div className="summary-card">
                  <div className="summary-value">{formatCurrency(reportData.summary.total_wages)}</div>
                  <div className="summary-label">Total Wages</div>
                </div>
                <div className="summary-card">
                  <div className="summary-value">{reportData.summary.photos_count}</div>
                  <div className="summary-label">Photos Taken</div>
                </div>
              </div>
            </div>

            {/* Work Progress Details */}
            <div className="report-section">
              <h3>🏗️ Work Progress Details</h3>
              {reportData.daily_updates.map((update, index) => (
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
                    {update.materials_used && (
                      <p><strong>Materials Used:</strong> {update.materials_used}</p>
                    )}
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
                    {Object.entries(reportData.labour_analysis).map(([workerType, data]) => (
                      <tr key={workerType}>
                        <td>{workerType}</td>
                        <td>{data.total_workers}</td>
                        <td>{data.total_hours}h</td>
                        <td>{data.overtime_hours}h</td>
                        <td>{formatCurrency(data.total_wages)}</td>
                        <td>{data.avg_productivity}/5</td>
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
                    <span>{formatCurrency(reportData.costs.labour_cost)}</span>
                  </div>
                  <div className="cost-item">
                    <span>Material Costs:</span>
                    <span>{formatCurrency(reportData.costs.material_cost)}</span>
                  </div>
                  <div className="cost-item">
                    <span>Equipment Costs:</span>
                    <span>{formatCurrency(reportData.costs.equipment_cost)}</span>
                  </div>
                  <div className="cost-item total">
                    <span>Total Period Cost:</span>
                    <span>{formatCurrency(reportData.costs.total_cost)}</span>
                  </div>
                </div>

                <div className="materials-used">
                  <h4>Materials Used</h4>
                  {reportData.materials.map((material, index) => (
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
                <p>Total Photos Captured: <strong>{reportData.summary.photos_count}</strong></p>
                <p>Geo-Located Photos: <strong>{reportData.summary.geo_photos_count}</strong></p>
                <div className="photo-grid">
                  {reportData.photos.slice(0, 6).map((photo, index) => (
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
                  <div className="metric-value">{reportData.quality.safety_score}/5</div>
                  <div className="metric-status">
                    {reportData.quality.safety_score >= 4 ? '✅ Excellent' : 
                     reportData.quality.safety_score >= 3 ? '⚠️ Good' : '❌ Needs Improvement'}
                  </div>
                </div>
                <div className="metric-card">
                  <h4>Work Quality</h4>
                  <div className="metric-value">{reportData.quality.quality_score}/5</div>
                  <div className="metric-status">
                    {reportData.quality.quality_score >= 4 ? '✅ High Quality' : 
                     reportData.quality.quality_score >= 3 ? '⚠️ Acceptable' : '❌ Below Standard'}
                  </div>
                </div>
                <div className="metric-card">
                  <h4>Schedule Adherence</h4>
                  <div className="metric-value">{reportData.quality.schedule_adherence}%</div>
                  <div className="metric-status">
                    {reportData.quality.schedule_adherence >= 90 ? '✅ On Track' : 
                     reportData.quality.schedule_adherence >= 70 ? '⚠️ Minor Delays' : '❌ Behind Schedule'}
                  </div>
                </div>
              </div>
            </div>

            {/* Recommendations & Next Steps */}
            <div className="report-section">
              <h3>💡 Recommendations & Next Steps</h3>
              <div className="recommendations">
                {reportData.recommendations.map((rec, index) => (
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
                  <p>{reportData.contractor.name}</p>
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
  );
};

export default ProgressReportGenerator;