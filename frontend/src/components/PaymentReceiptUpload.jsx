import React, { useState, useRef } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/PaymentReceiptUpload.css';

/**
 * PaymentReceiptUpload Component
 * 
 * This component is used by HOMEOWNERS ONLY to upload payment receipts.
 * Contractors should use PaymentReceiptViewer to view and verify receipts.
 */
const PaymentReceiptUpload = ({ 
  paymentId, 
  paymentMethod, 
  amount, 
  onUploadComplete, 
  onCancel,
  show = false 
}) => {
  const toast = useToast();
  const fileInputRef = useRef(null);
  
  const [uploading, setUploading] = useState(false);
  const [selectedFiles, setSelectedFiles] = useState([]);
  const [uploadProgress, setUploadProgress] = useState(0);
  const [transactionDetails, setTransactionDetails] = useState({
    transaction_reference: '',
    payment_date: new Date().toISOString().split('T')[0], // Default to today
    payment_method: paymentMethod || 'bank_transfer',
    notes: ''
  });

  // Handle body scroll prevention
  React.useEffect(() => {
    if (show) {
      document.body.style.overflow = 'hidden';
      document.body.classList.add('modal-open');
    } else {
      document.body.style.overflow = '';
      document.body.classList.remove('modal-open');
    }

    return () => {
      document.body.style.overflow = '';
      document.body.classList.remove('modal-open');
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

  const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    const validFiles = files.filter(file => {
      const isValidType = file.type.startsWith('image/') || file.type === 'application/pdf';
      const isValidSize = file.size <= 10 * 1024 * 1024; // 10MB limit
      
      if (!isValidType) {
        toast.error(`${file.name} is not a valid file type. Please upload images or PDF files.`);
        return false;
      }
      
      if (!isValidSize) {
        toast.error(`${file.name} is too large. Maximum file size is 10MB.`);
        return false;
      }
      
      return true;
    });
    
    setSelectedFiles(validFiles);
  };

  const handleDragOver = (event) => {
    event.preventDefault();
    event.stopPropagation();
  };

  const handleDrop = (event) => {
    event.preventDefault();
    event.stopPropagation();
    
    const files = Array.from(event.dataTransfer.files);
    const validFiles = files.filter(file => {
      const isValidType = file.type.startsWith('image/') || file.type === 'application/pdf';
      const isValidSize = file.size <= 10 * 1024 * 1024;
      return isValidType && isValidSize;
    });
    
    setSelectedFiles(validFiles);
  };

  const removeFile = (index) => {
    setSelectedFiles(files => files.filter((_, i) => i !== index));
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setTransactionDetails(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const uploadReceipt = async () => {
    if (selectedFiles.length === 0) {
      toast.error('Please select at least one receipt file to upload');
      return;
    }

    if (!transactionDetails.transaction_reference.trim()) {
      toast.error('Please enter the transaction reference number');
      return;
    }

    if (!transactionDetails.payment_date) {
      toast.error('Please select the payment date');
      return;
    }

    if (!paymentId) {
      toast.error('Payment ID is missing. Please try again.');
      console.error('Missing paymentId:', { paymentId, amount, paymentMethod });
      return;
    }

    try {
      setUploading(true);
      setUploadProgress(0);

      const formData = new FormData();
      formData.append('payment_id', String(paymentId));
      formData.append('transaction_reference', transactionDetails.transaction_reference.trim());
      formData.append('payment_date', transactionDetails.payment_date);
      formData.append('payment_method', transactionDetails.payment_method);
      formData.append('notes', transactionDetails.notes);

      // Add all selected files
      if (selectedFiles && selectedFiles.length > 0) {
        selectedFiles.forEach((file, index) => {
          formData.append('receipt_files[]', file);
        });
      } else {
        toast.error('No valid files to upload');
        setUploading(false);
        return;
      }

      // Log FormData contents for debugging
      console.log('FormData contents:', {
        payment_id: paymentId,
        transaction_reference: transactionDetails.transaction_reference,
        payment_date: transactionDetails.payment_date,
        payment_method: transactionDetails.payment_method,
        file_count: selectedFiles.length,
        files: selectedFiles.map(f => ({ name: f.name, size: f.size, type: f.type }))
      });
      console.log('Component props:', { paymentId, paymentMethod, amount });

      // Use homeowner API for receipt upload (only homeowners should upload receipts)
      const apiEndpoint = '/buildhub/backend/api/homeowner/upload_payment_receipt.php';
      const response = await fetch(apiEndpoint, {
        method: 'POST',
        credentials: 'include',
        body: formData
        // NOTE: Don't set Content-Type header - browser will set it automatically with boundary
      });

      const data = await response.json();

      if (data.success) {
        toast.success('Receipt uploaded successfully! Payment verification is in progress.');
        setUploadProgress(100);
        
        if (onUploadComplete) {
          onUploadComplete(data.data);
        }
      } else {
        console.error('Upload failed:', data);
        toast.error('Failed to upload receipt: ' + (data.message || 'Unknown error'));
        if (data.debug) {
          console.error('Debug info:', data.debug);
        }
      }
    } catch (error) {
      console.error('Receipt upload error:', error);
      toast.error('Error uploading receipt: ' + error.message);
    } finally {
      setUploading(false);
    }
  };

  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const getPaymentMethodIcon = (method) => {
    switch (method) {
      case 'bank_transfer': return '🏦';
      case 'upi': return '📱';
      case 'cash': return '💵';
      case 'cheque': return '📝';
      default: return '💳';
    }
  };

  const getPaymentMethodName = (method) => {
    switch (method) {
      case 'bank_transfer': return 'Bank Transfer';
      case 'upi': return 'UPI Payment';
      case 'cash': return 'Cash Payment';
      case 'cheque': return 'Cheque Payment';
      default: return 'Payment';
    }
  };

  if (!show) return null;

  return (
    <div className="receipt-upload-modal">
      <div 
        className="modal-overlay"
        onClick={(e) => {
          if (e.target === e.currentTarget && !uploading) {
            onCancel();
          }
        }}
      >
        <div className="modal-content">
          <div className="modal-header">
            <div>
              <h2>{getPaymentMethodIcon(paymentMethod)} Upload Payment Receipt</h2>
            </div>
            <div className="header-actions">
              <button
                className="secondary-btn"
                onClick={onCancel}
                disabled={uploading}
              >
                Cancel
              </button>
              <button
                className="upload-btn"
                onClick={uploadReceipt}
                disabled={uploading || selectedFiles.length === 0}
              >
                {uploading ? 'Uploading...' : `Upload Receipt${selectedFiles.length > 1 ? 's' : ''}`}
              </button>
            </div>
          </div>

          <div className="modal-body">
            <div className="payment-summary">
              <h3>Payment Details</h3>
              <div className="summary-grid">
                <div className="summary-item">
                  <label>Payment Method:</label>
                  <span>{getPaymentMethodName(paymentMethod)}</span>
                </div>
                <div className="summary-item">
                  <label>Amount:</label>
                  <span>₹{amount?.toLocaleString('en-IN')}</span>
                </div>
                <div className="summary-item">
                  <label>Payment ID:</label>
                  <span>#{paymentId}</span>
                </div>
              </div>
            </div>

            <div className="transaction-details">
              <h3>Transaction Details</h3>
              <div className="form-grid">
                <div className="form-group">
                  <label htmlFor="transaction_reference">
                    Transaction Reference Number *
                  </label>
                  <input
                    type="text"
                    id="transaction_reference"
                    name="transaction_reference"
                    value={transactionDetails.transaction_reference}
                    onChange={handleInputChange}
                    placeholder={
                      paymentMethod === 'bank_transfer' ? 'NEFT/RTGS Reference Number' :
                      paymentMethod === 'upi' ? 'UPI Transaction ID' :
                      paymentMethod === 'cheque' ? 'Cheque Number' :
                      'Transaction Reference'
                    }
                    required
                  />
                  <div className="field-info">
                    {paymentMethod === 'bank_transfer' && 'Enter the NEFT/RTGS reference number from your bank'}
                    {paymentMethod === 'upi' && 'Enter the UPI transaction ID from your payment app'}
                    {paymentMethod === 'cheque' && 'Enter the cheque number'}
                    {paymentMethod === 'cash' && 'Enter any reference number or receipt number'}
                  </div>
                </div>

                <div className="form-group">
                  <label htmlFor="payment_date">Payment Date *</label>
                  <input
                    type="date"
                    id="payment_date"
                    name="payment_date"
                    value={transactionDetails.payment_date}
                    onChange={handleInputChange}
                    max={new Date().toISOString().split('T')[0]}
                    required
                  />
                  <div className="field-info">
                    Select the date when you made the payment
                  </div>
                </div>

                <div className="form-group">
                  <label htmlFor="payment_method">Payment Method *</label>
                  <select
                    id="payment_method"
                    name="payment_method"
                    value={transactionDetails.payment_method}
                    onChange={handleInputChange}
                    required
                  >
                    <option value="bank_transfer">🏦 Bank Transfer</option>
                    <option value="upi">📱 UPI Payment</option>
                    <option value="cash">💵 Cash Payment</option>
                    <option value="cheque">📝 Cheque Payment</option>
                    <option value="other">💳 Other</option>
                  </select>
                  <div className="field-info">
                    Select the method you used to make the payment
                  </div>
                </div>

                <div className="form-group full-width">
                  <label htmlFor="notes">Additional Notes</label>
                  <textarea
                    id="notes"
                    name="notes"
                    value={transactionDetails.notes}
                    onChange={handleInputChange}
                    placeholder="Any additional information about the payment..."
                    rows="3"
                    maxLength="500"
                  />
                  <div className="field-info">
                    {transactionDetails.notes.length}/500 characters
                  </div>
                </div>
              </div>
            </div>

            <div className="file-upload-section">
              <h3>Upload Receipt/Proof</h3>
              <div className="upload-instructions">
                <p>Please upload clear photos or scanned copies of your payment receipt/proof:</p>
                <ul>
                  <li>✅ Bank transfer receipt/confirmation</li>
                  <li>✅ UPI payment screenshot</li>
                  <li>✅ Cheque photo (front and back)</li>
                  <li>✅ Cash receipt from contractor</li>
                  <li>📄 Supported formats: JPG, PNG, PDF (max 10MB each)</li>
                </ul>
              </div>

              <div 
                className="file-drop-zone"
                onDragOver={handleDragOver}
                onDrop={handleDrop}
                onClick={() => fileInputRef.current?.click()}
              >
                <div className="drop-zone-content">
                  <div className="upload-icon">📁</div>
                  <p>Drag and drop files here or click to browse</p>
                  <p className="file-types">Images (JPG, PNG) or PDF files, max 10MB each</p>
                </div>
                <input
                  ref={fileInputRef}
                  type="file"
                  multiple
                  accept="image/*,.pdf"
                  onChange={handleFileSelect}
                  style={{ display: 'none' }}
                />
              </div>

              {selectedFiles.length > 0 && (
                <div className="selected-files">
                  <h4>Selected Files ({selectedFiles.length})</h4>
                  <div className="files-list">
                    {selectedFiles.map((file, index) => (
                      <div key={index} className="file-item">
                        <div className="file-info">
                          <div className="file-icon">
                            {file.type.startsWith('image/') ? '🖼️' : '📄'}
                          </div>
                          <div className="file-details">
                            <div className="file-name">{file.name}</div>
                            <div className="file-size">{formatFileSize(file.size)}</div>
                          </div>
                        </div>
                        <button
                          className="remove-file-btn"
                          onClick={() => removeFile(index)}
                          disabled={uploading}
                        >
                          ×
                        </button>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {uploading && (
                <div className="upload-progress">
                  <div className="progress-bar">
                    <div 
                      className="progress-fill" 
                      style={{ width: `${uploadProgress}%` }}
                    ></div>
                  </div>
                  <p>Uploading receipt... {uploadProgress}%</p>
                </div>
              )}
            </div>

            <div className="verification-info">
              <h4>🔍 What happens next?</h4>
              <ol>
                <li>Your receipt will be uploaded securely</li>
                <li>The contractor will be notified to verify the payment</li>
                <li>Verification typically takes 1-2 business days</li>
                <li>You'll receive a notification once verified</li>
                <li>Payment status will be updated to "Completed"</li>
              </ol>
            </div>
          </div>


        </div>
      </div>
    </div>
  );
};

export default PaymentReceiptUpload;