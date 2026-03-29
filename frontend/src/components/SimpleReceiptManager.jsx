import React, { useState, useEffect } from 'react';
import SimpleReceiptUpload from './SimpleReceiptUpload.jsx';
import SimpleReceiptViewer from './SimpleReceiptViewer.jsx';
import { useToast } from './ToastProvider.jsx';
import '../styles/SimpleReceiptManager.css';

const SimpleReceiptManager = ({ projectId, userRole = 'contractor' }) => {
  const toast = useToast();
  const [showUploadModal, setShowUploadModal] = useState(false);
  const [refreshKey, setRefreshKey] = useState(0);

  const handleUploadComplete = (uploadData) => {
    // Refresh the viewer to show new receipt
    setRefreshKey(prev => prev + 1);
    toast.success('Receipt uploaded successfully!');
  };

  const handleUploadClick = () => {
    if (userRole !== 'contractor') {
      toast.error('Only contractors can upload receipts');
      return;
    }
    setShowUploadModal(true);
  };

  if (!projectId) {
    return (
      <div className="simple-receipt-manager">
        <div className="no-project-message">
          <p>Please select a project to view receipts</p>
        </div>
      </div>
    );
  }

  return (
    <div className="simple-receipt-manager">
      <div className="manager-header">
        <div className="header-content">
          <h2>Project Documents</h2>
          <p>Simple receipt upload and viewing system</p>
        </div>
        
        {userRole === 'contractor' && (
          <button 
            className="upload-receipt-button"
            onClick={handleUploadClick}
          >
            + Upload Receipt
          </button>
        )}
      </div>
      
      <div className="manager-content">
        <SimpleReceiptViewer 
          projectId={projectId} 
          userRole={userRole}
          key={refreshKey}
        />
      </div>
      
      {/* Upload Modal */}
      <SimpleReceiptUpload
        projectId={projectId}
        show={showUploadModal}
        onUploadComplete={handleUploadComplete}
        onCancel={() => setShowUploadModal(false)}
      />
    </div>
  );
};

export default SimpleReceiptManager;