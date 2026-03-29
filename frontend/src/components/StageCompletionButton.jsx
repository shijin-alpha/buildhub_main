import React, { useState } from 'react';
import { useToast } from './ToastProvider.jsx';

const StageCompletionButton = ({ 
  projectId, 
  stageName, 
  currentStatus = 'Not Started',
  onStatusUpdate,
  className = '' 
}) => {
  const toast = useToast();
  const [loading, setLoading] = useState(false);
  const [status, setStatus] = useState(currentStatus);

  const handleStatusChange = async (newStatus) => {
    if (loading) return;
    
    setLoading(true);
    
    try {
      const response = await fetch(
        'http://localhost/buildhub/backend/api/contractor/update_stage_completion.php',
        {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            project_id: projectId,
            stage_name: stageName,
            stage_status: newStatus,
            completion_percentage: newStatus === 'Completed' ? 100 : (newStatus === 'In Progress' ? 50 : 0),
            remarks: `Stage marked as ${newStatus} via quick update`
          })
        }
      );

      const result = await response.json();
      
      if (result.success) {
        setStatus(newStatus);
        toast.success(`${stageName} marked as ${newStatus}`);
        
        // Trigger progress recalculation notification
        if (result.data && result.data.project_id) {
          localStorage.setItem(`progress_update_${result.data.project_id}`, Date.now().toString());
        }
        
        if (onStatusUpdate) {
          onStatusUpdate(result.data);
        }
      } else {
        toast.error(result.message || 'Failed to update stage status');
      }
    } catch (error) {
      console.error('Error updating stage status:', error);
      toast.error('Network error updating stage status');
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (statusValue) => {
    switch (statusValue) {
      case 'Completed': return '#22c55e';
      case 'In Progress': return '#f59e0b';
      case 'Not Started': return '#6b7280';
      default: return '#6b7280';
    }
  };

  const getStatusIcon = (statusValue) => {
    switch (statusValue) {
      case 'Completed': return '✅';
      case 'In Progress': return '🔄';
      case 'Not Started': return '⏸️';
      default: return '⏸️';
    }
  };

  return (
    <div className={`stage-completion-button ${className}`}>
      <div className="stage-info">
        <span className="stage-name">{stageName}</span>
        <span 
          className="stage-status"
          style={{ 
            color: getStatusColor(status),
            fontWeight: '500'
          }}
        >
          {getStatusIcon(status)} {status}
        </span>
      </div>
      
      <div className="status-controls">
        {status !== 'In Progress' && (
          <button
            onClick={() => handleStatusChange('In Progress')}
            disabled={loading}
            className="status-btn progress-btn"
            title="Mark as In Progress"
          >
            🔄
          </button>
        )}
        
        {status !== 'Completed' && (
          <button
            onClick={() => handleStatusChange('Completed')}
            disabled={loading}
            className="status-btn complete-btn"
            title="Mark as Completed"
          >
            ✅
          </button>
        )}
        
        {status !== 'Not Started' && (
          <button
            onClick={() => handleStatusChange('Not Started')}
            disabled={loading}
            className="status-btn reset-btn"
            title="Reset to Not Started"
          >
            ⏸️
          </button>
        )}
      </div>
      
      <style jsx>{`
        .stage-completion-button {
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 12px 16px;
          background: white;
          border: 1px solid #e5e7eb;
          border-radius: 8px;
          margin-bottom: 8px;
          transition: all 0.2s ease;
        }
        
        .stage-completion-button:hover {
          border-color: #8b5cf6;
          box-shadow: 0 2px 8px rgba(139, 92, 246, 0.1);
        }
        
        .stage-info {
          display: flex;
          flex-direction: column;
          gap: 4px;
        }
        
        .stage-name {
          font-weight: 600;
          color: #374151;
          font-size: 14px;
        }
        
        .stage-status {
          font-size: 12px;
          display: flex;
          align-items: center;
          gap: 4px;
        }
        
        .status-controls {
          display: flex;
          gap: 8px;
        }
        
        .status-btn {
          width: 32px;
          height: 32px;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          font-size: 14px;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.2s ease;
          background: #f3f4f6;
        }
        
        .status-btn:hover {
          transform: scale(1.05);
        }
        
        .status-btn:disabled {
          opacity: 0.5;
          cursor: not-allowed;
          transform: none;
        }
        
        .progress-btn:hover {
          background: #fef3c7;
        }
        
        .complete-btn:hover {
          background: #dcfce7;
        }
        
        .reset-btn:hover {
          background: #f1f5f9;
        }
      `}</style>
    </div>
  );
};

export default StageCompletionButton;