import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import '../styles/ContractorDashboard.css';
import '../styles/InboxEstimationForm.css';
import '../styles/BlueGlassTheme.css';
import '../styles/SoftSidebar.css';
import '../styles/PaymentHistory.css';
import './WidgetColors.css';
import { badgeClass, formatStatus } from '../utils/status';
import { useToast } from './ToastProvider.jsx';
import ContractorProfileButton from './ContractorProfileButton';
import BuildHubSeal from './BuildHubSeal';
import TechnicalDetailsDisplay from './TechnicalDetailsDisplay';
import ConstructionProgressUpdate from './ConstructionProgressUpdate';
import ContractorConstructionTimeline from './ContractorConstructionTimeline';
import EnhancedProgressUpdate from './EnhancedProgressUpdate';
import ProgressReportGenerator from './ProgressReportGenerator';
import ContractorPaymentManager from './ContractorPaymentManager';
import StagePaymentWithdrawals from './StagePaymentWithdrawals';
import SimplePaymentRequestForm from './SimplePaymentRequestForm.jsx';
import CustomPaymentRequestForm from './CustomPaymentRequestForm.jsx';
import PaymentHistory from './PaymentHistory.jsx';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';

import ConfirmModal from './ConfirmModal';
import EstimationForm from './EstimationForm';

