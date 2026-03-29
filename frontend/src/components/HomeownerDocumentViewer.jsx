import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/HomeownerDocumentViewer.css';

const HomeownerDocumentViewer = ({ homeownerId }) => {
  const toast = useToast();
  
  const [documents, setDocuments] = useState({});
  const [summary, setSummary] = useState({});
  const [loading, setLoading] = useState(false);
  
  // Project state (single project for homeowner)
  const [selectedProject, setSelectedProject] = useState('');
  const [projects, setProjects] = useState([]);
  const [projectInfo, setProjectInfo] = useState(null);
  const [loadingProjects, setLoadingProjects] = useState(true);
  
  // Filter state
  const [filters, setFilters] = useState({
    stage_name: '',
    document_type: ''
  });
  
  const stages = [
    'Foundation', 'Structure', 'Brickwork', 'Roofing', 
    'Electrical', 'Plumbing', 'Finishing', 'Other'
  ];
  
  const documentTypes = [
    { value: 'receipt', label: 'Receipt' },
    { value: 'bill', label: 'Bill' },
    { value: 'invoice', label: 'Invoice' },
    { value: 'material_certificate', label: 'Material Certificate' },
    { value: 'quality_report', label: 'Quality Report' },
    { value: 'safety_certificate', label: 'Safety Certificate' },
    { value: 'permit', label: 'Permit' },
    { value: 'inspection_report', label: 'Inspection Report' },
    { value: 'other', label: 'Other' }
  ];

  // Load homeowner's projects on component mount
  useEffect(() => {
    if (homeownerId) {
      loadHomeownerProjects();
    }
  }, [homeownerId]);

  // Load documents when project changes
  useEffect(() => {
    if (selectedProject) {
      loadDocuments();
    }
  }, [selectedProject, filters]);

  const loadHomeownerProjects = async () => {
    if (!homeownerId) return;
    
    try {
      setLoadingProjects(true);
      const response = await fetch(
        `/buildhub/backend/api/homeowner/get_homeowner_projects.php?homeowner_id=${homeownerId}`,
        { credentials: 'include' }
      );
      
      const data = await response.json();
      
      if (data.success) {
        const projects = data.data.projects || [];
        setProjects(projects);
        
        // Auto-select the first (and typically only) project
        if (projects.length > 0) {
          const project = projects[0];
          setSelectedProject(project.id);
          setProjectInfo(project);
        } else {
          toast.error('No projects found for this homeowner');
        }
      } else {
        toast.error('Failed to load projects: ' + (data.message || 'Unknown error'));
        setProjects([]);
      }
    } catch (error) {
      console.error('Error loading projects:', error);
      toast.error('Failed to load projects. Please try again.');
      setProjects([]);
    } finally {
      setLoadingProjects(false);
    }
  };

  const loadDocuments = async () => {
    if (!selectedProject) return;
    
    try {
      setLoading(true);
      const params = new URLSearchParams({
        project_id: selectedProject,
        ...filters
      });
      
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_stage_documents.php?${params}`,
        { credentials: 'include' }
      );
      
      const data = await response.json();
      
      if (data.success) {
        setDocuments(data.data.documents || {});
        setSummary(data.data.summary || {});
        
        // Count total documents
        const totalDocs = Object.values(data.data.documents || {}).reduce((total, stageData) => {
          return total + Object.values(stageData).reduce((stageTotal, typeData) => {
            return stageTotal + (Array.isArray(typeData) ? typeData.length : 0);
          }, 0);
        }, 0);
        
        if (totalDocs > 0) {
          toast.success(`Loaded ${totalDocs} document${totalDocs > 1 ? 's' : ''} from contractor`);
        }
      } else {
        toast.error('Failed to load documents: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading documents:', error);
      toast.error('Failed to load documents. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  const getDocumentTypeIcon = (type) => {
    const icons = {
      'receipt': '🧾',
      'bill': '📄',
      'invoice': '📋',
      'material_certificate': '📜',
      'quality_report': '📊',
      'safety_certificate': '🛡️',
      'permit': '📝',
      'inspection_report': '🔍',
      'other': '📁'
    };
    return icons[type] || '📁';
  };

  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const handleDocumentView = (filePath) => {
    window.open(`/buildhub/backend/${filePath}`, '_blank');
  };

  const handleDocumentDownload = (filePath, filename) => {
    const link = document.createElement('a');
    link.href = `/buildhub/backend/${filePath}`;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const renderDocumentCard = (doc) => {
    const typeIcon = getDocumentTypeIcon(doc.document_type);
    
    return (
      <div key={doc.id} className="document-card">
        <div className="document-header">
          <div className="document-icon">
            {typeIcon}
          </div>
          <div className="document-info">
            <h4 className="document-title">{doc.original_filename}</h4>
            <p className="document-meta">
              {documentTypes.find(t => t.value === doc.document_type)?.label || doc.document_type}
              {doc.description && ` • ${doc.description}`}
            </p>
          </div>
        </div>
        
        <div className="document-details">
          <div className="detail-row">
            <span className="detail-label">Uploaded by:</span>
            <span className="detail-value">{doc.contractor_name}</span>
          </div>
          <div className="detail-row">
            <span className="detail-label">Upload Date:</span>
            <span className="detail-value">{formatDate(doc.upload_date)}</span>
          </div>
          <div className="detail-row">
            <span className="detail-label">File Size:</span>
            <span className="detail-value">{formatFileSize(doc.file_size)}</span>
          </div>
        </div>
        
        <div className="document-actions">
          <button 
            className="action-btn view-btn"
            onClick={() => handleDocumentView(doc.file_path)}
          >
            👁️ View
          </button>
          <button 
            className="action-btn download-btn"
            onClick={() => handleDocumentDownload(doc.file_path, doc.original_filename)}
          >
            📥 Download
          </button>
        </div>
      </div>
    );
  };

  const renderStageSection = (stageName, stageData) => {
    const stageDocuments = [];
    
    // Collect all documents for this stage
    Object.entries(stageData).forEach(([docType, docs]) => {
      if (Array.isArray(docs)) {
        stageDocuments.push(...docs);
      }
    });
    
    if (stageDocuments.length === 0) return null;
    
    return (
      <div key={stageName} className="stage-section">
        <div className="stage-header">
          <h3 className="stage-title">🏗️ {stageName} Stage</h3>
          <div className="stage-stats">
            <span className="stat-item">
              📄 {stageDocuments.length} document{stageDocuments.length !== 1 ? 's' : ''}
            </span>
          </div>
        </div>
        
        <div className="documents-grid">
          {stageDocuments.map(doc => renderDocumentCard(doc))}
        </div>
      </div>
    );
  };

  if (loadingProjects) {
    return (
      <div className="loading-container">
        <div className="loading-spinner">🔄</div>
        <p>Loading your projects...</p>
      </div>
    );
  }

  return (
    <div className="homeowner-document-viewer">
      <div className="viewer-header">
        <h2>📁 Project Documents</h2>
        <p>View documents uploaded by your contractor for each construction stage</p>
        {projectInfo && (
          <div className="project-info">
            <h3>🏠 {projectInfo.project_name || `Project ${projectInfo.id}`}</h3>
          </div>
        )}
      </div>
      
      {selectedProject && (
        <>
          {/* Filters */}
          <div className="filters-section">
            <div className="filter-group">
              <select 
                value={filters.stage_name} 
                onChange={(e) => setFilters({...filters, stage_name: e.target.value})}
                className="filter-select"
              >
                <option value="">All Stages</option>
                {stages.map(stage => (
                  <option key={stage} value={stage}>{stage}</option>
                ))}
              </select>
              
              <select 
                value={filters.document_type} 
                onChange={(e) => setFilters({...filters, document_type: e.target.value})}
                className="filter-select"
              >
                <option value="">All Types</option>
                {documentTypes.map(type => (
                  <option key={type.value} value={type.value}>{type.label}</option>
                ))}
              </select>
            </div>
          </div>
          
          {/* Documents Display */}
          {loading ? (
            <div className="loading-container">
              <div className="loading-spinner">🔄</div>
              <p>Loading documents...</p>
            </div>
          ) : Object.keys(documents).length === 0 ? (
            <div className="no-documents">
              <div className="no-documents-icon">📋</div>
              <h3>No Documents Found</h3>
              <p>Your contractor hasn't uploaded any documents yet for this project.</p>
            </div>
          ) : (
            <div className="documents-container">
              {Object.entries(documents).map(([stageName, stageData]) => 
                renderStageSection(stageName, stageData)
              )}
            </div>
          )}
        </>
      )}
    </div>
  );
};

export default HomeownerDocumentViewer;