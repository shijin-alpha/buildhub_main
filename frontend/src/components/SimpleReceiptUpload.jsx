import React, { useState, useRef } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/SimpleReceiptUpload.css';

const SimpleReceiptUpload = ({ projectId, onUploadComplete, onCancel, show = false }) => {
  const toast = useToast();
  const fileInputRef = useRef(null);
  
  const [uploading, setUploading] = useState(false);
  const [formData, setFormData] = useState({
    receipt_title: '',
    description: ''
  });
  const [selectedFile, setSelectedFile] = useState(null);
  const [dragActive, setDragActive] = useState(false);

  // Handle body scroll prevention
  React.useEffect(() => {
    if (show) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }

    return () => {
      document.body.style.overflow = '';
    };
  }, [show]);

  // Handle escape key
  React.useEffect(() => {
    const handleEscapeKey = (event) => {
      if (event.key === 'Escape' && show && !uploading) {
        onCancel();
      }
    };

    if (show) {
      document.addEventListener('keydown', handleEscapeKey);
    }

    return () => {
      document.removeEventListener('keydown', handleEscapeKey);
    };
  }, [show, uploading, onCancel]);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
      validateAndSetFile(file);
    }
  };

  const validateAndSetFile = (file) => {
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!validTypes.includes(file.type)) {
      toast.error('Invalid file type. Please upload JPG, PNG, or PDF files only.');
      return;
    }
    
    if (file.size > maxSize) {
      toast.error('File too large. Maximum size is 10MB.');
      return;
    }
    
    setSelectedFile(file);
  };

  const handleDragOver = (event) => {
    event.preventDefault();
    event.stopPropagation();
    setDragActive(true);
  };

  const handleDragLeave = (event) => {
    event.preventDefault();
    event.stopPropagation();
    setDragActive(false);
  };

  const handleDrop = (event) => {
    event.preventDefault();
    event.stopPropagation();
    setDragActive(false);
    
    const file = event.dataTransfer.files[0];
    if (file) {
      validateAndSetFile(file);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!formData.receipt_title.trim()) {
      toast.error('Please enter a receipt title');
      return;
    }
    
    if (!selectedFile) {
      toast.error('Please select a file to upload');
      return;
    }
    
    setUploading(true);
    
    try {
      const uploadData = new FormData();
      uploadData.append('project_id', projectId);
      uploadData.append('receipt_title', formData.receipt_title.trim());
      uploadData.append('description', formData.description.trim());
      uploadData.append('receipt_file', selectedFile);
      
      const response = await fetch('/buildhub/backend/api/contractor/upload_simple_receipt.php', {
        method: 'POST',
        credentials: 'include',
        body: uploadData
      });
      
      const result = await response.json();
      
      if (result.success) {
        toast.success('Receipt uploaded successfully!');
        
        // Reset form
        setFormData({ receipt_title: '', description: '' });
        setSelectedFile(null);
        if (fileInputRef.current) {
          fileInputRef.current.value = '';
        }
        
        // Notify parent component
        if (onUploadComplete) {
          onUploadComplete(result.data);
        }
        
        // Close modal
        onCancel();
      } else {
        toast.error(result.message || 'Upload failed');
      }
    } catch (error) {
      console.error('Upload error:', error);
      toast.error('Upload failed. Please try again.');
    } finally {
      setUploading(false);
    }
  };

  const removeFile = () => {
    setSelectedFile(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  if (!show) return null;

  return (
    <div className="simple-receipt-upload-overlay">
      <div className="simple-receipt-upload-modal">
        <div className="modal-header">
          <h3>Upload Receipt</h3>
          <button 
            type="button" 
            className="close-button"
            onClick={onCancel}
            disabled={uploading}
          >
            ×
          </button>
        </div>
        
        <form onSubmit={handleSubmit} className="upload-form">
          <div className="form-group">
            <label htmlFor="receipt_title">Receipt Title *</label>
            <input
              type="text"
              id="receipt_title"
              name="receipt_title"
              value={formData.receipt_title}
              onChange={handleInputChange}
              placeholder="e.g., Cement Purchase Receipt"
              required
              disabled={uploading}
            />
          </div>
          
          <div className="form-group">
            <label htmlFor="description">Description (Optional)</label>
            <textarea
              id="description"
              name="description"
              value={formData.description}
              onChange={handleInputChange}
              placeholder="Additional details about this receipt..."
              rows="3"
              disabled={uploading}
            />
          </div>
          
          <div className="form-group">
            <label>Receipt File *</label>
            <div 
              className={`file-drop-zone ${dragActive ? 'drag-active' : ''} ${selectedFile ? 'has-file' : ''}`}
              onDragOver={handleDragOver}
              onDragLeave={handleDragLeave}
              onDrop={handleDrop}
              onClick={() => !uploading && fileInputRef.current?.click()}
            >
              <input
                type="file"
                ref={fileInputRef}
                onChange={handleFileSelect}
                accept="image/*,.pdf"
                style={{ display: 'none' }}
                disabled={uploading}
              />
              
              {selectedFile ? (
                <div className="selected-file">
                  <div className="file-info">
                    <span className="file-name">{selectedFile.name}</span>
                    <span className="file-size">
                      {(selectedFile.size / 1024 / 1024).toFixed(2)} MB
                    </span>
                  </div>
                  <button
                    type="button"
                    className="remove-file"
                    onClick={(e) => {
                      e.stopPropagation();
                      removeFile();
                    }}
                    disabled={uploading}
                  >
                    Remove
                  </button>
                </div>
              ) : (
                <div className="drop-zone-content">
                  <div className="upload-icon">📁</div>
                  <p>Click to select file or drag and drop</p>
                  <small>JPG, PNG, PDF (Max 10MB)</small>
                </div>
              )}
            </div>
          </div>
          
          <div className="form-actions">
            <button
              type="button"
              className="cancel-button"
              onClick={onCancel}
              disabled={uploading}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="upload-button"
              disabled={uploading || !selectedFile || !formData.receipt_title.trim()}
            >
              {uploading ? 'Uploading...' : 'Upload Receipt'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default SimpleReceiptUpload;