const ContractorDashboard = () => {
  const navigate = useNavigate();
  const toast = useToast();
  const [activeTab, setActiveTab] = useState('overview');
  const [user, setUser] = useState(null);
  const [layoutRequests, setLayoutRequests] = useState([]);
  const [recentPaidPayments, setRecentPaidPayments] = useState([]);
  const [inbox, setInbox] = useState([]);
  const [ackDateById, setAckDateById] = useState({});
  const [ackOpenById, setAckOpenById] = useState({});
  const [myProposals, setMyProposals] = useState([]);
  const [myEstimates, setMyEstimates] = useState([]);
  const [constructionEstimates, setConstructionEstimates] = useState([]);
  const [constructionDetails, setConstructionDetails] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [collapsed] = useState(false);
  const [showRequestDetails, setShowRequestDetails] = useState({});
  const [expandedProject, setExpandedProject] = useState(null);
  // Live totals for inbox estimate form
  const estimateFormRef = useRef(null);
  const [materialsTotal, setMaterialsTotal] = useState(0);
  const [laborTotal, setLaborTotal] = useState(0);
  const [utilitiesTotal, setUtilitiesTotal] = useState(0);
  const [miscTotal, setMiscTotal] = useState(0);
  const [grandTotal, setGrandTotal] = useState(0);
  const [recentReportUrl, setRecentReportUrl] = useState('');
  
  // Draft functionality
  const [draftData, setDraftData] = useState({});
  const [lastSaved, setLastSaved] = useState({});
  const [autoSaveTimeout, setAutoSaveTimeout] = useState(null);
  const [progressView, setProgressView] = useState('submit'); // 'submit' or 'timeline'
  const [showReportGenerator, setShowReportGenerator] = useState(false);
  const [currentEstimationItem, setCurrentEstimationItem] = useState(null);
  const [showEstimationForm, setShowEstimationForm] = useState(false);
  
  // Confirmation modal state
  const [confirmModal, setConfirmModal] = useState({
    isOpen: false,
    title: '',
    message: '',
    onConfirm: null,
    type: 'danger'
  });

  // Helper function to show confirmation dialog
  const showConfirmation = (title, message, onConfirm, type = 'danger') => {
    setConfirmModal({
      isOpen: true,
      title,
      message,
      onConfirm,
      type
    });
  };

  // Helper function to close confirmation dialog
  const closeConfirmation = () => {
    setConfirmModal({
      isOpen: false,
      title: '',
      message: '',
      onConfirm: null,
      type: 'danger'
    });
  };

  // Estimation form handlers
  const openEstimationForm = (inboxItem) => {
    setCurrentEstimationItem(inboxItem);
    setShowEstimationForm(true);
  };

  const closeEstimationForm = () => {
    setShowEstimationForm(false);
    setCurrentEstimationItem(null);
  };

  const handleEstimateSubmit = async (estimateData) => {
    try {
      const response = await fetch('/buildhub/backend/api/contractor/submit_estimate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(estimateData)
      });

      const result = await response.json();
      
      if (result.success) {
        toast.success(`Estimate submitted successfully! Total: ₹${result.data.total_cost?.toLocaleString('en-IN') || '0'}`);
        closeEstimationForm();
        
        // Refresh inbox and estimates
        const me = JSON.parse(sessionStorage.getItem('user') || '{}');
        if (me?.id) {
          try {
            const r = await fetch(`/buildhub/backend/api/contractor/get_inbox.php?contractor_id=${me.id}`, { credentials: 'include' });
            const j = await r.json().catch(() => ({}));
            if (j?.success) setInbox(Array.isArray(j.items) ? j.items : []);
          } catch {}
          
          try {
            const r2 = await fetch(`/buildhub/backend/api/contractor/get_my_estimates.php?contractor_id=${me.id}`, { credentials: 'include' });
            const j2 = await r2.json().catch(() => ({}));
            if (j2?.success) setMyEstimates(Array.isArray(j2.estimates) ? j2.estimates : []);
          } catch {}
        }
      } else {
        toast.error(result.message || 'Failed to submit estimate');
      }
    } catch (error) {
      console.error('Error submitting estimate:', error);
      toast.error('Error submitting estimate. Please try again.');
    }
  };

  const autoGrow = (el) => {
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight}px`;
  };

  // Draft functionality
  const saveDraft = async (sendId, formData) => {
    try {
      const me = JSON.parse(sessionStorage.getItem('user') || '{}');
      if (!me.id || !sendId) return;

      const response = await fetch('/buildhub/backend/api/contractor/save_estimate_draft.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          contractor_id: me.id,
          send_id: sendId,
          draft_data: formData
        })
      });

      const result = await response.json();
      if (result.success) {
        setLastSaved(prev => ({ ...prev, [sendId]: new Date().toLocaleTimeString() }));
      }
    } catch (error) {
      console.error('Error saving draft:', error);
    }
  };

  const loadDraft = async (sendId) => {
    try {
      const me = JSON.parse(sessionStorage.getItem('user') || '{}');
      if (!me.id || !sendId) return null;

      const response = await fetch(`/buildhub/backend/api/contractor/save_estimate_draft.php?contractor_id=${me.id}&send_id=${sendId}`, {
        credentials: 'include'
      });

      const result = await response.json();
      if (result.success && result.draft_data) {
        setDraftData(prev => ({ ...prev, [sendId]: result.draft_data }));
        setLastSaved(prev => ({ ...prev, [sendId]: new Date(result.last_saved).toLocaleTimeString() }));
        return result.draft_data;
      }
    } catch (error) {
      console.error('Error loading draft:', error);
    }
    return null;
  };

  const handleFormChange = (sendId, formEl) => {
    if (!formEl) return;

    // Clear existing timeout
    if (autoSaveTimeout) {
      clearTimeout(autoSaveTimeout);
    }

    // Collect form data
    const formData = {};
    const inputs = formEl.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
      if (input.name && input.value) {
        formData[input.name] = input.value;
      }
    });

    // Update local state
    setDraftData(prev => ({ ...prev, [sendId]: formData }));

    // Auto-save after 2 seconds of inactivity
    const timeout = setTimeout(() => {
      saveDraft(sendId, formData);
    }, 2000);

    setAutoSaveTimeout(timeout);
  };

  const populateFormFromDraft = (formEl, draftData) => {
    if (!formEl || !draftData) return;

    Object.entries(draftData).forEach(([name, value]) => {
      const input = formEl.querySelector(`[name="${name}"]`);
      if (input && !input.readOnly) { // Don't override readonly fields
        input.value = value;
        // Trigger change event to update calculations
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }
    });
  };

  const buildEstimateReport = async (formEl) => {
    try {
      const data = new FormData(formEl);
      const get = (k) => data.get(k) || '';
      const currentDate = new Date().toLocaleDateString('en-IN', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
      const contractorName = user?.first_name && user?.last_name ? 
        `${user.first_name} ${user.last_name}` : 
        'Contractor Name';
      
      // Create a temporary div to render the HTML content
      const tempDiv = document.createElement('div');
      tempDiv.style.position = 'absolute';
      tempDiv.style.left = '-9999px';
      tempDiv.style.top = '-9999px';
      tempDiv.style.width = '210mm'; // A4 width in mm
      tempDiv.style.padding = '20mm';
      tempDiv.style.backgroundColor = 'white';
      tempDiv.style.fontFamily = 'Times New Roman, serif';
      tempDiv.style.fontSize = '12px';
      tempDiv.style.lineHeight = '1.4';
      tempDiv.style.color = '#1a1a1a';
      
      const html = `
      <style>
        @page { 
          margin: 20mm; 
          size: A4;
        }
        body{
          font-family: 'Times New Roman', serif;
          color: #1a1a1a;
          margin: 0;
          padding: 0;
          line-height: 1.4;
          background: white;
        }
        .header {
          text-align: center;
          margin-bottom: 30px;
          border-bottom: 3px solid #2c3e50;
          padding-bottom: 20px;
        }
        .company-logo {
          width: 120px;
          height: 120px;
          margin: 0 auto 15px;
          border: 2px solid #2c3e50;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 48px;
          font-weight: bold;
          color: #2c3e50;
          background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .company-name {
          font-size: 28px;
          font-weight: bold;
          color: #2c3e50;
          margin: 10px 0 5px 0;
          text-transform: uppercase;
          letter-spacing: 2px;
        }
        .company-tagline {
          font-size: 14px;
          color: #6c757d;
          font-style: italic;
          margin-bottom: 10px;
        }
        .company-details {
          font-size: 12px;
          color: #495057;
          line-height: 1.3;
        }
        .document-title {
          text-align: center;
          margin: 30px 0;
          font-size: 24px;
          font-weight: bold;
          color: #2c3e50;
          text-transform: uppercase;
          letter-spacing: 1px;
        }
        .estimate-info {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 20px;
          margin-bottom: 30px;
          padding: 15px;
          background: #f8f9fa;
          border-left: 4px solid #2c3e50;
        }
        .info-section h3 {
          margin: 0 0 10px 0;
          font-size: 16px;
          color: #2c3e50;
          border-bottom: 1px solid #dee2e6;
          padding-bottom: 5px;
        }
        .info-section p {
          margin: 5px 0;
          font-size: 14px;
        }
        .cost-breakdown {
          margin: 30px 0;
        }
        .cost-breakdown h2 {
          font-size: 20px;
          color: #2c3e50;
          border-bottom: 2px solid #2c3e50;
          padding-bottom: 10px;
          margin-bottom: 20px;
        }
        .cost-table {
          width: 100%;
          border-collapse: collapse;
          margin-bottom: 20px;
          box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cost-table th {
          background: #2c3e50;
          color: white;
          padding: 12px;
          text-align: left;
          font-weight: bold;
          font-size: 14px;
        }
        .cost-table td {
          padding: 10px 12px;
          border-bottom: 1px solid #dee2e6;
          font-size: 14px;
        }
        .cost-table tr:nth-child(even) {
          background: #f8f9fa;
        }
        .cost-table tr:hover {
          background: #e9ecef;
        }
        .total-section {
          margin: 30px 0;
          padding: 20px;
          background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
          color: white;
          border-radius: 8px;
        }
        .total-section h2 {
          margin: 0 0 15px 0;
          font-size: 18px;
          text-align: center;
        }
        .total-row {
          display: flex;
          justify-content: space-between;
          margin: 8px 0;
          font-size: 16px;
        }
        .grand-total {
          border-top: 2px solid white;
          padding-top: 10px;
          margin-top: 15px;
          font-size: 18px;
          font-weight: bold;
        }
        .terms-section {
          margin: 30px 0;
          padding: 20px;
          background: #f8f9fa;
          border-left: 4px solid #28a745;
        }
        .terms-section h3 {
          margin: 0 0 15px 0;
          color: #28a745;
          font-size: 16px;
        }
        .terms-section p {
          margin: 8px 0;
          font-size: 14px;
          line-height: 1.5;
        }
        .signature-section {
          margin-top: 50px;
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 40px;
        }
        .signature-box {
          text-align: center;
          padding: 20px;
          border: 2px solid #2c3e50;
          border-radius: 8px;
          background: #f8f9fa;
        }
        .signature-line {
          border-bottom: 2px solid #2c3e50;
          margin: 40px 0 10px 0;
          height: 2px;
        }
        .signature-label {
          font-size: 14px;
          font-weight: bold;
          color: #2c3e50;
          margin-top: 10px;
        }
        .contractor-seal {
          width: 100px;
          height: 100px;
          border: 3px solid #dc3545;
          border-radius: 50%;
          margin: 0 auto 15px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 12px;
          font-weight: bold;
          color: #dc3545;
          background: white;
          text-align: center;
          line-height: 1.2;
        }
        .footer {
          margin-top: 40px;
          text-align: center;
          font-size: 12px;
          color: #6c757d;
          border-top: 1px solid #dee2e6;
          padding-top: 15px;
        }
        @media print {
          body { margin: 0; }
          .header { page-break-inside: avoid; }
          .signature-section { page-break-inside: avoid; }
        }
      </style>
      </head><body>
        <!-- Company Header -->
        <div class="header">
          <div class="company-logo">🏗️</div>
          <div class="company-name">${contractorName} Construction</div>
          <div class="company-tagline">Professional Construction Services</div>
          <div class="company-details">
            📧 Email: ${user?.email || 'contact@company.com'} | 
            📱 Phone: ${user?.phone || '+91-XXXXX-XXXXX'} | 
            🏢 License: ${user?.license_number || 'LIC-XXXXX'}
          </div>
        </div>

        <!-- Document Title -->
        <div class="document-title">Cost Estimate Report</div>

        <!-- Estimate Information -->
        <div class="estimate-info">
          <div class="info-section">
            <h3>Project Details</h3>
            <p><strong>Project Name:</strong> ${get('structured[project_name]') || get('project_name') || 'Construction Project'}</p>
            <p><strong>Location:</strong> ${get('structured[project_address]') || get('location') || 'Project Location'}</p>
            <p><strong>Client:</strong> ${get('structured[client_name]') || get('client_name') || 'Client Name'}</p>
            <p><strong>Project Type:</strong> ${get('project_type') || 'Residential/Commercial'}</p>
        </div>
          <div class="info-section">
            <h3>Estimate Information</h3>
            <p><strong>Estimate Date:</strong> ${currentDate}</p>
            <p><strong>Estimate Valid Until:</strong> ${new Date(Date.now() + 30*24*60*60*1000).toLocaleDateString('en-IN')}</p>
            <p><strong>Project Duration:</strong> ${get('timeline') || '90 days'}</p>
            <p><strong>Estimate #:</strong> EST-${Date.now().toString().slice(-6)}</p>
        </div>
        </div>

        <!-- Cost Breakdown -->
        <div class="cost-breakdown">
          <h2>Detailed Cost Breakdown</h2>
          <table class="cost-table">
            <thead>
              <tr>
                <th>Item Description</th>
                <th>Quantity</th>
                <th>Unit Rate (₹)</th>
                <th>Amount (₹)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>MATERIALS</strong></td>
                <td></td>
                <td></td>
                <td></td>
              </tr>
              <tr>
                <td>Cement (OPC 43 Grade)</td>
                <td>${get('structured[materials][cement][qty]') || '50'} bags</td>
                <td>${get('structured[materials][cement][rate]') || '400'}</td>
                <td>₹${(parseFloat(get('structured[materials][cement][qty]') || '50') * parseFloat(get('structured[materials][cement][rate]') || '400')).toLocaleString('en-IN')}</td>
              </tr>
              <tr>
                <td>Sand (River Sand)</td>
                <td>${get('structured[materials][sand][qty]') || '5'} m³</td>
                <td>${get('structured[materials][sand][rate]') || '2000'}</td>
                <td>₹${(parseFloat(get('structured[materials][sand][qty]') || '5') * parseFloat(get('structured[materials][sand][rate]') || '2000')).toLocaleString('en-IN')}</td>
              </tr>
              <tr>
                <td>Bricks (Red Clay)</td>
                <td>${get('structured[materials][bricks][qty]') || '2000'} nos</td>
                <td>${get('structured[materials][bricks][rate]') || '8'}</td>
                <td>₹${(parseFloat(get('structured[materials][bricks][qty]') || '2000') * parseFloat(get('structured[materials][bricks][rate]') || '8')).toLocaleString('en-IN')}</td>
              </tr>
              <tr>
                <td><strong>LABOR</strong></td>
                <td></td>
                <td></td>
                <td></td>
              </tr>
              <tr>
                <td>Masonry Work</td>
                <td>${get('structured[labor][masonry][qty]') || '1'} unit</td>
                <td>${get('structured[labor][masonry][rate]') || '15000'}</td>
                <td>₹${(parseFloat(get('structured[labor][masonry][qty]') || '1') * parseFloat(get('structured[labor][masonry][rate]') || '15000')).toLocaleString('en-IN')}</td>
              </tr>
              <tr>
                <td>Plumbing Work</td>
                <td>${get('structured[labor][plumbing][qty]') || '1'} unit</td>
                <td>${get('structured[labor][plumbing][rate]') || '12000'}</td>
                <td>₹${(parseFloat(get('structured[labor][plumbing][qty]') || '1') * parseFloat(get('structured[labor][plumbing][rate]') || '12000')).toLocaleString('en-IN')}</td>
              </tr>
              <tr>
                <td>Electrical Work</td>
                <td>${get('structured[labor][electrical][qty]') || '1'} unit</td>
                <td>${get('structured[labor][electrical][rate]') || '10000'}</td>
                <td>₹${(parseFloat(get('structured[labor][electrical][qty]') || '1') * parseFloat(get('structured[labor][electrical][rate]') || '10000')).toLocaleString('en-IN')}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Total Section -->
        <div class="total-section">
          <h2>Cost Summary</h2>
          <div class="total-row">
            <span>Materials Cost:</span>
            <span>₹${get('structured[totals][materials_total]') || '50000'}</span>
        </div>
          <div class="total-row">
            <span>Labor Cost:</span>
            <span>₹${get('structured[totals][labor_total]') || '37000'}</span>
          </div>
          <div class="total-row">
            <span>Transportation:</span>
            <span>₹${get('structured[totals][transport_total]') || '5000'}</span>
          </div>
          <div class="total-row">
            <span>Contingency (5%):</span>
            <span>₹${get('structured[totals][contingency_total]') || '4600'}</span>
          </div>
          <div class="total-row grand-total">
            <span>GRAND TOTAL:</span>
            <span>₹${get('structured[totals][grand_total]') || '96600'}</span>
          </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="terms-section">
          <h3>Terms & Conditions</h3>
          <p><strong>Payment Terms:</strong> 30% advance, 40% on completion of foundation, 30% on completion</p>
          <p><strong>Validity:</strong> This estimate is valid for 30 days from the date of issue</p>
          <p><strong>Materials:</strong> All materials will be of standard quality as per specifications</p>
          <p><strong>Timeline:</strong> Project completion within ${get('timeline') || '90'} days from commencement</p>
          <p><strong>Warranty:</strong> 1 year warranty on workmanship, 5 years on structural elements</p>
          <p><strong>Notes:</strong> ${get('notes') || 'All work to be done as per approved drawings and specifications'}</p>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
          <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Client Signature</div>
            <p style="margin-top: 10px; font-size: 12px; color: #6c757d;">Date: _______________</p>
          </div>
          <div class="signature-box">
            <div class="contractor-seal">
              <div>OFFICIAL<br/>SEAL</div>
            </div>
            <div class="signature-line"></div>
            <div class="signature-label">${contractorName}</div>
            <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Authorized Contractor</p>
            <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Date: ${currentDate}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="footer">
          <p>This is a computer-generated estimate. For any clarifications, please contact us.</p>
          <p>© ${new Date().getFullYear()} ${contractorName} Construction. All rights reserved.</p>
        </div>
      </body></html>`;
      
      tempDiv.innerHTML = html;
      document.body.appendChild(tempDiv);
      
      // Convert to canvas and then to PDF
      console.log('Converting HTML to canvas...');
      const canvas = await html2canvas(tempDiv, {
        scale: 2,
        useCORS: true,
        allowTaint: true,
        backgroundColor: '#ffffff'
      });
      
      console.log('Canvas created:', canvas.width, 'x', canvas.height);
      
      // Remove the temporary div
      document.body.removeChild(tempDiv);
      
      // Create PDF
      console.log('Creating PDF...');
      const pdf = new jsPDF('p', 'mm', 'a4');
      const imgData = canvas.toDataURL('image/png');
      const imgWidth = 210; // A4 width in mm
      const pageHeight = 295; // A4 height in mm
      const imgHeight = (canvas.height * imgWidth) / canvas.width;
      let heightLeft = imgHeight;
      
      console.log('Image dimensions:', imgWidth, 'x', imgHeight);
      
      let position = 0;
      
      pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
      heightLeft -= pageHeight;
      
      while (heightLeft >= 0) {
        position = heightLeft - imgHeight;
        pdf.addPage();
        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;
      }
      
      // Download the PDF
      const fileName = `Estimate_${contractorName.replace(/\s+/g, '_')}_${Date.now().toString().slice(-6)}.pdf`;
      console.log('Saving PDF as:', fileName);
      
      // Try alternative download method
      try {
        const pdfBlob = pdf.output('blob');
        const url = URL.createObjectURL(pdfBlob);
        const a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        console.log('PDF downloaded via blob method');
      } catch (blobError) {
        console.log('Blob method failed, trying direct save:', blobError);
        pdf.save(fileName);
      }
      console.log('PDF save method called');
    } catch (e) {
      console.error('Error generating PDF report:', e);
      toast.error('Error generating PDF report');
    }
  };

  // Ensure legacy calls to window.__bhCalcSectionTotals work
  useEffect(() => {
    window.__bhCalcSectionTotals = (form) => {
      try { recalcTotalsFromForm(form); } catch {}
    };
    return () => {
      try { if (window.__bhCalcSectionTotals) delete window.__bhCalcSectionTotals; } catch {}
    };
  }, []);

  // Load drafts for inbox items
  useEffect(() => {
    const loadDraftsForInbox = async () => {
      for (const item of inbox) {
        if (item.id && item.acknowledged_at) {
          await loadDraft(item.id);
        }
      }
    };
    
    if (inbox.length > 0) {
      loadDraftsForInbox();
    }
  }, [inbox]);

  // Cleanup auto-save timeout on unmount
  useEffect(() => {
    return () => {
      if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
      }
    };
  }, [autoSaveTimeout]);

  const recalcTotalsFromForm = (formEl) => {
    if (!formEl) return;
    // Compute per-line amounts for each section (qty * rate)
    try {
      ['materials','labor','utilities','misc'].forEach((section) => {
        const qtyInputs = formEl.querySelectorAll(`input[name^="structured[${section}]"][name$="[qty]"]`);
        qtyInputs.forEach((qtyEl) => {
          const base = qtyEl.name.replace(/\[qty\]$/, '');
          const rateEl = formEl.querySelector(`input[name="${base}[rate]"]`);
          const amountEl = formEl.querySelector(`input[name="${base}[amount]"]`);
          const qty = parseFloat((qtyEl.value || '').toString().replace(/[,\s]/g, '')) || 0;
          const rate = parseFloat(((rateEl && rateEl.value) || '').toString().replace(/[,\s]/g, '')) || 0;
          const amount = qty * rate;
          if (amountEl) amountEl.value = amount ? String(amount) : '';
        });
      });
    } catch {}
    const sumSection = (prefix) => {
      const amountInputs = formEl.querySelectorAll(`input[name^="${prefix}"][name$="[amount]"]`);
      let inputs;
      if (amountInputs && amountInputs.length > 0) {
        inputs = amountInputs;
      } else {
        inputs = formEl.querySelectorAll(`input[name^="${prefix}"]`);
      }
      let total = 0;
      inputs.forEach((inp) => {
        const v = (inp && inp.value) || '';
        // Extract any numbers present (supports "123", "123.45", embedded in text)
        const matches = v.match(/[-+]?(?:\d+\.?\d*|\d*\.?\d+)/g);
        if (matches) {
          matches.forEach((tok) => {
            const n = parseFloat(tok);
            if (!Number.isNaN(n)) total += n;
          });
        }
      });
      return total;
    };
    const m = sumSection('structured[materials]');
    const l = sumSection('structured[labor]');
    const u = sumSection('structured[utilities]');
    const s = sumSection('structured[misc]');
    setMaterialsTotal(m);
    setLaborTotal(l);
    setUtilitiesTotal(u);
    setMiscTotal(s);
    setGrandTotal(m + l + u + s);
  };

  // Sidebar counts
  const requestsCount = Array.isArray(layoutRequests) ? layoutRequests.length : 0;
  const proposalsCount = Array.isArray(myProposals) ? myProposals.length : 0;
  const inboxCount = Array.isArray(inbox) ? inbox.length : 0;

  // Periodic refresh for sidebar counts
  useEffect(() => {
    let mounted = true;
    const refreshCounts = async () => {
      try {
        const r1 = await fetch('/buildhub/backend/api/contractor/get_layout_requests.php');
        const j1 = await r1.json().catch(() => ({}));
        if (mounted && j1?.success) setLayoutRequests(Array.isArray(j1.requests) ? j1.requests : []);
      } catch {}
      try {
        const r2 = await fetch('/buildhub/backend/api/contractor/get_my_proposals.php');
        const j2 = await r2.json().catch(() => ({}));
        if (mounted && j2?.success) setMyProposals(Array.isArray(j2.proposals) ? j2.proposals : []);
      } catch {}
      try {
        const me = JSON.parse(sessionStorage.getItem('user') || '{}');
        if (me?.id) {
          const r4 = await fetch(`/buildhub/backend/api/contractor/get_construction_estimates.php?contractor_id=${me.id}`, { credentials: 'include' });
          const j4 = await r4.json().catch(() => ({}));
          if (mounted && j4?.success) setConstructionEstimates(Array.isArray(j4.estimates) ? j4.estimates : []);
          
          // Also fetch construction projects
          const r5 = await fetch(`/buildhub/backend/api/contractor/get_projects.php?contractor_id=${me.id}`, { credentials: 'include' });
          const j5 = await r5.json().catch(() => ({}));
          if (mounted && j5?.success) {
            setConstructionDetails(Array.isArray(j5.data.projects) ? j5.data.projects : []);
          }
          
          // Fetch recent paid payments
          const r6 = await fetch(`/buildhub/backend/api/contractor/get_recent_paid_payments.php?limit=2`, { credentials: 'include' });
          const j6 = await r6.json().catch(() => ({}));
          if (mounted && j6?.success) setRecentPaidPayments(Array.isArray(j6.payments) ? j6.payments : []);
        }
      } catch {}
      try {
        const me = JSON.parse(sessionStorage.getItem('user') || '{}');
        if (me?.id) {
          const r3 = await fetch(`/buildhub/backend/api/contractor/get_inbox.php?contractor_id=${me.id}`, { credentials: 'include' });
          const j3 = await r3.json().catch(() => ({}));
          if (mounted && j3?.success) setInbox(Array.isArray(j3.items) ? j3.items : []);
        }
      } catch {}
      try {
        const me = JSON.parse(sessionStorage.getItem('user') || '{}');
        if (me?.id) {
          const r3 = await fetch(`/buildhub/backend/api/contractor/get_inbox.php?contractor_id=${me.id}`, { credentials: 'include' });
          const j3 = await r3.json().catch(() => ({}));
          if (mounted && j3?.success) setInbox(Array.isArray(j3.items) ? j3.items : []);
        }
      } catch {}
      try {
        const me = JSON.parse(sessionStorage.getItem('user') || '{}');
        if (me?.id) {
          const r3 = await fetch(`/buildhub/backend/api/contractor/get_inbox.php?contractor_id=${me.id}`, { credentials: 'include' });
          const j3 = await r3.json().catch(() => ({}));
          if (mounted && j3?.success) setInbox(Array.isArray(j3.items) ? j3.items : []);
        }
      } catch {}
      try {
        const me = JSON.parse(sessionStorage.getItem('user') || '{}');
        if (mounted && me?.id) {
          const r4 = await fetch(`/buildhub/backend/api/contractor/get_my_estimates.php?contractor_id=${me.id}`, { credentials: 'include' });
          const j4 = await r4.json().catch(() => ({}));
          if (j4?.success) setMyEstimates(Array.isArray(j4.estimates) ? j4.estimates : []);
        }
      } catch {}
    };
    refreshCounts();
    const id = setInterval(refreshCounts, 60000);
    return () => { mounted = false; clearInterval(id); };
  }, []);
  


  useEffect(() => {
    // Get user data from session
    const userData = JSON.parse(sessionStorage.getItem('user') || '{}');
    setUser(userData);

    import('../utils/session').then(({ preventCache, verifyServerSession }) => {
      preventCache();
      (async () => {
        const serverAuth = await verifyServerSession();
        if (!userData.id || userData.role !== 'contractor' || !serverAuth) {
          sessionStorage.removeItem('user');
          localStorage.removeItem('bh_user');
          navigate('/login', { replace: true });
          return;
        }
        fetchLayoutRequests();
        fetchMyProposals();
        fetchMyEstimates();
      })();
    });

    // Add event listener for estimate refresh
    const handleRefreshEstimates = () => {
      fetchMyEstimates();
    };
    
    window.addEventListener('refreshEstimates', handleRefreshEstimates);
    
    return () => {
      window.removeEventListener('refreshEstimates', handleRefreshEstimates);
    };
  }, []);


  // Static sidebar – no auto-collapse

  const fetchLayoutRequests = async () => {
    setLoading(true);
    try {
      const response = await fetch('/buildhub/backend/api/contractor/get_layout_requests.php');
      const result = await response.json();
      if (result.success) {
        setLayoutRequests(result.requests || []);
      }
    } catch (error) {
      console.error('Error fetching layout requests:', error);
    } finally {
      setLoading(false);
    }
  };

  const parseRequirements = (req) => {
    if (!req) return {};
    try { return typeof req === 'string' ? JSON.parse(req) : req; } catch { return {}; }
  };

  const renderForwardedDesign = (request) => {
    const reqObj = parseRequirements(request.requirements);
    const forwarded = reqObj.forwarded_design;
    if (!forwarded) return null;
    const files = Array.isArray(forwarded.files) ? forwarded.files : [];
    const td = forwarded.technical_details || {};
    
    const formatValue = (value) => {
      if (!value) return '-';
      
      // Handle string values that might contain JSON
      if (typeof value === 'string') {
        // Try to parse as JSON first
        try {
          const parsed = JSON.parse(value);
          if (typeof parsed === 'object' && parsed !== null) {
            return Object.entries(parsed)
              .map(([k, v]) => `• ${k.replace(/_/g, ' ')}: ${String(v)}`)
              .join('\n');
          }
        } catch {}
        
        // Handle newline characters and clean up formatting
        return value
          .replace(/\\n/g, '\n')
          .replace(/\n\s*\n/g, '\n') // Remove extra newlines
          .trim();
      }
      
      // Handle object values directly
      if (typeof value === 'object' && value !== null) {
        return Object.entries(value)
          .map(([k, v]) => `• ${k.replace(/_/g, ' ')}: ${String(v)}`)
          .join('\n');
      }
      
      return String(value);
    };
    
    const renderKV = (obj) => {
      if (!obj || typeof obj !== 'object') return null;
      return (
        <div style={{ display:'grid', gridTemplateColumns:'repeat(auto-fit, minmax(280px, 1fr))', gap:12 }}>
          {Object.entries(obj).map(([k, v]) => (
            <div key={k} style={{ padding:'12px', background:'#fff', border:'1px solid #e5e7eb', borderRadius:8, boxShadow:'0 1px 3px rgba(0,0,0,0.1)' }}>
              <div style={{ fontSize:13, fontWeight:600, color:'#374151', marginBottom:8, textTransform:'capitalize' }}>
                {k.replaceAll('_',' ').replace(/\b\w/g, l => l.toUpperCase())}
              </div>
              <div style={{ fontSize:14, lineHeight:1.5, color:'#6b7280', whiteSpace:'pre-wrap' }}>
                {formatValue(v)}
              </div>
            </div>
          ))}
        </div>
      );
    };
    return (
      <div className="forwarded-design" style={{ marginTop: 10 }}>
        <div className="details-grid" style={{ marginBottom: 8 }}>
          <div><strong>Design Title:</strong> {forwarded.title || '-'}</div>
          <div><strong>Uploaded:</strong> {forwarded.created_at ? new Date(forwarded.created_at).toLocaleString() : '-'}</div>
        </div>
        {forwarded.description && (
          <div className="description-section" style={{ marginBottom: 8 }}>
            <strong>Description:</strong>
            <div className="description-content">{forwarded.description}</div>
          </div>
        )}
        {reqObj.contractor_message && (
          <div className="description-section" style={{ marginBottom: 8 }}>
            <strong>Message from homeowner:</strong>
            <div className="description-content">{reqObj.contractor_message}</div>
          </div>
        )}
        <div style={{display:'grid', gridTemplateColumns:'repeat(auto-fill, minmax(200px, 1fr))', gap:'12px'}}>
          {files.map((f, idx) => {
            const href = f.path || `/buildhub/backend/uploads/designs/${f.stored || f.original}`;
            const ext = (f.ext || '').toLowerCase();
            const isImage = ['jpg','jpeg','png','gif','webp','svg','heic'].includes(ext);
            return (
              <div key={idx} className="file-card-interactive" style={{
                position:'relative', 
                cursor:'pointer',
                borderRadius:8,
                overflow:'hidden',
                boxShadow:'0 2px 8px rgba(0,0,0,0.1)',
                transition:'transform 0.2s ease',
                ':hover': { transform:'scale(1.02)' }
              }}>
                {isImage ? (
                  <div style={{position:'relative', width:'100%', height:140}}>
                    <img 
                      src={href} 
                      alt={f.original} 
                      style={{width:'100%', height:'100%', objectFit:'cover'}}
                      onClick={(e) => {
                        e.stopPropagation();
                        const overlay = e.currentTarget.nextSibling;
                        overlay.style.display = overlay.style.display === 'flex' ? 'none' : 'flex';
                      }}
                    />
                    <div className="image-overlay" style={{
                      position:'absolute',
                      top:0, left:0, right:0, bottom:0,
                      background:'rgba(0,0,0,0.7)',
                      display:'none',
                      alignItems:'center',
                      justifyContent:'center',
                      gap:10,
                      zIndex:10
                    }}>
                      <a 
                        href={href} 
                        target="_blank" 
                        rel="noreferrer" 
                        className="btn btn-light" 
                        style={{padding:'8px 16px', fontSize:'14px'}}
                        onClick={(e) => e.stopPropagation()}
                      >
                        👁️ View
                      </a>
                      <a 
                        href={href} 
                        download 
                        className="btn btn-light" 
                        style={{padding:'8px 16px', fontSize:'14px'}}
                        onClick={(e) => e.stopPropagation()}
                      >
                        💾 Download
                      </a>
                    </div>
                  </div>
                ) : (
                  <div style={{height:140, display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', background:'#f8fafc', border:'2px dashed #cbd5e1'}}>
                    <span style={{fontSize:'2.5rem', marginBottom:8}}>📄</span>
                    <div style={{display:'flex', gap:8}}>
                      <a href={href} target="_blank" rel="noreferrer" className="btn btn-secondary btn-sm">View</a>
                      <a href={href} download className="btn btn-primary btn-sm">Download</a>
                    </div>
                  </div>
                )}
                <div style={{padding:'8px', background:'#fff', borderTop:'1px solid #e5e7eb'}}>
                  <div style={{fontSize:'13px', fontWeight:500, color:'#374151', whiteSpace:'nowrap', overflow:'hidden', textOverflow:'ellipsis'}} title={f.original || f.stored}>
                    {f.original || f.stored}
                  </div>
                  <div style={{fontSize:'11px', color:'#9ca3af', marginTop:2}}>
                    {ext.toUpperCase()} • Click to {isImage ? 'view' : 'download'}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
        {(td && Object.keys(td).length > 0) && (
          <div style={{ marginTop: 16 }}>
            <h4 style={{ margin: '10px 0' }}>Technical Details</h4>
            {td.floor_plans && (
              <div style={{ marginBottom: 12 }}>
                <div style={{ fontWeight:600, marginBottom:6 }}>Floor Plans</div>
                {renderKV(td.floor_plans)}
              </div>
            )}
            {td.site_orientation && (
              <div style={{ marginBottom: 12 }}>
                <div style={{ fontWeight:600, marginBottom:6 }}>Site Orientation</div>
                {renderKV(td.site_orientation)}
              </div>
            )}
            {td.structural && (
              <div style={{ marginBottom: 12 }}>
                <div style={{ fontWeight:600, marginBottom:6 }}>Structural</div>
                {renderKV(td.structural)}
              </div>
            )}
            {td.elevations && (
              <div style={{ marginBottom: 12 }}>
                <div style={{ fontWeight:600, marginBottom:6 }}>Elevations</div>
                {renderKV(td.elevations)}
              </div>
            )}
            {td.construction && (
              <div style={{ marginBottom: 12 }}>
                <div style={{ fontWeight:600, marginBottom:6 }}>Construction</div>
                {renderKV(td.construction)}
              </div>
            )}
          </div>
        )}
      </div>
    );
  };

  const getForwardedSummary = (request) => {
    const reqObj = parseRequirements(request.requirements);
    const forwarded = reqObj.forwarded_design || {};
    const title = forwarded.title || reqObj.layout_description || 'Project Request';
    const desc = (forwarded.description || '').trim();
    const short = desc.length > 120 ? desc.slice(0, 117) + '…' : desc;
    return { title, short, hasDesc: !!desc };
  };

  const getForwardedThumb = (request) => {
    const reqObj = parseRequirements(request.requirements);
    const forwarded = reqObj.forwarded_design;
    const files = Array.isArray(forwarded?.files) ? forwarded.files : [];
    const first = files[0];
    if (!first) return null;
    const href = first.path || `/buildhub/backend/uploads/designs/${first.stored || first.original}`;
    const ext = (first.ext || '').toLowerCase();
    const isImage = ['jpg','jpeg','png','gif','webp','svg','heic'].includes(ext);
    return { href, isImage, name: first.original || first.stored };
  };

  const getTimeAgo = (date) => {
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} week${Math.floor(diffDays / 7) > 1 ? 's' : ''} ago`;
    return date.toLocaleDateString();
  };

  const fetchMyProposals = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/contractor/get_my_proposals.php');
      const result = await response.json();
      if (result.success) {
        setMyProposals(result.proposals || []);
      }
    } catch (error) {
      console.error('Error fetching proposals:', error);
    }
  };

  const fetchMyEstimates = async () => {
    try {
      const me = JSON.parse(sessionStorage.getItem('user') || '{}');
      if (me?.id) {
        const response = await fetch(`/buildhub/backend/api/contractor/get_my_estimates.php?contractor_id=${me.id}`, { credentials: 'include' });
        const result = await response.json();
        if (result.success) {
          console.log('Fetched estimates:', result.estimates);
          setMyEstimates(Array.isArray(result.estimates) ? result.estimates : []);
        }
      }
    } catch (error) {
      console.error('Error fetching estimates:', error);
    }
  };

  const handleLogout = async () => {
    try { await fetch('/buildhub/backend/api/logout.php', { method: 'POST', credentials: 'include' }); } catch {}
    localStorage.removeItem('bh_user');
    sessionStorage.removeItem('user');
    navigate('/login', { replace: true });
  };

  const renderOverview = () => (
    <div>
      {/* Main Header */}
      <div className="main-header">
        <h1>Welcome back, {user?.first_name}!</h1>
        <p>Manage your cost estimates and project bids</p>
      </div>

      {/* Stats Grid */}
      <div className="stats-grid">
        <div className="stat-card w-blue">
          <div className="stat-content">
            <div className="stat-icon pending">⏰</div>
            <div className="stat-info">
              <h3>{layoutRequests.length}</h3>
              <p>Pending Requests</p>
            </div>
          </div>
        </div>
        <div className="stat-card w-purple">
          <div className="stat-content">
            <div className="stat-icon estimates">📋</div>
            <div className="stat-info">
              <h3>{myEstimates.length}</h3>
              <p>Estimates Sent</p>
            </div>
          </div>
        </div>
        <div className="stat-card w-green">
          <div className="stat-content">
            <div className="stat-icon projects">✅</div>
            <div className="stat-info">
              <h3>{myProposals.filter(p => p.status === 'accepted').length}</h3>
              <p>Projects Won</p>
            </div>
          </div>
        </div>
        <div className="stat-card w-orange">
          <div className="stat-content">
            <div className="stat-icon total">💰</div>
            <div className="stat-info">
              <h3>₹{myProposals.reduce((sum, p) => sum + (parseFloat(p.total_cost) || 0), 0).toLocaleString()}</h3>
              <p>Total Bids</p>
            </div>
          </div>
        </div>
      </div>

      {/* Recent Cost Requests */}
      <div className="section-card">
        <div className="section-header">
          <h2>💰 Recent Cost Requests</h2>
          <p>Latest layout approvals requiring cost estimates</p>
        </div>
        <div className="section-content">
          {loading ? (
            <div className="loading">Loading requests...</div>
          ) : layoutRequests.length === 0 ? (
            <div>
              <div className="empty-state">
                <div className="empty-icon">📭</div>
                <h3>No Cost Requests Available</h3>
                <p>Check back later for new project opportunities!</p>
              </div>
              
              {/* Show Recent Paid Payments */}
              {recentPaidPayments.length > 0 && (
                <div style={{ marginTop: '32px' }}>
                  <div className="section-header" style={{ marginBottom: '20px' }}>
                    <h3 style={{ fontSize: '20px', fontWeight: '600', color: '#1e293b', margin: '0 0 6px 0' }}>
                      💸 Recent Paid Payments
                    </h3>
                    <p style={{ fontSize: '14px', color: '#64748b', margin: 0 }}>
                      Your latest completed payments
                    </p>
                  </div>
                  
                  <div className="recent-payments-grid">
                    {recentPaidPayments.map(payment => {
                      const paymentDate = new Date(payment.payment_date || payment.updated_at);
                      const timeAgo = getTimeAgo(paymentDate);
                      
                      return (
                        <div key={payment.id} className="payment-card">
                          <div className="payment-card-header">
                            <div className="payment-icon">💰</div>
                            <div className="payment-badge">
                              <span className="badge-paid">PAID</span>
                            </div>
                          </div>
                          
                          <div className="payment-card-body">
                            <h4 className="payment-title">{payment.stage_name}</h4>
                            <p className="payment-project">{payment.project_name}</p>
                            
                            <div className="payment-info">
                              <div className="info-row">
                                <span className="info-icon">👤</span>
                                <span className="info-text">{payment.homeowner_name}</span>
                              </div>
                              <div className="info-row">
                                <span className="info-icon">💵</span>
                                <span className="info-text">₹{payment.paid_amount.toLocaleString('en-IN')}</span>
                              </div>
                              <div className="info-row">
                                <span className="info-icon">📍</span>
                                <span className="info-text">{payment.project_location}</span>
                              </div>
                              <div className="info-row">
                                <span className="info-icon">🕒</span>
                                <span className="info-text">{timeAgo}</span>
                              </div>
                            </div>
                            
                            {payment.work_description && (
                              <p className="payment-desc">{payment.work_description}</p>
                            )}
                          </div>
                          
                          <div className="payment-card-footer">
                            <div className="verification-status">
                              {payment.verification_status === 'verified' ? (
                                <span className="status-verified">✅ Verified</span>
                              ) : payment.verification_status === 'rejected' ? (
                                <span className="status-rejected">❌ Rejected</span>
                              ) : (
                                <span className="status-pending">⏳ Pending Verification</span>
                              )}
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              )}
            </div>
          ) : (
            <div className="cost-requests-grid">
              {layoutRequests.slice(0, 6).map(request => {
                const summary = getForwardedSummary(request);
                const thumb = getForwardedThumb(request);
                const createdDate = new Date(request.created_at);
                const timeAgo = getTimeAgo(createdDate);
                
                return (
                <div key={request.id} className="cost-request-card">
                  <div className="cost-request-header">
                    <div className="cost-request-image">
                      {thumb?.isImage ? (
                        <img src={thumb.href} alt={thumb.name} />
                      ) : request.selected_layout_image ? (
                        <img src={request.selected_layout_image} alt="Layout" />
                      ) : (
                        <div className="placeholder-image">🏠</div>
                      )}
                    </div>
                    <div className="cost-request-badge">
                      <span className="badge-new">NEW</span>
                    </div>
                  </div>
                  
                  <div className="cost-request-body">
                    <h4 className="cost-request-title">{summary.title || request.selected_layout_title || 'Layout Request'}</h4>
                    
                    <div className="cost-request-info">
                      <div className="info-row">
                        <span className="info-icon">👤</span>
                        <span className="info-text">{request.homeowner_name}</span>
                      </div>
                      <div className="info-row">
                        <span className="info-icon">📐</span>
                        <span className="info-text">{request.plot_size}</span>
                      </div>
                      <div className="info-row">
                        <span className="info-icon">💵</span>
                        <span className="info-text">₹{request.budget_range}</span>
                      </div>
                      <div className="info-row">
                        <span className="info-icon">🕒</span>
                        <span className="info-text">{timeAgo}</span>
                      </div>
                    </div>
                    
                    {summary.hasDesc && (
                      <p className="cost-request-desc">{summary.short}</p>
                    )}
                  </div>
                  
                  <div className="cost-request-footer">
                    <button 
                      className="btn-view-details" 
                      onClick={() => setShowRequestDetails(prev => ({...prev, [request.id]: !prev[request.id]}))}>
                      {showRequestDetails[request.id] ? '👁️ Hide Details' : '👁️ View Details'}
                    </button>
                    <button 
                      className="btn-submit-estimate" 
                      onClick={() => navigate(`/contractor/estimate?layout_request_id=${request.id}`)}>
                      📝 Submit Estimate
                    </button>
                  </div>
                  
                  {showRequestDetails[request.id] && (
                    <div className="cost-request-details">
                      {renderForwardedDesign(request)}
                    </div>
                  )}
                </div>
              );})}
            </div>
          )}
        </div>
      </div>

      {/* Inbox */}
      <div className="section-card">
        <div className="section-header" style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
          <div>
            <h2>Inbox</h2>
            <p>Layouts and designs sent directly to you</p>
          </div>
          <button className="btn btn-secondary" onClick={async ()=>{
            try {
              const me = JSON.parse(sessionStorage.getItem('user') || '{}');
              const r = await fetch(`/buildhub/backend/api/contractor/get_inbox.php?contractor_id=${me.id}`, { credentials: 'include' });
              const j = await r.json().catch(() => ({}));
              if (j?.success) setInbox(Array.isArray(j.items) ? j.items : []);
            } catch {}
          }}>Refresh</button>
        </div>
        <div className="section-content">
          {Array.isArray(inbox) && inbox.length ? inbox.map(renderInboxItem) : (
            <div className="empty-state">
              <div className="empty-icon">📥</div>
              <h3>No items yet</h3>
              <p>When a homeowner sends you a layout, it appears here</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const renderMyEstimates = () => (
    <div>
      <div className="main-header">
        <h1>My Estimates</h1>
        <p>Your submitted estimates with totals and timestamps</p>
      </div>
      <div className="section-card">
        <div className="section-header">
          <h2>Recent Estimates</h2>
          <p>Download or reference previous submissions</p>
        </div>
        <div className="section-content">
          {myEstimates.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">📄</div>
              <h3>No Estimates Yet</h3>
              <p>Submit an estimate from your Inbox to see it here.</p>
            </div>
          ) : (
            <div className="item-list">
              {myEstimates.map(est => (
                <EstimateListItem key={est.id} est={est} user={user} showConfirmation={showConfirmation} toast={toast} />
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const renderInbox = () => (
    <div>
      <div className="main-header">
        <h1>📥 Inbox</h1>
        <p>Project requests and notifications from homeowners</p>
      </div>

      <div className="section-card">
        <div className="section-header" style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
          <div>
            <h2>Recent Messages</h2>
            <p>New project requests, estimate approvals, and construction notifications</p>
          </div>
          <button className="btn btn-secondary" onClick={async ()=>{
            try {
              const me = JSON.parse(sessionStorage.getItem('user') || '{}');
              const r = await fetch(`/buildhub/backend/api/contractor/get_inbox.php?contractor_id=${me.id}`, { credentials: 'include' });
              const j = await r.json().catch(() => ({}));
              if (j?.success) setInbox(Array.isArray(j.items) ? j.items : []);
            } catch {}
          }}>🔄 Refresh</button>
        </div>
        <div className="section-content">
          {Array.isArray(inbox) && inbox.length ? inbox.map(renderInboxItem) : (
            <div className="empty-state">
              <div className="empty-icon">📭</div>
              <h3>No messages yet</h3>
              <p>When homeowners send you project requests or approve estimates, they will appear here</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const phpOrigin = (typeof window !== 'undefined' && window.location && window.location.port === '3000') ? 'http://localhost' : '';
  const assetUrl = (path) => {
    if (!path) return path;
    if (/^https?:\/\//i.test(path)) return path;
    return `${phpOrigin}/${String(path).replace(/^\/?/, '')}`;
  };

  // Helper function to format dates consistently
  const formatDate = (dateString) => {
    if (!dateString) return 'Unknown date';
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) return 'Today';
    if (diffDays === 2) return 'Yesterday';
    if (diffDays <= 7) return `${diffDays - 1} days ago`;
    return date.toLocaleDateString();
  };

  const renderTechnicalNeat = (obj) => {
    if (!obj || typeof obj !== 'object') return null;
    const sections = Object.keys(obj);
    if (!sections.length) return null;
    return (
      <div style={{border:'1px solid #eee', borderRadius:8, padding:10, background:'#fafafa', display:'grid', gap:10}}>
        {sections.map((sectionKey) => {
          const section = obj[sectionKey];
          if (!section || typeof section !== 'object') return null;
          const entries = Object.entries(section);
          if (!entries.length) return null;
          return (
            <div key={sectionKey}>
              <div style={{fontWeight:600, marginBottom:6, textTransform:'capitalize'}}>{sectionKey.replace(/[_-]/g,' ')}</div>
              <div style={{display:'grid', gridTemplateColumns:'1fr 2fr', gap:'6px 12px'}}>
                {entries.map(([k, v]) => (
                  <React.Fragment key={k}>
                    <div style={{color:'#555'}}>{String(k).replace(/[_-]/g,' ')}</div>
                    <div style={{color:'#111'}}>{typeof v === 'object' ? JSON.stringify(v) : String(v)}</div>
                  </React.Fragment>
                ))}
              </div>
            </div>
          );
        })}
      </div>
    );
  };

  const renderInboxItem = (item) => {
    const payload = item.payload || {};
    const fd = payload.forwarded_design || null;
    const firstFile = fd && Array.isArray(fd.files) && fd.files[0] ? fd.files[0] : null;
    
    // Prioritize layout image URL from multiple sources
    let rawImg = null;
    if (item.layout_image_url) {
      rawImg = item.layout_image_url;
    } else if (payload.layout_image_url) {
      rawImg = payload.layout_image_url;
    } else if (firstFile && (firstFile.url || firstFile.path)) {
      rawImg = firstFile.url || firstFile.path;
    } else if (typeof firstFile === 'string') {
      rawImg = firstFile;
    } else if (payload.layout_images && Array.isArray(payload.layout_images) && payload.layout_images[0]) {
      // Try to get from layout_images array
      const firstLayoutImg = payload.layout_images[0];
      rawImg = firstLayoutImg.url || firstLayoutImg.path || `/buildhub/backend/uploads/house_plans/${firstLayoutImg.stored || firstLayoutImg.original}`;
    }
    
    const img = assetUrl(rawImg);
    const technical = item.technical_details || fd?.technical_details || payload.technical_details || null;
    const floor = payload.floor_details || null;
    
    // Handle construction start notifications
    if (item.type === 'construction_start') {
      return (
        <div className="card" key={item.id} style={{marginBottom: 12, borderLeft: '4px solid #10b981'}}>
          <div className="card-header" style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
            <div>
              <div className="card-title" style={{color: '#10b981', fontWeight: 'bold'}}>
                🏗️ {item.title || 'Construction Started'}
              </div>
              <div className="muted" style={{fontSize:'0.85rem'}}>
                From: {item.homeowner_name || 'Homeowner'}{item.homeowner_email ? ` • ${item.homeowner_email}` : ''}
              </div>
              <div style={{marginTop: 8, fontSize: '0.9rem', color: '#047857'}}>
                The homeowner has approved your estimate and given permission to start construction work.
              </div>
            </div>
            <div style={{display:'flex', alignItems:'center', gap:8}}>
              <div className="muted" style={{fontSize:'0.85rem'}}>{formatDate(item.created_at)}</div>
              <span className="status-badge success">Construction Approved</span>
            </div>
          </div>
        </div>
      );
    }
    
    // Handle estimate message notifications
    if (item.type === 'estimate_message') {
      const estimateDetails = payload.estimate_details || {};
      const homeownerDetails = payload.homeowner_details || {};
      const layoutDetails = payload.layout_details || {};
      
      return (
        <div className="card" key={item.id} style={{
          marginBottom: 12, 
          border: '1px solid #3b82f6', 
          borderRadius: '12px', 
          boxShadow: '0 2px 8px rgba(0,0,0,0.05)',
          background: 'linear-gradient(to right, #ffffff 0%, #f8fafc 100%)'
        }}>
          <div className="card-header" style={{
            display:'flex',
            justifyContent:'space-between',
            alignItems:'center',
            padding: '16px',
            borderBottom: '1px solid #3b82f6',
            background: 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)'
          }}>
            <div>
              <div className="card-title" style={{color: '#1e40af', fontWeight: 'bold', fontSize: '16px'}}>
                ✅ Estimate Accepted - Ready to Start
              </div>
              <div className="muted" style={{fontSize:'0.875rem', color: '#6b7280', marginTop: '4px'}}>
                From: <strong style={{color: '#1f2937'}}>{item.homeowner_name || 'Homeowner'}</strong>{item.homeowner_email ? ` • ${item.homeowner_email}` : ''}
              </div>
            </div>
            <div style={{display:'flex', alignItems:'center', gap:8}}>
              <div className="muted" style={{fontSize:'0.85rem'}}>{formatDate(item.created_at)}</div>
              <span className="status-badge success">Estimate Approved</span>
            </div>
          </div>
          <div className="card-body" style={{padding: '20px'}}>
            {/* Homeowner Message - Prominent Display */}
            <div style={{
              marginBottom: '20px', 
              padding: '16px', 
              background: 'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%)',
              borderRadius: '10px', 
              border: '2px solid #10b981',
              boxShadow: '0 2px 4px rgba(0,0,0,0.05)'
            }}>
              <div style={{display: 'flex', alignItems: 'center', marginBottom: '12px'}}>
                <span style={{fontSize: '24px', marginRight: '10px'}}>💬</span>
                <h4 style={{margin: 0, fontSize: '15px', fontWeight: '700', color: '#065f46'}}>
                  Message from Homeowner
                </h4>
              </div>
              <div style={{
                whiteSpace: 'pre-wrap', 
                fontSize: '14px', 
                color: '#047857',
                lineHeight: '1.7',
                paddingLeft: '34px'
              }}>
                {item.message || 'I am satisfied with this estimate and would like to proceed with the project.'}
              </div>
            </div>

            {/* Estimate Details */}
            <details style={{marginBottom: '12px'}}>
              <summary style={{
                cursor: 'pointer',
                padding: '12px',
                background: 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)',
                borderRadius: '8px',
                fontWeight: '600',
                fontSize: '14px',
                color: '#1e40af',
                border: '1px solid #bfdbfe'
              }}>
                📊 Estimate Details
              </summary>
              <div style={{padding: '16px', background: '#f8fafc', borderRadius: '6px', marginTop: '8px'}}>
                <div style={{display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', fontSize: '0.9rem'}}>
                  <div style={{padding: '8px', background: 'white', borderRadius: '4px'}}>
                    <strong style={{color: '#6b7280'}}>Total Cost:</strong> <span style={{color: '#10b981', fontWeight: '600'}}>₹{estimateDetails.total_cost || 'N/A'}</span>
                  </div>
                  <div style={{padding: '8px', background: 'white', borderRadius: '4px'}}>
                    <strong style={{color: '#6b7280'}}>Timeline:</strong> {estimateDetails.timeline || 'N/A'}
                  </div>
                  {estimateDetails.materials && (
                    <div style={{gridColumn: '1 / -1', padding: '8px', background: 'white', borderRadius: '4px'}}>
                      <strong style={{color: '#6b7280'}}>Materials:</strong> {estimateDetails.materials}
                    </div>
                  )}
                  {estimateDetails.cost_breakdown && (
                    <div style={{gridColumn: '1 / -1', padding: '8px', background: 'white', borderRadius: '4px'}}>
                      <strong style={{color: '#6b7280'}}>Cost Breakdown:</strong> {estimateDetails.cost_breakdown}
                    </div>
                  )}
                  {estimateDetails.notes && (
                    <div style={{gridColumn: '1 / -1', padding: '8px', background: 'white', borderRadius: '4px'}}>
                      <strong style={{color: '#6b7280'}}>Notes:</strong> {estimateDetails.notes}
                    </div>
                  )}
                </div>
              </div>
            </details>

            {/* Homeowner Contact Details */}
            <details style={{marginBottom: '12px'}}>
              <summary style={{
                cursor: 'pointer',
                padding: '12px',
                background: 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)',
                borderRadius: '8px',
                fontWeight: '600',
                fontSize: '14px',
                color: '#1e40af',
                border: '1px solid #bfdbfe'
              }}>
                👤 Homeowner Contact Details
              </summary>
              <div style={{padding: '16px', background: '#f8fafc', borderRadius: '6px', marginTop: '8px'}}>
                <div style={{display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', fontSize: '0.9rem'}}>
                  <div style={{padding: '8px', background: 'white', borderRadius: '4px'}}>
                    <strong style={{color: '#6b7280'}}>Name:</strong> {homeownerDetails.first_name} {homeownerDetails.last_name}
                  </div>
                  <div style={{padding: '8px', background: 'white', borderRadius: '4px'}}>
                    <strong style={{color: '#6b7280'}}>Email:</strong> {homeownerDetails.email}
                  </div>
                  <div style={{padding: '8px', background: 'white', borderRadius: '4px'}}>
                    <strong style={{color: '#6b7280'}}>Phone:</strong> {homeownerDetails.phone || 'Not provided'}
                  </div>
                  <div style={{padding: '8px', background: 'white', borderRadius: '4px'}}>
                    <strong style={{color: '#6b7280'}}>City:</strong> {homeownerDetails.city || 'Not provided'}
                  </div>
                  {homeownerDetails.address && (
                    <div style={{gridColumn: '1 / -1', padding: '8px', background: 'white', borderRadius: '4px'}}>
                      <strong style={{color: '#6b7280'}}>Address:</strong> {homeownerDetails.address}
                    </div>
                  )}
                  {homeownerDetails.zip_code && (
                    <div style={{padding: '8px', background: 'white', borderRadius: '4px'}}>
                      <strong style={{color: '#6b7280'}}>Zip Code:</strong> {homeownerDetails.zip_code}
                    </div>
                  )}
                  {homeownerDetails.state && (
                    <div style={{padding: '8px', background: 'white', borderRadius: '4px'}}>
                      <strong style={{color: '#6b7280'}}>State:</strong> {homeownerDetails.state}
                    </div>
                  )}
                </div>
              </div>
            </details>

            {/* Layout Details (if available) */}
            {layoutDetails && layoutDetails.id && (
              <details style={{marginBottom: '12px'}}>
                <summary style={{
                  cursor: 'pointer',
                  padding: '12px',
                  background: 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)',
                  borderRadius: '8px',
                  fontWeight: '600',
                  fontSize: '14px',
                  color: '#1e40af',
                  border: '1px solid #bfdbfe'
                }}>
                  🏠 Layout Details
                </summary>
                <div style={{padding: '16px', background: '#f8fafc', borderRadius: '6px', marginTop: '8px'}}>
                  <div style={{fontSize: '0.9rem'}}>
                    <div style={{padding: '8px', background: 'white', borderRadius: '4px', marginBottom: '8px'}}>
                      <strong style={{color: '#6b7280'}}>Layout Title:</strong> {layoutDetails.title}
                    </div>
                    {layoutDetails.description && (
                      <div style={{padding: '8px', background: 'white', borderRadius: '4px', marginBottom: '8px'}}>
                        <strong style={{color: '#6b7280'}}>Description:</strong> {layoutDetails.description}
                      </div>
                    )}
                    <div style={{padding: '8px', background: 'white', borderRadius: '4px'}}>
                      <strong style={{color: '#6b7280'}}>Created:</strong> {new Date(layoutDetails.created_at).toLocaleDateString()}
                    </div>
                  </div>
                </div>
              </details>
            )}

            {/* Action Buttons */}
            <div style={{display: 'flex', gap: '8px', justifyContent: 'flex-end'}}>
              <button 
                className="btn btn-primary"
                onClick={async () => {
                  try {
                    const me = JSON.parse(sessionStorage.getItem('user') || '{}');
                    await fetch('/buildhub/backend/api/contractor/acknowledge_inbox_item.php', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      credentials: 'include',
                      body: JSON.stringify({ id: item.id, contractor_id: me.id, due_date: null })
                    });
                    // Refresh inbox
                    const r = await fetch(`/buildhub/backend/api/contractor/get_inbox.php?contractor_id=${me.id}`, { credentials: 'include' });
                    const j = await r.json().catch(() => ({}));
                    if (j?.success) setInbox(Array.isArray(j.items) ? j.items : []);
                  } catch {}
                }}
              >
                ✓ Acknowledge
              </button>
              <button 
                className="btn btn-secondary"
                onClick={async () => {
                  showConfirmation(
                    'Remove Item',
                    'Are you sure you want to remove this item from your inbox?',
                    async () => {
                      try {
                        const me = JSON.parse(sessionStorage.getItem('user') || '{}');
                        const response = await fetch('/buildhub/backend/api/contractor/delete_inbox_item.php', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          credentials: 'include',
                          body: JSON.stringify({ id: item.id, contractor_id: me.id })
                        });
                        
                        const result = await response.json().catch(() => ({}));
                        if (result.success) {
                          // Remove item from local state immediately for instant UI update
                          setInbox(prevInbox => prevInbox.filter(inboxItem => inboxItem.id !== item.id));
                          toast.success('Item removed successfully!');
                          
                          // Also refresh from server as backup (after a short delay)
                          setTimeout(async () => {
                            try {
                              const r = await fetch(`/buildhub/backend/api/contractor/get_inbox.php?contractor_id=${me.id}`, { credentials: 'include' });
                              const j = await r.json().catch(() => ({}));
                              if (j?.success) setInbox(Array.isArray(j.items) ? j.items : []);
                            } catch {}
                          }, 500);
                        } else {
                          toast.error(result.message || 'Failed to remove item. Please try again.');
                        }
                      } catch (e) {
                        toast.error('Error removing item. Please try again.');
                        console.error('Remove error:', e);
                      }
                    }
                  );
                }}
                style={{
                  background: '#ef4444', 
                  color: 'white', 
                  border: '1px solid #ef4444'
                }}
              >
                🗑️ Remove
              </button>
            </div>
          </div>
        </div>
      );
    }
    
    return (
      <div className="card" key={item.id} style={{marginBottom: 12, border: '1px solid #e5e7eb', borderRadius: '12px', boxShadow: '0 2px 8px rgba(0,0,0,0.05)'}}>
        <div className="card-header" style={{
          display:'flex',
          justifyContent:'space-between',
          alignItems:'center',
          padding: '16px',
          borderBottom: '1px solid #e5e7eb',
          background: 'linear-gradient(135deg, #ffffff 0%, #f8fafc 100%)'
        }}>
          <div>
            <div className="card-title" style={{fontWeight: '600', fontSize: '16px', color: '#1f2937'}}>📋 New Layout Request</div>
            <div className="muted" style={{fontSize:'0.875rem', color: '#6b7280', marginTop: '4px'}}>
              From: <strong style={{color: '#374151'}}>{item.homeowner_name || 'Homeowner'}</strong>{item.homeowner_email ? ` • ${item.homeowner_email}` : ''}
            </div>
          </div>
          <div style={{display:'flex', alignItems:'center', gap:8}}>
            <div className="muted" style={{fontSize:'0.85rem'}}>{formatDate(item.created_at)}</div>
            {item.acknowledged_at ? (
              <div style={{display:'flex', alignItems:'center', gap:8}}>
                <span className="status-badge accepted" title={`Due: ${item.due_date || '—'}`}>Acknowledged</span>
              </div>
            ) : (
              <div style={{display:'flex', alignItems:'center', gap:6}}>
                {!ackOpenById[item.id] && (
                  <button 
                    className="btn btn-primary acknowledge-button" 
                    onClick={()=> setAckOpenById(prev=>({...prev, [item.id]: true}))}
                  >
                    ⚡ Acknowledge Request
                  </button>
                )}
                {ackOpenById[item.id] && (
                  <div className="acknowledgment-form">
                    <span>Due Date:</span>
                    <input 
                      type="date" 
                      value={ackDateById[item.id] || ''}
                      onChange={(e)=> setAckDateById(prev=>({...prev, [item.id]: e.target.value}))}
                    />
                    <button 
                      className="btn btn-primary" 
                      onClick={async ()=>{
                      const me = JSON.parse(sessionStorage.getItem('user') || '{}');
                      try {
                        const response = await fetch('/buildhub/backend/api/contractor/acknowledge_inbox_item.php', {
                          method: 'POST',
                          headers: { 'Content-Type': 'application/json' },
                          credentials: 'include',
                          body: JSON.stringify({ id: item.id, contractor_id: me.id, due_date: ackDateById[item.id] || null })
                        });
                        const result = await response.json();
                        if (result.success) {
                          // Show alert with acknowledgment time
                          const ackTime = result.acknowledged_at || new Date().toLocaleString();
                          const dueDate = ackDateById[item.id] ? new Date(ackDateById[item.id]).toLocaleDateString() : 'not specified';
                          toast.success(`✅ Acknowledgement sent successfully! Acknowledged at: ${ackTime}, Due date: ${dueDate}. The homeowner has been notified.`);
                        } else {
                          toast.error(`Failed to acknowledge: ${result.message || 'Unknown error'}`);
                        }
                      } catch (e) {
                        toast.error('Network error. Please try again.');
                        console.error(e);
                      }
                      try {
                        const r = await fetch(`/buildhub/backend/api/contractor/get_inbox.php?contractor_id=${me.id}`, { credentials: 'include' });
                        const j = await r.json().catch(() => ({}));
                        if (j?.success) setInbox(Array.isArray(j.items) ? j.items : []);
                      } catch {}
                      setAckOpenById(prev=>({...prev, [item.id]: false}));
                    }}>✅ Confirm</button>
                    <button 
                      className="btn btn-secondary" 
                      onClick={()=> setAckOpenById(prev=>({...prev, [item.id]: false}))}
                    >
                      Cancel
                    </button>
                  </div>
                )}
              </div>
            )}
            <button className="btn btn-secondary" onClick={async ()=>{
              showConfirmation(
                'Remove Item',
                'Are you sure you want to remove this item from your inbox?',
                async () => {
                  try {
                    const me = JSON.parse(sessionStorage.getItem('user') || '{}');
                    const response = await fetch('/buildhub/backend/api/contractor/delete_inbox_item.php', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json' },
                      credentials: 'include',
                      body: JSON.stringify({ id: item.id, contractor_id: me.id })
                    });
                    
                    const result = await response.json().catch(() => ({}));
                    if (result.success) {
                      // Remove item from local state immediately for instant UI update
                      setInbox(prevInbox => prevInbox.filter(inboxItem => inboxItem.id !== item.id));
                      toast.success('Item removed successfully!');
                      
                      // Also refresh from server as backup (after a short delay)
                      setTimeout(async () => {
                        try {
                          const r = await fetch(`/buildhub/backend/api/contractor/get_inbox.php?contractor_id=${me.id}`, { credentials: 'include' });
                          const j = await r.json().catch(() => ({}));
                          if (j?.success) setInbox(Array.isArray(j.items) ? j.items : []);
                        } catch {}
                      }, 500);
                    } else {
                      toast.error(result.message || 'Failed to remove item. Please try again.');
                    }
                  } catch (e) {
                    toast.error('Error removing item. Please try again.');
                    console.error('Remove error:', e);
                  }
                }
              );
            }} style={{
              background: '#ef4444', 
              color: 'white', 
              border: '1px solid #ef4444',
              fontSize: '12px',
              padding: '6px 12px'
            }}>🗑️ Remove</button>
          </div>
        </div>
        <div className="card-body" style={{display:'grid',gridTemplateColumns:'160px 1fr',gap:16, padding: '20px'}}>
          <div>
            {img ? (
              <div style={{position: 'relative'}}>
                <img 
                  src={img} 
                  alt="Layout preview" 
                  style={{
                    width:'160px',
                    height:'120px',
                    objectFit:'cover',
                    borderRadius:8,
                    border:'2px solid #3b82f6',
                    boxShadow: '0 4px 8px rgba(0,0,0,0.1)'
                  }} 
                  onError={(e)=>{ e.currentTarget.style.display='none'; }} 
                />
                <div style={{
                  position: 'absolute',
                  bottom: '4px',
                  left: '4px',
                  right: '4px',
                  background: 'rgba(59, 130, 246, 0.9)',
                  color: 'white',
                  padding: '2px 6px',
                  borderRadius: '4px',
                  fontSize: '11px',
                  fontWeight: '600',
                  textAlign: 'center'
                }}>
                  📐 Layout Plan
                </div>
                <div style={{
                  position: 'absolute',
                  top: '6px',
                  right: '6px',
                  display: 'flex',
                  gap: '6px'
                }}>
                  <button
                    onClick={() => window.open(img, '_blank')}
                    style={{
                      background: 'rgba(255, 255, 255, 0.95)',
                      border: 'none',
                      borderRadius: '6px',
                      padding: '8px 10px',
                      fontSize: '14px',
                      cursor: 'pointer',
                      boxShadow: '0 2px 6px rgba(0,0,0,0.1)',
                      color: '#1e40af',
                      fontWeight: '600',
                      minWidth: '36px',
                      minHeight: '36px'
                    }}
                    title="View full size"
                  >
                    🔍
                  </button>
                  <button
                    onClick={async () => {
                      try {
                        // Extract image name from URL for the new API
                        const me = JSON.parse(sessionStorage.getItem('user') || '{}');
                        let imageName = '';
                        
                        // Try to extract image name from the current img URL
                        if (img.includes('/buildhub/backend/uploads/house_plans/')) {
                          imageName = img.split('/buildhub/backend/uploads/house_plans/')[1];
                        } else if (img.includes('serve_layout_image.php')) {
                          const urlParams = new URLSearchParams(img.split('?')[1]);
                          imageName = urlParams.get('image');
                        }
                        
                        if (imageName) {
                          const downloadUrl = `/buildhub/backend/api/contractor/serve_layout_image.php?contractor_id=${me.id}&image=${encodeURIComponent(imageName)}&download=1`;
                          const link = document.createElement('a');
                          link.href = downloadUrl;
                          link.download = imageName;
                          link.target = '_blank';
                          document.body.appendChild(link);
                          link.click();
                          document.body.removeChild(link);
                          toast.success('Layout image downloaded!');
                        } else {
                          // Fallback to old method if image name extraction fails
                          const response = await fetch(img);
                          const blob = await response.blob();
                          const url = window.URL.createObjectURL(blob);
                          const link = document.createElement('a');
                          link.href = url;
                          link.download = `layout_image_${Date.now()}.png`;
                          document.body.appendChild(link);
                          link.click();
                          document.body.removeChild(link);
                          window.URL.revokeObjectURL(url);
                          toast.success('Layout image downloaded!');
                        }
                      } catch (error) {
                        toast.error('Failed to download image');
                        console.error('Download error:', error);
                      }
                    }}
                    style={{
                      background: 'rgba(16, 185, 129, 0.95)',
                      border: 'none',
                      borderRadius: '6px',
                      padding: '8px 10px',
                      fontSize: '14px',
                      cursor: 'pointer',
                      boxShadow: '0 2px 6px rgba(0,0,0,0.1)',
                      color: 'white',
                      fontWeight: '600',
                      minWidth: '36px',
                      minHeight: '36px'
                    }}
                    title="Download image"
                  >
                    📥
                  </button>
                </div>
              </div>
            ) : (
              <div style={{
                width:'160px',
                height:'120px',
                display:'grid',
                placeItems:'center',
                border:'2px dashed #e5e7eb',
                borderRadius:8,
                background: '#f9fafb',
                color: '#6b7280'
              }}>
                <div style={{textAlign: 'center'}}>
                  <div style={{fontSize: '24px', marginBottom: '4px'}}>📋</div>
                  <div style={{fontSize: '12px'}}>No layout image</div>
                </div>
              </div>
            )}
          </div>
          <div style={{display:'grid',gap:6}}>
            {fd?.title && <div><strong>Design:</strong> {fd.title}</div>}
            {fd?.description && <div><strong>Description:</strong> {fd.description}</div>}
            
            {/* Homeowner Message in a nice card */}
            {item.message && (
              <div style={{
                background: 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)',
                borderLeft: '4px solid #3b82f6',
                padding: '16px',
                borderRadius: '8px',
                marginBottom: '8px'
              }}>
                <div style={{display: 'flex', alignItems: 'center', marginBottom: '8px'}}>
                  <span style={{fontSize: '20px', marginRight: '8px'}}>💬</span>
                  <strong style={{color: '#1e40af', fontSize: '15px'}}>Message from Homeowner</strong>
                </div>
                <div style={{
                  color: '#1e3a8a',
                  lineHeight: '1.6',
                  whiteSpace: 'pre-wrap',
                  fontSize: '14px',
                  paddingLeft: '28px'
                }}>
                  {item.message}
                </div>
              </div>
            )}
            
            {(item.plot_size || item.building_size) && (
              <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:8, padding: '10px', backgroundColor: '#f8f9fa', borderRadius: '6px', border: '1px solid #e0e0e0'}}>
                {item.plot_size && <div><strong>📐 Plot Size:</strong> {item.plot_size}</div>}
                {item.building_size && <div><strong>🏗️ Building Size:</strong> {item.building_size}</div>}
              </div>
            )}
            {floor && (
              <details style={{marginTop: '8px'}}>
                <summary style={{
                  cursor: 'pointer',
                  padding: '10px',
                  background: '#f8f9fa',
                  borderRadius: '6px',
                  fontWeight: '500',
                  fontSize: '14px',
                  border: '1px solid #dee2e6'
                }}>
                  🏢 Floor Details
                </summary>
                <div style={{marginTop:8, padding: '12px', background: '#f8f9fa', borderRadius: '6px'}}>
                  <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:8}}>
                    {floor.floors_count !== undefined && <div><strong>Floors:</strong> {String(floor.floors_count)}</div>}
                    {floor.floor_height && <div><strong>Floor height:</strong> {String(floor.floor_height)}</div>}
                    {floor.ground_floor_area && <div><strong>Ground floor area:</strong> {String(floor.ground_floor_area)}</div>}
                    {floor.first_floor_area && <div><strong>First floor area:</strong> {String(floor.first_floor_area)}</div>}
                    {floor.second_floor_area && <div><strong>Second floor area:</strong> {String(floor.second_floor_area)}</div>}
                    {floor.flooring_materials && <div style={{gridColumn:'1 / -1'}}><strong>Flooring materials:</strong> {String(floor.flooring_materials)}</div>}
                  </div>
                </div>
              </details>
            )}
            
            {/* Layout Images Section - Prominent display for contractor estimation */}
            {(payload.layout_images && Array.isArray(payload.layout_images) && payload.layout_images.length > 0) && (
              <div style={{
                marginTop: '12px',
                padding: '16px',
                background: 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)',
                borderRadius: '8px',
                border: '2px solid #3b82f6'
              }}>
                <div style={{display: 'flex', alignItems: 'center', marginBottom: '12px'}}>
                  <span style={{fontSize: '20px', marginRight: '8px'}}>📐</span>
                  <strong style={{color: '#1e40af', fontSize: '15px'}}>Layout Images for Estimation</strong>
                </div>
                <div style={{display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))', gap: '16px'}}>
                  {payload.layout_images.map((layoutImg, idx) => {
                    // Better image name extraction with fallbacks
                    let imageName = layoutImg.stored || layoutImg.original || layoutImg.name;
                    let fileName = layoutImg.original || layoutImg.name;
                    
                    // If not found in layout_images, try to get from technical_details
                    if (!imageName && payload.technical_details && payload.technical_details.layout_image) {
                      const techLayoutImg = payload.technical_details.layout_image;
                      imageName = techLayoutImg.stored || techLayoutImg.name;
                      fileName = techLayoutImg.name || techLayoutImg.original;
                    }
                    
                    // Extract from URL if still not found
                    if (!imageName && layoutImg.url) {
                      const urlParts = layoutImg.url.split('/');
                      imageName = urlParts[urlParts.length - 1];
                    }
                    
                    // Final fallback
                    if (!imageName) {
                      imageName = `layout_${idx + 1}.png`;
                    }
                    if (!fileName) {
                      fileName = imageName;
                    }
                    
                    // Use the new image serving API with fallback to direct URL
                    const me = JSON.parse(sessionStorage.getItem('user') || '{}');
                    let imgUrl, downloadUrl;
                    
                    if (imageName && imageName !== 'undefined' && me.id) {
                      imgUrl = `/buildhub/backend/api/contractor/serve_layout_image.php?contractor_id=${me.id}&image=${encodeURIComponent(imageName)}`;
                      downloadUrl = `/buildhub/backend/api/contractor/serve_layout_image.php?contractor_id=${me.id}&image=${encodeURIComponent(imageName)}&download=1`;
                    } else {
                      // Fallback to direct URL
                      imgUrl = assetUrl(layoutImg.url || layoutImg.path || `/buildhub/backend/uploads/house_plans/${imageName}`);
                      downloadUrl = imgUrl;
                    }
                    
                    const downloadImage = async () => {
                      try {
                        if (downloadUrl.includes('serve_layout_image.php')) {
                          // Use the API download
                          const link = document.createElement('a');
                          link.href = downloadUrl;
                          link.download = fileName;
                          link.target = '_blank';
                          document.body.appendChild(link);
                          link.click();
                          document.body.removeChild(link);
                        } else {
                          // Fallback to fetch method
                          const response = await fetch(imgUrl);
                          const blob = await response.blob();
                          const url = window.URL.createObjectURL(blob);
                          const link = document.createElement('a');
                          link.href = url;
                          link.download = fileName;
                          document.body.appendChild(link);
                          link.click();
                          document.body.removeChild(link);
                          window.URL.revokeObjectURL(url);
                        }
                        toast.success(`Downloaded: ${fileName}`);
                      } catch (error) {
                        toast.error('Failed to download image');
                        console.error('Download error:', error);
                      }
                    };
                    
                    return (
                      <div key={idx} style={{position: 'relative', border: '2px solid #3b82f6', borderRadius: '12px', overflow: 'hidden', boxShadow: '0 4px 8px rgba(0,0,0,0.1)'}}>
                        <img
                          src={imgUrl}
                          alt={`Layout ${idx + 1}`}
                          style={{
                            width: '100%',
                            height: '120px',
                            objectFit: 'cover',
                            cursor: 'pointer'
                          }}
                          onClick={() => window.open(imgUrl, '_blank')}
                          onError={(e) => { 
                            console.error('Image load error for:', imgUrl);
                            console.error('Layout image data:', layoutImg);
                            // Try fallback URL
                            const fallbackUrl = assetUrl(`/buildhub/backend/uploads/house_plans/${imageName}`);
                            if (e.currentTarget.src !== fallbackUrl) {
                              e.currentTarget.src = fallbackUrl;
                            } else {
                              e.currentTarget.style.display = 'none';
                            }
                          }}
                        />
                        
                        {/* Action buttons overlay - Increased size */}
                        <div style={{
                          position: 'absolute',
                          top: '8px',
                          right: '8px',
                          display: 'flex',
                          gap: '6px'
                        }}>
                          <button
                            onClick={() => window.open(imgUrl, '_blank')}
                            style={{
                              background: 'rgba(255, 255, 255, 0.95)',
                              border: 'none',
                              borderRadius: '6px',
                              padding: '8px 10px',
                              fontSize: '14px',
                              cursor: 'pointer',
                              boxShadow: '0 2px 6px rgba(0,0,0,0.2)',
                              color: '#1e40af',
                              fontWeight: '600',
                              minWidth: '36px',
                              minHeight: '36px'
                            }}
                            title="View full size"
                          >
                            🔍
                          </button>
                          <button
                            onClick={downloadImage}
                            style={{
                              background: 'rgba(16, 185, 129, 0.95)',
                              border: 'none',
                              borderRadius: '6px',
                              padding: '8px 10px',
                              fontSize: '14px',
                              cursor: 'pointer',
                              boxShadow: '0 2px 6px rgba(0,0,0,0.2)',
                              color: 'white',
                              fontWeight: '600',
                              minWidth: '36px',
                              minHeight: '36px'
                            }}
                            title="Download image"
                          >
                            📥
                          </button>
                        </div>
                        
                        {/* Image name label */}
                        <div style={{
                          position: 'absolute',
                          bottom: '0',
                          left: '0',
                          right: '0',
                          background: 'rgba(30, 64, 175, 0.95)',
                          color: 'white',
                          padding: '6px 8px',
                          fontSize: '11px',
                          textAlign: 'center',
                          fontWeight: '600'
                        }}>
                          {fileName}
                        </div>
                      </div>
                    );
                  })}
                </div>
                <div style={{
                  marginTop: '12px',
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center'
                }}>
                  <div style={{
                    fontSize: '12px',
                    color: '#1e40af',
                    fontStyle: 'italic'
                  }}>
                    💡 Click 🔍 to view full size • Click 📥 to download
                  </div>
                  {payload.layout_images.length > 1 && (
                    <button
                      onClick={async () => {
                        try {
                          const me = JSON.parse(sessionStorage.getItem('user') || '{}');
                          for (let i = 0; i < payload.layout_images.length; i++) {
                            const layoutImg = payload.layout_images[i];
                            
                            // Better image name extraction with fallbacks
                            let imageName = layoutImg.stored || layoutImg.original || layoutImg.name;
                            let fileName = layoutImg.original || layoutImg.name;
                            
                            // If not found in layout_images, try to get from technical_details
                            if (!imageName && payload.technical_details && payload.technical_details.layout_image) {
                              const techLayoutImg = payload.technical_details.layout_image;
                              imageName = techLayoutImg.stored || techLayoutImg.name;
                              fileName = techLayoutImg.name || techLayoutImg.original;
                            }
                            
                            // Extract from URL if still not found
                            if (!imageName && layoutImg.url) {
                              const urlParts = layoutImg.url.split('/');
                              imageName = urlParts[urlParts.length - 1];
                            }
                            
                            // Final fallback
                            if (!imageName) {
                              imageName = `layout_${i + 1}.png`;
                            }
                            if (!fileName) {
                              fileName = imageName;
                            }
                            
                            let downloadUrl;
                            if (imageName && imageName !== 'undefined' && me.id) {
                              downloadUrl = `/buildhub/backend/api/contractor/serve_layout_image.php?contractor_id=${me.id}&image=${encodeURIComponent(imageName)}&download=1`;
                            } else {
                              downloadUrl = assetUrl(layoutImg.url || layoutImg.path || `/buildhub/backend/uploads/house_plans/${imageName}`);
                            }
                            
                            // Add delay between downloads to avoid browser blocking
                            setTimeout(() => {
                              try {
                                const link = document.createElement('a');
                                link.href = downloadUrl;
                                link.download = fileName;
                                link.target = '_blank';
                                document.body.appendChild(link);
                                link.click();
                                document.body.removeChild(link);
                              } catch (error) {
                                console.error(`Failed to download ${fileName}:`, error);
                              }
                            }, i * 500); // 500ms delay between downloads
                          }
                          toast.success(`Downloading ${payload.layout_images.length} layout images...`);
                        } catch (error) {
                          toast.error('Failed to download images');
                          console.error('Bulk download error:', error);
                        }
                      }}
                      style={{
                        background: '#10b981',
                        color: 'white',
                        border: 'none',
                        borderRadius: '8px',
                        padding: '10px 16px',
                        fontSize: '14px',
                        cursor: 'pointer',
                        fontWeight: '600',
                        boxShadow: '0 2px 6px rgba(0,0,0,0.1)',
                        minHeight: '40px'
                      }}
                      title="Download all layout images"
                    >
                      📥 Download All ({payload.layout_images.length})
                    </button>
                  )}
                </div>
              </div>
            )}
            
            {technical && (
              <div style={{marginTop: 16}}>
                <TechnicalDetailsDisplay technicalDetails={technical} startExpanded={false} />
              </div>
            )}
            {fd?.files && Array.isArray(fd.files) && fd.files.length > 0 && (
              <details style={{marginTop: '8px'}}>
                <summary style={{
                  cursor: 'pointer',
                  padding: '10px',
                  background: '#f8f9fa',
                  borderRadius: '6px',
                  fontWeight: '500',
                  fontSize: '14px',
                  border: '1px solid #dee2e6'
                }}>
                  📎 Files ({fd.files.length})
                </summary>
                <ul style={{margin:'8px 0 0 16px', listStyle: 'none', padding: '8px'}}>
                  {fd.files.map((f, idx) => {
                    const url = assetUrl(f.url || f.path || (typeof f === 'string' ? f : ''));
                    const name = f.name || (typeof f === 'string' ? f : `File ${idx+1}`);
                    return (
                      <li key={idx} style={{marginBottom: '6px'}}>
                        <a 
                          href={url} 
                          target="_blank" 
                          rel="noreferrer"
                          style={{
                            color: '#3b82f6',
                            textDecoration: 'none',
                            padding: '4px 8px',
                            borderRadius: '4px',
                            display: 'inline-block',
                            background: '#e0f2fe',
                            transition: 'background 0.2s'
                          }}
                          onMouseEnter={(e) => e.target.style.background = '#bae6fd'}
                          onMouseLeave={(e) => e.target.style.background = '#e0f2fe'}
                        >
                          📄 {name}
                        </a>
                      </li>
                    );
                  })}
                </ul>
              </details>
            )}

            {/* Submit Estimate for this send - Only show if acknowledged */}
            {item.acknowledged_at ? (
              <div style={{marginTop: '12px'}}>
                {/* Estimation Form */}
                <details 
                  style={{marginTop: '12px'}}
                  onToggle={(e) => {
                    if (e.target.open && estimateFormRef.current) {
                      // Load and populate draft when details is opened
                      setTimeout(() => {
                        const form = estimateFormRef.current;
                        
                        // Always ensure project name and client info are set correctly
                        const projectNameInput = form.querySelector('[name="structured[project_name]"]');
                        const clientNameInput = form.querySelector('[name="structured[client_name]"]');
                        const clientContactInput = form.querySelector('[name="structured[client_contact]"]');
                        
                        if (projectNameInput) {
                          projectNameInput.value = `${item?.homeowner_name || 'Homeowner'} Construction`;
                        }
                        if (clientNameInput) {
                          clientNameInput.value = item?.homeowner_name || '';
                        }
                        if (clientContactInput && !clientContactInput.value) {
                          clientContactInput.value = item?.homeowner_email || '';
                        }
                        
                        // Then load draft data (but don't override readonly fields)
                        const draft = draftData[item?.id];
                        if (draft) {
                          populateFormFromDraft(form, draft);
                        }
                      }, 100);
                    }
                  }}
                >
                  <summary style={{
                    cursor: 'pointer',
                    padding: '8px 12px',
                    background: '#f3f4f6',
                    border: '1px solid #d1d5db',
                    borderRadius: '6px',
                    fontSize: '14px',
                    fontWeight: '500',
                    color: '#374151',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between'
                  }}>
                    <span>📝 Create Estimate</span>
                    {draftData[item?.id] && (
                      <span style={{
                        fontSize: '12px',
                        color: '#10b981',
                        fontWeight: '600',
                        background: '#ecfdf5',
                        padding: '2px 8px',
                        borderRadius: '12px',
                        border: '1px solid #a7f3d0'
                      }}>
                        💾 Draft {lastSaved[item?.id] && `• ${lastSaved[item?.id]}`}
                      </span>
                    )}
                  </summary>
              
              <form 
                ref={estimateFormRef} 
                className="estimate-form" 
                onInput={(e) => { 
                  try { 
                    recalcTotalsFromForm(e.currentTarget);
                    // Auto-save draft on input changes
                    handleFormChange(item?.id, e.currentTarget);
                  } catch(_) {} 
                }} 
                onSubmit={async (e) => {
                e.preventDefault();
                const me = JSON.parse(sessionStorage.getItem('user') || '{}');
                const form = e.currentTarget;
                const sid = String(item?.id || form.querySelector('input[name="send_id"]')?.value || '');
                const cid = String(user?.id || me?.id || form.querySelector('input[name="contractor_id"]')?.value || '');
                if (!sid || !cid) {
                  toast.error('Missing identifiers. Please refresh and try again.');
                  return;
                }
                // Build structured object from inputs named like structured[...]
                const structured = {};
                const setNested = (obj, pathArr, value) => {
                  let ref = obj;
                  for (let i = 0; i < pathArr.length - 1; i++) {
                    const key = pathArr[i];
                    if (!(key in ref) || typeof ref[key] !== 'object') ref[key] = {};
                    ref = ref[key];
                  }
                  ref[pathArr[pathArr.length - 1]] = value;
                };
                const els = form.querySelectorAll('[name^="structured[" ]');
                els.forEach((el) => {
                  const name = el.getAttribute('name');
                  const m = name.match(/^structured\[(.+)\]$/);
                  if (!m) return;
                  const raw = m[1];
                  const parts = raw.split('][').map(s => s.replace(/\]$/,'').replace(/^\[/,''));
                  const val = el.value;
                  setNested(structured, parts, val);
                });
                const payload = {
                  send_id: Number(sid),
                  contractor_id: Number(cid),
                  materials: form.querySelector('textarea[name="materials"]')?.value || '',
                  cost_breakdown: form.querySelector('textarea[name="cost_breakdown"]')?.value || '',
                  total_cost: form.querySelector('input[name="total_cost"]')?.value || '',
                  timeline: form.querySelector('input[name="timeline"]')?.value || '',
                  notes: form.querySelector('textarea[name="notes"]')?.value || '',
                  structured
                };
                try {
                  const res = await fetch('/buildhub/backend/api/contractor/submit_estimate_for_send.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                  });
                  const json = await res.json();
                  if (json?.success) {
                    toast.success('Estimate submitted successfully!');
                    form.reset();
                    
                    // Clear draft data for this send
                    const sendId = Number(sid);
                    setDraftData(prev => {
                      const newData = { ...prev };
                      delete newData[sendId];
                      return newData;
                    });
                    setLastSaved(prev => {
                      const newData = { ...prev };
                      delete newData[sendId];
                      return newData;
                    });
                    
                    // Refresh My Estimates section
                    try {
                      const me = JSON.parse(sessionStorage.getItem('user') || '{}');
                      if (me?.id) {
                        const r2 = await fetch(`/buildhub/backend/api/contractor/get_my_estimates.php?contractor_id=${me.id}`, { credentials: 'include' });
                        const j2 = await r2.json().catch(() => ({}));
                        if (j2?.success) setMyEstimates(Array.isArray(j2.estimates) ? j2.estimates : []);
                      }
                    } catch (refreshError) {
                      console.error('Error refreshing estimates:', refreshError);
                    }
                  } else {
                    toast.error(json?.message || 'Failed to submit');
                  }
                } catch {
                  toast.error('Network error');
                }
              }}>
                {/* BuildHub Estimation Seal */}
                <div style={{ display: 'flex', justifyContent: 'center', marginBottom: '20px', padding: '20px 0' }}>
                  <BuildHubSeal size="medium" />
                </div>

                {/* Site Details Preview */}
                {(item?.plot_size || item?.building_size || item?.layout_request_details) && (
                  <div style={{
                    background: 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)',
                    border: '2px solid #22c55e',
                    borderRadius: '12px',
                    padding: '16px',
                    marginBottom: '20px',
                    boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)'
                  }}>
                    <div style={{
                      display: 'flex',
                      alignItems: 'center',
                      marginBottom: '12px',
                      paddingBottom: '8px',
                      borderBottom: '2px solid #22c55e'
                    }}>
                      <span style={{ fontSize: '20px', marginRight: '8px' }}>🏗️</span>
                      <h4 style={{ margin: 0, color: '#15803d', fontSize: '16px', fontWeight: '600' }}>
                        Site Details Available
                      </h4>
                      <span style={{
                        marginLeft: 'auto',
                        fontSize: '12px',
                        background: '#22c55e',
                        color: 'white',
                        padding: '4px 8px',
                        borderRadius: '12px',
                        fontWeight: '600'
                      }}>
                        ✅ Auto-populated
                      </span>
                    </div>
                    <div style={{
                      display: 'grid',
                      gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
                      gap: '12px'
                    }}>
                      {(item?.plot_size || item?.layout_request_details?.plot_size) && (
                        <div style={{ display: 'flex', alignItems: 'center' }}>
                          <strong style={{ color: '#15803d' }}>📐 Plot Size:</strong>
                          <span style={{ marginLeft: '8px', color: '#166534', fontWeight: '600' }}>
                            {item?.plot_size || item?.layout_request_details?.plot_size}
                          </span>
                        </div>
                      )}
                      {(item?.building_size || item?.layout_request_details?.building_size) && (
                        <div style={{ display: 'flex', alignItems: 'center' }}>
                          <strong style={{ color: '#15803d' }}>🏠 Building Size:</strong>
                          <span style={{ marginLeft: '8px', color: '#166534', fontWeight: '600' }}>
                            {item?.building_size || item?.layout_request_details?.building_size}
                          </span>
                        </div>
                      )}
                      {(item?.budget_range || item?.layout_request_details?.budget_range) && (
                        <div style={{ display: 'flex', alignItems: 'center' }}>
                          <strong style={{ color: '#15803d' }}>💰 Budget Range:</strong>
                          <span style={{ marginLeft: '8px', color: '#166534', fontWeight: '600' }}>
                            {item?.budget_range || item?.layout_request_details?.budget_range}
                          </span>
                        </div>
                      )}
                      {(item?.timeline || item?.layout_request_details?.timeline) && (
                        <div style={{ display: 'flex', alignItems: 'center' }}>
                          <strong style={{ color: '#15803d' }}>⏱️ Timeline:</strong>
                          <span style={{ marginLeft: '8px', color: '#166534', fontWeight: '600' }}>
                            {item?.timeline || item?.layout_request_details?.timeline}
                          </span>
                        </div>
                      )}
                      {(item?.num_floors || item?.layout_request_details?.num_floors) && (
                        <div style={{ display: 'flex', alignItems: 'center' }}>
                          <strong style={{ color: '#15803d' }}>🏢 Floors:</strong>
                          <span style={{ marginLeft: '8px', color: '#166534', fontWeight: '600' }}>
                            {item?.num_floors || item?.layout_request_details?.num_floors}
                          </span>
                        </div>
                      )}
                      {(item?.layout_request_details?.location) && (
                        <div style={{ display: 'flex', alignItems: 'center' }}>
                          <strong style={{ color: '#15803d' }}>📍 Location:</strong>
                          <span style={{ marginLeft: '8px', color: '#166534', fontWeight: '600' }}>
                            {item?.layout_request_details?.location}
                          </span>
                        </div>
                      )}
                    </div>
                  </div>
                )}
                
                {/* Hidden identifiers for backend (use defaultValue to avoid React controlled issues) */}
                <input type="hidden" name="send_id" defaultValue={String(item?.id || '')} />
                <input type="hidden" name="contractor_id" defaultValue={String(user?.id || (JSON.parse(sessionStorage.getItem('user')||'{}').id || ''))} />
                {/* helper: auto-calc grand total from section totals */}
                <script dangerouslySetInnerHTML={{__html:`window.__bhCalcGrandTotal = window.__bhCalcGrandTotal || function(form){try{var m=form.querySelector('[name="structured[totals][materials]"]');var l=form.querySelector('[name="structured[totals][labor]"]');var u=form.querySelector('[name="structured[totals][utilities]"]');var s=form.querySelector('[name="structured[totals][misc]"]');var g=form.querySelector('[name="structured[totals][grand]"]');var tc=form.querySelector('[name="total_cost"]');function num(v){if(!v) return 0;return parseFloat(String(v).replace(/[,\s]/g,''))||0;}var sum=num(m&&m.value)+num(l&&l.value)+num(u&&u.value)+num(s&&s.value);if(g){g.value=sum?String(sum):'';}if(tc){tc.value=sum?String(sum):tc.value;}}catch(e){}};`}} />
                {/* section calculators */}
                <script dangerouslySetInnerHTML={{__html:`window.__bhCalcSectionTotals = window.__bhCalcSectionTotals || function(form){try{function sumSection(prefix){var inputs=form.querySelectorAll('[name^="'+prefix+'"]');var total=0;inputs.forEach(function(inp){var v=(inp && inp.value)||'';var match=v.match(/[-+]?(?:\\d+\\.?\\d*|\\d*\\.?\\d+)/g);if(match){match.forEach(function(tok){var n=parseFloat(tok);if(!isNaN(n)) total+=n;});}});return total;}var m=sumSection('structured[materials]');var l=sumSection('structured[labor]');var u=sumSection('structured[utilities]');var s=sumSection('structured[misc]');var fm=form.querySelector('[name="structured[totals][materials]"]'); if(fm) fm.value = m?String(m):'';var fl=form.querySelector('[name="structured[totals][labor]"]'); if(fl) fl.value = l?String(l):'';var fu=form.querySelector('[name="structured[totals][utilities]"]'); if(fu) fu.value = u?String(u):'';var fs=form.querySelector('[name="structured[totals][misc]"]'); if(fs) fs.value = s?String(s):''; if(typeof window.__bhCalcGrandTotal==='function'){ window.__bhCalcGrandTotal(form); } }catch(e){}};`}} />
                {/* 1. Basic Project Information */}
                <div className="section-title">1. Basic Project Information</div>
                <div className="grid-2">
                  <input 
                    name="structured[project_name]" 
                    placeholder="Project Name" 
                    list="bh_project_names" 
                    defaultValue={`${item?.homeowner_name || 'Homeowner'} Construction`}
                    readOnly
                    style={{
                      background: 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)',
                      border: '2px solid #3b82f6',
                      color: '#1e40af',
                      fontWeight: '600'
                    }}
                  />
                  <input 
                    name="structured[project_address]" 
                    placeholder="Project Address / Location" 
                    defaultValue={item?.layout_request_details?.location || ''}
                  />
                  <input 
                    name="structured[plot_size]" 
                    placeholder="Plot Size (sq.ft / sq.m)" 
                    defaultValue={item?.plot_size || item?.layout_request_details?.plot_size || ''}
                    style={item?.plot_size || item?.layout_request_details?.plot_size ? {
                      background: 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)',
                      border: '2px solid #22c55e',
                      color: '#15803d',
                      fontWeight: '600'
                    } : {}}
                  />
                  <input 
                    name="structured[built_up_area]" 
                    placeholder="Built-up Area (sq.ft / sq.m)" 
                    defaultValue={item?.building_size || item?.layout_request_details?.building_size || ''}
                    style={item?.building_size || item?.layout_request_details?.building_size ? {
                      background: 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)',
                      border: '2px solid #22c55e',
                      color: '#15803d',
                      fontWeight: '600'
                    } : {}}
                  />
                  <select 
                    name="structured[floors]" 
                    defaultValue={item?.num_floors || item?.layout_request_details?.num_floors || ""}
                    style={item?.num_floors || item?.layout_request_details?.num_floors ? {
                      background: 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)',
                      border: '2px solid #22c55e',
                      color: '#15803d',
                      fontWeight: '600'
                    } : {}}
                  >
                    <option value="" disabled>Number of Floors</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                  </select>
                  <input name="structured[estimation_date]" type="date" placeholder="Estimation Date" />
                  <input 
                    name="structured[client_name]" 
                    placeholder="Client / Homeowner Name" 
                    defaultValue={item?.homeowner_name || ''}
                    readOnly
                    style={{
                      background: 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)',
                      border: '2px solid #3b82f6',
                      color: '#1e40af',
                      fontWeight: '600'
                    }}
                  />
                  <input 
                    name="structured[client_contact]" 
                    placeholder="Contact Info" 
                    defaultValue={item?.homeowner_email || ''}
                  />
                </div>

                {/* 2. Material Costs */}
                <div className="section-title" style={{marginTop:8}}>2. Material Costs</div>
                <div className="muted" style={{marginTop:4}}>Tip: enter numeric amounts anywhere in the field (e.g., "OPC 53 - 60000"). The calculator sums the numbers it finds.</div>
                <div 
                  className="grid-3" 
                  onInput={(e) => { 
                    try { 
                      recalcTotalsFromForm(e.currentTarget.closest('form')); 
                    } catch(_) {} 
                  }}
                >
                  <div className="estimate-line grid-4">
                    <input name="structured[materials][cement][name]" placeholder="Cement grade (OPC 43/53, PPC)" list="bh_cement" />
                    <input name="structured[materials][cement][qty]" placeholder="Qty (bags) e.g., 50" />
                    <input name="structured[materials][cement][rate]" placeholder="Rate (₹ per bag) e.g., 380" />
                    <input name="structured[materials][cement][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[materials][sand][name]" placeholder="Sand type (River/M-sand)" list="bh_sand" />
                    <input name="structured[materials][sand][qty]" placeholder="Qty (m³) e.g., 6" />
                    <input name="structured[materials][sand][rate]" placeholder="Rate (₹ per m³) e.g., 2500" />
                    <input name="structured[materials][sand][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[materials][bricks][name]" placeholder="Bricks/Blocks (Clay/AAC)" list="bh_bricks" />
                    <input name="structured[materials][bricks][qty]" placeholder="Qty (nos) e.g., 5000" />
                    <input name="structured[materials][bricks][rate]" placeholder="Rate (₹ per 1000) e.g., 8500" />
                    <input name="structured[materials][bricks][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[materials][steel][name]" placeholder="Steel/TMT (8/10/12mm)" list="bh_steel" />
                    <input name="structured[materials][steel][qty]" placeholder="Qty (kg) e.g., 1200" />
                    <input name="structured[materials][steel][rate]" placeholder="Rate (₹ per kg) e.g., 68" />
                    <input name="structured[materials][steel][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[materials][aggregate][name]" placeholder="Aggregate (10/20mm)" list="bh_aggregate" />
                    <input name="structured[materials][aggregate][qty]" placeholder="Qty (m³) e.g., 8" />
                    <input name="structured[materials][aggregate][rate]" placeholder="Rate (₹ per m³) e.g., 1800" />
                    <input name="structured[materials][aggregate][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[materials][tiles][name]" placeholder="Tiles/Flooring (Vitrified/Ceramic)" list="bh_tiles" />
                    <input name="structured[materials][tiles][qty]" placeholder="Qty (m²) e.g., 120" />
                    <input name="structured[materials][tiles][rate]" placeholder="Rate (₹ per m²) e.g., 600" />
                    <input name="structured[materials][tiles][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[materials][paint][name]" placeholder="Paint (Interior/Exterior)" list="bh_paint" />
                    <input name="structured[materials][paint][qty]" placeholder="Qty (L) e.g., 80" />
                    <input name="structured[materials][paint][rate]" placeholder="Rate (₹ per L) e.g., 250" />
                    <input name="structured[materials][paint][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[materials][doors][name]" placeholder="Doors (Teak/Flush)" list="bh_doors" />
                    <input name="structured[materials][doors][qty]" placeholder="Qty (nos) e.g., 10" />
                    <input name="structured[materials][doors][rate]" placeholder="Rate (₹ per door) e.g., 7000" />
                    <input name="structured[materials][doors][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[materials][windows][name]" placeholder="Windows (uPVC/Aluminium)" list="bh_windows" />
                    <input name="structured[materials][windows][qty]" placeholder="Qty (nos) e.g., 12" />
                    <input name="structured[materials][windows][rate]" placeholder="Rate (₹ per window) e.g., 6000" />
                    <input name="structured[materials][windows][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4" style={{gridColumn:'1 / -1'}}>
                    <input name="structured[materials][others][name]" placeholder="Other material (e.g., Glass/Hardware)" />
                    <input name="structured[materials][others][qty]" placeholder="Qty (units)" />
                    <input name="structured[materials][others][rate]" placeholder="Rate (₹ per unit)" />
                    <input name="structured[materials][others][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                </div>

                {/* 3. Labor Charges */}
                <div className="section-title" style={{marginTop:8}}>3. Labor Charges</div>
                <div 
                  className="grid-3" 
                  onInput={(e) => { 
                    try { 
                      recalcTotalsFromForm(e.currentTarget.closest('form')); 
                    } catch(_) {} 
                  }}
                >
                  <div className="estimate-line grid-4">
                    <input name="structured[labor][mason][name]" placeholder="Masonry work" list="bh_labor_mason" />
                    <input name="structured[labor][mason][qty]" placeholder="Qty (m³ / days)" />
                    <input name="structured[labor][mason][rate]" placeholder="Rate (₹ per unit)" />
                    <input name="structured[labor][mason][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[labor][plaster][name]" placeholder="Plaster work" list="bh_labor_plaster" />
                    <input name="structured[labor][plaster][qty]" placeholder="Qty (m²)" />
                    <input name="structured[labor][plaster][rate]" placeholder="Rate (₹ per m²)" />
                    <input name="structured[labor][plaster][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[labor][painting][name]" placeholder="Painting" list="bh_labor_painting" />
                    <input name="structured[labor][painting][qty]" placeholder="Qty (m² / rooms)" />
                    <input name="structured[labor][painting][rate]" placeholder="Rate (₹ per unit)" />
                    <input name="structured[labor][painting][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[labor][electrical][name]" placeholder="Electrical" list="bh_labor_electrical" />
                    <input name="structured[labor][electrical][qty]" placeholder="Qty (points / rooms)" />
                    <input name="structured[labor][electrical][rate]" placeholder="Rate (₹ per point/room)" />
                    <input name="structured[labor][electrical][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[labor][plumbing][name]" placeholder="Plumbing" list="bh_labor_plumbing" />
                    <input name="structured[labor][plumbing][qty]" placeholder="Qty (fittings / rooms)" />
                    <input name="structured[labor][plumbing][rate]" placeholder="Rate (₹ per fitting)" />
                    <input name="structured[labor][plumbing][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[labor][flooring][name]" placeholder="Flooring installation" list="bh_labor_flooring" />
                    <input name="structured[labor][flooring][qty]" placeholder="Qty (m²)" />
                    <input name="structured[labor][flooring][rate]" placeholder="Rate (₹ per m²)" />
                    <input name="structured[labor][flooring][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[labor][roofing][name]" placeholder="Roofing/Ceiling work" list="bh_labor_roofing" />
                    <input name="structured[labor][roofing][qty]" placeholder="Qty (m²)" />
                    <input name="structured[labor][roofing][rate]" placeholder="Rate (₹ per m²)" />
                    <input name="structured[labor][roofing][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4" style={{gridColumn:'1 / -1'}}>
                    <input name="structured[labor][others][name]" placeholder="Other labor" />
                    <input name="structured[labor][others][qty]" placeholder="Qty (units)" />
                    <input name="structured[labor][others][rate]" placeholder="Rate (₹ per unit)" />
                    <input name="structured[labor][others][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                </div>

                {/* 4. Utilities & Fixtures */}
                <div className="section-title" style={{marginTop:8}}>4. Utilities & Fixtures</div>
                <div 
                  className="grid-3" 
                  onInput={(e) => { 
                    try { 
                      recalcTotalsFromForm(e.currentTarget.closest('form')); 
                    } catch(_) {} 
                  }}
                >
                  <div className="estimate-line grid-4">
                    <input name="structured[utilities][sanitary][name]" placeholder="Sanitary fittings" list="bh_util_sanitary" />
                    <input name="structured[utilities][sanitary][qty]" placeholder="Qty (sets)" />
                    <input name="structured[utilities][sanitary][rate]" placeholder="Rate (₹ per set)" />
                    <input name="structured[utilities][sanitary][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[utilities][kitchen][name]" placeholder="Kitchen cabinets / modular" list="bh_util_kitchen" />
                    <input name="structured[utilities][kitchen][qty]" placeholder="Qty (ft / set)" />
                    <input name="structured[utilities][kitchen][rate]" placeholder="Rate (₹ per ft/set)" />
                    <input name="structured[utilities][kitchen][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[utilities][electrical_fixtures][name]" placeholder="Electrical fixtures" list="bh_util_electrical_fixtures" />
                    <input name="structured[utilities][electrical_fixtures][qty]" placeholder="Qty (points)" />
                    <input name="structured[utilities][electrical_fixtures][rate]" placeholder="Rate (₹ per point)" />
                    <input name="structured[utilities][electrical_fixtures][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[utilities][water_tank][name]" placeholder="Water tank & pumps" list="bh_util_watertank" />
                    <input name="structured[utilities][water_tank][qty]" placeholder="Qty (units)" />
                    <input name="structured[utilities][water_tank][rate]" placeholder="Rate (₹ per unit)" />
                    <input name="structured[utilities][water_tank][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[utilities][hvac][name]" placeholder="AC / Heating" list="bh_util_hvac" />
                    <input name="structured[utilities][hvac][qty]" placeholder="Qty (tons / units)" />
                    <input name="structured[utilities][hvac][rate]" placeholder="Rate (₹ per unit)" />
                    <input name="structured[utilities][hvac][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[utilities][gas_water][name]" placeholder="Gas / Water lines" list="bh_util_gaswater" />
                    <input name="structured[utilities][gas_water][qty]" placeholder="Qty (m / points)" />
                    <input name="structured[utilities][gas_water][rate]" placeholder="Rate (₹ per m/point)" />
                    <input name="structured[utilities][gas_water][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  {/* Other utilities: simple Item + Price rows */}
                  <div className="estimate-line grid-4" style={{gridColumn:'1 / -1'}}>
                    <input name="structured[utilities][others1][name]" placeholder="Other utility item (e.g., Geyser install)" />
                    <div />
                    <div />
                    <input name="structured[utilities][others1][amount]" placeholder="Price (₹)" />
                  </div>
                  <div className="estimate-line grid-4" style={{gridColumn:'1 / -1'}}>
                    <input name="structured[utilities][others2][name]" placeholder="Other utility item" />
                    <div />
                    <div />
                    <input name="structured[utilities][others2][amount]" placeholder="Price (₹)" />
                  </div>
                  <div className="estimate-line grid-4" style={{gridColumn:'1 / -1'}}>
                    <input name="structured[utilities][others3][name]" placeholder="Other utility item" />
                    <div />
                    <div />
                    <input name="structured[utilities][others3][amount]" placeholder="Price (₹)" />
                  </div>
                </div>

                {/* 5. Miscellaneous Costs */}
                <div className="section-title" style={{marginTop:8}}>5. Miscellaneous Costs</div>
                <div 
                  className="grid-3" 
                  onInput={(e) => { 
                    try { 
                      recalcTotalsFromForm(e.currentTarget.closest('form')); 
                    } catch(_) {} 
                  }}
                >
                  <div className="estimate-line grid-4">
                    <input name="structured[misc][transport][name]" placeholder="Transport (local/long-haul)" list="bh_misc_transport" />
                    <input name="structured[misc][transport][qty]" placeholder="Qty (trips)" />
                    <input name="structured[misc][transport][rate]" placeholder="Rate (₹ per trip)" />
                    <input name="structured[misc][transport][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[misc][contingency][name]" placeholder="Contingency buffer" list="bh_misc_contingency" />
                    <input name="structured[misc][contingency][qty]" placeholder="Qty (%) e.g., 5" />
                    <input name="structured[misc][contingency][rate]" placeholder="Base amount (₹)" />
                    <input name="structured[misc][contingency][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                <div className="estimate-line grid-4">
                  <input name="structured[misc][fees][name]" placeholder="Permit/Municipal/Registration fees" list="bh_misc_fees" />
                  <div />
                  <div />
                  <input name="structured[misc][fees][amount]" placeholder="Price (₹)" />
                </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[misc][cleaning][name]" placeholder="Cleaning / Waste removal" list="bh_misc_cleaning" />
                    <input name="structured[misc][cleaning][qty]" placeholder="Qty (days / loads)" />
                    <input name="structured[misc][cleaning][rate]" placeholder="Rate (₹ per unit)" />
                    <input name="structured[misc][cleaning][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  <div className="estimate-line grid-4">
                    <input name="structured[misc][safety][name]" placeholder="Safety equipment / Scaffolding" list="bh_misc_safety" />
                    <input name="structured[misc][safety][qty]" placeholder="Qty (sets / days)" />
                    <input name="structured[misc][safety][rate]" placeholder="Rate (₹ per unit)" />
                    <input name="structured[misc][safety][amount]" placeholder="Amount (auto)" readOnly />
                  </div>
                  {/* Other miscellaneous: simple Item + Price rows */}
                  <div className="estimate-line grid-4" style={{gridColumn:'1 / -1'}}>
                    <input name="structured[misc][others1][name]" placeholder="Other expense (e.g., Site security)" />
                    <div />
                    <div />
                    <input name="structured[misc][others1][amount]" placeholder="Price (₹)" />
                  </div>
                  <div className="estimate-line grid-4" style={{gridColumn:'1 / -1'}}>
                    <input name="structured[misc][others2][name]" placeholder="Other expense" />
                    <div />
                    <div />
                    <input name="structured[misc][others2][amount]" placeholder="Price (₹)" />
                  </div>
                  <div className="estimate-line grid-4" style={{gridColumn:'1 / -1'}}>
                    <input name="structured[misc][others3][name]" placeholder="Other expense" />
                    <div />
                    <div />
                    <input name="structured[misc][others3][amount]" placeholder="Price (₹)" />
                  </div>
                </div>

                {/* 6. Totals */}
                <div className="section-title" style={{marginTop:8}}>6. Total Estimation</div>
                <div 
                  className="totals" 
                  onInput={(e) => { 
                    try { 
                      recalcTotalsFromForm(e.currentTarget.closest('form')); 
                    } catch(_) {} 
                  }}
                >
                  <input name="structured[totals][materials]" placeholder="Material Costs Total" value={materialsTotal || ''} readOnly style={{border:'1px solid #e5e7eb', borderRadius:6, padding:8}} />
                  <input name="structured[totals][labor]" placeholder="Labor Charges Total" value={laborTotal || ''} readOnly style={{border:'1px solid #e5e7eb', borderRadius:6, padding:8}} />
                  <input name="structured[totals][utilities]" placeholder="Utilities & Fixtures Total" value={utilitiesTotal || ''} readOnly style={{border:'1px solid #e5e7eb', borderRadius:6, padding:8}} />
                  <input name="structured[totals][misc]" placeholder="Miscellaneous Total" value={miscTotal || ''} readOnly style={{border:'1px solid #e5e7eb', borderRadius:6, padding:8}} />
                  <input className="grand-total" name="structured[totals][grand]" placeholder="Grand Total (auto)" readOnly value={grandTotal || ''} />
                </div>

                {/* 7. Notes for Homeowner */}
                <div className="section-title" style={{marginTop:8}}>7. Notes for Homeowner</div>
                <textarea name="notes" placeholder="Prices approximate; quantities based on layout; payment schedule; taxes; warranties" rows={3} />

                {/* 8. Optional Details for Transparency */}
                <div style={{fontWeight:700, marginTop:8}}>8. Optional Details</div>
                <textarea name="structured[brands]" placeholder="Brands (cement, steel, tiles) / Grades / Units / Photos links" rows={2} style={{border:'1px solid #e5e7eb', borderRadius:6, padding:8}} />
                <label style={{fontWeight:600}}>Materials</label>
                <textarea name="materials" placeholder="List key materials, grades, brands if any" rows={2} style={{border:'1px solid #e5e7eb', borderRadius:6, padding:8}} />
                <label style={{fontWeight:600}}>Cost breakdown</label>
                <textarea name="cost_breakdown" placeholder="Itemized costs (e.g., excavation, RCC, masonry, finishes, labor)" rows={3} style={{border:'1px solid #e5e7eb', borderRadius:6, padding:8}} />
                <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:8}}>
                  <div>
                    <label style={{fontWeight:600}}>Total cost (₹)</label>
                    <input name="total_cost" type="number" step="0.01" placeholder="Auto-calculated" required readOnly style={{width:'100%', border:'1px solid #e5e7eb', borderRadius:6, padding:8, background:'#f9fafb'}} />
                  </div>
                  <div>
                    <label style={{fontWeight:600}}>Timeline</label>
                    <input name="timeline" placeholder="e.g., 8–10 weeks" required style={{width:'100%', border:'1px solid #e5e7eb', borderRadius:6, padding:8}} />
                  </div>
                </div>
                <label style={{fontWeight:600}}>Notes</label>
                <textarea name="notes" placeholder="Assumptions, exclusions, payment terms, validity" rows={2} style={{border:'1px solid #e5e7eb', borderRadius:6, padding:8}} />
                <div style={{padding:10, background:'#f8fafc', border:'1px dashed #cbd5e1', borderRadius:8}}>
                  <div style={{fontWeight:600, marginBottom:6}}>Preview</div>
                  <div style={{fontSize:14, color:'#374151'}}>Fill the fields and your estimate will be visible to the homeowner under "Sent to Contractors".</div>
                </div>
                <div>
                  <label style={{fontWeight:600}}>Attachments (optional)</label>
                  <input name="attachments" type="file" multiple />
                </div>
                <div className="actions">
                  <button className="btn btn-secondary" type="button" onClick={(e)=>{ 
                    const form = e.currentTarget.closest('form'); 
                    if (form) {
                      form.reset(); 
                      recalcTotalsFromForm(form);
                      // Clear draft data
                      const sendId = item?.id;
                      if (sendId) {
                        setDraftData(prev => ({ ...prev, [sendId]: null }));
                        setLastSaved(prev => ({ ...prev, [sendId]: null }));
                      }
                    }
                  }}>🔄 Reset</button>
                  {draftData[item?.id] && (
                    <button className="btn btn-secondary" type="button" onClick={async () => {
                      const sendId = item?.id;
                      if (sendId && confirm('Are you sure you want to clear the saved draft?')) {
                        try {
                          // Clear from backend
                          const me = JSON.parse(sessionStorage.getItem('user') || '{}');
                          await fetch('/buildhub/backend/api/contractor/save_estimate_draft.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'include',
                            body: JSON.stringify({
                              contractor_id: me.id,
                              send_id: sendId,
                              draft_data: {}
                            })
                          });
                          
                          // Clear from local state
                          setDraftData(prev => ({ ...prev, [sendId]: null }));
                          setLastSaved(prev => ({ ...prev, [sendId]: null }));
                          
                          // Reset form
                          if (estimateFormRef.current) {
                            estimateFormRef.current.reset();
                            recalcTotalsFromForm(estimateFormRef.current);
                          }
                          
                          toast.success('Draft cleared successfully');
                        } catch (error) {
                          toast.error('Error clearing draft');
                        }
                      }
                    }}>🗑️ Clear Draft</button>
                  )}
                  <button className="btn btn-primary" type="button" onClick={async (e)=>{ const form = e.currentTarget.closest('form'); await buildEstimateReport(form); }}>📄 Download PDF Report</button>
                  <button className="btn btn-primary" type="submit">✅ Submit Estimate</button>
                </div>

                {/* Predictive option sources (datalists) */}
                <datalist id="bh_project_names">
                  <option value="Residential Villa" />
                  <option value="Commercial Complex" />
                  <option value="Renovation Project" />
                </datalist>
                <datalist id="bh_cement">
                  <option value="OPC 43 grade - 50 bags" />
                  <option value="OPC 53 grade - 60 bags" />
                  <option value="PPC - 55 bags" />
                </datalist>
                <datalist id="bh_sand">
                  <option value="River sand - 5 m³" />
                  <option value="M-sand - 6 m³" />
                </datalist>
                <datalist id="bh_bricks">
                  <option value="Clay bricks - 5000 nos" />
                  <option value="AAC blocks - 3000 nos" />
                </datalist>
                <datalist id="bh_steel">
                  <option value="TMT 8/10/12mm - 1500 kg" />
                  <option value="TMT 16/20mm - 1000 kg" />
                </datalist>
                <datalist id="bh_aggregate">
                  <option value="20mm aggregate - 8 m³" />
                  <option value="10mm aggregate - 6 m³" />
                </datalist>
                <datalist id="bh_tiles">
                  <option value="Vitrified tiles - 120 m²" />
                  <option value="Ceramic tiles - 90 m²" />
                </datalist>
                <datalist id="bh_paint">
                  <option value="Interior emulsion - 80 L" />
                  <option value="Exterior emulsion - 60 L" />
                </datalist>
                <datalist id="bh_doors">
                  <option value="Teakwood doors - 10 nos" />
                  <option value="Flush doors - 8 nos" />
                </datalist>
                <datalist id="bh_windows">
                  <option value="uPVC windows - 12 nos" />
                  <option value="Aluminium windows - 10 nos" />
                </datalist>
                <datalist id="bh_labor_mason">
                  <option value="Masonry - ₹/m³" />
                  <option value="Block work - ₹/m²" />
                </datalist>
                <datalist id="bh_labor_plaster">
                  <option value="Internal plaster - ₹/m²" />
                  <option value="External plaster - ₹/m²" />
                </datalist>
                <datalist id="bh_labor_painting">
                  <option value="2-coat interior - ₹/m²" />
                  <option value="3-coat exterior - ₹/m²" />
                </datalist>
                <datalist id="bh_labor_electrical">
                  <option value="Per point - ₹/pt" />
                  <option value="Per room - ₹/room" />
                </datalist>
                <datalist id="bh_labor_plumbing">
                  <option value="Per fitting - ₹/fit" />
                  <option value="Per bathroom - ₹/bath" />
                </datalist>
                <datalist id="bh_labor_flooring">
                  <option value="Flooring install - ₹/m²" />
                  <option value="Skirting - ₹/m" />
                </datalist>
                <datalist id="bh_labor_roofing">
                  <option value="Roof sheet install - ₹/m²" />
                  <option value="Ceiling grid - ₹/m²" />
                </datalist>
                <datalist id="bh_util_sanitary">
                  <option value="WC, basin, shower set" />
                  <option value="Premium sanitary set" />
                </datalist>
                <datalist id="bh_util_kitchen">
                  <option value="Modular kitchen - 12ft" />
                  <option value="Modular kitchen - 15ft" />
                </datalist>
                <datalist id="bh_util_electrical_fixtures">
                  <option value="LED panels, fans, switches" />
                  <option value="Designer lights set" />
                </datalist>
                <datalist id="bh_util_watertank">
                  <option value="Overhead tank 1000L + pump" />
                  <option value="Overhead tank 2000L + pump" />
                </datalist>
                <datalist id="bh_util_hvac">
                  <option value="Split AC - 1.5T x 2" />
                  <option value="Inverter AC - 1T x 3" />
                </datalist>
                <datalist id="bh_util_gaswater">
                  <option value="Gas line + kitchen water line" />
                  <option value="Full house water lines" />
                </datalist>
                <datalist id="bh_misc_transport">
                  <option value="Material transport local" />
                  <option value="Material transport long-haul" />
                </datalist>
                <datalist id="bh_misc_contingency">
                  <option value="5% buffer" />
                  <option value="10% buffer" />
                </datalist>
                <datalist id="bh_misc_fees">
                  <option value="Permit & registration" />
                  <option value="Municipal fees" />
                </datalist>
                <datalist id="bh_misc_cleaning">
                  <option value="Debris removal" />
                  <option value="Final cleaning" />
                </datalist>
                <datalist id="bh_misc_safety">
                  <option value="PPE & scaffolding" />
                  <option value="Safety nets & signage" />
                </datalist>
              </form>
              </details>
              </div>
            ) : (
              <div className="acknowledgment-required">
                <div style={{display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: '8px'}}>
                  <span className="icon">⏳</span>
                  <strong className="title">Acknowledgment Required</strong>
                </div>
                <p className="message">
                  Please acknowledge this request first before submitting an estimate. 
                  Use the "Acknowledge" button above to confirm receipt and set your completion timeline.
                </p>
              </div>
            )}
          </div>
        </div>
      </div>
    );
  };

  const renderAvailableProjects = () => {
    return (
    <div>
      <div className="main-header">
          <h1>Construction Projects</h1>
          <p>Approved estimates ready for construction with complete project details</p>
      </div>

      <div className="section-card">
        <div className="section-header">
            <h2>Active Construction Projects</h2>
            <p>Detailed project information including layouts, estimates, and technical specifications</p>
            <div className="section-actions" style={{display: 'flex', gap: '12px', marginTop: '12px'}}>
              <button 
                className="btn btn-primary" 
                onClick={async () => {
                  setLoading(true);
                  try {
                    const me = JSON.parse(sessionStorage.getItem('user') || '{}');
                    const r = await fetch(`/buildhub/backend/api/contractor/get_projects.php?contractor_id=${me.id}`, { credentials: 'include' });
                    const j = await r.json().catch(() => ({}));
                    if (j?.success) setConstructionDetails(Array.isArray(j.data.projects) ? j.data.projects : []);
                  } catch {}
                  setLoading(false);
                }}
                disabled={loading}
              >
                {loading ? '🔄 Refreshing...' : '🔄 Refresh Projects'}
              </button>
              <button 
                className="btn btn-secondary" 
                onClick={() => setActiveTab('proposals')}
              >
                📋 View All Estimates
              </button>
              <button 
                className="btn btn-secondary" 
                onClick={() => setActiveTab('inbox')}
              >
                📥 Check Inbox
              </button>
            </div>
        </div>
        <div className="section-content">
          {loading ? (
              <div className="loading">Loading construction projects...</div>
            ) : constructionDetails.length === 0 ? (
            <div className="empty-state">
                <div className="empty-icon">🏗️</div>
                <h3>No Construction Projects Yet</h3>
                <p>When homeowners approve your estimates and start construction, they will appear here with complete project details!</p>
                <div style={{marginTop: '24px', display: 'flex', flexDirection: 'column', gap: '12px', alignItems: 'center'}}>
                  <div style={{display: 'flex', gap: '12px', flexWrap: 'wrap', justifyContent: 'center'}}>
                    <button 
                      className="btn btn-primary" 
                      onClick={() => setActiveTab('projects')}
                      style={{display: 'flex', alignItems: 'center', gap: '6px'}}
                    >
                      🔍 Browse Available Projects
                    </button>
                    <button 
                      className="btn btn-secondary" 
                      onClick={() => setActiveTab('proposals')}
                      style={{display: 'flex', alignItems: 'center', gap: '6px'}}
                    >
                      📝 View My Estimates
                    </button>
                    <button 
                      className="btn btn-secondary" 
                      onClick={() => setActiveTab('inbox')}
                      style={{display: 'flex', alignItems: 'center', gap: '6px'}}
                    >
                      📥 Check Messages
                    </button>
                  </div>
                  <div style={{fontSize: '14px', color: '#6b7280', textAlign: 'center', maxWidth: '400px'}}>
                    <strong>💡 Tip:</strong> Submit detailed estimates for available projects to increase your chances of getting approved for construction work!
                  </div>
                </div>
            </div>
          ) : (
            <div className="construction-projects">
                {constructionDetails.map(project => (
                  <div key={project.id} className="construction-project-card">
                    <div className="project-header">
                      <div className="project-icon">
                        <div style={{
                          width: 48,
                          height: 48,
                          borderRadius: 8,
                          background: 'linear-gradient(135deg, #10b981, #059669)',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                          fontSize: '20px',
                          color: 'white'
                        }}>
                          🏗️
                        </div>
                      </div>
                      <div className="project-title-section">
                        <h3 className="project-title">
                          {project.project_name}
                        </h3>
                        <p className="project-subtitle">
                          {project.project_description}
                        </p>
                        <div className="project-meta">
                          <span className="meta-item">Total: {project.technical_summary.total_cost}</span>
                          <span className="meta-item">Timeline: {project.timeline || 'TBD'}</span>
                          <span className="meta-item">Progress: {project.project_summary.progress}</span>
                          <span className="meta-item">Status: {project.project_summary.status}</span>
                        </div>
                      </div>
                      <div className="project-actions" style={{display: 'flex', gap: '8px', flexWrap: 'wrap'}}>
                        <button 
                          className="btn btn-primary"
                          onClick={() => setExpandedProject(expandedProject === project.id ? null : project.id)}
                        >
                          {expandedProject === project.id ? 'Hide Details' : 'View Details'}
                        </button>
                        <button 
                          className="btn btn-secondary"
                          onClick={() => {
                            const subject = `Construction Project Update - ${project.project_name}`;
                            const body = `Hi ${project.homeowner_name},\n\nI wanted to provide you with an update on your construction project.\n\nProject: ${project.project_name}\nTotal Cost: ${project.technical_summary.total_cost}\nTimeline: ${project.timeline || 'TBD'}\nCurrent Progress: ${project.project_summary.progress}\nCurrent Stage: ${project.project_summary.current_stage}\n\nPlease let me know if you have any questions.\n\nBest regards,\n[Your Name]`;
                            window.open(`mailto:${project.homeowner_email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`);
                          }}
                          title="Send email to homeowner"
                        >
                          📧 Contact Homeowner
                        </button>
                        <button 
                          className="btn btn-secondary"
                          onClick={() => {
                            const message = `Project: ${project.project_name}\nHomeowner: ${project.homeowner_name}\nEmail: ${project.homeowner_email}\nPhone: ${project.homeowner_phone || 'Not provided'}\nTotal Cost: ${project.technical_summary.total_cost}\nTimeline: ${project.timeline || 'TBD'}\nProgress: ${project.project_summary.progress}\nStatus: ${project.project_summary.status}`;
                            navigator.clipboard.writeText(message).then(() => {
                              try { toast.success('Project details copied to clipboard!'); } catch {}
                            }).catch(() => {
                              try { toast.error('Failed to copy details'); } catch {}
                            });
                          }}
                          title="Copy project details"
                        >
                          📋 Copy Details
                        </button>
                      </div>
                    </div>

                    {expandedProject === project.id && (
                      <div className="project-details">
                        <div className="details-grid">
                          {/* Homeowner Information */}
                          <div className="detail-section">
                            <h4 className="section-title">👤 Homeowner Information</h4>
                            <div className="detail-content">
                              <p><strong>Name:</strong> {project.homeowner_name}</p>
                              <p><strong>Email:</strong> {project.homeowner_email}</p>
                              <p><strong>Phone:</strong> {project.homeowner_phone || 'Not provided'}</p>
                              <p><strong>Location:</strong> {project.project_location || 'Not provided'}</p>
                            </div>
                          </div>

                          {/* Project Requirements */}
                          <div className="detail-section">
                            <h4 className="section-title">🏠 Project Requirements</h4>
                            <div className="detail-content">
                              {project.plot_size && <p><strong>Plot Size:</strong> {project.plot_size}</p>}
                              {project.budget_range && <p><strong>Budget Range:</strong> {project.budget_range}</p>}
                              {project.project_location && <p><strong>Location:</strong> {project.project_location}</p>}
                              {project.preferred_style && <p><strong>Preferred Style:</strong> {project.preferred_style}</p>}
                              {project.requirements && <p><strong>Requirements:</strong> {project.requirements}</p>}
                              {project.timeline && <p><strong>Timeline:</strong> {project.timeline}</p>}
                            </div>
                          </div>

                          {/* Project Cost Details */}
                          <div className="detail-section">
                            <h4 className="section-title">💰 Cost Breakdown</h4>
                            <div className="detail-content">
                              <p><strong>Total Cost:</strong> {project.technical_summary.total_cost}</p>
                              <p><strong>Materials Cost:</strong> {project.technical_summary.materials_cost}</p>
                              <p><strong>Labor Cost:</strong> {project.technical_summary.labor_cost}</p>
                              <p><strong>Timeline:</strong> {project.timeline || 'TBD'}</p>
                              <p><strong>Status:</strong> {project.project_summary.status}</p>
                              <p><strong>Created Date:</strong> {project.created_date_formatted}</p>
                              {project.materials && <p><strong>Materials:</strong> {project.materials}</p>}
                              {project.contractor_notes && <p><strong>Notes:</strong> {project.contractor_notes}</p>}
                            </div>
                          </div>

                          {/* Layout Information */}
                          {project.layout_data && (
                            <div className="detail-section">
                              <h4 className="section-title">📐 Layout & Technical Details</h4>
                              <div className="detail-content">
                                {project.layout_data.layout_image && (
                                  <div style={{marginBottom: '12px'}}>
                                    <p><strong>Layout Image:</strong></p>
                                    <img 
                                      src={`/buildhub/backend/api/contractor/serve_layout_image.php?contractor_id=${user?.id}&image=${project.layout_data.layout_image}`}
                                      alt="Layout"
                                      style={{maxWidth: '300px', maxHeight: '200px', border: '1px solid #ddd', borderRadius: '4px'}}
                                      onError={(e) => {
                                        e.target.style.display = 'none';
                                      }}
                                    />
                                  </div>
                                )}
                                {project.layout_data.layout_file && <p><strong>Layout File:</strong> {project.layout_data.layout_file}</p>}
                                {project.layout_data.technical_details && <p><strong>Technical Details:</strong> {project.layout_data.technical_details}</p>}
                              </div>
                            </div>
                          )}

                          {/* Progress Information */}
                          <div className="detail-section">
                            <h4 className="section-title">📊 Progress Status</h4>
                            <div className="detail-content">
                              <p><strong>Current Progress:</strong> {project.project_summary.progress}</p>
                              <p><strong>Current Stage:</strong> {project.project_summary.current_stage}</p>
                              <p><strong>Total Updates:</strong> {project.project_summary.updates_count}</p>
                              <p><strong>Last Activity:</strong> {project.project_summary.last_activity}</p>
                              <p><strong>Expected Completion:</strong> {project.expected_completion_formatted}</p>
                            </div>
                          </div>

                          {/* Construction Management */}
                          <div className="detail-section construction-start">
                            <h4 className="section-title">🚀 Construction Management</h4>
                            <div className="detail-content">
                              <div className="construction-checklist">
                                <h5>Project Status:</h5>
                                <ul>
                                  <li>✅ Estimate approved by homeowner</li>
                                  <li>✅ Project created automatically</li>
                                  <li>✅ Technical details available</li>
                                  <li>✅ Layout plans included</li>
                                  <li>✅ Ready for construction start</li>
                                </ul>
                              </div>
                              <div className="next-steps">
                                <h5>Next Steps:</h5>
                                <ol>
                                  <li>Contact homeowner to schedule site visit</li>
                                  <li>Finalize construction timeline</li>
                                  <li>Arrange material delivery</li>
                                  <li>Set up construction site</li>
                                  <li>Begin construction work</li>
                                  <li>Submit regular progress updates</li>
                                </ol>
                              </div>
                              <div className="construction-actions" style={{marginTop: '20px', padding: '16px', backgroundColor: '#f8fafc', borderRadius: '8px', border: '1px solid #e2e8f0'}}>
                                <h5 style={{margin: '0 0 12px 0', color: '#374151'}}>🚀 Project Management Actions</h5>
                                <div style={{display: 'flex', gap: '12px', flexWrap: 'wrap'}}>
                                  <button 
                                    className="btn btn-success"
                                    onClick={() => {
                                      const subject = `Construction Start Confirmation - ${project.project_name}`;
                                      const body = `Hi ${project.homeowner_name},\n\nI'm excited to confirm that we're ready to begin construction on your project!\n\nProject: ${project.project_name}\nTotal Cost: ${project.technical_summary.total_cost}\nTimeline: ${project.timeline || 'TBD'}\nCurrent Progress: ${project.project_summary.progress}\n\nI'll be in touch soon to schedule the site visit and discuss the next steps.\n\nBest regards,\n[Your Name]`;
                                      window.open(`mailto:${project.homeowner_email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`);
                                    }}
                                  >
                                    📧 Confirm Start with Homeowner
                                  </button>
                                  <button 
                                    className="btn btn-primary"
                                    onClick={() => setActiveTab('progress')}
                                  >
                                    📊 Submit Progress Update
                                  </button>
                                  <button 
                                    className="btn btn-secondary"
                                    onClick={() => {
                                      const message = `Construction Project Details\n\nProject: ${project.project_name}\nHomeowner: ${project.homeowner_name}\nStart Date: ${project.start_date_formatted}\nTimeline: ${project.timeline || 'TBD'}\nTotal Cost: ${project.technical_summary.total_cost}\nProgress: ${project.project_summary.progress}\nStatus: ${project.project_summary.status}`;
                                      navigator.clipboard.writeText(message).then(() => {
                                        try { toast.success('Project details copied!'); } catch {}
                                      }).catch(() => {
                                        try { toast.error('Failed to copy details'); } catch {}
                                      });
                                    }}
                                  >
                                    📋 Copy Project Details
                                  </button>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
  };

  const renderMyProposals = () => (
    <div>
      <div className="main-header">
        <h1>My Estimates</h1>
        <p>Track your submitted cost estimates and their status</p>
      </div>

      <div className="section-card">
        <div className="section-header">
          <h2>Submitted Estimates</h2>
          <p>Monitor the status of your cost estimates</p>
        </div>
        <div className="section-content">
          {myProposals.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">📝</div>
              <h3>No Estimates Yet</h3>
              <p>Submit estimates for available projects to get started!</p>
            </div>
          ) : (
            <div className="item-list">
              {myProposals.map(proposal => (
                <ProposalItem key={proposal.id} proposal={proposal} />
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const renderProfile = () => (
    <div>
      <div className="main-header">
        <h1>Profile</h1>
        <p>Manage your account information and settings</p>
      </div>

      <div className="profile-grid">
        <div className="profile-card">
          <div className="profile-avatar-large">
            {user?.first_name?.charAt(0)}{user?.last_name?.charAt(0)}
          </div>
          <div className="profile-info">
            <h3>{user?.first_name} {user?.last_name}</h3>
            <p className="profile-role">Contractor</p>
            <p className="profile-email">{user?.email}</p>
          </div>
        </div>

        <div className="profile-card">
          <div className="section-header">
            <h2>Account Information</h2>
            <p>Your personal and professional details</p>
          </div>
          <div className="detail-grid">
            <div className="detail-item">
              <label>Full Name</label>
              <p>{user?.first_name} {user?.last_name}</p>
            </div>
            <div className="detail-item">
              <label>Email Address</label>
              <p>{user?.email}</p>
            </div>
            <div className="detail-item">
              <label>Role</label>
              <p>Contractor</p>
            </div>
            <div className="detail-item">
              <label>Account Status</label>
              <p className="status-badge accepted">Active</p>
            </div>
            <div className="detail-item">
              <label>Total Projects</label>
              <p>{myProposals.filter(p => p.status === 'accepted').length}</p>
            </div>
            <div className="detail-item">
              <label>Success Rate</label>
              <p>{myProposals.length > 0 ? Math.round((myProposals.filter(p => p.status === 'accepted').length / myProposals.length) * 100) : 0}%</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  const renderProgressUpdates = () => {
    const handleUpdateSubmitted = (updateData) => {
      toast.success('Progress update submitted successfully!');
      
      // Trigger progress recalculation for homeowner dashboard
      // This could be enhanced with WebSocket or real-time updates in the future
      if (updateData && updateData.project_id) {
        // Store the update timestamp for potential real-time sync
        localStorage.setItem(`progress_update_${updateData.project_id}`, Date.now().toString());
      }
      
      // Optionally switch to timeline view to show the new update
      setProgressView('timeline');
    };

    return (
      <div>
        <div className="main-header">
          <h1>Construction Progress Updates</h1>
          <p>Submit progress updates, track construction timeline, and generate audit reports</p>
        </div>

        {/* Progress View Toggle */}
        <div className="progress-view-toggle" style={{ marginBottom: '20px' }}>
          <button 
            className={`toggle-btn ${progressView === 'submit' ? 'active' : ''}`}
            onClick={() => setProgressView('submit')}
          >
            📝 Submit Update
          </button>
          <button 
            className={`toggle-btn ${progressView === 'timeline' ? 'active' : ''}`}
            onClick={() => setProgressView('timeline')}
          >
            📊 View Timeline
          </button>
          <button 
            className={`toggle-btn ${progressView === 'payment' ? 'active' : ''}`}
            onClick={() => setProgressView('payment')}
          >
            💰 Stage Payment
          </button>
          <button 
            className={`toggle-btn ${progressView === 'custom-payment' ? 'active' : ''}`}
            onClick={() => setProgressView('custom-payment')}
          >
            💳 Custom Payment
          </button>
          <button 
            className={`toggle-btn ${progressView === 'history' ? 'active' : ''}`}
            onClick={() => setProgressView('history')}
          >
            📋 Payment History
          </button>
          <button 
            className={`toggle-btn ${progressView === 'reports' ? 'active' : ''}`}
            onClick={() => setProgressView('reports')}
          >
            📊 Generate Reports
          </button>
        </div>

        {/* Content based on selected view */}
        {progressView === 'submit' ? (
          <EnhancedProgressUpdate 
            contractorId={user?.id}
            onUpdateSubmitted={handleUpdateSubmitted}
          />
        ) : progressView === 'timeline' ? (
          <ContractorConstructionTimeline 
            contractorId={user?.id}
          />
        ) : progressView === 'payment' ? (
          <div className="payment-section">
            <SimplePaymentRequestForm 
              contractorId={user?.id}
              onPaymentRequested={(data) => {
                toast.success(`Stage payment request submitted successfully!`);
              }}
            />
          </div>
        ) : progressView === 'custom-payment' ? (
          <div className="custom-payment-section">
            <CustomPaymentRequestForm 
              contractorId={user?.id}
              onPaymentRequested={(data) => {
                toast.success(`Custom payment request submitted successfully!`);
              }}
            />
          </div>
        ) : progressView === 'history' ? (
          <div className="payment-history-section">
            <PaymentHistory 
              contractorId={user?.id}
            />
          </div>
        ) : progressView === 'reports' ? (
          <div className="reports-section">
            <div className="reports-header">
              <h2>📊 Progress Report Generator</h2>
              <p>Generate comprehensive audit reports for daily, weekly, and monthly progress tracking</p>
            </div>
            
            <div className="reports-actions">
              <button 
                className="btn btn-primary"
                onClick={() => setShowReportGenerator(true)}
                style={{ 
                  background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                  color: 'white',
                  border: 'none',
                  padding: '12px 24px',
                  borderRadius: '8px',
                  fontSize: '16px',
                  fontWeight: '600',
                  cursor: 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  gap: '8px',
                  boxShadow: '0 4px 12px rgba(102, 126, 234, 0.3)',
                  transition: 'all 0.3s ease'
                }}
                onMouseOver={(e) => {
                  e.target.style.transform = 'translateY(-2px)';
                  e.target.style.boxShadow = '0 6px 16px rgba(102, 126, 234, 0.4)';
                }}
                onMouseOut={(e) => {
                  e.target.style.transform = 'translateY(0)';
                  e.target.style.boxShadow = '0 4px 12px rgba(102, 126, 234, 0.3)';
                }}
              >
                📊 Generate New Report
              </button>
            </div>

            <div className="reports-info" style={{ 
              marginTop: '30px',
              padding: '20px',
              background: 'linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%)',
              borderRadius: '12px',
              border: '1px solid #dee2e6'
            }}>
              <h3 style={{ margin: '0 0 15px 0', color: '#2c3e50' }}>📋 Available Report Types</h3>
              
              <div style={{ 
                display: 'grid', 
                gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', 
                gap: '20px',
                marginTop: '15px'
              }}>
                <div style={{ 
                  padding: '15px', 
                  background: 'white', 
                  borderRadius: '8px',
                  border: '1px solid #e9ecef',
                  boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                }}>
                  <h4 style={{ margin: '0 0 10px 0', color: '#28a745' }}>📅 Daily Reports</h4>
                  <p style={{ margin: '0', fontSize: '14px', color: '#6c757d', lineHeight: '1.5' }}>
                    Detailed daily progress with work done, labour tracking, materials used, photos, and quality metrics.
                  </p>
                </div>
                
                <div style={{ 
                  padding: '15px', 
                  background: 'white', 
                  borderRadius: '8px',
                  border: '1px solid #e9ecef',
                  boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                }}>
                  <h4 style={{ margin: '0 0 10px 0', color: '#007bff' }}>📊 Weekly Summaries</h4>
                  <p style={{ margin: '0', fontSize: '14px', color: '#6c757d', lineHeight: '1.5' }}>
                    Weekly progress summaries with stage completion, delays analysis, and productivity insights.
                  </p>
                </div>
                
                <div style={{ 
                  padding: '15px', 
                  background: 'white', 
                  borderRadius: '8px',
                  border: '1px solid #e9ecef',
                  boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                }}>
                  <h4 style={{ margin: '0 0 10px 0', color: '#6f42c1' }}>📈 Monthly Reports</h4>
                  <p style={{ margin: '0', fontSize: '14px', color: '#6c757d', lineHeight: '1.5' }}>
                    Comprehensive monthly reports with milestone tracking, cost analysis, and project forecasting.
                  </p>
                </div>
              </div>

              <div style={{ 
                marginTop: '20px', 
                padding: '15px', 
                background: '#e3f2fd', 
                borderRadius: '8px',
                border: '1px solid #bbdefb'
              }}>
                <h4 style={{ margin: '0 0 10px 0', color: '#1976d2' }}>✨ Report Features</h4>
                <ul style={{ margin: '0', paddingLeft: '20px', fontSize: '14px', color: '#424242' }}>
                  <li>Professional PDF generation with company branding</li>
                  <li>Automatic homeowner notifications and delivery</li>
                  <li>Comprehensive labour and cost analysis</li>
                  <li>GPS-verified photo documentation</li>
                  <li>Quality and safety compliance tracking</li>
                  <li>Progress recommendations and insights</li>
                </ul>
              </div>
            </div>
          </div>
        ) : null}

        {/* Report Generator Modal */}
        {showReportGenerator && (
          <ProgressReportGenerator
            contractorId={user?.id}
            onClose={() => setShowReportGenerator(false)}
          />
        )}
      </div>
    );
  };

  return (
    <div className="dashboard-container">
      {/* Sidebar */}
      <div className={`dashboard-sidebar soft-sidebar expanded`}>
        <div className="sidebar-header sb-brand">

          <a href="#" className="sidebar-logo">
            <div className="logo-icon">🏠</div>
            <span className="logo-text sb-title">BUILDHUB</span>
          </a>
        </div>

        <nav className="sidebar-nav sb-nav">
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'overview' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('overview'); }}
            title="Dashboard"
          >
            <span className="nav-label sb-label">Dashboard</span>
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'projects' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('projects'); }}
            title="Construction"
          >
            <span className="nav-label sb-label">Construction</span>
            {requestsCount > 0 && (<span className="nav-badge pulse" style={{ marginLeft:'auto' }}>{requestsCount}</span>)}
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'proposals' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('proposals'); }}
            title="My Estimates"
          >
            <span className="nav-label sb-label">My Estimates</span>
            {myEstimates.length > 0 && (<span className="nav-badge pulse" style={{ marginLeft:'auto' }}>{myEstimates.length}</span>)}
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'inbox' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('inbox'); }}
            title="Inbox"
          >
            <span className="nav-label sb-label">Inbox</span>
            {inboxCount > 0 && (<span className="nav-badge pulse" style={{ marginLeft:'auto' }}>{inboxCount}</span>)}
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'progress' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('progress'); }}
            title="Progress Updates"
          >
            <span className="nav-label sb-label">Progress Updates</span>
          </a>
        
        </nav>

              </div>

      {/* Main Content */}
      <div className="dashboard-main blue-glass soft-main shifted">
        {/* Top Glass Header */}
        <div className="top-glassbar">
          <div className="left">
            <div className="search">
              <span className="icon">🔎</span>
              <input type="text" placeholder="Search projects, estimates, requests..." aria-label="Search" />
                </div>
                </div>
          <div className="right">
            <button className="icon-btn" title="Help">❓</button>
            <ContractorProfileButton 
              user={user}
              position="bottom-right"
              onProfileClick={() => setActiveTab('profile')}
              onLogout={handleLogout}
            />
        </div>
      </div>

        {error && (
          <div className="alert alert-error">
            {error}
          </div>
        )}
        
        {success && (
          <div className="alert alert-success">
            {success}
          </div>
        )}

        {activeTab === 'overview' && renderOverview()}
        {activeTab === 'projects' && renderAvailableProjects()}
        {activeTab === 'proposals' && renderMyEstimates()}
        {activeTab === 'inbox' && renderInbox()}
        {activeTab === 'progress' && renderProgressUpdates()}
        {activeTab === 'profile' && renderProfile()}
      </div>

      {/* Confirmation Modal */}
      <ConfirmModal
        isOpen={confirmModal.isOpen}
        onClose={closeConfirmation}
        onConfirm={confirmModal.onConfirm}
        title={confirmModal.title}
        message={confirmModal.message}
        type={confirmModal.type}
      />

      {/* Estimation Form Modal */}
      <EstimationForm
        isOpen={showEstimationForm}
        onClose={closeEstimationForm}
        inboxItem={currentEstimationItem}
        onSubmit={handleEstimateSubmit}
      />
    </div>
  );
};

// Project Item Component
const ProjectItem = ({ request, onProposalSubmit }) => {
  const [showProposalForm, setShowProposalForm] = useState(false);
  const [proposalData, setProposalData] = useState({
    materials: '',
    cost_breakdown: '',
    total_cost: '',
    timeline: '',
    notes: ''
  });
  const [submitting, setSubmitting] = useState(false);

  const handleSubmitProposal = async (e) => {
    e.preventDefault();
    setSubmitting(true);

    try {
      const response = await fetch('/buildhub/backend/api/contractor/submit_proposal.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          layout_request_id: request.id,
          ...proposalData
        })
      });

      const result = await response.json();
      if (result.success) {
        setShowProposalForm(false);
        setProposalData({
          materials: '',
          cost_breakdown: '',
          total_cost: '',
          timeline: '',
          notes: ''
        });
        onProposalSubmit();
        // Notify success via toast if available
        if (window.dispatchEvent) {
          window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Proposal submitted successfully!' } }));
        }
      } else {
        if (window.dispatchEvent) {
          window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Failed to submit proposal: ' + (result.message || '') } }));
          }
      }
    } catch (error) {
      if (window.dispatchEvent) {
        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Error submitting proposal' } }));
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <>
      <div className="list-item">
        <div className="item-image">🏠</div>
        <div className="item-content">
          <h4 className="item-title">{request.homeowner_name}</h4>
          <p className="item-subtitle">{request.requirements || 'Modern Home Project'}</p>
          <p className="item-meta">{request.plot_size} • Budget: ₹{request.budget_range} • {new Date(request.created_at).toLocaleDateString()}</p>
        </div>
        <div className="item-actions">
          <span className={`status-badge ${badgeClass(request.status)}`}>{formatStatus(request.status)}</span>
          <button 
            onClick={() => setShowProposalForm(true)}
            className="btn btn-primary"
          >
            Submit Estimate
          </button>
        </div>
      </div>

      {showProposalForm && (
        <div className="form-modal">
          <div className="form-content">
            <div className="form-header">
              <h3>Submit Cost Estimate</h3>
              <p>Provide detailed cost breakdown for {request.homeowner_name}'s project</p>
            </div>
            
            <div style={{ display: 'flex', justifyContent: 'center', marginBottom: '20px', padding: '20px 0' }}>
              <BuildHubSeal size="medium" />
            </div>
            
            <form onSubmit={handleSubmitProposal}>
              <div className="form-group">
                <label>Materials List *</label>
                <textarea
                  value={proposalData.materials}
                  onChange={(e) => setProposalData({...proposalData, materials: e.target.value})}
                  placeholder="List all materials needed (cement, steel, bricks, etc.)"
                  rows="4"
                  required
                />
              </div>

              <div className="form-group">
                <label>Cost Breakdown *</label>
                <textarea
                  value={proposalData.cost_breakdown}
                  onChange={(e) => setProposalData({...proposalData, cost_breakdown: e.target.value})}
                  placeholder="Detailed cost breakdown for materials and labor"
                  rows="4"
                  required
                />
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Total Cost (₹) *</label>
                  <input
                    type="number"
                    value={proposalData.total_cost}
                    onChange={(e) => setProposalData({...proposalData, total_cost: e.target.value})}
                    placeholder="Total project cost"
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Timeline *</label>
                  <input
                    type="text"
                    value={proposalData.timeline}
                    onChange={(e) => setProposalData({...proposalData, timeline: e.target.value})}
                    placeholder="e.g., 3-4 months"
                    required
                  />
                </div>
              </div>

              <div className="form-group">
                <label>Additional Notes</label>
                <textarea
                  value={proposalData.notes}
                  onChange={(e) => setProposalData({...proposalData, notes: e.target.value})}
                  placeholder="Any additional information or terms"
                  rows="3"
                />
              </div>

              <div className="form-actions">
                <button 
                  type="button" 
                  onClick={() => setShowProposalForm(false)}
                  className="btn btn-secondary"
                >
                  Cancel
                </button>
                <button 
                  type="submit" 
                  disabled={submitting}
                  className="btn btn-primary"
                >
                  {submitting ? 'Submitting...' : 'Submit Estimate'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  );
};

// Proposal Item Component
const ProposalItem = ({ proposal }) => {
  const hasMessage = proposal.homeowner_message && proposal.homeowner_message.trim();
  
  return (
    <div className="list-item" style={{
      borderLeft: hasMessage && proposal.status === 'accepted' ? '4px solid #10b981' : undefined,
      background: hasMessage && proposal.status === 'accepted' ? 'linear-gradient(to right, #ffffff 0%, #f0fdf4 5%)' : undefined
    }}>
      <div className="item-image">
        {hasMessage && proposal.status === 'accepted' ? '✅' : '📄'}
      </div>
      <div className="item-content" style={{ flex: 1 }}>
        <h4 className="item-title">
          {hasMessage && proposal.status === 'accepted' ? '✓ Accepted' : ''} Estimate for {proposal.homeowner_name}
        </h4>
        <p className="item-subtitle">Total Cost: ₹{proposal.total_cost} • Timeline: {proposal.timeline}</p>
        <p className="item-meta">Submitted: {new Date(proposal.created_at).toLocaleDateString()}</p>
        
        {/* Homeowner Message Display */}
        {hasMessage && proposal.status === 'accepted' && (
          <div style={{
            marginTop: '12px',
            padding: '12px',
            background: 'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%)',
            borderRadius: '8px',
            border: '1px solid #10b981'
          }}>
            <div style={{ display: 'flex', alignItems: 'center', marginBottom: '8px' }}>
              <span style={{ fontSize: '18px', marginRight: '8px' }}>💬</span>
              <strong style={{ color: '#065f46', fontSize: '14px' }}>Message from Homeowner:</strong>
            </div>
            <div style={{
              color: '#047857',
              fontSize: '13px',
              lineHeight: '1.6',
              whiteSpace: 'pre-wrap',
              paddingLeft: '26px'
            }}>
              {proposal.homeowner_message}
            </div>
          </div>
        )}
      </div>
      <div className="item-actions">
        <span className={`status-badge ${badgeClass(proposal.status)}`}>
          {formatStatus(proposal.status)}
        </span>
      </div>
    </div>
  );
};

const EstimateListItem = ({ est, user, showConfirmation, toast }) => {
  let structured = null;
  try { 
    // Try structured_data first (from API), then fallback to structured
    const structuredStr = est?.structured_data || est?.structured;
    structured = structuredStr ? JSON.parse(structuredStr) : null; 
  } catch {}
  const s = structured || {};
  const money = (v) => {
    const n = parseFloat(v);
    if (Number.isFinite(n)) return `₹${n.toLocaleString()}`;
    const m = String(v || '').match(/[-+]?(?:\d+\.?\d*|\d*\.?\d+)/);
    const x = m ? parseFloat(m[0]) : null;
    return Number.isFinite(x) ? `₹${x.toLocaleString()}` : '—';
  };
  const Section = ({ title, rows }) => (
    <div className="card" style={{ marginTop: 8 }}>
      <h4 style={{ margin: '0 0 6px 0' }}>{title}</h4>
      <table style={{ width:'100%', borderCollapse:'collapse' }}>
        <thead>
          <tr>
            <th style={{ textAlign:'left', borderBottom:'1px solid #eef2f7', padding:'6px 4px' }}>Item</th>
            <th style={{ textAlign:'left', borderBottom:'1px solid #eef2f7', padding:'6px 4px' }} className="muted">Qty</th>
            <th style={{ textAlign:'left', borderBottom:'1px solid #eef2f7', padding:'6px 4px' }} className="muted">Rate</th>
            <th style={{ textAlign:'left', borderBottom:'1px solid #eef2f7', padding:'6px 4px' }}>Amount</th>
          </tr>
        </thead>
        <tbody>
          {rows.filter(r => r && (r.name || r.amount)).map((r, i) => (
            <tr key={i}>
              <td style={{ padding:'6px 4px' }}>{r.name || '—'}</td>
              <td style={{ padding:'6px 4px' }} className="muted">{r.qty || ''}</td>
              <td style={{ padding:'6px 4px' }} className="muted">{r.rate || ''}</td>
              <td style={{ padding:'6px 4px' }}>{money(r.amount)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );

  const rowsFrom = (obj, keys) => {
    if (!obj || typeof obj !== 'object') return [];
    return keys.map(k => ({
      name: obj?.[k]?.name || '',
      qty: obj?.[k]?.qty || '',
      rate: obj?.[k]?.rate || '',
      amount: obj?.[k]?.amount || ''
    }));
  };

  const materialsRows = rowsFrom(s.materials, ['cement','sand','bricks','steel','aggregate','tiles','paint','doors','windows','others']);
  const laborRows = rowsFrom(s.labor, ['mason','plaster','painting','electrical','plumbing','flooring','roofing','others']);
  const utilitiesRows = rowsFrom(s.utilities, ['sanitary','kitchen','electrical_fixtures','water_tank','hvac','gas_water','others1','others2','others3']);
  const miscRows = rowsFrom(s.misc, ['transport','contingency','fees','cleaning','safety','others1','others2','others3']);

  const buildReportHtml = (userData = user) => {
    const contractorName = userData?.first_name && userData?.last_name ? 
      `${userData.first_name} ${userData.last_name}` : 
      'Contractor Name';
    const currentDate = new Date().toLocaleDateString('en-IN', { 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric' 
    });
    
    return `<!doctype html><html><head><meta charset="utf-8"/>
    <title>Professional Cost Estimate Report</title>
    <style>
      @page { 
        margin: 20mm; 
        size: A4;
      }
      body{
        font-family: 'Times New Roman', serif;
        color: #1a1a1a;
        margin: 0;
        padding: 0;
        line-height: 1.4;
        background: white;
      }
      .header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 3px solid #2c3e50;
        padding-bottom: 20px;
      }
      .company-logo {
        width: 120px;
        height: 120px;
        margin: 0 auto 15px;
        border: 2px solid #2c3e50;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: bold;
        color: #2c3e50;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      }
      .company-name {
        font-size: 28px;
        font-weight: bold;
        color: #2c3e50;
        margin: 10px 0 5px 0;
        text-transform: uppercase;
        letter-spacing: 2px;
      }
      .company-tagline {
        font-size: 14px;
        color: #6c757d;
        font-style: italic;
        margin-bottom: 10px;
      }
      .company-details {
        font-size: 12px;
        color: #495057;
        line-height: 1.3;
      }
      .document-title {
        text-align: center;
        margin: 30px 0;
        font-size: 24px;
        font-weight: bold;
        color: #2c3e50;
        text-transform: uppercase;
        letter-spacing: 1px;
      }
      .estimate-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
        padding: 15px;
        background: #f8f9fa;
        border-left: 4px solid #2c3e50;
      }
      .info-section h3 {
        margin: 0 0 10px 0;
        font-size: 16px;
        color: #2c3e50;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 5px;
      }
      .info-section p {
        margin: 5px 0;
        font-size: 14px;
      }
      .cost-breakdown {
        margin: 30px 0;
      }
      .cost-breakdown h2 {
        font-size: 20px;
        color: #2c3e50;
        border-bottom: 2px solid #2c3e50;
        padding-bottom: 10px;
        margin-bottom: 20px;
      }
      .cost-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      }
      .cost-table th {
        background: #2c3e50;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: bold;
        font-size: 14px;
      }
      .cost-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #dee2e6;
        font-size: 14px;
      }
      .cost-table tr:nth-child(even) {
        background: #f8f9fa;
      }
      .cost-table tr:hover {
        background: #e9ecef;
      }
      .total-section {
        margin: 30px 0;
        padding: 20px;
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        border-radius: 8px;
      }
      .total-section h2 {
        margin: 0 0 15px 0;
        font-size: 18px;
        text-align: center;
      }
      .total-row {
        display: flex;
        justify-content: space-between;
        margin: 8px 0;
        font-size: 16px;
      }
      .grand-total {
        border-top: 2px solid white;
        padding-top: 10px;
        margin-top: 15px;
        font-size: 18px;
        font-weight: bold;
      }
      .terms-section {
        margin: 30px 0;
        padding: 20px;
        background: #f8f9fa;
        border-left: 4px solid #28a745;
      }
      .terms-section h3 {
        margin: 0 0 15px 0;
        color: #28a745;
        font-size: 16px;
      }
      .terms-section p {
        margin: 8px 0;
        font-size: 14px;
        line-height: 1.5;
      }
      .signature-section {
        margin-top: 50px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
      }
      .signature-box {
        text-align: center;
        padding: 20px;
        border: 2px solid #2c3e50;
        border-radius: 8px;
        background: #f8f9fa;
      }
      .signature-line {
        border-bottom: 2px solid #2c3e50;
        margin: 40px 0 10px 0;
        height: 2px;
      }
      .signature-label {
        font-size: 14px;
        font-weight: bold;
        color: #2c3e50;
        margin-top: 10px;
      }
      .contractor-seal {
        width: 100px;
        height: 100px;
        border: 3px solid #dc3545;
        border-radius: 50%;
        margin: 0 auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        color: #dc3545;
        background: white;
        text-align: center;
        line-height: 1.2;
      }
      .footer {
        margin-top: 40px;
        text-align: center;
        font-size: 12px;
        color: #6c757d;
        border-top: 1px solid #dee2e6;
        padding-top: 15px;
      }
      @media print {
        body { margin: 0; }
        .header { page-break-inside: avoid; }
        .signature-section { page-break-inside: avoid; }
      }
    </style>
    </head><body>
    <!-- Company Header -->
    <div class="header">
      <div class="company-logo">🏗️</div>
      <div class="company-name">${contractorName} Construction</div>
      <div class="company-tagline">Professional Construction Services</div>
      <div class="company-details">
        📧 Email: ${user?.email || 'contact@company.com'} | 
        📱 Phone: ${user?.phone || '+91-XXXXX-XXXXX'} | 
        🏢 License: ${user?.license_number || 'LIC-XXXXX'}
      </div>
    </div>

    <!-- Document Title -->
    <div class="document-title">Cost Estimate Report</div>

    <!-- Estimate Information -->
    <div class="estimate-info">
      <div class="info-section">
        <h3>Project Details</h3>
        <p><strong>Project Name:</strong> ${s?.project_name||'Construction Project'}</p>
        <p><strong>Location:</strong> ${s?.project_address||'Project Location'}</p>
        <p><strong>Client:</strong> ${est.client_name||'Client Name'}</p>
        <p><strong>Plot Size:</strong> ${s?.plot_size||'—'}</p>
        <p><strong>Built-up Area:</strong> ${s?.built_up_area||'—'}</p>
        <p><strong>Floors:</strong> ${s?.floors||'—'}</p>
      </div>
      <div class="info-section">
        <h3>Estimate Information</h3>
        <p><strong>Estimate Date:</strong> ${currentDate}</p>
        <p><strong>Estimate Valid Until:</strong> ${new Date(Date.now() + 30*24*60*60*1000).toLocaleDateString('en-IN')}</p>
        <p><strong>Project Duration:</strong> ${est.timeline||'90 days'}</p>
        <p><strong>Estimate #:</strong> EST-${est.id}</p>
      </div>
    </div>

    <!-- Cost Breakdown -->
    <div class="cost-breakdown">
      <h2>Detailed Cost Breakdown</h2>
      <table class="cost-table">
        <thead>
          <tr>
            <th>Item Description</th>
            <th>Quantity</th>
            <th>Unit Rate (₹)</th>
            <th>Amount (₹)</th>
          </tr>
        </thead>
        <tbody>
          ${materialsRows.filter(r=>r&&(r.name||r.amount)).map(r => `
      <tr>
        <td>${(r.name||'').toString().replace(/</g,'&lt;')}</td>
              <td>${(r.qty||'').toString().replace(/</g,'&lt;')}</td>
              <td>${(r.rate||'').toString().replace(/</g,'&lt;')}</td>
        <td>${money(r.amount)}</td>
      </tr>
          `).join('')}
          ${laborRows.filter(r=>r&&(r.name||r.amount)).map(r => `
            <tr>
              <td>${(r.name||'').toString().replace(/</g,'&lt;')}</td>
              <td>${(r.qty||'').toString().replace(/</g,'&lt;')}</td>
              <td>${(r.rate||'').toString().replace(/</g,'&lt;')}</td>
              <td>${money(r.amount)}</td>
            </tr>
          `).join('')}
          ${utilitiesRows.filter(r=>r&&(r.name||r.amount)).map(r => `
            <tr>
              <td>${(r.name||'').toString().replace(/</g,'&lt;')}</td>
              <td>${(r.qty||'').toString().replace(/</g,'&lt;')}</td>
              <td>${(r.rate||'').toString().replace(/</g,'&lt;')}</td>
              <td>${money(r.amount)}</td>
            </tr>
          `).join('')}
          ${miscRows.filter(r=>r&&(r.name||r.amount)).map(r => `
            <tr>
              <td>${(r.name||'').toString().replace(/</g,'&lt;')}</td>
              <td>${(r.qty||'').toString().replace(/</g,'&lt;')}</td>
              <td>${(r.rate||'').toString().replace(/</g,'&lt;')}</td>
              <td>${money(r.amount)}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
      </div>

    <!-- Total Section -->
    <div class="total-section">
      <h2>Cost Summary</h2>
      <div class="total-row">
        <span>Materials Cost:</span>
        <span>${money(s?.totals?.materials||0)}</span>
    </div>
      <div class="total-row">
        <span>Labor Cost:</span>
        <span>${money(s?.totals?.labor||0)}</span>
    </div>
      <div class="total-row">
        <span>Utilities:</span>
        <span>${money(s?.totals?.utilities||0)}</span>
    </div>
      <div class="total-row">
        <span>Miscellaneous:</span>
        <span>${money(s?.totals?.misc||0)}</span>
    </div>
      <div class="total-row">
        <span>Transportation:</span>
        <span>${money(s?.totals?.transport||0)}</span>
    </div>
      <div class="total-row">
        <span>Contingency (5%):</span>
        <span>${money(s?.totals?.contingency||0)}</span>
    </div>
      <div class="total-row grand-total">
        <span>GRAND TOTAL:</span>
        <span>${money(s?.totals?.grand || est.total_cost)}</span>
      </div>
    </div>

    <!-- Terms and Conditions -->
    <div class="terms-section">
      <h3>Terms & Conditions</h3>
      <p><strong>Payment Terms:</strong> 30% advance, 40% on completion of foundation, 30% on completion</p>
      <p><strong>Validity:</strong> This estimate is valid for 30 days from the date of issue</p>
      <p><strong>Materials:</strong> All materials will be of standard quality as per specifications</p>
      <p><strong>Timeline:</strong> Project completion within ${est.timeline||'90'} days from commencement</p>
      <p><strong>Warranty:</strong> 1 year warranty on workmanship, 5 years on structural elements</p>
      <p><strong>Notes:</strong> ${est.notes ? est.notes.replace(/\n/g,'<br/>') : 'All work to be done as per approved drawings and specifications'}</p>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
      <div class="signature-box">
        <div class="signature-line"></div>
        <div class="signature-label">Client Signature</div>
        <p style="margin-top: 10px; font-size: 12px; color: #6c757d;">Date: _______________</p>
      </div>
      <div class="signature-box">
        <div class="contractor-seal">
          <div>OFFICIAL<br/>SEAL</div>
        </div>
        <div class="signature-line"></div>
        <div class="signature-label">${contractorName}</div>
        <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Authorized Contractor</p>
        <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Date: ${currentDate}</p>
      </div>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p>This is a computer-generated estimate. For any clarifications, please contact us.</p>
      <p>© ${new Date().getFullYear()} ${contractorName} Construction. All rights reserved.</p>
    </div>
    </body></html>`;
  };

  const hasMessage = est.homeowner_message && est.homeowner_message.trim();
  const isAccepted = est.status === 'accepted';
  
  // Debug logging
  if (hasMessage) {
    console.log('Estimate has homeowner message:', est.id, est.homeowner_message);
  }
  
  return (
    <div className="list-item" style={{
      borderLeft: hasMessage ? '4px solid #10b981' : undefined,
      background: hasMessage ? 'linear-gradient(to right, #ffffff 0%, #f0fdf4 5%)' : undefined
    }}>
      <div className="item-image">
        {hasMessage ? '✅' : '📄'}
      </div>
      <div className="item-content" style={{flex:1}}>
        <h4 className="item-title" style={{margin:0}}>
          {hasMessage && isAccepted ? '✓ Accepted ' : ''}Estimate #{est.id}
        </h4>
        <p className="item-subtitle" style={{margin:'2px 0 0 0'}}>Total: {money(est.total_cost ?? s?.totals?.grand)} • {new Date(est.created_at).toLocaleString()}</p>
        {est.timeline && <p className="item-meta">Timeline: {est.timeline}</p>}

        {/* Homeowner Message Display */}
        {hasMessage && (
          <div style={{
            marginTop: '12px',
            padding: '12px',
            background: 'linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%)',
            borderRadius: '8px',
            border: '1px solid #10b981'
          }}>
            <div style={{ display: 'flex', alignItems: 'center', marginBottom: '8px' }}>
              <span style={{ fontSize: '18px', marginRight: '8px' }}>💬</span>
              <strong style={{ color: '#065f46', fontSize: '14px' }}>Message from Homeowner:</strong>
            </div>
            <div style={{
              color: '#047857',
              fontSize: '13px',
              lineHeight: '1.6',
              whiteSpace: 'pre-wrap',
              paddingLeft: '26px'
            }}>
              {est.homeowner_message}
            </div>
          </div>
        )}

        <details style={{ marginTop: 6 }}>
          <summary style={{ cursor:'pointer' }}>View breakdown</summary>
          <div className="card" style={{ marginTop: 8 }}>
            <div className="grid" style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:8 }}>
              <div><strong>Project:</strong> {s?.project_name || '—'}</div>
              <div><strong>Address:</strong> {s?.project_address || '—'}</div>
              <div><strong>Plot Size:</strong> {s?.plot_size || '—'}</div>
              <div><strong>Built-up Area:</strong> {s?.built_up_area || '—'}</div>
              <div><strong>Floors:</strong> {s?.floors || '—'}</div>
              <div><strong>Date:</strong> {s?.estimation_date || '—'}</div>
            </div>
          </div>
          {materialsRows.length > 0 && <Section title="Materials" rows={materialsRows} />}
          {laborRows.length > 0 && <Section title="Labor" rows={laborRows} />}
          {utilitiesRows.length > 0 && <Section title="Utilities & Fixtures" rows={utilitiesRows} />}
          {miscRows.length > 0 && <Section title="Miscellaneous" rows={miscRows} />}
          <div className="card" style={{ marginTop: 8 }}>
            <div className="row" style={{ display:'flex', justifyContent:'space-between' }}>
              <div className="muted">Materials Total</div>
              <div><strong>{money(s?.totals?.materials)}</strong></div>
            </div>
            <div className="row" style={{ display:'flex', justifyContent:'space-between' }}>
              <div className="muted">Labor Total</div>
              <div><strong>{money(s?.totals?.labor)}</strong></div>
            </div>
            <div className="row" style={{ display:'flex', justifyContent:'space-between' }}>
              <div className="muted">Utilities Total</div>
              <div><strong>{money(s?.totals?.utilities)}</strong></div>
            </div>
            <div className="row" style={{ display:'flex', justifyContent:'space-between' }}>
              <div className="muted">Misc Total</div>
              <div><strong>{money(s?.totals?.misc)}</strong></div>
            </div>
            <div className="row" style={{ display:'flex', justifyContent:'space-between', marginTop:6 }}>
              <div className="muted">Grand Total</div>
              <div className="total"><strong>{money(s?.totals?.grand || est.total_cost)}</strong></div>
            </div>
          </div>
          {est.notes && (
            <div className="card" style={{ marginTop: 8 }}>
              <h4 style={{ margin:'0 0 6px 0' }}>Notes</h4>
              <div style={{ whiteSpace:'pre-wrap' }}>{est.notes}</div>
            </div>
          )}
        </details>
      </div>
      <div className="item-actions" style={{display:'flex', gap:6}}>
        <button className="btn btn-primary" onClick={async ()=>{
          try {
            const html = buildReportHtml(user);
            const contractorName = user?.first_name && user?.last_name ? 
              `${user.first_name} ${user.last_name}` : 
              'Contractor';
            
            // Create a temporary div to render the HTML content
            const tempDiv = document.createElement('div');
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            tempDiv.style.top = '-9999px';
            tempDiv.style.width = '210mm';
            tempDiv.style.padding = '20mm';
            tempDiv.style.backgroundColor = 'white';
            tempDiv.style.fontFamily = 'Times New Roman, serif';
            tempDiv.style.fontSize = '12px';
            tempDiv.style.lineHeight = '1.4';
            tempDiv.style.color = '#1a1a1a';
            
            tempDiv.innerHTML = html;
            document.body.appendChild(tempDiv);
            
            // Convert to canvas and then to PDF
            console.log('Converting HTML to canvas...');
            const canvas = await html2canvas(tempDiv, {
              scale: 2,
              useCORS: true,
              allowTaint: true,
              backgroundColor: '#ffffff'
            });
            
            console.log('Canvas created:', canvas.width, 'x', canvas.height);
            
            // Remove the temporary div
            document.body.removeChild(tempDiv);
            
            // Create PDF
            console.log('Creating PDF...');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgData = canvas.toDataURL('image/png');
            const imgWidth = 210; // A4 width in mm
            const pageHeight = 295; // A4 height in mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            let heightLeft = imgHeight;
            
            console.log('Image dimensions:', imgWidth, 'x', imgHeight);
            
            let position = 0;
            
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
            
            while (heightLeft >= 0) {
              position = heightLeft - imgHeight;
              pdf.addPage();
              pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
              heightLeft -= pageHeight;
            }
            
            // Download the PDF
            const fileName = `Estimate_${contractorName.replace(/\s+/g, '_')}_${Date.now().toString().slice(-6)}.pdf`;
            console.log('Saving PDF as:', fileName);
            
            // Try alternative download method
            try {
              const pdfBlob = pdf.output('blob');
              const url = URL.createObjectURL(pdfBlob);
              const a = document.createElement('a');
              a.href = url;
              a.download = fileName;
              a.style.display = 'none';
              document.body.appendChild(a);
              a.click();
              document.body.removeChild(a);
              URL.revokeObjectURL(url);
              console.log('PDF downloaded via blob method');
            } catch (blobError) {
              console.log('Blob method failed, trying direct save:', blobError);
              pdf.save(fileName);
            }
            console.log('PDF save method called');
          } catch (error) {
            console.error('Download failed:', error);
          }
        }}>Download PDF Report</button>
        <button className="btn btn-secondary" onClick={async () => {
          showConfirmation(
            'Remove Estimate',
            'Are you sure you want to remove this estimate? This action cannot be undone.',
            async () => {
              try {
                const me = JSON.parse(sessionStorage.getItem('user') || '{}');
                const response = await fetch('/buildhub/backend/api/contractor/delete_estimate.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  credentials: 'include',
                  body: JSON.stringify({ estimate_id: est.id, contractor_id: me.id })
                });
                
                const result = await response.json().catch(() => ({}));
                if (result.success) {
                  toast.success('Estimate removed successfully!');
                  // Trigger a refresh of the estimates list
                  window.dispatchEvent(new CustomEvent('refreshEstimates'));
                } else {
                  toast.error(result.message || 'Failed to remove estimate. Please try again.');
                }
              } catch (e) {
                toast.error('Error removing estimate. Please try again.');
                console.error(e);
              }
            }
          );
        }} style={{
          background: '#ef4444', 
          color: 'white', 
          border: '1px solid #ef4444',
          fontSize: '12px',
          padding: '6px 12px'
        }}>🗑️ Remove</button>
      </div>
    </div>
  );
};

export default ContractorDashboard;