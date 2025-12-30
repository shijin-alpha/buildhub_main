import React, { useState, useEffect } from 'react';
import '../styles/OCRVerificationModal.css';

const OCRVerificationModal = ({ user, isOpen, onClose, onVerificationComplete }) => {
  const [ocrData, setOcrData] = useState(null);
  const [comparisonResults, setComparisonResults] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [adminNotes, setAdminNotes] = useState('');
  const [selectedAction, setSelectedAction] = useState('');
  const [processingOCR, setProcessingOCR] = useState(false);

  useEffect(() => {
    if (isOpen && user) {
      fetchOCRData();
    }
  }, [isOpen, user]);

  const fetchOCRData = async () => {
    setLoading(true);
    setError('');
    
    try {
      const response = await fetch(`/buildhub/backend/api/admin/get_ocr_verification.php?user_id=${user.id}`);
      const result = await response.json();
      
      if (result.success) {
        setOcrData(result.ocr_data);
        setComparisonResults(result.comparison_results);
      } else {
        setError(result.message || 'Failed to fetch OCR data');
      }
    } catch (error) {
      console.error('OCR fetch error:', error);
      setError('Network error occurred');
    } finally {
      setLoading(false);
    }
  };

  const triggerOCR = async () => {
    setProcessingOCR(true);
    setError('');
    
    try {
      const response = await fetch('/buildhub/backend/api/admin/trigger_ocr.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: user.id
        })
      });
      
      const result = await response.json();
      
      if (result.success) {
        // Wait a moment then refresh OCR data
        setTimeout(() => {
          fetchOCRData();
        }, 1000);
      } else {
        setError(result.message || 'OCR processing failed');
      }
    } catch (error) {
      console.error('OCR trigger error:', error);
      setError('Network error occurred');
    } finally {
      setProcessingOCR(false);
    }
  };

  const handleVerificationAction = async (action) => {
    if (!action) return;
    
    setLoading(true);
    setError('');
    
    try {
      const response = await fetch('/buildhub/backend/api/admin/verify_ocr_data.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: user.id,
          action: action,
          admin_notes: adminNotes,
          verification_details: {
            comparison_results: comparisonResults,
            admin_decision: action,
            timestamp: new Date().toISOString()
          }
        })
      });
      
      const result = await response.json();
      
      if (result.success) {
        onVerificationComplete(result.message);
        onClose();
      } else {
        setError(result.message || 'Verification action failed');
      }
    } catch (error) {
      console.error('Verification error:', error);
      setError('Network error occurred');
    } finally {
      setLoading(false);
    }
  };

  const getMatchStatusColor = (status) => {
    switch (status) {
      case 'EXACT_MATCH':
      case 'STRONG_MATCH':
        return '#28a745';
      case 'PARTIAL_MATCH':
        return '#ffc107';
      case 'NO_MATCH':
        return '#dc3545';
      case 'OCR_ONLY':
      case 'USER_ONLY':
        return '#6c757d';
      default:
        return '#6c757d';
    }
  };

  const getMatchStatusIcon = (status) => {
    switch (status) {
      case 'EXACT_MATCH':
        return '✅';
      case 'STRONG_MATCH':
        return '✅';
      case 'PARTIAL_MATCH':
        return '⚠️';
      case 'NO_MATCH':
        return '❌';
      case 'OCR_ONLY':
        return '📄';
      case 'USER_ONLY':
        return '👤';
      default:
        return '❓';
    }
  };

  const getOverallRecommendation = () => {
    if (!comparisonResults) return null;
    
    const { overall_match, match_score, recommendations } = comparisonResults;
    
    let color = '#6c757d';
    let icon = '❓';
    
    if (overall_match === 'HIGH') {
      color = '#28a745';
      icon = '✅';
    } else if (overall_match === 'MEDIUM') {
      color = '#ffc107';
      icon = '⚠️';
    } else if (overall_match === 'LOW') {
      color = '#dc3545';
      icon = '❌';
    }
    
    return {
      color,
      icon,
      score: Math.round(match_score),
      level: overall_match,
      recommendations
    };
  };

  if (!isOpen) return null;

  return (
    <div className="ocr-modal-overlay">
      <div className="ocr-modal">
        <div className="ocr-modal-header">
          <h2>OCR Document Verification</h2>
          <button className="close-btn" onClick={onClose}>×</button>
        </div>
        
        <div className="ocr-modal-content">
          {loading && (
            <div className="loading-spinner">
              <div className="spinner"></div>
              <p>Loading OCR data...</p>
            </div>
          )}
          
          {error && (
            <div className="error-message">
              <span className="error-icon">⚠️</span>
              {error}
            </div>
          )}
          
          {user && (
            <div className="user-info-section">
              <h3>User Information</h3>
              <div className="user-details">
                <p><strong>Name:</strong> {user.first_name} {user.last_name}</p>
                <p><strong>Email:</strong> {user.email}</p>
                <p><strong>Role:</strong> {user.role}</p>
              </div>
            </div>
          )}
          
          {!ocrData && !loading && (
            <div className="no-ocr-section">
              <div className="no-ocr-message">
                <span className="info-icon">📄</span>
                <h3>No OCR Data Available</h3>
                <p>Document has not been processed yet. Click below to start OCR processing.</p>
                <button 
                  className="trigger-ocr-btn"
                  onClick={triggerOCR}
                  disabled={processingOCR}
                >
                  {processingOCR ? 'Processing...' : 'Process Document with OCR'}
                </button>
              </div>
            </div>
          )}
          
          {ocrData && comparisonResults && (
            <>
              <div className="ocr-summary-section">
                <h3>OCR Processing Summary</h3>
                <div className="ocr-summary">
                  <div className="summary-item">
                    <span className="label">Document Type:</span>
                    <span className="value">{ocrData.document_type}</span>
                  </div>
                  <div className="summary-item">
                    <span className="label">Confidence Level:</span>
                    <span className={`confidence-badge ${ocrData.confidence_level.toLowerCase()}`}>
                      {ocrData.confidence_level}
                    </span>
                  </div>
                  <div className="summary-item">
                    <span className="label">Processing Date:</span>
                    <span className="value">{new Date(ocrData.created_at).toLocaleString()}</span>
                  </div>
                </div>
              </div>
              
              {(() => {
                const recommendation = getOverallRecommendation();
                return recommendation && (
                  <div className="overall-match-section">
                    <h3>Overall Verification Result</h3>
                    <div className="overall-match" style={{ borderColor: recommendation.color }}>
                      <div className="match-header">
                        <span className="match-icon">{recommendation.icon}</span>
                        <span className="match-score" style={{ color: recommendation.color }}>
                          {recommendation.score}% Match ({recommendation.level})
                        </span>
                      </div>
                      {recommendation.recommendations && recommendation.recommendations.length > 0 && (
                        <div className="recommendations">
                          <h4>Recommendations:</h4>
                          <ul>
                            {recommendation.recommendations.map((rec, index) => (
                              <li key={index}>{rec}</li>
                            ))}
                          </ul>
                        </div>
                      )}
                    </div>
                  </div>
                );
              })()}
              
              <div className="field-comparison-section">
                <h3>Field-by-Field Comparison</h3>
                <div className="comparison-table">
                  <div className="comparison-header">
                    <div className="field-col">Field</div>
                    <div className="user-col">User Profile</div>
                    <div className="ocr-col">OCR Extracted</div>
                    <div className="match-col">Match Status</div>
                  </div>
                  
                  {comparisonResults.field_comparisons.map((comparison, index) => (
                    <div key={index} className="comparison-row">
                      <div className="field-col">
                        <strong>{comparison.field_label}</strong>
                      </div>
                      <div className="user-col">
                        {comparison.user_value || <em>Not provided</em>}
                      </div>
                      <div className="ocr-col">
                        {comparison.ocr_value || <em>Not found</em>}
                      </div>
                      <div className="match-col">
                        <div 
                          className="match-status"
                          style={{ color: getMatchStatusColor(comparison.match_status) }}
                        >
                          <span className="match-icon">
                            {getMatchStatusIcon(comparison.match_status)}
                          </span>
                          <span className="match-text">
                            {comparison.match_status.replace('_', ' ')}
                          </span>
                          {comparison.similarity_score > 0 && (
                            <span className="similarity-score">
                              ({Math.round(comparison.similarity_score * 100)}%)
                            </span>
                          )}
                        </div>
                        {comparison.notes && (
                          <div className="match-notes">{comparison.notes}</div>
                        )}
                        {comparison.recommendation && (
                          <div className="match-recommendation">
                            <strong>Recommendation:</strong> {comparison.recommendation}
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
              
              {ocrData.cleaned_text && (
                <div className="extracted-text-section">
                  <h3>Extracted Text Preview</h3>
                  <div className="extracted-text">
                    <pre>{ocrData.cleaned_text}</pre>
                  </div>
                </div>
              )}
              
              <div className="admin-notes-section">
                <h3>Admin Notes</h3>
                <textarea
                  className="admin-notes-input"
                  value={adminNotes}
                  onChange={(e) => setAdminNotes(e.target.value)}
                  placeholder="Add notes about your verification decision..."
                  rows={4}
                />
              </div>
              
              <div className="verification-actions">
                <h3>Verification Decision</h3>
                <div className="action-buttons">
                  <button
                    className="approve-btn"
                    onClick={() => handleVerificationAction('approve')}
                    disabled={loading}
                  >
                    ✅ Approve User
                  </button>
                  <button
                    className="reupload-btn"
                    onClick={() => handleVerificationAction('request_reupload')}
                    disabled={loading}
                  >
                    📄 Request Reupload
                  </button>
                  <button
                    className="reject-btn"
                    onClick={() => handleVerificationAction('reject')}
                    disabled={loading}
                  >
                    ❌ Reject User
                  </button>
                </div>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default OCRVerificationModal;