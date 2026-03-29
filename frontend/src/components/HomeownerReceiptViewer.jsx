import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/HomeownerReceiptViewer.css';

const HomeownerReceiptViewer = ({ homeownerId }) => {
  const toast = useToast();
  
  const [receipts, setReceipts] = useState([]);
  const [statistics, setStatistics] = useState({});
  const [loading, setLoading] = useState(true);
  const [selectedReceipt, setSelectedReceipt] = useState(null);
  const [showModal, setShowModal] = useState(false);

  useEffect(() => {
    loadContractorReceipts();
  }, [homeownerId]);

  const loadContractorReceipts = async () => {
    try {
      setLoading(true);
      
      const response = await fetch('/buildhub/backend/api/homeowner/get_contractor_receipts.php', {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setReceipts(result.data.receipts || []);
        setStatistics(result.data.statistics || {});
        
        if (result.data.receipts && result.data.receipts.length > 0) {
          toast.success(`Loaded ${result.data.receipts.length} receipt${result.data.receipts.length > 1 ? 's' : ''}`);
        }
      } else {
        toast.error('Failed to fetch receipts: ' + result.message);
      }
    } catch (error) {
      console.error('Error fetching receipts:', error);
      toast.error('Error fetching receipts: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  const openReceiptModal = (receipt) => {
    setSelectedReceipt(receipt);
    setShowModal(true);
  };

  const closeReceiptModal = () => {
    setSelectedReceipt(null);
    setShowModal(false);
  };

  const getFileIcon = (fileIcon) => {
    switch (fileIcon) {
      case 'pdf':
        return '📄';
      case 'image':
        return '🖼️';
      default:
        return '📎';
    }
  };

  const downloadReceipt = (receipt) => {
    const link = document.createElement('a');
    link.href = `/buildhub/backend/${receipt.file_path}`;
    link.download = receipt.original_filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  if (loading) {
    return (
      <div className="homeowner-receipt-viewer">
        <div className="loading-state">
          <div className="spinner"></div>
          <p>Loading receipts...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="homeowner-receipt-viewer">
      <div className="viewer-header">
        <h3>Documents & Files</h3>
        <p>Receipts and documents uploaded by your contractors</p>
      </div>

      {statistics.total_receipts > 0 && (
        <div className="receipt-statistics">
          <div className="stat-card">
            <div className="stat-number">{statistics.total_receipts}</div>
            <div className="stat-label">Total Receipts</div>
          </div>
          <div className="stat-card">
            <div className="stat-number">{statistics.projects_with_receipts}</div>
            <div className="stat-label">Projects</div>
          </div>
          <div className="stat-card">
            <div className="stat-number">{statistics.total_size_formatted}</div>
            <div className="stat-label">Total Size</div>
          </div>
        </div>
      )}

      {receipts.length === 0 ? (
        <div className="empty-state">
          <div className="empty-icon">📄</div>
          <h4>No receipts uploaded yet</h4>
          <p>Your contractors haven't uploaded any receipts yet. They will appear here once uploaded.</p>
        </div>
      ) : (
        <div className="receipts-grid">
          {receipts.map(receipt => (
            <div key={receipt.id} className="receipt-card">
              <div className="receipt-header">
                <div className="file-icon">
                  {getFileIcon(receipt.file_icon)}
                </div>
                <div className="receipt-info">
                  <h4 className="receipt-filename">{receipt.original_filename}</h4>
                  <p className="receipt-size">{receipt.file_size_formatted}</p>
                </div>
              </div>
              
              <div className="receipt-details">
                <div className="detail-row">
                  <span className="detail-label">Project:</span>
                  <span className="detail-value">{receipt.project_name}</span>
                </div>
                <div className="detail-row">
                  <span className="detail-label">Contractor:</span>
                  <span className="detail-value">{receipt.contractor_name}</span>
                </div>
                <div className="detail-row">
                  <span className="detail-label">Uploaded:</span>
                  <span className="detail-value">{receipt.upload_date_formatted}</span>
                </div>
                {receipt.description && (
                  <div className="detail-row">
                    <span className="detail-label">Description:</span>
                    <span className="detail-value">{receipt.description}</span>
                  </div>
                )}
              </div>
              
              <div className="receipt-actions">
                <button
                  className="view-btn"
                  onClick={() => openReceiptModal(receipt)}
                >
                  View
                </button>
                <button
                  className="download-btn"
                  onClick={() => downloadReceipt(receipt)}
                >
                  Download
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Receipt Modal */}
      {showModal && selectedReceipt && (
        <div className="receipt-modal-overlay" onClick={closeReceiptModal}>
          <div className="receipt-modal" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h3>{selectedReceipt.original_filename}</h3>
              <button className="close-btn" onClick={closeReceiptModal}>×</button>
            </div>
            
            <div className="modal-content">
              {selectedReceipt.file_icon === 'image' ? (
                <img
                  src={`/buildhub/backend/${selectedReceipt.file_path}`}
                  alt={selectedReceipt.original_filename}
                  className="receipt-image"
                />
              ) : (
                <div className="pdf-viewer">
                  <iframe
                    src={`/buildhub/backend/${selectedReceipt.file_path}`}
                    title={selectedReceipt.original_filename}
                    className="receipt-pdf"
                  />
                </div>
              )}
            </div>
            
            <div className="modal-footer">
              <button
                className="download-btn"
                onClick={() => downloadReceipt(selectedReceipt)}
              >
                Download
              </button>
              <button className="close-btn" onClick={closeReceiptModal}>
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default HomeownerReceiptViewer;