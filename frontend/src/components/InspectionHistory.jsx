import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/InspectionHistory.css';

const Icon = ({ name, size = 20, stroke = 1.8, color = 'currentColor' }) => {
  const common = { width: size, height: size, viewBox: '0 0 24 24', fill: 'none', stroke: color, strokeWidth: stroke, strokeLinecap: 'round', strokeLinejoin: 'round' };
  switch (name) {
    case 'filter':
      return (<svg {...common}><polygon points="22,3 2,3 10,12.46 10,19 14,21 14,12.46"/></svg>);
    case 'calendar':
      return (<svg {...common}><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>);
    case 'eye':
      return (<svg {...common}><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>);
    case 'camera':
      return (<svg {...common}><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>);
    case 'clipboard':
      return (<svg {...common}><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>);
    case 'check-circle':
      return (<svg {...common}><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M9 11l3 3L22 4"/></svg>);
    case 'alert-circle':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>);
    case 'x-circle':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>);
    case 'clock':
      return (<svg {...common}><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>);
    case 'star':
      return (<svg {...common}><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>);
    case 'chevron-left':
      return (<svg {...common}><path d="M15 18l-6-6 6-6"/></svg>);
    case 'chevron-right':
      return (<svg {...common}><path d="M9 18l6-6-6-6"/></svg>);
    default:
      return null;
  }
};

