import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import PaymentMethodSelector from './PaymentMethodSelector.jsx';
import PaymentReceiptUpload from './PaymentReceiptUpload.jsx';
import HomeownerDocumentViewer from './HomeownerDocumentViewer.jsx';
import DocumentPreview from './DocumentPreview.jsx';
import '../styles/HomeownerProgressReports.css';
import '../styles/PaymentDetailsModal.css';

const HomeownerProgressReports = ({ activeTab, isVisible = true }) => {
  const toast = useToast();
  
  // User state
  const [homeownerId, setHomeownerId] = useState(null); // Will be set from session
  
  const [progressReports, setProgressReports] = useState([]);
  const [paymentRequests, setPaymentRequests] = useState([]);
  const [selectedReport, setSelectedReport] = useState(null);
  const [selectedPaymentRequest, setSelectedPaymentRequest] = useState(null);
  const [showReportDetails, setShowReportDetails] = useState(false);
  const [showPaymentDetails, setShowPaymentDetails] = useState(false);
  const [showPaymentSelector, setShowPaymentSelector] = useState(false);
  const [showReceiptUpload, setShowReceiptUpload] = useState(false);
  const [progressLoading, setProgressLoading] = useState(false);
  const [paymentsLoading, setPaymentsLoading] = useState(false);
  const [reportFilter, setReportFilter] = useState('all');
  const [paymentProcessing, setPaymentProcessing] = useState(false);

  // Inspection reports state
  const [inspectionReports, setInspectionReports] = useState([]);
  const [inspectionStats, setInspectionStats] = useState({});
  const [inspectionLoading, setInspectionLoading] = useState(false);

  // Documents & Files state
  const [billsReceipts, setBillsReceipts] = useState([]);
  const [billsReceiptsStats, setBillsReceiptsStats] = useState({});
  const [billsReceiptsLoading, setBillsReceiptsLoading] = useState(false);

  // Contractor Documents state
  const [contractorDocuments, setContractorDocuments] = useState([]);
  const [contractorDocumentsStats, setContractorDocumentsStats] = useState({});
  const [contractorDocumentsLoading, setContractorDocumentsLoading] = useState(false);

  const fetchProgressReports = async () => {
    if (!homeownerId) {
      console.log('No homeowner ID available, skipping progress reports fetch');
      return;
    }
    
    setProgressLoading(true);
    try {
      // Use session-based API call (no need to pass homeowner_id)
      const response = await fetch(`/buildhub/backend/api/homeowner/get_progress_updates.php?limit=50`, {
        credentials: 'include'
      });
      const data = await response.json();
      
      if (data.success) {
        // Transform the daily progress updates to match the expected report format
        const transformedReports = data.data.progress_updates.map(update => ({
          id: update.id,
          project_id: update.project_id,
          contractor_id: update.contractor_id,
          contractor_name: update.contractor_name,
          project_name: `Project ${update.project_id} - ${update.construction_stage}`,
          report_type: 'daily',
          period_start: update.update_date,
          period_end: update.update_date,
          created_at: update.created_at,
          status: 'sent', // All submitted reports are 'sent'
          viewed_at: null,
          acknowledged_at: null,
          summary: {
            total_days: 1,
            total_workers: update.total_workers || 0,
            total_hours: update.working_hours || 0,
            progress_percentage: update.cumulative_completion_percentage || 0,
            photos_count: update.photos ? update.photos.length : 0,
            stage: update.construction_stage,
            work_description: update.work_done_today,
            weather: update.weather_condition,
            incremental_progress: update.incremental_completion_percentage
          },
          has_photos: (update.photos && update.photos.length > 0) || false,
          // Add the full update data for detailed view
          full_update_data: update
        }));
        
        setProgressReports(transformedReports);
        console.log('Progress reports set:', transformedReports.length, 'reports');
      } else {
        console.error('API returned error:', data.message);
        toast.error('Failed to load progress reports: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading progress reports:', error);
      toast.error('Error loading progress reports');
    } finally {
      setProgressLoading(false);
    }
  };

  const fetchPaymentRequests = async () => {
    setPaymentsLoading(true);
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/get_all_payment_requests.php', {
        credentials: 'include'
      });
      const data = await response.json();
      
      if (data.success) {
        setPaymentRequests(data.data.requests || []);
      } else {
        toast.error('Failed to load payment requests: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading payment requests:', error);
      toast.error('Error loading payment requests');
    } finally {
      setPaymentsLoading(false);
    }
  };

  // Fetch inspection reports for homeowner
  const fetchInspectionReports = async () => {
    try {
      setInspectionLoading(true);
      
      // First establish session
      await establishSession();
      
      const response = await fetch('/buildhub/backend/api/homeowner/get_inspection_reports.php', {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const responseText = await response.text();
      
      // Try to parse JSON from response (handle PHP warnings)
      let result;
      try {
        result = JSON.parse(responseText);
      } catch (parseError) {
        // If direct parse fails, try to extract JSON from response with PHP warnings
        const lines = responseText.split('\n');
        let jsonLine = '';
        for (const line of lines) {
          if (line.trim().startsWith('{"success"')) {
            jsonLine = line.trim();
            break;
          }
        }
        if (jsonLine) {
          result = JSON.parse(jsonLine);
        } else {
          throw new Error('No valid JSON found in response');
        }
      }
      
      if (result.success) {
        setInspectionReports(result.reports || []);
        setInspectionStats(result.statistics || {});
        if (result.reports && result.reports.length > 0) {
          toast.success(`Loaded ${result.reports.length} inspection report${result.reports.length > 1 ? 's' : ''}`);
        }
      } else {
        toast.error('Failed to fetch inspection reports: ' + result.message);
      }
    } catch (error) {
      console.error('Error fetching inspection reports:', error);
      toast.error('Error fetching inspection reports: ' + error.message);
    } finally {
      setInspectionLoading(false);
    }
  };

  // Fetch bills and receipts for homeowner
  const fetchBillsReceipts = async () => {
    try {
      setBillsReceiptsLoading(true);
      
      // First establish session
      await establishSession();
      
      const response = await fetch('/buildhub/backend/api/homeowner/get_bills_receipts.php', {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setBillsReceipts(result.data.receipts || []);
        setBillsReceiptsStats(result.data.statistics || {});
        if (result.data.receipts && result.data.receipts.length > 0) {
          toast.success(`Loaded ${result.data.receipts.length} receipt${result.data.receipts.length > 1 ? 's' : ''}`);
        }
      } else {
        toast.error('Failed to fetch bills and receipts: ' + result.message);
      }
    } catch (error) {
      console.error('Error fetching bills and receipts:', error);
      toast.error('Error fetching bills and receipts: ' + error.message);
    } finally {
      setBillsReceiptsLoading(false);
    }
  };

  // Fetch contractor stage documents for homeowner
  const fetchContractorDocuments = async () => {
    try {
      setContractorDocumentsLoading(true);
      
      // First establish session
      await establishSession();
      
      const response = await fetch('/buildhub/backend/api/homeowner/get_contractor_stage_documents.php', {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setContractorDocuments(result.data.documents || []);
        setContractorDocumentsStats(result.data.statistics || {});
        if (result.data.documents && result.data.documents.length > 0) {
          toast.success(`Loaded ${result.data.documents.length} contractor document${result.data.documents.length > 1 ? 's' : ''}`);
        }
      } else {
        console.error('Failed to fetch contractor documents:', result.message);
        // Don't show error toast for missing table - just log it
        if (!result.message.includes('Table') && !result.message.includes('doesn\'t exist')) {
          toast.error('Failed to fetch contractor documents: ' + result.message);
        }
      }
    } catch (error) {
      console.error('Error fetching contractor documents:', error);
      // Don't show error toast for network/API issues
    } finally {
      setContractorDocumentsLoading(false);
    }
  };

  const viewReportDetails = async (reportId) => {
    try {
      // Find the report in our local data
      const report = progressReports.find(r => r.id === reportId);
      if (!report) {
        toast.error('Report not found');
        return;
      }

      // Create a detailed view from the daily progress data
      const detailedReport = {
        id: report.id,
        project_id: report.project_id,
        contractor_id: report.contractor_id,
        contractor_name: report.contractor_name,
        project_name: report.project_name,
        report_type: 'daily',
        created_at: report.created_at,
        status: 'sent',
        
        // Daily progress specific details
        update_date: report.full_update_data.update_date,
        construction_stage: report.full_update_data.construction_stage,
        work_done_today: report.full_update_data.work_done_today,
        incremental_completion: report.full_update_data.incremental_completion_percentage,
        cumulative_completion: report.full_update_data.cumulative_completion_percentage,
        working_hours: report.full_update_data.working_hours,
        weather_condition: report.full_update_data.weather_condition,
        site_issues: report.full_update_data.site_issues,
        
        // Labour information
        worker_types: report.full_update_data.worker_types_array || [],
        total_workers: report.full_update_data.total_workers || 0,
        avg_productivity: report.full_update_data.avg_productivity || 0,
        labour_entries_count: report.full_update_data.labour_entries_count || 0,
        
        // Location information
        location_verified: report.full_update_data.location_verified,
        location_status: report.full_update_data.location_status,
        
        // Photos
        photos: report.full_update_data.photos || [],
        has_photos: report.has_photos,
        
        // Summary for display
        summary: report.summary
      };
      
      setSelectedReport(detailedReport);
      setShowReportDetails(true);
    } catch (error) {
      console.error('Error loading report details:', error);
      toast.error('Error loading report details');
    }
  };

  const viewPaymentDetails = (paymentRequest) => {
    setSelectedPaymentRequest(paymentRequest);
    setShowPaymentDetails(true);
    // Prevent body scroll when modal opens
    document.body.style.overflow = 'hidden';
    document.body.classList.add('modal-open');
  };

  const respondToPaymentRequest = async (requestId, action, notes = '', rejectionReason = '') => {
    try {
      // Determine which API to use based on the selected payment request type
      const isCustomPayment = selectedPaymentRequest?.request_type === 'custom';
      const apiEndpoint = isCustomPayment 
        ? '/buildhub/backend/api/homeowner/respond_to_custom_payment.php'
        : '/buildhub/backend/api/homeowner/respond_payment_request.php';
      
      const response = await fetch(apiEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          request_id: requestId,
          action: action,
          homeowner_notes: notes,
          rejection_reason: rejectionReason
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        toast.success(data.message);
        fetchPaymentRequests(); // Refresh the list
        setShowPaymentDetails(false);
        // Restore body scroll when modal closes
        document.body.style.overflow = '';
        document.body.classList.remove('modal-open');
      } else {
        toast.error('Failed to respond to payment request: ' + data.message);
      }
    } catch (error) {
      console.error('Error responding to payment request:', error);
      toast.error('Error responding to payment request');
    }
  };

  const initiatePayment = async (paymentRequest) => {
    setSelectedPaymentRequest(paymentRequest);
    setShowPaymentSelector(true);
  };

  const handlePaymentInitiated = (paymentData) => {
    setShowPaymentSelector(false);
    setSelectedPaymentRequest(null);
    fetchPaymentRequests(); // Reload to get updated status
    setShowPaymentDetails(false);
    // Restore body scroll when modal closes
    document.body.style.overflow = '';
    document.body.classList.remove('modal-open');
  };

  const handlePaymentCancel = () => {
    setShowPaymentSelector(false);
    setSelectedPaymentRequest(null);
  };

  const handleReceiptUpload = () => {
    setShowReceiptUpload(true);
  };

  const handleReceiptUploadComplete = (uploadData) => {
    setShowReceiptUpload(false);
    toast.success('Receipt uploaded successfully! The contractor will verify your payment.');
    fetchPaymentRequests(); // Reload to get updated status
  };

  const handleReceiptUploadCancel = () => {
    setShowReceiptUpload(false);
  };

  const acknowledgeReport = async (reportId, notes = '') => {
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/view_progress_report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          report_id: reportId,
          notes: notes
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        toast.success('Report acknowledged successfully');
        fetchProgressReports();
        setShowReportDetails(false);
      } else {
        toast.error('Failed to acknowledge report: ' + data.message);
      }
    } catch (error) {
      console.error('Error acknowledging report:', error);
      toast.error('Error acknowledging report');
    }
  };

  // Load data when component becomes active
  useEffect(() => {
    if (activeTab === 'progress' && isVisible) {
      // First establish session, then fetch data
      establishSession().then((sessionSuccess) => {
        if (sessionSuccess) {
          // Wait a bit for homeownerId to be set, then fetch data
          setTimeout(() => {
            fetchProgressReports();
            fetchPaymentRequests();
            fetchInspectionReports();
            fetchBillsReceipts();
            fetchContractorDocuments();
          }, 100);
        }
      });
    }
  }, [activeTab, isVisible]);

  // Separate effect to fetch data when homeownerId changes
  useEffect(() => {
    if (homeownerId && activeTab === 'progress' && isVisible) {
      fetchProgressReports();
      fetchPaymentRequests();
    }
  }, [homeownerId]);

  // Load inspection reports when inspection reports filter is selected
  useEffect(() => {
    if (reportFilter === 'inspection_reports' && isVisible) {
      fetchInspectionReports();
    }
  }, [reportFilter, isVisible]);

  // Load documents and files when documents & files filter is selected
  useEffect(() => {
    if (reportFilter === 'bills_receipts' && isVisible) {
      fetchBillsReceipts();
    }
  }, [reportFilter, isVisible]);

  // Load contractor documents when contractor documents filter is selected
  useEffect(() => {
    if (reportFilter === 'contractor_documents' && isVisible) {
      fetchContractorDocuments();
    }
  }, [reportFilter, isVisible]);

  // Function to establish session for authentication
  const establishSession = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/session_bridge.php', {
        credentials: 'include'
      });
      const data = await response.json();
      if (data.success && data.user && data.user.id) {
        // Update homeowner ID from session
        setHomeownerId(data.user.id);
        return data.success;
      } else {
        console.warn('Session establishment failed:', data.message);
        toast.error('Session establishment failed: ' + data.message);
        return false;
      }
    } catch (error) {
      console.warn('Session establishment error:', error);
      toast.error('Session establishment error: ' + error.message);
      return false;
    }
  };

  // Handle escape key for modal
  useEffect(() => {
    const handleEscapeKey = (event) => {
      if (event.key === 'Escape' && showPaymentDetails) {
        setShowPaymentDetails(false);
        document.body.style.overflow = '';
        document.body.classList.remove('modal-open');
      }
    };

    if (showPaymentDetails) {
      document.addEventListener('keydown', handleEscapeKey);
    }

    return () => {
      document.removeEventListener('keydown', handleEscapeKey);
    };
  }, [showPaymentDetails]);

  // Don't render anything if not visible
  if (!isVisible) {
    return null;
  }

  const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      'sent': { color: '#007bff', bg: '#e3f2fd', text: '📤 Sent' },
      'viewed': { color: '#28a745', bg: '#e8f5e9', text: '👀 Viewed' },
      'acknowledged': { color: '#6f42c1', bg: '#f3e5f5', text: '✅ Acknowledged' }
    };
    
    const config = statusConfig[status] || statusConfig['sent'];
    
    return (
      <span style={{
        background: config.bg,
        color: config.color,
        padding: '4px 12px',
        borderRadius: '20px',
        fontSize: '12px',
        fontWeight: '600',
        border: `1px solid ${config.color}20`
      }}>
        {config.text}
      </span>
    );
  };

  const getReportTypeIcon = (type) => {
    const icons = {
      'daily': '📅',
      'weekly': '📊',
      'monthly': '📈'
    };
    return icons[type] || '📋';
  };

  const getPaymentStatusBadge = (status) => {
    const statusConfig = {
      'pending': { color: '#ffc107', bg: '#fff3cd', text: '⏳ Pending Review' },
      'approved': { color: '#28a745', bg: '#d4edda', text: '✅ Approved' },
      'paid': { color: '#6f42c1', bg: '#f3e5f5', text: '💰 Paid' },
      'rejected': { color: '#dc3545', bg: '#f8d7da', text: '❌ Rejected' }
    };
    
    const config = statusConfig[status] || statusConfig['pending'];
    
    return (
      <span style={{
        background: config.bg,
        color: config.color,
        padding: '6px 12px',
        borderRadius: '20px',
        fontSize: '12px',
        fontWeight: '600',
        border: `1px solid ${config.color}30`
      }}>
        {config.text}
      </span>
    );
  };

  const filteredReports = progressReports.filter(report => {
    if (reportFilter === 'all') return true;
    if (reportFilter === 'payment_requests' || reportFilter === 'payment_history') return false; // Payment requests are separate
    return report.report_type === reportFilter;
  });

  // Filter payment requests based on selected filter
  const filteredPaymentRequests = reportFilter === 'payment_requests' 
    ? paymentRequests.filter(req => {
        // Only show unpaid and unverified payments in Payment Requests tab
        // Exclude: paid status OR verified status
        const isPaid = req.status === 'paid';
        const isVerified = req.verification_status === 'verified';
        const shouldShowInRequests = !isPaid && !isVerified;
        
        // Debug logging for troubleshooting
        if (process.env.NODE_ENV === 'development') {
          console.log(`Payment ${req.id}: status=${req.status}, verification=${req.verification_status}, showInRequests=${shouldShowInRequests}`);
        }
        
        return shouldShowInRequests;
      })
    : reportFilter === 'payment_history'
    ? paymentRequests.filter(req => {
        // Show paid payments OR verified payments in Payment History tab
        const isPaid = req.status === 'paid';
        const isVerified = req.verification_status === 'verified';
        const shouldShowInHistory = isPaid || isVerified;
        
        // Debug logging for troubleshooting
        if (process.env.NODE_ENV === 'development') {
          console.log(`Payment ${req.id}: status=${req.status}, verification=${req.verification_status}, showInHistory=${shouldShowInHistory}`);
        }
        
        return shouldShowInHistory;
      })
    : [];

  return (
    <div className="section-card" style={{ marginTop: '1rem' }}>
      <div className="section-header">
        <div className="header-content">
          <div>
            <h2>📊 Construction Progress Reports</h2>
            <p>View detailed progress reports submitted by your contractor with comprehensive project updates</p>
          </div>
          <button 
            className="btn btn-primary" 
            onClick={fetchProgressReports}
            disabled={progressLoading}
          >
            {progressLoading ? '⏳ Loading...' : '↻ Refresh Reports'}
          </button>
        </div>
      </div>

      {/* Filter Tabs */}
      <div className="progress-filters" style={{ 
        display: 'flex', 
        gap: '10px', 
        marginBottom: '20px',
        borderBottom: '1px solid #e9ecef',
        paddingBottom: '15px',
        flexWrap: 'wrap'
      }}>
        {['all', 'daily', 'weekly', 'monthly', 'payment_requests', 'payment_history', 'bills_receipts', 'contractor_documents', 'inspection_reports'].map(filter => (
          <button
            key={filter}
            className={`filter-btn ${reportFilter === filter ? 'active' : ''}`}
            onClick={() => setReportFilter(filter)}
            style={{
              padding: '8px 16px',
              border: '2px solid #007bff',
              background: reportFilter === filter ? '#007bff' : 'transparent',
              color: reportFilter === filter ? 'white' : '#007bff',
              borderRadius: '6px',
              fontWeight: '600',
              cursor: 'pointer',
              transition: 'all 0.3s ease',
              textTransform: 'capitalize'
            }}
          >
            {filter === 'all' ? '📋 All Reports' : 
             filter === 'payment_requests' ? '💰 Payment Requests' :
             filter === 'payment_history' ? '📜 Payment History' :
             filter === 'bills_receipts' ? '📋 Documents & Files' :
             filter === 'contractor_documents' ? '🏗️ Contractor Documents' :
             filter === 'inspection_reports' ? '🔍 Inspection Reports' :
             `${getReportTypeIcon(filter)} ${filter} Reports`}
          </button>
        ))}
      </div>

      <div className="section-content">
        {/* Payment Requests Section */}
        {reportFilter === 'payment_requests' && (
          <>
            {paymentsLoading ? (
              <div style={{ textAlign: 'center', padding: '40px' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>⏳</div>
                <h3>Loading Payment Requests...</h3>
                <p>Fetching payment requests from your contractors</p>
              </div>
            ) : filteredPaymentRequests.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '40px' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>💰</div>
                <h3>No Pending Payment Requests</h3>
                <p>All payment requests have been processed. Check Payment History for completed payments.</p>
              </div>
            ) : (
              <div className="payment-requests-grid" style={{ 
                display: 'grid', 
                gap: '20px',
                gridTemplateColumns: 'repeat(auto-fill, minmax(450px, 1fr))'
              }}>
                {filteredPaymentRequests.map(request => (
                  <div 
                    key={request.id} 
                    className="payment-request-card"
                    style={{
                      background: 'white',
                      border: '1px solid #e9ecef',
                      borderRadius: '12px',
                      padding: '20px',
                      boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                      transition: 'all 0.3s ease',
                      position: 'relative'
                    }}
                  >
                    <div style={{
                      position: 'absolute',
                      top: 0,
                      left: 0,
                      right: 0,
                      height: '4px',
                      background: request.status === 'pending' ? 'linear-gradient(135deg, #ffc107 0%, #ff8c00 100%)' :
                                 request.status === 'approved' ? 'linear-gradient(135deg, #28a745 0%, #20c997 100%)' :
                                 request.status === 'paid' ? 'linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%)' :
                                 'linear-gradient(135deg, #dc3545 0%, #fd7e14 100%)',
                      borderRadius: '12px 12px 0 0'
                    }}></div>
                    
                    <div className="payment-header" style={{ 
                      display: 'flex', 
                      justifyContent: 'space-between', 
                      alignItems: 'flex-start',
                      marginBottom: '15px',
                      marginTop: '10px'
                    }}>
                      <div>
                        <h3 style={{ 
                          margin: '0 0 8px 0', 
                          color: '#2c3e50',
                          display: 'flex',
                          alignItems: 'center',
                          gap: '8px'
                        }}>
                          {request.request_type === 'custom' ? (
                            <>
                              💰 {request.request_title}
                              {request.urgency_level && (
                                <span style={{
                                  fontSize: '12px',
                                  padding: '2px 6px',
                                  borderRadius: '10px',
                                  background: request.urgency_badge?.color || '#6c757d',
                                  color: 'white',
                                  marginLeft: '8px'
                                }}>
                                  {request.urgency_badge?.icon} {request.urgency_badge?.label}
                                </span>
                              )}
                            </>
                          ) : (
                            <>🏗️ {request.stage_name || request.request_title} Stage</>
                          )}
                        </h3>
                        <p style={{ 
                          margin: '0', 
                          color: '#6c757d', 
                          fontSize: '14px',
                          fontWeight: '500'
                        }}>
                          {request.contractor_first_name} {request.contractor_last_name}
                          {request.request_type === 'custom' && request.category && (
                            <span style={{ 
                              marginLeft: '8px', 
                              padding: '2px 6px', 
                              background: '#e3f2fd', 
                              color: '#1976d2', 
                              borderRadius: '8px', 
                              fontSize: '11px' 
                            }}>
                              {request.category}
                            </span>
                          )}
                        </p>
                      </div>
                      {getPaymentStatusBadge(request.status)}
                    </div>

                    <div className="payment-amount" style={{
                      background: '#f8f9fa',
                      padding: '15px',
                      borderRadius: '8px',
                      marginBottom: '15px',
                      textAlign: 'center'
                    }}>
                      <div style={{ fontSize: '24px', fontWeight: '700', color: '#667eea' }}>
                        ₹{parseFloat(request.requested_amount).toLocaleString()}
                      </div>
                      <div style={{ fontSize: '12px', color: '#6c757d' }}>
                        {request.request_type === 'custom' ? (
                          <>Custom payment request - {request.category || 'Additional work'}</>
                        ) : (
                          <>{request.completion_percentage}% of {request.stage_name || request.request_title} stage completed</>
                        )}
                      </div>
                    </div>

                    <div className="payment-details" style={{ marginBottom: '15px' }}>
                      <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: '1fr 1fr', 
                        gap: '10px',
                        fontSize: '13px',
                        color: '#6c757d'
                      }}>
                        <div><strong>Requested:</strong> {formatDate(request.request_date)}</div>
                        <div><strong>Workers:</strong> {request.labor_count || 'N/A'}</div>
                        {request.work_start_date && (
                          <div><strong>Work Started:</strong> {new Date(request.work_start_date).toLocaleDateString('en-IN')}</div>
                        )}
                        {request.work_end_date && (
                          <div><strong>Work Ended:</strong> {new Date(request.work_end_date).toLocaleDateString('en-IN')}</div>
                        )}
                      </div>
                    </div>

                    {(request.work_description || request.request_description) && (
                      <div className="work-description" style={{
                        background: '#f8f9fa',
                        padding: '12px',
                        borderRadius: '6px',
                        marginBottom: '15px',
                        fontSize: '14px',
                        lineHeight: '1.4'
                      }}>
                        <strong>{request.request_type === 'custom' ? 'Request Reason:' : 'Work Description:'}</strong><br />
                        {((request.work_description || request.request_description) || '').length > 100 ? 
                          (request.work_description || request.request_description).substring(0, 100) + '...' : 
                          (request.work_description || request.request_description)}
                      </div>
                    )}

                    <div className="payment-actions" style={{ 
                      display: 'flex', 
                      gap: '10px',
                      justifyContent: 'flex-end'
                    }}>
                      <button
                        onClick={() => viewPaymentDetails(request)}
                        style={{
                          background: 'transparent',
                          border: '2px solid #007bff',
                          color: '#007bff',
                          padding: '8px 16px',
                          borderRadius: '6px',
                          cursor: 'pointer',
                          fontSize: '14px',
                          fontWeight: '600'
                        }}
                      >
                        👁️ View Details
                      </button>
                      
                      {request.status === 'pending' && (
                        <button
                          onClick={() => respondToPaymentRequest(request.id, 'approve')}
                          style={{
                            background: '#28a745',
                            border: 'none',
                            color: 'white',
                            padding: '8px 16px',
                            borderRadius: '6px',
                            cursor: 'pointer',
                            fontSize: '14px',
                            fontWeight: '600'
                          }}
                        >
                          ✅ Approve
                        </button>
                      )}
                      
                      {request.status === 'approved' && (
                        <button
                          onClick={() => initiatePayment(request)}
                          disabled={paymentProcessing}
                          style={{
                            background: '#667eea',
                            border: 'none',
                            color: 'white',
                            padding: '8px 16px',
                            borderRadius: '6px',
                            cursor: paymentProcessing ? 'not-allowed' : 'pointer',
                            fontSize: '14px',
                            fontWeight: '600',
                            opacity: paymentProcessing ? 0.7 : 1
                          }}
                        >
                          {paymentProcessing ? '⏳ Processing...' : '💳 Pay Now'}
                        </button>
                      )}
                    </div>

                    {request.is_overdue && (
                      <div style={{
                        marginTop: '10px',
                        padding: '8px 12px',
                        background: '#fff3cd',
                        border: '1px solid #ffeaa7',
                        borderRadius: '4px',
                        fontSize: '12px',
                        color: '#856404'
                      }}>
                        ⚠️ This request is overdue ({request.days_since_request} days old)
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </>
        )}

        {/* Payment History Section */}
        {reportFilter === 'payment_history' && (
          <>
            {paymentsLoading ? (
              <div style={{ textAlign: 'center', padding: '40px' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>⏳</div>
                <h3>Loading Payment History...</h3>
                <p>Fetching your payment history</p>
              </div>
            ) : filteredPaymentRequests.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '40px' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>📜</div>
                <h3>No Payment History Yet</h3>
                <p>Completed payments will appear here.</p>
              </div>
            ) : (
              <div className="payment-history-grid" style={{ 
                display: 'grid', 
                gap: '20px',
                gridTemplateColumns: 'repeat(auto-fill, minmax(450px, 1fr))'
              }}>
                {filteredPaymentRequests.map(request => (
                  <div 
                    key={request.id} 
                    className="payment-history-card"
                    style={{
                      background: 'white',
                      border: '1px solid #e9ecef',
                      borderRadius: '12px',
                      padding: '20px',
                      boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                      transition: 'all 0.3s ease',
                      position: 'relative'
                    }}
                  >
                    <div style={{
                      position: 'absolute',
                      top: 0,
                      left: 0,
                      right: 0,
                      height: '4px',
                      background: request.status === 'paid' && request.verification_status === 'verified' 
                        ? 'linear-gradient(135deg, #28a745 0%, #20c997 100%)' 
                        : 'linear-gradient(135deg, #ffc107 0%, #ff8c00 100%)',
                      borderRadius: '12px 12px 0 0'
                    }}></div>
                    
                    <div className="payment-header" style={{ 
                      display: 'flex', 
                      justifyContent: 'space-between', 
                      alignItems: 'flex-start',
                      marginBottom: '15px',
                      marginTop: '10px'
                    }}>
                      <div>
                        <h3 style={{ 
                          margin: '0 0 8px 0', 
                          color: '#2c3e50',
                          display: 'flex',
                          alignItems: 'center',
                          gap: '8px'
                        }}>
                          {request.request_type === 'custom' ? (
                            <>
                              💰 {request.request_title}
                              {request.urgency_level && (
                                <span style={{
                                  fontSize: '12px',
                                  padding: '2px 6px',
                                  borderRadius: '10px',
                                  background: request.urgency_badge?.color || '#6c757d',
                                  color: 'white',
                                  marginLeft: '8px'
                                }}>
                                  {request.urgency_badge?.icon} {request.urgency_badge?.label}
                                </span>
                              )}
                            </>
                          ) : (
                            <>🏗️ {request.stage_name || request.request_title} Stage</>
                          )}
                        </h3>
                        <p style={{ 
                          margin: '0', 
                          color: '#6c757d', 
                          fontSize: '14px',
                          fontWeight: '500'
                        }}>
                          {request.contractor_first_name} {request.contractor_last_name}
                          {request.request_type === 'custom' && request.category && (
                            <span style={{ 
                              marginLeft: '8px', 
                              padding: '2px 6px', 
                              background: '#e3f2fd', 
                              color: '#1976d2', 
                              borderRadius: '8px', 
                              fontSize: '11px' 
                            }}>
                              {request.category}
                            </span>
                          )}
                        </p>
                      </div>
                      <div style={{ display: 'flex', flexDirection: 'column', gap: '6px', alignItems: 'flex-end' }}>
                        {getPaymentStatusBadge(request.status)}
                        {request.verification_status && (
                          <span style={{
                            background: request.verification_status === 'verified' ? '#d4edda' : 
                                       request.verification_status === 'pending' ? '#fff3cd' : '#f8d7da',
                            color: request.verification_status === 'verified' ? '#155724' : 
                                   request.verification_status === 'pending' ? '#856404' : '#721c24',
                            padding: '4px 10px',
                            borderRadius: '20px',
                            fontSize: '11px',
                            fontWeight: '600',
                            border: `1px solid ${request.verification_status === 'verified' ? '#c3e6cb' : 
                                                 request.verification_status === 'pending' ? '#ffeaa7' : '#f5c6cb'}`
                          }}>
                            {request.verification_status === 'verified' && '✅ Verified'}
                            {request.verification_status === 'pending' && '⏳ Verifying'}
                            {request.verification_status === 'rejected' && '❌ Rejected'}
                          </span>
                        )}
                      </div>
                    </div>

                    <div className="payment-amount" style={{
                      background: '#f8f9fa',
                      padding: '15px',
                      borderRadius: '8px',
                      marginBottom: '15px',
                      textAlign: 'center'
                    }}>
                      <div style={{ fontSize: '24px', fontWeight: '700', color: '#28a745' }}>
                        ₹{parseFloat(request.requested_amount).toLocaleString()}
                      </div>
                      <div style={{ fontSize: '12px', color: '#6c757d' }}>
                        {request.request_type === 'custom' ? (
                          <>Custom payment request - {request.category || 'Additional work'}</>
                        ) : (
                          <>{request.completion_percentage}% of {request.stage_name || request.request_title} stage completed</>
                        )}
                      </div>
                    </div>

                    <div className="payment-details" style={{ marginBottom: '15px' }}>
                      <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: '1fr 1fr', 
                        gap: '10px',
                        fontSize: '13px',
                        color: '#6c757d'
                      }}>
                        {request.payment_date && (
                          <div><strong>Paid On:</strong> {request.payment_date_formatted}</div>
                        )}
                        {request.payment_method && (
                          <div><strong>Method:</strong> {
                            request.payment_method === 'bank_transfer' ? '🏦 Bank' :
                            request.payment_method === 'upi' ? '📱 UPI' :
                            request.payment_method === 'cash' ? '💵 Cash' :
                            request.payment_method === 'cheque' ? '📝 Cheque' :
                            request.payment_method === 'razorpay' ? '💳 Razorpay' :
                            '💳 Other'
                          }</div>
                        )}
                        {request.transaction_reference && (
                          <div style={{ gridColumn: '1 / -1' }}>
                            <strong>Ref:</strong> {request.transaction_reference}
                          </div>
                        )}
                        {request.verified_at && (
                          <div style={{ gridColumn: '1 / -1' }}>
                            <strong>Verified:</strong> {request.verified_at_formatted}
                          </div>
                        )}
                      </div>
                    </div>

                    {request.verification_notes && (
                      <div className="verification-notes" style={{
                        background: '#e3f2fd',
                        padding: '12px',
                        borderRadius: '6px',
                        marginBottom: '15px',
                        fontSize: '14px',
                        lineHeight: '1.4',
                        border: '1px solid #bbdefb'
                      }}>
                        <strong style={{ color: '#1976d2' }}>Verification Notes:</strong><br />
                        <span style={{ color: '#1565c0' }}>{request.verification_notes}</span>
                      </div>
                    )}

                    <div className="payment-actions" style={{ 
                      display: 'flex', 
                      gap: '10px',
                      justifyContent: 'flex-end'
                    }}>
                      <button
                        onClick={() => viewPaymentDetails(request)}
                        style={{
                          background: 'transparent',
                          border: '2px solid #007bff',
                          color: '#007bff',
                          padding: '8px 16px',
                          borderRadius: '6px',
                          cursor: 'pointer',
                          fontSize: '14px',
                          fontWeight: '600'
                        }}
                      >
                        👁️ View Details
                      </button>
                      
                      {request.receipt_files && request.receipt_files.length > 0 && (
                        <button
                          onClick={() => {
                            // Open first receipt file
                            window.open(`/buildhub/backend/${request.receipt_files[0].file_path}`, '_blank');
                          }}
                          style={{
                            background: '#28a745',
                            border: 'none',
                            color: 'white',
                            padding: '8px 16px',
                            borderRadius: '6px',
                            cursor: 'pointer',
                            fontSize: '14px',
                            fontWeight: '600'
                          }}
                        >
                          📄 View Receipt
                        </button>
                      )}
                    </div>

                    {request.verification_status === 'pending' && (
                      <div style={{
                        marginTop: '10px',
                        padding: '8px 12px',
                        background: '#fff3cd',
                        border: '1px solid #ffeaa7',
                        borderRadius: '4px',
                        fontSize: '12px',
                        color: '#856404'
                      }}>
                        ⏳ Payment receipt is being verified by the contractor
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </>
        )}

        {/* Inspection Reports Section */}
        {reportFilter === 'inspection_reports' && (
          <>
            {inspectionLoading ? (
              <div style={{ textAlign: 'center', padding: '40px' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>🔍</div>
                <h3>Loading Inspection Reports...</h3>
                <p>Fetching inspection reports from site inspectors</p>
              </div>
            ) : inspectionReports.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '40px' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>🔍</div>
                <h3>No Inspection Reports</h3>
                <p>No inspection reports have been created for your projects yet.</p>
              </div>
            ) : (
              <>
                {/* Statistics Cards */}
                <div style={{
                  display: 'grid',
                  gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
                  gap: '15px',
                  marginBottom: '25px'
                }}>
                  <div style={{
                    backgroundColor: '#f0f9ff',
                    padding: '20px',
                    borderRadius: '8px',
                    border: '1px solid #e0f2fe',
                    textAlign: 'center'
                  }}>
                    <div style={{ fontSize: '24px', fontWeight: 'bold', color: '#0369a1' }}>
                      {inspectionStats.total_reports || 0}
                    </div>
                    <div style={{ color: '#64748b', fontSize: '14px' }}>Total Inspections</div>
                  </div>
                  <div style={{
                    backgroundColor: '#f0fdf4',
                    padding: '20px',
                    borderRadius: '8px',
                    border: '1px solid #dcfce7',
                    textAlign: 'center'
                  }}>
                    <div style={{ fontSize: '24px', fontWeight: 'bold', color: '#16a34a' }}>
                      {inspectionStats.approved_count || 0}
                    </div>
                    <div style={{ color: '#64748b', fontSize: '14px' }}>Approved</div>
                  </div>
                  <div style={{
                    backgroundColor: '#fef3c7',
                    padding: '20px',
                    borderRadius: '8px',
                    border: '1px solid #fde68a',
                    textAlign: 'center'
                  }}>
                    <div style={{ fontSize: '24px', fontWeight: 'bold', color: '#d97706' }}>
                      {inspectionStats.needs_attention_count || 0}
                    </div>
                    <div style={{ color: '#64748b', fontSize: '14px' }}>Need Attention</div>
                  </div>
                  <div style={{
                    backgroundColor: '#fef2f2',
                    padding: '20px',
                    borderRadius: '8px',
                    border: '1px solid #fecaca',
                    textAlign: 'center'
                  }}>
                    <div style={{ fontSize: '24px', fontWeight: 'bold', color: '#dc2626' }}>
                      {inspectionStats.rejected_count || 0}
                    </div>
                    <div style={{ color: '#64748b', fontSize: '14px' }}>Rejected</div>
                  </div>
                </div>

                {/* Inspection Reports List */}
                <div className="inspection-reports-grid" style={{ 
                  display: 'grid', 
                  gap: '25px',
                  gridTemplateColumns: 'repeat(auto-fill, minmax(600px, 1fr))'
                }}>
                  {inspectionReports.map((report) => (
                    <div 
                      key={report.id} 
                      className="inspection-report-card"
                      style={{
                        background: 'white',
                        border: '1px solid #e9ecef',
                        borderRadius: '16px',
                        padding: '25px',
                        boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
                        transition: 'all 0.3s ease',
                        position: 'relative'
                      }}
                    >
                      {/* Status Header Bar */}
                      <div style={{
                        position: 'absolute',
                        top: 0,
                        left: 0,
                        right: 0,
                        height: '5px',
                        background: report.inspection.status === 'approved' ? 'linear-gradient(135deg, #28a745 0%, #20c997 100%)' :
                                   report.inspection.status === 'rejected' ? 'linear-gradient(135deg, #dc3545 0%, #fd7e14 100%)' :
                                   report.inspection.status === 'needs_attention' ? 'linear-gradient(135deg, #ffc107 0%, #ff8c00 100%)' :
                                   'linear-gradient(135deg, #6c757d 0%, #adb5bd 100%)',
                        borderRadius: '16px 16px 0 0'
                      }}></div>

                      {/* Header Section */}
                      <div style={{ marginTop: '10px', marginBottom: '20px' }}>
                        <div style={{
                          display: 'flex',
                          justifyContent: 'space-between',
                          alignItems: 'flex-start',
                          marginBottom: '15px'
                        }}>
                          <div>
                            <h3 style={{ margin: '0 0 8px 0', color: '#1f2937', fontSize: '20px', fontWeight: '700' }}>
                              🏗️ {report.project.name}
                            </h3>
                            <p style={{ margin: '0', color: '#6b7280', fontSize: '14px' }}>
                              📍 {report.project.location || 'Location not specified'} • 🏗️ {report.inspection.stage} • 📋 {report.inspection.type.replace('_', ' ').toUpperCase()}
                            </p>
                          </div>
                          <div style={{ textAlign: 'right' }}>
                            <span style={{
                              padding: '8px 16px',
                              borderRadius: '25px',
                              fontSize: '13px',
                              fontWeight: '700',
                              backgroundColor: 
                                report.inspection.status === 'approved' ? '#d1fae5' :
                                report.inspection.status === 'rejected' ? '#fee2e2' :
                                report.inspection.status === 'needs_attention' ? '#fef3c7' : '#f3f4f6',
                              color:
                                report.inspection.status === 'approved' ? '#065f46' :
                                report.inspection.status === 'rejected' ? '#991b1b' :
                                report.inspection.status === 'needs_attention' ? '#92400e' : '#374151'
                            }}>
                              {report.inspection.status === 'approved' ? '✅ APPROVED' :
                               report.inspection.status === 'rejected' ? '❌ REJECTED' :
                               report.inspection.status === 'needs_attention' ? '⚠️ NEEDS ATTENTION' : '⏳ PENDING'}
                            </span>
                            <div style={{ fontSize: '13px', color: '#6b7280', marginTop: '8px' }}>
                              📅 {new Date(report.inspection.date).toLocaleDateString('en-IN', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                              })}
                            </div>
                          </div>
                        </div>
                      </div>

                      {/* Key Metrics */}
                      <div style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))',
                        gap: '15px',
                        marginBottom: '20px',
                        padding: '15px',
                        background: '#f8fafc',
                        borderRadius: '12px'
                      }}>
                        <div style={{ textAlign: 'center' }}>
                          <div style={{ fontSize: '24px', fontWeight: '700', color: '#3b82f6' }}>
                            {report.inspection.quality_score ? `${report.inspection.quality_score}/10` : 'N/A'}
                          </div>
                          <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '600' }}>Quality Score</div>
                        </div>
                        <div style={{ textAlign: 'center' }}>
                          <div style={{ fontSize: '18px', fontWeight: '700', color: '#10b981' }}>
                            {report.inspection.safety_compliance.replace('_', ' ').toUpperCase()}
                          </div>
                          <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '600' }}>Safety Status</div>
                        </div>
                        <div style={{ textAlign: 'center' }}>
                          <div style={{ fontSize: '18px', fontWeight: '700', color: '#8b5cf6' }}>
                            {report.checklist_summary.passed_items}/{report.checklist_summary.total_items}
                          </div>
                          <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '600' }}>Checklist Passed</div>
                        </div>
                        <div style={{ textAlign: 'center' }}>
                          <div style={{ fontSize: '18px', fontWeight: '700', color: '#f59e0b' }}>
                            {report.site_conditions.temperature ? `${report.site_conditions.temperature}°C` : 'N/A'}
                          </div>
                          <div style={{ fontSize: '12px', color: '#64748b', fontWeight: '600' }}>Temperature</div>
                        </div>
                      </div>

                      {/* Inspector Information */}
                      <div style={{
                        background: '#e0f2fe',
                        padding: '15px',
                        borderRadius: '10px',
                        marginBottom: '20px',
                        border: '1px solid #b3e5fc'
                      }}>
                        <h4 style={{ margin: '0 0 10px 0', color: '#0277bd', fontSize: '16px', fontWeight: '600' }}>
                          👨‍🔧 Inspector Details
                        </h4>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '10px', fontSize: '14px' }}>
                          <div><strong>Name:</strong> {report.inspector.name}</div>
                          <div><strong>Email:</strong> {report.inspector.email}</div>
                          <div><strong>Phone:</strong> {report.inspector.phone}</div>
                          <div><strong>Inspection Time:</strong> {report.timestamps.created_at ? new Date(report.timestamps.created_at).toLocaleTimeString('en-IN') : 'Not recorded'}</div>
                        </div>
                      </div>

                      {/* Site Conditions */}
                      <div style={{
                        background: '#f0fdf4',
                        padding: '15px',
                        borderRadius: '10px',
                        marginBottom: '20px',
                        border: '1px solid #bbf7d0'
                      }}>
                        <h4 style={{ margin: '0 0 10px 0', color: '#15803d', fontSize: '16px', fontWeight: '600' }}>
                          🌤️ Site Conditions
                        </h4>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '10px', fontSize: '14px' }}>
                          <div><strong>Weather:</strong> {report.site_conditions.weather_conditions || 'Not recorded'}</div>
                          <div><strong>Accessibility:</strong> {report.site_conditions.site_accessibility || 'Not assessed'}</div>
                          <div><strong>Cleanliness:</strong> {report.site_conditions.site_cleanliness || 'Not assessed'}</div>
                          <div><strong>Environmental Impact:</strong> {report.site_conditions.environmental_impact || 'Not assessed'}</div>
                          <div><strong>Waste Management:</strong> {report.site_conditions.waste_management || 'Not assessed'}</div>
                        </div>
                      </div>

                      {/* Quality Assessment */}
                      <div style={{
                        background: '#fef7ff',
                        padding: '15px',
                        borderRadius: '10px',
                        marginBottom: '20px',
                        border: '1px solid #e9d5ff'
                      }}>
                        <h4 style={{ margin: '0 0 10px 0', color: '#7c3aed', fontSize: '16px', fontWeight: '600' }}>
                          🔍 Quality Assessment
                        </h4>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: '10px', fontSize: '14px' }}>
                          <div><strong>Structural Integrity:</strong> {report.quality_assessment.structural_integrity || 'Not assessed'}</div>
                          <div><strong>Workmanship:</strong> {report.quality_assessment.workmanship_quality || 'Not assessed'}</div>
                          <div><strong>Code Compliance:</strong> {report.quality_assessment.code_compliance || 'Not assessed'}</div>
                          <div><strong>Safety Equipment:</strong> {report.quality_assessment.safety_equipment_available || 'Not specified'}</div>
                          <div><strong>Safety Violations:</strong> {report.quality_assessment.safety_violations_found || 'Not specified'}</div>
                        </div>
                      </div>

                      {/* Inspector Notes */}
                      {report.inspection.notes && (
                        <div style={{
                          background: '#f3f4f6',
                          padding: '15px',
                          borderRadius: '10px',
                          marginBottom: '15px',
                          border: '1px solid #d1d5db'
                        }}>
                          <h4 style={{ margin: '0 0 10px 0', color: '#374151', fontSize: '16px', fontWeight: '600' }}>
                            📝 Inspector Notes
                          </h4>
                          <p style={{ margin: '0', fontSize: '14px', lineHeight: '1.6', color: '#4b5563' }}>
                            {report.inspection.notes}
                          </p>
                        </div>
                      )}

                      {/* Recommendations */}
                      {report.inspection.recommendations && (
                        <div style={{
                          background: '#dbeafe',
                          padding: '15px',
                          borderRadius: '10px',
                          marginBottom: '15px',
                          border: '1px solid #93c5fd'
                        }}>
                          <h4 style={{ margin: '0 0 10px 0', color: '#1e40af', fontSize: '16px', fontWeight: '600' }}>
                            💡 Recommendations
                          </h4>
                          <p style={{ margin: '0', fontSize: '14px', lineHeight: '1.6', color: '#1e3a8a' }}>
                            {report.inspection.recommendations}
                          </p>
                        </div>
                      )}

                      {/* Issues Identified */}
                      {report.inspection.issues_identified && (
                        <div style={{
                          background: '#fef3c7',
                          padding: '15px',
                          borderRadius: '10px',
                          marginBottom: '15px',
                          border: '1px solid #fde68a'
                        }}>
                          <h4 style={{ margin: '0 0 10px 0', color: '#92400e', fontSize: '16px', fontWeight: '600' }}>
                            ⚠️ Issues Identified
                          </h4>
                          <p style={{ margin: '0', fontSize: '14px', lineHeight: '1.6', color: '#92400e' }}>
                            {report.inspection.issues_identified}
                          </p>
                        </div>
                      )}

                      {/* Corrective Actions */}
                      {report.inspection.corrective_actions_required && (
                        <div style={{
                          background: '#fee2e2',
                          padding: '15px',
                          borderRadius: '10px',
                          marginBottom: '15px',
                          border: '1px solid #fecaca'
                        }}>
                          <h4 style={{ margin: '0 0 10px 0', color: '#dc2626', fontSize: '16px', fontWeight: '600' }}>
                            🔧 Corrective Actions Required
                          </h4>
                          <p style={{ margin: '0', fontSize: '14px', lineHeight: '1.6', color: '#dc2626' }}>
                            {report.inspection.corrective_actions_required}
                          </p>
                        </div>
                      )}

                      {/* Next Inspection */}
                      {report.inspection.next_inspection_date && (
                        <div style={{
                          background: '#ecfdf5',
                          padding: '15px',
                          borderRadius: '10px',
                          marginBottom: '15px',
                          border: '1px solid #a7f3d0'
                        }}>
                          <h4 style={{ margin: '0 0 10px 0', color: '#059669', fontSize: '16px', fontWeight: '600' }}>
                            📅 Next Inspection Scheduled
                          </h4>
                          <p style={{ margin: '0', fontSize: '14px', color: '#047857' }}>
                            {new Date(report.inspection.next_inspection_date).toLocaleDateString('en-IN', {
                              year: 'numeric',
                              month: 'long',
                              day: 'numeric'
                            })}
                          </p>
                        </div>
                      )}

                      {/* Footer */}
                      <div style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        marginTop: '20px',
                        paddingTop: '15px',
                        borderTop: '1px solid #e5e7eb',
                        fontSize: '12px',
                        color: '#6b7280'
                      }}>
                        <div>
                          📊 Report ID: {report.id} • 👷 {report.follow_up.contractor_present === 'yes' ? 'Contractor Present' : 'Contractor Absent'}
                        </div>
                        <div>
                          {report.follow_up.follow_up_required !== 'no' && (
                            <span style={{
                              backgroundColor: '#fef3c7',
                              color: '#92400e',
                              padding: '4px 10px',
                              borderRadius: '15px',
                              fontSize: '11px',
                              fontWeight: '600'
                            }}>
                              🔄 Follow-up Required
                            </span>
                          )}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </>
            )}
          </>
        )}

        {/* Progress Reports Section */}
        {reportFilter !== 'payment_requests' && reportFilter !== 'payment_history' && reportFilter !== 'inspection_reports' && reportFilter !== 'bills_receipts' && reportFilter !== 'contractor_documents' && (
          <>
            {progressLoading ? (
              <div style={{ textAlign: 'center', padding: '40px' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>⏳</div>
                <h3>Loading Progress Reports...</h3>
                <p>Fetching your latest construction progress updates</p>
              </div>
            ) : filteredReports.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '40px' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>📋</div>
                <h3>No Progress Reports Yet</h3>
                <p>Your contractor will submit progress reports here as work progresses on your project.</p>
              </div>
            ) : (
              <div className="reports-grid" style={{ 
                display: 'grid', 
                gap: '20px',
                gridTemplateColumns: 'repeat(auto-fill, minmax(400px, 1fr))'
              }}>
                {filteredReports.map(report => (
                  <div 
                    key={report.id} 
                    className="report-card"
                    style={{
                      background: 'white',
                      border: '1px solid #e9ecef',
                      borderRadius: '12px',
                      padding: '20px',
                      boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                      transition: 'all 0.3s ease',
                      cursor: 'pointer',
                      position: 'relative'
                    }}
                    onClick={() => viewReportDetails(report.id)}
                    onMouseOver={(e) => {
                      e.currentTarget.style.transform = 'translateY(-2px)';
                      e.currentTarget.style.boxShadow = '0 4px 16px rgba(0,0,0,0.15)';
                    }}
                    onMouseOut={(e) => {
                      e.currentTarget.style.transform = 'translateY(0)';
                      e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                    }}
                  >
                    <div style={{
                      position: 'absolute',
                      top: 0,
                      left: 0,
                      right: 0,
                      height: '4px',
                      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                      borderRadius: '12px 12px 0 0'
                    }}></div>
                    
                    <div className="report-header" style={{ 
                      display: 'flex', 
                      justifyContent: 'space-between', 
                      alignItems: 'flex-start',
                      marginBottom: '15px',
                      marginTop: '10px'
                    }}>
                      <div>
                        <h3 style={{ 
                          margin: '0 0 8px 0', 
                          color: '#2c3e50',
                          display: 'flex',
                          alignItems: 'center',
                          gap: '8px'
                        }}>
                          {getReportTypeIcon(report.report_type)} {report.report_type.charAt(0).toUpperCase() + report.report_type.slice(1)} Report
                        </h3>
                        <p style={{ 
                          margin: '0', 
                          color: '#6c757d', 
                          fontSize: '14px',
                          fontWeight: '500'
                        }}>
                          {report.project_name}
                        </p>
                      </div>
                      {getStatusBadge(report.status)}
                    </div>

                    <div className="report-meta" style={{ marginBottom: '15px' }}>
                      <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: '1fr 1fr', 
                        gap: '10px',
                        fontSize: '13px',
                        color: '#6c757d'
                      }}>
                        <div><strong>Contractor:</strong> {report.contractor_name}</div>
                        <div><strong>Submitted:</strong> {formatDate(report.created_at)}</div>
                        {report.period_start && (
                          <>
                            <div><strong>Period Start:</strong> {new Date(report.period_start).toLocaleDateString('en-IN')}</div>
                            <div><strong>Period End:</strong> {new Date(report.period_end).toLocaleDateString('en-IN')}</div>
                          </>
                        )}
                      </div>
                    </div>

                    {report.summary && (
                      <div className="report-summary" style={{
                        background: '#f8f9fa',
                        padding: '15px',
                        borderRadius: '8px',
                        marginBottom: '15px'
                      }}>
                        <h4 style={{ margin: '0 0 10px 0', color: '#2c3e50', fontSize: '14px' }}>📋 Daily Progress Summary</h4>
                        <div style={{ 
                          display: 'grid', 
                          gridTemplateColumns: 'repeat(2, 1fr)', 
                          gap: '8px',
                          fontSize: '12px',
                          marginBottom: '10px'
                        }}>
                          {report.summary.stage && (
                            <div><strong>Stage:</strong> {report.summary.stage}</div>
                          )}
                          {report.summary.progress_percentage > 0 && (
                            <div><strong>Total Progress:</strong> {report.summary.progress_percentage}%</div>
                          )}
                          {report.summary.incremental_progress > 0 && (
                            <div><strong>Daily Progress:</strong> +{report.summary.incremental_progress}%</div>
                          )}
                          {report.summary.total_workers > 0 && (
                            <div><strong>Workers:</strong> {report.summary.total_workers}</div>
                          )}
                          {report.summary.total_hours > 0 && (
                            <div><strong>Working Hours:</strong> {report.summary.total_hours}h</div>
                          )}
                          {report.summary.weather && (
                            <div><strong>Weather:</strong> {report.summary.weather}</div>
                          )}
                        </div>
                        {report.summary.work_description && (
                          <div style={{
                            background: 'white',
                            padding: '10px',
                            borderRadius: '6px',
                            fontSize: '12px',
                            border: '1px solid #e9ecef'
                          }}>
                            <strong>Work Done:</strong> {report.summary.work_description.length > 100 ? 
                              report.summary.work_description.substring(0, 100) + '...' : 
                              report.summary.work_description}
                          </div>
                        )}
                      </div>
                    )}

                    <div className="report-actions" style={{ 
                      display: 'flex', 
                      justifyContent: 'space-between',
                      alignItems: 'center'
                    }}>
                      <div style={{ fontSize: '12px', color: '#6c757d' }}>
                        {report.has_photos && '📸 Contains Photos'}
                      </div>
                      <div style={{ 
                        color: '#007bff', 
                        fontSize: '14px', 
                        fontWeight: '600',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '4px'
                      }}>
                        👁️ View Details
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </>
        )}

        {/* Contractor Documents Section */}
        {reportFilter === 'contractor_documents' && (
          <>
            {contractorDocumentsLoading ? (
              <div style={{ textAlign: 'center', padding: '40px' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>🏗️</div>
                <h3>Loading Contractor Documents...</h3>
                <p>Fetching documents uploaded by contractors</p>
              </div>
            ) : (
              <>
                {/* Statistics Summary */}
                {contractorDocumentsStats && Object.keys(contractorDocumentsStats).length > 0 && (
                  <div style={{ 
                    background: 'linear-gradient(135deg, #007bff 0%, #0056b3 100%)',
                    color: 'white',
                    padding: '20px',
                    borderRadius: '12px',
                    marginBottom: '20px'
                  }}>
                    <h3 style={{ margin: '0 0 15px 0', fontSize: '18px' }}>📊 Contractor Documents Summary</h3>
                    <div style={{ 
                      display: 'grid', 
                      gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', 
                      gap: '15px' 
                    }}>
                      <div style={{ textAlign: 'center' }}>
                        <div style={{ fontSize: '24px', fontWeight: 'bold' }}>
                          {contractorDocumentsStats.total_documents || 0}
                        </div>
                        <div style={{ fontSize: '12px', opacity: 0.9 }}>Total Documents</div>
                      </div>
                      <div style={{ textAlign: 'center' }}>
                        <div style={{ fontSize: '24px', fontWeight: 'bold' }}>
                          {contractorDocumentsStats.document_types || 0}
                        </div>
                        <div style={{ fontSize: '12px', opacity: 0.9 }}>Document Types</div>
                      </div>
                    </div>
                  </div>
                )}

                {/* Contractor Documents Grid */}
                {contractorDocuments.length === 0 ? (
                  <div style={{ textAlign: 'center', padding: '40px' }}>
                    <div style={{ fontSize: '48px', marginBottom: '16px' }}>🏗️</div>
                    <h3>No Contractor Documents</h3>
                    <p>No documents have been uploaded by contractors yet.</p>
                    <p style={{ color: '#6c757d', fontSize: '14px' }}>
                      Contractors will upload receipts, bills, and certificates during construction progress.
                    </p>
                  </div>
                ) : (
                  <div className="receipts-grid" style={{ 
                    display: 'grid', 
                    gap: '20px',
                    gridTemplateColumns: 'repeat(auto-fill, minmax(350px, 1fr))'
                  }}>
                    {contractorDocuments.map((document, index) => (
                      <div 
                        key={document.id || index} 
                        className="receipt-card"
                        style={{
                          background: 'white',
                          border: '1px solid #e9ecef',
                          borderRadius: '12px',
                          padding: '20px',
                          boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                          transition: 'all 0.3s ease'
                        }}
                      >
                        {/* Document Preview */}
                        <div style={{ marginBottom: '15px' }}>
                          <DocumentPreview 
                            file={{
                              original_name: document.original_filename,
                              file_path: document.file_path,
                              file_size: document.file_size
                            }}
                            onFullView={(file) => window.open(`/buildhub/backend/${file.file_path}`, '_blank')}
                          />
                        </div>

                        {/* Document Info */}
                        <div style={{ 
                          display: 'flex', 
                          alignItems: 'center',
                          gap: '12px',
                          marginBottom: '15px'
                        }}>
                          <div style={{
                            width: '40px',
                            height: '40px',
                            background: document.stage_badge?.color || '#667eea',
                            borderRadius: '8px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            fontSize: '16px'
                          }}>
                            {document.stage_badge?.icon || '🏗️'}
                          </div>
                          <div style={{ flex: 1 }}>
                            <h3 style={{ 
                              margin: '0 0 4px 0', 
                              color: '#2c3e50',
                              fontSize: '14px',
                              fontWeight: '600'
                            }}>
                              {document.original_filename}
                            </h3>
                            <p style={{ 
                              margin: '0', 
                              color: '#6c757d', 
                              fontSize: '12px'
                            }}>
                              {document.file_size_kb} KB • {document.document_type}
                            </p>
                          </div>
                        </div>

                        {/* Document Details */}
                        <div style={{ marginBottom: '15px' }}>
                          <h4 style={{ 
                            margin: '0 0 8px 0', 
                            color: '#495057',
                            fontSize: '14px',
                            fontWeight: '600'
                          }}>
                            {document.stage_name} - {document.document_type_icon} {document.document_type}
                          </h4>
                          {document.description && (
                            <p style={{ 
                              margin: '0 0 8px 0', 
                              color: '#6c757d', 
                              fontSize: '13px',
                              lineHeight: '1.4'
                            }}>
                              {document.description}
                            </p>
                          )}
                          <div style={{ 
                            display: 'flex', 
                            alignItems: 'center', 
                            gap: '8px',
                            marginBottom: '8px'
                          }}>
                            <span style={{ 
                              fontSize: '12px', 
                              color: '#6c757d' 
                            }}>
                              👷 {document.contractor_name}
                            </span>
                          </div>
                          <div style={{ 
                            display: 'flex', 
                            alignItems: 'center', 
                            gap: '8px'
                          }}>
                            <span style={{ 
                              fontSize: '12px', 
                              color: '#6c757d' 
                            }}>
                              📅 {document.upload_date_formatted}
                            </span>
                          </div>
                        </div>

                        {/* Action Button */}
                        <div style={{
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'flex-end',
                          paddingTop: '15px',
                          borderTop: '1px solid #e9ecef'
                        }}>
                          <button
                            onClick={() => window.open(`/buildhub/backend/${document.file_path}`, '_blank')}
                            style={{
                              background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                              color: 'white',
                              border: 'none',
                              padding: '6px 12px',
                              borderRadius: '6px',
                              fontSize: '12px',
                              cursor: 'pointer',
                              fontWeight: '500'
                            }}
                          >
                            📄 View
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </>
            )}
          </>
        )}

        {/* Documents & Files Section */}
        {reportFilter === 'bills_receipts' && (
          <>
            {billsReceiptsLoading ? (
              <div style={{ textAlign: 'center', padding: '40px' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>📋</div>
                <h3>Loading Documents & Files...</h3>
                <p>Fetching uploaded documents and files</p>
              </div>
            ) : (
              <>
                {/* Filter uploaded receipts from bills receipts data */}
                {(() => {
                  const receiptsWithFiles = billsReceipts.filter(req => 
                    req.receipt_files && req.receipt_files.length > 0
                  );
                  
                  return receiptsWithFiles.length === 0 ? (
                    <div style={{ textAlign: 'center', padding: '40px' }}>
                      <div style={{ fontSize: '48px', marginBottom: '16px' }}>📋</div>
                      <h3>No Documents & Files</h3>
                      <p>No documents or files have been uploaded yet by your contractor.</p>
                    </div>
                  ) : (
                    <>
                      {/* Simple Receipt Files Grid */}
                      <div className="receipts-grid" style={{ 
                        display: 'grid', 
                        gap: '20px',
                        gridTemplateColumns: 'repeat(auto-fill, minmax(350px, 1fr))'
                      }}>
                        {receiptsWithFiles.map(request => 
                          request.receipt_files.map((file, fileIndex) => (
                            <div 
                              key={`${request.id}-${fileIndex}`} 
                              className="receipt-card"
                              style={{
                                background: 'white',
                                border: '1px solid #e9ecef',
                                borderRadius: '12px',
                                padding: '20px',
                                boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                                transition: 'all 0.3s ease'
                              }}
                            >
                              {/* Document Preview */}
                              <div style={{ marginBottom: '15px' }}>
                                <DocumentPreview 
                                  file={file}
                                  onFullView={(file) => window.open(`/buildhub/backend/${file.file_path}`, '_blank')}
                                />
                              </div>

                              {/* File Info */}
                              <div style={{ 
                                display: 'flex', 
                                alignItems: 'center',
                                gap: '12px',
                                marginBottom: '15px'
                              }}>
                                <div style={{
                                  width: '40px',
                                  height: '40px',
                                  background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                  borderRadius: '8px',
                                  display: 'flex',
                                  alignItems: 'center',
                                  justifyContent: 'center',
                                  fontSize: '16px'
                                }}>
                                  {file.original_name.toLowerCase().includes('.pdf') ? '📄' : 
                                   file.original_name.toLowerCase().match(/\.(jpg|jpeg|png|gif)$/) ? '🖼️' : '📋'}
                                </div>
                                <div style={{ flex: 1 }}>
                                  <h3 style={{ 
                                    margin: '0 0 4px 0', 
                                    color: '#2c3e50',
                                    fontSize: '14px',
                                    fontWeight: '600'
                                  }}>
                                    {file.original_name}
                                  </h3>
                                  <p style={{ 
                                    margin: '0', 
                                    color: '#6c757d', 
                                    fontSize: '12px'
                                  }}>
                                    {(file.file_size / 1024).toFixed(1)} KB
                                  </p>
                                </div>
                              </div>

                              {/* Description */}
                              <div style={{ marginBottom: '15px' }}>
                                <h4 style={{ 
                                  margin: '0 0 8px 0', 
                                  color: '#495057',
                                  fontSize: '14px',
                                  fontWeight: '600'
                                }}>
                                  {request.request_type === 'custom' ? request.request_title : `${request.stage_name || request.request_title}`}
                                </h4>
                                {request.description && (
                                  <p style={{ 
                                    margin: '0', 
                                    color: '#6c757d', 
                                    fontSize: '13px',
                                    lineHeight: '1.4'
                                  }}>
                                    {request.description}
                                  </p>
                                )}
                              </div>

                              {/* Upload Info */}
                              <div style={{ 
                                paddingTop: '15px',
                                borderTop: '1px solid #f1f3f4'
                              }}>
                                <span style={{ 
                                  color: '#6c757d', 
                                  fontSize: '12px'
                                }}>
                                  Uploaded: {formatDate(request.request_date)}
                                </span>
                              </div>
                            </div>
                          ))
                        )}
                      </div>
                    </>
                  );
                })()}
              </>
            )}
          </>
        )}

        {/* Contractor Documents Section */}
        {reportFilter === 'contractor_documents' && (
          <>
            {contractorDocumentsLoading ? (
              <div style={{ textAlign: 'center', padding: '40px' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>🏗️</div>
                <h3>Loading Contractor Documents...</h3>
                <p>Fetching documents uploaded by contractors</p>
              </div>
            ) : (
              <>
                {/* Statistics Summary */}
                {contractorDocumentsStats && Object.keys(contractorDocumentsStats).length > 0 && (
                  <div style={{ 
                    background: 'linear-gradient(135deg, #007bff 0%, #0056b3 100%)',
                    color: 'white',
                    padding: '20px',
                    borderRadius: '12px',
                    marginBottom: '20px'
                  }}>
                    <h3 style={{ margin: '0 0 15px 0', fontSize: '18px' }}>📊 Contractor Documents Summary</h3>
                    <div style={{ 
                      display: 'grid', 
                      gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', 
                      gap: '15px' 
                    }}>
                      <div style={{ textAlign: 'center' }}>
                        <div style={{ fontSize: '24px', fontWeight: 'bold' }}>
                          {contractorDocumentsStats.total_documents || 0}
                        </div>
                        <div style={{ fontSize: '12px', opacity: 0.9 }}>Total Documents</div>
                      </div>
                      <div style={{ textAlign: 'center' }}>
                        <div style={{ fontSize: '24px', fontWeight: 'bold' }}>
                          {Object.keys(contractorDocumentsStats.document_types || {}).length}
                        </div>
                        <div style={{ fontSize: '12px', opacity: 0.9 }}>Document Types</div>
                      </div>
                    </div>
                  </div>
                )}

                {/* Filter contractor documents with files */}
                {(() => {
                  const documentsWithFiles = contractorDocuments.filter(doc => 
                    doc.receipt_files && doc.receipt_files.length > 0
                  );
                  
                  return documentsWithFiles.length === 0 ? (
                    <div style={{ textAlign: 'center', padding: '40px' }}>
                      <div style={{ fontSize: '48px', marginBottom: '16px' }}>🏗️</div>
                      <h3>No Contractor Documents</h3>
                      <p>No documents have been uploaded by contractors yet.</p>
                      <p style={{ color: '#6c757d', fontSize: '14px' }}>
                        Contractors will upload receipts, bills, and certificates during construction progress.
                      </p>
                    </div>
                  ) : (
                    <>
                      {/* Contractor Documents Grid */}
                      <div className="receipts-grid" style={{ 
                        display: 'grid', 
                        gap: '20px',
                        gridTemplateColumns: 'repeat(auto-fill, minmax(350px, 1fr))'
                      }}>
                        {documentsWithFiles.map(document => 
                          document.receipt_files.map((file, fileIndex) => (
                            <div 
                              key={`${document.id}-${fileIndex}`} 
                              className="receipt-card"
                              style={{
                                background: 'white',
                                border: '1px solid #e9ecef',
                                borderRadius: '12px',
                                padding: '20px',
                                boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                                transition: 'all 0.3s ease',
                                cursor: 'pointer'
                              }}
                              onClick={() => window.open(`/buildhub/backend/${file.file_path}`, '_blank')}
                            >
                              {/* File Icon and Name */}
                              <div style={{ 
                                display: 'flex', 
                                alignItems: 'center',
                                gap: '12px',
                                marginBottom: '15px'
                              }}>
                                <div style={{
                                  width: '48px',
                                  height: '48px',
                                  background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                  borderRadius: '12px',
                                  display: 'flex',
                                  alignItems: 'center',
                                  justifyContent: 'center',
                                  fontSize: '20px'
                                }}>
                                  {document.file_type_icon || '📄'}
                                </div>
                                <div style={{ flex: 1 }}>
                                  <h3 style={{ 
                                    margin: '0 0 4px 0', 
                                    color: '#2c3e50',
                                    fontSize: '16px',
                                    fontWeight: '600'
                                  }}>
                                    {file.original_name}
                                  </h3>
                                  <p style={{ 
                                    margin: '0', 
                                    color: '#6c757d', 
                                    fontSize: '12px'
                                  }}>
                                    {document.file_size_kb} KB
                                  </p>
                                </div>
                              </div>

                              {/* Document Details */}
                              <div style={{ marginBottom: '15px' }}>
                                <div style={{ 
                                  display: 'flex', 
                                  alignItems: 'center', 
                                  gap: '8px',
                                  marginBottom: '8px'
                                }}>
                                  <span style={{ fontSize: '16px' }}>
                                    {document.stage_badge?.icon || '🏗️'}
                                  </span>
                                  <h4 style={{ 
                                    margin: '0', 
                                    color: '#495057',
                                    fontSize: '14px',
                                    fontWeight: '600'
                                  }}>
                                    {document.stage_name}
                                  </h4>
                                </div>
                                
                                <div style={{ 
                                  display: 'flex', 
                                  alignItems: 'center', 
                                  gap: '8px',
                                  marginBottom: '8px'
                                }}>
                                  <span style={{ fontSize: '14px' }}>
                                    {document.document_type_icon}
                                  </span>
                                  <span style={{ 
                                    color: '#6c757d', 
                                    fontSize: '13px',
                                    textTransform: 'capitalize'
                                  }}>
                                    {document.document_type.replace('_', ' ')}
                                  </span>
                                </div>

                                {document.description && (
                                  <p style={{ 
                                    margin: '8px 0 0 0', 
                                    color: '#6c757d', 
                                    fontSize: '13px',
                                    lineHeight: '1.4'
                                  }}>
                                    {document.description}
                                  </p>
                                )}
                              </div>

                              {/* Contractor Info */}
                              <div style={{ 
                                background: '#f8f9fa',
                                padding: '12px',
                                borderRadius: '8px',
                                marginBottom: '15px'
                              }}>
                                <div style={{ 
                                  display: 'flex', 
                                  alignItems: 'center', 
                                  gap: '8px',
                                  marginBottom: '4px'
                                }}>
                                  <span style={{ fontSize: '14px' }}>👷</span>
                                  <span style={{ 
                                    fontSize: '13px', 
                                    fontWeight: '600',
                                    color: '#495057'
                                  }}>
                                    {document.contractor_name}
                                  </span>
                                </div>
                                <div style={{ 
                                  fontSize: '12px', 
                                  color: '#6c757d'
                                }}>
                                  Uploaded: {document.upload_date_formatted}
                                </div>
                              </div>
                            </div>
                          ))
                        )}
                      </div>
                    </>
                  );
                })()}
              </>
            )}
          </>
        )}

      {/* Payment Details Modal */}
      {showPaymentDetails && selectedPaymentRequest && (
        <div 
          className="modal-overlay payment-details-modal" 
          onClick={(e) => {
            if (e.target === e.currentTarget) {
              setShowPaymentDetails(false);
              document.body.style.overflow = '';
              document.body.classList.remove('modal-open');
            }
          }}
          style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'rgba(0,0,0,0.8)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 999999,
          padding: '20px',
          overflowY: 'auto'
        }}>
          <div className="modal-content" style={{
            background: 'white',
            borderRadius: '16px',
            maxWidth: '800px',
            width: '100%',
            maxHeight: 'calc(100vh - 40px)',
            overflow: 'auto',
            boxShadow: '0 20px 60px rgba(0,0,0,0.3)',
            position: 'relative',
            zIndex: 1000000
          }}>
            <div className="modal-header" style={{
              padding: '24px',
              background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
              color: 'white',
              borderRadius: '16px 16px 0 0',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              position: 'sticky',
              top: 0,
              zIndex: 1000001
            }}>
              <div>
                <h2 style={{ margin: '0 0 8px 0' }}>
                  💰 Payment Request: {selectedPaymentRequest.request_type === 'custom' ? selectedPaymentRequest.request_title : `${selectedPaymentRequest.stage_name || selectedPaymentRequest.request_title} Stage`}
                </h2>
                <p style={{ margin: '0', opacity: '0.9' }}>
                  Requested by {selectedPaymentRequest.contractor_first_name} {selectedPaymentRequest.contractor_last_name} on {formatDate(selectedPaymentRequest.request_date)}
                  {selectedPaymentRequest.request_type === 'custom' && selectedPaymentRequest.category && (
                    <span style={{ 
                      marginLeft: '8px', 
                      padding: '2px 6px', 
                      background: 'rgba(255,255,255,0.2)', 
                      borderRadius: '8px', 
                      fontSize: '12px' 
                    }}>
                      {selectedPaymentRequest.category}
                    </span>
                  )}
                </p>
              </div>
              <button 
                onClick={() => {
                  setShowPaymentDetails(false);
                  // Restore body scroll when modal closes
                  document.body.style.overflow = '';
                  document.body.classList.remove('modal-open');
                }}
                style={{
                  background: 'rgba(255,255,255,0.2)',
                  border: 'none',
                  color: 'white',
                  fontSize: '24px',
                  width: '40px',
                  height: '40px',
                  borderRadius: '50%',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center'
                }}
              >
                ×
              </button>
            </div>

            <div className="modal-body" style={{ 
              padding: '24px',
              maxHeight: 'calc(100vh - 200px)',
              overflowY: 'auto'
            }}>
              {/* Payment Summary */}
              <div className="payment-summary" style={{
                background: '#f8f9fa',
                padding: '20px',
                borderRadius: '12px',
                marginBottom: '24px',
                textAlign: 'center'
              }}>
                <div style={{ fontSize: '32px', fontWeight: '700', color: '#667eea', marginBottom: '8px' }}>
                  ₹{parseFloat(selectedPaymentRequest.requested_amount).toLocaleString()}
                </div>
                <div style={{ fontSize: '16px', color: '#6c757d', marginBottom: '12px' }}>
                  {selectedPaymentRequest.request_type === 'custom' ? (
                    <>Custom payment request - {selectedPaymentRequest.category || 'Additional work'}</>
                  ) : (
                    <>{selectedPaymentRequest.completion_percentage}% of {selectedPaymentRequest.stage_name || selectedPaymentRequest.request_title} stage completed</>
                  )}
                </div>
                {getPaymentStatusBadge(selectedPaymentRequest.status)}
              </div>

              {/* Work Details */}
              <div className="work-details" style={{ marginBottom: '24px' }}>
                <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                  🏗️ Work Details
                </h3>
                
                <div style={{ 
                  display: 'grid', 
                  gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', 
                  gap: '15px',
                  marginTop: '15px',
                  marginBottom: '20px'
                }}>
                  <div style={{ textAlign: 'center', padding: '15px', background: '#f8f9fa', borderRadius: '8px' }}>
                    <div style={{ fontSize: '1.2rem', fontWeight: '600', color: '#667eea' }}>
                      {selectedPaymentRequest.labor_count || 'N/A'}
                    </div>
                    <div style={{ fontSize: '0.9rem', color: '#6c757d' }}>Workers</div>
                  </div>
                  
                  {selectedPaymentRequest.work_start_date && (
                    <div style={{ textAlign: 'center', padding: '15px', background: '#f8f9fa', borderRadius: '8px' }}>
                      <div style={{ fontSize: '1rem', fontWeight: '600', color: '#667eea' }}>
                        {new Date(selectedPaymentRequest.work_start_date).toLocaleDateString('en-IN')}
                      </div>
                      <div style={{ fontSize: '0.9rem', color: '#6c757d' }}>Start Date</div>
                    </div>
                  )}
                  
                  {selectedPaymentRequest.work_end_date && (
                    <div style={{ textAlign: 'center', padding: '15px', background: '#f8f9fa', borderRadius: '8px' }}>
                      <div style={{ fontSize: '1rem', fontWeight: '600', color: '#667eea' }}>
                        {new Date(selectedPaymentRequest.work_end_date).toLocaleDateString('en-IN')}
                      </div>
                      <div style={{ fontSize: '0.9rem', color: '#6c757d' }}>End Date</div>
                    </div>
                  )}
                </div>

                {selectedPaymentRequest.work_description && (
                  <div style={{
                    background: '#f8f9fa',
                    padding: '15px',
                    borderRadius: '8px',
                    marginBottom: '15px'
                  }}>
                    <h4 style={{ margin: '0 0 10px 0', color: '#2c3e50' }}>Work Description</h4>
                    <p style={{ margin: '0', lineHeight: '1.5' }}>{selectedPaymentRequest.work_description}</p>
                  </div>
                )}

                {selectedPaymentRequest.contractor_notes && (
                  <div style={{
                    background: '#e3f2fd',
                    padding: '15px',
                    borderRadius: '8px',
                    border: '1px solid #bbdefb'
                  }}>
                    <h4 style={{ margin: '0 0 10px 0', color: '#1976d2' }}>Contractor Notes</h4>
                    <p style={{ margin: '0', lineHeight: '1.5' }}>{selectedPaymentRequest.contractor_notes}</p>
                  </div>
                )}
              </div>

              {/* Quality Checks */}
              <div className="quality-checks" style={{ marginBottom: '24px' }}>
                <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                  ✅ Quality & Compliance
                </h3>
                <div style={{ display: 'flex', gap: '20px', marginTop: '15px' }}>
                  <div style={{ 
                    display: 'flex', 
                    alignItems: 'center', 
                    gap: '8px',
                    padding: '10px 15px',
                    background: selectedPaymentRequest.quality_check ? '#d4edda' : '#f8d7da',
                    borderRadius: '8px',
                    border: `1px solid ${selectedPaymentRequest.quality_check ? '#c3e6cb' : '#f5c6cb'}`
                  }}>
                    <span style={{ fontSize: '18px' }}>
                      {selectedPaymentRequest.quality_check ? '✅' : '❌'}
                    </span>
                    <span style={{ 
                      color: selectedPaymentRequest.quality_check ? '#155724' : '#721c24',
                      fontWeight: '600'
                    }}>
                      Quality Check {selectedPaymentRequest.quality_check ? 'Completed' : 'Pending'}
                    </span>
                  </div>
                  
                  <div style={{ 
                    display: 'flex', 
                    alignItems: 'center', 
                    gap: '8px',
                    padding: '10px 15px',
                    background: selectedPaymentRequest.safety_compliance ? '#d4edda' : '#f8d7da',
                    borderRadius: '8px',
                    border: `1px solid ${selectedPaymentRequest.safety_compliance ? '#c3e6cb' : '#f5c6cb'}`
                  }}>
                    <span style={{ fontSize: '18px' }}>
                      {selectedPaymentRequest.safety_compliance ? '✅' : '❌'}
                    </span>
                    <span style={{ 
                      color: selectedPaymentRequest.safety_compliance ? '#155724' : '#721c24',
                      fontWeight: '600'
                    }}>
                      Safety Compliance {selectedPaymentRequest.safety_compliance ? 'Verified' : 'Pending'}
                    </span>
                  </div>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="payment-actions" style={{
                display: 'flex',
                gap: '15px',
                justifyContent: 'center',
                marginTop: '30px'
              }}>
                {selectedPaymentRequest.status === 'pending' && (
                  <>
                    <button
                      onClick={() => {
                        const reason = prompt('Please provide a reason for rejection:');
                        if (reason) {
                          respondToPaymentRequest(selectedPaymentRequest.id, 'reject', '', reason);
                        }
                      }}
                      style={{
                        background: '#dc3545',
                        border: 'none',
                        color: 'white',
                        padding: '12px 24px',
                        borderRadius: '8px',
                        cursor: 'pointer',
                        fontSize: '16px',
                        fontWeight: '600'
                      }}
                    >
                      ❌ Reject Request
                    </button>
                    
                    <button
                      onClick={() => respondToPaymentRequest(selectedPaymentRequest.id, 'approve')}
                      style={{
                        background: '#28a745',
                        border: 'none',
                        color: 'white',
                        padding: '12px 24px',
                        borderRadius: '8px',
                        cursor: 'pointer',
                        fontSize: '16px',
                        fontWeight: '600'
                      }}
                    >
                      ✅ Approve Request
                    </button>
                  </>
                )}
                
                {selectedPaymentRequest.status === 'approved' && (
                  <button
                    onClick={() => initiatePayment(selectedPaymentRequest)}
                    disabled={paymentProcessing}
                    style={{
                      background: '#667eea',
                      border: 'none',
                      color: 'white',
                      padding: '15px 30px',
                      borderRadius: '8px',
                      cursor: paymentProcessing ? 'not-allowed' : 'pointer',
                      fontSize: '18px',
                      fontWeight: '600',
                      opacity: paymentProcessing ? 0.7 : 1
                    }}
                  >
                    {paymentProcessing ? '⏳ Processing Payment...' : '💳 Pay ₹' + parseFloat(selectedPaymentRequest.requested_amount).toLocaleString()}
                  </button>
                )}
                
                {selectedPaymentRequest.status === 'paid' && (
                  <div style={{
                    padding: '15px 30px',
                    background: '#d4edda',
                    border: '1px solid #c3e6cb',
                    borderRadius: '8px',
                    textAlign: 'center',
                    color: '#155724',
                    fontWeight: '600'
                  }}>
                    ✅ Payment Completed on {selectedPaymentRequest.payment_date_formatted}
                  </div>
                )}
                
                {selectedPaymentRequest.status === 'rejected' && (
                  <div style={{
                    padding: '15px 30px',
                    background: '#f8d7da',
                    border: '1px solid #f5c6cb',
                    borderRadius: '8px',
                    textAlign: 'center',
                    color: '#721c24',
                    fontWeight: '600'
                  }}>
                    ❌ Request Rejected: {selectedPaymentRequest.rejection_reason}
                  </div>
                )}

                {/* Receipt Upload Section for Alternative Payments */}
                {(selectedPaymentRequest.status === 'approved' || selectedPaymentRequest.status === 'paid') && (
                  <div style={{ marginTop: '30px' }}>
                    {/* Show uploaded receipt information if available */}
                    {selectedPaymentRequest.receipt_files && selectedPaymentRequest.receipt_files.length > 0 ? (
                      <div style={{
                        background: '#e3f2fd',
                        padding: '20px',
                        borderRadius: '12px',
                        border: '1px solid #bbdefb'
                      }}>
                        <h3 style={{ 
                          color: '#1976d2', 
                          marginBottom: '15px',
                          display: 'flex',
                          alignItems: 'center',
                          gap: '8px'
                        }}>
                          📄 Uploaded Payment Receipt
                        </h3>
                        
                        <div style={{ marginBottom: '15px' }}>
                          <div style={{ 
                            display: 'grid', 
                            gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', 
                            gap: '12px',
                            marginBottom: '15px'
                          }}>
                            {selectedPaymentRequest.payment_method && (
                              <div style={{ background: 'white', padding: '12px', borderRadius: '6px' }}>
                                <strong style={{ fontSize: '12px', color: '#1565c0', display: 'block', marginBottom: '4px' }}>
                                  PAYMENT METHOD
                                </strong>
                                <span style={{ color: '#1976d2', fontWeight: '500' }}>
                                  {selectedPaymentRequest.payment_method === 'bank_transfer' && '🏦 Bank Transfer'}
                                  {selectedPaymentRequest.payment_method === 'upi' && '📱 UPI Payment'}
                                  {selectedPaymentRequest.payment_method === 'cash' && '💵 Cash Payment'}
                                  {selectedPaymentRequest.payment_method === 'cheque' && '📝 Cheque Payment'}
                                  {selectedPaymentRequest.payment_method === 'other' && '💳 Other'}
                                </span>
                              </div>
                            )}
                            
                            {selectedPaymentRequest.transaction_reference && (
                              <div style={{ background: 'white', padding: '12px', borderRadius: '6px' }}>
                                <strong style={{ fontSize: '12px', color: '#1565c0', display: 'block', marginBottom: '4px' }}>
                                  TRANSACTION REFERENCE
                                </strong>
                                <span style={{ color: '#1976d2', fontWeight: '500' }}>
                                  {selectedPaymentRequest.transaction_reference}
                                </span>
                              </div>
                            )}
                            
                            {selectedPaymentRequest.payment_date && (
                              <div style={{ background: 'white', padding: '12px', borderRadius: '6px' }}>
                                <strong style={{ fontSize: '12px', color: '#1565c0', display: 'block', marginBottom: '4px' }}>
                                  PAYMENT DATE
                                </strong>
                                <span style={{ color: '#1976d2', fontWeight: '500' }}>
                                  {selectedPaymentRequest.payment_date_formatted || new Date(selectedPaymentRequest.payment_date).toLocaleDateString('en-IN')}
                                </span>
                              </div>
                            )}
                            
                            {selectedPaymentRequest.verification_status && (
                              <div style={{ background: 'white', padding: '12px', borderRadius: '6px' }}>
                                <strong style={{ fontSize: '12px', color: '#1565c0', display: 'block', marginBottom: '4px' }}>
                                  VERIFICATION STATUS
                                </strong>
                                <span style={{ 
                                  padding: '4px 8px', 
                                  borderRadius: '12px', 
                                  fontSize: '12px', 
                                  fontWeight: '600',
                                  background: selectedPaymentRequest.verification_status === 'pending' ? '#fff3cd' :
                                            selectedPaymentRequest.verification_status === 'verified' ? '#d4edda' : '#f8d7da',
                                  color: selectedPaymentRequest.verification_status === 'pending' ? '#856404' :
                                        selectedPaymentRequest.verification_status === 'verified' ? '#155724' : '#721c24'
                                }}>
                                  {selectedPaymentRequest.verification_status === 'pending' && '⏳ Pending Verification'}
                                  {selectedPaymentRequest.verification_status === 'verified' && '✅ Verified'}
                                  {selectedPaymentRequest.verification_status === 'rejected' && '❌ Rejected'}
                                </span>
                              </div>
                            )}
                          </div>
                          
                          <div style={{ background: 'white', padding: '16px', borderRadius: '8px', border: '1px solid #bbdefb' }}>
                            <strong style={{ display: 'block', marginBottom: '12px', color: '#1976d2' }}>
                              Uploaded Files:
                            </strong>
                            <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                              {selectedPaymentRequest.receipt_files.map((file, index) => (
                                <div key={index} style={{
                                  display: 'flex',
                                  alignItems: 'center',
                                  gap: '12px',
                                  padding: '12px',
                                  background: '#f8f9fa',
                                  border: '1px solid #e9ecef',
                                  borderRadius: '6px'
                                }}>
                                  <span style={{ fontSize: '20px' }}>
                                    {file.file_type && file.file_type.startsWith('image/') ? '🖼️' : '📄'}
                                  </span>
                                  <div style={{ flex: 1 }}>
                                    <div style={{ fontWeight: '500', color: '#2c3e50', fontSize: '14px' }}>
                                      {file.original_name}
                                    </div>
                                    {file.file_size && (
                                      <div style={{ fontSize: '12px', color: '#6c757d' }}>
                                        {(file.file_size / 1024 / 1024).toFixed(2)} MB
                                      </div>
                                    )}
                                  </div>
                                  <a 
                                    href={`/buildhub/backend/${file.file_path}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    style={{
                                      background: '#2196f3',
                                      color: 'white',
                                      padding: '6px 12px',
                                      borderRadius: '4px',
                                      textDecoration: 'none',
                                      fontSize: '12px',
                                      fontWeight: '500'
                                    }}
                                  >
                                    👁️ View
                                  </a>
                                </div>
                              ))}
                            </div>
                          </div>
                          
                          {selectedPaymentRequest.verification_notes && (
                            <div style={{ 
                              background: 'white', 
                              padding: '12px', 
                              borderRadius: '6px', 
                              border: '1px solid #bbdefb',
                              marginTop: '12px'
                            }}>
                              <strong style={{ display: 'block', marginBottom: '8px', color: '#1976d2' }}>
                                Verification Notes:
                              </strong>
                              <p style={{ margin: '0', color: '#1565c0', fontSize: '14px', lineHeight: '1.5' }}>
                                {selectedPaymentRequest.verification_notes}
                              </p>
                            </div>
                          )}
                          
                          {selectedPaymentRequest.verified_at && (
                            <div style={{ textAlign: 'right', paddingTop: '8px', borderTop: '1px solid #e3f2fd' }}>
                              <small style={{ color: '#6c757d', fontSize: '12px' }}>
                                Verified on {selectedPaymentRequest.verified_at_formatted || formatDate(selectedPaymentRequest.verified_at)}
                              </small>
                            </div>
                          )}
                        </div>
                      </div>
                    ) : (
                      <div style={{
                        background: '#e3f2fd',
                        padding: '20px',
                        borderRadius: '12px',
                        border: '1px solid #bbdefb'
                      }}>
                        <h3 style={{ 
                          color: '#1976d2', 
                          marginBottom: '15px',
                          display: 'flex',
                          alignItems: 'center',
                          gap: '8px'
                        }}>
                          📄 Payment Receipt Upload
                        </h3>
                        <p style={{ 
                          margin: '0 0 15px 0', 
                          color: '#1565c0',
                          lineHeight: '1.5'
                        }}>
                          If you've made payment via bank transfer, UPI, or other methods, please upload your payment receipt for verification.
                        </p>
                        <button
                          onClick={handleReceiptUpload}
                          style={{
                            background: 'linear-gradient(135deg, #1976d2 0%, #1565c0 100%)',
                            border: 'none',
                            color: 'white',
                            padding: '12px 24px',
                            borderRadius: '8px',
                            cursor: 'pointer',
                            fontSize: '16px',
                            fontWeight: '600',
                            display: 'flex',
                            alignItems: 'center',
                            gap: '8px'
                          }}
                        >
                          📤 Upload Payment Receipt
                        </button>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Report Details Modal */}
      {showReportDetails && selectedReport && (
        <div className="modal-overlay" style={{
          position: 'fixed',
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          background: 'rgba(0,0,0,0.8)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          zIndex: 1000,
          padding: '20px'
        }}>
          <div className="modal-content" style={{
            background: 'white',
            borderRadius: '16px',
            maxWidth: '1000px',
            width: '100%',
            maxHeight: '90vh',
            overflow: 'auto',
            boxShadow: '0 20px 60px rgba(0,0,0,0.3)'
          }}>
            <div className="modal-header" style={{
              padding: '24px',
              background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
              color: 'white',
              borderRadius: '16px 16px 0 0',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center'
            }}>
              <div>
                <h2 style={{ margin: '0 0 8px 0' }}>
                  {getReportTypeIcon(selectedReport.report_type)} {selectedReport.report_type.charAt(0).toUpperCase() + selectedReport.report_type.slice(1)} Progress Report
                </h2>
                <p style={{ margin: '0', opacity: '0.9' }}>
                  Submitted by {selectedReport.contractor_name} on {formatDate(selectedReport.created_at)}
                </p>
              </div>
              <button 
                onClick={() => setShowReportDetails(false)}
                style={{
                  background: 'rgba(255,255,255,0.2)',
                  border: 'none',
                  color: 'white',
                  fontSize: '24px',
                  width: '40px',
                  height: '40px',
                  borderRadius: '50%',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center'
                }}
              >
                ×
              </button>
            </div>

            <div className="modal-body" style={{ padding: '24px' }}>
              {selectedReport.report_data && (
                <div>
                  {/* Executive Summary */}
                  {selectedReport.report_data.summary && (
                    <div className="report-section" style={{ marginBottom: '30px' }}>
                      <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                        📋 Executive Summary
                      </h3>
                      <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', 
                        gap: '15px',
                        marginTop: '15px'
                      }}>
                        {Object.entries(selectedReport.report_data.summary).map(([key, value]) => (
                          <div key={key} style={{
                            textAlign: 'center',
                            padding: '15px',
                            background: '#f8f9fa',
                            borderRadius: '8px',
                            border: '1px solid #dee2e6'
                          }}>
                            <div style={{ fontSize: '1.5rem', fontWeight: '700', color: '#667eea' }}>
                              {value}
                            </div>
                            <div style={{ fontSize: '0.9rem', color: '#6c757d', textTransform: 'capitalize' }}>
                              {key.replace(/_/g, ' ')}
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Daily Updates */}
                  {selectedReport.report_data.daily_updates && selectedReport.report_data.daily_updates.length > 0 && (
                    <div className="report-section" style={{ marginBottom: '30px' }}>
                      <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                        🏗️ Work Progress Details
                      </h3>
                      {selectedReport.report_data.daily_updates.map((update, index) => (
                        <div key={index} style={{
                          marginTop: '15px',
                          padding: '15px',
                          background: '#f8f9fa',
                          borderRadius: '8px',
                          borderLeft: '4px solid #667eea'
                        }}>
                          <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '10px' }}>
                            <h4 style={{ margin: '0', color: '#2c3e50' }}>
                              {formatDate(update.update_date)}
                            </h4>
                            <span style={{
                              background: '#667eea',
                              color: 'white',
                              padding: '4px 12px',
                              borderRadius: '20px',
                              fontSize: '12px'
                            }}>
                              {update.construction_stage}
                            </span>
                          </div>
                          <p><strong>Work Done:</strong> {update.work_done_today}</p>
                          <div style={{ display: 'flex', gap: '20px', fontSize: '14px', color: '#6c757d' }}>
                            <span>Progress: +{update.incremental_completion_percentage}%</span>
                            <span>Hours: {update.working_hours}h</span>
                            <span>Weather: {update.weather_condition}</span>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}

                  {/* Photos */}
                  {selectedReport.report_data.photos && selectedReport.report_data.photos.length > 0 && (
                    <div className="report-section" style={{ marginBottom: '30px' }}>
                      <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                        📸 Progress Photos
                      </h3>
                      <div style={{ 
                        display: 'grid', 
                        gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', 
                        gap: '15px',
                        marginTop: '15px'
                      }}>
                        {selectedReport.report_data.photos.slice(0, 6).map((photo, index) => (
                          <div key={index} style={{ textAlign: 'center' }}>
                            <img 
                              src={photo.url} 
                              alt={`Progress ${index + 1}`}
                              style={{
                                width: '100%',
                                height: '150px',
                                objectFit: 'cover',
                                borderRadius: '8px',
                                border: '1px solid #dee2e6'
                              }}
                            />
                            <div style={{ marginTop: '8px', fontSize: '12px', color: '#6c757d' }}>
                              <div>{formatDate(photo.date)}</div>
                              <div>{photo.location}</div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              )}

              {/* Acknowledgment Section */}
              {selectedReport.status !== 'acknowledged' && (
                <div className="acknowledgment-section" style={{
                  marginTop: '30px',
                  padding: '20px',
                  background: '#e3f2fd',
                  borderRadius: '8px',
                  border: '1px solid #bbdefb'
                }}>
                  <h4 style={{ margin: '0 0 15px 0', color: '#1976d2' }}>✅ Acknowledge This Report</h4>
                  <p style={{ margin: '0 0 15px 0', fontSize: '14px', color: '#424242' }}>
                    Acknowledge that you have reviewed this progress report. You can optionally add feedback or comments.
                  </p>
                  <div style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
                    <button
                      onClick={() => acknowledgeReport(selectedReport.id)}
                      style={{
                        background: '#28a745',
                        color: 'white',
                        border: 'none',
                        padding: '10px 20px',
                        borderRadius: '6px',
                        fontWeight: '600',
                        cursor: 'pointer'
                      }}
                    >
                      ✅ Acknowledge Report
                    </button>
                    <span style={{ fontSize: '12px', color: '#6c757d' }}>
                      This will notify your contractor that you've reviewed the report
                    </span>
                  </div>
                </div>
              )}

              {selectedReport.status === 'acknowledged' && (
                <div style={{
                  marginTop: '20px',
                  padding: '15px',
                  background: '#d4edda',
                  borderRadius: '8px',
                  border: '1px solid #c3e6cb',
                  textAlign: 'center'
                }}>
                  <span style={{ color: '#155724', fontWeight: '600' }}>
                    ✅ Report Acknowledged on {formatDate(selectedReport.acknowledged_at)}
                  </span>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Report Details Modal */}
      {showReportDetails && selectedReport && (
        <div 
          className="modal-overlay report-details-modal" 
          onClick={(e) => {
            if (e.target === e.currentTarget) {
              setShowReportDetails(false);
            }
          }}
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(0,0,0,0.8)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 999999,
            padding: '20px',
            overflowY: 'auto'
          }}
        >
          <div className="modal-content" style={{
            background: 'white',
            borderRadius: '16px',
            maxWidth: '900px',
            width: '100%',
            maxHeight: 'calc(100vh - 40px)',
            overflow: 'auto',
            boxShadow: '0 20px 60px rgba(0,0,0,0.3)',
            position: 'relative'
          }}>
            <div className="modal-header" style={{
              padding: '24px',
              background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
              color: 'white',
              borderRadius: '16px 16px 0 0',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              position: 'sticky',
              top: 0,
              zIndex: 1000001
            }}>
              <div>
                <h2 style={{ margin: '0 0 8px 0' }}>
                  📅 Daily Progress Report - {selectedReport.construction_stage}
                </h2>
                <p style={{ margin: '0', opacity: '0.9' }}>
                  Submitted by {selectedReport.contractor_name} on {formatDate(selectedReport.created_at)}
                </p>
              </div>
              <button 
                onClick={() => setShowReportDetails(false)}
                style={{
                  background: 'rgba(255,255,255,0.2)',
                  border: 'none',
                  color: 'white',
                  fontSize: '24px',
                  width: '40px',
                  height: '40px',
                  borderRadius: '50%',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center'
                }}
              >
                ×
              </button>
            </div>

            <div className="modal-body" style={{ 
              padding: '24px',
              maxHeight: 'calc(100vh - 200px)',
              overflowY: 'auto'
            }}>
              {/* Progress Summary */}
              <div className="progress-summary" style={{
                background: '#f8f9fa',
                padding: '20px',
                borderRadius: '12px',
                marginBottom: '24px',
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))',
                gap: '15px'
              }}>
                <div style={{ textAlign: 'center' }}>
                  <div style={{ fontSize: '24px', fontWeight: '700', color: '#28a745' }}>
                    +{selectedReport.incremental_completion}%
                  </div>
                  <div style={{ fontSize: '12px', color: '#6c757d' }}>Daily Progress</div>
                </div>
                <div style={{ textAlign: 'center' }}>
                  <div style={{ fontSize: '24px', fontWeight: '700', color: '#667eea' }}>
                    {selectedReport.cumulative_completion}%
                  </div>
                  <div style={{ fontSize: '12px', color: '#6c757d' }}>Total Progress</div>
                </div>
                <div style={{ textAlign: 'center' }}>
                  <div style={{ fontSize: '24px', fontWeight: '700', color: '#ffc107' }}>
                    {selectedReport.working_hours}h
                  </div>
                  <div style={{ fontSize: '12px', color: '#6c757d' }}>Working Hours</div>
                </div>
                <div style={{ textAlign: 'center' }}>
                  <div style={{ fontSize: '24px', fontWeight: '700', color: '#17a2b8' }}>
                    {selectedReport.total_workers}
                  </div>
                  <div style={{ fontSize: '12px', color: '#6c757d' }}>Workers</div>
                </div>
              </div>

              {/* Work Details */}
              <div className="work-details" style={{ marginBottom: '24px' }}>
                <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                  🏗️ Work Completed Today
                </h3>
                <div style={{
                  background: '#f8f9fa',
                  padding: '15px',
                  borderRadius: '8px',
                  marginTop: '15px',
                  lineHeight: '1.6'
                }}>
                  {selectedReport.work_done_today}
                </div>
              </div>

              {/* Project Information */}
              <div className="project-info" style={{ marginBottom: '24px' }}>
                <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                  📋 Project Information
                </h3>
                <div style={{ 
                  display: 'grid', 
                  gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', 
                  gap: '15px',
                  marginTop: '15px'
                }}>
                  <div style={{ background: '#f8f9fa', padding: '15px', borderRadius: '8px' }}>
                    <strong style={{ color: '#667eea', display: 'block', marginBottom: '5px' }}>
                      Construction Stage
                    </strong>
                    {selectedReport.construction_stage}
                  </div>
                  <div style={{ background: '#f8f9fa', padding: '15px', borderRadius: '8px' }}>
                    <strong style={{ color: '#667eea', display: 'block', marginBottom: '5px' }}>
                      Weather Condition
                    </strong>
                    {selectedReport.weather_condition}
                  </div>
                  <div style={{ background: '#f8f9fa', padding: '15px', borderRadius: '8px' }}>
                    <strong style={{ color: '#667eea', display: 'block', marginBottom: '5px' }}>
                      Location Status
                    </strong>
                    <span style={{
                      padding: '4px 8px',
                      borderRadius: '12px',
                      fontSize: '12px',
                      fontWeight: '600',
                      background: selectedReport.location_verified ? '#d4edda' : '#fff3cd',
                      color: selectedReport.location_verified ? '#155724' : '#856404'
                    }}>
                      {selectedReport.location_status}
                    </span>
                  </div>
                  <div style={{ background: '#f8f9fa', padding: '15px', borderRadius: '8px' }}>
                    <strong style={{ color: '#667eea', display: 'block', marginBottom: '5px' }}>
                      Update Date
                    </strong>
                    {new Date(selectedReport.update_date).toLocaleDateString('en-IN')}
                  </div>
                </div>
              </div>

              {/* Labour Information */}
              {selectedReport.labour_entries_count > 0 && (
                <div className="labour-info" style={{ marginBottom: '24px' }}>
                  <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                    👷 Labour Information
                  </h3>
                  <div style={{ 
                    display: 'grid', 
                    gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', 
                    gap: '15px',
                    marginTop: '15px'
                  }}>
                    <div style={{ background: '#f8f9fa', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                      <div style={{ fontSize: '20px', fontWeight: '700', color: '#667eea' }}>
                        {selectedReport.labour_entries_count}
                      </div>
                      <div style={{ fontSize: '12px', color: '#6c757d' }}>Worker Types</div>
                    </div>
                    <div style={{ background: '#f8f9fa', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                      <div style={{ fontSize: '20px', fontWeight: '700', color: '#28a745' }}>
                        {selectedReport.total_workers}
                      </div>
                      <div style={{ fontSize: '12px', color: '#6c757d' }}>Total Workers</div>
                    </div>
                    <div style={{ background: '#f8f9fa', padding: '15px', borderRadius: '8px', textAlign: 'center' }}>
                      <div style={{ fontSize: '20px', fontWeight: '700', color: '#ffc107' }}>
                        {parseFloat(selectedReport.avg_productivity || 0).toFixed(1)}/5
                      </div>
                      <div style={{ fontSize: '12px', color: '#6c757d' }}>Avg Productivity</div>
                    </div>
                  </div>
                  
                  {selectedReport.worker_types && selectedReport.worker_types.length > 0 && (
                    <div style={{ marginTop: '15px' }}>
                      <strong style={{ color: '#2c3e50', display: 'block', marginBottom: '10px' }}>
                        Worker Types:
                      </strong>
                      <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px' }}>
                        {selectedReport.worker_types.map((type, index) => (
                          <span key={index} style={{
                            background: '#e3f2fd',
                            color: '#1976d2',
                            padding: '6px 12px',
                            borderRadius: '16px',
                            fontSize: '12px',
                            fontWeight: '600',
                            border: '1px solid #bbdefb'
                          }}>
                            {type}
                          </span>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              )}

              {/* Site Issues */}
              {selectedReport.site_issues && (
                <div className="site-issues" style={{ marginBottom: '24px' }}>
                  <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                    ⚠️ Site Issues
                  </h3>
                  <div style={{
                    background: '#fff3cd',
                    padding: '15px',
                    borderRadius: '8px',
                    marginTop: '15px',
                    border: '1px solid #ffeaa7',
                    lineHeight: '1.6'
                  }}>
                    {selectedReport.site_issues}
                  </div>
                </div>
              )}

              {/* Photos Section */}
              {selectedReport.has_photos && selectedReport.photos && selectedReport.photos.length > 0 && (
                <div className="photos-section" style={{ marginBottom: '24px' }}>
                  <h3 style={{ color: '#2c3e50', borderBottom: '2px solid #e9ecef', paddingBottom: '10px' }}>
                    📸 Progress Photos ({selectedReport.photos.length})
                  </h3>
                  <div style={{ 
                    display: 'grid', 
                    gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', 
                    gap: '15px',
                    marginTop: '15px'
                  }}>
                    {selectedReport.photos.slice(0, 6).map((photo, index) => (
                      <div key={index} style={{ 
                        background: '#f8f9fa', 
                        padding: '10px', 
                        borderRadius: '8px',
                        textAlign: 'center'
                      }}>
                        <img 
                          src={photo.path || photo.url} 
                          alt={`Progress ${index + 1}`}
                          style={{
                            width: '100%',
                            height: '150px',
                            objectFit: 'cover',
                            borderRadius: '6px',
                            border: '1px solid #dee2e6'
                          }}
                          onError={(e) => {
                            e.target.style.display = 'none';
                            e.target.nextSibling.style.display = 'flex';
                          }}
                        />
                        <div style={{ 
                          display: 'none',
                          height: '150px',
                          alignItems: 'center',
                          justifyContent: 'center',
                          background: '#e9ecef',
                          borderRadius: '6px',
                          color: '#6c757d'
                        }}>
                          📷 Photo unavailable
                        </div>
                        <div style={{ marginTop: '8px', fontSize: '12px', color: '#6c757d' }}>
                          Photo {index + 1}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Acknowledgment Section */}
              <div className="acknowledgment-section" style={{
                marginTop: '30px',
                padding: '20px',
                background: '#e3f2fd',
                borderRadius: '8px',
                border: '1px solid #bbdefb',
                textAlign: 'center'
              }}>
                <h4 style={{ margin: '0 0 15px 0', color: '#1976d2' }}>✅ Report Reviewed</h4>
                <p style={{ margin: '0', fontSize: '14px', color: '#424242' }}>
                  This daily progress report has been successfully submitted and is available for your review.
                  The contractor will continue to provide regular updates on the project progress.
                </p>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Payment Method Selector */}
      <PaymentMethodSelector
        show={showPaymentSelector}
        amount={selectedPaymentRequest?.approved_amount || selectedPaymentRequest?.requested_amount || 0}
        paymentRequest={selectedPaymentRequest}
        onPaymentInitiated={handlePaymentInitiated}
        onCancel={handlePaymentCancel}
      />

      {/* Payment Receipt Upload */}
      <PaymentReceiptUpload
        show={showReceiptUpload}
        paymentId={selectedPaymentRequest?.id}
        paymentMethod={selectedPaymentRequest?.payment_method || "bank_transfer"}
        amount={selectedPaymentRequest?.requested_amount}
        onUploadComplete={handleReceiptUploadComplete}
        onCancel={handleReceiptUploadCancel}
      />
    </div>
    </div>
  );
};

export default HomeownerProgressReports;