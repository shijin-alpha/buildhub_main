import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import '../styles/SiteInspectorDashboard.css';
import '../styles/BlueGlassTheme.css';
import '../styles/SoftSidebar.css';
import './WidgetColors.css';
import { useToast } from './ToastProvider.jsx';
import SiteInspectorLogin from './SiteInspectorLogin';
import InspectionReportForm from './InspectionReportForm';
import InspectionHistory from './InspectionHistory';
import ProjectDetailsModal from './ProjectDetailsModal';

// Lightweight icon component
const Icon = ({ name, size = 20, stroke = 1.8, color = 'currentColor' }) => {
  const common = { width: size, height: size, viewBox: '0 0 24 24', fill: 'none', stroke: color, strokeWidth: stroke, strokeLinecap: 'round', strokeLinejoin: 'round' };
  switch (name) {
    case 'home':
      return (<svg {...common}><path d="M3 10.5L12 3l9 7.5"/><path d="M5 10v10h14V10"/></svg>);
    case 'clipboard':
      return (<svg {...common}><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>);
    case 'camera':
      return (<svg {...common}><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>);
    case 'history':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>);
    case 'eye':
      return (<svg {...common}><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>);
    case 'map-pin':
      return (<svg {...common}><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>);
    case 'calendar':
      return (<svg {...common}><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>);
    case 'check-circle':
      return (<svg {...common}><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M9 11l3 3L22 4"/></svg>);
    case 'alert-circle':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>);
    case 'x-circle':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>);
    case 'clock':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>);
    case 'dollar-sign':
      return (<svg {...common}><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>);
    case 'logout':
      return (<svg {...common}><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>);
    case 'plus':
      return (<svg {...common}><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>);
    case 'filter':
      return (<svg {...common}><polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46"/></svg>);
    default:
      return null;
  }
};