const InspectionHistory = () => {
  const toast = useToast();
  const [inspections, setInspections] = useState([]);
  const [stats, setStats] = useState({});
  const [loading, setLoading] = useState(false);
  const [filters, setFilters] = useState({
    status: 'all',
    date_from: '',
    date_to: '',
    project_id: ''
  });
  const [pagination, setPagination] = useState({
    limit: 20,
    offset: 0,
    has_more: false
  });
  const [selectedInspection, setSelectedInspection] = useState(null);
  const [showDetails, setShowDetails] = useState(false);

  useEffect(() => {
    fetchInspectionHistory();
  }, [filters, pagination.offset]);

  const fetchInspectionHistory = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams({
        ...filters,
        limit: pagination.limit.toString(),
        offset: pagination.offset.toString()
      });

      // Remove empty filters
      Object.keys(filters).forEach(key => {
        if (!filters[key] || filters[key] === 'all') {
          params.delete(key);
        }
      });

      const response = await fetch(`/buildhub/backend/api/inspector/get_inspection_history.php?${params}`, {
        credentials: 'include'
      });
      const data = await response.json();

      if (data.success) {
        setInspections(data.inspections);
        setStats(data.stats);
        setPagination(prev => ({
          ...prev,
          has_more: data.pagination.has_more
        }));
      } else {
        toast.error(data.message);
      }
    } catch (error) {
      console.error('Error fetching inspection history:', error);
      toast.error('Failed to fetch inspection history');
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (field, value) => {
    setFilters(prev => ({
      ...prev,
      [field]: value
    }));
    setPagination(prev => ({ ...prev, offset: 0 }));
  };

  const handlePageChange = (direction) => {
    if (direction === 'next' && pagination.has_more) {
      setPagination(prev => ({
        ...prev,
        offset: prev.offset + prev.limit
      }));
    } else if (direction === 'prev' && pagination.offset > 0) {
      setPagination(prev => ({
        ...prev,
        offset: Math.max(0, prev.offset - prev.limit)
      }));
    }
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
      default: return '#6b7280';
    }
  };

  const getTypeIcon = (type) => {
    switch (type) {
      case 'routine': return <Icon name="clipboard" color="#6b7280" />;
      case 'milestone': return <Icon name="star" color="#f59e0b" />;
      case 'quality': return <Icon name="check-circle" color="#10b981" />;
      case 'safety': return <Icon name="alert-circle" color="#ef4444" />;
      case 'final': return <Icon name="star" color="#8b5cf6" />;
      default: return <Icon name="clipboard" color="#6b7280" />;
    }
  };

  const openInspectionDetails = (inspection) => {
    setSelectedInspection(inspection);
    setShowDetails(true);
  };

  return (
    <div className="inspection-history">
      {/* Stats Cards */}
      <div className="stats-section">
        <div className="stats-cards">
          <div className="stat-card">
            <div className="stat-icon">
              <Icon name="clipboard" size={24} color="#3b82f6" />
            </div>
            <div className="stat-content">
              <h3>{stats.total_inspections || 0}</h3>
              <p>Total Inspections</p>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">
              <Icon name="check-circle" size={24} color="#10b981" />
            </div>
            <div className="stat-content">
              <h3>{stats.approved_count || 0}</h3>
              <p>Approved</p>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">
              <Icon name="alert-circle" size={24} color="#f59e0b" />
            </div>
            <div className="stat-content">
              <h3>{stats.attention_count || 0}</h3>
              <p>Need Attention</p>
            </div>
          </div>
          <div className="stat-card">
            <div className="stat-icon">
              <Icon name="star" size={24} color="#8b5cf6" />
            </div>
            <div className="stat-content">
              <h3>{stats.avg_quality_score ? parseFloat(stats.avg_quality_score).toFixed(1) : 'N/A'}</h3>
              <p>Avg Quality Score</p>
            </div>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="filters-section">
        <div className="filters-header">
          <h2>Inspection History</h2>
          <div className="filters-controls">
            <div className="filter-group">
              <label htmlFor="status-filter">Status</label>
              <select
                id="status-filter"
                value={filters.status}
                onChange={(e) => handleFilterChange('status', e.target.value)}
              >
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="needs_attention">Needs Attention</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
            
            <div className="filter-group">
              <label htmlFor="date-from">From Date</label>
              <input
                type="date"
                id="date-from"
                value={filters.date_from}
                onChange={(e) => handleFilterChange('date_from', e.target.value)}
              />
            </div>
            
            <div className="filter-group">
              <label htmlFor="date-to">To Date</label>
              <input
                type="date"
                id="date-to"
                value={filters.date_to}
                onChange={(e) => handleFilterChange('date_to', e.target.value)}
              />
            </div>
          </div>
        </div>
      </div>

      {/* Inspections List */}
      <div className="inspections-section">
        {loading ? (
          <div className="loading-state">
            <div className="spinner"></div>
            <p>Loading inspection history...</p>
          </div>
        ) : inspections.length === 0 ? (
          <div className="empty-state">
            <Icon name="clipboard" size={48} color="#9ca3af" />
            <h3>No Inspections Found</h3>
            <p>No inspection reports match your current filters.</p>
          </div>
        ) : (
          <>
            <div className="inspections-list">
              {inspections.map((inspection) => (
                <div key={inspection.id} className="inspection-card">
                  <div className="inspection-header">
                    <div className="inspection-title">
                      <h3>{inspection.project_name}</h3>
                      <p>{inspection.project_location}</p>
                    </div>
                    <div className="inspection-status" style={{ color: getStatusColor(inspection.overall_status) }}>
                      {getStatusIcon(inspection.overall_status)}
                      <span>{inspection.overall_status.replace('_', ' ').toUpperCase()}</span>
                    </div>
                  </div>

                  <div className="inspection-details">
                    <div className="detail-row">
                      <div className="detail-item">
                        <Icon name="calendar" size={16} />
                        <span>Date: {new Date(inspection.inspection_date).toLocaleDateString()}</span>
                      </div>
                      <div className="detail-item">
                        {getTypeIcon(inspection.inspection_type)}
                        <span>Type: {inspection.inspection_type.charAt(0).toUpperCase() + inspection.inspection_type.slice(1)}</span>
                      </div>
                    </div>
                    
                    <div className="detail-row">
                      <div className="detail-item">
                        <Icon name="clipboard" size={16} />
                        <span>Stage: {inspection.inspection_stage}</span>
                      </div>
                      {inspection.quality_score && (
                        <div className="detail-item">
                          <Icon name="star" size={16} />
                          <span>Quality: {inspection.quality_score}/10</span>
                        </div>
                      )}
                    </div>

                    <div className="inspection-meta">
                      <div className="meta-item">
                        <strong>Homeowner:</strong> {inspection.homeowner_name}
                      </div>
                      <div className="meta-item">
                        <strong>Contractor:</strong> {inspection.contractor_name}
                      </div>
                      <div className="meta-item">
                        <strong>Photos:</strong> {inspection.photo_count} attached
                      </div>
                      <div className="meta-item">
                        <strong>Checklist Items:</strong> {inspection.checklist_count}
                      </div>
                    </div>

                    {inspection.notes && (
                      <div className="inspection-notes">
                        <strong>Notes:</strong>
                        <p>{inspection.notes}</p>
                      </div>
                    )}
                  </div>

                  <div className="inspection-actions">
                    <button 
                      className="btn-view"
                      onClick={() => openInspectionDetails(inspection)}
                    >
                      <Icon name="eye" size={16} />
                      View Details
                    </button>
                  </div>
                </div>
              ))}
            </div>

            {/* Pagination */}
            <div className="pagination">
              <button 
                className="pagination-btn"
                onClick={() => handlePageChange('prev')}
                disabled={pagination.offset === 0}
              >
                <Icon name="chevron-left" size={16} />
                Previous
              </button>
              
              <span className="pagination-info">
                Showing {pagination.offset + 1} - {Math.min(pagination.offset + pagination.limit, pagination.offset + inspections.length)} of {stats.total_inspections || 0}
              </span>
              
              <button 
                className="pagination-btn"
                onClick={() => handlePageChange('next')}
                disabled={!pagination.has_more}
              >
                Next
                <Icon name="chevron-right" size={16} />
              </button>
            </div>
          </>
        )}
      </div>

      {/* Inspection Details Modal */}
      {showDetails && selectedInspection && (
        <div className="inspection-details-modal">
          <div className="modal-overlay" onClick={() => setShowDetails(false)}></div>
          <div className="modal-content">
            <div className="modal-header">
              <h2>Inspection Report Details</h2>
              <button className="close-btn" onClick={() => setShowDetails(false)}>
                <Icon name="x" />
              </button>
            </div>
            
            <div className="modal-body">
              <div className="inspection-info">
                <h3>{selectedInspection.project_name}</h3>
                <p><strong>Date:</strong> {new Date(selectedInspection.inspection_date).toLocaleDateString()}</p>
                <p><strong>Stage:</strong> {selectedInspection.inspection_stage}</p>
                <p><strong>Type:</strong> {selectedInspection.inspection_type}</p>
                <p><strong>Status:</strong> {selectedInspection.overall_status}</p>
                {selectedInspection.quality_score && (
                  <p><strong>Quality Score:</strong> {selectedInspection.quality_score}/10</p>
                )}
                <p><strong>Safety Compliance:</strong> {selectedInspection.safety_compliance}</p>
              </div>
              
              {selectedInspection.notes && (
                <div className="notes-section">
                  <h4>Notes</h4>
                  <p>{selectedInspection.notes}</p>
                </div>
              )}
              
              {selectedInspection.recommendations && (
                <div className="recommendations-section">
                  <h4>Recommendations</h4>
                  <p>{selectedInspection.recommendations}</p>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InspectionHistory;