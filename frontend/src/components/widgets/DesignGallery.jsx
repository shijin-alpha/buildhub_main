import React, { useState, useEffect } from 'react';
import { DndProvider, useDrag, useDrop } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import '../../styles/Widgets.css';

// Design Item Component with Drag functionality
const DesignItem = ({ design, index, moveDesign, onView, onApprove, onReject }) => {
  const [{ isDragging }, drag] = useDrag(() => ({
    type: 'design',
    item: { id: design.id, index },
    collect: (monitor) => ({
      isDragging: monitor.isDragging(),
    }),
  }));

  const [{ isOver }, drop] = useDrop(() => ({
    accept: 'design',
    hover: (item) => {
      if (item.index !== index) {
        moveDesign(item.index, index);
        item.index = index;
      }
    },
    collect: (monitor) => ({
      isOver: monitor.isOver(),
    }),
  }));

  return (
    <div 
      ref={(node) => drag(drop(node))}
      className={`design-item ${isDragging ? 'dragging' : ''} ${isOver ? 'drop-target' : ''}`}
      style={{ opacity: isDragging ? 0.5 : 1 }}
    >
      <div className="design-preview">
        <img src={design.thumbnail || '/images/placeholder-design.svg'} alt={design.title} />
        {design.status && (
          <span className={`design-status status-${design.status.toLowerCase()}`}>
            {design.status}
          </span>
        )}
      </div>
      <div className="design-info">
        <h4>{design.title}</h4>
        <p className="design-date">{new Date(design.created_at).toLocaleDateString()}</p>
        <p className="design-architect">{design.architect_name}</p>
      </div>
      <div className="design-actions">
        <button onClick={() => onView(design)} className="btn-view" title="View Design">
          <span className="icon">👁️</span>
        </button>
        <button onClick={() => onApprove(design)} className="btn-approve" title="Approve Design">
          <span className="icon">✓</span>
        </button>
        <button onClick={() => onReject(design)} className="btn-reject" title="Request Changes">
          <span className="icon">✗</span>
        </button>
      </div>
    </div>
  );
};

// Design Collection Component
const DesignCollection = ({ title, designs, moveDesign, onView, onApprove, onReject }) => {
  return (
    <div className="design-collection">
      <h3>{title}</h3>
      <div className="designs-container">
        {designs.map((design, index) => (
          <DesignItem
            key={design.id}
            design={design}
            index={index}
            moveDesign={moveDesign}
            onView={onView}
            onApprove={onApprove}
            onReject={onReject}
          />
        ))}
        {designs.length === 0 && (
          <div className="empty-designs">
            <p>No designs in this collection</p>
          </div>
        )}
      </div>
    </div>
  );
};

