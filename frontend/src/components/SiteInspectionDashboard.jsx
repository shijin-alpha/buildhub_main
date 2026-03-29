import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import '../styles/SiteInspectionDashboard.css';
import '../styles/EnhancedInspectionForm.css';

// Lightweight icon component
const Icon = ({ name, size = 20, stroke = 1.8, color = 'currentColor' }) => {
  const common = { width: size, height: size, viewBox: '0 0 24 24', fill: 'none', stroke: color, strokeWidth: stroke, strokeLinecap: 'round', strokeLinejoin: 'round' };
  switch (name) {
    case 'project':
      return (<svg {...common}><rect x="3" y="3" width="18" height="14" rx="2"/><path d="M7 21h10"/><path d="M12 17v4"/></svg>);
    case 'eye':
      return (<svg {...common}><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>);
    case 'calendar':
      return (<svg {...common}><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>);
    case 'map-pin':
      return (<svg {...common}><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>);
    case 'user':
      return (<svg {...common}><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>);
    case 'arrow-left':
      return (<svg {...common}><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12,19 5,12 12,5"/></svg>);
    case 'check-circle':
      return (<svg {...common}><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/></svg>);
    case 'alert-circle':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>);
    case 'clock':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><polyline points="12,6 12,12 16,14"/></svg>);
    case 'file-text':
      return (<svg {...common}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>);
    case 'camera':
      return (<svg {...common}><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>);
    case 'plus':
      return (<svg {...common}><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>);
    case 'x':
      return (<svg {...common}><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>);
    case 'cloud':
      return (<svg {...common}><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>);
    default:
      return null;
  }
};

