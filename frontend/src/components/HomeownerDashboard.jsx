import React, { useState, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import { useToast } from './ToastProvider.jsx';
import ArchitectDetailsModal from './ArchitectDetailsModal.jsx';
import TourGuide from './TourGuide.jsx';
import HomeownerDashboardTour from './HomeownerDashboardTour.jsx';
import ArchitectSelection from './ArchitectSelection.jsx';
import GeoPhotoViewer from './GeoPhotoViewer.jsx';
import HousePlanViewer from './HousePlanViewer.jsx';
import { useNavigate } from 'react-router-dom';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';
import '../styles/HomeownerDashboard.css';
import '../styles/HomeownerProgressReports.css';
import '../styles/BlueGlassTheme.css';
import '../styles/SoftSidebar.css';
import '../styles/Widgets.css';
import '../styles/ReviewSection.css';
import '../styles/ArchitectRecommendation.css';
import './WidgetColors.css';
import SearchableDropdown from './SearchableDropdown';
import { indianCities } from '../data/indianCities';
import { badgeClass, formatStatus } from '../utils/status';
import { ProjectProgressChart, ProjectTimeline, BudgetTracker } from './widgets/ProjectTrackingWidgets';
import NotificationSystem from './widgets/NotificationSystem';
import DesignGallery from './widgets/DesignGallery';
import NeatJsonCard from './NeatJsonCard';
import TechnicalDetailsDisplay from './TechnicalDetailsDisplay';
import HomeownerProfileButton from './HomeownerProfileButton';
import HomeownerProgressReports from './HomeownerProgressReports';
import ConfirmModal from './ConfirmModal';
import '../styles/SupportSystem.css';

const HomeownerDashboard = () => {
  const navigate = useNavigate();
  const toast = useToast();
  const [activeTab, setActiveTab] = useState('dashboard');
  const [requestsTab, setRequestsTab] = useState('all'); // 'all' or 'contractors'
  const [receivedDesigns, setReceivedDesigns] = useState([]);
  const [comments, setComments] = useState({}); // designId -> list
  const [commentDrafts, setCommentDrafts] = useState({}); // designId -> text
  const [commentRatings, setCommentRatings] = useState({}); // designId -> 1..5
  const [user, setUser] = useState(null);
  const [layoutRequests, setLayoutRequests] = useState([]);
  const [showTourGuide, setShowTourGuide] = useState(false);
  const [tourStep, setTourStep] = useState(0);
  const [showDashboardTour, setShowDashboardTour] = useState(false);
  const [contractorRequests, setContractorRequests] = useState([]);
  const [myProjects, setMyProjects] = useState([]);
  const [layoutLibrary, setLayoutLibrary] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [showRequestForm, setShowRequestForm] = useState(false);
  const [showLibraryModal, setShowLibraryModal] = useState(false);
  const [selectedLibraryLayout, setSelectedLibraryLayout] = useState(null);
  // Request form data state used for customization and submissions
  const [requestData, setRequestData] = useState({
    plot_size: '',
    building_size: '',
    budget_range: '',
    plot_shape: '',
    num_floors: '',
    topography: '',
    development_laws: '',
    family_needs: [], // Changed to array for multi-select
    rooms: [], // Changed to array for multi-select
    aesthetic: '',
    style_preferences: {}, // AI recommendation style preferences
    location: '',
    timeline: '',
    requirements: '',
    selected_layout_id: null,
    layout_type: 'custom',
    custom_budget: '' // Added for custom budget input
  });
  const [previewLayout, setPreviewLayout] = useState(null);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [showDesignDetails, setShowDesignDetails] = useState({}); // designId -> boolean
  const [profileMenuOpen, setProfileMenuOpen] = useState(false);
  const [showArchitectModal, setShowArchitectModal] = useState(false);

  // Payment state (gates layout files until paid)
  const [paymentLoading, setPaymentLoading] = useState(false);
  const [paymentError, setPaymentError] = useState('');
  const [payingDesignId, setPayingDesignId] = useState(null);
  const [unlockedDesignIds, setUnlockedDesignIds] = useState({});
  const [homeownerEstimates, setHomeownerEstimates] = useState([]);
  const [openChangeByEstimateId, setOpenChangeByEstimateId] = useState({});
  const [changeTextByEstimateId, setChangeTextByEstimateId] = useState({});
  const [showConstructionModal, setShowConstructionModal] = useState(false);
  const [selectedEstimate, setSelectedEstimate] = useState(null);
  const [showMessageModal, setShowMessageModal] = useState(false);
  const [messageToContractor, setMessageToContractor] = useState('');
  const [selectedEstimateForMessage, setSelectedEstimateForMessage] = useState(null);
  const [messagesSentToContractors, setMessagesSentToContractors] = useState({}); // Track which estimates have messages sent

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

  // Debug useEffect to monitor modal state changes
  useEffect(() => {
    console.log('Modal state changed to:', showConstructionModal);
    console.log('Selected estimate changed to:', selectedEstimate);
  }, [showConstructionModal, selectedEstimate]);

  // Dashboard tour functions
  const handleDashboardTourNext = () => {
    if (tourStep < 9) { // 10 steps total (0-9)
      setTourStep(tourStep + 1);
    } else {
      setShowDashboardTour(false);
      setTourStep(0);
    }
  };

  const handleDashboardTourPrev = () => {
    if (tourStep > 0) {
      setTourStep(tourStep - 1);
    }
  };

  const handleDashboardTourSkip = () => {
    setShowDashboardTour(false);
    setTourStep(0);
    // Mark dashboard tour as completed
    localStorage.setItem('buildhub_dashboard_tour_completed', 'true');
  };

  const handleDashboardTourClose = () => {
    setShowDashboardTour(false);
    setTourStep(0);
    // Mark dashboard tour as completed
    localStorage.setItem('buildhub_dashboard_tour_completed', 'true');
  };

  // Check if this is the first time user visits dashboard and show tour
  useEffect(() => {
    const dashboardTourCompleted = localStorage.getItem('buildhub_dashboard_tour_completed');
    if (!dashboardTourCompleted) {
      // Show tour after a short delay to let the page load
      const timer = setTimeout(() => {
        setShowDashboardTour(true);
        setTourStep(0);
      }, 2000);
      return () => clearTimeout(timer);
    }
  }, []);

  // Load locally remembered paid layouts so refresh doesn't re-lock
  useEffect(() => {
    try {
      const saved = JSON.parse(localStorage.getItem('bh_paid_layouts') || '[]');
      if (Array.isArray(saved) && saved.length) {
        const map = {};
        saved.forEach((id) => { map[id] = true; });
        setUnlockedDesignIds((prev) => ({ ...prev, ...map }));
      }
    } catch { }
  }, []);

  // Fetch contractor estimates submitted for this homeowner
  useEffect(() => {
    let mounted = true;
    const fetchEstimates = async () => {
      try {
        const me = JSON.parse(sessionStorage.getItem('user') || '{}');
        if (!me?.id) {
          console.log('No user ID found for fetching estimates');
          return;
        }
        console.log('Fetching estimates for homeowner:', me.id);
        const r = await fetch(`/buildhub/backend/api/homeowner/get_estimates.php?homeowner_id=${me.id}`, { credentials: 'include' });
        const j = await r.json().catch(() => ({}));
        console.log('Estimates API response:', j);
        if (mounted && j?.success) {
          console.log('Setting estimates:', Array.isArray(j.estimates) ? j.estimates : []);
          setHomeownerEstimates(Array.isArray(j.estimates) ? j.estimates : []);
          // Check which estimates have messages sent (based on status)
          const messagesSent = {};
          if (Array.isArray(j.estimates)) {
            j.estimates.forEach(est => {
              if (est.status === 'approved_with_message' || est.homeowner_feedback) {
                messagesSent[est.id] = true;
              }
            });
          }
          setMessagesSentToContractors(messagesSent);
        } else {
          console.log('Estimates fetch failed:', j.message);
        }
      } catch (error) {
        console.error('Error fetching estimates:', error);
      }
    };
    fetchEstimates();
    const id = setInterval(fetchEstimates, 60000);
    return () => { mounted = false; clearInterval(id); };
  }, []);

  const downloadEstimateReport = async (est) => {
    try {
      // Require payment: unlock only if paid
      const isPaid = Number(est.is_paid || 0) > 0;
      if (!isPaid) {
        // Wait for Razorpay to be available
        let attempts = 0;
        while ((!window.Razorpay || window._razorpayLoading) && attempts < 20) {
          await new Promise(resolve => setTimeout(resolve, 100));
          attempts++;
        }

        if (!window.Razorpay) {
          toast.error('Payment system not loaded. Please refresh the page and try again.');
          return;
        }

        const me = JSON.parse(sessionStorage.getItem('user') || '{}');
        const initRes = await fetch('/buildhub/backend/api/homeowner/initiate_estimate_payment.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify({ homeowner_id: me.id, estimate_id: est.id })
        });
        const init = await initRes.json();
        if (!init?.success) { toast.error(init?.message || 'Failed to start payment'); return; }

        const options = {
          key: init.razorpay_key_id,
          amount: init.amount,
          currency: init.currency || 'INR',
          name: 'BuildHub',
          description: `Unlock Contractor Estimate #${est.id}`,
          order_id: init.razorpay_order_id,
          handler: async function (rzpRes) {
            try {
              const verifyRes = await fetch('/buildhub/backend/api/homeowner/verify_estimate_payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                  razorpay_payment_id: rzpRes.razorpay_payment_id,
                  razorpay_order_id: rzpRes.razorpay_order_id,
                  razorpay_signature: rzpRes.razorpay_signature,
                  payment_id: init.payment_id
                })
              });
              const ver = await verifyRes.json();
              if (ver?.success) {
                // Refresh estimates to get paid flag
                try {
                  const me2 = JSON.parse(sessionStorage.getItem('user') || '{}');
                  const r = await fetch(`/buildhub/backend/api/homeowner/get_estimates.php?homeowner_id=${me2.id}`, { credentials: 'include' });
                  const j = await r.json().catch(() => ({}));
                  if (j?.success) setHomeownerEstimates(Array.isArray(j.estimates) ? j.estimates : []);
                } catch { }
                toast.success('Payment successful. Estimate unlocked. Click again to download.');
              } else {
                toast.error(ver?.message || 'Payment verification failed');
              }
            } catch (e) { toast.error('Verification error'); }
          },
          prefill: { name: me?.first_name || 'Homeowner', email: me?.email || '' }
        };

        try {
          const rzp = new window.Razorpay(options);
          rzp.open();
        } catch (e) {
          console.error('Razorpay error:', e);
          toast.error('Failed to open payment gateway. Please try again.');
        }
        return;
      }

      const structured = (() => { try { return est.structured ? JSON.parse(est.structured) : null; } catch { return null; } })();
      const fmt = (n) => (n === undefined || n === null || n === '' || isNaN(Number(n))) ? '₹0' : `₹${Number(n).toLocaleString('en-IN')}`;

      const contractorName = est.contractor_name || 'Contractor Name';
      const currentDate = new Date().toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });

      const html = `
        <div style="font-family: 'Times New Roman', serif; color: #1a1a1a; margin: 0; padding: 0; line-height: 1.4; background: white;">
      <!-- Company Header -->
          <div style="text-align: center; margin-bottom: 30px; border-bottom: 3px solid #2c3e50; padding-bottom: 20px;">
            <div style="width: 120px; height: 120px; margin: 0 auto 15px; border: 2px solid #2c3e50; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: bold; color: #2c3e50; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">🏗️</div>
            <div style="font-size: 28px; font-weight: bold; color: #2c3e50; margin: 10px 0 5px 0; text-transform: uppercase; letter-spacing: 2px;">${contractorName} Construction</div>
            <div style="font-size: 14px; color: #6c757d; font-style: italic; margin-bottom: 10px;">Professional Construction Services</div>
            <div style="font-size: 12px; color: #495057; line-height: 1.3;">
          📧 Email: ${est.contractor_email || 'contact@company.com'} | 
          📱 Phone: ${est.contractor_phone || '+91-XXXXX-XXXXX'} | 
          🏢 License: ${est.contractor_license || 'LIC-XXXXX'}
        </div>
      </div>

      <!-- Document Title -->
          <div style="text-align: center; margin: 30px 0; font-size: 24px; font-weight: bold; color: #2c3e50; text-transform: uppercase; letter-spacing: 1px;">Cost Estimate Report</div>

      <!-- Estimate Information -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; padding: 15px; background: #f8f9fa; border-left: 4px solid #2c3e50;">
            <div>
              <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #2c3e50; border-bottom: 1px solid #dee2e6; padding-bottom: 5px;">Project Details</h3>
              <p style="margin: 5px 0; font-size: 14px;"><strong>Project Name:</strong> ${structured?.project_name || 'Construction Project'}</p>
              <p style="margin: 5px 0; font-size: 14px;"><strong>Location:</strong> ${structured?.project_address || 'Project Location'}</p>
              <p style="margin: 5px 0; font-size: 14px;"><strong>Client:</strong> ${est.client_name || 'Client Name'}</p>
              <p style="margin: 5px 0; font-size: 14px;"><strong>Plot Size:</strong> ${structured?.plot_size || '—'}</p>
              <p style="margin: 5px 0; font-size: 14px;"><strong>Built-up Area:</strong> ${structured?.built_up_area || '—'}</p>
              <p style="margin: 5px 0; font-size: 14px;"><strong>Floors:</strong> ${structured?.floors || '—'}</p>
        </div>
            <div>
              <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #2c3e50; border-bottom: 1px solid #dee2e6; padding-bottom: 5px;">Estimate Information</h3>
              <p style="margin: 5px 0; font-size: 14px;"><strong>Estimate Date:</strong> ${currentDate}</p>
              <p style="margin: 5px 0; font-size: 14px;"><strong>Estimate Valid Until:</strong> ${new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toLocaleDateString('en-IN')}</p>
              <p style="margin: 5px 0; font-size: 14px;"><strong>Project Duration:</strong> ${est.timeline || '90 days'}</p>
              <p style="margin: 5px 0; font-size: 14px;"><strong>Estimate #:</strong> EST-${est.id}</p>
        </div>
      </div>

      <!-- Cost Breakdown -->
          <div style="margin: 30px 0;">
            <h2 style="font-size: 20px; color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px;">Detailed Cost Breakdown</h2>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
          <thead>
            <tr>
                  <th style="background: #2c3e50; color: white; padding: 12px; text-align: left; font-weight: bold; font-size: 14px;">Item Description</th>
                  <th style="background: #2c3e50; color: white; padding: 12px; text-align: left; font-weight: bold; font-size: 14px;">Quantity</th>
                  <th style="background: #2c3e50; color: white; padding: 12px; text-align: left; font-weight: bold; font-size: 14px;">Unit Rate (₹)</th>
                  <th style="background: #2c3e50; color: white; padding: 12px; text-align: left; font-weight: bold; font-size: 14px;">Amount (₹)</th>
            </tr>
          </thead>
          <tbody>
                ${structured && structured.materials ? Object.entries(structured.materials).filter(([key, val]) => val && typeof val === 'object' && (val.name || val.amount)).map(([key, val], index) => `
                  <tr style="background: ${index % 2 === 0 ? '#f8f9fa' : 'white'};">
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${val.name || key}</td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${val.qty || ''}</td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${val.rate || ''}</td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${fmt(val.amount)}</td>
              </tr>
            `).join('') : ''}
                ${structured && structured.labor ? Object.entries(structured.labor).filter(([key, val]) => val && typeof val === 'object' && (val.name || val.amount)).map(([key, val], index) => `
                  <tr style="background: ${index % 2 === 0 ? '#f8f9fa' : 'white'};">
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${val.name || key}</td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${val.qty || ''}</td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${val.rate || ''}</td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${fmt(val.amount)}</td>
              </tr>
            `).join('') : ''}
                ${structured && structured.utilities ? Object.entries(structured.utilities).filter(([key, val]) => val && typeof val === 'object' && (val.name || val.amount)).map(([key, val], index) => `
                  <tr style="background: ${index % 2 === 0 ? '#f8f9fa' : 'white'};">
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${val.name || key}</td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${val.qty || ''}</td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${val.rate || ''}</td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${fmt(val.amount)}</td>
              </tr>
            `).join('') : ''}
                ${structured && structured.misc ? Object.entries(structured.misc).filter(([key, val]) => val && typeof val === 'object' && (val.name || val.amount)).map(([key, val], index) => `
                  <tr style="background: ${index % 2 === 0 ? '#f8f9fa' : 'white'};">
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${val.name || key}</td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${val.qty || ''}</td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${val.rate || ''}</td>
                    <td style="padding: 10px 12px; border-bottom: 1px solid #dee2e6; font-size: 14px;">${fmt(val.amount)}</td>
              </tr>
            `).join('') : ''}
          </tbody>
        </table>
      </div>

      <!-- Total Section -->
          <div style="margin: 30px 0; padding: 20px; background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; border-radius: 8px;">
            <h2 style="margin: 0 0 15px 0; font-size: 18px; text-align: center;">Cost Summary</h2>
            <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 16px;">
          <span>Materials Cost:</span>
          <span>${fmt(structured?.totals?.materials || 0)}</span>
        </div>
            <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 16px;">
          <span>Labor Cost:</span>
          <span>${fmt(structured?.totals?.labor || 0)}</span>
        </div>
            <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 16px;">
          <span>Utilities:</span>
          <span>${fmt(structured?.totals?.utilities || 0)}</span>
        </div>
            <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 16px;">
          <span>Miscellaneous:</span>
          <span>${fmt(structured?.totals?.misc || 0)}</span>
        </div>
            <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 16px;">
          <span>Transportation:</span>
          <span>${fmt(structured?.totals?.transport || 0)}</span>
        </div>
            <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 16px;">
          <span>Contingency (5%):</span>
          <span>${fmt(structured?.totals?.contingency || 0)}</span>
        </div>
            <div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 18px; font-weight: bold; border-top: 2px solid white; padding-top: 10px; margin-top: 15px;">
          <span>GRAND TOTAL:</span>
          <span>${fmt(structured?.totals?.grand || est.total_cost)}</span>
        </div>
      </div>

      <!-- Terms and Conditions -->
          <div style="margin: 30px 0; padding: 20px; background: #f8f9fa; border-left: 4px solid #28a745;">
            <h3 style="margin: 0 0 15px 0; color: #28a745; font-size: 16px;">Terms & Conditions</h3>
            <p style="margin: 8px 0; font-size: 14px; line-height: 1.5;"><strong>Payment Terms:</strong> 30% advance, 40% on completion of foundation, 30% on completion</p>
            <p style="margin: 8px 0; font-size: 14px; line-height: 1.5;"><strong>Validity:</strong> This estimate is valid for 30 days from the date of issue</p>
            <p style="margin: 8px 0; font-size: 14px; line-height: 1.5;"><strong>Materials:</strong> All materials will be of standard quality as per specifications</p>
            <p style="margin: 8px 0; font-size: 14px; line-height: 1.5;"><strong>Timeline:</strong> Project completion within ${est.timeline || '90'} days from commencement</p>
            <p style="margin: 8px 0; font-size: 14px; line-height: 1.5;"><strong>Warranty:</strong> 1 year warranty on workmanship, 5 years on structural elements</p>
            <p style="margin: 8px 0; font-size: 14px; line-height: 1.5;"><strong>Notes:</strong> ${est.notes ? est.notes.replace(/\n/g, '<br/>') : 'All work to be done as per approved drawings and specifications'}</p>
      </div>

      <!-- Signature Section -->
          <div style="margin-top: 50px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <div style="text-align: center; padding: 20px; border: 2px solid #2c3e50; border-radius: 8px; background: #f8f9fa;">
              <div style="border-bottom: 2px solid #2c3e50; margin: 40px 0 10px 0; height: 2px;"></div>
              <div style="font-size: 14px; font-weight: bold; color: #2c3e50; margin-top: 10px;">Client Signature</div>
          <p style="margin-top: 10px; font-size: 12px; color: #6c757d;">Date: _______________</p>
        </div>
            <div style="text-align: center; padding: 20px; border: 2px solid #2c3e50; border-radius: 8px; background: #f8f9fa;">
              <div style="width: 100px; height: 100px; border: 3px solid #dc3545; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: #dc3545; background: white; text-align: center; line-height: 1.2;">
            <div>OFFICIAL<br/>SEAL</div>
          </div>
              <div style="border-bottom: 2px solid #2c3e50; margin: 40px 0 10px 0; height: 2px;"></div>
              <div style="font-size: 14px; font-weight: bold; color: #2c3e50; margin-top: 10px;">${contractorName}</div>
          <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Authorized Contractor</p>
          <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Date: ${currentDate}</p>
        </div>
      </div>

      <!-- Footer -->
          <div style="margin-top: 40px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #dee2e6; padding-top: 15px;">
        <p>This is a computer-generated estimate. For any clarifications, please contact us.</p>
        <p>© ${new Date().getFullYear()} ${contractorName} Construction. All rights reserved.</p>
      </div>
        </div>`;

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
    } catch (e) {
      console.error('Error generating PDF report:', e);
      toast.error('Error generating PDF report');
    }
  };

  const downloadTechnicalDetailsPDF = async (est) => {
    try {
      const parsed = (() => {
        try { return est.structured ? JSON.parse(est.structured) : null; }
        catch { return null; }
      })();

      const technicalDetails = parsed?.technical_details || {};
      if (!technicalDetails || Object.keys(technicalDetails).length === 0) {
        toast.warning('No technical details available for this estimate');
        return;
      }

      const contractorName = est.contractor_name || 'Contractor';
      const currentDate = new Date().toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });

      const formatValue = (value) => {
        if (!value) return '—';
        if (typeof value === 'object') return JSON.stringify(value, null, 2);
        return String(value);
      };

      const html = `
        <div style="font-family: 'Times New Roman', serif; color: #1a1a1a; margin: 0; padding: 20px; line-height: 1.4; background: white;">
          <div style="text-align: center; margin-bottom: 30px; border-bottom: 3px solid #2c3e50; padding-bottom: 20px;">
            <div style="font-size: 28px; font-weight: bold; color: #2c3e50;">Technical Details Report</div>
            <div style="font-size: 14px; color: #6c757d; margin-top: 8px;">Contractor: ${contractorName}</div>
            <div style="font-size: 12px; color: #9ca3af;">Date: ${currentDate}</div>
          </div>
          
          ${technicalDetails.room_dimensions ? `
          <div style="margin-bottom: 24px;">
            <h3 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 8px;">Room Dimensions</h3>
            <div style="margin-top: 12px;">
              ${Object.entries(technicalDetails.room_dimensions).map(([room, dimensions]) => `
                <div style="padding: 8px 12px; margin: 4px 0; background: #f8f9fa; border-left: 3px solid #2c3e50;">
                  <strong>${room}:</strong> ${formatValue(dimensions)}
                </div>
              `).join('')}
            </div>
          </div>
          ` : ''}

          ${technicalDetails.floor_plans ? `
          <div style="margin-bottom: 24px;">
            <h3 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 8px;">Floor Plans</h3>
            <div style="margin-top: 12px;">
              ${Object.entries(technicalDetails.floor_plans).map(([key, value]) => `
                <div style="padding: 8px 12px; margin: 4px 0; background: #f8f9fa; border-left: 3px solid #2c3e50;">
                  <strong>${key.replace(/_/g, ' ')}:</strong> ${formatValue(value)}
                </div>
              `).join('')}
            </div>
          </div>
          ` : ''}

          ${technicalDetails.structural_elements ? `
          <div style="margin-bottom: 24px;">
            <h3 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 8px;">Structural Elements</h3>
            <div style="margin-top: 12px;">
              ${Object.entries(technicalDetails.structural_elements).map(([key, value]) => `
                <div style="padding: 8px 12px; margin: 4px 0; background: #f8f9fa; border-left: 3px solid #2c3e50;">
                  <strong>${key.replace(/_/g, ' ')}:</strong> ${formatValue(value)}
                </div>
              `).join('')}
            </div>
          </div>
          ` : ''}

          ${technicalDetails.material_specifications ? `
          <div style="margin-bottom: 24px;">
            <h3 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 8px;">Material Specifications</h3>
            <div style="margin-top: 12px;">
              ${Object.entries(technicalDetails.material_specifications).map(([key, value]) => `
                <div style="padding: 8px 12px; margin: 4px 0; background: #f8f9fa; border-left: 3px solid #2c3e50;">
                  <strong>${key.replace(/_/g, ' ')}:</strong> ${formatValue(value)}
                </div>
              `).join('')}
            </div>
          </div>
          ` : ''}
        </div>`;

      const tempDiv = document.createElement('div');
      tempDiv.style.position = 'absolute';
      tempDiv.style.left = '-9999px';
      tempDiv.style.top = '-9999px';
      tempDiv.style.width = '210mm';
      tempDiv.style.padding = '20mm';
      tempDiv.style.backgroundColor = 'white';
      tempDiv.innerHTML = html;
      document.body.appendChild(tempDiv);

      const canvas = await html2canvas(tempDiv, { scale: 2, useCORS: true, allowTaint: true, backgroundColor: '#ffffff' });
      document.body.removeChild(tempDiv);

      const pdf = new jsPDF('p', 'mm', 'a4');
      const imgData = canvas.toDataURL('image/png');
      const imgWidth = 210;
      const pageHeight = 295;
      const imgHeight = (canvas.height * imgWidth) / canvas.width;
      let heightLeft = imgHeight;
      let position = 0;

      pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
      heightLeft -= pageHeight;

      while (heightLeft >= 0) {
        position = heightLeft - imgHeight;
        pdf.addPage();
        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;
      }

      const fileName = `Technical_Details_${contractorName.replace(/\s+/g, '_')}_${Date.now().toString().slice(-6)}.pdf`;
      pdf.save(fileName);
      toast.success('Technical details PDF downloaded successfully');
    } catch (e) {
      console.error('Error generating technical details PDF:', e);
      toast.error('Error generating PDF');
    }
  };

  const respondToEstimate = async (est, action, message = '') => {
    try {
      const me = JSON.parse(sessionStorage.getItem('user') || '{}');
      const res = await fetch('/buildhub/backend/api/homeowner/respond_to_estimate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ homeowner_id: me.id, estimate_id: est.id, action, message })
      });
      const j = await res.json();
      if (j?.success) {
        // refresh list
        try {
          const r = await fetch(`/buildhub/backend/api/homeowner/get_estimates.php?homeowner_id=${me.id}`, { credentials: 'include' });
          const k = await r.json().catch(() => ({}));
          if (k?.success) setHomeownerEstimates(Array.isArray(k.estimates) ? k.estimates : []);
        } catch { }
        const msg = action === 'accept' ? 'Accepted. Contractor will be notified.' : action === 'changes' ? 'Change request sent.' : 'Estimate rejected.';
        try { toast.success(msg); } catch { /* no-op */ }
      } else {
        try { toast.error(j?.message || 'Failed to update'); } catch { /* no-op */ }
      }
    } catch { }
  };

  const startConstruction = async (est) => {
    try {
      const me = JSON.parse(sessionStorage.getItem('user') || '{}');
      if (!me?.id) { 
        toast.error('Not logged in'); 
        return; 
      }
      if (!est?.id) { 
        toast.error('Invalid estimate'); 
        return; 
      }

      const res = await fetch('/buildhub/backend/api/homeowner/start_construction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          homeowner_id: me.id,
          estimate_id: est.id
        })
      });
      
      const j = await res.json().catch(() => ({}));
      if (j?.success) {
        toast.success('Construction started successfully! Contractor can now submit progress updates.');
        // Refresh estimates to reflect status update
        try {
          const r = await fetch(`/buildhub/backend/api/homeowner/get_estimates.php?homeowner_id=${me.id}`, { credentials: 'include' });
          const k = await r.json().catch(() => ({}));
          if (k?.success) setHomeownerEstimates(Array.isArray(k.estimates) ? k.estimates : []);
        } catch { }
      } else {
        toast.error(j?.message || 'Failed to start construction');
      }
    } catch (e) {
      console.error('Error starting construction:', e);
      toast.error('Error starting construction');
    }
  };

  // Remove estimate function
  const removeEstimate = async (estimateId) => {
    if (!estimateId) return;
    
    showConfirmation(
      'Remove Estimate',
      'Are you sure you want to remove this estimate? This action cannot be undone.',
      async () => {
        try {
          const res = await fetch('/buildhub/backend/api/homeowner/delete_estimate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ estimate_id: estimateId })
          });
          const json = await res.json();
          if (json.success) {
            setHomeownerEstimates(prev => prev.filter(est => est.id !== estimateId));
            toast.success('Estimate removed successfully');
          } else {
            toast.error(json.message || 'Failed to remove estimate');
          }
        } catch (e) {
          console.error('Error removing estimate:', e);
          toast.error('Error removing estimate. Please try again.');
        }
      }
    );
  };

  const confirmStartConstruction = async () => {
    if (!selectedEstimate) return;

    try {
      const me = JSON.parse(sessionStorage.getItem('user') || '{}');
      const res = await fetch('/buildhub/backend/api/homeowner/start_construction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          homeowner_id: me.id,
          estimate_id: selectedEstimate.id,
          contractor_id: selectedEstimate.contractor_id,
          project_title: selectedEstimate.project_title || 'Untitled Project'
        })
      });

      const j = await res.json();
      if (j?.success) {
        try { toast.success('Construction started! Contractor has been notified.'); } catch { /* no-op */ }
        // Refresh estimates to update status
        try {
          const r = await fetch(`/buildhub/backend/api/homeowner/get_estimates.php?homeowner_id=${me.id}`, { credentials: 'include' });
          const k = await r.json().catch(() => ({}));
          if (k?.success) setHomeownerEstimates(Array.isArray(k.estimates) ? k.estimates : []);
        } catch { }
      } else {
        try { toast.error(j?.message || 'Failed to start construction'); } catch { /* no-op */ }
      }
    } catch (error) {
      try { toast.error('Error starting construction'); } catch { /* no-op */ }
    } finally {
      setShowConstructionModal(false);
      setSelectedEstimate(null);
    }
  };

  const sendMessageToContractor = async (est) => {
    try {
      const me = JSON.parse(sessionStorage.getItem('user') || '{}');
      if (!me?.id) {
        try { toast.error('Not logged in'); } catch { }
        return;
      }
      if (!est?.id || !est?.contractor_id) {
        try { toast.error('Invalid estimate'); } catch { }
        return;
      }

      const res = await fetch('/buildhub/backend/api/homeowner/send_estimate_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          homeowner_id: me.id,
          estimate_id: est.id,
          contractor_id: est.contractor_id,
          project_title: est.project_title || 'Untitled Project',
          message: messageToContractor.trim() || 'I am satisfied with this estimate and ready to start the construction project. Please let me know the next steps and when we can begin work.'
        })
      });

      const j = await res.json();
      if (j?.success) {
        try { toast.success('Message sent to contractor successfully!'); } catch { /* no-op */ }
        // Mark this estimate as having a message sent
        setMessagesSentToContractors(prev => ({
          ...prev,
          [est.id]: true
        }));
        // Refresh estimates to update status
        try {
          const r = await fetch(`/buildhub/backend/api/homeowner/get_estimates.php?homeowner_id=${me.id}`, { credentials: 'include' });
          const k = await r.json().catch(() => ({}));
          if (k?.success) setHomeownerEstimates(Array.isArray(k.estimates) ? k.estimates : []);
        } catch { }
        // Close modal and reset state
        setShowMessageModal(false);
        setMessageToContractor('');
        setSelectedEstimateForMessage(null);
        
        // Refresh notifications (handled by NotificationSystem component)
      } else {
        try { toast.error(j?.message || 'Failed to send message'); } catch { /* no-op */ }
      }
    } catch (error) {
      try { toast.error('Error sending message'); } catch { /* no-op */ }
    }
  };

  // Direct message function - sends message immediately without modal
  const sendDirectMessageToContractor = async (est) => {
    try {
      console.log('=== SEND MESSAGE DEBUG ===');
      console.log('Estimate object:', est);

      const me = JSON.parse(sessionStorage.getItem('user') || '{}');
      console.log('User:', me);

      if (!me?.id) {
        console.log('No user ID found');
        try { toast.error('Not logged in'); } catch { }
        return;
      }
      if (!est?.id || !est?.contractor_id) {
        console.log('Invalid estimate data:', { id: est?.id, contractor_id: est?.contractor_id });
        try { toast.error('Invalid estimate data'); } catch { }
        return;
      }

      console.log('Sending direct message for estimate:', est.id);
      console.log('Contractor ID:', est.contractor_id);
      console.log('Homeowner ID:', me.id);

      const requestData = {
        homeowner_id: me.id,
        estimate_id: est.id,
        contractor_id: est.contractor_id,
        project_title: est.project_title || `Estimate #${est.id}`,
        message: 'I am satisfied with this estimate and ready to start the construction project. Please let me know the next steps and when we can begin work.'
      };

      console.log('Request data:', requestData);

      const res = await fetch('/buildhub/backend/api/homeowner/send_estimate_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(requestData)
      });

      console.log('Response status:', res.status);
      const j = await res.json();
      console.log('Response data:', j);

      if (j?.success) {
        console.log('Message sent successfully!');
        try { toast.success('Message sent to contractor successfully!'); } catch { /* no-op */ }
        // Mark this estimate as having a message sent
        setMessagesSentToContractors(prev => ({
          ...prev,
          [est.id]: true
        }));
        // Refresh estimates to update status
        try {
          const r = await fetch(`/buildhub/backend/api/homeowner/get_estimates.php?homeowner_id=${me.id}`, { credentials: 'include' });
          const k = await r.json().catch(() => ({}));
          if (k?.success) setHomeownerEstimates(Array.isArray(k.estimates) ? k.estimates : []);
        } catch { }
      } else {
        console.log('Failed to send message:', j);
        try { toast.error(j?.message || 'Failed to send message'); } catch { /* no-op */ }
      }
    } catch (error) {
      console.error('Error sending message:', error);
      try { toast.error('Error sending message: ' + error.message); } catch { /* no-op */ }
    }
  };

  // Sidebar badge counts
  const requestsCount = Array.isArray(layoutRequests) ? layoutRequests.length : 0;
  const designsCount = Array.isArray(receivedDesigns) ? receivedDesigns.length : 0;
  const projectsCount = Array.isArray(myProjects) ? myProjects.length : 0;
  const contractorReqCount = Array.isArray(contractorRequests) ? contractorRequests.length : 0;
  const libraryCount = Array.isArray(layoutLibrary) ? layoutLibrary.length : 0;

  // Background refresh for sidebar counts (does not toggle main loading state)
  useEffect(() => {
    let mounted = true;
    const refreshCounts = async () => {
      try {
        // My Requests
        const r1 = await fetch('/buildhub/backend/api/homeowner/get_my_requests.php');
        const j1 = await r1.json().catch(() => ({}));
        if (mounted && j1?.success) {
          const reqs = Array.isArray(j1.requests) ? j1.requests : [];
          setLayoutRequests(reqs.filter(r => r.status !== 'deleted'));
        }
      } catch { }
      try {
        // Contractor Requests
        const r2 = await fetch('/buildhub/backend/api/homeowner/get_contractor_requests.php');
        const j2 = await r2.json().catch(() => ({}));
        if (mounted && j2?.success) {
          const reqs = Array.isArray(j2.requests) ? j2.requests : [];
          setContractorRequests(reqs.filter(r => r.status !== 'deleted'));
        }
      } catch { }
      try {
        // Received Designs
        const r3 = await fetch('/buildhub/backend/api/homeowner/get_received_designs.php', { credentials: 'include' });
        const j3 = await r3.json().catch(() => ({}));
        if (mounted && j3?.success) {
          setReceivedDesigns(Array.isArray(j3.designs) ? j3.designs : []);
        }
      } catch { }
      try {
        // Projects
        const r4 = await fetch('/buildhub/backend/api/homeowner/get_my_projects.php');
        const j4 = await r4.json().catch(() => ({}));
        if (mounted && j4?.success) {
          setMyProjects(Array.isArray(j4.projects) ? j4.projects : []);
        }
      } catch { }
      try {
        // Layout Library
        const r5 = await fetch('/buildhub/backend/api/homeowner/get_layout_library.php', { credentials: 'include' });
        const j5 = await r5.json().catch(() => ({}));
        if (mounted && j5?.success) {
          setLayoutLibrary(Array.isArray(j5.layouts) ? j5.layouts : []);
        }
      } catch { }
    };

    // Initial immediate refresh
    refreshCounts();
    // Poll every 60s
    const id = setInterval(refreshCounts, 60000);
    return () => { mounted = false; clearInterval(id); };
  }, []);

  // Extract sqft from various possible fields provided by architect
  const getDesignSqft = (design) => {
    const direct = Number(design?.sqft || design?.area || 0);
    if (direct > 0) return direct;
    // Try nested technical details structures
    const td = design?.technical_details || design?.technicalDetails || {};
    const tdSqft = Number(td?.sqft || td?.area_sqft || td?.total_sqft || td?.totalSqft || 0);
    if (tdSqft > 0) return tdSqft;
    // Try metadata-like fields
    const meta = design?.meta || {};
    const metaSqft = Number(meta?.sqft || meta?.area_sqft || 0);
    if (metaSqft > 0) return metaSqft;
    return 0;
  };

  // Pricing: use architect-set price if available, otherwise fallback to calculated price
  const calculateDesignPrice = (design) => {
    // If architect has set a specific price, use that
    if (design?.view_price && design.view_price > 0) {
      return parseFloat(design.view_price);
    }

    // Fallback to calculated price based on sqft
    const sqft = getDesignSqft(design);
    const base = 8000;
    const variable = sqft > 0 ? sqft * 10 : 0;
    return base + variable;
  };

  // Initiate payment with Razorpay
  const handlePayToView = async (design) => {
    setPaymentError('');
    setPaymentLoading(true);
    setPayingDesignId(design.id);
    try {
      // Request order from backend
      const response = await fetch('/buildhub/backend/api/homeowner/initiate_layout_payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          design_id: design.id,
          amount_override: calculateDesignPrice(design) // in rupees; backend can convert to paise
        })
      });
      const result = await response.json();
      if (!result?.success) {
        setPaymentError(result?.message || 'Failed to initiate payment');
        return;
      }

      const options = {
        key: result.razorpay_key_id,
        amount: result.amount, // in paise
        currency: result.currency || 'INR',
        name: 'BuildHub',
        description: `View layout: ${result.design_title || design.title || design.design_title || 'Layout'}`,
        order_id: result.razorpay_order_id,
        handler: async function (rzpRes) {
          try {
            const verifyRes = await fetch('/buildhub/backend/api/homeowner/verify_layout_payment.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              credentials: 'include',
              body: JSON.stringify({
                razorpay_payment_id: rzpRes.razorpay_payment_id,
                razorpay_order_id: rzpRes.razorpay_order_id,
                razorpay_signature: rzpRes.razorpay_signature,
                payment_id: result.payment_id,
                design_id: design.id
              })
            });
            const verifyJson = await verifyRes.json();
            if (verifyJson?.success) {
              // Refresh designs so payment_status is updated
              const r3 = await fetch('/buildhub/backend/api/homeowner/get_received_designs.php', { credentials: 'include' });
              const j3 = await r3.json().catch(() => ({}));
              if (j3?.success) setReceivedDesigns(Array.isArray(j3.designs) ? j3.designs : []);
              // Persist unlock locally so refresh retains access
              setUnlockedDesignIds(prev => {
                const next = { ...prev, [design.id]: true };
                try {
                  const current = JSON.parse(localStorage.getItem('bh_paid_layouts') || '[]');
                  const set = new Set(Array.isArray(current) ? current : []);
                  set.add(design.id);
                  localStorage.setItem('bh_paid_layouts', JSON.stringify(Array.from(set)));
                } catch { }
                return next;
              });
              setPaymentError('');
            } else {
              setPaymentError(verifyJson?.message || 'Payment verification failed');
            }
          } catch {
            setPaymentError('Payment verification failed');
          }
        },
        prefill: {
          name: `${user?.first_name || ''} ${user?.last_name || ''}`.trim() || undefined,
          email: user?.email
        },
        theme: { color: '#2563eb' }
      };

      const rzp = new window.Razorpay(options);
      rzp.open();
    } catch {
      setPaymentError('Network error during payment');
    } finally {
      setPaymentLoading(false);
      setPayingDesignId(null);
    }
  };

  // Helper: has paid access
  const hasPaidAccess = (design) => {
    // Treat any explicit completed status as paid
    if (design?.payment_status === 'completed') return true;
    // If backend marks unlocked flag
    if (design?.unlocked === true) return true;
    // If we unlocked locally after a successful verification
    if (unlockedDesignIds?.[design?.id]) return true;
    return false;
  };

  // Handle payment for unlocking technical details of house plans
  const handlePayToUnlockTechnicalDetails = async (housePlan) => {
    setPaymentError('');
    setPaymentLoading(true);
    setPayingDesignId(housePlan.id);
    
    try {
      // Wait for Razorpay to be available
      let attempts = 0;
      while ((!window.Razorpay || window._razorpayLoading) && attempts < 20) {
        await new Promise(resolve => setTimeout(resolve, 100));
        attempts++;
      }

      if (!window.Razorpay) {
        setPaymentError('Payment system not loaded. Please refresh the page and try again.');
        return;
      }

      // Request order from backend
      const response = await fetch('/buildhub/backend/api/homeowner/initiate_technical_details_payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          house_plan_id: housePlan.house_plan_id
        })
      });
      
      const result = await response.json();
      if (!result?.success) {
        setPaymentError(result?.message || 'Failed to initiate payment');
        return;
      }

      const options = {
        key: result.razorpay_key_id,
        amount: result.amount, // in paise
        currency: result.currency || 'INR',
        name: 'BuildHub',
        description: result.description || `Unlock Technical Details: ${housePlan.design_title}`,
        order_id: result.razorpay_order_id,
        handler: async function (rzpRes) {
          try {
            const verifyRes = await fetch('/buildhub/backend/api/homeowner/verify_technical_details_payment.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              credentials: 'include',
              body: JSON.stringify({
                razorpay_payment_id: rzpRes.razorpay_payment_id,
                razorpay_order_id: rzpRes.razorpay_order_id,
                razorpay_signature: rzpRes.razorpay_signature,
                payment_id: result.payment_id
              })
            });
            
            const verifyJson = await verifyRes.json();
            if (verifyJson?.success) {
              // Refresh designs to get updated payment status
              await fetchReceivedDesigns();
              setPaymentError('');
              toast?.success('Technical details unlocked successfully!');
            } else {
              setPaymentError(verifyJson?.message || 'Payment verification failed');
            }
          } catch (error) {
            console.error('Payment verification error:', error);
            setPaymentError('Payment verification failed');
          }
        },
        prefill: {
          name: `${user?.first_name || ''} ${user?.last_name || ''}`.trim() || undefined,
          email: user?.email
        },
        theme: { color: '#2563eb' }
      };

      try {
        const rzp = new window.Razorpay(options);
        rzp.open();
      } catch (error) {
        console.error('Razorpay error:', error);
        setPaymentError('Failed to open payment gateway. Please try again.');
      }
    } catch (error) {
      console.error('Payment initiation error:', error);
      setPaymentError('Network error during payment');
    } finally {
      setPaymentLoading(false);
      setPayingDesignId(null);
    }
  };

  // Architect assignment state
  const [architects, setArchitects] = useState([]);
  const [archLoading, setArchLoading] = useState(false);
  const [archError, setArchError] = useState('');
  // Architect filters state (must be declared before effects that reference them)
  const [archSearch, setArchSearch] = useState('');
  const [archSpec, setArchSpec] = useState('');
  const [archMinExp, setArchMinExp] = useState('');
  // Debounced fetch for specialization input
  const archSpecRef = useRef('');
  useEffect(() => { archSpecRef.current = archSpec; }, [archSpec]);
  useEffect(() => {
    const id = setTimeout(() => {
      const spec = archSpecRef.current;
      if (typeof spec === 'string' && spec.trim().length > 0) {
        fetchArchitects({ status: 'approved', search: archSearch, specialization: spec, min_experience: archMinExp });
      }
    }, 350);
    return () => clearTimeout(id);
  }, [archSpec, archSearch, archMinExp]);
  const [showArchitectDetails, setShowArchitectDetails] = useState(false);
  const [architectForDetails, setArchitectForDetails] = useState(null);
  const [architectReviews, setArchitectReviews] = useState([]);
  const [architectReviewsLoading, setArchitectReviewsLoading] = useState(false);


  const [archStepDone, setArchStepDone] = useState(false);
  const [selectedRequestForAssign, setSelectedRequestForAssign] = useState(null);
  const [selectedArchitectId, setSelectedArchitectId] = useState([]);
  const [assignMessage, setAssignMessage] = useState('');

  // Support / Help modal state
  const [showSupportModal, setShowSupportModal] = useState(false);
  const [supportForm, setSupportForm] = useState({ subject: '', category: 'general', message: '' });
  const [supportLoading, setSupportLoading] = useState(false);
  const [supportIssues, setSupportIssues] = useState([]);
  const [selectedIssue, setSelectedIssue] = useState(null);
  const [issueReplies, setIssueReplies] = useState([]);
  const [showReportsList, setShowReportsList] = useState(false);

  // Add Layout modal state
  const [showAddLayoutModal, setShowAddLayoutModal] = useState(false);
  const [addLayoutForm, setAddLayoutForm] = useState({ title: '', layoutType: '', bedrooms: '', bathrooms: '', area: '', priceRange: '', description: '', previewImage: null, layoutFile: null });

  // 3D computed plan from finalized design (fallback to placeholder)
  const [computed3DRooms, setComputed3DRooms] = useState([]);
  const [computed3DWalls, setComputed3DWalls] = useState([]);

  // Image/File viewer state
  const [viewer, setViewer] = useState({ open: false, src: '', title: '' });

  // Contractor selection state
  const [showContractorModal, setShowContractorModal] = useState(false);
  const [contractors, setContractors] = useState([]);
  const [contractorLoading, setContractorLoading] = useState(false);
  const [contractorError, setContractorError] = useState('');
  const [selectedContractors, setSelectedContractors] = useState([]);
  const [contractorMessage, setContractorMessage] = useState('');
  const [sendingToContractor, setSendingToContractor] = useState(false);
  const [sourceDesignForContractor, setSourceDesignForContractor] = useState(null); // when opened from Received Designs

  // Technical details modal state
  const [technicalDetailsModal, setTechnicalDetailsModal] = useState(null);

  // Profile dropdown outside-click handler (top header)
  const profileRef = useRef(null);
  useEffect(() => {
    const onDocClick = (e) => {
      if (profileRef.current && !profileRef.current.contains(e.target)) {
        setProfileMenuOpen(false);
      }
    };
    const onKey = (e) => { if (e.key === 'Escape') { setProfileMenuOpen(false); } };
    document.addEventListener('mousedown', onDocClick);
    document.addEventListener('keydown', onKey);
    return () => { document.removeEventListener('mousedown', onDocClick); document.removeEventListener('keydown', onKey); };
  }, []);

  useEffect(() => {
    // Get user data from session
    const userData = JSON.parse(sessionStorage.getItem('user') || '{}');
    console.log('User data loaded:', userData);
    setUser(userData);
  }, []);

  useEffect(() => {
    console.log('Profile menu open state changed:', profileMenuOpen);
  }, [profileMenuOpen]);

  // Load notifications and messages periodically
  useEffect(() => {
    if (user?.id) {
      console.log('Loading notifications for user:', user.id);
      // Notifications are now handled by NotificationSystem component
    }
  }, [user?.id]);

  useEffect(() => {
    import('../utils/session').then(({ preventCache, verifyServerSession }) => {
      preventCache();
      (async () => {
        const userData = JSON.parse(sessionStorage.getItem('user') || '{}');
        const serverAuth = await verifyServerSession();
        if (!userData.id || userData.role !== 'homeowner' || !serverAuth) {
          sessionStorage.removeItem('user');
          localStorage.removeItem('bh_user');
          navigate('/login', { replace: true });
          return;
        }
        // Only load data when tabs are actually clicked
      })();
    });
  }, []);

  // Support helpers
  const loadSupportIssues = async () => {
    try {
      const res = await fetch('/buildhub/backend/api/support/get_issues.php', { credentials: 'include' });
      const json = await res.json();
      if (json.success) {
        setSupportIssues(json.issues || []);
      }
    } catch { }
  };

  const openIssueThread = async (issueId) => {
    try {
      const res = await fetch(`/buildhub/backend/api/support/get_issues.php?issue_id=${issueId}`, { credentials: 'include' });
      const json = await res.json();
      if (json.success) {
        setSelectedIssue(json.issue);
        setIssueReplies(json.replies || []);
      }
    } catch { }
  };

  const submitSupportIssue = async () => {
    if (!supportForm.subject.trim() || !supportForm.message.trim()) return;
    setSupportLoading(true);
    try {
      const res = await fetch('/buildhub/backend/api/support/create_issue.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(supportForm)
      });
      const json = await res.json();
      if (json.success) {
        setSupportForm({ subject: '', category: 'general', message: '' });
        setSuccess('Issue reported to admin');
        await loadSupportIssues();
        if (json.issue_id) {
          await openIssueThread(json.issue_id);
        } else {
          // If no id returned, default to list view
          setSelectedIssue(null);
        }
      } else {
        setError(json.message || 'Failed to submit issue');
      }
    } catch {
      setError('Network error submitting issue');
    } finally {
      setSupportLoading(false);
    }
  };

  const handleLogout = async () => {
    try { await fetch('/buildhub/backend/api/logout.php', { method: 'POST', credentials: 'include' }); } catch { }
    localStorage.removeItem('bh_user');
    sessionStorage.removeItem('user');
    navigate('/login', { replace: true });
  };

  const fetchMyRequests = async () => {
    setLoading(true);
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/get_my_requests.php');
      const result = await response.json();
      if (result.success) {
        const reqs = Array.isArray(result.requests) ? result.requests : [];
        setLayoutRequests(reqs.filter(r => r.status !== 'deleted'));
      }
    } catch (error) {
      console.error('Error fetching requests:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchMyProjects = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/get_my_projects.php');
      const result = await response.json();
      if (result.success) {
        setMyProjects(result.projects || []);
      }
    } catch (error) {
      console.error('Error fetching projects:', error);
    }
  };

  const fetchContractorRequests = async () => {
    try {
      setLoading(true);
      const response = await fetch('/buildhub/backend/api/homeowner/get_contractor_requests.php');
      const result = await response.json();
      if (result.success) {
        const reqs = Array.isArray(result.requests) ? result.requests : [];
        // Exclude deleted contractor requests from the list
        setContractorRequests(reqs.filter(r => r.status !== 'deleted'));
      }
    } catch (error) {
      console.error('Error fetching contractor requests:', error);
    } finally {
      setLoading(false);
    }
  };

  // Persistently remove a request (soft-delete via backend)
  const removeRequest = async (requestId) => {
    if (!requestId) return;
    try {
      const res = await fetch('/buildhub/backend/api/homeowner/delete_request.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ layout_request_id: requestId })
      });
      const json = await res.json();
      if (json.success) {
        setLayoutRequests(prev => prev.filter(r => r.id !== requestId));
        setContractorRequests(prev => prev.filter(r => r.id !== requestId));
        setSuccess('Request removed');
      } else {
        setError(json.message || 'Failed to remove request');
      }
    } catch (e) {
      setError('Error removing request');
    }
  };

  const fetchLayoutLibrary = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/get_layout_library.php');
      const result = await response.json();
      if (result.success) {
        setLayoutLibrary(result.layouts || []);
      }
    } catch (error) {
      console.error('Error fetching layout library:', error);
    }
  };

  const handleAddLayout = async (e) => {
    e.preventDefault();
    // Placeholder: send to backend
    // For now, just close modal
    setShowAddLayoutModal(false);
    setAddLayoutForm({ title: '', layoutType: '', bedrooms: '', bathrooms: '', area: '', priceRange: '', description: '', previewImage: null, layoutFile: null });
    setSuccess('Layout added to library (placeholder)');
  };

  const fetchReceivedDesigns = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/get_received_designs.php', {
        credentials: 'include'
      });
      const result = await response.json();
      if (result.success) {
        const designs = result.designs || [];
        setReceivedDesigns(designs);
        const finalized = designs.find(d => d.status === 'finalized');
        if (finalized) {
          hydrate3DFromDesign(finalized);
        } else {
          setComputed3DRooms(defaultRooms());
          setComputed3DWalls(defaultWalls());
        }
      }
    } catch (e) {
      console.error('Error fetching designs:', e);
    }
  };

  // Helpers for file type checks used in library previews
  const isImageUrl = (url) => /\.(png|jpe?g|gif|webp|bmp|svg|heic)$/i.test(url || '');
  const isPdfUrl = (url) => /\.(pdf)$/i.test(url || '');

  const handleDeleteDesign = async (designId) => {
    if (!designId) return;
    
    // Check if this is a house plan (ID starts with 'hp_')
    const isHousePlan = typeof designId === 'string' && designId.startsWith('hp_');
    
    try {
      if (isHousePlan) {
        // Extract the actual house plan ID from the prefixed ID (hp_123 -> 123)
        const housePlanId = designId.replace('hp_', '');
        
        const res = await fetch('/buildhub/backend/api/homeowner/delete_house_plan.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ house_plan_id: parseInt(housePlanId) })
        });
        const json = await res.json();
        
        if (json.success) {
          setReceivedDesigns(prev => prev.filter(d => d.id !== designId));
          setSuccess('House plan deleted successfully');
        } else {
          setError(json.message || 'Failed to delete house plan');
        }
      } else {
        // Handle regular design deletion
        const res = await fetch('/buildhub/backend/api/homeowner/delete_design.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ design_id: designId })
        });
        const json = await res.json();
        
        if (json.success) {
          setReceivedDesigns(prev => prev.filter(d => d.id !== designId));
          setSuccess('Design deleted successfully');
        } else {
          setError(json.message || 'Failed to delete design');
        }
      }
    } catch (e) {
      setError('Error deleting ' + (isHousePlan ? 'house plan' : 'design'));
    }
  };

  const confirmDeleteDesign = (designId, designTitle) => {
    const isHousePlan = typeof designId === 'string' && designId.startsWith('hp_');
    const itemType = isHousePlan ? 'house plan' : 'design';
    
    if (window.confirm(`Are you sure you want to delete this ${itemType}?\n\n"${designTitle}"\n\nThis action cannot be undone.${isHousePlan ? '\n\nNote: This will also delete all associated files and payment records.' : ''}`)) {
      handleDeleteDesign(designId);
    }
  };

  const updateSelection = async (designId, action) => {
    try {
      const res = await fetch('/buildhub/backend/api/homeowner/update_design_selection.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ design_id: designId, action })
      });
      const json = await res.json();
      if (json.success) {
        setSuccess(action === 'finalize' ? 'Design finalized' : action === 'shortlist' ? 'Added to shortlist' : 'Removed from shortlist');
        await fetchReceivedDesigns();
        // Removed auto-switch to 3D tab
      } else {
        setError(json.message || 'Failed to update selection');
      }
    } catch (e) {
      setError('Error updating selection');
    }
  };

  const fetchComments = async (designId) => {
    try {
      const res = await fetch(`/buildhub/backend/api/comments/get_comments.php?design_id=${designId}`);
      const json = await res.json();
      if (json.success) setComments(prev => ({ ...prev, [designId]: json.comments }));
    } catch { }
  };

  const postComment = async (designId) => {
    const text = (commentDrafts[designId] || '').trim();
    const rating = Number(commentRatings[designId] || 0);
    if (!text) return;
    try {
      // 1) Post design comment
      const res = await fetch('/buildhub/backend/api/comments/post_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ design_id: designId, message: text })
      });
      const json = await res.json();

      // 2) Also post a review with optional rating
      const design = receivedDesigns.find(d => d.id === designId);
      if (design?.architect_id) {
        try {
          await fetch('/buildhub/backend/api/reviews/post_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ architect_id: design.architect_id, design_id: designId, rating: rating > 0 ? rating : 5, comment: text })
          });
        } catch { }
      }

      if (json.success) {
        setCommentDrafts(prev => ({ ...prev, [designId]: '' }));
        setCommentRatings(prev => ({ ...prev, [designId]: 0 }));
        fetchComments(designId);
        setSuccess('Comment & review posted');
        // Re-hydrate 3D if commenting on finalized design
        const isFinal = (receivedDesigns.find(d => d.id === designId)?.status === 'finalized');
        if (isFinal) hydrate3DFromDesign(receivedDesigns.find(d => d.id === designId));
      } else {
        setError(json.message || 'Failed to post comment');
      }
    } catch {
      setError('Error posting comment');
    }
  };

  // Architect directory + assignment
  const fetchArchitects = async (params = {}) => {
    setArchLoading(true);
    setArchError('');
    try {
      const q = new URLSearchParams({
        ...(params.search ? { search: params.search } : {}),
        ...(params.specialization ? { specialization: params.specialization } : {}),
        ...(params.min_experience ? { min_experience: params.min_experience } : {}),
        ...(params.layout_request_id ? { layout_request_id: params.layout_request_id } : {}),
      }).toString();
      const response = await fetch(`/buildhub/backend/api/homeowner/get_architects.php${q ? `?${q}` : ''}`, { credentials: 'include' });
      // If backend supports status filter, ensure approved only by default
      // else we will filter client-side below
      const result = await response.json();
      if (result.success) {
        const list = result.architects || [];
        setArchitects(list);
      } else {
        setArchError(result.message || 'Failed to load architects');
      }
    } catch (e) {
      setArchError('Error loading architects');
    } finally {
      setArchLoading(false);
    }
  };

  const openArchitectDetails = async (architect) => {
    setArchitectForDetails(architect);
    setShowArchitectDetails(true);
    setArchitectReviewsLoading(true);
    setArchitectReviews([]);
    try {
      if (architect?.id) {
        const r = await fetch(`/buildhub/backend/api/reviews/get_reviews.php?architect_id=${architect.id}`);
        const rj = await r.json();
        if (rj.success) {
          setArchitectReviews(Array.isArray(rj.reviews) ? rj.reviews : []);
        }
      }
    } catch { }
    finally { setArchitectReviewsLoading(false); }
  };

  // Contractor directory + selection
  const fetchContractors = async (params = {}) => {
    setContractorLoading(true);
    setContractorError('');
    try {
      const q = new URLSearchParams({
        ...(params.search ? { search: params.search } : {}),
        ...(params.specialization ? { specialization: params.specialization } : {}),
        ...(params.min_experience ? { min_experience: params.min_experience } : {}),
      }).toString();
      const response = await fetch(`/buildhub/backend/api/homeowner/get_contractors.php${q ? `?${q}` : ''}`, { credentials: 'include' });
      const result = await response.json();
      if (result.success) {
        setContractors(result.contractors || []);
      } else {
        setContractorError(result.message || 'Failed to load contractors');
      }
    } catch (e) {
      setContractorError('Error loading contractors');
    } finally {
      setContractorLoading(false);
    }
  };

  const openArchitectModal = (request) => {
    setSelectedRequestForAssign(request);
    setSelectedArchitectId(null);
    setAssignMessage('');
    setShowArchitectModal(true);
    // Pass layout_request_id so backend can mark already assigned architects
    fetchArchitects({ status: 'approved', search: archSearch, specialization: archSpec, min_experience: archMinExp, layout_request_id: request?.id });
  };

  const openContractorModal = (layout) => {
    setSelectedLibraryLayout(layout);
    setSelectedContractors([]);
    setContractorMessage('');
    setShowContractorModal(true);
    fetchContractors();
  };

  // From a received design: resolve related library layout and open modal
  const openSendToContractorFromDesign = (design) => {
    setSourceDesignForContractor(design || null);
    const layoutId = design?.selected_layout_id;
    // Immediately set minimal selection so sending works without waiting for library load
    if (layoutId) {
      setSelectedLibraryLayout({ id: layoutId, title: 'Selected Layout' });
    } else {
      setSelectedLibraryLayout(null);
    }
    setSelectedContractors([]);
    setShowContractorModal(true);
    fetchContractors();

    // Try to enrich with full layout details for the modal display
    const ensureFullLayout = async () => {
      if (!layoutId) return;
      // If already in memory with full details, use it
      if (Array.isArray(layoutLibrary) && layoutLibrary.length > 0) {
        const match = layoutLibrary.find(l => Number(l.id) === Number(layoutId));
        if (match) {
          setSelectedLibraryLayout(match);
          return;
        }
      }
      // Otherwise, fetch the library and find the item
      try {
        const res = await fetch('/buildhub/backend/api/homeowner/get_layout_library.php', { credentials: 'include' });
        const json = await res.json();
        if (json?.success && Array.isArray(json.layouts)) {
          const match = json.layouts.find(l => Number(l.id) === Number(layoutId));
          if (match) setSelectedLibraryLayout(match);
        }
      } catch (_) { /* ignore, minimal selection already set */ }
    };
    ensureFullLayout();
  };

  const sendToContractor = async () => {
    const layoutIdToSend = selectedLibraryLayout?.id || sourceDesignForContractor?.selected_layout_id;
    if (!selectedContractors || selectedContractors.length === 0) {
      setError('Please select at least one contractor');
      return;
    }
    
    // Check if we're sending a house plan
    const isHousePlan = sourceDesignForContractor?.source_type === 'house_plan';
    
    // Allow send without layout if we have a forwarded design bundle or house plan
    const canSendWithoutLayout = !!sourceDesignForContractor;
    if (!layoutIdToSend && !canSendWithoutLayout) {
      setError('Please select a layout to send');
      return;
    }

    setSendingToContractor(true);
    const successMessages = [];
    const errorMessages = [];

    try {
      // Send to all selected contractors
      for (const contractor of selectedContractors) {
        try {
          let response, result;
          
          if (isHousePlan) {
            // Use house plan API
            const housePlanData = {
              house_plan_id: sourceDesignForContractor.house_plan_id,
              plan_name: sourceDesignForContractor.design_title,
              plot_dimensions: sourceDesignForContractor.plot_dimensions,
              total_area: sourceDesignForContractor.total_area,
              technical_details: sourceDesignForContractor.technical_details,
              plan_data: sourceDesignForContractor.plan_data,
              architect_info: sourceDesignForContractor.architect,
              layout_images: sourceDesignForContractor.files?.filter(f => f.type === 'layout_image') || [],
              notes: sourceDesignForContractor.description
            };
            
            response = await fetch('/buildhub/backend/api/homeowner/send_house_plan_to_contractor.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              credentials: 'include',
              body: JSON.stringify({
                contractor_id: contractor.id,
                house_plan_data: housePlanData,
                message: ''
              })
            });
          } else {
            // Use regular design API
            response = await fetch('/buildhub/backend/api/homeowner/send_to_contractor.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              credentials: 'include',
              body: JSON.stringify({
                layout_id: layoutIdToSend || null,
                contractor_id: contractor.id,
                homeowner_id: user?.id,
                contractor_message: '',
                forwarded_design: sourceDesignForContractor ? {
                  id: sourceDesignForContractor.id,
                  title: sourceDesignForContractor.design_title,
                  description: sourceDesignForContractor.description,
                  files: Array.isArray(sourceDesignForContractor.files) ? sourceDesignForContractor.files : [],
                  technical_details: sourceDesignForContractor.technical_details || null,
                  created_at: sourceDesignForContractor.created_at
                } : null,
                plot_size: selectedLibraryLayout?.plot_size || requestData.plot_size || null,
                building_size: selectedLibraryLayout?.building_size || requestData.building_size || null
              })
            });
          }

          result = await response.json();
          if (result.success) {
            successMessages.push(result.contractor_name || contractor.first_name);
          } else {
            errorMessages.push(`${contractor.first_name}: ${result.message || 'Failed'}`);
          }
        } catch (error) {
          errorMessages.push(`${contractor.first_name}: Network error`);
        }
      }

      // Show success/error messages
      if (successMessages.length > 0) {
        const itemType = isHousePlan ? 'House plan' : 'Layout';
        setSuccess(`${itemType} sent to ${successMessages.length} contractor(s): ${successMessages.join(', ')}`);
        setShowContractorModal(false);
        setSelectedContractors([]);
        setSelectedLibraryLayout(null);
        setSourceDesignForContractor(null);
        // Refresh the requests to show the new entry
        fetchMyRequests();
        // Auto-hide success message after 5 seconds
        setTimeout(() => setSuccess(''), 5000);
      }

      if (errorMessages.length > 0) {
        setError(errorMessages.join('; '));
      }

      if (successMessages.length === 0 && errorMessages.length > 0) {
        setError('Failed to send to any contractors: ' + errorMessages.join('; '));
      }
    } catch (error) {
      setError('Network error. Please try again.');
    } finally {
      setSendingToContractor(false);
    }
  };

  // Map a finalized design (images/PDF or JSON-like description) to a simple 3D plan.
  // Priority: 1) JSON in description; 2) filename keywords; 3) defaults.
  const hydrate3DFromDesign = (design) => {
    const fallbackRooms = defaultRooms();
    const fallbackWalls = defaultWalls();

    // 1) Try JSON in description (e.g., your example payload)
    const desc = (design?.description || '').trim();
    if (desc.startsWith('{') || desc.startsWith('[')) {
      try {
        const attrs = JSON.parse(desc);
        const fromAttrs = computePlanFromAttributes(attrs);
        if (fromAttrs && Array.isArray(fromAttrs.rooms) && Array.isArray(fromAttrs.walls)) {
          setComputed3DRooms(fromAttrs.rooms.length ? fromAttrs.rooms : fallbackRooms);
          setComputed3DWalls(fromAttrs.walls.length ? fromAttrs.walls : fallbackWalls);
          return;
        }
      } catch (_) { /* ignore and fallback */ }
    }

    // 1b) Try JSON from layout_json field (preferred)
    if (design?.layout_json) {
      try {
        const attrs = JSON.parse(design.layout_json);
        const fromAttrs = computePlanFromAttributes(attrs);
        if (fromAttrs && Array.isArray(fromAttrs.rooms) && Array.isArray(fromAttrs.walls)) {
          setComputed3DRooms(fromAttrs.rooms.length ? fromAttrs.rooms : fallbackRooms);
          setComputed3DWalls(fromAttrs.walls.length ? fromAttrs.walls : fallbackWalls);
          return;
        }
      } catch (_) { /* ignore and fallback */ }
    }

    // 2) Heuristic via filenames
    const files = Array.isArray(design?.files) ? design.files : [];
    if (!files.length) {
      setComputed3DRooms(fallbackRooms);
      setComputed3DWalls(fallbackWalls);
      return;
    }

    const names = files.map(f => (f.original || f.stored || '').toLowerCase());
    const has = (kw) => names.some(n => n.includes(kw));

    const rooms = [];
    if (has('living')) rooms.push({ name: 'Living Room', position: [-1.4, 0, 1.0], size: [2.8, 2.0], color: '#eef6ff' });
    if (has('kitchen')) rooms.push({ name: 'Kitchen', position: [1.5, 0, 1.0], size: [2.4, 2.0], color: '#fff6e9' });
    if (has('bed') || has('master')) rooms.push({ name: 'Bedroom', position: [-1.6, 0, -1.0], size: [2.6, 1.8], color: '#f3e8ff' });
    if (has('bath') || has('toilet') || has('wc')) rooms.push({ name: 'Bath', position: [1.2, 0, -1.0], size: [2.0, 1.2], color: '#fdecec' });
    if (has('hall') || has('corridor') || has('foyer')) rooms.push({ name: 'Hallway', position: [1.2, 0, -2.1], size: [2.0, 0.6], color: '#ecfdf5' });

    const finalRooms = rooms.length ? rooms : fallbackRooms;
    const walls = [
      { position: [0, 0.175, 2.25], rotation: [0, 0, 0], length: 6.2 },
      { position: [0, 0.175, -2.25], rotation: [0, 0, 0], length: 6.2 },
      { position: [-3.1, 0.175, 0], rotation: [0, Math.PI / 2, 0], length: 4.5 },
      { position: [3.1, 0.175, 0], rotation: [0, Math.PI / 2, 0], length: 4.5 },
      { position: [0.15, 0.175, 0], rotation: [0, Math.PI / 2, 0], length: 4.2 },
      { position: [-0.25, 0.175, -2.0], rotation: [0, 0, 0], length: 4.0 },
      { position: [2.2, 0.175, -1.6], rotation: [0, Math.PI / 2, 0], length: 1.8 },
    ];

    setComputed3DRooms(finalRooms);
    setComputed3DWalls(walls);
  };

  // Empty default rooms/walls to be populated with real data from API
  const defaultRooms = () => ([]);
  const defaultWalls = () => ([]);

  // Compute plan from attributes or structured JSON
  const computePlanFromAttributes = (attrs) => {
    // If structured rooms present, map them precisely
    if (attrs && Array.isArray(attrs.rooms)) {
      const scale = Number(attrs.scale || 1);
      const rooms = attrs.rooms.map((r, idx) => ({
        name: r.name || `Room ${idx + 1}`,
        position: [Number(r.x || 0) * scale, 0, Number(r.z || 0) * scale],
        size: [Number(r.width || 2) * scale, Number(r.depth || 2) * scale],
        color: r.color || undefined,
      }));
      const walls = Array.isArray(attrs.walls) ? attrs.walls.map((w) => ({
        position: [Number(w.x || 0) * scale, 0.175, Number(w.z || 0) * scale],
        rotation: [0, degToRad(Number(w.rotation || 0)), 0],
        length: Number(w.length || 2) * scale,
        thickness: Number(w.thickness || 0.06) * (scale > 0 ? scale : 1),
        height: Number(w.height || 0.35) * (scale > 0 ? scale : 1),
      })) : [];
      return { rooms, walls };
    }

    // Fallback: infer from high-level attributes (e.g., "3 bhk")
    const roomsText = (attrs?.rooms || '').toString().toLowerCase();
    const is3Bhk = roomsText.includes('3') || roomsText.includes('3bhk') || roomsText.includes('3-bhk');

    const style = (attrs?.aesthetic || '').toString().toLowerCase();
    const modern = style.includes('modern');

    // Return empty rooms and walls until real data is available from API
    return { rooms: defaultRooms(), walls: defaultWalls() };
  };

  const degToRad = (deg) => (deg * Math.PI) / 180;

  const handleAssignArchitect = async () => {
    if (!selectedRequestForAssign) {
      setArchError('No request selected');
      return;
    }
    // Collect selected architect IDs (multi-select)
    const selectedIds = Array.isArray(selectedArchitectId)
      ? selectedArchitectId
      : (selectedArchitectId ? [selectedArchitectId] : []);
    if (selectedIds.length === 0) {
      setArchError('Please select at least one architect');
      return;
    }
    try {
      setArchLoading(true);
      // Guard: require layout_request_id
      if (!selectedRequestForAssign?.id) {
        setArchError('Please select a request first');
        setArchLoading(false);
        return;
      }
      const response = await fetch('/buildhub/backend/api/homeowner/assign_architect.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          layout_request_id: selectedRequestForAssign.id,
          architect_ids: selectedIds,
          message: assignMessage
        })
      });
      const result = await response.json();
      if (result.success) {
        setSuccess('Request sent to selected architect(s)');
        setShowArchitectModal(false);
        setSelectedRequestForAssign(null);
        setSelectedArchitectId(null);
      } else {
        setArchError(result.message || 'Failed to send request');
      }
    } catch (e) {
      setArchError('Error sending request');
    } finally {
      setArchLoading(false);
    }
  };

  const handleRequestSubmit = async (e) => {
    e.preventDefault();
    if (!requestData.plot_size || !requestData.budget_range || !requestData.requirements) {
      setError('Please fill all required fields');
      return;
    }

    // Handle custom budget
    if (requestData.budget_range === 'Custom' && !requestData.custom_budget) {
      setError('Please enter a custom budget amount');
      return;
    }

    try {
      setLoading(true);

      // Prepare data for submission
      const submitData = {
        ...requestData,
        budget_range: requestData.budget_range === 'Custom' ? requestData.custom_budget : requestData.budget_range
      };

      const response = await fetch('/buildhub/backend/api/homeowner/submit_request.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(submitData)
      });

      const result = await response.json();
      if (result.success) {
        // If architect(s) selected, assign immediately
        const selIds = Array.isArray(selectedArchitectId) ? selectedArchitectId : (selectedArchitectId ? [selectedArchitectId] : []);
        if (selIds.length > 0) {
          try {
            await fetch('/buildhub/backend/api/homeowner/assign_architect.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              credentials: 'include',
              body: JSON.stringify({
                layout_request_id: result.request_id,
                architect_ids: selIds,
                message: assignMessage || 'Custom design request from dashboard'
              })
            });
          } catch (e) { /* ignore assignment errors here */ }
        }
        setSuccess(`Your layout request has been submitted successfully! ${selIds.length > 0 ? 'It has been assigned to the selected architect(s).' : 'You can assign it to architects from the Requests tab.'}`);
        setShowRequestForm(false);
        setSelectedArchitectId(null);
        setAssignMessage('');
        setRequestData({
          plot_size: '',
          plot_shape: '',
          topography: '',
          development_laws: '',
          family_needs: [], // Reset to empty array
          rooms: [], // Reset to empty array
          budget_range: '',
          aesthetic: '',
          requirements: '',
          location: '',
          timeline: '',
          selected_layout_id: null,
          layout_type: 'custom',
          custom_budget: '' // Reset custom budget
        });
        fetchMyRequests();
      } else {
        setError('Failed to submit request: ' + result.message);
      }
    } catch (error) {
      setError('Error submitting request');
    } finally {
      setLoading(false);
    }
  };

  const handleSelectFromLibrary = (layout) => {
    setSelectedLibraryLayout(layout);
    setRequestData({
      ...requestData,
      selected_layout_id: layout.id,
      layout_type: 'library'
    });
    setShowLibraryModal(false);
    navigate(`/homeowner/request?selected_layout_id=${layout.id}&layout_type=library`);
  };

  const renderDashboard = () => (
    <div>
      {/* Hero – UrbanEye-like purple banner */}
      <div className="hero-card">
        <div className="hero-content">
          <div>
            <h1>Welcome back, {user?.first_name || 'Homeowner'} <span role="img" aria-label="wave"></span></h1>
            <p>Plan and track your home project. Request designs, review proposals, and manage progress.</p>
          </div>
          <div className="hero-actions">
            <button
              className="btn btn-primary"
              onClick={() => {
                setShowDashboardTour(true);
                setTourStep(0);
              }}
              style={{
                padding: '12px 24px',
                backgroundColor: '#007bff',
                color: 'white',
                border: 'none',
                borderRadius: '8px',
                cursor: 'pointer',
                fontSize: '16px',
                fontWeight: '600',
                boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
                transition: 'all 0.2s ease'
              }}
            >
              🎯 Take a Tour Guide
            </button>
          </div>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="stats-grid">
        <div className="stat-card w-blue">
          <div className="stat-content">
            <div className="stat-icon requests">📋</div>
            <div className="stat-info">
              <h3>{layoutRequests.length}</h3>
              <p>Layout Requests</p>
            </div>
          </div>
        </div>
        <div className="stat-card w-green">
          <div className="stat-content">
            <div className="stat-icon projects">🏗️</div>
            <div className="stat-info">
              <h3>{myProjects.length}</h3>
              <p>Active Projects</p>
            </div>
          </div>
        </div>
        <div className="stat-card w-purple">
          <div className="stat-content">
            <div className="stat-icon library">📚</div>
            <div className="stat-info">
              <h3>{layoutLibrary.length}</h3>
              <p>Available Layouts</p>
            </div>
          </div>
        </div>
        <div className="stat-card w-orange">
          <div className="stat-content">
            <div className="stat-icon completed">✅</div>
            <div className="stat-info">
              <h3>{layoutRequests.filter(r => r.status === 'approved').length}</h3>
              <p>Approved Requests</p>
            </div>
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="section-card">
        <div className="section-header">
          <h2>Quick Actions</h2>
          <p>Get started with your construction project</p>
        </div>
        <div className="section-content">
          <div className="quick-actions float-grid stagger-children">
            <button
              className="float-rect w-blue"
              onClick={() => navigate('/homeowner/request')}
            >
              <div className="fr-icon">📐</div>
              <div className="fr-title">Request Custom Design</div>
              <div className="fr-sub">Get professional architectural designs for your plot</div>
              <div className="fr-help" style={{ position: 'absolute', top: '8px', right: '8px', background: 'rgba(255,255,255,0.2)', borderRadius: '50%', width: '24px', height: '24px', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '12px', cursor: 'pointer' }} title="New to custom design requests? Click for a guided tour!">
              </div>
            </button>
            <button
              className="float-rect w-purple"
              onClick={() => { setShowLibraryModal(true); fetchLayoutLibrary(); }}
            >
              <div className="fr-icon">📚</div>
              <div className="fr-title">Browse Layout Library</div>
              <div className="fr-sub">Choose from pre-designed layouts and customize them</div>
            </button>
            <button
              className="float-rect w-orange"
              onClick={() => { setActiveTab('requests'); fetchMyRequests(); }}
            >
              <div className="fr-icon">👁️</div>
              <div className="fr-title">View My Requests</div>
              <div className="fr-sub">Track the status of your layout requests</div>
            </button>
            <button
              className="float-rect w-green"
              onClick={() => { setActiveTab('projects'); fetchMyProjects(); }}
            >
              <div className="fr-icon">🏠</div>
              <div className="fr-title">Manage Projects</div>
              <div className="fr-sub">Monitor your ongoing construction projects</div>
            </button>
          </div>
        </div>
      </div>

      {/* Project Progress Tracking */}
      <div className="section-card">
        <div className="section-header">
          <h2>Project Progress</h2>
          <p>Track the progress of your active projects</p>
        </div>
        <div className="section-content">
          <div className="widgets-grid">
            <div className="widget-container">
              <div className="widget-header">
                <h3 className="widget-title">Progress Overview</h3>
                <div className="widget-actions">
                  <button className="icon-btn" title="Refresh" onClick={fetchMyProjects}>↻</button>
                </div>
              </div>
              <ProjectProgressChart projects={myProjects} />
            </div>
            <div className="widget-container">
              <div className="widget-header">
                <h3 className="widget-title">Budget Tracker</h3>
                <div className="widget-actions">
                  <button className="icon-btn" title="View Details">👁️</button>
                </div>
              </div>
              <BudgetTracker projects={myProjects} />
            </div>
          </div>
        </div>
      </div>

      {/* Recent Activity */}
      <div className="section-header">
        <h2>Recent Activity</h2>
        <p>Latest updates on your requests and projects</p>
      </div>
      <div className="section-content">
        <div className="item-list">
          {layoutRequests.slice(0, 5).map(request => {
            const derivedStatus = (Number(request?.accepted_count) > 0 || request?.status === 'approved' || request?.status === 'accepted') ? 'accepted' : request?.status;
            const icon = derivedStatus === 'accepted' ? '✅' : derivedStatus === 'rejected' ? '❌' : '⏳';
            return (
              <div key={request.id} className="list-item">
                <div className="item-icon">{icon}</div>
                <div className="item-content">
                  <h4 className="item-title">Layout Request - {request.plot_size}</h4>
                  <p className="item-subtitle">Budget: {request.budget_range}</p>
                  <p className="item-meta">Submitted: {new Date(request.created_at).toLocaleDateString()}</p>
                </div>
                <div className="item-actions">
                  <span className={`status-badge ${badgeClass(derivedStatus)}`}>
                    {formatStatus(derivedStatus)}
                  </span>
                </div>
              </div>
            );
          })}
          {layoutRequests.length === 0 && (
            <div className="empty-state">
              <div className="empty-icon">📋</div>
              <h3>No Requests Yet</h3>
              <p>Start by submitting your first layout request!</p>
              <div className="empty-actions">
                <button
                  className="btn btn-primary"
                  onClick={() => setShowRequestForm(true)}
                >
                  Request Custom Design
                </button>
                <button
                  className="btn btn-secondary"
                  onClick={() => { setShowLibraryModal(true); fetchLayoutLibrary(); }}
                >
                  Browse Library
                </button>
              </div>
            </div>
          )}
        </div>
      </div>

    </div>
  );

  const renderRequests = () => (
    <div>
      <div className="main-header">
        <div className="header-content">
          <div>
            <h1>My Layout Requests</h1>
            <p>Track your architectural design requests and their progress</p>
          </div>
          <div className="header-actions">
            <button
              className="btn btn-secondary"
              onClick={() => { setShowLibraryModal(true); fetchLayoutLibrary(); }}
            >
              📚 Browse Library
            </button>
          </div>
        </div>
      </div>

      {/* Tab Navigation */}
      <div className="tab-navigation" style={{ marginBottom: '20px', borderBottom: '1px solid #e5e7eb' }}>
        <button
          className={`tab-button ${requestsTab === 'all' ? 'active' : ''}`}
          onClick={() => setRequestsTab('all')}
          style={{
            padding: '12px 24px',
            border: 'none',
            background: 'transparent',
            borderBottom: requestsTab === 'all' ? '2px solid #3b82f6' : '2px solid transparent',
            color: requestsTab === 'all' ? '#3b82f6' : '#6b7280',
            cursor: 'pointer',
            fontWeight: requestsTab === 'all' ? '600' : '400'
          }}
        >
          All Requests
        </button>
        <button
          className={`tab-button ${requestsTab === 'contractors' ? 'active' : ''}`}
          onClick={() => {
            setRequestsTab('contractors');
            fetchContractorRequests();
          }}
          style={{
            padding: '12px 24px',
            border: 'none',
            background: 'transparent',
            borderBottom: requestsTab === 'contractors' ? '2px solid #3b82f6' : '2px solid transparent',
            color: requestsTab === 'contractors' ? '#3b82f6' : '#6b7280',
            cursor: 'pointer',
            fontWeight: requestsTab === 'contractors' ? '600' : '400'
          }}
        >
          Sent to Contractors
        </button>
      </div>

      <div className="section-card">
        <div className="section-header">
          <h2>{requestsTab === 'all' ? 'Request History' : 'Contractor Requests'}</h2>
          <p>{requestsTab === 'all' ? 'All your submitted layout requests' : 'Requests sent to contractors for proposals'}</p>
        </div>
        <div className="section-content">
          {loading ? (
            <div className="loading">Loading requests...</div>
          ) : (requestsTab === 'all' ? layoutRequests : contractorRequests).length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">{requestsTab === 'all' ? '📭' : '🏗️'}</div>
              <h3>{requestsTab === 'all' ? 'No Requests Yet' : 'No Contractor Requests'}</h3>
              <p>{requestsTab === 'all' ? 'Submit your first layout request to get started!' : 'Send requests to contractors to get started!'}</p>
              <div className="empty-actions">
                <button
                  className="btn btn-primary"
                  onClick={() => navigate('/homeowner/request')}
                >
                  Request Custom Design
                </button>
                <button
                  className="btn btn-secondary"
                  onClick={() => { setShowLibraryModal(true); fetchLayoutLibrary(); }}
                >
                  Browse Library
                </button>
              </div>
            </div>
          ) : (
            <div className="item-list">
              {(requestsTab === 'all' ? layoutRequests : contractorRequests).map(request => (
                <RequestItem
                  key={request.id}
                  request={request}
                  onAssignArchitect={() => openArchitectModal(request)}
                  onRemove={() => removeRequest(request.id)}
                  showContractorInfo={requestsTab === 'contractors'}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const renderReceivedDesigns = () => (
    <div>
      <div className="main-header">
        <div className="header-content">
          <div>
            <h1>Received Designs</h1>
            <p>Review designs, shortlist your favorites, and finalize one</p>
          </div>
          <button className="btn btn-secondary" onClick={fetchReceivedDesigns}>↻ Refresh</button>
        </div>
      </div>

      <div className="section-card">
        <div className="section-header">
          <h2>All Designs & House Plans</h2>
          <p>Designs sent by architects and house plans with technical specifications</p>
        </div>
        <div className="section-content">
          {receivedDesigns.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">🎨</div>
              <h3>No Designs Yet</h3>
              <p>Assigned architects will send designs here for your review.</p>
            </div>
          ) : (
            <div className="item-list">
              {receivedDesigns.map(d => (
                <div key={d.id} className="list-item">
                  <div className="item-icon">
                    {d.source_type === 'house_plan' ? '🏗️' : 
                     d.status === 'finalized' ? '🏁' : 
                     d.status === 'shortlisted' ? '⭐' : '🎨'}
                  </div>
                  <div className="item-content" style={{ flex: 1 }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 10 }}>
                      <div>
                        <h4 className="item-title" style={{ margin: 0 }}>
                          {d.design_title}
                          {d.source_type === 'house_plan' && (
                            <span style={{ 
                              marginLeft: '8px', 
                              padding: '2px 6px', 
                              background: '#e3f2fd', 
                              color: '#1976d2', 
                              borderRadius: '4px', 
                              fontSize: '12px',
                              fontWeight: 'normal'
                            }}>
                              House Plan
                            </span>
                          )}
                        </h4>
                        <p className="item-subtitle" style={{ margin: '2px 0 0 0' }}>
                          By {d.architect?.name || 'Architect'} • {new Date(d.created_at).toLocaleString()}
                          {d.source_type === 'house_plan' && d.plot_dimensions && (
                            <span style={{ marginLeft: '8px', color: '#666' }}>
                              • Plot: {d.plot_dimensions} • Area: {d.total_area} sq ft
                            </span>
                          )}
                        </p>
                        <button className="btn btn-secondary" style={{ marginTop: 6 }} onClick={() => setShowDesignDetails(prev => ({ ...prev, [d.id]: !prev[d.id] }))}>
                          {showDesignDetails[d.id] ? 'Hide Details' : 'View Details'}
                        </button>
                        {showDesignDetails[d.id] && (
                          <div className="details-panel">
                            <div className="details-grid">
                              <div><strong>Sent By:</strong> {d.architect?.name || 'Architect'}</div>
                              <div><strong>Architect Email:</strong> {d.architect?.email || '-'}</div>
                              <div><strong>Design ID:</strong> {d.id}</div>
                              {d.layout_request_id ? (
                                <div><strong>Request ID:</strong> {d.layout_request_id}</div>
                              ) : (
                                <div><strong>Request:</strong> Direct upload</div>
                              )}
                              <div><strong>Status:</strong> <span className={`status-chip ${d.status}`}>{d.status}</span></div>
                              {d.source_type === 'house_plan' && (
                                <>
                                  <div><strong>House Plan Status:</strong> <span className={`status-chip ${d.house_plan_status}`}>{d.house_plan_status}</span></div>
                                  <div><strong>Plot Dimensions:</strong> {d.plot_dimensions}</div>
                                  <div><strong>Total Area:</strong> {d.total_area} sq ft</div>
                                </>
                              )}
                              <div><strong>Uploaded:</strong> {new Date(d.created_at).toLocaleString()}</div>
                            </div>
                            {d.description && (
                              <div className="description-section">
                                <strong>Description:</strong>
                                <div className="description-content">
                                  {d.source_type === 'house_plan' ? (
                                    <p>{d.description}</p>
                                  ) : (
                                    <NeatJsonCard raw={d.description} title="Requirements" />
                                  )}
                                </div>
                              </div>
                            )}
                            {d.technical_details && (
                              <div className="technical-details-section" style={{ marginTop: 16 }}>
                                <h3 style={{ margin: '0 0 12px 0', fontSize: '16px', fontWeight: 600, color: '#374151' }}>
                                  {d.source_type === 'house_plan' ? 'House Plan Technical Specifications' : 'Technical Details'}
                                  {d.source_type === 'house_plan' && !d.is_technical_details_unlocked && (
                                    <span style={{ 
                                      marginLeft: '8px', 
                                      padding: '2px 6px', 
                                      background: '#fef3c7', 
                                      color: '#92400e', 
                                      borderRadius: '4px', 
                                      fontSize: '12px',
                                      fontWeight: 'normal'
                                    }}>
                                      🔒 Locked - Pay ₹{parseFloat(d.unlock_price || 8000).toLocaleString('en-IN')} to unlock
                                    </span>
                                  )}
                                  {d.source_type === 'house_plan' && d.is_technical_details_unlocked && (
                                    <span style={{ 
                                      marginLeft: '8px', 
                                      padding: '2px 6px', 
                                      background: '#d1fae5', 
                                      color: '#065f46', 
                                      borderRadius: '4px', 
                                      fontSize: '12px',
                                      fontWeight: 'normal'
                                    }}>
                                      ✅ Unlocked
                                    </span>
                                  )}
                                </h3>
                                {d.source_type === 'house_plan' && !d.is_technical_details_unlocked ? (
                                  <div style={{ 
                                    padding: '16px', 
                                    background: '#fffbeb', 
                                    border: '1px solid #fbbf24', 
                                    borderRadius: '8px',
                                    textAlign: 'center'
                                  }}>
                                    <div style={{ fontSize: '14px', color: '#92400e', marginBottom: '8px' }}>
                                      Technical details are locked. Pay ₹{parseFloat(d.unlock_price || 8000).toLocaleString('en-IN')} to unlock complete specifications.
                                    </div>
                                    <button 
                                      className="btn btn-primary"
                                      onClick={() => handlePayToUnlockTechnicalDetails(d)}
                                      disabled={paymentLoading && payingDesignId === d.id}
                                      style={{ fontSize: '14px', padding: '8px 16px' }}
                                    >
                                      {paymentLoading && payingDesignId === d.id ? 'Processing...' : 
                                       `Pay ₹${parseFloat(d.unlock_price || 8000).toLocaleString('en-IN')} to Unlock`}
                                    </button>
                                  </div>
                                ) : (
                                  <TechnicalDetailsDisplay technicalDetails={d.technical_details} startExpanded={true} />
                                )}
                              </div>
                            )}
                            {d.source_type === 'house_plan' && d.plan_data && (
                              <div className="plan-data-section" style={{ marginTop: 16 }}>
                                <h3 style={{ margin: '0 0 12px 0', fontSize: '16px', fontWeight: 600, color: '#374151' }}>Plan Information</h3>
                                <div className="plan-info-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '12px' }}>
                                  <div><strong>Plot Size:</strong> {d.plot_dimensions}</div>
                                  <div><strong>Total Area:</strong> {d.total_area} sq ft</div>
                                  <div><strong>Floors:</strong> {d.plan_data.floors?.total_floors || 1}</div>
                                  <div><strong>Rooms:</strong> {d.plan_data.rooms?.length || 0}</div>
                                  {d.plan_data.scale_ratio && (
                                    <div><strong>Scale Ratio:</strong> {d.plan_data.scale_ratio}</div>
                                  )}
                                </div>
                              </div>
                            )}
                          </div>
                        )}
                      </div>
                      <button className="btn btn-danger" onClick={() => confirmDeleteDesign(d.id, d.design_title)}>🗑️ Delete</button>
                    </div>
                    <p className="item-meta" style={{ marginTop: 6 }}>
                      Status: <span className={`status-badge ${d.status}`}>{d.status}</span>
                    </p>

                    {/* Payment gate - for house plans with technical details or regular designs */}
                    {((d.source_type === 'house_plan' && !d.is_technical_details_unlocked) || 
                      (d.source_type !== 'house_plan' && !hasPaidAccess(d))) ? (
                      <div style={{ margin: '12px 0', padding: '12px', border: '1px solid #f59e0b', background: '#fffbeb', borderRadius: 8 }}>
                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12 }}>
                          <div>
                            <div style={{ fontWeight: 600, color: '#92400e' }}>
                              {d.source_type === 'house_plan' ? 'Payment required to unlock technical details' : 'Payment required to view files'}
                            </div>
                            <div style={{ color: '#92400e', fontSize: 14 }}>
                              {d.source_type === 'house_plan' ? 
                                `Unlock Price: ₹${parseFloat(d.unlock_price || 8000).toLocaleString('en-IN')}` :
                                (d?.view_price && d.view_price > 0 ?
                                  `Architect-set price: ₹${parseFloat(d.view_price).toLocaleString('en-IN')}` :
                                  `Price based on sqft: ₹${calculateDesignPrice(d).toLocaleString('en-IN')}`
                                )
                              }
                            </div>
                          </div>
                          <button
                            className="btn btn-primary"
                            onClick={() => d.source_type === 'house_plan' ? handlePayToUnlockTechnicalDetails(d) : handlePayToView(d)}
                            disabled={paymentLoading && payingDesignId === d.id}
                          >
                            {paymentLoading && payingDesignId === d.id ? 'Processing…' : 
                             d.source_type === 'house_plan' ? 
                               `Pay ₹${parseFloat(d.unlock_price || 8000).toLocaleString('en-IN')} to Unlock` : 
                               'Pay to View'}
                          </button>
                        </div>
                        {paymentError && (
                          <div style={{ marginTop: 8, color: '#991b1b', background: '#fef2f2', border: '1px solid #fecaca', borderRadius: 6, padding: '8px 10px' }}>{paymentError}</div>
                        )}
                      </div>
                    ) : null}

                    {/* Files grid - visible for unlocked house plans or paid regular designs */}
                    {((d.source_type === 'house_plan' && d.is_technical_details_unlocked) || 
                      (d.source_type !== 'house_plan' && hasPaidAccess(d))) && (
                      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))', gap: '10px', marginTop: '10px' }}>
                        {/* Handle different file types for house plans vs regular designs */}
                        {(() => {
                          const files = Array.isArray(d.files) ? d.files : [];
                          
                          if (d.source_type === 'house_plan') {
                            // For house plans, organize by file type
                            const layoutImages = files.filter(f => f.type === 'layout_image');
                            const elevationImages = files.filter(f => f.type === 'elevation_images');
                            const sectionDrawings = files.filter(f => f.type === 'section_drawings');
                            const renders3d = files.filter(f => f.type === 'renders_3d');
                            const allFiles = [...layoutImages, ...elevationImages, ...sectionDrawings, ...renders3d];
                            
                            return allFiles.map((f, idx) => {
                              const href = f.path || `/buildhub/backend/uploads/house_plans/${f.stored || f.original}`;
                              const ext = (f.ext || '').toLowerCase();
                              const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'heic'].includes(ext);
                              
                              let label = '';
                              switch(f.type) {
                                case 'layout_image': label = 'Layout'; break;
                                case 'elevation_images': label = 'Elevation'; break;
                                case 'section_drawings': label = 'Section'; break;
                                case 'renders_3d': label = '3D Render'; break;
                                default: label = 'File';
                              }
                              
                              return (
                                <div key={idx} className="file-card" style={{ cursor: 'default' }}>
                                  {isImage ? (
                                    <img 
                                      src={href} 
                                      alt={f.original} 
                                      style={{ width: '100%', height: 120, objectFit: 'cover', borderRadius: 6 }} 
                                      onClick={() => setViewer({ open: true, src: href, title: f.original || f.stored })} 
                                      onError={(e) => {
                                        e.target.style.display = 'none';
                                        e.target.nextSibling.style.display = 'flex';
                                      }}
                                    />
                                  ) : null}
                                  {isImage && (
                                    <div 
                                      className="file-thumb" 
                                      style={{ 
                                        height: 120, 
                                        display: 'none', 
                                        alignItems: 'center', 
                                        justifyContent: 'center', 
                                        background: '#f5f5f7', 
                                        borderRadius: 6,
                                        flexDirection: 'column',
                                        color: '#6b7280'
                                      }}
                                    >
                                      <span style={{ fontSize: '2rem', marginBottom: 8 }}>🖼️</span>
                                      <span style={{ fontSize: '0.75rem', textAlign: 'center' }}>Image not found</span>
                                    </div>
                                  )}
                                  {!isImage && (
                                    <div className="file-thumb" style={{ height: 120, display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#f5f5f7', borderRadius: 6 }}>
                                      <span style={{ fontSize: '2rem' }}>📄</span>
                                    </div>
                                  )}
                                  <div className="file-name" style={{ fontSize: '0.85rem', marginTop: 6, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }} title={f.original || f.stored}>
                                    {label}: {f.original || f.stored}
                                  </div>
                                  <div style={{ display: 'flex', gap: 8, marginTop: 6 }}>
                                    {isImage ? (
                                      <button type="button" className="btn btn-secondary" style={{ padding: '6px 10px' }} onClick={() => setViewer({ open: true, src: href, title: f.original || f.stored })}>View</button>
                                    ) : (
                                      <a href={href} target="_blank" rel="noreferrer" className="btn btn-secondary" style={{ padding: '6px 10px' }}>Open</a>
                                    )}
                                    <a href={href} download className="btn" style={{ padding: '6px 10px' }}>Download</a>
                                  </div>
                                </div>
                              );
                            });
                          } else {
                            // For regular designs, use existing logic
                            const preview = files.find(x => x.tag === 'preview') || files.find(x => /preview|thumb|cover/i.test(x.original || ''));
                            const layout = files.find(x => x.tag === 'layout') || files.find(x => /layout|plan|floor|design/i.test(x.original || ''));
                            const others = files.filter(x => x !== preview && x !== layout);
                            const toCards = [preview, layout, ...others].filter(Boolean);
                            
                            return toCards.map((f, idx) => {
                              const href = f.path || `/buildhub/backend/uploads/designs/${f.stored || f.original}`;
                              const ext = (f.ext || '').toLowerCase();
                              const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'heic'].includes(ext);
                              const label = f.tag === 'preview' ? 'Preview' : f.tag === 'layout' ? 'Layout' : undefined;
                              
                              return (
                                <div key={idx} className="file-card" style={{ cursor: 'default' }}>
                                  {isImage ? (
                                    <img 
                                      src={href} 
                                      alt={f.original} 
                                      style={{ width: '100%', height: 120, objectFit: 'cover', borderRadius: 6 }} 
                                      onClick={() => setViewer({ open: true, src: href, title: f.original || f.stored })} 
                                      onError={(e) => {
                                        e.target.style.display = 'none';
                                        e.target.nextSibling.style.display = 'flex';
                                      }}
                                    />
                                  ) : null}
                                  {isImage && (
                                    <div 
                                      className="file-thumb" 
                                      style={{ 
                                        height: 120, 
                                        display: 'none', 
                                        alignItems: 'center', 
                                        justifyContent: 'center', 
                                        background: '#f5f5f7', 
                                        borderRadius: 6,
                                        flexDirection: 'column',
                                        color: '#6b7280'
                                      }}
                                    >
                                      <span style={{ fontSize: '2rem', marginBottom: 8 }}>🖼️</span>
                                      <span style={{ fontSize: '0.75rem', textAlign: 'center' }}>Image not found</span>
                                    </div>
                                  )}
                                  {!isImage && (
                                    <div className="file-thumb" style={{ height: 120, display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#f5f5f7', borderRadius: 6 }}>
                                      <span style={{ fontSize: '2rem' }}>📄</span>
                                    </div>
                                  )}
                                  <div className="file-name" style={{ fontSize: '0.85rem', marginTop: 6, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }} title={f.original || f.stored}>
                                    {label ? `${label}: ` : ''}{f.original || f.stored}
                                  </div>
                                  <div style={{ display: 'flex', gap: 8, marginTop: 6 }}>
                                    {isImage ? (
                                      <button type="button" className="btn btn-secondary" style={{ padding: '6px 10px' }} onClick={() => setViewer({ open: true, src: href, title: f.original || f.stored })}>View</button>
                                    ) : (
                                      <a href={href} target="_blank" rel="noreferrer" className="btn btn-secondary" style={{ padding: '6px 10px' }}>Open</a>
                                    )}
                                    <a href={href} download className="btn" style={{ padding: '6px 10px' }}>Download</a>
                                  </div>
                                </div>
                              );
                            });
                          }
                        })()}
                      </div>
                    )}

                    {/* Comments */}
                    <div className="comment-section">
                      <button className="btn btn-secondary" onClick={() => fetchComments(d.id)}>Load comments</button>
                      <div className="comment-list">
                        {(comments[d.id] || []).map(c => (
                          <div key={c.id} className="comment-item">
                            <div className="comment-author">{c.author}</div>
                            <div className="comment-message">{c.message}</div>
                            <div className="comment-date">{new Date(c.created_at).toLocaleString()}</div>
                          </div>
                        ))}
                        <div className="comment-compose">
                          <div className="star-input">
                            {[1, 2, 3, 4, 5].map(star => (
                              <span
                                key={star}
                                role="button"
                                onClick={() => setCommentRatings(prev => ({ ...prev, [d.id]: star }))}
                                style={{ color: (commentRatings[d.id] || 0) >= star ? '#f5a623' : '#ddd' }}
                                title={`${star} star${star > 1 ? 's' : ''}`}
                              >★</span>
                            ))}
                          </div>
                          <input
                            type="text"
                            value={commentDrafts[d.id] || ''}
                            onChange={(e) => setCommentDrafts(prev => ({ ...prev, [d.id]: e.target.value }))}
                            placeholder="Write a comment... (will also be posted as a review)"
                          />
                          <button onClick={() => postComment(d.id)}>Post</button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="item-actions" style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                    <button className="btn" onClick={() => openSendToContractorFromDesign(d)}>Send to Contractor</button>
                    {d.source_type === 'house_plan' ? (
                      <div style={{ padding: '8px', background: '#f0f9ff', border: '1px solid #0ea5e9', borderRadius: '4px', fontSize: '12px', color: '#0369a1' }}>
                        House Plan - Review in House Plans tab for approval/rejection
                      </div>
                    ) : (
                      <>
                        {d.status !== 'shortlisted' && d.status !== 'finalized' && (
                          <button className="btn" onClick={() => updateSelection(d.id, 'shortlist')}>⭐ Shortlist</button>
                        )}
                        {d.status === 'shortlisted' && (
                          <button className="btn" onClick={() => updateSelection(d.id, 'remove-shortlist')}>Remove shortlist</button>
                        )}
                        {d.status !== 'finalized' && (
                          <button className="btn btn-primary" onClick={() => updateSelection(d.id, 'finalize')}>🏁 Finalize</button>
                        )}
                      </>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Contractor Estimates moved to 'Estimations' tab */}
    </div>
  );

  const renderLibrary = () => (
    <div>
      <div className="main-header">
        <div className="header-content">
          <div>
            <h1>Layout Library</h1>
            <p>Browse and select from our collection of pre-designed layouts</p>
          </div>
        </div>
      </div>

      <div className="section-card">
        <div className="section-header">
          <h2>Available Layouts</h2>
          <p>Choose from professionally designed layouts and customize them for your needs</p>
        </div>
        <div className="section-content">
          {loading ? (
            <div className="loading">Loading layouts...</div>
          ) : layoutLibrary.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">📚</div>
              <h3>No Layouts Available</h3>
              <p>Check back later for new layout designs!</p>
            </div>
          ) : (
            <div className="layout-grid">
              {layoutLibrary.map(layout => (
                <LayoutCard
                  key={layout.id}
                  layout={layout}
                  onSelect={() => handleSelectFromLibrary(layout)}
                  onPreview={() => setPreviewLayout(layout)}
                  isImageUrl={isImageUrl}
                  isPdfUrl={isPdfUrl}
                  onSendToContractor={openContractorModal}
                  onViewDetails={setTechnicalDetailsModal}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const renderProjects = () => (
    <div>
      <div className="main-header">
        <div className="header-content">
          <div>
            <h1>My Projects</h1>
            <p>Monitor your ongoing construction projects</p>
          </div>
          <button className="btn btn-primary" onClick={fetchMyProjects}>↻ Refresh Projects</button>
        </div>
      </div>

      {myProjects.length > 0 && (
        <div className="section-card">
          <div className="section-header">
            <h2>Project Timeline</h2>
            <p>View your project schedule and milestones</p>
          </div>
          <div className="section-content">
            <div className="widget-container">
              <ProjectTimeline projects={myProjects} />
            </div>
          </div>
        </div>
      )}

      <div className="section-card">
        <div className="section-header">
          <h2>Active Projects</h2>
          <p>Your current construction projects</p>
        </div>
        <div className="section-content">
          {myProjects.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">🏗️</div>
              <h3>No Projects Yet</h3>
              <p>Your approved layout requests will appear here as projects!</p>
              <button className="btn btn-primary" onClick={() => setActiveTab('dashboard')}>Go to Dashboard</button>
            </div>
          ) : (
            <div className="item-list">
              {myProjects.map(project => (
                <ProjectItem key={project.id} project={project} />
              ))}
            </div>
          )}
        </div>
      </div>

      {myProjects.length > 0 && (
        <div className="section-card">
          <div className="section-header">
            <h2>Budget Overview</h2>
            <p>Track your project expenses and budget allocation</p>
          </div>
          <div className="section-content">
            <div className="widget-container">
              <BudgetTracker projects={myProjects} />
            </div>
          </div>
        </div>
      )}
    </div>
  );

  return (
    <div className="dashboard-container">
      {/* Mobile Menu Button */}
      <button
        className="mobile-menu-btn"
        onClick={() => setSidebarOpen(!sidebarOpen)}
      >
        ☰
      </button>

      {/* Sidebar */}
      <div className={`dashboard-sidebar soft-sidebar expanded ${sidebarOpen ? 'open' : ''}`}>
        <div className="sidebar-header sb-brand">
          <a href="#" className="sidebar-logo">
            <div className="logo-icon">🏠</div>
            <span className="logo-text sb-title">BUILDHUB</span>
          </a>
        </div>

        <nav className="sidebar-nav sb-nav">
          <a
            href="#"
            className={`nav-item sb-item ${activeTab === 'dashboard' ? 'active' : ''}`}
            data-title="Dashboard"
            onClick={(e) => { e.preventDefault(); setActiveTab('dashboard'); fetchMyProjects(); fetchReceivedDesigns(); }}
          >
            <span className="nav-label sb-label">Dashboard</span>
          </a>
          <a
            href="#"
            className={`nav-item sb-item ${activeTab === 'library' ? 'active' : ''}`}
            data-title="Layout Library"
            onClick={(e) => { e.preventDefault(); setActiveTab('library'); fetchLayoutLibrary(); }}
          >
            <span className="nav-label sb-label">Layout Library</span>
            {libraryCount > 0 && (
              <span className="nav-badge pulse" style={{ marginLeft: 'auto' }}>{libraryCount}</span>
            )}
          </a>
          <a
            href="#"
            className={`nav-item sb-item ${activeTab === 'requests' ? 'active' : ''}`}
            data-title="My Requests"
            onClick={(e) => { e.preventDefault(); setActiveTab('requests'); fetchMyRequests(); }}
          >
            <span className="nav-label sb-label">My Requests</span>
            {requestsCount > 0 && (
              <span className="nav-badge pulse" style={{ marginLeft: 'auto' }}>{requestsCount}</span>
            )}
          </a>
          <a
            href="#"
            className={`nav-item sb-item ${activeTab === 'designs' ? 'active' : ''}`}
            data-title="Received Designs"
            onClick={(e) => { e.preventDefault(); setActiveTab('designs'); fetchReceivedDesigns(); }}
          >
            <span className="nav-label sb-label">Received Designs</span>
            {designsCount > 0 && (
              <span className="nav-badge pulse" style={{ marginLeft: 'auto' }}>{designsCount}</span>
            )}
          </a>

          <a
            href="#"
            className={`nav-item sb-item ${activeTab === 'house-plans' ? 'active' : ''}`}
            data-title="House Plans"
            onClick={(e) => { e.preventDefault(); setActiveTab('house-plans'); }}
          >
            <span className="nav-label sb-label">House Plans</span>
          </a>
          <a
            href="#"
            className={`nav-item sb-item ${activeTab === 'estimates' ? 'active' : ''}`}
            data-title="Estimations"
            onClick={(e) => { e.preventDefault(); setActiveTab('estimates'); }}
          >
            <span className="nav-label sb-label">Estimations</span>
            {homeownerEstimates.length > 0 && (
              <span className="nav-badge pulse" style={{ marginLeft: 'auto' }}>{homeownerEstimates.length}</span>
            )}
          </a>
          <a
            href="#"
            className={`nav-item sb-item ${activeTab === 'progress' ? 'active' : ''}`}
            data-title="Construction Progress"
            onClick={(e) => { e.preventDefault(); setActiveTab('progress'); }}
          >
            <span className="nav-label sb-label">Construction Progress</span>
          </a>
          <a
            href="#"
            className={`nav-item sb-item ${activeTab === 'photos' ? 'active' : ''}`}
            data-title="Construction Photos"
            onClick={(e) => { e.preventDefault(); setActiveTab('photos'); }}
          >
            <span className="nav-label sb-label">Construction Photos</span>
          </a>

        </nav>

      </div>

      {/* Main Content */}
      <div className={`dashboard-main soft-main shifted`}>
        {/* Top Glass Header */}
        <div className="top-glassbar">
          <div className="left">
            <div className="search">
              <span className="icon">🔎</span>
              <input type="text" placeholder="Search tasks, requests, designs..." aria-label="Search" />
            </div>
          </div>
          <div className="right">
            <NotificationSystem userId={user?.id || null} />
            <button className="icon-btn" title="Help" onClick={() => { setShowSupportModal(true); loadSupportIssues(); }}>❓</button>
            <HomeownerProfileButton
              user={user}
              position="bottom-right"
              onLogout={handleLogout}
            />
          </div>
        </div>
        {/* In-page alerts */}
        <div style={{ position: 'fixed', top: 20, right: 20, zIndex: 1000, display: 'flex', flexDirection: 'column', gap: 10 }}>
          {error && (
            <div className="alert alert-error" style={{ minWidth: 280 }}>
              {error}
              <button onClick={() => setError('')} className="alert-close">×</button>
            </div>
          )}
          {success && (
            <div className="alert alert-success" style={{ minWidth: 280 }}>
              {success}
              <button onClick={() => setSuccess('')} className="alert-close">×</button>
            </div>
          )}
        </div>

        {activeTab === 'dashboard' && renderDashboard()}
        {activeTab === 'library' && renderLibrary()}
        {activeTab === 'requests' && renderRequests()}
        {activeTab === 'designs' && renderReceivedDesigns()}
        {activeTab === 'house-plans' && <HousePlanViewer />}
        {activeTab === 'estimates' && (
          <div className="section-card" style={{ marginTop: '1rem' }}>
            <div className="section-header">
              <h2>Contractor Estimates</h2>
              <p>Submitted cost estimates for your layouts/designs</p>
            </div>
            <div className="section-content">
              {(() => {
                console.log('Rendering estimates tab. Count:', homeownerEstimates.length);
                console.log('Estimates data:', homeownerEstimates);
                return null;
              })()}
              {homeownerEstimates.length === 0 ? (
                <div className="empty-state">
                  <div className="empty-icon">📄</div>
                  <h3>No Estimates Yet</h3>
                  <p>When contractors submit estimates, they will appear here.</p>
                </div>
              ) : (
                <div className="item-list">
                  {homeownerEstimates.map(est => (
                    <div key={est.id} className="list-item">
                      <div className="item-icon">📋</div>
                      <div className="item-content" style={{ flex: 1 }}>
                        <h4 className="item-title" style={{ margin: 0 }}>Estimate #{est.id}</h4>
                        <p className="item-subtitle" style={{ margin: '2px 0 0 0' }}>Total: ₹{est.total_cost ?? '—'} • {new Date(est.created_at).toLocaleString()}</p>
                        {est.timeline && <p className="item-meta">Timeline: {est.timeline}</p>}
                        {est.notes && <p className="item-meta">Notes: {est.notes}</p>}
                        <p className="item-meta">Contractor: {est.contractor_name || 'Unknown'}{est.contractor_email ? ` • ${est.contractor_email}` : ''}</p>
                        <p className="item-meta" style={{ fontSize: '12px', color: '#666' }}>Status: {est.status || 'unknown'}</p>

                        {/* Acknowledgment Information */}
                        {est.acknowledged_at && (
                          <div style={{
                            marginTop: '12px',
                            padding: '12px',
                            background: 'linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)',
                            borderRadius: '8px',
                            border: '1px solid #3b82f6'
                          }}>
                            <div style={{ display: 'flex', alignItems: 'center', marginBottom: '6px' }}>
                              <span style={{ fontSize: '16px', marginRight: '8px' }}>✅</span>
                              <strong style={{ color: '#1e40af', fontSize: '13px' }}>Acknowledged by Contractor</strong>
                            </div>
                            <div style={{ color: '#1e3a8a', fontSize: '12px', paddingLeft: '24px' }}>
                              Acknowledged: {new Date(est.acknowledged_at).toLocaleString()}
                              {est.due_date && <span> • Due: {new Date(est.due_date).toLocaleDateString()}</span>}
                            </div>
                          </div>
                        )}
                        {Number(est.is_paid || 0) === 0 && (
                          <span className="status-badge pending">Locked • Pay ₹100 to view</span>
                        )}
                      </div>
                      <div className="item-actions" style={{ display: 'flex', gap: 6, alignItems: 'center', flexWrap: 'wrap' }}>
                        <button className="btn btn-primary" onClick={() => downloadEstimateReport(est)}>{Number(est.is_paid || 0) === 0 ? 'Pay ₹100 to Unlock' : 'Download Report'}</button>
                        {Number(est.is_paid || 0) === 1 && (
                          <>
                            <button className="btn btn-secondary" onClick={() => downloadTechnicalDetailsPDF(est)} style={{ background: '#10b981', color: 'white', border: '1px solid #10b981' }}>📄 Download Tech Details</button>
                          </>
                        )}
                        {Number(est.is_paid || 0) === 1 && (
                          <>
                            {est.status === 'submitted' && (
                              <>
                                <button className="btn btn-secondary" onClick={() => respondToEstimate(est, 'accept')}>Accept</button>
                                <button className="btn btn-secondary" onClick={() => respondToEstimate(est, 'reject')}>Reject</button>
                              </>
                            )}
                            {est.status === 'accepted' && (
                              <>
                                <button className="btn btn-secondary" onClick={() => setOpenChangeByEstimateId(prev => ({ ...prev, [est.id]: !prev[est.id] }))}>
                                  {openChangeByEstimateId[est.id] ? 'Close Changes' : 'Request Changes'}
                                </button>
                                <button
                                  className={messagesSentToContractors[est.id] ? "btn btn-success" : "btn btn-primary"}
                                  onClick={() => {
                                    console.log('Send message button clicked for estimate:', est.id);
                                    sendDirectMessageToContractor(est);
                                  }}
                                  style={messagesSentToContractors[est.id] ? {
                                    backgroundColor: '#10b981',
                                    borderColor: '#10b981',
                                    color: 'white'
                                  } : {}}
                                >
                                  {messagesSentToContractors[est.id] ? '✅ Message Sent' : '💬 Send Message'}
                                </button>
                                <button
                                  type="button"
                                  className="btn btn-success"
                                  onClick={(e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    console.log('Button clicked!', est);
                                    startConstruction(est);
                                  }}
                                  style={{ cursor: 'pointer' }}
                                >
                                  🏗️ Start Construction
                                </button>
                              </>
                            )}
                            {(est.status === 'construction_started' || est.status === 'changes_requested') && (
                              <>
                                <button
                                  className={messagesSentToContractors[est.id] ? "btn btn-success" : "btn btn-primary"}
                                  onClick={() => {
                                    console.log('Send message button clicked for estimate:', est.id);
                                    sendDirectMessageToContractor(est);
                                  }}
                                  style={messagesSentToContractors[est.id] ? {
                                    backgroundColor: '#10b981',
                                    borderColor: '#10b981',
                                    color: 'white'
                                  } : {}}
                                >
                                  {messagesSentToContractors[est.id] ? '✅ Message Sent' : '💬 Send Message'}
                                </button>
                                {est.status === 'changes_requested' && (
                                  <button className="btn btn-secondary" onClick={() => setOpenChangeByEstimateId(prev => ({ ...prev, [est.id]: !prev[est.id] }))}>
                                    {openChangeByEstimateId[est.id] ? 'Close Changes' : 'Request Changes'}
                                  </button>
                                )}
                              </>
                            )}
                            {(est.status === 'rejected') && (
                              <span className="status-badge rejected">Rejected</span>
                            )}
                          </>
                        )}
                        {/* Remove button for estimates */}
                        <button 
                          className="btn btn-danger" 
                          onClick={() => removeEstimate(est.id)}
                          style={{ 
                            background: '#ef4444', 
                            color: 'white', 
                            border: '1px solid #ef4444',
                            fontSize: '12px',
                            padding: '6px 12px'
                          }}
                          title="Remove estimate"
                        >
                          🗑️ Remove
                        </button>
                      </div>
                      {openChangeByEstimateId[est.id] && (
                        <div className="card" style={{ marginTop: 8 }}>
                          <div style={{ fontWeight: 600, marginBottom: 6 }}>Request Changes</div>
                          <textarea
                            placeholder="Describe the changes you want the contractor to make"
                            rows={3}
                            value={changeTextByEstimateId[est.id] || ''}
                            onChange={(e) => setChangeTextByEstimateId(prev => ({ ...prev, [est.id]: e.target.value }))}
                            style={{ width: '100%', border: '1px solid #e5e7eb', borderRadius: 6, padding: 8 }}
                          />
                          <div style={{ display: 'flex', gap: 8, marginTop: 8 }}>
                            <button className="btn btn-primary" onClick={() => {
                              const msg = (changeTextByEstimateId[est.id] || '').trim();
                              if (!msg) { try { toast.warning('Please describe the changes.'); } catch { } return; }
                              respondToEstimate(est, 'changes', msg);
                              setOpenChangeByEstimateId(prev => ({ ...prev, [est.id]: false }));
                              setChangeTextByEstimateId(prev => ({ ...prev, [est.id]: '' }));
                            }}>Send</button>
                            <button className="btn btn-secondary" onClick={() => setOpenChangeByEstimateId(prev => ({ ...prev, [est.id]: false }))}>Cancel</button>
                          </div>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}
        <HomeownerProgressReports activeTab={activeTab} isVisible={activeTab === 'progress'} />
        {activeTab === 'photos' && (
          <div className="section-card" style={{ marginTop: '1rem' }}>
            <div className="section-header">
              <h2>📸 Construction Progress Photos</h2>
              <p>View geo-located photos sent by your contractor with location details and timestamps</p>
            </div>
            <div className="section-content">
              <GeoPhotoViewer 
                homeownerId={user?.id}
                projectId={null} // Show all projects by default
              />
            </div>
          </div>
        )}
        {activeTab === 'scene3d' && (
          <div className="section-card">
            <div className="section-header">
              <h2>Finalized Layout – 3D View</h2>
              <p>View your finalized floor plan in 3D. Click rooms to highlight.</p>
            </div>
            <div className="section-content">
              <div className="home-3d-panel" role="region" aria-label="3D home layout">
                <Home3DLayout rooms={computed3DRooms} walls={computed3DWalls} onSelectRoom={(room) => setSuccess(`${room} selected`)} />
              </div>
            </div>
          </div>
        )}

        {/* Support / Help Modal */}
        {showSupportModal && (
          <div className="support-modal-overlay" onClick={() => setShowSupportModal(false)}>
            <div
              className="support-modal-content"
              onClick={(e) => e.stopPropagation()}
            >
              {/* Header */}
              <div className="support-modal-header">
                <div>
                  <h3 className="support-modal-title">
                    🎧 Help & Support
                  </h3>
                  <p className="support-modal-subtitle">Report issues and get help from our admin team</p>
                </div>
                <div className="support-modal-actions">
                  <button 
                    className="support-btn support-btn-secondary" 
                    onClick={() => { setSelectedIssue(null); setShowReportsList(true); loadSupportIssues(); }}
                  >
                    📋 My Reports
                  </button>
                  <button 
                    className="support-btn support-btn-secondary" 
                    onClick={() => setShowSupportModal(false)}
                  >
                    ✕ Close
                  </button>
                </div>
              </div>

              {/* Body */}
              <div className={`support-modal-body ${selectedIssue ? 'has-thread' : ''}`}>
                {/* Form Panel */}
                <div className={`support-form-panel ${selectedIssue ? 'has-thread' : ''}`}>
                  <div className={`support-form-container ${selectedIssue ? 'compact' : ''}`}>
                    <h4 className="support-form-title">
                      📝 Report New Issue
                    </h4>
                    <form className="support-form" onSubmit={(e) => { e.preventDefault(); submitSupportIssue(); }}>
                      <div className="support-form-group">
                        <label className="support-form-label">Subject *</label>
                        <input 
                          type="text" 
                          className="support-form-input"
                          value={supportForm.subject} 
                          onChange={(e) => setSupportForm({ ...supportForm, subject: e.target.value })} 
                          placeholder="Brief description of your issue"
                          required 
                        />
                      </div>
                      
                      <div className="support-form-group">
                        <label className="support-form-label">Category</label>
                        <select 
                          className="support-form-select"
                          value={supportForm.category} 
                          onChange={(e) => setSupportForm({ ...supportForm, category: e.target.value })}
                        >
                          <option value="general">General Support</option>
                          <option value="bug">Bug Report</option>
                          <option value="billing">Billing Issue</option>
                          <option value="account">Account Problem</option>
                          <option value="feature">Feature Request</option>
                        </select>
                      </div>
                      
                      <div className="support-form-group">
                        <label className="support-form-label">Message *</label>
                        <textarea 
                          className="support-form-textarea"
                          rows={selectedIssue ? 6 : 8} 
                          value={supportForm.message} 
                          onChange={(e) => setSupportForm({ ...supportForm, message: e.target.value })} 
                          placeholder="Describe your issue in detail. Include steps to reproduce if it's a bug."
                          required 
                        />
                      </div>
                      
                      <div className="support-form-actions">
                        <button 
                          type="submit" 
                          className="support-btn support-btn-primary" 
                          disabled={supportLoading}
                        >
                          {supportLoading ? '📤 Sending...' : '📤 Send to Admin'}
                        </button>
                        <button 
                          type="button" 
                          className="support-btn support-btn-secondary" 
                          onClick={() => { loadSupportIssues(); setSelectedIssue(null); setShowReportsList(true); }}
                        >
                          📋 View Reports
                        </button>
                      </div>
                    </form>
                  </div>
                  
                  {/* Reports List */}
                  {showReportsList && (
                    <div className="support-reports-section">
                      <div className={`support-reports-header ${selectedIssue ? 'compact' : ''}`}>
                        <h4 className="support-reports-title">
                          📋 My Reports ({supportIssues.length})
                        </h4>
                      </div>
                      <div className={`support-reports-list ${selectedIssue ? 'compact' : ''}`}>
                        {supportIssues.length === 0 ? (
                          <div className="support-empty-state">
                            <div className="support-empty-icon">📝</div>
                            <h3 className="support-empty-title">No Reports Yet</h3>
                            <p className="support-empty-text">Submit your first issue using the form above and it will appear here.</p>
                          </div>
                        ) : (
                          supportIssues.map(iss => (
                            <div 
                              key={iss.id} 
                              className={`support-report-item ${selectedIssue?.id === iss.id ? 'selected' : ''}`}
                              onClick={() => openIssueThread(iss.id)}
                            >
                              <div className="support-report-header">
                                <div className="support-report-icon">
                                  {iss.status === 'replied' ? '✅' : iss.status === 'closed' ? '🔒' : '🕘'}
                                </div>
                                <div className="support-report-content">
                                  <h4 className="support-report-title">{iss.subject}</h4>
                                  <p className="support-report-meta">
                                    #{iss.id} • {iss.category} • {new Date(iss.created_at).toLocaleDateString()} at {new Date(iss.created_at).toLocaleTimeString()}
                                  </p>
                                </div>
                              </div>
                              <div className="support-report-status">
                                <span className={`support-status-badge ${iss.status}`}>
                                  {iss.status}
                                </span>
                              </div>
                            </div>
                          ))
                        )}
                      </div>
                    </div>
                  )}
                </div>

                {/* Thread Panel */}
                {selectedIssue && (
                  <div className="support-thread-panel">
                    <div className="support-thread-header">
                      <h4 className="support-thread-title">{selectedIssue.subject}</h4>
                      <div className="support-thread-meta">
                        Category: {selectedIssue.category} • Status: {selectedIssue.status} • 
                        Opened: {new Date(selectedIssue.created_at).toLocaleDateString()} at {new Date(selectedIssue.created_at).toLocaleTimeString()}
                      </div>
                    </div>
                    <div className="support-thread-messages">
                      <div className="support-message user">
                        <div className="support-message-header">You</div>
                        <div className="support-message-body">{selectedIssue.message}</div>
                        <div className="support-message-time">
                          {new Date(selectedIssue.created_at).toLocaleDateString()} at {new Date(selectedIssue.created_at).toLocaleTimeString()}
                        </div>
                      </div>
                      {issueReplies.map(r => (
                        <div key={r.id} className={`support-message ${r.sender === 'admin' ? 'admin' : 'user'}`}>
                          <div className="support-message-header">
                            {r.sender === 'admin' ? 'Admin Support' : 'You'}
                          </div>
                          <div className="support-message-body">{r.message}</div>
                          <div className="support-message-time">
                            {new Date(r.created_at).toLocaleDateString()} at {new Date(r.created_at).toLocaleTimeString()}
                          </div>
                        </div>
                      ))}
                      {issueReplies.length === 0 && selectedIssue.status === 'open' && (
                        <div className="support-empty-state">
                          <div className="support-empty-icon">⏳</div>
                          <h3 className="support-empty-title">Waiting for Response</h3>
                          <p className="support-empty-text">Our admin team will respond to your issue soon. You'll see their reply here.</p>
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {/* Request Form Modal */}
        {showRequestForm && (
          <div className="form-modal">
            <div className="form-content">
              <div className="form-header">
                <h3>
                  {requestData.layout_type === 'library' ? 'Customize Selected Layout' : 'Submit Layout Request'}
                </h3>
                <p>
                  {requestData.layout_type === 'library'
                    ? 'Provide your requirements to customize the selected layout'
                    : 'Get professional architectural designs for your construction project'
                  }
                </p>
              </div>

              {/* Selected Layout Display */}
              {requestData.layout_type === 'library' && selectedLibraryLayout && (
                <div className="selected-layout-display">
                  <h4>Selected Layout: {selectedLibraryLayout.title}</h4>
                  <div className="layout-preview">
                    <img
                      src={selectedLibraryLayout.image_url || '/images/default-layout.jpg'}
                      alt={selectedLibraryLayout.title}
                      className="layout-image"
                    />
                    <div className="layout-details">
                      <p><strong>Type:</strong> {selectedLibraryLayout.layout_type}</p>
                      <p><strong>Bedrooms:</strong> {selectedLibraryLayout.bedrooms}</p>
                      <p><strong>Bathrooms:</strong> {selectedLibraryLayout.bathrooms}</p>
                      <p><strong>Area:</strong> {selectedLibraryLayout.area} sq ft</p>
                    </div>
                  </div>

                  {/* Technical Details in Selected Layout */}
                  {selectedLibraryLayout.technical_details && (
                    <div style={{ marginTop: '12px', padding: '12px', background: '#f8f9fa', borderRadius: '6px', border: '1px solid #e9ecef' }}>
                      <h5 style={{ margin: '0 0 8px 0', fontSize: '0.9rem', color: '#495057' }}>Technical Specifications</h5>
                      <TechnicalDetailsDisplay
                        technicalDetails={selectedLibraryLayout.technical_details}
                        compact={true}
                      />
                    </div>
                  )}
                </div>
              )}

              <form onSubmit={handleRequestSubmit}>
                <div className="form-section">
                  <h4 className="section-title">Basic Information</h4>
                  <div className="form-row">
                    <div className="form-group">
                      <label>Plot Size (sq ft) *</label>
                      <input
                        type="number"
                        value={requestData.plot_size}
                        onChange={(e) => setRequestData({ ...requestData, plot_size: e.target.value })}
                        placeholder="e.g., 1200"
                        required
                        min="100"
                        className="form-control"
                      />
                    </div>
                    <div className="form-group">
                      <label>Building Size (sq ft)</label>
                      <input
                        type="number"
                        value={requestData.building_size}
                        onChange={(e) => setRequestData({ ...requestData, building_size: e.target.value })}
                        placeholder="e.g., 800"
                        min="100"
                        className="form-control"
                      />
                    </div>
                  </div>
                  <div className="form-row">
                    <div className="form-group">
                      <label>Budget Range (₹) *</label>
                      <select
                        value={requestData.budget_range}
                        onChange={(e) => setRequestData({ ...requestData, budget_range: e.target.value })}
                        required
                        className="form-control"
                      >
                        <option value="">Select budget range</option>
                        <option value="5-10 Lakhs">₹5-10 Lakhs</option>
                        <option value="10-20 Lakhs">₹10-20 Lakhs</option>
                        <option value="20-30 Lakhs">₹20-30 Lakhs</option>
                        <option value="30-50 Lakhs">₹30-50 Lakhs</option>
                        <option value="50-75 Lakhs">₹50-75 Lakhs</option>
                        <option value="75 Lakhs - 1 Crore">₹75 Lakhs - 1 Crore</option>
                        <option value="1-2 Crores">₹1-2 Crores</option>
                        <option value="2-5 Crores">₹2-5 Crores</option>
                        <option value="5+ Crores">₹5+ Crores</option>
                        <option value="Custom">Custom Amount</option>
                      </select>
                      {requestData.budget_range === 'Custom' && (
                        <input
                          type="number"
                          placeholder="Enter custom budget amount in rupees"
                          value={requestData.custom_budget || ''}
                          onChange={(e) => setRequestData({ ...requestData, custom_budget: e.target.value })}
                          min="0"
                          step="10000"
                          className="form-control"
                          style={{ marginTop: '8px' }}
                        />
                      )}
                    </div>
                  </div>
                </div>

                {/* Site details */}
                <div className="form-section">
                  <h4 className="section-title">Site Details</h4>
                  <div className="form-row">
                    <div className="form-group">
                      <label>Plot Shape</label>
                      <select
                        value={requestData.plot_shape}
                        onChange={(e) => setRequestData({ ...requestData, plot_shape: e.target.value })}
                        className="form-control"
                      >
                        <option value="">Select plot shape</option>
                        <option value="Rectangular">Rectangular</option>
                        <option value="Square">Square</option>
                        <option value="L-shaped">L-shaped</option>
                        <option value="U-shaped">U-shaped</option>
                        <option value="Triangular">Triangular</option>
                        <option value="Irregular">Irregular</option>
                        <option value="Corner Plot">Corner Plot</option>
                        <option value="Trapezoidal">Trapezoidal</option>
                        <option value="Other">Other</option>
                      </select>
                    </div>
                    <div className="form-group">
                      <label>Number of Floors *</label>
                      <select
                        value={requestData.num_floors}
                        onChange={(e) => setRequestData({ ...requestData, num_floors: e.target.value })}
                        required
                        className="form-control"
                      >
                        <option value="">Select number of floors</option>
                        <option value="1">1 Floor (Ground Floor Only)</option>
                        <option value="2">2 Floors (G+1)</option>
                        <option value="3">3 Floors (G+2)</option>
                        <option value="4">4 Floors (G+3)</option>
                        <option value="5">5 Floors (G+4)</option>
                        <option value="6+">6+ Floors</option>
                      </select>
                    </div>
                  </div>

                  <div className="form-row">
                    <div className="form-group">
                      <label>Topography</label>
                      <select
                        value={requestData.topography}
                        onChange={(e) => setRequestData({ ...requestData, topography: e.target.value })}
                        className="form-control"
                      >
                        <option value="">Select topography</option>
                        <option value="Flat">Flat</option>
                        <option value="Slightly Sloped">Slightly Sloped</option>
                        <option value="Moderately Sloped">Moderately Sloped</option>
                        <option value="Steeply Sloped">Steeply Sloped</option>
                        <option value="Rocky">Rocky</option>
                        <option value="Sandy">Sandy</option>
                        <option value="Clayey">Clayey</option>
                        <option value="Mixed Terrain">Mixed Terrain</option>
                        <option value="Other">Other</option>
                      </select>
                    </div>
                    <div className="form-group">
                      <label>Local Development Laws / Restrictions</label>
                      <input
                        type="text"
                        value={requestData.development_laws}
                        onChange={(e) => setRequestData({ ...requestData, development_laws: e.target.value })}
                        placeholder="e.g., Setbacks, FSI/FAR, height limits"
                        className="form-control"
                      />
                    </div>
                  </div>
                </div>

                {/* Family needs */}
                <div className="form-section">
                  <h4 className="section-title">Family & Design Preferences</h4>
                  <div className="form-row">
                    <div className="form-group">
                      <label>Family Needs</label>
                      <select
                        multiple
                        value={Array.isArray(requestData.family_needs) ? requestData.family_needs : []}
                        onChange={(e) => {
                          const selectedOptions = Array.from(e.target.selectedOptions, option => option.value);
                          setRequestData({ ...requestData, family_needs: selectedOptions });
                        }}
                        className="form-control multi-select"
                        style={{ height: '120px' }}
                      >
                        <option value="Elder-friendly">Elder-friendly</option>
                        <option value="Work-from-home">Work-from-home</option>
                        <option value="Kids play area">Kids play area</option>
                        <option value="Pet-friendly">Pet-friendly</option>
                        <option value="Wheelchair accessible">Wheelchair accessible</option>
                        <option value="Home office">Home office</option>
                        <option value="Guest accommodation">Guest accommodation</option>
                        <option value="Storage space">Storage space</option>
                        <option value="Garden/Outdoor space">Garden/Outdoor space</option>
                        <option value="Security features">Security features</option>
                        <option value="Energy efficient">Energy efficient</option>
                        <option value="Low maintenance">Low maintenance</option>
                      </select>
                      <small className="form-text text-muted">Hold Ctrl/Cmd to select multiple options</small>
                    </div>
                    <div className="form-group">
                      <label>Rooms</label>
                      <select
                        multiple
                        value={Array.isArray(requestData.rooms) ? requestData.rooms : []}
                        onChange={(e) => {
                          const selectedOptions = Array.from(e.target.selectedOptions, option => option.value);
                          setRequestData({ ...requestData, rooms: selectedOptions });
                        }}
                        className="form-control multi-select"
                        style={{ height: '120px' }}
                      >
                        <option value="1 Bedroom">1 Bedroom</option>
                        <option value="2 Bedrooms">2 Bedrooms</option>
                        <option value="3 Bedrooms">3 Bedrooms</option>
                        <option value="4 Bedrooms">4 Bedrooms</option>
                        <option value="5+ Bedrooms">5+ Bedrooms</option>
                        <option value="1 Bathroom">1 Bathroom</option>
                        <option value="2 Bathrooms">2 Bathrooms</option>
                        <option value="3 Bathrooms">3 Bathrooms</option>
                        <option value="4+ Bathrooms">4+ Bathrooms</option>
                        <option value="Living Room">Living Room</option>
                        <option value="Dining Room">Dining Room</option>
                        <option value="Kitchen">Kitchen</option>
                        <option value="Study Room">Study Room</option>
                        <option value="Puja Room">Puja Room</option>
                        <option value="Guest Room">Guest Room</option>
                        <option value="Store Room">Store Room</option>
                        <option value="Balcony">Balcony</option>
                        <option value="Terrace">Terrace</option>
                        <option value="Garage">Garage</option>
                        <option value="Utility Area">Utility Area</option>
                      </select>
                      <small className="form-text text-muted">Hold Ctrl/Cmd to select multiple options</small>
                    </div>
                  </div>

                  {/* Aesthetic */}
                  <div className="form-row">
                    <div className="form-group">
                      <label>House Aesthetic / Style</label>
                      <select
                        value={requestData.aesthetic}
                        onChange={(e) => setRequestData({ ...requestData, aesthetic: e.target.value })}
                        className="form-control"
                      >
                        <option value="">Select house style</option>
                        <option value="Modern">Modern</option>
                        <option value="Contemporary">Contemporary</option>
                        <option value="Traditional">Traditional</option>
                        <option value="Minimalist">Minimalist</option>
                        <option value="Luxury">Luxury</option>
                        <option value="Mediterranean">Mediterranean</option>
                        <option value="Colonial">Colonial</option>
                        <option value="Victorian">Victorian</option>
                        <option value="Art Deco">Art Deco</option>
                        <option value="Scandinavian">Scandinavian</option>
                        <option value="Industrial">Industrial</option>
                        <option value="Rustic">Rustic</option>
                        <option value="Farmhouse">Farmhouse</option>
                        <option value="Craftsman">Craftsman</option>
                        <option value="Tudor">Tudor</option>
                        <option value="Ranch">Ranch</option>
                        <option value="Cape Cod">Cape Cod</option>
                        <option value="Other">Other</option>
                      </select>
                    </div>
                  </div>

                  {/* AI Style Preferences */}
                  <div className="form-row">
                    <div className="form-group" style={{ width: '100%' }}>
                      <label>🎨 Style Preferences (AI Matching)</label>
                      <p style={{ fontSize: '0.9rem', color: '#666', margin: '4px 0 12px 0' }}>
                        Select your preferred styles to get AI-recommended architects
                      </p>
                      <div className="style-preferences-grid">
                        {[
                          { key: 'modern', label: 'Modern', icon: '🏢' },
                          { key: 'contemporary', label: 'Contemporary', icon: '✨' },
                          { key: 'minimalist', label: 'Minimalist', icon: '⚪' },
                          { key: 'traditional', label: 'Traditional', icon: '🏛️' },
                          { key: 'luxury', label: 'Luxury', icon: '💎' },
                          { key: 'sustainable', label: 'Sustainable', icon: '🌱' },
                          { key: 'eco_friendly', label: 'Eco-friendly', icon: '♻️' },
                          { key: 'natural', label: 'Natural', icon: '🌿' },
                          { key: 'aesthetic', label: 'Aesthetic', icon: '🎨' },
                          { key: 'functional', label: 'Functional', icon: '⚙️' },
                          { key: 'elegant', label: 'Elegant', icon: '👑' },
                          { key: 'innovative', label: 'Innovative', icon: '💡' }
                        ].map(option => (
                          <button
                            key={option.key}
                            type="button"
                            className={`style-preference-option ${requestData.style_preferences[option.key] ? 'selected' : ''}`}
                            onClick={() => {
                              const newPreferences = { ...requestData.style_preferences };
                              if (newPreferences[option.key]) {
                                delete newPreferences[option.key];
                              } else {
                                newPreferences[option.key] = 1;
                              }
                              setRequestData({ ...requestData, style_preferences: newPreferences });
                            }}
                          >
                            <span className="style-icon">{option.icon}</span>
                            <span className="style-label">{option.label}</span>
                          </button>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>

                <div className="form-section location-timeline-section">
                  <h4 className="section-title">Location & Timeline</h4>
                  <div className="form-row">
                    <div className="form-group">
                      <label>Location</label>
                      <div className="location-input-wrapper">
                        <SearchableDropdown
                          options={indianCities}
                          value={requestData.location}
                          onChange={(value) => setRequestData({ ...requestData, location: value })}
                          placeholder="Search for a city..."
                          detectLocation={true}
                        />
                        <div className="location-detect-info">
                          <i className="fas fa-info-circle"></i>
                          <span>Click the location icon to detect your current city</span>
                        </div>
                      </div>
                    </div>
                    <div className="form-group">
                      <label>Timeline</label>
                      <select
                        value={requestData.timeline}
                        onChange={(e) => setRequestData({ ...requestData, timeline: e.target.value })}
                        className="form-control timeline-select"
                      >
                        <option value="">Select timeline</option>
                        <option value="0-6 months">0-6 months</option>
                        <option value="6-12 months">6-12 months</option>
                        <option value="12-18 months">12-18 months</option>
                        <option value="18-24 months">18-24 months</option>
                      </select>
                    </div>
                  </div>
                </div>

                {/* Additional notes */}
                <div className="form-section">
                  <h4 className="section-title">
                    {requestData.layout_type === 'library'
                      ? 'Customization Requirements'
                      : 'Additional Notes'
                    }
                  </h4>
                  <div className="form-group">
                    <label>
                      {requestData.layout_type === 'library'
                        ? 'Describe your modifications *'
                        : 'Any other preferences or constraints'
                      }
                    </label>
                    <textarea
                      value={requestData.requirements}
                      onChange={(e) => setRequestData({ ...requestData, requirements: e.target.value })}
                      placeholder={
                        requestData.layout_type === 'library'
                          ? "Describe any modifications you'd like to make to the selected layout: room changes, additional features, material preferences, etc."
                          : "Any other preferences or constraints"
                      }
                      rows="4"
                      required={requestData.layout_type === 'library'}
                      className="form-control"
                    />
                  </div>
                </div>

                {/* Integrated Architect Selection */}
                <div className="form-card architect-selection-card">
                  <ArchitectSelection
                    selectedArchitectIds={Array.isArray(selectedArchitectId) ? selectedArchitectId : (selectedArchitectId ? [selectedArchitectId] : [])}
                    onSelectionChange={(selectedIds) => {
                      setSelectedArchitectId(selectedIds);
                      setArchStepDone(selectedIds.length > 0);
                    }}
                    layoutRequestId={null}
                    showAIRecommendations={Object.keys(requestData.style_preferences || {}).length > 0}
                    stylePreferences={requestData.style_preferences || {}}
                  />

                  {!archStepDone ? (
                    <div className="muted" style={{ marginTop: 8 }}>Select an approved architect above to proceed.</div>
                  ) : (
                    <div className="form-group" style={{ marginTop: 8 }}>
                      <label>Message to architect (optional)</label>
                      <textarea
                        value={assignMessage}
                        onChange={(e) => setAssignMessage(e.target.value)}
                        placeholder="Add any notes for the architect"
                        rows="2"
                      />
                    </div>
                  )}
                </div>

                <div className="form-actions">
                  <button
                    type="button"
                    onClick={() => {
                      setShowRequestForm(false);
                      setSelectedLibraryLayout(null);
                      setRequestData({
                        plot_size: '',
                        budget_range: '',
                        requirements: '',
                        location: '',
                        timeline: '',
                        selected_layout_id: null,
                        layout_type: 'custom'
                      });
                    }}
                    className="btn btn-secondary"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    disabled={loading || (!archStepDone && Array.isArray(selectedArchitectId) && selectedArchitectId.length > 0 && !assignMessage)}
                    className="btn btn-primary"
                  >
                    {loading ? 'Submitting...' :
                      requestData.layout_type === 'library' ? 'Submit Customization Request' : 'Submit Request'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Architect Selection Modal */}
        {showArchitectModal && (
          <div className="form-modal">
            <div className="form-content architect-modal" style={{ maxWidth: 'min(1200px, 95vw)', maxHeight: '90vh', height: '90vh', overflowY: 'auto', scrollbarWidth: 'thin', scrollbarColor: 'rgb(203, 213, 224) rgb(247, 250, 252)', padding: '20px', position: 'relative', scrollBehavior: 'smooth', display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
              <div className="modal-close-container" style={{ position: 'absolute', top: '16px', right: '16px', zIndex: 10 }}>
                <button className="modal-close" onClick={() => setShowArchitectModal(false)} style={{ background: 'rgba(0,0,0,0.1)', border: 'none', borderRadius: '50%', width: '32px', height: '32px', display: 'flex', alignItems: 'center', justifyContent: 'center', cursor: 'pointer', fontSize: '18px', color: '#666' }}>×</button>
              </div>

              {/* Request selection fallback */}
              <div style={{ width: '100%', maxWidth: '1000px', marginBottom: '24px', marginTop: '10px' }}>
                {(!selectedRequestForAssign || !selectedRequestForAssign.id) ? (
                  <div className="form-group request-selector" style={{ width: '100%', background: '#f8fafc', padding: '20px', borderRadius: '12px', border: '1px solid #e5e7eb' }}>
                    <label style={{ fontSize: '18px', fontWeight: '700', color: '#1f2937', marginBottom: '12px', display: 'block' }}>📋 Select Your Request</label>
                    <p style={{ fontSize: '14px', color: '#6b7280', marginBottom: '16px' }}>Choose which project you want to assign an architect to</p>
                    <select
                      value={selectedRequestForAssign?.id || ''}
                      onChange={(e) => {
                        const req = layoutRequests.find(r => String(r.id) === e.target.value);
                        setSelectedRequestForAssign(req || null);
                      }}
                      className="form-control"
                      style={{ width: '100%', padding: '14px 18px', border: '2px solid #d1d5db', borderRadius: '10px', fontSize: '16px', background: 'white' }}
                    >
                      <option value="">-- Select request --</option>
                      {layoutRequests.map(r => (
                        <option key={r.id} value={r.id}>
                          #{r.id} • {r.layout_type === 'library' ? (r.selected_layout_title || 'Library') : 'Custom'} • {r.plot_size} sq ft
                        </option>
                      ))}
                    </select>
                  </div>
                ) : (
                  <div className="info-row" style={{ width: '100%', background: 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)', padding: '20px', borderRadius: '12px', border: '2px solid #bae6fd', textAlign: 'center' }}>
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '12px' }}>
                      <span style={{ fontSize: '20px' }}>✅</span>
                      <span className="status-chip success" style={{ fontSize: '16px', fontWeight: '700', color: '#0369a1' }}>Working on Request #{selectedRequestForAssign.id}</span>
                    </div>
                  </div>
                )}
              </div>


              {/* Integrated Architect Selection */}
              <div className="form-card architect-selection-card" style={{ width: '100%', maxWidth: '1000px', background: 'white', borderRadius: '16px', boxShadow: '0 4px 20px rgba(0,0,0,0.1)', border: '1px solid #e5e7eb' }}>
                <ArchitectSelection
                  selectedArchitectIds={Array.isArray(selectedArchitectId) ? selectedArchitectId : (selectedArchitectId ? [selectedArchitectId] : [])}
                  onSelectionChange={(selectedIds) => {
                    setSelectedArchitectId(selectedIds);
                    setArchStepDone(selectedIds.length > 0);
                  }}
                  layoutRequestId={selectedRequestForAssign?.id}
                  showAIRecommendations={Object.keys(requestData.style_preferences || {}).length > 0}
                  stylePreferences={requestData.style_preferences || {}}
                />
              </div>

              {/* Message section for selected architects */}
              <div className="form-group message-section" style={{ width: '100%', maxWidth: '600px', marginTop: '24px' }}>
                <label>Message to architect (optional)</label>
                <textarea
                  value={assignMessage}
                  onChange={(e) => setAssignMessage(e.target.value)}
                  placeholder="Add any specific requirements or notes for the architect..."
                  rows="3"
                />

                {/* Action buttons inside the form */}
                <div className="form-actions" style={{ display: 'flex', gap: '12px', justifyContent: 'center', marginTop: '20px' }}>
                  <button className="btn btn-secondary" onClick={() => setShowArchitectModal(false)}>Cancel</button>
                  <button className="btn btn-primary" disabled={archLoading || !selectedArchitectId} onClick={handleAssignArchitect}>
                    {archLoading ? 'Sending...' : 'Send Request'}
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Architect Details Modal (global) */}
        <ArchitectDetailsModal
          open={showArchitectDetails}
          onClose={() => setShowArchitectDetails(false)}
          architect={architectForDetails}
          reviews={architectReviews}
          loading={architectReviewsLoading}
        />

        {/* Simple Contractor Selection Modal */}
        {showContractorModal && (
          <div className="form-modal">
            <div className="form-content simple-contractor-modal">
              {/* Simple Header */}
              <div className="simple-modal-header">
                <h3>Send to Contractors</h3>
                <p>Select contractors to receive your {sourceDesignForContractor?.source_type === 'house_plan' ? 'house plan' : 'design'}</p>
                <button className="modal-close" onClick={() => setShowContractorModal(false)}>×</button>
              </div>

              {/* Project Info */}
              <div className="project-info-simple">
                {sourceDesignForContractor?.source_type === 'house_plan' ? (
                  <div className="info-row">
                    <strong>House Plan:</strong> {sourceDesignForContractor.design_title}
                    <span className="info-details">
                      Plot: {sourceDesignForContractor.plot_dimensions} • 
                      Area: {sourceDesignForContractor.total_area} sq ft • 
                      Files: {sourceDesignForContractor.files?.length || 0}
                    </span>
                  </div>
                ) : selectedLibraryLayout ? (
                  <div className="info-row">
                    <strong>Layout:</strong> {selectedLibraryLayout.title}
                    <span className="info-details">
                      {selectedLibraryLayout.bedrooms}BR • 
                      {selectedLibraryLayout.bathrooms}BA • 
                      {selectedLibraryLayout.area} sq ft
                    </span>
                  </div>
                ) : sourceDesignForContractor ? (
                  <div className="info-row">
                    <strong>Design:</strong> {sourceDesignForContractor.design_title}
                    <span className="info-details">
                      By {sourceDesignForContractor.architect?.name} • 
                      Files: {sourceDesignForContractor.files?.length || 0}
                    </span>
                  </div>
                ) : null}
              </div>

              {/* Search */}
              <div className="search-simple">
                <input
                  type="text"
                  placeholder="Search contractors..."
                  value={archSearch}
                  onChange={(e) => setArchSearch(e.target.value)}
                  className="search-input-simple"
                />
                <button 
                  className="search-btn-simple" 
                  onClick={() => fetchContractors({ search: archSearch })}
                >
                  Search
                </button>
              </div>

              {/* Selection Info */}
              {selectedContractors.length > 0 && (
                <div className="selection-info">
                  {selectedContractors.length} contractor{selectedContractors.length > 1 ? 's' : ''} selected
                </div>
              )}

              {/* Error */}
              {contractorError && (
                <div className="error-simple">{contractorError}</div>
              )}

              {/* Contractors List */}
              <div className="contractors-list-simple">
                {contractorLoading ? (
                  <div className="loading-simple">Loading contractors...</div>
                ) : contractors.length === 0 ? (
                  <div className="empty-simple">
                    <p>No contractors found</p>
                  </div>
                ) : (
                  <div className="contractors-simple">
                    {contractors.map(contractor => {
                      const isSelected = selectedContractors.some(c => c.id === contractor.id);
                      return (
                        <label 
                          key={contractor.id} 
                          className={`contractor-item ${isSelected ? 'selected' : ''}`}
                        >
                          <input
                            type="checkbox"
                            checked={isSelected}
                            onChange={(e) => {
                              if (e.target.checked) {
                                setSelectedContractors([...selectedContractors, contractor]);
                              } else {
                                setSelectedContractors(selectedContractors.filter(c => c.id !== contractor.id));
                              }
                            }}
                          />
                          <div className="contractor-info-simple">
                            <div className="contractor-name-simple">
                              {contractor.first_name} {contractor.last_name}
                            </div>
                            <div className="contractor-details-simple">
                              <div className="detail-line">
                                <span className="detail-icon">📧</span>
                                <span>{contractor.email}</span>
                              </div>
                              <div className="detail-line">
                                <span className="detail-icon">📜</span>
                                <span>{contractor.license ? 'Licensed Contractor' : 'License Pending'}</span>
                              </div>
                              <div className="detail-line">
                                <span className="detail-icon">📅</span>
                                <span>Member since {contractor.created_at ? new Date(contractor.created_at).getFullYear() : 'N/A'}</span>
                              </div>
                            </div>
                            <div className="contractor-rating">
                              <div className="rating-stars">
                                {[1, 2, 3, 4, 5].map(star => (
                                  <span 
                                    key={star} 
                                    className={`rating-star ${(contractor.avg_rating || 0) >= star ? '' : 'empty'}`}
                                  >
                                    ★
                                  </span>
                                ))}
                              </div>
                              <span className="rating-text">
                                {contractor.avg_rating || 0}/5 ({contractor.review_count || 0} reviews)
                              </span>
                            </div>
                          </div>
                        </label>
                      );
                    })}
                  </div>
                )}
              </div>

              {/* Actions */}
              <div className="modal-actions-simple">
                <button 
                  className="btn-cancel" 
                  onClick={() => setShowContractorModal(false)}
                >
                  Cancel
                </button>
                <button 
                  className="btn-send" 
                  disabled={contractorLoading || !selectedContractors || selectedContractors.length === 0 || sendingToContractor} 
                  onClick={sendToContractor}
                >
                  {sendingToContractor ? 
                    `Sending to ${selectedContractors.length} contractor${selectedContractors.length > 1 ? 's' : ''}...` : 
                    `Send to ${selectedContractors.length || 0} Contractor${selectedContractors.length > 1 ? 's' : ''}`
                  }
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Library Modal */}
        {showLibraryModal && (
          <div className="form-modal">
            <div className="form-content library-modal">
              <div className="form-header">
                <h3>Layout Library</h3>
                <p>Choose from our collection of professionally designed layouts</p>
                <button
                  className="modal-close"
                  onClick={() => setShowLibraryModal(false)}
                >
                  ×
                </button>
                <button className="btn btn-primary" onClick={() => setShowAddLayoutModal(true)} style={{ marginTop: 10 }}>Add Layout</button>
              </div>

              <div className="library-content">
                {loading ? (
                  <div className="loading">Loading layouts...</div>
                ) : layoutLibrary.length === 0 ? (
                  <div className="empty-state">
                    <div className="empty-icon">📚</div>
                    <h3>No Layouts Available</h3>
                    <p>Check back later for new layout designs!</p>
                  </div>
                ) : (
                  <div className="layout-grid">
                    {layoutLibrary.map(layout => (
                      <LayoutCard
                        key={layout.id}
                        layout={layout}
                        onSelect={() => handleSelectFromLibrary(layout)}
                        onPreview={() => setPreviewLayout(layout)}
                        isImageUrl={isImageUrl}
                        isPdfUrl={isPdfUrl}
                        isModal={true}
                        onSendToContractor={openContractorModal}
                        onViewDetails={setTechnicalDetailsModal}
                      />
                    ))}
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {/* Add Layout Modal */}
        {showAddLayoutModal && (
          <div className="form-modal">
            <div className="form-content" style={{ maxWidth: 920, maxHeight: '90vh', height: '90vh', overflowY: 'auto', scrollbarWidth: 'thin', scrollbarColor: 'rgb(203, 213, 224) rgb(247, 250, 252)', paddingRight: 12, marginRight: 8, position: 'relative', scrollBehavior: 'smooth' }}>
              <div className="fade-top"></div>
              <div className="form-header">
                <h3>Add Layout</h3>
                <p>Publish a new layout to the library</p>
                <div className="step-indicator" style={{ marginTop: 10, display: 'flex', gap: 10 }}>
                  <span className="step active">Basic Info &amp; Files</span>
                </div>
                <button
                  className="modal-close"
                  onClick={() => setShowAddLayoutModal(false)}
                >
                  ×
                </button>
              </div>
              <form onSubmit={handleAddLayout}>
                <div className="form-row">
                  <div className="form-group">
                    <label>Title</label>
                    <input placeholder="e.g., Modern 3BHK House" required type="text" value={addLayoutForm.title} onChange={(e) => setAddLayoutForm({ ...addLayoutForm, title: e.target.value })} />
                  </div>
                  <div className="form-group">
                    <label>Layout Type</label>
                    <select required value={addLayoutForm.layoutType} onChange={(e) => setAddLayoutForm({ ...addLayoutForm, layoutType: e.target.value })}>
                      <option value="">Select Type</option>
                      <option value="Residential">Residential</option>
                      <option value="Commercial">Commercial</option>
                      <option value="Mixed Use">Mixed Use</option>
                    </select>
                  </div>
                </div>
                <div className="form-row">
                  <div className="form-group">
                    <label>Bedrooms</label>
                    <input min="1" required type="number" value={addLayoutForm.bedrooms} onChange={(e) => setAddLayoutForm({ ...addLayoutForm, bedrooms: e.target.value })} />
                  </div>
                  <div className="form-group">
                    <label>Bathrooms</label>
                    <input min="1" required type="number" value={addLayoutForm.bathrooms} onChange={(e) => setAddLayoutForm({ ...addLayoutForm, bathrooms: e.target.value })} />
                  </div>
                  <div className="form-group">
                    <label>Area (sq ft)<div className="info-popup-container" style={{ position: 'relative', display: 'inline-block' }}><span style={{ marginLeft: 8, cursor: 'pointer', color: 'rgb(107, 114, 128)' }}>ℹ️</span></div></label>
                    <input min="100" required type="number" value={addLayoutForm.area} onChange={(e) => setAddLayoutForm({ ...addLayoutForm, area: e.target.value })} />
                  </div>
                </div>
                <div className="form-row">
                  <div className="form-group">
                    <label>Price Range</label>
                    <input placeholder="e.g., 20-30 Lakhs" type="text" value={addLayoutForm.priceRange} onChange={(e) => setAddLayoutForm({ ...addLayoutForm, priceRange: e.target.value })} />
                  </div>
                </div>
                <div className="form-section" style={{ marginTop: 20, padding: 16, border: '1px solid rgb(229, 231, 235)', borderRadius: 8, background: 'rgb(249, 250, 251)' }}>
                  <h4 style={{ margin: '0px 0px 16px', color: 'rgb(55, 65, 81)' }}>Files &amp; Media</h4>
                  <div className="form-row">
                    <div className="form-group">
                      <label>Preview Image *</label>
                      <input accept="image/*" required type="file" onChange={(e) => setAddLayoutForm({ ...addLayoutForm, previewImage: e.target.files[0] })} />
                      <p className="form-help" style={{ margin: '4px 0px 0px', fontSize: '0.8rem', color: 'rgb(107, 114, 128)' }}>Upload a preview image (JPG, PNG, GIF, WebP)</p>
                    </div>
                    <div className="form-group">
                      <label>Layout Design File *</label>
                      <input accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.svg,.dwg,.dxf,.ifc,.rvt,.skp,.3dm,.obj,.stl" required type="file" onChange={(e) => setAddLayoutForm({ ...addLayoutForm, layoutFile: e.target.files[0] })} />
                      <p className="form-help" style={{ margin: '4px 0px 0px', fontSize: '0.8rem', color: 'rgb(107, 114, 128)' }}>Upload layout file (PDF, Images, CAD files, 3D models)</p>
                    </div>
                  </div>
                  <div className="form-row">
                    <div className="form-group"></div>
                    <div className="form-group"></div>
                  </div>
                </div>
                <div className="form-group">
                  <label>Description</label>
                  <textarea rows="6" placeholder="Describe the layout features and design highlights..." style={{ minHeight: 120 }} value={addLayoutForm.description} onChange={(e) => setAddLayoutForm({ ...addLayoutForm, description: e.target.value })}></textarea>
                </div>
                <div className="form-actions" style={{ marginTop: 30, paddingBottom: 30, borderTop: '1px solid rgb(229, 231, 235)', paddingTop: 20 }}>
                  <button type="submit" className="btn btn-primary">Add Layout</button>
                  <button type="button" className="btn btn-secondary" onClick={() => setShowAddLayoutModal(false)}>Cancel</button>
                </div>
              </form>
              <div className="fade-bottom"></div>
            </div>
          </div>
        )}

        {/* Preview modal for image + layout */}
        {previewLayout && (
          <div className="form-modal" onClick={() => setPreviewLayout(null)}>
            <div className="form-content" onClick={(e) => e.stopPropagation()} style={{ maxWidth: 'min(1200px, 96vw)' }}>
              <div className="form-header">
                <h3>{previewLayout.title}</h3>
                <p>Preview Image and Layout</p>
                {(previewLayout.architect_name || previewLayout.architect_email) && (
                  <div style={{ marginTop: 6, color: '#6b7280' }}>
                    {previewLayout.architect_name && (<div><strong>Architect:</strong> {previewLayout.architect_name}</div>)}
                    {previewLayout.architect_email && (<div><strong>Email:</strong> {previewLayout.architect_email}</div>)}
                  </div>
                )}
              </div>
              <div className="form-row" style={{ gap: '16px' }}>
                <div className="form-group" style={{ flex: 1 }}>
                  <label>Preview Image</label>
                  {isImageUrl(previewLayout.image_url) ? (
                    <img src={previewLayout.image_url} alt="Preview" style={{ width: '100%', maxHeight: '70vh', objectFit: 'contain', borderRadius: 8 }} />
                  ) : (
                    <div style={{ padding: 12, background: '#fafafa', border: '1px dashed #ddd', borderRadius: 8 }}>No image</div>
                  )}
                </div>
                <div className="form-group" style={{ flex: 1 }}>
                  <label>Layout</label>
                  {isImageUrl(previewLayout.design_file_url) ? (
                    <img src={previewLayout.design_file_url} alt="Layout" style={{ width: '100%', maxHeight: '70vh', objectFit: 'contain', borderRadius: 8 }} />
                  ) : isPdfUrl(previewLayout.design_file_url) ? (
                    <iframe title="Layout PDF" src={previewLayout.design_file_url} style={{ width: '100%', height: '70vh', border: '1px solid #eee', borderRadius: 8 }} />
                  ) : previewLayout.design_file_url ? (
                    <a className="btn btn-link" href={previewLayout.design_file_url} target="_blank" rel="noreferrer">Open/Download Layout</a>
                  ) : (
                    <div style={{ padding: 12, background: '#fafafa', border: '1px dashed #ddd', borderRadius: 8 }}>No layout file</div>
                  )}
                </div>
              </div>

              {/* Technical Details in Preview Modal */}
              {previewLayout.technical_details && (
                <div style={{ marginTop: '16px', padding: '12px', background: '#f8f9fa', borderRadius: '6px', border: '1px solid #e9ecef' }}>
                  <h5 style={{ margin: '0 0 8px 0', fontSize: '0.9rem', color: '#495057' }}>Technical Specifications</h5>
                  <TechnicalDetailsDisplay
                    technicalDetails={previewLayout.technical_details}
                    compact={true}
                  />
                </div>
              )}

              <div className="form-actions">
                <button type="button" className="btn btn-primary" onClick={() => setPreviewLayout(null)}>Close</button>
              </div>
            </div>
          </div>
        )}

        {/* Layout Details Modal */}
        {technicalDetailsModal && (
          <div className="form-modal" onClick={() => setTechnicalDetailsModal(null)}>
            <div className="form-content" onClick={(e) => e.stopPropagation()} style={{ maxWidth: 'min(1000px, 95vw)', maxHeight: '90vh' }}>
              <div className="form-header">
                <h3>Layout Details - {technicalDetailsModal.title}</h3>
                <p>Complete layout information and specifications</p>
                {(technicalDetailsModal.architect_name || technicalDetailsModal.architect_email) && (
                  <div style={{ marginTop: 6, color: '#6b7280' }}>
                    {technicalDetailsModal.architect_name && (<div><strong>Architect:</strong> {technicalDetailsModal.architect_name}</div>)}
                    {technicalDetailsModal.architect_email && (<div><strong>Email:</strong> {technicalDetailsModal.architect_email}</div>)}
                  </div>
                )}
              </div>

              <div style={{ overflowY: 'auto', maxHeight: '70vh', paddingRight: '8px' }}>
                {/* Basic Layout Information */}
                <div style={{ marginBottom: '24px', padding: '16px', background: '#f8f9fa', borderRadius: '8px', border: '1px solid #e9ecef' }}>
                  <h4 style={{ margin: '0 0 12px 0', color: '#495057' }}>Basic Information</h4>
                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '12px' }}>
                    <div>
                      <strong>Title:</strong> {technicalDetailsModal.title || 'N/A'}
                    </div>
                    <div>
                      <strong>Type:</strong> {technicalDetailsModal.layout_type || 'N/A'}
                    </div>
                    <div>
                      <strong>Bedrooms:</strong> {technicalDetailsModal.bedrooms || 'N/A'}
                    </div>
                    <div>
                      <strong>Bathrooms:</strong> {technicalDetailsModal.bathrooms || 'N/A'}
                    </div>
                    <div>
                      <strong>Area:</strong> {technicalDetailsModal.area ? `${technicalDetailsModal.area} sq ft` : 'N/A'}
                    </div>
                    <div>
                      <strong>Price Range:</strong> {technicalDetailsModal.price_range || 'N/A'}
                    </div>
                    <div>
                      <strong>Status:</strong> {technicalDetailsModal.status || 'N/A'}
                    </div>
                    <div>
                      <strong>Created:</strong> {technicalDetailsModal.created_at ? new Date(technicalDetailsModal.created_at).toLocaleDateString() : 'N/A'}
                    </div>
                  </div>
                  {technicalDetailsModal.description && (
                    <div style={{ marginTop: '12px' }}>
                      <strong>Description:</strong>
                      <p style={{ margin: '4px 0 0 0', color: '#6c757d' }}>{technicalDetailsModal.description}</p>
                    </div>
                  )}
                </div>

                {/* Technical Details */}
                {technicalDetailsModal.technical_details && (
                  <div style={{ marginBottom: '24px' }}>
                    <h4 style={{ margin: '0 0 12px 0', color: '#495057' }}>Technical Specifications</h4>
                    <TechnicalDetailsDisplay
                      technicalDetails={technicalDetailsModal.technical_details}
                      compact={false}
                    />
                  </div>
                )}

                {/* Files Information */}
                <div style={{ padding: '16px', background: '#f8f9fa', borderRadius: '8px', border: '1px solid #e9ecef' }}>
                  <h4 style={{ margin: '0 0 12px 0', color: '#495057' }}>Files & Media</h4>
                  <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))', gap: '16px' }}>
                    {technicalDetailsModal.image_url && (
                      <div>
                        <strong>Preview Image:</strong>
                        <div style={{ marginTop: '8px' }}>
                          <img
                            src={technicalDetailsModal.image_url}
                            alt="Preview"
                            style={{ maxWidth: '100%', maxHeight: '200px', borderRadius: '6px', objectFit: 'cover' }}
                          />
                        </div>
                      </div>
                    )}
                    {technicalDetailsModal.design_file_url && (
                      <div>
                        <strong>Layout File:</strong>
                        <div style={{ marginTop: '8px', padding: '8px', background: '#fff', borderRadius: '6px', border: '1px solid #dee2e6' }}>
                          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                            <span style={{ fontSize: '1.5rem' }}>
                              {technicalDetailsModal.design_file_url.toLowerCase().endsWith('.pdf') ? '📄' :
                                technicalDetailsModal.design_file_url.toLowerCase().match(/\.(jpg|jpeg|png|gif|webp)$/) ? '🖼️' :
                                  technicalDetailsModal.design_file_url.toLowerCase().match(/\.(dwg|dxf)$/) ? '📐' :
                                    technicalDetailsModal.design_file_url.toLowerCase().match(/\.(skp|3dm|obj|stl)$/) ? '🏗️' : '📎'}
                            </span>
                            <div>
                              <div style={{ fontWeight: '500' }}>{technicalDetailsModal.design_file_url.split('/').pop()}</div>
                              <a href={technicalDetailsModal.design_file_url} target="_blank" rel="noreferrer" style={{ fontSize: '0.8rem', color: '#3b82f6' }}>View File</a>
                            </div>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              <div className="form-actions">
                <button type="button" className="btn btn-primary" onClick={() => setTechnicalDetailsModal(null)}>Close</button>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Image/File Viewer Modal */}
      <ImageViewer viewer={viewer} setViewer={setViewer} />
    </div>
  );
};

// Request Item Component
const RequestItem = ({ request, onAssignArchitect, onRemove, showContractorInfo = false }) => {
  const [showDetails, setShowDetails] = React.useState(false);
  const parseRequirements = (req) => {
    if (!req) return {};
    try { return typeof req === 'string' ? JSON.parse(req) : req; } catch { return {}; }
  };

  // Handle direct sends to contractors
  if (request.type === 'direct_send' || request.id?.toString().startsWith('send_')) {
    return (
      <div className="list-item" style={{
        background: 'white',
        borderRadius: '12px',
        border: '1px solid #e5e7eb',
        boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)',
        padding: '20px',
        marginBottom: '16px'
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '12px' }}>
          <div style={{
            width: '48px',
            height: '48px',
            borderRadius: '12px',
            background: 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: '20px',
            color: 'white'
          }}>
            🏗️
          </div>
          <div style={{ flex: 1 }}>
            <h4 style={{ margin: 0, fontSize: '18px', fontWeight: '600', color: '#1f2937' }}>
              Sent to Contractor
            </h4>
            <p style={{ margin: '4px 0 0 0', fontSize: '14px', color: '#6b7280' }}>
              {request.contractor_name || 'Contractor'} • {request.layout_title || 'Layout'}
            </p>
            <div style={{ margin: '4px 0 0 0', fontSize: '12px', color: '#9ca3af' }}>
              <div>📤 Sent: {new Date(request.created_at).toLocaleString()}</div>
              {request.acknowledged_at && (
                <div style={{ color: '#10b981', marginTop: '2px' }}>
                  ✅ Acknowledged: {new Date(request.acknowledged_at).toLocaleString()}
                </div>
              )}
              {request.due_date && (
                <div style={{ color: '#f59e0b', marginTop: '2px' }}>
                  📅 Due: {new Date(request.due_date).toLocaleDateString()}
                </div>
              )}
            </div>
          </div>
          <span className={`status-badge ${request.acknowledged_at ? 'accepted' : 'pending'}`} style={{ marginLeft: 'auto' }}>
            {request.acknowledged_at ? 'Acknowledged' : 'Sent'}
          </span>
        </div>
        {request.message && (
          <div style={{
            marginTop: '12px',
            padding: '12px',
            background: '#f9fafb',
            borderRadius: '8px',
            border: '1px solid #e5e7eb'
          }}>
            <strong style={{ fontSize: '13px', color: '#374151' }}>Message:</strong>
            <p style={{ margin: '4px 0 0 0', fontSize: '14px', color: '#6b7280', whiteSpace: 'pre-wrap' }}>
              {request.message}
            </p>
          </div>
        )}
      </div>
    );
  }
  const renderForwardedDesign = () => {
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
        } catch { }

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
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: 12 }}>
          {Object.entries(obj).map(([k, v]) => (
            <div key={k} style={{ padding: '12px', background: '#fff', border: '1px solid #e5e7eb', borderRadius: 8, boxShadow: '0 1px 3px rgba(0,0,0,0.1)' }}>
              <div style={{ fontSize: 13, fontWeight: 600, color: '#374151', marginBottom: 8, textTransform: 'capitalize' }}>
                {k.replaceAll('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
              </div>
              <div style={{ fontSize: 14, lineHeight: 1.5, color: '#6b7280', whiteSpace: 'pre-wrap' }}>
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
            <strong>Message to contractor:</strong>
            <div className="description-content">{reqObj.contractor_message}</div>
          </div>
        )}
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: '12px' }}>
          {files.map((f, idx) => {
            const href = f.path || `/buildhub/backend/uploads/designs/${f.stored || f.original}`;
            const ext = (f.ext || '').toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'heic'].includes(ext);
            return (
              <div key={idx} className="file-card-interactive" style={{
                position: 'relative',
                cursor: 'pointer',
                borderRadius: 8,
                overflow: 'hidden',
                boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                transition: 'transform 0.2s ease',
                ':hover': { transform: 'scale(1.02)' }
              }}>
                {isImage ? (
                  <div style={{ position: 'relative', width: '100%', height: 140 }}>
                    <img
                      src={href}
                      alt={f.original}
                      style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                      onClick={(e) => {
                        e.stopPropagation();
                        const overlay = e.currentTarget.nextSibling;
                        overlay.style.display = overlay.style.display === 'flex' ? 'none' : 'flex';
                      }}
                      onError={(e) => {
                        e.target.style.display = 'none';
                        const errorDiv = document.createElement('div');
                        errorDiv.style.cssText = 'width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f5f5f7; flex-direction: column; color: #6b7280;';
                        errorDiv.innerHTML = '<span style="font-size: 2rem; margin-bottom: 8px;">🖼️</span><span style="font-size: 0.75rem;">Image not found</span>';
                        e.target.parentNode.insertBefore(errorDiv, e.target);
                      }}
                    />
                    <div className="image-overlay" style={{
                      position: 'absolute',
                      top: 0, left: 0, right: 0, bottom: 0,
                      background: 'rgba(0,0,0,0.7)',
                      display: 'none',
                      alignItems: 'center',
                      justifyContent: 'center',
                      gap: 10,
                      zIndex: 10
                    }}>
                      <a
                        href={href}
                        target="_blank"
                        rel="noreferrer"
                        className="btn btn-light"
                        style={{ padding: '8px 16px', fontSize: '14px' }}
                        onClick={(e) => e.stopPropagation()}
                      >
                        👁️ View
                      </a>
                      <a
                        href={href}
                        download
                        className="btn btn-light"
                        style={{ padding: '8px 16px', fontSize: '14px' }}
                        onClick={(e) => e.stopPropagation()}
                      >
                        💾 Download
                      </a>
                    </div>
                  </div>
                ) : (
                  <div style={{ height: 140, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', background: '#f8fafc', border: '2px dashed #cbd5e1' }}>
                    <span style={{ fontSize: '2.5rem', marginBottom: 8 }}>📄</span>
                    <div style={{ display: 'flex', gap: 8 }}>
                      <a href={href} target="_blank" rel="noreferrer" className="btn btn-secondary btn-sm">View</a>
                      <a href={href} download className="btn btn-primary btn-sm">Download</a>
                    </div>
                  </div>
                )}
                <div style={{ padding: '8px', background: '#fff', borderTop: '1px solid #e5e7eb' }}>
                  <div style={{ fontSize: '13px', fontWeight: 500, color: '#374151', whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }} title={f.original || f.stored}>
                    {f.original || f.stored}
                  </div>
                  <div style={{ fontSize: '11px', color: '#9ca3af', marginTop: 2 }}>
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
                <div style={{ fontWeight: 600, marginBottom: 6 }}>Floor Plans</div>
                {renderKV(td.floor_plans)}
              </div>
            )}
            {td.site_orientation && (
              <div style={{ marginBottom: 12 }}>
                <div style={{ fontWeight: 600, marginBottom: 6 }}>Site Orientation</div>
                {renderKV(td.site_orientation)}
              </div>
            )}
            {td.structural && (
              <div style={{ marginBottom: 12 }}>
                <div style={{ fontWeight: 600, marginBottom: 6 }}>Structural</div>
                {renderKV(td.structural)}
              </div>
            )}
            {td.elevations && (
              <div style={{ marginBottom: 12 }}>
                <div style={{ fontWeight: 600, marginBottom: 6 }}>Elevations</div>
                {renderKV(td.elevations)}
              </div>
            )}
            {td.construction && (
              <div style={{ marginBottom: 12 }}>
                <div style={{ fontWeight: 600, marginBottom: 6 }}>Construction</div>
                {renderKV(td.construction)}
              </div>
            )}
          </div>
        )}
      </div>
    );
  };
  return (
    <div className="list-item" style={{
      background: 'white',
      borderRadius: '12px',
      border: '1px solid #e5e7eb',
      boxShadow: '0 1px 3px rgba(0, 0, 0, 0.1)',
      padding: '20px',
      marginBottom: '16px',
      transition: 'all 0.2s ease',
      position: 'relative',
      overflow: 'hidden'
    }}>
      {/* Header Section */}
      <div style={{
        display: 'flex',
        alignItems: 'flex-start',
        justifyContent: 'space-between',
        marginBottom: '16px'
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
          <div style={{
            width: '48px',
            height: '48px',
            borderRadius: '12px',
            background: request.layout_type === 'library'
              ? 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)'
              : 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: '20px',
            color: 'white',
            boxShadow: '0 2px 8px rgba(0, 0, 0, 0.15)'
          }}>
            {request.layout_type === 'library'
              ? '📚'
              : ((Number(request?.accepted_count) > 0 || request?.status === 'approved' || request?.status === 'accepted') ? '✅' : (request.status === 'rejected' ? '❌' : '⏳'))}
          </div>
          <div>
            <div style={{
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              marginBottom: '4px'
            }}>
              <span style={{
                background: '#eef2ff',
                color: '#3730a3',
                padding: '2px 8px',
                borderRadius: '6px',
                fontSize: '12px',
                fontWeight: '600'
              }}>
                #{request.id}
              </span>
              <h4 style={{
                margin: 0,
                fontSize: '18px',
                fontWeight: '600',
                color: '#1f2937',
                lineHeight: '1.3'
              }}>
                {request.layout_type === 'library'
                  ? `Library Layout: ${request.selected_layout_title || 'Selected Layout'}`
                  : `Custom Layout Request - ${request.plot_size} sq ft`
                }
              </h4>
            </div>
            <p style={{
              margin: 0,
              fontSize: '14px',
              color: '#6b7280',
              lineHeight: '1.4'
            }}>
              Budget: {request.budget_range}
              {request.layout_type === 'library' && request.selected_layout_type && (
                <span style={{
                  background: '#f0fdf4',
                  color: '#166534',
                  padding: '2px 6px',
                  borderRadius: '4px',
                  marginLeft: '8px',
                  fontSize: '12px',
                  fontWeight: '500'
                }}>
                  {request.selected_layout_type}
                </span>
              )}
            </p>
          </div>
        </div>

        {/* Status Badge */}
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          {(() => {
            const derivedStatus = (Number(request?.accepted_count) > 0 || request?.status === 'approved' || request?.status === 'accepted') ? 'accepted' : request?.status;
            return (
              <span style={{
                background: derivedStatus === 'accepted' ? '#d1fae5' :
                  derivedStatus === 'rejected' ? '#fee2e2' :
                    derivedStatus === 'pending' ? '#fef3c7' : '#f3f4f6',
                color: derivedStatus === 'accepted' ? '#065f46' :
                  derivedStatus === 'rejected' ? '#991b1b' :
                    derivedStatus === 'pending' ? '#92400e' : '#6b7280',
                padding: '6px 12px',
                borderRadius: '20px',
                fontSize: '12px',
                fontWeight: '600',
                textTransform: 'uppercase',
                letterSpacing: '0.5px'
              }}>
                {derivedStatus === 'deleted' ? 'Deleted' : formatStatus(derivedStatus)}
              </span>
            );
          })()}
        </div>
      </div>

      {/* Details Grid */}
      <div style={{
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
        gap: '16px',
        marginBottom: '16px'
      }}>
        <div style={{
          background: '#f8fafc',
          padding: '12px',
          borderRadius: '8px',
          border: '1px solid #e2e8f0'
        }}>
          <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '500' }}>Submitted</div>
          <div style={{ fontSize: '14px', color: '#1f2937', fontWeight: '600' }}>
            {new Date(request.created_at).toLocaleDateString()}
          </div>
        </div>

        {request.location && (
          <div style={{
            background: '#f8fafc',
            padding: '12px',
            borderRadius: '8px',
            border: '1px solid #e2e8f0'
          }}>
            <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '500' }}>Location</div>
            <div style={{ fontSize: '14px', color: '#1f2937', fontWeight: '600' }}>{request.location}</div>
          </div>
        )}

        <div style={{
          background: '#f8fafc',
          padding: '12px',
          borderRadius: '8px',
          border: '1px solid #e2e8f0'
        }}>
          <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '500' }}>Designs</div>
          <div style={{ fontSize: '14px', color: '#1f2937', fontWeight: '600' }}>{request.design_count || 0}</div>
        </div>

        <div style={{
          background: '#f8fafc',
          padding: '12px',
          borderRadius: '8px',
          border: '1px solid #e2e8f0'
        }}>
          <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '500' }}>Proposals</div>
          <div style={{ fontSize: '14px', color: '#1f2937', fontWeight: '600' }}>{request.proposal_count || 0}</div>
        </div>
      </div>

      {/* Status Row */}
      <div style={{
        display: 'flex',
        gap: '8px',
        flexWrap: 'wrap',
        marginBottom: '16px'
      }}>
        <span style={{
          background: '#e0f2fe',
          color: '#0369a1',
          padding: '4px 10px',
          borderRadius: '12px',
          fontSize: '12px',
          fontWeight: '500'
        }}>
          Sent: {request.sent_count || 0}
        </span>
        <span style={{
          background: '#d1fae5',
          color: '#065f46',
          padding: '4px 10px',
          borderRadius: '12px',
          fontSize: '12px',
          fontWeight: '500'
        }}>
          Accepted: {request.accepted_count || 0}
        </span>
        <span style={{
          background: '#fee2e2',
          color: '#991b1b',
          padding: '4px 10px',
          borderRadius: '12px',
          fontSize: '12px',
          fontWeight: '500'
        }}>
          Rejected: {request.rejected_count || 0}
        </span>
      </div>
      {/* Contractor Info Section */}
      {showContractorInfo && (
        <div style={{
          background: 'linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)',
          padding: '16px',
          borderRadius: '8px',
          border: '1px solid #e2e8f0',
          marginBottom: '16px'
        }}>
          <div style={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            marginBottom: '12px'
          }}>
            <span style={{
              fontWeight: '600',
              color: '#374151',
              fontSize: '14px'
            }}>
              Contractor Assignments
            </span>
            <span style={{
              background: '#dbeafe',
              color: '#1e40af',
              padding: '4px 10px',
              borderRadius: '12px',
              fontSize: '12px',
              fontWeight: '500'
            }}>
              {request.assignment_count || 0} assigned
            </span>
          </div>
          {request.assigned_contractors && request.assigned_contractors.length > 0 ? (
            <div>
              <div style={{
                fontSize: '13px',
                color: '#6b7280',
                marginBottom: '8px',
                fontWeight: '500'
              }}>
                Assigned Contractors:
              </div>
              <div style={{
                display: 'flex',
                flexWrap: 'wrap',
                gap: '6px'
              }}>
                {request.assigned_contractors.map((contractor, index) => (
                  <span key={index} style={{
                    background: '#ecfdf5',
                    color: '#065f46',
                    padding: '4px 8px',
                    borderRadius: '8px',
                    fontSize: '12px',
                    fontWeight: '500'
                  }}>
                    {contractor}
                  </span>
                ))}
              </div>
              {request.assignment_statuses && request.assignment_statuses.length > 0 && (
                <div style={{
                  marginTop: '8px',
                  fontSize: '12px',
                  color: '#6b7280'
                }}>
                  Status: {request.assignment_statuses.join(', ')}
                </div>
              )}
            </div>
          ) : (
            <div style={{
              fontSize: '14px',
              color: '#6b7280',
              fontStyle: 'italic'
            }}>
              No contractors assigned yet
            </div>
          )}
          <div style={{
            marginTop: '12px',
            fontSize: '12px',
            color: '#6b7280',
            fontWeight: '500'
          }}>
            Proposals received: {request.proposal_count || 0}
          </div>
        </div>
      )}
      {/* Details Panel */}
      {showDetails && (
        <div style={{
          background: 'linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)',
          padding: '20px',
          borderRadius: '12px',
          border: '1px solid #e2e8f0',
          marginBottom: '16px'
        }}>
          <div style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
            gap: '16px'
          }}>
            <div style={{
              background: 'white',
              padding: '12px',
              borderRadius: '8px',
              border: '1px solid #e5e7eb'
            }}>
              <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '500' }}>Request Type</div>
              <div style={{ fontSize: '14px', fontWeight: '600', color: '#1f2937' }}>
                {request.layout_type === 'library' ? 'Library Layout' : 'Custom Request'}
              </div>
            </div>
            <div style={{
              background: 'white',
              padding: '12px',
              borderRadius: '8px',
              border: '1px solid #e5e7eb'
            }}>
              <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '500' }}>Plot Size</div>
              <div style={{ fontSize: '14px', fontWeight: '600', color: '#1f2937' }}>{request.plot_size || '-'}</div>
            </div>
            <div style={{
              background: 'white',
              padding: '12px',
              borderRadius: '8px',
              border: '1px solid #e5e7eb'
            }}>
              <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '500' }}>Budget Range</div>
              <div style={{ fontSize: '14px', fontWeight: '600', color: '#1f2937' }}>{request.budget_range || '-'}</div>
            </div>
            <div style={{
              background: 'white',
              padding: '12px',
              borderRadius: '8px',
              border: '1px solid #e5e7eb'
            }}>
              <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '500' }}>Location</div>
              <div style={{ fontSize: '14px', fontWeight: '600', color: '#1f2937' }}>{request.location || '-'}</div>
            </div>
            <div style={{
              background: 'white',
              padding: '12px',
              borderRadius: '8px',
              border: '1px solid #e5e7eb'
            }}>
              <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '500' }}>Timeline</div>
              <div style={{ fontSize: '14px', fontWeight: '600', color: '#1f2937' }}>{request.timeline || '-'}</div>
            </div>
            {request.layout_type === 'library' && (
              <div style={{
                background: 'white',
                padding: '12px',
                borderRadius: '8px',
                border: '1px solid #e5e7eb',
                gridColumn: '1 / -1'
              }}>
                <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '500' }}>Selected Layout</div>
                <div style={{ fontSize: '14px', fontWeight: '600', color: '#1f2937' }}>{request.selected_layout_title || 'Selected Layout'}</div>
              </div>
            )}
            {request.layout_type === 'library' && (request.selected_layout_architect_name || request.selected_layout_architect_email) && (
              <div style={{
                background: 'white',
                padding: '12px',
                borderRadius: '8px',
                border: '1px solid #e5e7eb',
                gridColumn: '1 / -1',
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
                gap: '12px'
              }}>
                {request.selected_layout_architect_name && (
                  <div>
                    <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '500' }}>Architect</div>
                    <div style={{ fontSize: '14px', fontWeight: '600', color: '#1f2937' }}>{request.selected_layout_architect_name}</div>
                  </div>
                )}
                {request.selected_layout_architect_email && (
                  <div>
                    <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '4px', fontWeight: '500' }}>Email</div>
                    <div style={{ fontSize: '14px', fontWeight: '600', color: '#1f2937' }}>{request.selected_layout_architect_email}</div>
                  </div>
                )}
              </div>
            )}
            {request.requirements && (
              <div style={{ gridColumn: '1 / -1' }}>
                <NeatJsonCard raw={request.requirements} title="Requirements" />
              </div>
            )}
            {showContractorInfo && (
              <div style={{ gridColumn: '1 / -1' }}>
                <div style={{ fontSize: '12px', color: '#6b7280', marginBottom: '6px', fontWeight: '500' }}>Forwarded to Contractor</div>
                {renderForwardedDesign()}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Action Buttons */}
      <div style={{
        display: 'flex',
        gap: '12px',
        flexWrap: 'wrap',
        alignItems: 'center',
        justifyContent: 'flex-end',
        paddingTop: '16px',
        borderTop: '1px solid #e5e7eb'
      }}>
        <button
          style={{
            background: '#f3f4f6',
            color: '#374151',
            border: '1px solid #d1d5db',
            padding: '8px 16px',
            borderRadius: '8px',
            fontSize: '14px',
            fontWeight: '500',
            cursor: 'pointer',
            transition: 'all 0.2s ease'
          }}
          onClick={() => setShowDetails(s => !s)}
          onMouseEnter={(e) => {
            e.target.style.background = '#e5e7eb';
            e.target.style.borderColor = '#9ca3af';
          }}
          onMouseLeave={(e) => {
            e.target.style.background = '#f3f4f6';
            e.target.style.borderColor = '#d1d5db';
          }}
        >
          {showDetails ? 'Hide Details' : 'View Details'}
        </button>
        <button
          style={{
            background: 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)',
            color: 'white',
            border: 'none',
            padding: '8px 16px',
            borderRadius: '8px',
            fontSize: '14px',
            fontWeight: '500',
            cursor: 'pointer',
            transition: 'all 0.2s ease',
            boxShadow: '0 2px 4px rgba(59, 130, 246, 0.3)'
          }}
          onClick={onAssignArchitect}
          title="Send this request to a selected architect"
          onMouseEnter={(e) => {
            e.target.style.transform = 'translateY(-1px)';
            e.target.style.boxShadow = '0 4px 8px rgba(59, 130, 246, 0.4)';
          }}
          onMouseLeave={(e) => {
            e.target.style.transform = 'translateY(0)';
            e.target.style.boxShadow = '0 2px 4px rgba(59, 130, 246, 0.3)';
          }}
        >
          Send to Architect
        </button>
        <button
          style={{
            background: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
            color: 'white',
            border: 'none',
            padding: '8px 16px',
            borderRadius: '8px',
            fontSize: '14px',
            fontWeight: '500',
            cursor: 'pointer',
            transition: 'all 0.2s ease',
            boxShadow: '0 2px 4px rgba(239, 68, 68, 0.3)'
          }}
          onClick={onRemove}
          title="Remove request"
          onMouseEnter={(e) => {
            e.target.style.transform = 'translateY(-1px)';
            e.target.style.boxShadow = '0 4px 8px rgba(239, 68, 68, 0.4)';
          }}
          onMouseLeave={(e) => {
            e.target.style.transform = 'translateY(0)';
            e.target.style.boxShadow = '0 2px 4px rgba(239, 68, 68, 0.3)';
          }}
        >
          Remove
        </button>
      </div>
    </div>
  );
};

// Project Item Component
const ProjectItem = ({ project }) => {
  const [showDetails, setShowDetails] = React.useState(false);

  // Calculate days remaining based on start date and estimated duration
  const calculateDaysRemaining = () => {
    const startDate = new Date(project.start_date);
    const duration = project.estimated_duration || 90; // Default to 90 days if not specified
    const endDate = new Date(startDate);
    endDate.setDate(startDate.getDate() + duration);

    const today = new Date();
    const daysRemaining = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));
    return daysRemaining > 0 ? daysRemaining : 0;
  };

  // Progress color based on percentage
  const getProgressColor = () => {
    const progress = project.progress || 0;
    if (progress < 25) return "#ff4d4d";
    if (progress < 50) return "#ffa64d";
    if (progress < 75) return "#4db8ff";
    return "#4dff88";
  };

  return (
    <div className="list-item project-item">
      <div className="item-icon">🏗️</div>
      <div className="item-content">
        <h4 className="item-title">{project.project_name}</h4>
        <p className="item-subtitle">Contractor: {project.contractor_name}</p>
        <p className="item-meta">
          Started: {new Date(project.start_date).toLocaleDateString()}
          • Progress: {project.progress || 0}%
        </p>

        {/* Progress bar */}
        <div className="progress-bar-container">
          <div
            className="progress-bar"
            style={{
              width: `${project.progress || 0}%`,
              backgroundColor: getProgressColor()
            }}
          ></div>
        </div>

        {showDetails && (
          <div className="project-details">
            <div className="detail-grid">
              <div className="detail-item">
                <span className="detail-label">Budget:</span>
                <span className="detail-value">{project.budget || "Not specified"}</span>
              </div>
              <div className="detail-item">
                <span className="detail-label">Days Remaining:</span>
                <span className="detail-value">{calculateDaysRemaining()}</span>
              </div>
              <div className="detail-item">
                <span className="detail-label">Location:</span>
                <span className="detail-value">{project.location || "Not specified"}</span>
              </div>
              <div className="detail-item">
                <span className="detail-label">Plot Size:</span>
                <span className="detail-value">{project.plot_size || "Not specified"}</span>
              </div>
            </div>
          </div>
        )}
      </div>
      <div className="item-actions">
        <span className={`status-badge ${project.status}`}>
          {project.status}
        </span>
        <div className="button-group">
          <button className="btn btn-secondary" onClick={() => setShowDetails(!showDetails)}>
            {showDetails ? "Hide Details" : "Show Details"}
          </button>
          <button className="btn btn-primary">
            View Project
          </button>
        </div>
      </div>
    </div>
  );
};

// Layout Card Component
const LayoutCard = ({ layout, onSelect, onPreview, isImageUrl, isPdfUrl, isModal = false, onSendToContractor, onViewDetails }) => (
  <div className={`layout-card ${isModal ? 'modal-card' : ''}`}>
    <div className="layout-image-container">
      <button type="button" className="layout-image-button" onClick={(e) => { e.stopPropagation(); onPreview(); }} style={{ cursor: 'zoom-in' }}>
        <img
          src={layout.image_url || '/images/default-layout.jpg'}
          alt={layout.title}
          className="layout-card-image"
        />
      </button>
      <div className="layout-overlay">
        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
          {(layout.design_file_url && (isImageUrl(layout.design_file_url) || isPdfUrl(layout.design_file_url))) && (
            <button type="button" className="btn" onClick={(e) => { e.stopPropagation(); onPreview(); }}>View Layout</button>
          )}
          <button type="button" className="btn" onClick={(e) => { e.stopPropagation(); onSelect(); }}>
            Customize
          </button>
        </div>
      </div>
    </div>
    <div className="layout-card-content">
      <h4 className="layout-title">{layout.title}</h4>
      <p className="layout-type">{layout.layout_type}</p>
      <div className="layout-specs">
        <span className="spec">🛏️ {layout.bedrooms} BR</span>
        <span className="spec">🚿 {layout.bathrooms} BA</span>
        <span className="spec">📐 {layout.area} sq ft</span>
      </div>
      {layout.architect_name && (
        <p className="layout-author" style={{ margin: '6px 0', color: '#555' }}>By {layout.architect_name}</p>
      )}
      {layout.architect_email && (
        <p className="layout-author" style={{ margin: '0 0 6px 0', color: '#6b7280' }}>Email: {layout.architect_email}</p>
      )}
      {layout.description && (
        <p className="layout-description">{layout.description}</p>
      )}

      {/* Technical Details Preview */}
      {layout.technical_details && (
        <div className="technical-details-preview" style={{ marginTop: '12px', padding: '12px', background: '#f8f9fa', borderRadius: '6px', border: '1px solid #e9ecef' }}>
          <h5 style={{ margin: '0 0 8px 0', fontSize: '0.9rem', color: '#495057' }}>Technical Specifications</h5>
          <TechnicalDetailsDisplay
            technicalDetails={layout.technical_details}
            compact={true}
          />
        </div>
      )}

      <div className="layout-price">
        {layout.price_range && (
          <span className="price-range">₹{layout.price_range}</span>
        )}
      </div>
    </div>
  </div>
);

// Image/File Viewer Modal Component
const ImageViewer = ({ viewer, setViewer }) => {
  if (!viewer.open) return null;

  const isImage = /\.(jpg|jpeg|png|gif|webp|svg|heic)$/i.test(viewer.src);
  const isPdf = /\.(pdf)$/i.test(viewer.src);

  return (
    <div
      className="viewer-overlay"
      style={{
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        backgroundColor: 'rgba(0, 0, 0, 0.9)',
        zIndex: 9999,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '20px'
      }}
      onClick={() => setViewer({ open: false, src: '', title: '' })}
    >
      <div
        className="viewer-content"
        style={{
          position: 'relative',
          maxWidth: '90vw',
          maxHeight: '90vh',
          backgroundColor: 'white',
          borderRadius: '8px',
          overflow: 'hidden',
          boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
        }}
        onClick={(e) => e.stopPropagation()}
      >
        <div
          className="viewer-header"
          style={{
            padding: '16px 20px',
            borderBottom: '1px solid #e5e7eb',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            backgroundColor: '#f9fafb'
          }}
        >
          <h3 style={{ margin: 0, fontSize: '18px', fontWeight: '600', color: '#374151' }}>
            {viewer.title}
          </h3>
          <button
            onClick={() => setViewer({ open: false, src: '', title: '' })}
            style={{
              background: 'none',
              border: 'none',
              fontSize: '24px',
              cursor: 'pointer',
              color: '#6b7280',
              padding: '4px',
              borderRadius: '4px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center'
            }}
            title="Close viewer"
          >
            ×
          </button>
        </div>
        <div
          className="viewer-body"
          style={{
            padding: '20px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            minHeight: '400px',
            maxHeight: '70vh',
            overflow: 'auto'
          }}
        >
          {isImage ? (
            <img
              src={viewer.src}
              alt={viewer.title}
              style={{
                maxWidth: '100%',
                maxHeight: '100%',
                objectFit: 'contain',
                borderRadius: '4px'
              }}
            />
          ) : isPdf ? (
            <iframe
              src={viewer.src}
              style={{
                width: '100%',
                height: '600px',
                border: 'none',
                borderRadius: '4px'
              }}
              title={viewer.title}
            />
          ) : (
            <div style={{ textAlign: 'center', color: '#6b7280' }}>
              <div style={{ fontSize: '48px', marginBottom: '16px' }}>📄</div>
              <p style={{ margin: 0, fontSize: '16px' }}>Preview not available</p>
              <a
                href={viewer.src}
                target="_blank"
                rel="noopener noreferrer"
                style={{
                  display: 'inline-block',
                  marginTop: '12px',
                  padding: '8px 16px',
                  backgroundColor: '#3b82f6',
                  color: 'white',
                  textDecoration: 'none',
                  borderRadius: '6px',
                  fontSize: '14px'
                }}
              >
                Open File
              </a>
            </div>
          )}
        </div>
        <div
          className="viewer-footer"
          style={{
            padding: '12px 20px',
            borderTop: '1px solid #e5e7eb',
            backgroundColor: '#f9fafb',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center'
          }}
        >
          <a
            href={viewer.src}
            download
            style={{
              padding: '8px 16px',
              backgroundColor: '#10b981',
              color: 'white',
              textDecoration: 'none',
              borderRadius: '6px',
              fontSize: '14px',
              fontWeight: '500'
            }}
          >
            Download
          </a>
          <a
            href={viewer.src}
            target="_blank"
            rel="noopener noreferrer"
            style={{
              padding: '8px 16px',
              backgroundColor: '#6b7280',
              color: 'white',
              textDecoration: 'none',
              borderRadius: '6px',
              fontSize: '14px',
              fontWeight: '500'
            }}
          >
            Open in New Tab
          </a>
        </div>
      </div>
    </div>
  );
};

export default HomeownerDashboard;