import React, { useState, useEffect, useRef } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/StageDocumentManager.css';

const StageDocumentManager = ({ contractorId }) => {
  const toast = useToast();
  const fileInputRef = useRef(null);
  
  const [activeTab, setActiveTab] = useState('upload');
  const [documents, setDocuments] = useState({});
  const [requirements, setRequirements] = useState({});
  const [summary, setSummary] = useState({});
  const [stageCompletion, setStageCompletion] = useState({});
  const [loading, setLoading] = useState(false);
  const [uploading, setUploading] = useState(false);
  
  // Project selection state
  const [selectedProject, setSelectedProject] = useState('');
  const [projects, setProjects] = useState([]);
  const [loadingProjects, setLoadingProjects] = useState(true);
  
  // Upload form state
  const [uploadForm, setUploadForm] = useState({
    stage_name: '',
    document_type: '',
    description: '',
    related_payment_id: null
  });
  const [selectedFiles, setSelectedFiles] = useState([]);
  const [dragActive, setDragActive] = useState(false);
  
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

  // Load contractor's projects on component mount
  useEffect(() => {
    if (contractorId) {
      loadContractorProjects();
    }
  }, [contractorId]);

  // Load documents when project changes
  useEffect(() => {
    if (selectedProject) {
      loadDocuments();
    }
  }, [selectedProject, filters]);

  const loadContractorProjects = async () => {
    if (!contractorId) return;
    
    try {
      setLoadingProjects(true);
      const response = await fetch(
        `/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id=${contractorId}`,
        { credentials: 'include' }
      );
      
      const data = await response.json();
      
      if (data.success) {
        const projects = data.data.projects || [];
        setProjects(projects);
        
        // Auto-select first project if available
        if (projects.length > 0 && !selectedProject) {
          setSelectedProject(projects[0].id);
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
        setRequirements(data.data.requirements || {});
        setSummary(data.data.summary || {});
        setStageCompletion(data.data.stage_completion || {});
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

  const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    setSelectedFiles(files);
  };

  const handleDrag = (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  };

  const handleDrop = (e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      const files = Array.from(e.dataTransfer.files);
      setSelectedFiles(files);
    }
  };

  const handleUpload = async (e) => {
    e.preventDefault();
    
    if (!selectedProject) {
      toast.error('Please select a project first');
      return;
    }
    
    if (!uploadForm.stage_name || !uploadForm.document_type) {
      toast.error('Please select stage and document type');
      return;
    }
    
    if (selectedFiles.length === 0) {
      toast.error('Please select files to upload');
      return;
    }
    
    try {
      setUploading(true);
      
      const formData = new FormData();
      formData.append('project_id', selectedProject);
      formData.append('stage_name', uploadForm.stage_name);
      formData.append('document_type', uploadForm.document_type);
      formData.append('description', uploadForm.description);
      
      if (uploadForm.related_payment_id) {
        formData.append('related_payment_id', uploadForm.related_payment_id);
      }
      
      selectedFiles.forEach((file, index) => {
        formData.append('documents[]', file);
      });
      
      const response = await fetch('/buildhub/backend/api/contractor/upload_stage_documents.php', {
        method: 'POST',
        credentials: 'include',
        body: formData
      });
      
      const data = await response.json();
      
      if (data.success) {
        toast.success(data.message);
        
        // Reset form
        setUploadForm({
          stage_name: '',
          document_type: '',
          description: '',
          related_payment_id: null
        });
        setSelectedFiles([]);
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
        
        // Reload documents
        loadDocuments();
      } else {
        toast.error('Upload failed: ' + data.message);
        if (data.data && data.data.errors && data.data.errors.length > 0) {
          data.data.errors.forEach(error => toast.error(error));
        }
      }
    } catch (error) {
      console.error('Upload error:', error);
      toast.error('Upload failed. Please try again.');
    } finally {
      setUploading(false);
    }
  };

  const getFileIcon = (filename) => {
    const extension = filename.split('.').pop().toLowerCase();
    switch (extension) {
      case 'pdf': return '📄';
      case 'jpg':
      case 'jpeg':
      case 'png': return '🖼️';
      case 'doc':
      case 'docx': return '📝';
      default: return '📎';
    }
  };

  const renderUploadTab = () => (
    <div className="upload-tab">
      <div className="upload-form-container">
        <h3>Upload Stage Documents</h3>
        
        <form onSubmit={handleUpload} className="document-upload-form">
          <div className="form-row">
            <div className="form-group">
              <label>Construction Stage *</label>
              <select
                value={uploadForm.stage_name}
                onChange={(e) => setUploadForm({...uploadForm, stage_name: e.target.value})}
                required
              >
                <option value="">Select Stage</option>
                {stages.map(stage => (
                  <option key={stage} value={stage}>{stage}</option>
                ))}
              </select>
            </div>
            
            <div className="form-group">
              <label>Document Type *</label>
              <select
                value={uploadForm.document_type}
                onChange={(e) => setUploadForm({...uploadForm, document_type: e.target.value})}
                required
              >
                <option value="">Select Type</option>
                {documentTypes.map(type => (
                  <option key={type.value} value={type.value}>{type.label}</option>
                ))}
              </select>
            </div>
          </div>
          
          <div className="form-group">
            <label>Description</label>
            <textarea
              value={uploadForm.description}
              onChange={(e) => setUploadForm({...uploadForm, description: e.target.value})}
              placeholder="Brief description of the documents..."
              rows="3"
            />
          </div>
          
          <div className="file-upload-area">
            <div 
              className={`file-drop-zone ${dragActive ? 'drag-active' : ''}`}
              onDragEnter={handleDrag}
              onDragLeave={handleDrag}
              onDragOver={handleDrag}
              onDrop={handleDrop}
              onClick={() => fileInputRef.current?.click()}
            >
              <input
                ref={fileInputRef}
                type="file"
                multiple
                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                onChange={handleFileSelect}
                style={{ display: 'none' }}
              />
              
              {selectedFiles.length > 0 ? (
                <div className="selected-files">
                  <h4>Selected Files ({selectedFiles.length})</h4>
                  {selectedFiles.map((file, index) => (
                    <div key={index} className="file-item">
                      <span className="file-icon">{getFileIcon(file.name)}</span>
                      <span className="file-name">{file.name}</span>
                      <span className="file-size">({(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="drop-zone-content">
                  <div className="upload-icon">📁</div>
                  <p>Drag and drop files here or click to browse</p>
                  <p className="file-types">Supported: PDF, JPG, PNG, DOC, DOCX (Max 10MB each)</p>
                </div>
              )}
            </div>
          </div>
          
          <div className="form-actions">
            <button 
              type="submit" 
              className="btn btn-primary"
              disabled={uploading || selectedFiles.length === 0}
            >
              {uploading ? 'Uploading...' : `Upload ${selectedFiles.length} File(s)`}
            </button>
            
            <button 
              type="button" 
              className="btn btn-secondary"
              onClick={() => {
                setSelectedFiles([]);
                if (fileInputRef.current) fileInputRef.current.value = '';
              }}
            >
              Clear
            </button>
          </div>
        </form>
      </div>
    </div>
  );

  const renderViewTab = () => (
    <div className="view-tab">
      <div className="documents-header">
        <h3>Uploaded Documents</h3>
        
        <div className="document-filters">
          <select
            value={filters.stage_name}
            onChange={(e) => setFilters({...filters, stage_name: e.target.value})}
          >
            <option value="">All Stages</option>
            {stages.map(stage => (
              <option key={stage} value={stage}>{stage}</option>
            ))}
          </select>
          
          <select
            value={filters.document_type}
            onChange={(e) => setFilters({...filters, document_type: e.target.value})}
          >
            <option value="">All Types</option>
            {documentTypes.map(type => (
              <option key={type.value} value={type.value}>{type.label}</option>
            ))}
          </select>
        </div>
      </div>
      
      {loading ? (
        <div className="loading-state">Loading documents...</div>
      ) : (
        <div className="documents-content">
          {Object.keys(documents).length === 0 ? (
            <div className="empty-state">
              <p>No documents uploaded yet.</p>
              <button 
                className="btn btn-primary"
                onClick={() => setActiveTab('upload')}
              >
                Upload Documents
              </button>
            </div>
          ) : (
            Object.entries(documents).map(([stageName, stageDocuments]) => (
              <div key={stageName} className="stage-section">
                <div className="stage-header">
                  <h4>{stageName} Stage</h4>
                  <div className="document-count">
                    {Object.values(stageDocuments).reduce((total, docs) => total + docs.length, 0)} Documents
                  </div>
                </div>
                
                {Object.entries(stageDocuments).map(([docType, docs]) => (
                  <div key={docType} className="document-type-section">
                    <h5>{documentTypes.find(t => t.value === docType)?.label || docType}</h5>
                    
                    <div className="documents-grid">
                      {docs.map((doc) => (
                        <div key={doc.id} className="document-card">
                          <div className="document-header">
                            <span className="file-icon">{getFileIcon(doc.original_filename)}</span>
                            <span className="file-name">{doc.original_filename}</span>
                          </div>
                          
                          <div className="document-details">
                            <p className="file-size">{doc.file_size_formatted}</p>
                            <p className="upload-date">
                              Uploaded: {new Date(doc.upload_date).toLocaleDateString()}
                            </p>
                            {doc.description && (
                              <p className="description">{doc.description}</p>
                            )}
                          </div>
                          
                          <div className="document-actions">
                            <button 
                              className="btn btn-sm btn-outline"
                              onClick={() => window.open(`/buildhub/backend/${doc.file_path}`, '_blank')}
                            >
                              View
                            </button>
                            <button 
                              className="btn btn-sm btn-outline"
                              onClick={() => {
                                const link = document.createElement('a');
                                link.href = `/buildhub/backend/${doc.file_path}`;
                                link.download = doc.original_filename;
                                link.click();
                              }}
                            >
                              Download
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            ))
          )}
        </div>
      )}
    </div>
  );

  const renderSummaryTab = () => (
    <div className="summary-tab">
      <h3>Document Summary</h3>
      
      <div className="summary-cards">
        {Object.entries(summary).map(([stageName, stageSummary]) => (
          <div key={stageName} className="summary-card">
            <h4>{stageName}</h4>
            <div className="summary-stats">
              <div className="stat">
                <span className="stat-value">{stageSummary.total_documents}</span>
                <span className="stat-label">Total Documents</span>
              </div>
              <div className="stat">
                <span className="stat-value">{Object.keys(stageSummary.document_types || {}).length}</span>
                <span className="stat-label">Document Types</span>
              </div>
            </div>
            
            {stageSummary.document_types && (
              <div className="document-types-breakdown">
                <h5>Document Types:</h5>
                {Object.entries(stageSummary.document_types).map(([type, typeStats]) => (
                  <div key={type} className="type-stat">
                    <span className="type-name">
                      {documentTypes.find(t => t.value === type)?.label || type}
                    </span>
                    <span className="type-count">{typeStats.count}</span>
                  </div>
                ))}
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );

  return (
    <div className="stage-document-manager">
      {/* Project Selection */}
      <div className="project-selector" style={{ 
        marginBottom: '24px', 
        padding: '20px', 
        background: 'white', 
        borderRadius: '12px',
        boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
      }}>
        <label style={{ 
          display: 'block', 
          marginBottom: '8px', 
          fontWeight: '600', 
          color: '#2c3e50' 
        }}>
          Select Project:
        </label>
        {loadingProjects ? (
          <div>Loading projects...</div>
        ) : (
          <select
            value={selectedProject}
            onChange={(e) => setSelectedProject(e.target.value)}
            style={{
              width: '100%',
              maxWidth: '400px',
              padding: '12px',
              border: '2px solid #e9ecef',
              borderRadius: '8px',
              fontSize: '16px',
              background: 'white'
            }}
          >
            <option value="">Select a project...</option>
            {projects.map(project => (
              <option key={project.id} value={project.id}>
                {project.project_name} - {project.homeowner_name}
              </option>
            ))}
          </select>
        )}
      </div>

      {selectedProject ? (
        <>
          <div className="document-tabs">
            <button 
              className={`tab-button ${activeTab === 'upload' ? 'active' : ''}`}
              onClick={() => setActiveTab('upload')}
            >
              Upload Documents
            </button>
            <button 
              className={`tab-button ${activeTab === 'view' ? 'active' : ''}`}
              onClick={() => setActiveTab('view')}
            >
              View Documents
            </button>
            <button 
              className={`tab-button ${activeTab === 'summary' ? 'active' : ''}`}
              onClick={() => setActiveTab('summary')}
            >
              Summary
            </button>
          </div>
          
          <div className="tab-content">
            {activeTab === 'upload' && renderUploadTab()}
            {activeTab === 'view' && renderViewTab()}
            {activeTab === 'summary' && renderSummaryTab()}
          </div>
        </>
      ) : (
        <div style={{
          textAlign: 'center',
          padding: '60px 20px',
          color: '#6c757d',
          background: 'white',
          borderRadius: '12px',
          boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
        }}>
          <div style={{ fontSize: '48px', marginBottom: '16px' }}>📁</div>
          <h3>Select a Project</h3>
          <p>Choose a construction project to manage its stage documents</p>
        </div>
      )}
    </div>
  );
};

export default StageDocumentManager;