const SiteInspectionDashboard = () => {
  const navigate = useNavigate();
  const [currentView, setCurrentView] = useState('project-list'); // 'project-list', 'project-detail', or 'all-reports'
  const [mainTab, setMainTab] = useState('projects'); // 'projects' or 'all-reports'
  const [selectedProject, setSelectedProject] = useState(null);
  const [assignedProjects, setAssignedProjects] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [inspectionReports, setInspectionReports] = useState([]);
  const [projectStats, setProjectStats] = useState({});
  const [projectProgress, setProjectProgress] = useState(null);
  const [progressLoading, setProgressLoading] = useState(false);

  // Admin inspection reports state
  const [allInspectionReports, setAllInspectionReports] = useState([]);
  const [inspectionStats, setInspectionStats] = useState({});
  const [inspectionLoading, setInspectionLoading] = useState(false);

  useEffect(() => {
    fetchAssignedProjects();
  }, []);

  const fetchAssignedProjects = async () => {
    try {
      setLoading(true);
      const response = await fetch('/buildhub/backend/api/inspector/get_all_real_projects.php', {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setAssignedProjects(result.projects || []);
        setProjectStats(result.statistics || {});
      } else {
        setError(result.message || 'Failed to fetch assigned projects');
      }
    } catch (error) {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const fetchProjectDetails = async (projectId) => {
    try {
      setLoading(true);
      const response = await fetch(`/buildhub/backend/api/inspector/get_project_details.php?project_id=${projectId}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setSelectedProject(result.project);
        setInspectionReports(result.inspection_reports || []);
        
        // Fetch detailed progress data
        await fetchProjectProgress(projectId);
        
        setCurrentView('project-detail');
      } else {
        setError(result.message || 'Failed to fetch project details');
      }
    } catch (error) {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  // Fetch all inspection reports for admin
  const fetchAllInspectionReports = async () => {
    try {
      setInspectionLoading(true);
      const response = await fetch('/buildhub/backend/api/admin/get_inspection_reports.php', {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setAllInspectionReports(result.reports || []);
        setInspectionStats(result.statistics || {});
      } else {
        setError(result.message || 'Failed to fetch inspection reports');
      }
    } catch (error) {
      setError('Network error. Please try again.');
    } finally {
      setInspectionLoading(false);
    }
  };

  const fetchProjectProgress = async (projectId) => {
    try {
      setProgressLoading(true);
      const response = await fetch(`/buildhub/backend/api/inspector/get_project_progress_details.php?project_id=${projectId}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setProjectProgress(result);
      } else {
        console.error('Failed to fetch project progress:', result.message);
      }
    } catch (error) {
      console.error('Error fetching project progress:', error);
    } finally {
      setProgressLoading(false);
    }
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'completed':
        return <Icon name="check-circle" color="#10b981" />;
      case 'in_progress':
        return <Icon name="clock" color="#f59e0b" />;
      case 'on_hold':
        return <Icon name="alert-circle" color="#ef4444" />;
      default:
        return <Icon name="clock" color="#6b7280" />;
    }
  };

  const getInspectionStatusBadge = (status) => {
    const statusConfig = {
      approved: { color: '#10b981', bg: '#d1fae5', text: 'Approved' },
      rejected: { color: '#ef4444', bg: '#fee2e2', text: 'Rejected' },
      needs_attention: { color: '#f59e0b', bg: '#fef3c7', text: 'Needs Attention' },
      pending: { color: '#6b7280', bg: '#f3f4f6', text: 'Pending' }
    };
    
    const config = statusConfig[status] || statusConfig.pending;
    
    return (
      <span 
        className="inspection-status-badge"
        style={{ 
          backgroundColor: config.bg, 
          color: config.color,
          padding: '4px 8px',
          borderRadius: '12px',
          fontSize: '12px',
          fontWeight: '500'
        }}
      >
        {config.text}
      </span>
    );
  };

  const handleProjectSelect = (project) => {
    fetchProjectDetails(project.id);
  };

  const handleBackToList = () => {
    setCurrentView('project-list');
    setSelectedProject(null);
    setInspectionReports([]);
    setProjectProgress(null);
  };

  if (loading) {
    return (
      <div className="site-inspection-dashboard">
        <div className="loading-container">
          <div className="loading-spinner"></div>
          <p>Loading inspection dashboard...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="site-inspection-dashboard">
      {/* Main Tab Navigation */}
      <div className="dashboard-header">
        <h1>Site Inspection Dashboard</h1>
        <p>Manage site inspections and view comprehensive reports</p>
      </div>
      
      <div className="main-tabs">
        <button 
          className={`main-tab-button ${mainTab === 'projects' ? 'active' : ''}`}
          onClick={() => {
            setMainTab('projects');
            setCurrentView('project-list');
          }}
        >
          <Icon name="project" size={18} />
          Projects & Inspections
        </button>
        <button 
          className={`main-tab-button ${mainTab === 'all-reports' ? 'active' : ''}`}
          onClick={() => {
            setMainTab('all-reports');
            setCurrentView('all-reports');
            fetchAllInspectionReports();
          }}
        >
          <Icon name="eye" size={18} />
          All Inspection Reports
        </button>
      </div>

      {/* Tab Content */}
      <div className="main-tab-content">
        {mainTab === 'projects' ? (
          currentView === 'project-list' ? (
            <ProjectListView 
              projects={assignedProjects}
              stats={projectStats}
              onProjectSelect={handleProjectSelect}
              error={error}
            />
          ) : (
            <ProjectDetailView 
              project={selectedProject}
              inspectionReports={inspectionReports}
              projectProgress={projectProgress}
              progressLoading={progressLoading}
              onBackToList={handleBackToList}
              getStatusIcon={getStatusIcon}
              getInspectionStatusBadge={getInspectionStatusBadge}
            />
          )
        ) : (
          <AllReportsView 
            reports={allInspectionReports}
            stats={inspectionStats}
            loading={inspectionLoading}
            getInspectionStatusBadge={getInspectionStatusBadge}
          />
        )}
      </div>
    </div>
  );
};

// Project List View Component
const ProjectListView = ({ projects, stats, onProjectSelect, error }) => {
  return (
    <>
      {error && (
        <div className="error-message">
          <Icon name="alert-circle" color="#ef4444" />
          <span>{error}</span>
        </div>
      )}

      {/* Statistics Cards */}
      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-icon">
            <Icon name="project" color="#3b82f6" />
          </div>
          <div className="stat-content">
            <h3>{stats.total_assigned || 0}</h3>
            <p>Assigned Projects</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon">
            <Icon name="clock" color="#f59e0b" />
          </div>
          <div className="stat-content">
            <h3>{stats.active_projects || 0}</h3>
            <p>Active Projects</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon">
            <Icon name="check-circle" color="#10b981" />
          </div>
          <div className="stat-content">
            <h3>{stats.completed_projects || 0}</h3>
            <p>Completed Projects</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon">
            <Icon name="alert-circle" color="#ef4444" />
          </div>
          <div className="stat-content">
            <h3>{stats.pending_inspections_total || 0}</h3>
            <p>Pending Inspections</p>
          </div>
        </div>
      </div>

      {/* Projects List */}
      <div className="projects-section">
        <h2>Assigned Projects</h2>
        {projects.length === 0 ? (
          <div className="empty-state">
            <Icon name="project" size={48} color="#9ca3af" />
            <h3>No Projects Assigned</h3>
            <p>You don't have any projects assigned for inspection yet.</p>
          </div>
        ) : (
          <div className="projects-grid">
            {projects.map((project) => (
              <ProjectCard 
                key={project.id} 
                project={project} 
                onSelect={() => onProjectSelect(project)}
              />
            ))}
          </div>
        )}
      </div>
    </>
  );
};

// Project Card Component
const ProjectCard = ({ project, onSelect }) => {
  const getStatusColor = (status) => {
    switch (status) {
      case 'completed': return '#10b981';
      case 'in_progress': return '#f59e0b';
      case 'on_hold': return '#ef4444';
      default: return '#6b7280';
    }
  };

  return (
    <div className="project-card" onClick={onSelect}>
      <div className="project-card-header">
        <h3>{project.project_name}</h3>
        <span 
          className="project-status"
          style={{ 
            backgroundColor: getStatusColor(project.status),
            color: 'white',
            padding: '4px 8px',
            borderRadius: '12px',
            fontSize: '12px',
            fontWeight: '500'
          }}
        >
          {project.status?.replace('_', ' ').toUpperCase()}
        </span>
      </div>
      
      <div className="project-card-content">
        <div className="project-info">
          <div className="info-item">
            <Icon name="map-pin" size={16} color="#6b7280" />
            <span>{project.project_location || 'Location not specified'}</span>
          </div>
          <div className="info-item">
            <Icon name="user" size={16} color="#6b7280" />
            <span>{project.homeowner.name}</span>
          </div>
          <div className="info-item">
            <Icon name="calendar" size={16} color="#6b7280" />
            <span>Stage: {project.actual_current_stage}</span>
          </div>
        </div>
        
        <div className="project-progress">
          <div className="progress-bar">
            <div 
              className="progress-fill"
              style={{ width: `${project.real_completion_percentage || 0}%` }}
            ></div>
          </div>
          <span className="progress-text">{project.real_completion_percentage || 0}% Complete</span>
        </div>
        
        <div className="inspection-summary">
          <div className="inspection-stat">
            <span className="stat-number">{project.total_inspections || 0}</span>
            <span className="stat-label">Total Inspections</span>
          </div>
          <div className="inspection-stat">
            <span className="stat-number">{project.pending_inspections || 0}</span>
            <span className="stat-label">Pending</span>
          </div>
          <div className="inspection-stat">
            <span className="stat-number">
              {project.last_inspection_date ? 
                new Date(project.last_inspection_date).toLocaleDateString() : 
                'Never'
              }
            </span>
            <span className="stat-label">Last Inspection</span>
          </div>
        </div>
      </div>
      
      <div className="project-card-footer">
        <button className="view-project-btn">
          <Icon name="eye" size={16} />
          View Project Details
        </button>
      </div>
    </div>
  );
};

// Project Detail View Component
const ProjectDetailView = ({ project, inspectionReports, projectProgress, progressLoading, onBackToList, getStatusIcon, getInspectionStatusBadge }) => {
  const [activeTab, setActiveTab] = useState('overview');

  if (!project) {
    return (
      <div className="project-detail-loading">
        <div className="loading-spinner"></div>
        <p>Loading project details...</p>
      </div>
    );
  }

  return (
    <>
      <div className="project-detail-header">
        <button className="back-button" onClick={onBackToList}>
          <Icon name="arrow-left" size={20} />
          Back to Projects
        </button>
        <div className="project-title-section">
          <h1>{project.project_name}</h1>
          <div className="project-status-info">
            {getStatusIcon(project.status)}
            <span className="status-text">{project.status?.replace('_', ' ').toUpperCase()}</span>
            <span className="stage-text">Current Stage: {project.actual_current_stage}</span>
          </div>
        </div>
      </div>

      <div className="project-detail-tabs">
        <button 
          className={`tab-button ${activeTab === 'overview' ? 'active' : ''}`}
          onClick={() => setActiveTab('overview')}
        >
          Project Overview
        </button>
        <button 
          className={`tab-button ${activeTab === 'progress' ? 'active' : ''}`}
          onClick={() => setActiveTab('progress')}
        >
          Daily Progress
        </button>
        <button 
          className={`tab-button ${activeTab === 'inspections' ? 'active' : ''}`}
          onClick={() => setActiveTab('inspections')}
        >
          Inspection Reports
        </button>
        <button 
          className={`tab-button ${activeTab === 'new-inspection' ? 'active' : ''}`}
          onClick={() => setActiveTab('new-inspection')}
        >
          New Inspection
        </button>
      </div>

      <div className="project-detail-content">
        {activeTab === 'overview' && (
          <ProjectOverviewTab project={project} />
        )}
        {activeTab === 'progress' && (
          <ProjectProgressTab 
            projectProgress={projectProgress} 
            progressLoading={progressLoading}
          />
        )}
        {activeTab === 'inspections' && (
          <InspectionReportsTab 
            reports={inspectionReports} 
            getInspectionStatusBadge={getInspectionStatusBadge}
          />
        )}
        {activeTab === 'new-inspection' && (
          <NewInspectionTab project={project} />
        )}
      </div>
    </>
  );
};

// Helper function to render info item only if data exists
const renderInfoItem = (label, value, formatter = null) => {
  // Check if value is provided and not empty
  if (value === null || value === undefined) return null;
  if (typeof value === 'string' && value.trim() === '') return null;
  if (typeof value === 'number' && isNaN(value)) return null;
  
  const displayValue = formatter ? formatter(value) : value;
  
  return (
    <div className="info-item">
      <label>{label}:</label>
      <span>{displayValue}</span>
    </div>
  );
};

// Helper function to calculate correct budget from multiple sources
const calculateCorrectBudget = (project) => {
  let totalBudget = 0;
  let budgetSource = 'Not specified';
  
  // Priority 1: Check structured_data from construction_projects
  if (project.structured_data) {
    try {
      const structuredData = typeof project.structured_data === 'string' 
        ? JSON.parse(project.structured_data) 
        : project.structured_data;
      
      if (structuredData.totals?.grand && parseFloat(structuredData.totals.grand) > 0) {
        totalBudget = parseFloat(structuredData.totals.grand);
        budgetSource = 'Project Estimate (Structured Data)';
      }
    } catch (e) {
      console.warn('Error parsing structured_data:', e);
    }
  }
  
  // Priority 2: Check total_cost from construction_projects
  if (!totalBudget && project.total_cost && parseFloat(project.total_cost) > 0) {
    totalBudget = parseFloat(project.total_cost);
    budgetSource = 'Project Total Cost';
  }
  
  // Priority 3: Check financial_summary.total_cost
  if (!totalBudget && project.financial_summary?.total_cost && parseFloat(project.financial_summary.total_cost) > 0) {
    totalBudget = parseFloat(project.financial_summary.total_cost);
    budgetSource = 'Financial Summary';
  }
  
  // Priority 4: Sum up stage payment requests total_project_cost
  if (!totalBudget && project.stage_payments && project.stage_payments.length > 0) {
    // Check if any stage payment has total_project_cost
    const stageWithTotal = project.stage_payments.find(payment => 
      payment.total_project_cost && parseFloat(payment.total_project_cost) > 0
    );
    if (stageWithTotal) {
      totalBudget = parseFloat(stageWithTotal.total_project_cost);
      budgetSource = 'Stage Payment Total Project Cost';
    }
  }
  
  return {
    amount: totalBudget,
    source: budgetSource,
    formatted: totalBudget > 0 ? `₹${totalBudget.toLocaleString()}` : null
  };
};

// Project Overview Tab
const ProjectOverviewTab = ({ project }) => {
  return (
    <div className="project-overview">
      <div className="overview-grid">
        <div className="overview-card">
          <h3>Project Information</h3>
          <div className="info-grid">
            {renderInfoItem('Project Name', project.project_name)}
            {renderInfoItem('Description', project.project_description)}
            {renderInfoItem('Location', project.project_location)}
            {renderInfoItem('Plot Size', project.plot_size)}
            {renderInfoItem('Budget Range', project.budget_range)}
            {renderInfoItem('Preferred Style', project.preferred_style)}
            {renderInfoItem('Start Date', project.dates?.start_date, (date) => new Date(date).toLocaleDateString())}
            {renderInfoItem('Expected Completion', project.dates?.expected_completion, (date) => new Date(date).toLocaleDateString())}
            {renderInfoItem('Timeline', project.timeline)}
            {renderInfoItem('Progress', project.real_completion_percentage, (val) => `${val}%`)}
            {renderInfoItem('Current Stage', project.actual_current_stage)}
            <div className="info-item">
              <label>Status:</label>
              <span className={`status-badge status-${project.status}`}>
                {project.status?.replace('_', ' ').toUpperCase() || 'Unknown'}
              </span>
            </div>
          </div>
        </div>

        <div className="overview-card">
          <h3>Homeowner Details</h3>
          <div className="info-grid">
            {renderInfoItem('Name', project.homeowner?.name)}
            {renderInfoItem('Email', project.homeowner?.email)}
            {renderInfoItem('Phone', project.homeowner?.phone)}
            {renderInfoItem('Address', project.homeowner?.address)}
            {renderInfoItem('City', project.homeowner?.city)}
            {renderInfoItem('State', project.homeowner?.state)}
          </div>
        </div>

        {(project.contractor?.name || project.contractor?.email || project.contractor?.phone || project.contractor?.company) && (
          <div className="overview-card">
            <h3>Contractor Details</h3>
            <div className="info-grid">
              {renderInfoItem('Name', project.contractor?.name)}
              {renderInfoItem('Email', project.contractor?.email)}
              {renderInfoItem('Phone', project.contractor?.phone)}
              {renderInfoItem('Company', project.contractor?.company)}
              {renderInfoItem('License', project.contractor?.license)}
              {renderInfoItem('Experience', project.contractor?.experience, (exp) => `${exp} years`)}
              {renderInfoItem('Specialization', project.contractor?.specialization)}
            </div>
          </div>
        )}

        <div className="overview-card">
          <h3>Financial Summary</h3>
          <div className="info-grid">
            {(() => {
              const budgetInfo = calculateCorrectBudget(project);
              return budgetInfo.amount > 0 ? (
                <div className="info-item">
                  <label>Total Project Budget:</label>
                  <span className="budget-amount">
                    {budgetInfo.formatted}
                    <small className="budget-source"> ({budgetInfo.source})</small>
                  </span>
                </div>
              ) : null;
            })()}
            {renderInfoItem('Total Requested', project.financial_summary?.total_requested, (amount) => `₹${Number(amount).toLocaleString()}`)}
            {renderInfoItem('Amount Paid', project.financial_summary?.paid_amount, (amount) => `₹${Number(amount).toLocaleString()}`)}
            {renderInfoItem('Pending Amount', project.financial_summary?.pending_amount, (amount) => `₹${Number(amount).toLocaleString()}`)}
            {renderInfoItem('Payment Completion', project.financial_summary?.payment_completion_rate, (rate) => `${rate}%`)}
            {renderInfoItem('Budget Utilization', project.financial_summary?.budget_utilization, (rate) => `${rate}%`)}
            {project.financial_summary?.payment_sources && (
              <div className="info-item full-width">
                <label>Payment Sources:</label>
                <div className="payment-sources">
                  {project.financial_summary.payment_sources.stage_payments > 0 && (
                    <span className="source-badge">Stage Payments: {project.financial_summary.payment_sources.stage_payments}</span>
                  )}
                  {project.financial_summary.payment_sources.alternative_payments > 0 && (
                    <span className="source-badge">Alternative Payments: {project.financial_summary.payment_sources.alternative_payments}</span>
                  )}
                  {project.financial_summary.payment_sources.custom_payments > 0 && (
                    <span className="source-badge">Custom Payments: {project.financial_summary.payment_sources.custom_payments}</span>
                  )}
                  {project.financial_summary.payment_sources.split_payments > 0 && (
                    <span className="source-badge">Split Payments: {project.financial_summary.payment_sources.split_payments}</span>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>

        <div className="overview-card">
          <h3>Progress Summary</h3>
          <div className="info-grid">
            {renderInfoItem('Real Completion', project.progress_summary?.real_completion, (val) => `${val}%`)}
            {renderInfoItem('Completed Stages', project.progress_summary?.completed_stages, (stages) => `${stages} of ${project.progress_summary?.total_stages || 0}`)}
            {renderInfoItem('Latest Daily Progress', project.progress_summary?.latest_daily_progress, (val) => `${val}%`)}
            {renderInfoItem('Last Update', project.progress_summary?.last_update, (date) => new Date(date).toLocaleDateString())}
          </div>
        </div>

        <div className="overview-card">
          <h3>Inspector Assignment</h3>
          <div className="info-grid">
            <div className="info-item">
              <label>Assignment Status:</label>
              <span className={`status-badge ${project.inspector_assignment?.is_assigned ? 'status-assigned' : 'status-unassigned'}`}>
                {project.inspector_assignment?.is_assigned ? 'ASSIGNED' : 'NOT ASSIGNED'}
              </span>
            </div>
            {project.inspector_assignment?.details && (
              <>
                {renderInfoItem('Inspector', `${project.inspector_assignment.details.inspector_first_name} ${project.inspector_assignment.details.inspector_last_name}`)}
                {renderInfoItem('Assigned Date', project.inspector_assignment.details.assigned_at, (date) => new Date(date).toLocaleDateString())}
                {renderInfoItem('Assigned By', `${project.inspector_assignment.details.assigned_by_first_name} ${project.inspector_assignment.details.assigned_by_last_name}`)}
                {renderInfoItem('Assignment Notes', project.inspector_assignment.details.assignment_notes)}
              </>
            )}
          </div>
        </div>

        {project.location && (project.location.address || project.location.latitude) && (
          <div className="overview-card">
            <h3>Location Details</h3>
            <div className="info-grid">
              {renderInfoItem('Address', project.location.address)}
              {renderInfoItem('Coordinates', 
                project.location.latitude && project.location.longitude ? 
                `${project.location.latitude}, ${project.location.longitude}` : null
              )}
              {renderInfoItem('Radius', project.location.radius_meters, (radius) => `${radius}m`)}
            </div>
          </div>
        )}

        {project.requirements && (
          <div className="overview-card">
            <h3>Project Requirements</h3>
            <div className="info-grid">
              <div className="info-item full-width">
                <label>Requirements:</label>
                <p className="requirements-text">{project.requirements}</p>
              </div>
            </div>
          </div>
        )}

        {(project.materials || project.cost_breakdown || project.contractor_notes) && (
          <div className="overview-card">
            <h3>Technical Details</h3>
            <div className="info-grid">
              {project.materials && (
                <div className="info-item full-width">
                  <label>Materials:</label>
                  <p className="technical-text">{project.materials}</p>
                </div>
              )}
              {project.cost_breakdown && (
                <div className="info-item full-width">
                  <label>Cost Breakdown:</label>
                  <div className="technical-text">
                    {typeof project.cost_breakdown === 'string' ? (
                      <p>{project.cost_breakdown}</p>
                    ) : (
                      <div className="cost-breakdown-details">
                        {project.cost_breakdown.materials && (
                          <div><strong>Materials:</strong> ₹{project.cost_breakdown.materials}</div>
                        )}
                        {project.cost_breakdown.labor && (
                          <div><strong>Labor:</strong> ₹{project.cost_breakdown.labor}</div>
                        )}
                        {project.cost_breakdown.utilities && (
                          <div><strong>Utilities:</strong> ₹{project.cost_breakdown.utilities}</div>
                        )}
                        {project.cost_breakdown.misc && (
                          <div><strong>Miscellaneous:</strong> ₹{project.cost_breakdown.misc}</div>
                        )}
                        {project.cost_breakdown.grand_total && (
                          <div><strong>Grand Total:</strong> ₹{project.cost_breakdown.grand_total}</div>
                        )}
                      </div>
                    )}
                  </div>
                </div>
              )}
              {project.contractor_notes && (
                <div className="info-item full-width">
                  <label>Contractor Notes:</label>
                  <p className="technical-text">{project.contractor_notes}</p>
                </div>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

// Inspection Reports Tab
const InspectionReportsTab = ({ reports, getInspectionStatusBadge }) => {
  return (
    <div className="inspection-reports">
      <div className="reports-header">
        <h3>Inspection History</h3>
        <p>All inspection reports for this project</p>
      </div>
      
      {reports.length === 0 ? (
        <div className="empty-state">
          <Icon name="file-text" size={48} color="#9ca3af" />
          <h3>No Inspection Reports</h3>
          <p>No inspections have been conducted for this project yet.</p>
        </div>
      ) : (
        <div className="reports-list">
          {reports.map((report) => (
            <div key={report.id} className="report-card">
              <div className="report-header">
                <div className="report-title">
                  <h4>{report.inspection_type?.replace('_', ' ').toUpperCase()} Inspection</h4>
                  <span className="report-date">{new Date(report.inspection_date).toLocaleDateString()}</span>
                </div>
                {getInspectionStatusBadge(report.overall_status)}
              </div>
              
              <div className="report-content">
                <div className="report-details">
                  <div className="detail-item">
                    <label>Stage:</label>
                    <span>{report.inspection_stage}</span>
                  </div>
                  <div className="detail-item">
                    <label>Quality Score:</label>
                    <span>{report.quality_score ? `${report.quality_score}/10` : 'Not scored'}</span>
                  </div>
                  <div className="detail-item">
                    <label>Safety Compliance:</label>
                    <span>{report.safety_compliance?.replace('_', ' ').toUpperCase()}</span>
                  </div>
                  {report.inspector_name && (
                    <div className="detail-item">
                      <label>Inspector:</label>
                      <span>{report.inspector_name}</span>
                    </div>
                  )}
                </div>
                
                {report.issues_identified && (
                  <div className="report-issues">
                    <label>Issues Identified:</label>
                    <p style={{ 
                      background: '#fef3c7', 
                      padding: '8px 12px', 
                      borderRadius: '6px', 
                      color: '#92400e',
                      border: '1px solid #fde68a',
                      margin: '4px 0'
                    }}>
                      {report.issues_identified}
                    </p>
                  </div>
                )}
                
                {report.corrective_actions_required && (
                  <div className="report-actions">
                    <label>Corrective Actions Required:</label>
                    <p style={{ 
                      background: '#fee2e2', 
                      padding: '8px 12px', 
                      borderRadius: '6px', 
                      color: '#991b1b',
                      border: '1px solid #fecaca',
                      margin: '4px 0'
                    }}>
                      {report.corrective_actions_required}
                    </p>
                  </div>
                )}
                
                {report.notes && (
                  <div className="report-notes">
                    <label>Notes:</label>
                    <p>{report.notes}</p>
                  </div>
                )}
                
                {report.recommendations && (
                  <div className="report-recommendations">
                    <label>Recommendations:</label>
                    <p style={{ 
                      background: '#dbeafe', 
                      padding: '8px 12px', 
                      borderRadius: '6px', 
                      color: '#1e40af',
                      border: '1px solid #93c5fd',
                      margin: '4px 0'
                    }}>
                      {report.recommendations}
                    </p>
                  </div>
                )}
                
                {report.next_inspection_date && (
                  <div className="report-next-inspection">
                    <label>Next Inspection Date:</label>
                    <span style={{ 
                      background: '#f0f9ff', 
                      padding: '4px 8px', 
                      borderRadius: '4px', 
                      color: '#0369a1',
                      border: '1px solid #bae6fd',
                      fontSize: '14px'
                    }}>
                      {new Date(report.next_inspection_date).toLocaleDateString()}
                    </span>
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

// New Inspection Tab
const NewInspectionTab = ({ project }) => {
  const [inspectionForm, setInspectionForm] = useState({
    inspection_date: new Date().toISOString().split('T')[0],
    inspection_time: new Date().toTimeString().slice(0, 5),
    inspection_stage: project.actual_current_stage || '',
    inspection_type: 'routine',
    overall_status: 'pending',
    quality_score: '',
    safety_compliance: 'compliant',
    notes: '',
    recommendations: '',
    next_inspection_date: '',
    // Enhanced inspection details
    weather_conditions: '',
    temperature: '',
    site_accessibility: 'good',
    work_progress_since_last: '',
    materials_on_site: '',
    equipment_on_site: '',
    workforce_present: '',
    safety_equipment_available: 'yes',
    safety_violations_found: 'no',
    structural_integrity: 'satisfactory',
    workmanship_quality: 'good',
    code_compliance: 'compliant',
    environmental_impact: 'minimal',
    waste_management: 'proper',
    site_cleanliness: 'good',
    access_roads_condition: 'good',
    utilities_status: 'operational',
    security_measures: 'adequate',
    issues_identified: '',
    corrective_actions_required: '',
    follow_up_required: 'no',
    inspector_signature: '',
    contractor_present: 'no',
    contractor_representative: '',
    homeowner_notified: 'no'
  });
  
  const [checklistItems, setChecklistItems] = useState([
    { category: 'Foundation', item_description: 'Foundation depth as per approved plans', status: 'pending', notes: '', priority: 'high' },
    { category: 'Foundation', item_description: 'Concrete quality and curing', status: 'pending', notes: '', priority: 'high' },
    { category: 'Foundation', item_description: 'Reinforcement placement and cover', status: 'pending', notes: '', priority: 'medium' },
    { category: 'Structure', item_description: 'Column alignment and dimensions', status: 'pending', notes: '', priority: 'high' },
    { category: 'Structure', item_description: 'Beam reinforcement and concrete quality', status: 'pending', notes: '', priority: 'high' },
    { category: 'Structure', item_description: 'Slab thickness and reinforcement', status: 'pending', notes: '', priority: 'medium' },
    { category: 'Electrical', item_description: 'Conduit installation as per code', status: 'pending', notes: '', priority: 'medium' },
    { category: 'Electrical', item_description: 'Earthing system compliance', status: 'pending', notes: '', priority: 'high' },
    { category: 'Plumbing', item_description: 'Pipe installation and testing', status: 'pending', notes: '', priority: 'medium' },
    { category: 'Plumbing', item_description: 'Drainage system functionality', status: 'pending', notes: '', priority: 'medium' },
    { category: 'Safety', item_description: 'Safety equipment availability', status: 'pending', notes: '', priority: 'critical' },
    { category: 'Safety', item_description: 'Site safety protocols followed', status: 'pending', notes: '', priority: 'critical' },
    { category: 'Quality', item_description: 'Material quality as per specifications', status: 'pending', notes: '', priority: 'high' },
    { category: 'Quality', item_description: 'Workmanship standards', status: 'pending', notes: '', priority: 'medium' }
  ]);
  
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setInspectionForm(prev => ({ ...prev, [name]: value }));
  };

  const handleChecklistChange = (index, field, value) => {
    setChecklistItems(prev => {
      const updated = [...prev];
      updated[index] = { ...updated[index], [field]: value };
      return updated;
    });
  };

  const addChecklistItem = () => {
    setChecklistItems(prev => [...prev, {
      category: '',
      item_description: '',
      status: 'pending',
      notes: '',
      priority: 'medium'
    }]);
  };

  const removeChecklistItem = (index) => {
    setChecklistItems(prev => prev.filter((_, i) => i !== index));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess('');

    try {
      const response = await fetch('/buildhub/backend/api/inspector/create_inspection_report.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          ...inspectionForm,
          project_id: project.id,
          checklist_items: checklistItems.filter(item => item.category && item.item_description)
        })
      });

      const result = await response.json();
      
      if (result.success) {
        setSuccess('Comprehensive inspection report created successfully!');
        // Reset form
        setInspectionForm({
          inspection_date: new Date().toISOString().split('T')[0],
          inspection_time: new Date().toTimeString().slice(0, 5),
          inspection_stage: project.actual_current_stage || '',
          inspection_type: 'routine',
          overall_status: 'pending',
          quality_score: '',
          safety_compliance: 'compliant',
          notes: '',
          recommendations: '',
          next_inspection_date: '',
          weather_conditions: '',
          temperature: '',
          site_accessibility: 'good',
          work_progress_since_last: '',
          materials_on_site: '',
          equipment_on_site: '',
          workforce_present: '',
          safety_equipment_available: 'yes',
          safety_violations_found: 'no',
          structural_integrity: 'satisfactory',
          workmanship_quality: 'good',
          code_compliance: 'compliant',
          environmental_impact: 'minimal',
          waste_management: 'proper',
          site_cleanliness: 'good',
          access_roads_condition: 'good',
          utilities_status: 'operational',
          security_measures: 'adequate',
          issues_identified: '',
          corrective_actions_required: '',
          follow_up_required: 'no',
          inspector_signature: '',
          contractor_present: 'no',
          contractor_representative: '',
          homeowner_notified: 'no'
        });
        // Reset checklist
        setChecklistItems([
          { category: 'Foundation', item_description: 'Foundation depth as per approved plans', status: 'pending', notes: '', priority: 'high' },
          { category: 'Foundation', item_description: 'Concrete quality and curing', status: 'pending', notes: '', priority: 'high' },
          { category: 'Foundation', item_description: 'Reinforcement placement and cover', status: 'pending', notes: '', priority: 'medium' },
          { category: 'Structure', item_description: 'Column alignment and dimensions', status: 'pending', notes: '', priority: 'high' },
          { category: 'Structure', item_description: 'Beam reinforcement and concrete quality', status: 'pending', notes: '', priority: 'high' },
          { category: 'Structure', item_description: 'Slab thickness and reinforcement', status: 'pending', notes: '', priority: 'medium' },
          { category: 'Electrical', item_description: 'Conduit installation as per code', status: 'pending', notes: '', priority: 'medium' },
          { category: 'Electrical', item_description: 'Earthing system compliance', status: 'pending', notes: '', priority: 'high' },
          { category: 'Plumbing', item_description: 'Pipe installation and testing', status: 'pending', notes: '', priority: 'medium' },
          { category: 'Plumbing', item_description: 'Drainage system functionality', status: 'pending', notes: '', priority: 'medium' },
          { category: 'Safety', item_description: 'Safety equipment availability', status: 'pending', notes: '', priority: 'critical' },
          { category: 'Safety', item_description: 'Site safety protocols followed', status: 'pending', notes: '', priority: 'critical' },
          { category: 'Quality', item_description: 'Material quality as per specifications', status: 'pending', notes: '', priority: 'high' },
          { category: 'Quality', item_description: 'Workmanship standards', status: 'pending', notes: '', priority: 'medium' }
        ]);
      } else {
        setError(result.message || 'Failed to create inspection report');
      }
    } catch (error) {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="new-inspection">
      <div className="inspection-form-header">
        <h3>Create Comprehensive Inspection Report</h3>
        <p>Document detailed inspection findings for {project.project_name}</p>
      </div>

      {error && (
        <div className="error-message">
          <Icon name="alert-circle" color="#ef4444" />
          <span>{error}</span>
        </div>
      )}

      {success && (
        <div className="success-message">
          <Icon name="check-circle" color="#10b981" />
          <span>{success}</span>
        </div>
      )}

      <form onSubmit={handleSubmit} className="comprehensive-inspection-form">
        {/* Basic Inspection Information */}
        <div className="form-section">
          <h4>Basic Inspection Information</h4>
          <div className="form-grid">
            <div className="form-group">
              <label htmlFor="inspection_date">Inspection Date *</label>
              <input
                type="date"
                id="inspection_date"
                name="inspection_date"
                value={inspectionForm.inspection_date}
                onChange={handleInputChange}
                required
              />
            </div>

            <div className="form-group">
              <label htmlFor="inspection_time">Inspection Time *</label>
              <input
                type="time"
                id="inspection_time"
                name="inspection_time"
                value={inspectionForm.inspection_time}
                onChange={handleInputChange}
                required
              />
            </div>

            <div className="form-group">
              <label htmlFor="inspection_stage">Construction Stage *</label>
              <select
                id="inspection_stage"
                name="inspection_stage"
                value={inspectionForm.inspection_stage}
                onChange={handleInputChange}
                required
              >
                <option value="">Select Stage</option>
                <option value="Site Preparation">Site Preparation</option>
                <option value="Foundation">Foundation</option>
                <option value="Structure">Structure</option>
                <option value="Brickwork">Brickwork</option>
                <option value="Roofing">Roofing</option>
                <option value="Electrical">Electrical</option>
                <option value="Plumbing">Plumbing</option>
                <option value="Finishing">Finishing</option>
                <option value="Final Inspection">Final Inspection</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="inspection_type">Inspection Type *</label>
              <select
                id="inspection_type"
                name="inspection_type"
                value={inspectionForm.inspection_type}
                onChange={handleInputChange}
                required
              >
                <option value="routine">Routine</option>
                <option value="milestone">Milestone</option>
                <option value="quality">Quality</option>
                <option value="safety">Safety</option>
                <option value="final">Final</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="overall_status">Overall Status *</label>
              <select
                id="overall_status"
                name="overall_status"
                value={inspectionForm.overall_status}
                onChange={handleInputChange}
                required
              >
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="needs_attention">Needs Attention</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="quality_score">Quality Score (1-10)</label>
              <input
                type="number"
                id="quality_score"
                name="quality_score"
                min="1"
                max="10"
                step="0.1"
                value={inspectionForm.quality_score}
                onChange={handleInputChange}
                placeholder="Rate quality out of 10"
              />
            </div>
          </div>
        </div>

        {/* Site Conditions */}
        <div className="form-section">
          <h4>Site Conditions</h4>
          <div className="form-grid">
            <div className="form-group">
              <label htmlFor="weather_conditions">Weather Conditions</label>
              <select
                id="weather_conditions"
                name="weather_conditions"
                value={inspectionForm.weather_conditions}
                onChange={handleInputChange}
              >
                <option value="">Select Weather</option>
                <option value="clear">Clear</option>
                <option value="cloudy">Cloudy</option>
                <option value="rainy">Rainy</option>
                <option value="windy">Windy</option>
                <option value="hot">Hot</option>
                <option value="cold">Cold</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="temperature">Temperature (°C)</label>
              <input
                type="number"
                id="temperature"
                name="temperature"
                value={inspectionForm.temperature}
                onChange={handleInputChange}
                placeholder="Temperature in Celsius"
              />
            </div>

            <div className="form-group">
              <label htmlFor="site_accessibility">Site Accessibility</label>
              <select
                id="site_accessibility"
                name="site_accessibility"
                value={inspectionForm.site_accessibility}
                onChange={handleInputChange}
              >
                <option value="good">Good</option>
                <option value="fair">Fair</option>
                <option value="poor">Poor</option>
                <option value="restricted">Restricted</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="access_roads_condition">Access Roads Condition</label>
              <select
                id="access_roads_condition"
                name="access_roads_condition"
                value={inspectionForm.access_roads_condition}
                onChange={handleInputChange}
              >
                <option value="good">Good</option>
                <option value="fair">Fair</option>
                <option value="poor">Poor</option>
                <option value="blocked">Blocked</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="site_cleanliness">Site Cleanliness</label>
              <select
                id="site_cleanliness"
                name="site_cleanliness"
                value={inspectionForm.site_cleanliness}
                onChange={handleInputChange}
              >
                <option value="excellent">Excellent</option>
                <option value="good">Good</option>
                <option value="fair">Fair</option>
                <option value="poor">Poor</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="utilities_status">Utilities Status</label>
              <select
                id="utilities_status"
                name="utilities_status"
                value={inspectionForm.utilities_status}
                onChange={handleInputChange}
              >
                <option value="operational">Operational</option>
                <option value="partial">Partial</option>
                <option value="not_available">Not Available</option>
                <option value="under_installation">Under Installation</option>
              </select>
            </div>
          </div>
        </div>

        {/* Work Progress Assessment */}
        <div className="form-section">
          <h4>Work Progress Assessment</h4>
          <div className="form-grid">
            <div className="form-group full-width">
              <label htmlFor="work_progress_since_last">Work Progress Since Last Inspection</label>
              <textarea
                id="work_progress_since_last"
                name="work_progress_since_last"
                rows="3"
                value={inspectionForm.work_progress_since_last}
                onChange={handleInputChange}
                placeholder="Describe the work completed since the last inspection..."
              />
            </div>

            <div className="form-group">
              <label htmlFor="workforce_present">Workforce Present</label>
              <input
                type="number"
                id="workforce_present"
                name="workforce_present"
                value={inspectionForm.workforce_present}
                onChange={handleInputChange}
                placeholder="Number of workers on site"
              />
            </div>

            <div className="form-group">
              <label htmlFor="contractor_present">Contractor Present</label>
              <select
                id="contractor_present"
                name="contractor_present"
                value={inspectionForm.contractor_present}
                onChange={handleInputChange}
              >
                <option value="no">No</option>
                <option value="yes">Yes</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="contractor_representative">Contractor Representative</label>
              <input
                type="text"
                id="contractor_representative"
                name="contractor_representative"
                value={inspectionForm.contractor_representative}
                onChange={handleInputChange}
                placeholder="Name of contractor representative present"
              />
            </div>

            <div className="form-group full-width">
              <label htmlFor="materials_on_site">Materials on Site</label>
              <textarea
                id="materials_on_site"
                name="materials_on_site"
                rows="2"
                value={inspectionForm.materials_on_site}
                onChange={handleInputChange}
                placeholder="List materials present on site..."
              />
            </div>

            <div className="form-group full-width">
              <label htmlFor="equipment_on_site">Equipment on Site</label>
              <textarea
                id="equipment_on_site"
                name="equipment_on_site"
                rows="2"
                value={inspectionForm.equipment_on_site}
                onChange={handleInputChange}
                placeholder="List equipment and machinery present on site..."
              />
            </div>
          </div>
        </div>

        {/* Safety Assessment */}
        <div className="form-section">
          <h4>Safety Assessment</h4>
          <div className="form-grid">
            <div className="form-group">
              <label htmlFor="safety_compliance">Safety Compliance</label>
              <select
                id="safety_compliance"
                name="safety_compliance"
                value={inspectionForm.safety_compliance}
                onChange={handleInputChange}
              >
                <option value="compliant">Compliant</option>
                <option value="non_compliant">Non-Compliant</option>
                <option value="partial">Partial</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="safety_equipment_available">Safety Equipment Available</label>
              <select
                id="safety_equipment_available"
                name="safety_equipment_available"
                value={inspectionForm.safety_equipment_available}
                onChange={handleInputChange}
              >
                <option value="yes">Yes</option>
                <option value="no">No</option>
                <option value="partial">Partial</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="safety_violations_found">Safety Violations Found</label>
              <select
                id="safety_violations_found"
                name="safety_violations_found"
                value={inspectionForm.safety_violations_found}
                onChange={handleInputChange}
              >
                <option value="no">No</option>
                <option value="yes">Yes</option>
                <option value="minor">Minor</option>
                <option value="major">Major</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="security_measures">Security Measures</label>
              <select
                id="security_measures"
                name="security_measures"
                value={inspectionForm.security_measures}
                onChange={handleInputChange}
              >
                <option value="adequate">Adequate</option>
                <option value="inadequate">Inadequate</option>
                <option value="excellent">Excellent</option>
                <option value="needs_improvement">Needs Improvement</option>
              </select>
            </div>
          </div>
        </div>

        {/* Quality Assessment */}
        <div className="form-section">
          <h4>Quality Assessment</h4>
          <div className="form-grid">
            <div className="form-group">
              <label htmlFor="structural_integrity">Structural Integrity</label>
              <select
                id="structural_integrity"
                name="structural_integrity"
                value={inspectionForm.structural_integrity}
                onChange={handleInputChange}
              >
                <option value="satisfactory">Satisfactory</option>
                <option value="excellent">Excellent</option>
                <option value="needs_attention">Needs Attention</option>
                <option value="unsatisfactory">Unsatisfactory</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="workmanship_quality">Workmanship Quality</label>
              <select
                id="workmanship_quality"
                name="workmanship_quality"
                value={inspectionForm.workmanship_quality}
                onChange={handleInputChange}
              >
                <option value="excellent">Excellent</option>
                <option value="good">Good</option>
                <option value="fair">Fair</option>
                <option value="poor">Poor</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="code_compliance">Code Compliance</label>
              <select
                id="code_compliance"
                name="code_compliance"
                value={inspectionForm.code_compliance}
                onChange={handleInputChange}
              >
                <option value="compliant">Compliant</option>
                <option value="non_compliant">Non-Compliant</option>
                <option value="partial">Partial</option>
                <option value="pending_verification">Pending Verification</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="waste_management">Waste Management</label>
              <select
                id="waste_management"
                name="waste_management"
                value={inspectionForm.waste_management}
                onChange={handleInputChange}
              >
                <option value="proper">Proper</option>
                <option value="improper">Improper</option>
                <option value="needs_improvement">Needs Improvement</option>
                <option value="excellent">Excellent</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="environmental_impact">Environmental Impact</label>
              <select
                id="environmental_impact"
                name="environmental_impact"
                value={inspectionForm.environmental_impact}
                onChange={handleInputChange}
              >
                <option value="minimal">Minimal</option>
                <option value="moderate">Moderate</option>
                <option value="significant">Significant</option>
                <option value="concerning">Concerning</option>
              </select>
            </div>
          </div>
        </div>

        {/* Inspection Checklist */}
        <div className="form-section">
          <h4>Inspection Checklist</h4>
          <div className="checklist-container">
            {checklistItems.map((item, index) => (
              <div key={index} className="checklist-item">
                <div className="checklist-header">
                  <select
                    value={item.category}
                    onChange={(e) => handleChecklistChange(index, 'category', e.target.value)}
                    className="category-select"
                  >
                    <option value="">Select Category</option>
                    <option value="Foundation">Foundation</option>
                    <option value="Structure">Structure</option>
                    <option value="Electrical">Electrical</option>
                    <option value="Plumbing">Plumbing</option>
                    <option value="Safety">Safety</option>
                    <option value="Quality">Quality</option>
                    <option value="Environmental">Environmental</option>
                    <option value="Other">Other</option>
                  </select>
                  <select
                    value={item.status}
                    onChange={(e) => handleChecklistChange(index, 'status', e.target.value)}
                    className="status-select"
                  >
                    <option value="pending">Pending</option>
                    <option value="pass">Pass</option>
                    <option value="fail">Fail</option>
                    <option value="na">N/A</option>
                  </select>
                  <select
                    value={item.priority}
                    onChange={(e) => handleChecklistChange(index, 'priority', e.target.value)}
                    className="priority-select"
                  >
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                  </select>
                  <button
                    type="button"
                    onClick={() => removeChecklistItem(index)}
                    className="remove-item-btn"
                  >
                    <Icon name="x" size={16} />
                  </button>
                </div>
                <input
                  type="text"
                  value={item.item_description}
                  onChange={(e) => handleChecklistChange(index, 'item_description', e.target.value)}
                  placeholder="Item description..."
                  className="item-description"
                />
                <textarea
                  value={item.notes}
                  onChange={(e) => handleChecklistChange(index, 'notes', e.target.value)}
                  placeholder="Notes..."
                  rows="2"
                  className="item-notes"
                />
              </div>
            ))}
            <button
              type="button"
              onClick={addChecklistItem}
              className="add-checklist-item-btn"
            >
              <Icon name="plus" size={16} />
              Add Checklist Item
            </button>
          </div>
        </div>

        {/* Issues and Recommendations */}
        <div className="form-section">
          <h4>Issues and Recommendations</h4>
          <div className="form-grid">
            <div className="form-group full-width">
              <label htmlFor="issues_identified">Issues Identified</label>
              <textarea
                id="issues_identified"
                name="issues_identified"
                rows="4"
                value={inspectionForm.issues_identified}
                onChange={handleInputChange}
                placeholder="Detail any issues, defects, or concerns identified during inspection..."
              />
            </div>

            <div className="form-group full-width">
              <label htmlFor="corrective_actions_required">Corrective Actions Required</label>
              <textarea
                id="corrective_actions_required"
                name="corrective_actions_required"
                rows="4"
                value={inspectionForm.corrective_actions_required}
                onChange={handleInputChange}
                placeholder="Specify corrective actions that need to be taken..."
              />
            </div>

            <div className="form-group full-width">
              <label htmlFor="notes">General Inspection Notes</label>
              <textarea
                id="notes"
                name="notes"
                rows="4"
                value={inspectionForm.notes}
                onChange={handleInputChange}
                placeholder="Document your inspection findings, observations, and any additional notes..."
              />
            </div>

            <div className="form-group full-width">
              <label htmlFor="recommendations">Recommendations</label>
              <textarea
                id="recommendations"
                name="recommendations"
                rows="3"
                value={inspectionForm.recommendations}
                onChange={handleInputChange}
                placeholder="Provide recommendations for improvements or next steps..."
              />
            </div>
          </div>
        </div>

        {/* Follow-up and Completion */}
        <div className="form-section">
          <h4>Follow-up and Completion</h4>
          <div className="form-grid">
            <div className="form-group">
              <label htmlFor="follow_up_required">Follow-up Required</label>
              <select
                id="follow_up_required"
                name="follow_up_required"
                value={inspectionForm.follow_up_required}
                onChange={handleInputChange}
              >
                <option value="no">No</option>
                <option value="yes">Yes</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="next_inspection_date">Next Inspection Date</label>
              <input
                type="date"
                id="next_inspection_date"
                name="next_inspection_date"
                value={inspectionForm.next_inspection_date}
                onChange={handleInputChange}
              />
            </div>

            <div className="form-group">
              <label htmlFor="homeowner_notified">Homeowner Notified</label>
              <select
                id="homeowner_notified"
                name="homeowner_notified"
                value={inspectionForm.homeowner_notified}
                onChange={handleInputChange}
              >
                <option value="no">No</option>
                <option value="yes">Yes</option>
                <option value="pending">Pending</option>
              </select>
            </div>

            <div className="form-group">
              <label htmlFor="inspector_signature">Inspector Signature/ID</label>
              <input
                type="text"
                id="inspector_signature"
                name="inspector_signature"
                value={inspectionForm.inspector_signature}
                onChange={handleInputChange}
                placeholder="Inspector name or digital signature"
              />
            </div>
          </div>
        </div>

        <div className="form-actions">
          <button type="submit" className="submit-btn" disabled={loading}>
            {loading ? 'Creating Comprehensive Report...' : 'Create Comprehensive Inspection Report'}
          </button>
        </div>
      </form>
    </div>
  );
};

// Project Progress Tab
const ProjectProgressTab = ({ projectProgress, progressLoading }) => {
  if (progressLoading) {
    return (
      <div className="progress-loading">
        <div className="loading-spinner"></div>
        <p>Loading project progress data...</p>
      </div>
    );
  }

  if (!projectProgress || !projectProgress.progress_updates) {
    return (
      <div className="empty-state">
        <Icon name="clock" size={48} color="#9ca3af" />
        <h3>No Progress Data</h3>
        <p>No daily progress updates have been recorded for this project yet.</p>
      </div>
    );
  }

  const { progress_updates, statistics, stage_breakdown, recent_issues } = projectProgress;

  return (
    <div className="project-progress">
      {/* Progress Statistics */}
      <div className="progress-stats-grid">
        <div className="stat-card">
          <div className="stat-icon">
            <Icon name="calendar" color="#3b82f6" />
          </div>
          <div className="stat-content">
            <h3>{statistics.total_updates}</h3>
            <p>Total Updates</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon">
            <Icon name="check-circle" color="#10b981" />
          </div>
          <div className="stat-content">
            <h3>{statistics.current_completion}%</h3>
            <p>Current Progress</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon">
            <Icon name="clock" color="#f59e0b" />
          </div>
          <div className="stat-content">
            <h3>{statistics.total_working_hours}h</h3>
            <p>Total Hours</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon">
            <Icon name="alert-circle" color="#ef4444" />
          </div>
          <div className="stat-content">
            <h3>{statistics.updates_with_issues}</h3>
            <p>Issues Reported</p>
          </div>
        </div>
      </div>

      {/* Stage Breakdown */}
      <div className="stage-breakdown-section">
        <h3>Construction Stages Progress</h3>
        <div className="stage-breakdown-grid">
          {stage_breakdown.map((stage, index) => (
            <div key={index} className="stage-card">
              <div className="stage-header">
                <h4>{stage.stage_name}</h4>
                <span className="stage-progress">{stage.total_progress}%</span>
              </div>
              <div className="stage-details">
                <div className="stage-detail">
                  <span className="label">Updates:</span>
                  <span className="value">{stage.update_count}</span>
                </div>
                <div className="stage-detail">
                  <span className="label">Avg Hours:</span>
                  <span className="value">{stage.avg_working_hours}h</span>
                </div>
                <div className="stage-detail">
                  <span className="label">Issues:</span>
                  <span className="value">{stage.issues_count}</span>
                </div>
                <div className="stage-detail">
                  <span className="label">Duration:</span>
                  <span className="value">
                    {stage.stage_start_date} to {stage.stage_last_update}
                  </span>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Recent Issues */}
      {recent_issues.length > 0 && (
        <div className="recent-issues-section">
          <h3>Recent Site Issues</h3>
          <div className="issues-list">
            {recent_issues.map((issue, index) => (
              <div key={index} className="issue-card">
                <div className="issue-header">
                  <span className="issue-date">{new Date(issue.update_date).toLocaleDateString()}</span>
                  <span className="issue-stage">{issue.construction_stage}</span>
                  <span className="issue-weather">{issue.weather_condition}</span>
                </div>
                <div className="issue-content">
                  <p>{issue.site_issues}</p>
                  <span className="issue-reporter">Reported by: {issue.contractor_name}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Daily Progress Updates */}
      <div className="daily-updates-section">
        <h3>Daily Progress Updates</h3>
        <div className="updates-list">
          {progress_updates.map((update) => (
            <div key={update.id} className="update-card">
              <div className="update-header">
                <div className="update-date-info">
                  <h4>{new Date(update.update_date).toLocaleDateString()}</h4>
                  <span className="update-stage">{update.construction_stage}</span>
                </div>
                <div className="update-progress-info">
                  <span className="daily-progress">+{update.incremental_completion_percentage}%</span>
                  <span className="total-progress">{update.cumulative_completion_percentage}% Total</span>
                </div>
              </div>
              
              <div className="update-content">
                <div className="work-description">
                  <h5>Work Done Today:</h5>
                  <p>{update.work_done_today}</p>
                </div>
                
                <div className="update-details-grid">
                  <div className="detail-item">
                    <Icon name="clock" size={16} color="#6b7280" />
                    <span>{update.working_hours} hours</span>
                  </div>
                  <div className="detail-item">
                    <Icon name="cloud" size={16} color="#6b7280" />
                    <span>{update.weather_condition}</span>
                  </div>
                  <div className="detail-item">
                    <Icon name="user" size={16} color="#6b7280" />
                    <span>{update.contractor.name}</span>
                  </div>
                  {update.location.latitude && (
                    <div className="detail-item">
                      <Icon name="map-pin" size={16} color="#6b7280" />
                      <span>
                        Location {update.location.verified ? 'Verified' : 'Unverified'}
                      </span>
                    </div>
                  )}
                </div>
                
                {update.site_issues && (
                  <div className="site-issues">
                    <h5>Site Issues:</h5>
                    <p className="issues-text">{update.site_issues}</p>
                  </div>
                )}
                
                {update.progress_photos && update.progress_photos.length > 0 && (
                  <div className="progress-photos">
                    <h5>Progress Photos:</h5>
                    <div className="photos-grid">
                      {update.progress_photos.map((photo, photoIndex) => (
                        <div key={photoIndex} className="photo-thumbnail">
                          <img 
                            src={photo.url || photo} 
                            alt={`Progress photo ${photoIndex + 1}`}
                            onError={(e) => {
                              e.target.style.display = 'none';
                            }}
                          />
                        </div>
                      ))}
                    </div>
                  </div>
                )}
                
                <div className="update-timestamps">
                  <span>Created: {new Date(update.timestamps.created_at).toLocaleString()}</span>
                  {update.timestamps.updated_at !== update.timestamps.created_at && (
                    <span>Updated: {new Date(update.timestamps.updated_at).toLocaleString()}</span>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

// All Reports View Component for Admin
const AllReportsView = ({ reports, stats, loading, getInspectionStatusBadge }) => {
  return (
    <>
      {/* Statistics Cards */}
      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-icon">
            <Icon name="clipboard" color="#3b82f6" />
          </div>
          <div className="stat-content">
            <h3>{stats.total_reports || 0}</h3>
            <p>Total Reports</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon">
            <Icon name="check-circle" color="#10b981" />
          </div>
          <div className="stat-content">
            <h3>{stats.approved_count || 0}</h3>
            <p>Approved</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon">
            <Icon name="x" color="#ef4444" />
          </div>
          <div className="stat-content">
            <h3>{stats.rejected_count || 0}</h3>
            <p>Rejected</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon">
            <Icon name="alert-circle" color="#f59e0b" />
          </div>
          <div className="stat-content">
            <h3>{stats.needs_attention_count || 0}</h3>
            <p>Need Attention</p>
          </div>
        </div>
      </div>

      {/* Reports List */}
      <div className="reports-section">
        <h2>All Inspection Reports</h2>
        {loading ? (
          <div style={{ textAlign: 'center', padding: '40px' }}>
            <div className="loading-spinner"></div>
            <p>Loading inspection reports...</p>
          </div>
        ) : reports.length === 0 ? (
          <div className="empty-state">
            <Icon name="clipboard" size={48} color="#9ca3af" />
            <h3>No Inspection Reports</h3>
            <p>No inspection reports found in the system.</p>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '15px' }}>
            {reports.map((report) => (
              <div key={report.id} style={{
                backgroundColor: 'white',
                border: '1px solid #e5e7eb',
                borderRadius: '8px',
                padding: '20px',
                boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)'
              }}>
                <div style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'flex-start',
                  marginBottom: '15px'
                }}>
                  <div>
                    <h4 style={{ margin: '0 0 5px 0', color: '#1f2937' }}>
                      {report.project.name}
                    </h4>
                    <p style={{ margin: '0', color: '#6b7280', fontSize: '14px' }}>
                      {report.project.location} • {report.inspection.stage} • {report.inspection.type.replace('_', ' ').toUpperCase()}
                    </p>
                  </div>
                  <div style={{ textAlign: 'right' }}>
                    {getInspectionStatusBadge(report.inspection.status)}
                    <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '5px' }}>
                      {new Date(report.inspection.date).toLocaleDateString()}
                    </div>
                  </div>
                </div>

                <div style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
                  gap: '15px',
                  marginBottom: '15px',
                  fontSize: '14px'
                }}>
                  <div>
                    <strong>Inspector:</strong> {report.inspector.name}<br/>
                    <strong>Quality Score:</strong> {report.inspection.quality_score ? `${report.inspection.quality_score}/10` : 'Not scored'}<br/>
                    <strong>Safety:</strong> {report.inspection.safety_compliance.replace('_', ' ').toUpperCase()}
                  </div>
                  <div>
                    <strong>Homeowner:</strong> {report.homeowner.name}<br/>
                    <strong>Contractor:</strong> {report.contractor.name || 'Not assigned'}<br/>
                    <strong>Checklist Items:</strong> {report.counts.checklist_items}
                  </div>
                  <div>
                    <strong>Failed Items:</strong> {report.counts.failed_items}<br/>
                    <strong>Photos:</strong> {report.counts.photos}<br/>
                    <strong>Follow-up:</strong> {report.follow_up.follow_up_required !== 'no' ? 'Required' : 'Not required'}
                  </div>
                </div>

                {report.inspection.issues_identified && (
                  <div style={{
                    backgroundColor: '#fef3c7',
                    padding: '12px',
                    borderRadius: '6px',
                    marginBottom: '10px',
                    border: '1px solid #fde68a'
                  }}>
                    <strong style={{ color: '#92400e' }}>Issues Identified:</strong>
                    <p style={{ margin: '5px 0 0 0', fontSize: '14px', color: '#92400e' }}>
                      {report.inspection.issues_identified}
                    </p>
                  </div>
                )}

                {report.inspection.recommendations && (
                  <div style={{
                    backgroundColor: '#dbeafe',
                    padding: '12px',
                    borderRadius: '6px',
                    marginBottom: '10px',
                    border: '1px solid #93c5fd'
                  }}>
                    <strong style={{ color: '#1e40af' }}>Recommendations:</strong>
                    <p style={{ margin: '5px 0 0 0', fontSize: '14px', color: '#1e40af' }}>
                      {report.inspection.recommendations}
                    </p>
                  </div>
                )}

                {report.inspection.notes && (
                  <div style={{
                    backgroundColor: '#f3f4f6',
                    padding: '12px',
                    borderRadius: '6px',
                    border: '1px solid #d1d5db'
                  }}>
                    <strong style={{ color: '#374151' }}>Inspector Notes:</strong>
                    <p style={{ margin: '5px 0 0 0', fontSize: '14px', color: '#374151' }}>
                      {report.inspection.notes}
                    </p>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </>
  );
};

export default SiteInspectionDashboard;