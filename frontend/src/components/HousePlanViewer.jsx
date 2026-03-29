import React, { useState, useEffect, useRef } from 'react';
import '../styles/HousePlanViewer.css';

const HousePlanViewer = () => {
  const [housePlans, setHousePlans] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedPlan, setSelectedPlan] = useState(null);
  const [showPlanDetails, setShowPlanDetails] = useState(false);
  const [reviewFeedback, setReviewFeedback] = useState('');
  const [reviewStatus, setReviewStatus] = useState('');
  const [submittingReview, setSubmittingReview] = useState(false);
  const [downloadLoading, setDownloadLoading] = useState(false);
  
  // Contractor sending states
  const [showContractorModal, setShowContractorModal] = useState(false);
  const [contractors, setContractors] = useState([]);
  const [selectedContractor, setSelectedContractor] = useState('');
  const [contractorMessage, setContractorMessage] = useState('');
  const [sendingToContractor, setSendingToContractor] = useState(false);
  const [planToSend, setPlanToSend] = useState(null);
  
  const planCanvasRef = useRef(null);

  useEffect(() => {
    fetchHousePlans();
    fetchContractors();
  }, []);

  const fetchContractors = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/get_contractors.php', {
        method: 'GET',
        credentials: 'include'
      });

      if (response.ok) {
        const result = await response.json();
        if (result.success) {
          // Add name property for easier display
          const contractorsWithNames = result.contractors.map(contractor => ({
            ...contractor,
            name: `${contractor.first_name || ''} ${contractor.last_name || ''}`.trim() || contractor.email
          }));
          setContractors(contractorsWithNames);
        }
      }
    } catch (error) {
      console.error('Error fetching contractors:', error);
    }
  };

  const fetchHousePlans = async () => {
    try {
      setLoading(true);
      const response = await fetch('/buildhub/backend/api/homeowner/get_house_plans.php', {
        method: 'GET',
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      
      if (result.success) {
        setHousePlans(result.plans || []);
      } else {
        console.error('Failed to fetch house plans:', result.message);
      }
    } catch (error) {
      console.error('Error fetching house plans:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleViewPlan = (plan) => {
    setSelectedPlan(plan);
    setShowPlanDetails(true);
    setReviewFeedback(plan.review_info?.feedback || '');
    setReviewStatus(plan.review_info?.status || 'pending');
  };

  const handleSubmitReview = async () => {
    if (!selectedPlan || !reviewStatus) return;

    setSubmittingReview(true);
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/review_house_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          house_plan_id: selectedPlan.id,
          status: reviewStatus,
          feedback: reviewFeedback
        })
      });

      const result = await response.json();
      
      if (result.success) {
        // Update the plan in the list
        setHousePlans(prev => prev.map(plan => 
          plan.id === selectedPlan.id 
            ? { 
                ...plan, 
                review_info: { 
                  status: reviewStatus, 
                  feedback: reviewFeedback, 
                  reviewed_at: new Date().toISOString() 
                } 
              }
            : plan
        ));
        
        setShowPlanDetails(false);
        alert('Review submitted successfully!');
      } else {
        alert('Failed to submit review: ' + result.message);
      }
    } catch (error) {
      console.error('Error submitting review:', error);
      alert('Error submitting review. Please try again.');
    } finally {
      setSubmittingReview(false);
    }
  };

  // Contractor sending functions
  const handleSendToContractor = (plan) => {
    setPlanToSend(plan);
    setSelectedContractor('');
    setContractorMessage(`Hi! I'd like to get a construction estimate for this house plan: "${plan.plan_name}". Please review the attached technical details and layout images.`);
    setShowContractorModal(true);
  };

  const handleContractorSubmit = async () => {
    if (!selectedContractor || !planToSend) {
      alert('Please select a contractor');
      return;
    }

    setSendingToContractor(true);
    try {
      // Prepare house plan data for contractor
      const housePlanData = {
        house_plan_id: planToSend.id,
        plan_name: planToSend.plan_name,
        plot_dimensions: `${planToSend.plot_width}' × ${planToSend.plot_height}'`,
        total_area: planToSend.total_area,
        technical_details: planToSend.technical_details,
        plan_data: planToSend.plan_data,
        architect_info: planToSend.architect_info,
        layout_images: [], // Will be populated from technical details
        notes: planToSend.notes
      };

      // Extract layout images from technical details
      if (planToSend.technical_details?.layout_image) {
        const layoutImage = planToSend.technical_details.layout_image;
        if (layoutImage.stored) {
          housePlanData.layout_images.push({
            type: 'layout_image',
            filename: layoutImage.stored,
            original_name: layoutImage.name,
            url: `/buildhub/backend/uploads/house_plans/${layoutImage.stored}`
          });
        }
      }

      // Add other images from technical details
      ['elevation_images', 'section_drawings', 'renders_3d'].forEach(imageType => {
        if (planToSend.technical_details?.[imageType] && Array.isArray(planToSend.technical_details[imageType])) {
          planToSend.technical_details[imageType].forEach(img => {
            if (img.stored) {
              housePlanData.layout_images.push({
                type: imageType,
                filename: img.stored,
                original_name: img.name,
                url: `/buildhub/backend/uploads/house_plans/${img.stored}`
              });
            }
          });
        }
      });

      const response = await fetch('/buildhub/backend/api/homeowner/send_house_plan_to_contractor.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          contractor_id: parseInt(selectedContractor),
          house_plan_data: housePlanData,
          message: contractorMessage
        })
      });

      const result = await response.json();
      
      if (result.success) {
        alert(`House plan sent successfully to ${result.contractor_name}!`);
        setShowContractorModal(false);
        setPlanToSend(null);
      } else {
        alert('Failed to send house plan: ' + result.message);
      }
    } catch (error) {
      console.error('Error sending to contractor:', error);
      alert('Error sending to contractor. Please try again.');
    } finally {
      setSendingToContractor(false);
    }
  };

  const formatCurrency = (amount) => {
    if (!amount) return 'Not specified';
    return `₹${amount.toLocaleString()}`;
  };

  const getStatusBadge = (status) => {
    const statusClasses = {
      'pending': 'status-pending',
      'approved': 'status-approved',
      'rejected': 'status-rejected',
      'revision_requested': 'status-revision'
    };
    
    const statusLabels = {
      'pending': 'Pending Review',
      'approved': 'Approved',
      'rejected': 'Rejected',
      'revision_requested': 'Revision Requested'
    };

    return (
      <span className={`status-badge ${statusClasses[status] || 'status-pending'}`}>
        {statusLabels[status] || status}
      </span>
    );
  };

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
      pdf.text(`Total Area: ${plan.total_area?.toFixed(0)} sq ft`, 20, yPos);
      yPos += 10;
      pdf.text(`Number of Rooms: ${plan.plan_data?.rooms?.length || 0}`, 20, yPos);
      yPos += 10;
      pdf.text(`Architect: ${plan.architect_info?.name}`, 20, yPos);
      yPos += 20;
      
      // Add technical details if available
      if (plan.technical_details && Object.keys(plan.technical_details).length > 0) {
        pdf.setFontSize(14);
        pdf.text('Technical Specifications:', 20, yPos);
        yPos += 10;
        
        pdf.setFontSize(10);
        const techDetails = plan.technical_details;
        
        if (techDetails.construction_cost) {
          pdf.text(`Estimated Cost: ${techDetails.construction_cost}`, 20, yPos);
          yPos += 8;
        }
        if (techDetails.construction_duration) {
          pdf.text(`Duration: ${techDetails.construction_duration}`, 20, yPos);
          yPos += 8;
        }
        if (techDetails.foundation_type) {
          pdf.text(`Foundation: ${techDetails.foundation_type}`, 20, yPos);
          yPos += 8;
        }
        if (techDetails.structure_type) {
          pdf.text(`Structure: ${techDetails.structure_type}`, 20, yPos);
          yPos += 8;
        }
        if (techDetails.wall_material) {
          pdf.text(`Wall Material: ${techDetails.wall_material}`, 20, yPos);
          yPos += 8;
        }
        
        yPos += 10;
      }
      
      // Add room details
      if (plan.plan_data?.rooms && plan.plan_data.rooms.length > 0) {
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
      
      // Add canvas visualization if available
      if (planCanvasRef.current) {
        try {
          // Dynamic import for html2canvas
          const html2canvas = (await import('html2canvas')).default;
          const canvas = await html2canvas(planCanvasRef.current);
          const imgData = canvas.toDataURL('image/png');
          
          // Add new page for the plan visualization
          pdf.addPage();
          pdf.setFontSize(14);
          pdf.text('Plan Layout:', 20, 20);
          
          // Calculate dimensions to fit the page
          const imgWidth = 250;
          const imgHeight = (canvas.height * imgWidth) / canvas.width;
          
          pdf.addImage(imgData, 'PNG', 20, 30, imgWidth, imgHeight);
        } catch (error) {
          console.error('Error adding canvas to PDF:', error);
        }
      }
      
      // Save the PDF
      pdf.save(`${plan.plan_name.replace(/[^a-z0-9]/gi, '_')}_house_plan.pdf`);
      
    } catch (error) {
      console.error('Error generating PDF:', error);
      alert('Error generating PDF. Please try again.');
    } finally {
      setDownloadLoading(false);
    }
  };

  const downloadPlanAsImage = async (plan) => {
    setDownloadLoading(true);
    try {
      if (planCanvasRef.current) {
        // Dynamic import for html2canvas
        const html2canvas = (await import('html2canvas')).default;
        const canvas = await html2canvas(planCanvasRef.current, {
          backgroundColor: '#ffffff',
          scale: 2 // Higher resolution
        });
        
        // Create download link
        const link = document.createElement('a');
        link.download = `${plan.plan_name.replace(/[^a-z0-9]/gi, '_')}_layout.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
      } else {
        alert('Plan visualization not available for download.');
      }
    } catch (error) {
      console.error('Error generating image:', error);
      alert('Error generating image. Please try again.');
    } finally {
      setDownloadLoading(false);
    }
  };

  const downloadPlanAsJSON = (plan) => {
    try {
      const planData = {
        plan_info: {
          name: plan.plan_name,
          plot_width: plan.plot_width,
          plot_height: plan.plot_height,
          total_area: plan.total_area,
          created_at: plan.created_at,
          updated_at: plan.updated_at
        },
        architect_info: plan.architect_info,
        technical_details: plan.technical_details,
        plan_data: plan.plan_data,
        notes: plan.notes
      };
      
      const dataStr = JSON.stringify(planData, null, 2);
      const dataBlob = new Blob([dataStr], { type: 'application/json' });
      
      const link = document.createElement('a');
      link.href = URL.createObjectURL(dataBlob);
      link.download = `${plan.plan_name.replace(/[^a-z0-9]/gi, '_')}_data.json`;
      link.click();
      
      // Clean up
      URL.revokeObjectURL(link.href);
    } catch (error) {
      console.error('Error generating JSON:', error);
      alert('Error generating JSON file. Please try again.');
    }
  };

  const renderPlanCanvas = (plan) => {
    if (!plan.plan_data?.rooms || plan.plan_data.rooms.length === 0) {
      return null;
    }

    const PIXELS_PER_FOOT = 10; // Smaller scale for overview
    const canvasWidth = (plan.plot_width * PIXELS_PER_FOOT) + 40;
    const canvasHeight = (plan.plot_height * PIXELS_PER_FOOT) + 40;

    return (
      <div className="plan-canvas-container" ref={planCanvasRef}>
        <svg width={canvasWidth} height={canvasHeight} className="plan-svg">
          {/* Plot boundary */}
          <rect
            x={20}
            y={20}
            width={plan.plot_width * PIXELS_PER_FOOT}
            height={plan.plot_height * PIXELS_PER_FOOT}
            fill="none"
            stroke="#2c3e50"
            strokeWidth="2"
          />
          
          {/* Plot dimensions */}
          <text x={20 + (plan.plot_width * PIXELS_PER_FOOT) / 2} y={15} textAnchor="middle" fontSize="10" fill="#2c3e50">
            {plan.plot_width}'
          </text>
          <text x={10} y={20 + (plan.plot_height * PIXELS_PER_FOOT) / 2} textAnchor="middle" fontSize="10" fill="#2c3e50" transform={`rotate(-90, 10, ${20 + (plan.plot_height * PIXELS_PER_FOOT) / 2})`}>
            {plan.plot_height}'
          </text>
          
          {/* Rooms */}
          {plan.plan_data.rooms.map((room, index) => {
            const x = (room.x || 0) + 20;
            const y = (room.y || 0) + 20;
            const width = (room.layout_width || 10) * PIXELS_PER_FOOT;
            const height = (room.layout_height || 10) * PIXELS_PER_FOOT;
            
            return (
              <g key={index}>
                <rect
                  x={x}
                  y={y}
                  width={width}
                  height={height}
                  fill={room.color || '#e3f2fd'}
                  stroke="#666"
                  strokeWidth="1"
                />
                <text
                  x={x + width / 2}
                  y={y + height / 2 - 5}
                  textAnchor="middle"
                  fontSize="8"
                  fill="#333"
                >
                  {room.name}
                </text>
                <text
                  x={x + width / 2}
                  y={y + height / 2 + 5}
                  textAnchor="middle"
                  fontSize="6"
                  fill="#666"
                >
                  {room.layout_width}' × {room.layout_height}'
                </text>
              </g>
            );
          })}
        </svg>
      </div>
    );
  };

  if (loading) {
    return (
      <div className="house-plan-viewer">
        <div className="loading-state">
          <div className="loading-spinner"></div>
          <p>Loading your house plans...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="house-plan-viewer">
      <div className="section-header">
        <h2>📐 House Plans</h2>
        <p>Review house plans submitted by your architect</p>
      </div>

      {housePlans.length === 0 ? (
        <div className="empty-state">
          <div className="empty-icon">🏗️</div>
          <h3>No House Plans Yet</h3>
          <p>Your architect will submit house plans here for your review.</p>
        </div>
      ) : (
        <div className="plans-grid">
          {housePlans.map(plan => (
            <div key={plan.id} className="plan-card">
              <div className="plan-header">
                <h3>{plan.plan_name}</h3>
                {getStatusBadge(plan.review_info?.status || 'pending')}
              </div>
              
              <div className="plan-summary">
                <div className="summary-item">
                  <span className="label">Plot Size:</span>
                  <span className="value">{plan.plot_width}' × {plan.plot_height}'</span>
                </div>
                <div className="summary-item">
                  <span className="label">Total Area:</span>
                  <span className="value">{plan.total_area?.toFixed(0)} sq ft</span>
                </div>
                <div className="summary-item">
                  <span className="label">Rooms:</span>
                  <span className="value">{plan.plan_data?.rooms?.length || 0}</span>
                </div>
                {plan.technical_details?.construction_cost && (
                  <div className="summary-item">
                    <span className="label">Estimated Cost:</span>
                    <span className="value">{plan.technical_details.construction_cost}</span>
                  </div>
                )}
              </div>

              <div className="plan-architect">
                <span className="architect-label">Architect:</span>
                <span className="architect-name">{plan.architect_info?.name}</span>
              </div>

              <div className="plan-actions">
                <button 
                  className="btn-primary"
                  onClick={() => handleViewPlan(plan)}
                >
                  View Details
                </button>
                
                {/* Send to Contractor Button */}
                <button 
                  className="btn-contractor"
                  onClick={() => handleSendToContractor(plan)}
                  title="Send this house plan to contractors for estimates"
                >
                  🏗️ Send to Contractor
                </button>
                
                <div className="download-actions">
                  <button 
                    className="btn-download"
                    onClick={() => downloadPlanAsPDF(plan)}
                    disabled={downloadLoading}
                    title="Download as PDF"
                  >
                    📄 PDF
                  </button>
                  <button 
                    className="btn-download"
                    onClick={() => downloadPlanAsImage(plan)}
                    disabled={downloadLoading}
                    title="Download as Image"
                  >
                    🖼️ PNG
                  </button>
                  <button 
                    className="btn-download"
                    onClick={() => downloadPlanAsJSON(plan)}
                    disabled={downloadLoading}
                    title="Download as JSON"
                  >
                    📊 JSON
                  </button>
                </div>
              </div>

              <div className="plan-dates">
                <small>Submitted: {new Date(plan.updated_at).toLocaleDateString()}</small>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Plan Details Modal */}
      {showPlanDetails && selectedPlan && (
        <div className="modal-overlay">
          <div className="plan-details-modal">
            <div className="modal-header">
              <h2>{selectedPlan.plan_name}</h2>
              <button 
                className="close-btn"
                onClick={() => setShowPlanDetails(false)}
              >
                ×
              </button>
            </div>

            <div className="modal-content">
              <div className="plan-info-grid">
                {/* Plan Visualization */}
                <div className="info-section plan-visualization">
                  <h3>🏗️ Plan Layout</h3>
                  {renderPlanCanvas(selectedPlan)}
                </div>

                {/* Basic Information */}
                <div className="info-section">
                  <h3>📏 Plan Information</h3>
                  <div className="info-grid">
                    <div className="info-item">
                      <label>Plot Dimensions:</label>
                      <span>{selectedPlan.plot_width}' × {selectedPlan.plot_height}'</span>
                    </div>
                    <div className="info-item">
                      <label>Total Area:</label>
                      <span>{selectedPlan.total_area?.toFixed(0)} sq ft</span>
                    </div>
                    <div className="info-item">
                      <label>Number of Rooms:</label>
                      <span>{selectedPlan.plan_data?.rooms?.length || 0}</span>
                    </div>
                    <div className="info-item">
                      <label>Status:</label>
                      {getStatusBadge(selectedPlan.review_info?.status || 'pending')}
                    </div>
                  </div>
                </div>

                {/* Technical Details */}
                {selectedPlan.technical_details && Object.keys(selectedPlan.technical_details).length > 0 && (
                  <div className="info-section">
                    <h3>🔧 Technical Specifications</h3>
                    <div className="technical-details">
                      {selectedPlan.technical_details.construction_cost && (
                        <div className="tech-item">
                          <label>Estimated Cost:</label>
                          <span className="cost-highlight">{selectedPlan.technical_details.construction_cost}</span>
                        </div>
                      )}
                      {selectedPlan.technical_details.construction_duration && (
                        <div className="tech-item">
                          <label>Construction Duration:</label>
                          <span>{selectedPlan.technical_details.construction_duration}</span>
                        </div>
                      )}
                      {selectedPlan.technical_details.foundation_type && (
                        <div className="tech-item">
                          <label>Foundation Type:</label>
                          <span>{selectedPlan.technical_details.foundation_type}</span>
                        </div>
                      )}
                      {selectedPlan.technical_details.structure_type && (
                        <div className="tech-item">
                          <label>Structure Type:</label>
                          <span>{selectedPlan.technical_details.structure_type}</span>
                        </div>
                      )}
                      {selectedPlan.technical_details.wall_material && (
                        <div className="tech-item">
                          <label>Wall Material:</label>
                          <span>{selectedPlan.technical_details.wall_material}</span>
                        </div>
                      )}
                      {selectedPlan.technical_details.roofing_type && (
                        <div className="tech-item">
                          <label>Roofing Type:</label>
                          <span>{selectedPlan.technical_details.roofing_type}</span>
                        </div>
                      )}
                    </div>
                  </div>
                )}

                {/* Room List */}
                {selectedPlan.plan_data?.rooms && (
                  <div className="info-section">
                    <h3>🏠 Room Details</h3>
                    <div className="rooms-list">
                      {selectedPlan.plan_data.rooms.map((room, index) => (
                        <div key={index} className="room-item">
                          <span className="room-name">{room.name}</span>
                          <span className="room-size">
                            {room.layout_width}' × {room.layout_height}' 
                            ({(room.layout_width * room.layout_height).toFixed(0)} sq ft)
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {/* Architect Information */}
                <div className="info-section">
                  <h3>👨‍💼 Architect Information</h3>
                  <div className="architect-info">
                    <div className="info-item">
                      <label>Name:</label>
                      <span>{selectedPlan.architect_info?.name}</span>
                    </div>
                    <div className="info-item">
                      <label>Email:</label>
                      <span>{selectedPlan.architect_info?.email}</span>
                    </div>
                    {selectedPlan.architect_info?.specialization && (
                      <div className="info-item">
                        <label>Specialization:</label>
                        <span>{selectedPlan.architect_info.specialization}</span>
                      </div>
                    )}
                  </div>
                </div>

                {/* Notes */}
                {selectedPlan.notes && (
                  <div className="info-section">
                    <h3>📝 Notes</h3>
                    <p className="plan-notes">{selectedPlan.notes}</p>
                  </div>
                )}
              </div>

              {/* Review Section */}
              <div className="review-section">
                <h3>📋 Your Review</h3>
                <div className="review-form">
                  <div className="form-group">
                    <label>Status:</label>
                    <select 
                      value={reviewStatus}
                      onChange={(e) => setReviewStatus(e.target.value)}
                      className="status-dropdown"
                    >
                      <option value="pending">Pending Review</option>
                      <option value="approved">✅ Approve Plan</option>
                      <option value="revision_requested">🔄 Request Revisions</option>
                      <option value="rejected">❌ Reject Plan</option>
                    </select>
                  </div>
                  
                  <div className="form-group">
                    <label>Feedback:</label>
                    <textarea
                      value={reviewFeedback}
                      onChange={(e) => setReviewFeedback(e.target.value)}
                      placeholder="Provide your feedback on the house plan..."
                      rows="4"
                      className="feedback-textarea"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div className="modal-footer">
              <div className="download-section">
                <span className="download-label">Download:</span>
                <div className="download-buttons">
                  <button 
                    className="btn-download-modal"
                    onClick={() => downloadPlanAsPDF(selectedPlan)}
                    disabled={downloadLoading}
                    title="Download complete plan as PDF"
                  >
                    📄 PDF Report
                  </button>
                  <button 
                    className="btn-download-modal"
                    onClick={() => downloadPlanAsImage(selectedPlan)}
                    disabled={downloadLoading}
                    title="Download layout as image"
                  >
                    🖼️ Layout Image
                  </button>
                  <button 
                    className="btn-download-modal"
                    onClick={() => downloadPlanAsJSON(selectedPlan)}
                    disabled={downloadLoading}
                    title="Download plan data as JSON"
                  >
                    📊 Plan Data
                  </button>
                </div>
              </div>
              
              <div className="modal-actions">
                <button 
                  className="btn-secondary"
                  onClick={() => setShowPlanDetails(false)}
                >
                  Close
                </button>
                <button 
                  className="btn-primary"
                  onClick={handleSubmitReview}
                  disabled={submittingReview}
                >
                  {submittingReview ? 'Submitting...' : 'Submit Review'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Contractor Selection Modal */}
      {showContractorModal && (
        <div className="modal-overlay">
          <div className="contractor-modal">
            <div className="modal-header">
              <h2>🏗️ Send to Contractor</h2>
              <button 
                className="close-btn"
                onClick={() => setShowContractorModal(false)}
              >
                ×
              </button>
            </div>

            <div className="modal-content">
              {planToSend && (
                <div className="plan-summary-card">
                  <h3>📐 {planToSend.plan_name}</h3>
                  <div className="summary-details">
                    <span>📏 {planToSend.plot_width}' × {planToSend.plot_height}'</span>
                    <span>🏠 {planToSend.total_area?.toFixed(0)} sq ft</span>
                    <span>🚪 {planToSend.plan_data?.rooms?.length || 0} rooms</span>
                    {planToSend.technical_details?.construction_cost && (
                      <span>💰 Est. {planToSend.technical_details.construction_cost}</span>
                    )}
                  </div>
                </div>
              )}

              <div className="contractor-selection">
                <div className="form-group">
                  <label>Select Contractor:</label>
                  <select 
                    value={selectedContractor}
                    onChange={(e) => setSelectedContractor(e.target.value)}
                    className="contractor-dropdown"
                  >
                    <option value="">Choose a contractor...</option>
                    {contractors.map(contractor => (
                      <option key={contractor.id} value={contractor.id}>
                        {contractor.name} - {contractor.specialization || 'General Contractor'}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="form-group">
                  <label>Message to Contractor:</label>
                  <textarea
                    value={contractorMessage}
                    onChange={(e) => setContractorMessage(e.target.value)}
                    placeholder="Add any specific requirements or questions..."
                    rows="4"
                    className="contractor-message"
                  />
                </div>

                <div className="included-items">
                  <h4>📋 What will be sent:</h4>
                  <ul className="items-list">
                    <li>✅ Complete house plan with dimensions</li>
                    <li>✅ Technical specifications & materials</li>
                    <li>✅ Layout images and drawings</li>
                    <li>✅ Room details and floor plans</li>
                    <li>✅ Estimated construction cost</li>
                    <li>✅ Architect information</li>
                  </ul>
                </div>
              </div>
            </div>

            <div className="modal-footer">
              <button 
                className="btn-secondary"
                onClick={() => setShowContractorModal(false)}
              >
                Cancel
              </button>
              <button 
                className="btn-primary"
                onClick={handleContractorSubmit}
                disabled={sendingToContractor || !selectedContractor}
              >
                {sendingToContractor ? 'Sending...' : '🚀 Send to Contractor'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default HousePlanViewer;