import { useState, useEffect } from 'react';
import HousePlanDrawer from './HousePlanDrawer';
import NotificationToast from './NotificationToast';
import ConfirmModal from './ConfirmModal';
import { useNotifications } from '../hooks/useNotifications';
import '../styles/HousePlanManager.css';

const HousePlanManager = ({ layoutRequestId = null, onClose }) => {
  const [view, setView] = useState('list'); // 'list', 'create', 'edit'
  const [plans, setPlans] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selectedPlan, setSelectedPlan] = useState(null);
  const [requestInfo, setRequestInfo] = useState(null);
  
  // Download functionality state
  const [downloadLoading, setDownloadLoading] = useState(false);
  
  // Confirmation modal state
  const [confirmModal, setConfirmModal] = useState({
    isOpen: false,
    type: 'danger',
    title: '',
    message: '',
    onConfirm: null
  });

  // Notification system
  const {
    notifications,
    removeNotification,
    showSuccess,
    showError,
    showInfo
  } = useNotifications();

  // Download Functions
  const downloadPlanAsPDF = async (plan) => {
    setDownloadLoading(true);
    try {
      // Dynamic import for jsPDF
      const { default: jsPDF } = await import('jspdf');
      
      const pdf = new jsPDF('l', 'mm', 'a4'); // Landscape orientation
      
      // Add title
      pdf.setFontSize(20);
      pdf.text(plan.plan_name, 20, 20);
      
      // Add basic info
      pdf.setFontSize(12);
      let yPos = 40;
      
      pdf.text(`Plot Size: ${plan.plot_width}' × ${plan.plot_height}'`, 20, yPos);
      yPos += 10;
      pdf.text(`Total Area: ${plan.total_area} sq ft`, 20, yPos);
      yPos += 10;
      pdf.text(`Number of Rooms: ${plan.plan_data?.rooms?.length || 0}`, 20, yPos);
      yPos += 10;
      pdf.text(`Status: ${plan.status.charAt(0).toUpperCase() + plan.status.slice(1)}`, 20, yPos);
      yPos += 20;
      
      // Add visual design if we can generate it
      try {
        const designImage = await generatePlanVisualization(plan);
        if (designImage) {
          pdf.addPage();
          pdf.setFontSize(14);
          pdf.text('Plan Layout:', 20, 20);
          
          // Calculate dimensions to fit the page
          const imgWidth = 250;
          const imgHeight = 150; // Fixed height for consistency
          
          pdf.addImage(designImage, 'PNG', 20, 30, imgWidth, imgHeight);
          yPos = 190; // Continue after image
        }
      } catch (error) {
        console.warn('Could not add visual design to PDF:', error);
      }
      
      // Add room details if available
      if (plan.plan_data?.rooms && plan.plan_data.rooms.length > 0) {
        if (yPos > 150) {
          pdf.addPage();
          yPos = 20;
        }
        
        pdf.setFontSize(14);
        pdf.text('Room Details:', 20, yPos);
        yPos += 10;
        
        pdf.setFontSize(10);
        plan.plan_data.rooms.forEach((room, index) => {
          const roomText = `${room.name}: ${room.layout_width}' × ${room.layout_height}' (${(room.layout_width * room.layout_height).toFixed(0)} sq ft)`;
          pdf.text(roomText, 20, yPos);
          yPos += 8;
          
          // Start new page if needed
          if (yPos > 180) {
            pdf.addPage();
            yPos = 20;
          }
        });
      }
      
      // Add notes if available
      if (plan.notes) {
        yPos += 10;
        pdf.setFontSize(14);
        pdf.text('Notes:', 20, yPos);
        yPos += 10;
        pdf.setFontSize(10);
        
        // Split notes into lines that fit the page
        const lines = pdf.splitTextToSize(plan.notes, 250);
        pdf.text(lines, 20, yPos);
      }
      
      // Save the PDF
      pdf.save(`${plan.plan_name.replace(/[^a-z0-9]/gi, '_')}_house_plan.pdf`);
      showSuccess('PDF Downloaded', 'House plan has been downloaded as PDF with visual design');
      
    } catch (error) {
      console.error('Error generating PDF:', error);
      showError('Download Failed', 'Error generating PDF. Please try again.');
    } finally {
      setDownloadLoading(false);
    }
  };

  const downloadPlanAsImage = async (plan) => {
    setDownloadLoading(true);
    try {
      const designImage = await generatePlanVisualization(plan);
      if (designImage) {
        // Create download link
        const link = document.createElement('a');
        link.download = `${plan.plan_name.replace(/[^a-z0-9]/gi, '_')}_layout.png`;
        link.href = designImage;
        link.click();
        
        showSuccess('Image Downloaded', 'House plan layout has been downloaded as PNG');
      } else {
        showError('Download Failed', 'Could not generate plan visualization');
      }
    } catch (error) {
      console.error('Error generating image:', error);
      showError('Download Failed', 'Error generating image. Please try again.');
    } finally {
      setDownloadLoading(false);
    }
  };

  // Function to generate plan visualization from plan data
  const generatePlanVisualization = async (plan) => {
    return new Promise((resolve) => {
      try {
        if (!plan.plan_data?.rooms || plan.plan_data.rooms.length === 0) {
          resolve(null);
          return;
        }

        // Create a temporary canvas for rendering
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // Set canvas size based on plot dimensions
        const PIXELS_PER_FOOT = 15; // Scale for visualization
        const MARGIN = 40;
        const canvasWidth = (plan.plot_width * PIXELS_PER_FOOT) + (MARGIN * 2);
        const canvasHeight = (plan.plot_height * PIXELS_PER_FOOT) + (MARGIN * 2);
        
        canvas.width = canvasWidth;
        canvas.height = canvasHeight;
        
        // Clear canvas with white background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvasWidth, canvasHeight);
        
        // Draw plot boundary
        ctx.strokeStyle = '#2c3e50';
        ctx.lineWidth = 3;
        ctx.strokeRect(MARGIN, MARGIN, plan.plot_width * PIXELS_PER_FOOT, plan.plot_height * PIXELS_PER_FOOT);
        
        // Add plot dimensions
        ctx.fillStyle = '#2c3e50';
        ctx.font = 'bold 14px Arial';
        ctx.textAlign = 'center';
        
        // Top dimension
        ctx.fillText(`${plan.plot_width}'`, MARGIN + (plan.plot_width * PIXELS_PER_FOOT) / 2, MARGIN - 10);
        
        // Left dimension
        ctx.save();
        ctx.translate(MARGIN - 15, MARGIN + (plan.plot_height * PIXELS_PER_FOOT) / 2);
        ctx.rotate(-Math.PI / 2);
        ctx.fillText(`${plan.plot_height}'`, 0, 0);
        ctx.restore();
        
        // Draw rooms
        plan.plan_data.rooms.forEach((room) => {
          const x = (room.x || 0) + MARGIN;
          const y = (room.y || 0) + MARGIN;
          const width = (room.layout_width || 10) * PIXELS_PER_FOOT;
          const height = (room.layout_height || 10) * PIXELS_PER_FOOT;
          
          // Room background
          ctx.fillStyle = room.color || '#e3f2fd';
          ctx.fillRect(x, y, width, height);
          
          // Room border
          ctx.strokeStyle = '#666';
          ctx.lineWidth = 1;
          ctx.strokeRect(x, y, width, height);
          
          // Room label
          ctx.fillStyle = '#333';
          ctx.font = 'bold 12px Arial';
          ctx.textAlign = 'center';
          
          const centerX = x + width / 2;
          const centerY = y + height / 2;
          
          // Room name
          ctx.fillText(room.name, centerX, centerY - 8);
          
          // Room dimensions
          ctx.font = '10px Arial';
          ctx.fillStyle = '#666';
          ctx.fillText(`${room.layout_width}' × ${room.layout_height}'`, centerX, centerY + 8);
        });
        
        // Convert canvas to data URL
        const dataURL = canvas.toDataURL('image/png', 1.0);
        resolve(dataURL);
        
      } catch (error) {
        console.error('Error generating visualization:', error);
        resolve(null);
      }
    });
  };

  const downloadPlanAsJSON = (plan) => {
    try {
      const exportData = {
        plan_info: {
          name: plan.plan_name,
          plot_width: plan.plot_width,
          plot_height: plan.plot_height,
          total_area: plan.total_area,
          status: plan.status,
          created_at: plan.created_at,
          updated_at: plan.updated_at
        },
        rooms: plan.plan_data?.rooms || [],
        notes: plan.notes,
        technical_details: plan.technical_details || null,
        request_info: requestInfo ? {
          homeowner_name: requestInfo.homeowner_name,
          budget_range: requestInfo.budget_range,
          plot_size: requestInfo.plot_size
        } : null
      };
      
      const dataStr = JSON.stringify(exportData, null, 2);
      const dataBlob = new Blob([dataStr], { type: 'application/json' });
      
      const link = document.createElement('a');
      link.href = URL.createObjectURL(dataBlob);
      link.download = `${plan.plan_name.replace(/[^a-z0-9]/gi, '_')}_data.json`;
      link.click();
      
      // Clean up
      URL.revokeObjectURL(link.href);
      
      showSuccess('JSON Downloaded', 'House plan data has been downloaded as JSON');
    } catch (error) {
      console.error('Error generating JSON:', error);
      showError('Download Failed', 'Error generating JSON file. Please try again.');
    }
  };

  // Real-time refresh functionality
  const [refreshInterval, setRefreshInterval] = useState(null);
  const [lastRefresh, setLastRefresh] = useState(Date.now());

  // Auto-refresh plans every 30 seconds when in list view
  useEffect(() => {
    if (view === 'list') {
      const interval = setInterval(() => {
        loadPlans();
        setLastRefresh(Date.now());
      }, 30000); // Refresh every 30 seconds
      
      setRefreshInterval(interval);
      
      return () => {
        if (interval) {
          clearInterval(interval);
        }
      };
    } else {
      // Clear interval when not in list view
      if (refreshInterval) {
        clearInterval(refreshInterval);
        setRefreshInterval(null);
      }
    }
  }, [view]);

  // Manual refresh function
  const handleManualRefresh = async () => {
    showInfo('Refreshing', 'Updating house plans...');
    await loadPlans();
    setLastRefresh(Date.now());
    showSuccess('Refreshed', 'House plans updated successfully');
  };

  useEffect(() => {
    loadPlans();
    if (layoutRequestId) {
      loadRequestInfo();
    }
  }, [layoutRequestId]);

  const loadPlans = async () => {
    setLoading(true);
    try {
      const url = layoutRequestId 
        ? `/buildhub/backend/api/architect/get_house_plans.php?layout_request_id=${layoutRequestId}`
        : '/buildhub/backend/api/architect/get_house_plans.php';
      
      const response = await fetch(url);
      const result = await response.json();
      
      if (result.success) {
        setPlans(result.plans);
      } else {
        console.error('Failed to load plans:', result.message);
      }
    } catch (error) {
      console.error('Error loading plans:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadRequestInfo = async () => {
    try {
      const response = await fetch(`/buildhub/backend/api/architect/get_assigned_requests.php`);
      const result = await response.json();
      
      if (result.success) {
        const assignment = result.assignments?.find(a => 
          a.layout_request_id === layoutRequestId || 
          a.layout_request?.id === layoutRequestId
        );
        if (assignment) {
          setRequestInfo(assignment);
        }
      }
    } catch (error) {
      console.error('Error loading request info:', error);
    }
  };

  const handleCreateNew = () => {
    setSelectedPlan(null);
    setView('create');
  };

  const handleEditPlan = (plan) => {
    setSelectedPlan(plan);
    setView('edit');
  };

  const handlePlanSaved = (result) => {
    setView('list');
    // Immediately refresh plans to show the latest changes
    loadPlans();
    // Show success toast notification
    showSuccess('Plan Saved', 'Your house plan has been saved successfully!');
    
    // Trigger a refresh of the parent dashboard if callback is provided
    if (window.refreshDashboard) {
      window.refreshDashboard();
    }
  };

  const handleSubmitPlan = async (planId) => {
    setConfirmModal({
      isOpen: true,
      type: 'warning',
      title: 'Submit House Plan',
      message: 'Are you sure you want to submit this plan to the homeowner? The homeowner will be notified and can review the plan. You can still edit the plan after submission if needed.',
      onConfirm: () => submitPlan(planId)
    });
  };

  const submitPlan = async (planId) => {
    try {
      const response = await fetch('/buildhub/backend/api/architect/submit_house_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ plan_id: planId })
      });

      const result = await response.json();
      
      if (result.success) {
        showSuccess('Plan Submitted', 'Plan submitted successfully! The homeowner has been notified.');
        loadPlans();
      } else {
        showError('Submission Failed', result.message || 'Failed to submit plan');
      }
    } catch (error) {
      console.error('Error submitting plan:', error);
      showError('Submission Error', 'Error submitting plan. Please try again.');
    }
  };

  const handleDeletePlan = async (planId) => {
    setConfirmModal({
      isOpen: true,
      type: 'danger',
      title: 'Delete House Plan',
      message: 'Are you sure you want to delete this plan? This action cannot be undone and the homeowner will be notified.',
      onConfirm: () => deletePlan(planId)
    });
  };

  const deletePlan = async (planId) => {
    try {
      const response = await fetch('/buildhub/backend/api/architect/delete_house_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ plan_id: planId })
      });

      const result = await response.json();
      
      if (result.success) {
        // Show toast notification for architect
        const message = result.data?.homeowner_notified 
          ? `Plan "${result.data.plan_name}" deleted successfully! The homeowner has been notified.`
          : `Plan "${result.data.plan_name}" deleted successfully!`;
        
        showSuccess('Plan Deleted', message);
        loadPlans();
      } else {
        showError('Delete Failed', result.message || 'Failed to delete plan');
      }
    } catch (error) {
      console.error('Error deleting plan:', error);
      showError('Delete Error', 'Error deleting plan. Please try again.');
    }
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      draft: { class: 'status-draft', text: 'Draft' },
      submitted: { class: 'status-submitted', text: 'Submitted' },
      approved: { class: 'status-approved', text: 'Approved' },
      rejected: { class: 'status-rejected', text: 'Rejected' }
    };
    
    const config = statusConfig[status] || statusConfig.draft;
    return <span className={`status-badge ${config.class}`}>{config.text}</span>;
  };

  if (view === 'create' || view === 'edit') {
    return (
      <HousePlanDrawer
        layoutRequestId={layoutRequestId}
        requestInfo={requestInfo}
        existingPlan={selectedPlan}
        onSave={handlePlanSaved}
        onCancel={() => setView('list')}
      />
    );
  }

  return (
    <div className="house-plan-manager">
      {/* Notification Toast System */}
      <NotificationToast
        notifications={notifications}
        onRemove={removeNotification}
      />

      {/* Confirmation Modal */}
      <ConfirmModal
        isOpen={confirmModal.isOpen}
        onClose={() => setConfirmModal({ ...confirmModal, isOpen: false })}
        onConfirm={confirmModal.onConfirm}
        title={confirmModal.title}
        message={confirmModal.message}
        type={confirmModal.type}
        confirmText={confirmModal.type === 'danger' ? 'Delete' : 'Submit'}
        cancelText="Cancel"
      />

      <div className="manager-header">
        <div className="header-content">
          <h2>House Plans</h2>
          {requestInfo && (
            <div className="request-info">
              <h3>For: {requestInfo.homeowner_name}</h3>
              <p>{requestInfo.plot_size} • {requestInfo.budget_range}</p>
            </div>
          )}
          <div className="refresh-info">
            <small>Last updated: {new Date(lastRefresh).toLocaleTimeString()}</small>
          </div>
        </div>
        <div className="header-actions">
          <button 
            onClick={handleManualRefresh} 
            className="refresh-btn"
            title="Refresh house plans"
          >
            🔄 Refresh
          </button>
          <button onClick={handleCreateNew} className="create-btn">
            Create New Plan
          </button>
          {onClose && (
            <button onClick={onClose} className="close-btn">
              Close
            </button>
          )}
        </div>
      </div>

      <div className="plans-content">
        {loading ? (
          <div className="loading">Loading plans...</div>
        ) : plans.length === 0 ? (
          <div className="empty-state">
            <div className="empty-icon">📐</div>
            <h3>No Plans Yet</h3>
            <p>Create your first custom house plan for this project</p>
            <button onClick={handleCreateNew} className="create-first-btn">
              Create First Plan
            </button>
          </div>
        ) : (
          <div className="plans-grid">
            {plans.map(plan => (
              <div key={plan.id} className="plan-card">
                <div className="plan-header">
                  <h4>{plan.plan_name}</h4>
                  {getStatusBadge(plan.status)}
                </div>
                
                <div className="plan-preview">
                  <div className="plan-stats">
                    <div className="stat">
                      <span className="stat-label">Plot Size:</span>
                      <span className="stat-value">{plan.plot_width}' × {plan.plot_height}'</span>
                    </div>
                    <div className="stat">
                      <span className="stat-label">Total Area:</span>
                      <span className="stat-value">{plan.total_area} sq ft</span>
                    </div>
                    <div className="stat">
                      <span className="stat-label">Rooms:</span>
                      <span className="stat-value">{plan.plan_data?.rooms?.length || 0}</span>
                    </div>
                  </div>
                  
                  {plan.notes && (
                    <div className="plan-notes">
                      <p>{plan.notes}</p>
                    </div>
                  )}
                </div>

                <div className="plan-meta">
                  <div className="plan-dates">
                    <small>Created: {new Date(plan.created_at).toLocaleDateString()}</small>
                    {plan.updated_at !== plan.created_at && (
                      <small>Updated: {new Date(plan.updated_at).toLocaleDateString()}</small>
                    )}
                  </div>
                </div>

                <div className="plan-actions">
                  {/* Download buttons - available for all statuses */}
                  <div className="download-actions">
                    <button 
                      onClick={() => downloadPlanAsPDF(plan)}
                      disabled={downloadLoading}
                      className="download-btn pdf-btn"
                      title="Download as PDF with visual design"
                    >
                      {downloadLoading ? '⏳' : '📄'} PDF
                    </button>
                    <button 
                      onClick={() => downloadPlanAsImage(plan)}
                      disabled={downloadLoading}
                      className="download-btn image-btn"
                      title="Download layout as image"
                    >
                      {downloadLoading ? '⏳' : '🖼️'} PNG
                    </button>
                    <button 
                      onClick={() => downloadPlanAsJSON(plan)}
                      disabled={downloadLoading}
                      className="download-btn json-btn"
                      title="Download as JSON data"
                    >
                      {downloadLoading ? '⏳' : '📊'} JSON
                    </button>
                  </div>

                  {/* Status-specific actions */}
                  {plan.status === 'draft' && (
                    <div className="status-actions">
                      <button 
                        onClick={() => handleEditPlan(plan)}
                        className="edit-btn"
                      >
                        Edit
                      </button>
                      <button 
                        onClick={() => handleSubmitPlan(plan.id)}
                        className="submit-btn"
                      >
                        Submit
                      </button>
                      <button 
                        onClick={() => handleDeletePlan(plan.id)}
                        className="delete-btn"
                      >
                        Delete
                      </button>
                    </div>
                  )}
                  
                  {plan.status === 'submitted' && (
                    <div className="status-actions">
                      <button 
                        onClick={() => handleEditPlan(plan)}
                        className="edit-btn"
                        title="Edit submitted plan"
                      >
                        Edit
                      </button>
                      <div className="submitted-info">
                        <span>Waiting for homeowner review</span>
                      </div>
                    </div>
                  )}
                  
                  {plan.status === 'approved' && (
                    <div className="status-actions">
                      <button 
                        onClick={() => handleEditPlan(plan)}
                        className="edit-btn"
                        title="Edit approved plan"
                      >
                        Edit
                      </button>
                      <div className="approved-info">
                        <span>✓ Approved by homeowner</span>
                      </div>
                    </div>
                  )}
                  
                  {plan.status === 'rejected' && (
                    <div className="status-actions">
                      <button 
                        onClick={() => handleEditPlan(plan)}
                        className="revise-btn"
                      >
                        Revise Plan
                      </button>
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default HousePlanManager;