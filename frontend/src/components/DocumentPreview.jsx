import React, { useState } from 'react';
import '../styles/DocumentPreview.css';

const DocumentPreview = ({ file, onFullView }) => {
  const [imageLoaded, setImageLoaded] = useState(false);
  const [imageError, setImageError] = useState(false);

  const isImage = file.original_name.toLowerCase().match(/\.(jpg|jpeg|png|gif|webp)$/);
  const isPDF = file.original_name.toLowerCase().includes('.pdf');
  
  const fileUrl = `/buildhub/backend/${file.file_path}`;

  const handleImageLoad = () => {
    setImageLoaded(true);
  };

  const handleImageError = () => {
    setImageError(true);
  };

  const handlePreviewClick = (e) => {
    e.stopPropagation();
    if (onFullView) {
      onFullView(file);
    } else {
      window.open(fileUrl, '_blank');
    }
  };

  return (
    <div className="document-preview-container">
      {isImage && !imageError ? (
        <div className="image-preview-wrapper">
          <div className="image-preview-mask">
            <img
              src={fileUrl}
              alt={file.original_name}
              className={`preview-image ${imageLoaded ? 'loaded' : ''}`}
              onLoad={handleImageLoad}
              onError={handleImageError}
            />
            <div className="preview-overlay">
              <div className="preview-fade"></div>
              <button 
                className="view-full-btn"
                onClick={handlePreviewClick}
                title="Click to view full document"
              >
                <span className="btn-icon">👁️</span>
                <span className="btn-text">View Full</span>
              </button>
            </div>
          </div>
          {!imageLoaded && !imageError && (
            <div className="loading-placeholder">
              <div className="loading-spinner"></div>
              <p>Loading preview...</p>
            </div>
          )}
        </div>
      ) : isPDF ? (
        <div className="pdf-preview-wrapper">
          <div className="pdf-preview-container">
            <iframe
              src={`${fileUrl}#view=FitH&toolbar=0&navpanes=0&scrollbar=0`}
              className="pdf-preview-frame"
              title={file.original_name}
            />
            <div className="preview-overlay">
              <div className="preview-fade"></div>
              <button 
                className="view-full-btn"
                onClick={handlePreviewClick}
                title="Click to view full PDF"
              >
                <span className="btn-icon">📄</span>
                <span className="btn-text">View Full PDF</span>
              </button>
            </div>
          </div>
        </div>
      ) : (
        <div className="file-preview-placeholder">
          <div className="file-icon">
            📋
          </div>
          <p className="file-name">{file.original_name}</p>
          <button 
            className="view-full-btn"
            onClick={handlePreviewClick}
            title="Click to download file"
          >
            <span className="btn-icon">📥</span>
            <span className="btn-text">Download</span>
          </button>
        </div>
      )}
    </div>
  );
};

export default DocumentPreview;