// Main Design Gallery Component
const DesignGallery = ({ userId }) => {
  const [designs, setDesigns] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [viewingDesign, setViewingDesign] = useState(null);
  const [filterStatus, setFilterStatus] = useState('all');

  // Fetch designs from API
  useEffect(() => {
    const fetchDesigns = async () => {
      setLoading(true);
      try {
        // Fetch designs from the real API endpoint
        const response = await fetch('/buildhub/backend/api/architect/get_my_designs.php');
        const result = await response.json();
        
        if (result.success) {
          // Transform the API response to match our component's expected format
          const userDesigns = result.designs.map(design => ({
            id: design.id,
            title: design.design_title,
            thumbnail: null, // API doesn't provide thumbnails yet
            status: design.status,
            created_at: design.created_at,
            architect_name: 'You', // Since these are the architect's own designs
            customer: {
              name: design.client_name,
              email: design.client_email,
              requirements: design.requirements,
              plot_size: design.plot_size,
              budget_range: design.budget_range
            }
          }));
          
          // Set the designs and loading state
          setDesigns(userDesigns);
        } else {
          setError('Failed to load designs: ' + (result.message || 'Unknown error'));
        }
        setLoading(false);
      } catch (err) {
        setError('Failed to load designs. Please try again.');
        setLoading(false);
      }
    };

    fetchDesigns();
  }, [userId]);

  // Move design between positions (drag and drop functionality)
  const moveDesign = (fromIndex, toIndex) => {
    const updatedDesigns = [...designs];
    const [movedDesign] = updatedDesigns.splice(fromIndex, 1);
    updatedDesigns.splice(toIndex, 0, movedDesign);
    setDesigns(updatedDesigns);
  };

  // Handle design actions
  const handleViewDesign = (design) => {
    setViewingDesign(design);
  };

  const handleApproveDesign = (design) => {
    // In a real app, make API call to approve design
    const updatedDesigns = designs.map(d => 
      d.id === design.id ? { ...d, status: 'Approved' } : d
    );
    setDesigns(updatedDesigns);
  };

  const handleRejectDesign = (design) => {
    // In a real app, make API call to reject design
    const updatedDesigns = designs.map(d => 
      d.id === design.id ? { ...d, status: 'Rejected' } : d
    );
    setDesigns(updatedDesigns);
  };

  // Filter designs by status
  const filteredDesigns = filterStatus === 'all' 
    ? designs 
    : designs.filter(design => design.status?.toLowerCase() === filterStatus.toLowerCase());

  // Group designs by status for collections
  const newDesigns = designs.filter(d => d.status === 'New');
  const pendingDesigns = designs.filter(d => d.status === 'Pending');
  const approvedDesigns = designs.filter(d => d.status === 'Approved');

  if (loading) return <div className="loading-spinner">Loading designs...</div>;
  if (error) return <div className="error-message">{error}</div>;

  return (
    <DndProvider backend={HTML5Backend}>
      <div className="design-gallery-widget">
        <div className="gallery-header">
          <h2>Design Management</h2>
          <div className="filter-controls">
            <label htmlFor="status-filter">Filter by status:</label>
            <select 
              id="status-filter"
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
            >
              <option value="all">All Designs</option>
              <option value="new">New</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        </div>

        {filterStatus === 'all' ? (
          <>
            <DesignCollection 
              title="New Designs" 
              designs={newDesigns}
              moveDesign={moveDesign}
              onView={handleViewDesign}
              onApprove={handleApproveDesign}
              onReject={handleRejectDesign}
            />
            <DesignCollection 
              title="Pending Review" 
              designs={pendingDesigns}
              moveDesign={moveDesign}
              onView={handleViewDesign}
              onApprove={handleApproveDesign}
              onReject={handleRejectDesign}
            />
            <DesignCollection 
              title="Approved Designs" 
              designs={approvedDesigns}
              moveDesign={moveDesign}
              onView={handleViewDesign}
              onApprove={handleApproveDesign}
              onReject={handleRejectDesign}
            />
          </>
        ) : (
          <div className="designs-container filtered">
            {filteredDesigns.map((design, index) => (
              <DesignItem
                key={design.id}
                design={design}
                index={index}
                moveDesign={moveDesign}
                onView={handleViewDesign}
                onApprove={handleApproveDesign}
                onReject={handleRejectDesign}
              />
            ))}
            {filteredDesigns.length === 0 && (
              <div className="empty-designs">
                <p>No designs match the selected filter</p>
              </div>
            )}
          </div>
        )}

        {/* Design Viewer Modal */}
        {viewingDesign && (
          <div className="design-modal">
            <div className="design-modal-content">
              <button className="close-modal" onClick={() => setViewingDesign(null)}>×</button>
              <h3>{viewingDesign.title}</h3>
              <div className="design-image-large">
                <img 
                  src={viewingDesign.thumbnail || '/images/placeholder-design.svg'} 
                  alt={viewingDesign.title} 
                />
              </div>
              <div className="design-details">
                <p><strong>Architect:</strong> {viewingDesign.architect_name}</p>
                <p><strong>Date Submitted:</strong> {new Date(viewingDesign.created_at).toLocaleDateString()}</p>
                <p><strong>Status:</strong> {viewingDesign.status}</p>
              </div>
              
              {/* Customer Details Section */}
              <div className="customer-details">
                <h4>Customer Information</h4>
                {viewingDesign.customer ? (
                  <div>
                    <p><strong>Name:</strong> {viewingDesign.customer.name}</p>
                    <p><strong>Contact:</strong> {viewingDesign.customer.email}</p>
                    {viewingDesign.customer.plot_size && (
                      <p><strong>Plot Size:</strong> {viewingDesign.customer.plot_size}</p>
                    )}
                    {viewingDesign.customer.budget_range && (
                      <p><strong>Budget Range:</strong> {viewingDesign.customer.budget_range}</p>
                    )}
                    <div className="customer-requirements">
                      <p><strong>Requirements:</strong></p>
                      <p>{viewingDesign.customer.requirements}</p>
                    </div>
                  </div>
                ) : (
                  <p>No customer information available</p>
                )}
              </div>
              <div className="design-modal-actions">
                <button 
                  onClick={() => {
                    handleApproveDesign(viewingDesign);
                    setViewingDesign(null);
                  }} 
                  className="btn-approve"
                >
                  Approve Design
                </button>
                <button 
                  onClick={() => {
                    handleRejectDesign(viewingDesign);
                    setViewingDesign(null);
                  }} 
                  className="btn-reject"
                >
                  Request Changes
                </button>
              </div>
            </div>
          </div>
        )}

        <div className="gallery-instructions">
          <h4>How to use:</h4>
          <ul>
            <li>Drag and drop designs to reorder them</li>
            <li>Click the eye icon to view design details</li>
            <li>Use the checkmark to approve a design</li>
            <li>Use the X to request changes</li>
          </ul>
        </div>
      </div>
    </DndProvider>
  );
};

export default DesignGallery;