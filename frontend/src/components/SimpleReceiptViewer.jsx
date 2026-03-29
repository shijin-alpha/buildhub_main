import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/SimpleReceiptViewer.css';

const SimpleReceiptViewer = ({ projectId, userRole }) => {
  const toast = useToast();
  
  const [receipts, setReceipts] = useState([]);
  const [loading, setLoading] = useState(false);
  const [selectedReceipt, setSelectedReceipt] = useState(null);
  const [showImageModal, setShowImageModal] = useState(false);

  useEffect(() => {
    if (projectId) {
      loadReceipts();
    }
  }, [projectId]);

  const loadReceipts = async () => {
    if (!projectId) return;
    
    setLoading(true);
    try {
      const response = await fetch(
        `/buildhub/backend/api/get_simple_receipts.php?project_id=${projectId}`,
        { credentials: 'include' }
      );
      
      const result = await response.json();
      
      if (result.success) {
        setReceipts(result.data.receipts || []);
      } else {
        toast.error(result.message || 'Failed to load receipts');
        setReceipts([]);
      }
    } catch (error) {
      console.error('Error loading receipts:', error);
      toast.error('Failed to load receipts. Please try again.');
      setReceipts([]);
    } finally {
      setLoading(false);
    }
  };

  const handleViewReceipt = (receipt) => {
    if (receipt.mime_type.startsWith('image/')) {
      setSelectedReceipt(receipt);
      setShowImageModal(true);
    } else {
      // For PDFs, open in new tab
      const fileUrl = `/buildhub/backend/${receipt.file_path}`;
      window.open(fileUrl, '_blank');
    }
  };

  const handleDownloadReceipt = (receipt) => {
    const fileUrl = `/buildhub/backend/${receipt.file_path}`;
    const link = document.createElement('a');
    link.href = fileUrl;
    link.download = receipt.original_filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const closeImageModal = () => {
    setShowImageModal(false);
    setSelectedReceipt(null);
  };

  const getFileIcon = (mimeType) => {
    if (mimeType.startsWith('image/')) {
      return '🖼️';
    } else if (mimeType === 'application/pdf') {
      return '📄';
    }
    return '📁';
  };

  if (loading) {
    return (
      <div className="simple-receipt-viewer">
        <div className="loading-state">
          <div className="loading-spinner"></div>
          <p>Loading receipts...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="simple-receipt-viewer">
      <div className="viewer-header">
        <h3>Project Documents & Receipts</h3>
        <p className="receipt-count">
          {receipts.length} {receipts.length === 1 ? 'receipt' : 'receipts'} uploaded
        </p>
      </div>
      
      {receipts.length === 0 ? (
        <div className="empty-state">
          <div className="empty-icon">📋</div>
          <h4>No receipts uploaded yet</h4>
          <p>
            {userRole === 'contractor' 
              ? 'Upload receipts to share with the homeowner'
              : 'Receipts uploaded by the contractor will appear here'
            }
          </p>
        </div>
      ) : (
        <div className="receipts-grid">
          {receipts.map((receipt) => (
            <div key={receipt.id} className="receipt-card">
              <div className="receipt-header">
                <div className="file-icon">
                  {getFileIcon(receipt.mime_type)}
                </div>
                <div className="receipt-info">
                  <h4 className="receipt-title">{receipt.receipt_title}</h4>
                  <p className="receipt-filename">{receipt.original_filename}</p>
                </div>
              </div>
              
              {receipt.description && (
                <div className="receipt-description">
                  <p>{receipt.description}</p>
                </div>
              )}
              
              <div className="receipt-meta">
                <div className="meta-item">
                  <span className="meta-label">Uploaded by:</span>
                  <span className="meta-value">{receipt.contractor_name}</span>
                </div>
                <div className="meta-item">
                  <span className="meta-label">Date:</span>
                  <span className="meta-value">{receipt.upload_date_formatted}</span>
                </div>
                <div className="meta-item">
                  <span className="meta-label">Size:</span>
                  <span className="meta-value">{receipt.file_size_formatted}</span>
                </div>
              </div>
              
              <div className="receipt-actions">
                <button
                  className="view-button"
                  onClick={() => handleViewReceipt(receipt)}
                  disabled={!receipt.file_exists}
                >
                  {receipt.mime_type.startsWith('image/') ? 'View' : 'Open PDF'}
                </button>
                <button
                  className="download-button"
                  onClick={() => handleDownloadReceipt(receipt)}
                  disabled={!receipt.file_exists}
                >
                  Download
                </button>
              </div>
              
              {!receipt.file_exists && (
                <div className="file-missing-warning">
                  ⚠️ File not found
                </div>
              )}
            </div>
          ))}
        </div>
      )}
      
      {/* Image Modal */}
      {showImageModal && selectedReceipt && (
        <div className="image-modal-overlay" onClick={closeImageModal}>
          <div className="image-modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h4>{selectedReceipt.receipt_title}</h4>
              <button className="close-button" onClick={closeImageModal}>
                ×
              </button>
            </div>
            <div className="modal-content">
              <img
                src={`/buildhub/backend/${selectedReceipt.file_path}`}
                alt={selectedReceipt.receipt_title}
                className="receipt-image"
              />
            </div>
            <div className="modal-actions">
              <button
                className="download-button"
                onClick={() => handleDownloadReceipt(selectedReceipt)}
              >
                Download
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SimpleReceiptViewer;