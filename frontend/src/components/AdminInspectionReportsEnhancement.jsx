import React, { useState, useEffect } from 'react';
import '../styles/SiteInspectionDashboard.css';

// Enhanced Admin Inspection Reports Component
const AdminInspectionReportsEnhancement = () => {
  const [reports, setReports] = useState([]);
  const [filteredReports, setFilteredReports] = useState([]);
  const [statistics, setStatistics] = useState({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  
  // Filter states
  const [filters, setFilters] = useState({
    status: 'all',
    inspector: 'all',
    project: 'all',
    dateFrom: '',
    dateTo: '',
    search: ''
  });
  
  const [inspectors, setInspectors] = useState([]);
  const [projects, setProjects] = useState([]);

  useEffect(() => {
    fetchInspectionReports();
    fetchFilterOptions();
  }, []);

  useEffect(() => {
    applyFilters();
  }, [reports, filters]);

  const fetchInspectionReports = async () => {
    try {
      setLoading(true);
      const response = await fetch('/buildhub/backend/api/admin/get_inspection_reports.php', {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setReports(result.reports || []);
        setStatistics(result.statistics || {});
      } else {
        setError(result.message || 'Failed to fetch inspection reports');
      }
    } catch (error) {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const fetchFilterOptions = async () => {
    try {
      // Get unique inspectors and projects from reports
      const response = await fetch('/buildhub/backend/api/admin/get_inspection_reports.php?limit=1000', {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const result = await response.json();
      
      if (result.success) {
        const uniqueInspectors = [...new Map(result.reports.map(r => [r.inspector.id, r.inspector])).values()];
        const uniqueProjects = [...new Map(result.reports.map(r => [r.project.id, r.project])).values()];
        
        setInspectors(uniqueInspectors);
        setProjects(uniqueProjects);
      }
    } catch (error) {
      console.error('Error fetching filter options:', error);
    }
  };

  const applyFilters = () => {
    let filtered = [...reports];

    // Status filter
    if (filters.status !== 'all') {
      filtered = filtered.filter(report => report.inspection.status === filters.status);
    }

    // Inspector filter
    if (filters.inspector !== 'all') {
      filtered = filtered.filter(report => report.inspector.id.toString() === filters.inspector);
    }

    // Project filter
    if (filters.project !== 'all') {
      filtered = filtered.filter(report => report.project.id.toString() === filters.project);
    }

    // Date range filter
    if (filters.dateFrom) {
      filtered = filtered.filter(report => new Date(report.inspection.date) >= new Date(filters.dateFrom));
    }
    if (filters.dateTo) {
      filtered = filtered.filter(report => new Date(report.inspection.date) <= new Date(filters.dateTo));
    }

    // Search filter
    if (filters.search) {
      const searchLower = filters.search.toLowerCase();
      filtered = filtered.filter(report => 
        report.project.name.toLowerCase().includes(searchLower) ||
        report.project.location.toLowerCase().includes(searchLower) ||
        report.inspector.name.toLowerCase().includes(searchLower) ||
        report.homeowner.name.toLowerCase().includes(searchLower) ||
        (report.inspection.notes && report.inspection.notes.toLowerCase().includes(searchLower))
      );
    }

    setFilteredReports(filtered);
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({
      ...prev,
      [key]: value
    }));
  };

  const clearFilters = () => {
    setFilters({
      status: 'all',
      inspector: 'all',
      project: 'all',
      dateFrom: '',
      dateTo: '',
      search: ''
    });
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
          padding: '4px 12px',
          borderRadius: '12px',
          fontSize: '12px',
          fontWeight: '500',
          border: `1px solid ${config.color}20`
        }}
      >
        {config.text}
      </span>
    );
  };

  const exportReports = () => {
    const csvContent = [
      ['Date', 'Project', 'Location', 'Inspector', 'Status', 'Quality Score', 'Safety', 'Issues', 'Recommendations'].join(','),
      ...filteredReports.map(report => [
        report.inspection.date,
        `"${report.project.name}"`,
        `"${report.project.location}"`,
        `"${report.inspector.name}"`,
        report.inspection.status,
        report.inspection.quality_score || 'N/A',
        report.inspection.safety_compliance,
        `"${report.inspection.issues_identified || 'None'}"`,
        `"${report.inspection.recommendations || 'None'}"`
      ].join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `inspection_reports_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
  };

  if (loading) {
    return (
      <div className="loading-container">
        <div className="loading-spinner"></div>
        <p>Loading inspection reports...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="error-message">
        <span>⚠️</span>
        {error}
      </div>
    );
  }

  return (
    <div className="site-inspection-dashboard">
      <div className="dashboard-header">
        <h1>Site Inspection Reports</h1>
        <p>Comprehensive view of all inspection reports across all projects</p>
      </div>

      {/* Statistics Cards */}
      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" strokeWidth="2">
              <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
              <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
            </svg>
          </div>
          <div className="stat-content">
            <h3>{statistics.total_reports || 0}</h3>
            <p>Total Reports</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" strokeWidth="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
              <polyline points="22,4 12,14.01 9,11.01"/>
            </svg>
          </div>
          <div className="stat-content">
            <h3>{statistics.approved_count || 0}</h3>
            <p>Approved</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" strokeWidth="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </div>
          <div className="stat-content">
            <h3>{statistics.rejected_count || 0}</h3>
            <p>Rejected</p>
          </div>
        </div>
        <div className="stat-card">
          <div className="stat-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" strokeWidth="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>
          <div className="stat-content">
            <h3>{statistics.needs_attention_count || 0}</h3>
            <p>Need Attention</p>
          </div>
        </div>
      </div>

      {/* Filters Section */}
      <div style={{
        backgroundColor: 'white',
        padding: '20px',
        borderRadius: '12px',
        marginBottom: '24px',
        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
        border: '1px solid #e2e8f0'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
          <h3 style={{ margin: 0, color: '#1e293b' }}>Filters</h3>
          <div style={{ display: 'flex', gap: '12px' }}>
            <button
              onClick={clearFilters}
              style={{
                padding: '8px 16px',
                backgroundColor: '#f3f4f6',
                color: '#374151',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                cursor: 'pointer',
                fontSize: '14px'
              }}
            >
              Clear Filters
            </button>
            <button
              onClick={exportReports}
              style={{
                padding: '8px 16px',
                backgroundColor: '#3b82f6',
                color: 'white',
                border: 'none',
                borderRadius: '6px',
                cursor: 'pointer',
                fontSize: '14px',
                display: 'flex',
                alignItems: 'center',
                gap: '6px'
              }}
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <path d="M7 10l5 5 5-5"/>
                <path d="M12 15V3"/>
              </svg>
              Export CSV
            </button>
          </div>
        </div>
        
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
          gap: '16px'
        }}>
          <div>
            <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500', color: '#374151' }}>
              Search
            </label>
            <input
              type="text"
              value={filters.search}
              onChange={(e) => handleFilterChange('search', e.target.value)}
              placeholder="Search projects, locations, inspectors..."
              style={{
                width: '100%',
                padding: '8px 12px',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                fontSize: '14px'
              }}
            />
          </div>
          
          <div>
            <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500', color: '#374151' }}>
              Status
            </label>
            <select
              value={filters.status}
              onChange={(e) => handleFilterChange('status', e.target.value)}
              style={{
                width: '100%',
                padding: '8px 12px',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                fontSize: '14px'
              }}
            >
              <option value="all">All Statuses</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="needs_attention">Needs Attention</option>
              <option value="pending">Pending</option>
            </select>
          </div>
          
          <div>
            <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500', color: '#374151' }}>
              Inspector
            </label>
            <select
              value={filters.inspector}
              onChange={(e) => handleFilterChange('inspector', e.target.value)}
              style={{
                width: '100%',
                padding: '8px 12px',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                fontSize: '14px'
              }}
            >
              <option value="all">All Inspectors</option>
              {inspectors.map(inspector => (
                <option key={inspector.id} value={inspector.id}>
                  {inspector.name}
                </option>
              ))}
            </select>
          </div>
          
          <div>
            <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500', color: '#374151' }}>
              From Date
            </label>
            <input
              type="date"
              value={filters.dateFrom}
              onChange={(e) => handleFilterChange('dateFrom', e.target.value)}
              style={{
                width: '100%',
                padding: '8px 12px',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                fontSize: '14px'
              }}
            />
          </div>
          
          <div>
            <label style={{ display: 'block', marginBottom: '4px', fontSize: '14px', fontWeight: '500', color: '#374151' }}>
              To Date
            </label>
            <input
              type="date"
              value={filters.dateTo}
              onChange={(e) => handleFilterChange('dateTo', e.target.value)}
              style={{
                width: '100%',
                padding: '8px 12px',
                border: '1px solid #d1d5db',
                borderRadius: '6px',
                fontSize: '14px'
              }}
            />
          </div>
        </div>
        
        <div style={{ marginTop: '12px', fontSize: '14px', color: '#6b7280' }}>
          Showing {filteredReports.length} of {reports.length} reports
        </div>
      </div>

      {/* Reports List */}
      <div className="reports-section">
        {filteredReports.length === 0 ? (
          <div className="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" strokeWidth="1.5">
              <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
              <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
            </svg>
            <h3>No Inspection Reports Found</h3>
            <p>No inspection reports match your current filters.</p>
          </div>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
            {filteredReports.map((report) => (
              <div key={report.id} style={{
                backgroundColor: 'white',
                border: '1px solid #e5e7eb',
                borderRadius: '12px',
                padding: '24px',
                boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                transition: 'all 0.2s ease'
              }}>
                <div style={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'flex-start',
                  marginBottom: '16px'
                }}>
                  <div>
                    <h4 style={{ margin: '0 0 6px 0', color: '#1f2937', fontSize: '18px', fontWeight: '600' }}>
                      {report.project.name}
                    </h4>
                    <p style={{ margin: '0', color: '#6b7280', fontSize: '14px' }}>
                      📍 {report.project.location} • 🏗️ {report.inspection.stage} • 📋 {report.inspection.type.replace('_', ' ').toUpperCase()}
                    </p>
                  </div>
                  <div style={{ textAlign: 'right' }}>
                    {getInspectionStatusBadge(report.inspection.status)}
                    <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '6px' }}>
                      📅 {new Date(report.inspection.date).toLocaleDateString()}
                    </div>
                  </div>
                </div>

                <div style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
                  gap: '20px',
                  marginBottom: '16px',
                  fontSize: '14px'
                }}>
                  <div style={{ padding: '16px', backgroundColor: '#f8fafc', borderRadius: '8px' }}>
                    <h5 style={{ margin: '0 0 8px 0', color: '#1e293b', fontWeight: '600' }}>👤 People</h5>
                    <div><strong>Inspector:</strong> {report.inspector.name}</div>
                    <div><strong>Homeowner:</strong> {report.homeowner.name}</div>
                    <div><strong>Contractor:</strong> {report.contractor.name || 'Not assigned'}</div>
                  </div>
                  
                  <div style={{ padding: '16px', backgroundColor: '#f0f9ff', borderRadius: '8px' }}>
                    <h5 style={{ margin: '0 0 8px 0', color: '#1e293b', fontWeight: '600' }}>📊 Assessment</h5>
                    <div><strong>Quality Score:</strong> {report.inspection.quality_score ? `${report.inspection.quality_score}/10` : 'Not scored'}</div>
                    <div><strong>Safety:</strong> {report.inspection.safety_compliance.replace('_', ' ').toUpperCase()}</div>
                    <div><strong>Follow-up:</strong> {report.follow_up.follow_up_required !== 'no' ? '⚠️ Required' : '✅ Not required'}</div>
                  </div>
                  
                  <div style={{ padding: '16px', backgroundColor: '#f0fdf4', borderRadius: '8px' }}>
                    <h5 style={{ margin: '0 0 8px 0', color: '#1e293b', fontWeight: '600' }}>📋 Checklist</h5>
                    <div><strong>Total Items:</strong> {report.counts.checklist_items}</div>
                    <div><strong>Failed Items:</strong> {report.counts.failed_items > 0 ? `❌ ${report.counts.failed_items}` : '✅ 0'}</div>
                    <div><strong>Photos:</strong> {report.counts.photos}</div>
                  </div>
                </div>

                {report.inspection.issues_identified && (
                  <div style={{
                    backgroundColor: '#fef3c7',
                    padding: '16px',
                    borderRadius: '8px',
                    marginBottom: '12px',
                    border: '1px solid #fde68a'
                  }}>
                    <strong style={{ color: '#92400e', display: 'flex', alignItems: 'center', gap: '6px' }}>
                      ⚠️ Issues Identified:
                    </strong>
                    <p style={{ margin: '8px 0 0 0', fontSize: '14px', color: '#92400e' }}>
                      {report.inspection.issues_identified}
                    </p>
                  </div>
                )}

                {report.inspection.recommendations && (
                  <div style={{
                    backgroundColor: '#dbeafe',
                    padding: '16px',
                    borderRadius: '8px',
                    marginBottom: '12px',
                    border: '1px solid #93c5fd'
                  }}>
                    <strong style={{ color: '#1e40af', display: 'flex', alignItems: 'center', gap: '6px' }}>
                      💡 Recommendations:
                    </strong>
                    <p style={{ margin: '8px 0 0 0', fontSize: '14px', color: '#1e40af' }}>
                      {report.inspection.recommendations}
                    </p>
                  </div>
                )}

                {report.inspection.notes && (
                  <div style={{
                    backgroundColor: '#f3f4f6',
                    padding: '16px',
                    borderRadius: '8px',
                    border: '1px solid #d1d5db'
                  }}>
                    <strong style={{ color: '#374151', display: 'flex', alignItems: 'center', gap: '6px' }}>
                      📝 Inspector Notes:
                    </strong>
                    <p style={{ margin: '8px 0 0 0', fontSize: '14px', color: '#374151' }}>
                      {report.inspection.notes}
                    </p>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default AdminInspectionReportsEnhancement;