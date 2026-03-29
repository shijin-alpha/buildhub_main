import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';

const PaymentHistoryDebug = ({ contractorId }) => {
  const toast = useToast();
  
  const [debugInfo, setDebugInfo] = useState({});
  const [projects, setProjects] = useState([]);
  const [selectedProject, setSelectedProject] = useState('');
  const [paymentHistory, setPaymentHistory] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const addDebugInfo = (key, value) => {
    setDebugInfo(prev => ({
      ...prev,
      [key]: value
    }));
  };

  useEffect(() => {
    addDebugInfo('contractorId', contractorId);
    addDebugInfo('timestamp', new Date().toISOString());
    loadContractorProjects();
  }, [contractorId]);

  const loadContractorProjects = async () => {
    try {
      setLoading(true);
      addDebugInfo('loadingProjects', 'Started');
      
      const url = `/buildhub/backend/api/contractor/get_contractor_projects.php?contractor_id=${contractorId}`;
      addDebugInfo('projectsUrl', url);
      
      const response = await fetch(url, { credentials: 'include' });
      addDebugInfo('projectsResponse', {
        status: response.status,
        statusText: response.statusText,
        ok: response.ok
      });
      
      const data = await response.json();
      addDebugInfo('projectsData', data);
      
      if (data.success) {
        const projects = data.data.projects || [];
        setProjects(projects);
        addDebugInfo('projectsCount', projects.length);
        
        if (projects.length > 0) {
          setSelectedProject(projects[0].id);
          addDebugInfo('autoSelectedProject', projects[0].id);
        }
      } else {
        setError('Failed to load projects: ' + (data.message || 'Unknown error'));
        addDebugInfo('projectsError', data.message);
      }
    } catch (error) {
      console.error('Error loading projects:', error);
      setError('Network error loading projects: ' + error.message);
      addDebugInfo('projectsException', error.message);
    } finally {
      setLoading(false);
      addDebugInfo('loadingProjects', 'Completed');
    }
  };

  const loadPaymentHistory = async () => {
    if (!selectedProject) return;
    
    try {
      setLoading(true);
      addDebugInfo('loadingHistory', 'Started');
      
      const url = `/buildhub/backend/api/contractor/get_payment_history.php?project_id=${selectedProject}`;
      addDebugInfo('historyUrl', url);
      
      const response = await fetch(url, { credentials: 'include' });
      addDebugInfo('historyResponse', {
        status: response.status,
        statusText: response.statusText,
        ok: response.ok
      });
      
      const data = await response.json();
      addDebugInfo('historyData', data);
      
      if (data.success) {
        setPaymentHistory(data.data.payment_requests || []);
        addDebugInfo('historyCount', data.data.payment_requests?.length || 0);
        setError('');
      } else {
        setError('Failed to load payment history: ' + (data.message || 'Unknown error'));
        addDebugInfo('historyError', data.message);
      }
    } catch (error) {
      console.error('Error loading payment history:', error);
      setError('Network error loading payment history: ' + error.message);
      addDebugInfo('historyException', error.message);
    } finally {
      setLoading(false);
      addDebugInfo('loadingHistory', 'Completed');
    }
  };

  useEffect(() => {
    if (selectedProject) {
      loadPaymentHistory();
    }
  }, [selectedProject]);

  return (
    <div style={{ padding: '20px', fontFamily: 'monospace' }}>
      <h2>🐛 Payment History Debug</h2>
      
      {/* Debug Information */}
      <div style={{ 
        background: '#f8f9fa', 
        border: '1px solid #dee2e6', 
        borderRadius: '5px', 
        padding: '15px', 
        marginBottom: '20px' 
      }}>
        <h3>Debug Information</h3>
        <pre style={{ fontSize: '12px', overflow: 'auto', maxHeight: '300px' }}>
          {JSON.stringify(debugInfo, null, 2)}
        </pre>
      </div>

      {/* Error Display */}
      {error && (
        <div style={{ 
          background: '#f8d7da', 
          border: '1px solid #dc3545', 
          borderRadius: '5px', 
          padding: '15px', 
          marginBottom: '20px',
          color: '#721c24'
        }}>
          <h3>❌ Error</h3>
          <p>{error}</p>
        </div>
      )}

      {/* Loading State */}
      {loading && (
        <div style={{ 
          background: '#d1ecf1', 
          border: '1px solid #17a2b8', 
          borderRadius: '5px', 
          padding: '15px', 
          marginBottom: '20px',
          color: '#0c5460'
        }}>
          <h3>⏳ Loading...</h3>
        </div>
      )}

      {/* Projects Display */}
      <div style={{ marginBottom: '20px' }}>
        <h3>📋 Projects ({projects.length})</h3>
        {projects.length > 0 ? (
          <div>
            <select 
              value={selectedProject} 
              onChange={(e) => setSelectedProject(e.target.value)}
              style={{ padding: '8px', marginBottom: '10px', width: '100%' }}
            >
              <option value="">Select a project...</option>
              {projects.map(project => (
                <option key={project.id} value={project.id}>
                  {project.project_name} (ID: {project.id})
                </option>
              ))}
            </select>
            
            {selectedProject && (
              <div style={{ 
                background: '#e7f3ff', 
                border: '1px solid #b3d9ff', 
                borderRadius: '5px', 
                padding: '10px' 
              }}>
                <h4>Selected Project Details:</h4>
                <pre style={{ fontSize: '12px' }}>
                  {JSON.stringify(projects.find(p => p.id == selectedProject), null, 2)}
                </pre>
              </div>
            )}
          </div>
        ) : (
          <p>No projects found</p>
        )}
      </div>

      {/* Payment History Display */}
      <div>
        <h3>💰 Payment History ({paymentHistory.length})</h3>
        {paymentHistory.length > 0 ? (
          <div>
            {paymentHistory.map((request, index) => (
              <div key={request.id} style={{ 
                background: '#f8f9fa', 
                border: '1px solid #dee2e6', 
                borderRadius: '5px', 
                padding: '15px', 
                marginBottom: '10px' 
              }}>
                <h4>{request.stage_name} - ₹{request.requested_amount?.toLocaleString()}</h4>
                <p><strong>Status:</strong> {request.status}</p>
                <p><strong>Completion:</strong> {request.completion_percentage}%</p>
                <p><strong>Description:</strong> {request.work_description}</p>
                {request.homeowner_notes && (
                  <p><strong>Homeowner Notes:</strong> {request.homeowner_notes}</p>
                )}
              </div>
            ))}
          </div>
        ) : (
          <p>No payment history found</p>
        )}
      </div>

      {/* Manual Test Buttons */}
      <div style={{ marginTop: '30px' }}>
        <h3>🔧 Manual Tests</h3>
        <button 
          onClick={loadContractorProjects}
          style={{ 
            background: '#007bff', 
            color: 'white', 
            border: 'none', 
            padding: '10px 20px', 
            borderRadius: '5px', 
            margin: '5px',
            cursor: 'pointer'
          }}
        >
          Reload Projects
        </button>
        <button 
          onClick={loadPaymentHistory}
          disabled={!selectedProject}
          style={{ 
            background: selectedProject ? '#28a745' : '#6c757d', 
            color: 'white', 
            border: 'none', 
            padding: '10px 20px', 
            borderRadius: '5px', 
            margin: '5px',
            cursor: selectedProject ? 'pointer' : 'not-allowed'
          }}
        >
          Reload Payment History
        </button>
      </div>
    </div>
  );
};

export default PaymentHistoryDebug;