const SiteInspectorDashboard = () => {
  const navigate = useNavigate();
  const toast = useToast();
  const [activeTab, setActiveTab] = useState('overview');
  const [user, setUser] = useState(null);
  const [assignedProjects, setAssignedProjects] = useState([]);
  const [stats, setStats] = useState({});
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [selectedProject, setSelectedProject] = useState(null);
  const [showProjectDetails, setShowProjectDetails] = useState(false);
  const [showInspectionForm, setShowInspectionForm] = useState(false);
  const [refreshTrigger, setRefreshTrigger] = useState(0);

  // Check authentication on component mount
  useEffect(() => {
    checkAuthentication();
  }, []);

  // Fetch data when authenticated
  useEffect(() => {
    if (user) {
      fetchAssignedProjects();
    }
  }, [user, refreshTrigger]);

  const checkAuthentication = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/auth/check_session.php', {
        credentials: 'include'
      });
      const data = await response.json();
      
      if (data.success && data.user && data.user.role === 'site_inspector') {
        setUser(data.user);
      } else {
        // Not authenticated as site inspector
        setUser(null);
      }
    } catch (error) {
      console.error('Authentication check failed:', error);
      setUser(null);
    }
  };

  const fetchAssignedProjects = async () => {
    setLoading(true);
    try {
      const response = await fetch('/buildhub/backend/api/inspector/get_all_real_projects.php', {
        credentials: 'include'
      });
      const data = await response.json();
      
      if (data.success) {
        setAssignedProjects(data.projects);
        setStats(data.statistics);
      } else {
        setError(data.message);
        toast.error(data.message);
      }
    } catch (error) {
      console.error('Error fetching projects:', error);
      setError('Failed to fetch real projects');
      toast.error('Failed to fetch real projects');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    try {
      await fetch('/buildhub/backend/api/auth/logout.php', {
        method: 'POST',
        credentials: 'include'
      });
      setUser(null);
      navigate('/');
    } catch (error) {
      console.error('Logout error:', error);
    }
  };

  const openProjectDetails = (project) => {
    setSelectedProject(project);
    setShowProjectDetails(true);
  };

  const openInspectionForm = (project) => {
    setSelectedProject(project);
    setShowInspectionForm(true);
  };

  const handleInspectionSubmitted = () => {
    setShowInspectionForm(false);
    setRefreshTrigger(prev => prev + 1);
    toast.success('Inspection report submitted successfully');
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'approved': return <Icon name="check-circle" color="#10b981" />;
      case 'rejected': return <Icon name="x-circle" color="#ef4444" />;
      case 'needs_attention': return <Icon name="alert-circle" color="#f59e0b" />;
      case 'pending': return <Icon name="clock" color="#6b7280" />;
      default: return <Icon name="clock" color="#6b7280" />;
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'approved': return '#10b981';
      case 'rejected': return '#ef4444';
      case 'needs_attention': return '#f59e0b';
      case 'pending': return '#6b7280';
      case 'in_progress': return '#3b82f6';
      case 'completed': return '#10b981';
      default: return '#6b7280';
    }
  };

  // If not authenticated, show login
  if (!user) {
    return <SiteInspectorLogin onLoginSuccess={checkAuthentication} />;
  }

  return (
    <div className="site-inspector-dashboard">
      {/* Header */}
      <header className="dashboard-header">
        <div className="header-content">
          <div className="header-left">
            <h1>Site Inspector Dashboard</h1>
            <p>Welcome, {user.first_name} {user.last_name}</p>
          </div>
          <div className="header-right">
            <button onClick={handleLogout} className="logout-btn">
              <Icon name="logout" size={18} />
              Logout
            </button>
          </div>
        </div>
      </header>

      {/* Navigation Tabs */}
      <nav className="dashboard-nav">
        <button 
          className={`nav-tab ${activeTab === 'overview' ? 'active' : ''}`}
          onClick={() => setActiveTab('overview')}
        >
          <Icon name="home" size={18} />
          Overview
        </button>
        <button 
          className={`nav-tab ${activeTab === 'inspections' ? 'active' : ''}`}
          onClick={() => setActiveTab('inspections')}
        >
          <Icon name="clipboard" size={18} />
          New Inspection
        </button>
        <button 
          className={`nav-tab ${activeTab === 'history' ? 'active' : ''}`}
          onClick={() => setActiveTab('history')}
        >
          <Icon name="history" size={18} />
          History
        </button>
      </nav>

      {/* Main Content */}
      <main className="dashboard-main">
        {activeTab === 'overview' && (
          <div className="overview-content">
            {/* Summary Cards */}
            <div className="summary-cards">
              <div className="summary-card">
                <div className="card-icon">
                  <Icon name="home" size={24} color="#3b82f6" />
                </div>
                <div className="card-content">
                  <h3>{stats.total_projects || 0}</h3>
                  <p>Total Projects</p>
                </div>
              </div>
              <div className="summary-card">
                <div className="card-icon">
                  <Icon name="clock" size={24} color="#f59e0b" />
                </div>
                <div className="card-content">
                  <h3>{stats.active_projects || 0}</h3>
                  <p>Active Projects</p>
                </div>
              </div>
              <div className="summary-card">
                <div className="card-icon">
                  <Icon name="check-circle" size={24} color="#10b981" />
                </div>
                <div className="card-content">
                  <h3>{stats.completed_projects || 0}</h3>
                  <p>Completed Projects</p>
                </div>
              </div>
              <div className="summary-card">
                <div className="card-icon">
                  <Icon name="calendar" size={24} color="#8b5cf6" />
                </div>
                <div className="card-content">
                  <h3>{stats.assigned_projects || 0}</h3>
                  <p>Assigned to Inspector</p>
                </div>
              </div>
            </div>

            {/* Assigned Projects */}
            <div className="projects-section">
              <div className="section-header">
                <h2>All Construction Projects</h2>
                <p>View and inspect all real construction projects in the system</p>
              </div>
              
              {loading ? (
                <div className="loading-state">Loading projects...</div>
              ) : assignedProjects.length === 0 ? (
                <div className="empty-state">
                  <Icon name="home" size={48} color="#9ca3af" />
                  <h3>No Projects Found</h3>
                  <p>No construction projects are available in the system.</p>
                </div>
              ) : (
                <div className="projects-grid">
                  {assignedProjects.map((project) => (
                    <div key={project.id} className="project-card">
                      <div className="project-header">
                        <h3>{project.project_name}</h3>
                        <div className="project-status" style={{ color: getStatusColor(project.status) }}>
                          {project.status.replace('_', ' ').toUpperCase()}
                        </div>
                      </div>
                      
                      <div className="project-details">
                        <div className="detail-row">
                          <Icon name="map-pin" size={16} />
                          <span>{project.project_location || 'Location not specified'}</span>
                        </div>
                        <div className="detail-row">
                          <Icon name="clipboard" size={16} />
                          <span>Stage: {project.actual_current_stage}</span>
                        </div>
                        <div className="detail-row">
                          <Icon name="calendar" size={16} />
                          <span>Real Progress: {project.real_completion_percentage}%</span>
                        </div>
                        {project.stored_completion_percentage !== project.real_completion_percentage && (
                          <div className="detail-row" style={{ color: '#f59e0b' }}>
                            <Icon name="alert-circle" size={16} />
                            <span>Stored: {project.stored_completion_percentage}% (outdated)</span>
                          </div>
                        )}
                        <div className="detail-row">
                          <Icon name="dollar-sign" size={16} />
                          <span>Cost: ₹{project.financial.total_cost ? project.financial.total_cost.toLocaleString() : 'Not specified'}</span>
                        </div>
                      </div>

                      <div className="project-meta">
                        <div className="meta-item">
                          <strong>Homeowner:</strong> {project.homeowner.name}
                        </div>
                        <div className="meta-item">
                          <strong>Email:</strong> {project.homeowner.email}
                        </div>
                        <div className="meta-item">
                          <strong>Contractor:</strong> {project.contractor.name}
                        </div>
                        <div className="meta-item">
                          <strong>Timeline:</strong> {project.financial.timeline}
                        </div>
                        <div className="meta-item">
                          <strong>Stage Progress:</strong> {project.statistics.completed_stages}/{project.statistics.total_stages} stages completed
                        </div>
                        <div className="meta-item">
                          <strong>Inspector Status:</strong> 
                          <span style={{ color: project.inspector_assignment.is_assigned ? '#10b981' : '#6b7280' }}>
                            {project.inspector_assignment.is_assigned ? ' Assigned' : ' Not assigned'}
                          </span>
                        </div>
                        {project.latest_stage_payment && (
                          <div className="meta-item">
                            <strong>Latest Payment:</strong> {project.latest_stage_payment.stage_name} - {project.latest_stage_payment.status}
                            <br />
                            <small>₹{project.latest_stage_payment.amount.toLocaleString()} on {project.latest_stage_payment.request_date}</small>
                          </div>
                        )}
                      </div>

                      <div className="project-actions">
                        <button 
                          className="btn-secondary"
                          onClick={() => openProjectDetails(project)}
                        >
                          <Icon name="eye" size={16} />
                          View Details
                        </button>
                        <button 
                          className="btn-primary"
                          onClick={() => openInspectionForm(project)}
                        >
                          <Icon name="plus" size={16} />
                          New Inspection
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}

        {activeTab === 'inspections' && (
          <div className="inspections-content">
            <div className="section-header">
              <h2>Create New Inspection Report</h2>
              <p>Select a project to create an inspection report</p>
            </div>
            
            {assignedProjects.length === 0 ? (
              <div className="empty-state">
                <Icon name="clipboard" size={48} color="#9ca3af" />
                <h3>No Projects Available</h3>
                <p>You need assigned projects to create inspection reports.</p>
              </div>
            ) : (
              <div className="projects-list">
                {assignedProjects.map((project) => (
                  <div key={project.id} className="project-list-item">
                    <div className="project-info">
                      <h3>{project.project_name}</h3>
                      <p>{project.project_location}</p>
                      <span className="stage-badge">Stage: {project.actual_current_stage}</span>
                    </div>
                    <button 
                      className="btn-primary"
                      onClick={() => openInspectionForm(project)}
                    >
                      <Icon name="clipboard" size={16} />
                      Create Inspection
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        {activeTab === 'history' && (
          <InspectionHistory />
        )}
      </main>

      {/* Modals */}
      {showProjectDetails && selectedProject && (
        <ProjectDetailsModal 
          project={selectedProject}
          onClose={() => setShowProjectDetails(false)}
        />
      )}

      {showInspectionForm && selectedProject && (
        <InspectionReportForm 
          project={selectedProject}
          onClose={() => setShowInspectionForm(false)}
          onSubmitted={handleInspectionSubmitted}
        />
      )}
    </div>
  );
};

export default SiteInspectorDashboard;