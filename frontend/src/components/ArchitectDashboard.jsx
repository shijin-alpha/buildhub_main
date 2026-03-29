import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import jsPDF from 'jspdf';
import '../../styles/ArchitectSoftUI.css';
import '../../styles/ArchitectDashboard.css';

import '../styles/SoftSidebar.css';
import '../styles/HeaderProfile.css';
import '../styles/Widgets.css';
import './WidgetColors.css';
import { badgeClass, formatStatus } from '../utils/status';
import { useToast } from './ToastProvider';
import ArchitectProfileButton from './ArchitectProfileButton';
import StylishProfile from './StylishProfile';
import NeatJsonCard from './NeatJsonCard';
import TechnicalDetailsDisplay from './TechnicalDetailsDisplay';
import TechnicalDetailsForm from './TechnicalDetailsForm';
import TechnicalDetailsModal from './TechnicalDetailsModal';
import '../styles/TechnicalDetailsForm.css';
import InfoPopup from './InfoPopup';
import HousePlanManager from './HousePlanManager';
import RequirementsDisplay from './RequirementsDisplay';


const ArchitectDashboard = () => {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('dashboard');
  const [user, setUser] = useState(null);
  const [layoutRequests, setLayoutRequests] = useState([]); // Assigned requests
  const [myDesigns, setMyDesigns] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const toast = useToast();
  const [showUploadForm, setShowUploadForm] = useState(false);
  const [uploadFormStep, setUploadFormStep] = useState(0);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [showSidebarProfileMenu, setShowSidebarProfileMenu] = useState(false);

  // Profile state
  const [profile, setProfile] = useState({ specialization: '', experience_years: '', email: '', phone: '', city: '', avg_rating: null, review_count: 0 });
  const [profileLoading, setProfileLoading] = useState(false);
  const [profileSaving, setProfileSaving] = useState(false);
  const [showProfileDrawer, setShowProfileDrawer] = useState(false);
  // Reviews in profile drawer
  const [archReviews, setArchReviews] = useState([]);
  const [archReviewsLoading, setArchReviewsLoading] = useState(false);
  const [archReviewsError, setArchReviewsError] = useState('');
  const [showReviews, setShowReviews] = useState(false);

  // My library state
  const [libraryLayouts, setLibraryLayouts] = useState([]);
  const [showLibraryForm, setShowLibraryForm] = useState(false);
  const [libraryFormStep, setLibraryFormStep] = useState(0);
  const [expandedAssignments, setExpandedAssignments] = useState({});
  const [imageModal, setImageModal] = useState({ open: false, image: null, title: '' });
  const [expandedImages, setExpandedImages] = useState({});
  const [libraryForm, setLibraryForm] = useState({
    title: '', layout_type: '', bedrooms: '', bathrooms: '', area: '', price_range: '', view_price: 0, description: '', image: null, design_file: null, technical_details: {}
  });

  // Upload form state
  const [uploadData, setUploadData] = useState({
    request_id: '',
    homeowner_id: '', // optional: send directly to a homeowner
    design_title: '',
    description: '',
    technical_details: {},
    files: []
  });

  // House plan state
  const [showHousePlanManager, setShowHousePlanManager] = useState(false);
  const [selectedRequestForPlan, setSelectedRequestForPlan] = useState(null);

  // Contractor house plans state
  const [contractorHousePlans, setContractorHousePlans] = useState([]);
  const [contractorPlansLoading, setContractorPlansLoading] = useState(false);
  const [contractorPlansSummary, setContractorPlansSummary] = useState({
    total_plans: 0,
    total_contractors: 0,
    active_estimates: 0,
    completed_estimates: 0
  });

  // Technical Details Modal state
  const [showTechnicalModal, setShowTechnicalModal] = useState(false);
  const [selectedRequestForUpload, setSelectedRequestForUpload] = useState(null);
  const [technicalSubmissionLoading, setTechnicalSubmissionLoading] = useState(false);

  // Concept Preview Generation state
  const [conceptPreviewText, setConceptPreviewText] = useState('');
  const [conceptGenerationLoading, setConceptGenerationLoading] = useState(false);
  const [conceptPreviews, setConceptPreviews] = useState([]);
  const [selectedProjectForConcept, setSelectedProjectForConcept] = useState('');

  useEffect(() => {
    // Get user data from session
    const userData = JSON.parse(sessionStorage.getItem('user') || '{}');
    setUser(userData);

    // Strict: prevent cached back navigation showing dashboard
    import('../utils/session').then(({ preventCache, verifyServerSession }) => {
      preventCache();
      (async () => {
        const serverAuth = await verifyServerSession();
        if (!userData.id || userData.role !== 'architect' || !serverAuth) {
          sessionStorage.removeItem('user');
          localStorage.removeItem('bh_user');
          navigate('/login', { replace: true });
          return;
        }
        // proceed
        fetchLayoutRequests();
        fetchMyDesigns();
        fetchMyLibrary();
        fetchMyProfile();
        fetchContractorHousePlans();
        fetchConceptPreviews();
      })();
    });
  }, []);

  // Poll for concept preview updates
  useEffect(() => {
    let pollInterval;
    
    // Check if there are any processing/generating concepts
    const hasProcessingConcepts = conceptPreviews.some(
      preview => preview.status === 'processing' || preview.status === 'generating'
    );
    
    if (hasProcessingConcepts) {
      // Poll every 5 seconds for updates
      pollInterval = setInterval(() => {
        fetchConceptPreviews();
      }, 5000);
    }
    
    return () => {
      if (pollInterval) {
        clearInterval(pollInterval);
      }
    };
  }, [conceptPreviews]);

  const fetchLayoutRequests = async () => {
    setLoading(true);
    try {
      const response = await fetch('/buildhub/backend/api/architect/get_assigned_requests.php');
      const result = await response.json();
      if (result.success) {
        // Convert assignments to requests format for compatibility
        const assignments = result.assignments || [];
        // Only show ACCEPTED assignments in "Your Assigned Projects" section
        const acceptedAssignments = assignments.filter(assignment => 
          assignment.assignment_status === 'accepted'
        );
        const requests = acceptedAssignments.map(assignment => ({
          ...assignment.layout_request,
          assignment_id: assignment.assignment_id,
          assignment_status: assignment.assignment_status,
          assigned_at: assignment.assigned_at,
          assignment_message: assignment.message,
          homeowner_name: assignment.homeowner.name,
          homeowner_email: assignment.homeowner.email,
          homeowner_id: assignment.homeowner.id
        }));
        setLayoutRequests(requests);
      }
    } catch (error) {
      console.error('Error fetching requests:', error);
    } finally {
      setLoading(false);
    }
  };


  // Comprehensive refresh function for all dashboard data
  const refreshDashboard = async () => {
    setLoading(true);
    try {
      await Promise.all([
        fetchLayoutRequests(),
        fetchMyDesigns(),
        fetchMyLibrary(),
        fetchMyProfile(),
        fetchContractorHousePlans(),
        fetchConceptPreviews()
      ]);
      toast.success('Dashboard refreshed successfully');
    } catch (error) {
      console.error('Error refreshing dashboard:', error);
      toast.error('Failed to refresh dashboard');
    } finally {
      setLoading(false);
    }
  };

  // Set up global refresh functions for real-time updates
  useEffect(() => {
    // Make refresh functions globally available for real-time updates
    window.refreshDashboard = refreshDashboard;
    window.refreshHousePlans = () => {
      // Refresh house plans specifically
      fetchMyDesigns();
    };
    
    // Cleanup on unmount
    return () => {
      delete window.refreshDashboard;
      delete window.refreshHousePlans;
    };
  }, []);

  // Auto-refresh dashboard data every 2 minutes for real-time updates
  useEffect(() => {
    const autoRefreshInterval = setInterval(() => {
      // Only auto-refresh if user is on dashboard tab and not actively working
      if (activeTab === 'dashboard' && !loading) {
        fetchLayoutRequests();
        fetchMyDesigns();
        fetchContractorHousePlans();
      }
    }, 120000); // 2 minutes

    return () => clearInterval(autoRefreshInterval);
  }, [activeTab, loading]);

  // Download project details as PDF
  const downloadProjectPDF = (request) => {
    const doc = new jsPDF();
    let yPos = 20;

    // Title
    doc.setFontSize(18);
    doc.setFont(undefined, 'bold');
    doc.text('Project Details', 105, yPos, { align: 'center' });
    yPos += 10;

    // Project ID
    doc.setFontSize(12);
    doc.setFont(undefined, 'normal');
    doc.text(`Project ID: ${request.id}`, 20, yPos);
    yPos += 8;

    // Client Information
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.text('Client Information', 20, yPos);
    yPos += 8;
    
    doc.setFontSize(11);
    doc.setFont(undefined, 'normal');
    doc.text(`Name: ${request.homeowner_name || 'Not specified'}`, 20, yPos);
    yPos += 7;
    doc.text(`Email: ${request.homeowner_email || 'Not specified'}`, 20, yPos);
    yPos += 7;
    doc.text(`Location: ${request.location || 'Not specified'}`, 20, yPos);
    yPos += 10;

    // Project Specifications
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.text('Project Specifications', 20, yPos);
    yPos += 8;
    
    doc.setFontSize(11);
    doc.setFont(undefined, 'normal');
    doc.text(`Plot Size: ${request.plot_size || 'Not specified'}`, 20, yPos);
    yPos += 7;
    doc.text(`Building Size: ${request.building_size || 'Not specified'}`, 20, yPos);
    yPos += 7;
    doc.text(`Budget Range: ${request.budget_range || 'Not specified'}`, 20, yPos);
    yPos += 7;
    doc.text(`Number of Floors: ${request.num_floors || 'Not specified'}`, 20, yPos);
    yPos += 7;
    doc.text(`Timeline: ${request.timeline || 'Not specified'}`, 20, yPos);
    yPos += 10;

    // Floor-wise Room Distribution
    const floorRoomsData = request.floor_rooms || request.requirements_parsed?.floor_rooms;
    if (floorRoomsData && (Array.isArray(floorRoomsData) ? floorRoomsData.length > 0 : Object.keys(floorRoomsData || {}).length > 0)) {
      doc.setFontSize(14);
      doc.setFont(undefined, 'bold');
      doc.text('Floor-wise Room Distribution', 20, yPos);
      yPos += 8;
      
      doc.setFontSize(11);
      doc.setFont(undefined, 'normal');
      
      const floorRooms = Array.isArray(floorRoomsData) ? floorRoomsData : Object.entries(floorRoomsData);
      
      floorRooms.forEach((item, floorIdx) => {
        let floorData, floorNumber;
        if (Array.isArray(floorRoomsData)) {
          floorData = item.rooms;
          floorNumber = parseInt(item.floor || floorIdx + 1);
        } else {
          const [floorKey, floorRooms] = item;
          floorData = floorRooms;
          floorNumber = parseInt(floorKey.replace('floor', ''));
        }
        
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text(floorNumber === 1 ? 'Ground Floor' : `Floor ${floorNumber}`, 25, yPos);
        yPos += 7;
        
        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');
        Object.entries(floorData || {}).forEach(([roomType, count]) => {
          const roomCount = typeof count === 'number' ? count : (count.length || 0);
          if (roomCount > 0) {
            doc.text(`  • ${roomType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}: ${roomCount}`, 30, yPos);
            yPos += 6;
          }
        });
        yPos += 4;
      });
      yPos += 5;
    }

    // Additional Details
    if (request.requirements_parsed) {
      doc.setFontSize(14);
      doc.setFont(undefined, 'bold');
      doc.text('Additional Requirements', 20, yPos);
      yPos += 8;
      
      doc.setFontSize(11);
      doc.setFont(undefined, 'normal');
      
      if (request.requirements_parsed.plot_shape) {
        doc.text(`Plot Shape: ${request.requirements_parsed.plot_shape}`, 20, yPos);
        yPos += 7;
      }
      if (request.requirements_parsed.topography) {
        doc.text(`Topography: ${request.requirements_parsed.topography}`, 20, yPos);
        yPos += 7;
      }
      if (request.requirements_parsed.aesthetic) {
        doc.text(`Style: ${request.requirements_parsed.aesthetic}`, 20, yPos);
        yPos += 7;
      }
      if (request.requirements_parsed.site_considerations) {
        doc.text(`Site Considerations: ${request.requirements_parsed.site_considerations}`, 20, yPos);
        yPos += 7;
      }
    }

    // Footer
    yPos = 280;
    doc.setFontSize(9);
    doc.setFont(undefined, 'italic');
    doc.text(`Generated on ${new Date().toLocaleString()}`, 105, yPos, { align: 'center' });

    // Save PDF
    const fileName = `Project_${request.id}_${request.homeowner_name?.replace(/\s+/g, '_') || 'Details'}.pdf`;
    doc.save(fileName);
    toast.success('PDF downloaded successfully');
  };

  const fetchMyDesigns = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/architect/get_my_designs.php');
      const result = await response.json();
      if (result.success) {
        setMyDesigns(result.designs || []);
      }
    } catch (error) {
      console.error('Error fetching designs:', error);
    }
  };

  const fetchConceptPreviews = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/architect/get_concept_previews.php');
      const result = await response.json();
      if (result.success) {
        setConceptPreviews(result.previews || []);
      }
    } catch (error) {
      console.error('Error fetching concept previews:', error);
    }
  };

  // Sidebar counts
  const requestsCount = Array.isArray(layoutRequests) ? layoutRequests.length : 0;
  // Count logic tolerant to different backend fields
  const normalize = (v) => String(v || '').toLowerCase();
  const pendingRequestsCount = Array.isArray(layoutRequests)
    ? layoutRequests.filter(r => {
        const s = normalize(r.status);
        const anyPending = s === 'pending' || s === 'awaiting' || s === 'in_review' || s === 'processing';
        const byCounters = (Number(r.accepted_count) || 0) === 0 && (Number(r.rejected_count) || 0) === 0;
        return anyPending || byCounters;
      }).length
    : 0;
  const acceptedRequestsCount = Array.isArray(layoutRequests)
    ? layoutRequests.filter(r => {
        const s = normalize(r.status);
        const byStatus = s === 'accepted' || s === 'approved' || s === 'finalized' || s === 'completed';
        const byCounters = (Number(r.accepted_count) || 0) > 0;
        return byStatus || byCounters;
      }).length
    : 0;
  const designsCount = Array.isArray(myDesigns) ? myDesigns.length : 0;
  const libraryCount = Array.isArray(libraryLayouts) ? libraryLayouts.length : 0;

  // Lightweight periodic refresh for counts
  useEffect(() => {
    let mounted = true;
    const refreshCounts = async () => {
      try {
        const r1 = await fetch('/buildhub/backend/api/architect/get_assigned_requests.php');
        const j1 = await r1.json().catch(() => ({}));
        if (mounted && j1?.success) {
          const assignments = j1.assignments || [];
          const requests = assignments.map(assignment => ({
            ...assignment.layout_request,
            assignment_id: assignment.assignment_id,
            assignment_status: assignment.assignment_status,
            assigned_at: assignment.assigned_at,
            assignment_message: assignment.message,
            homeowner_name: assignment.homeowner.name,
            homeowner_email: assignment.homeowner.email,
            homeowner_id: assignment.homeowner.id
          }));
          setLayoutRequests(requests);
        }
      } catch {}
      try {
        const r2 = await fetch('/buildhub/backend/api/architect/get_layout_requests.php');
        const j2 = await r2.json().catch(() => ({}));
        if (mounted && j2?.success) setAvailableRequests(Array.isArray(j2.requests) ? j2.requests : []);
      } catch {}
      try {
        const r3 = await fetch('/buildhub/backend/api/architect/get_my_designs.php');
        const j3 = await r3.json().catch(() => ({}));
        if (mounted && j3?.success) setMyDesigns(Array.isArray(j3.designs) ? j3.designs : []);
      } catch {}
      try {
        const r4 = await fetch('/buildhub/backend/api/architect/get_my_layouts.php');
        const j4 = await r4.json().catch(() => ({}));
        if (mounted && j4?.success) setLibraryLayouts(Array.isArray(j4.layouts) ? j4.layouts : []);
      } catch {}
    };
    refreshCounts();
    const id = setInterval(refreshCounts, 60000);
    return () => { mounted = false; clearInterval(id); };
  }, []);

  // Mark a design as finalized
  const finalizeDesign = async (designId) => {
    try {
      const res = await fetch('/buildhub/backend/api/architect/finalize_design.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ design_id: designId }),
      });
      const json = await res.json();
      if (json.success) {
        toast.success('Design finalized');
        // Update list locally without full refetch
        setMyDesigns(prev => prev.map(d => d.id === designId ? { ...d, status: 'finalized' } : d));
      } else {
        toast.error(json.message || 'Failed to finalize');
      }
    } catch (e) {
      toast.error('Network error while finalizing');
    }
  };

  const fetchMyProfile = async () => {
    setProfileLoading(true);
    try {
      const res = await fetch('/buildhub/backend/api/architect/get_my_profile.php');
      const json = await res.json();
      if (json.success) {
        const p = json.profile || {};
        setProfile({
          specialization: p.specialization || '',
          experience_years: p.experience_years ?? '',
          email: p.email || '',
          phone: p.phone || '',
          city: p.city || '',
          avg_rating: p.avg_rating ?? null,
          review_count: p.review_count || 0,
        });
        // Fetch reviews for this architect (self)
        if (json.profile?.id || sessionStorage.getItem('user')) {
          const me = JSON.parse(sessionStorage.getItem('user') || '{}');
          const myId = json.profile?.id || me.id;
          if (myId) {
            setArchReviewsLoading(true);
            setArchReviewsError('');
            try {
              const r = await fetch(`/buildhub/backend/api/reviews/get_reviews.php?architect_id=${myId}`);
              const rj = await r.json();
              if (rj.success) {
                setArchReviews(Array.isArray(rj.reviews) ? rj.reviews : []);
                // also sync avg + count if backend computes differently
                if (typeof rj.avg_rating === 'number') setProfile(prev => ({...prev, avg_rating: rj.avg_rating}));
                if (typeof rj.review_count === 'number') setProfile(prev => ({...prev, review_count: rj.review_count}));
              } else {
                setArchReviewsError(rj.message || 'Failed to load reviews');
              }
            } catch {
              setArchReviewsError('Network error while loading reviews');
            } finally {
              setArchReviewsLoading(false);
            }
          }
        }
      }
    } catch (e) {
      // non-blocking
    } finally {
      setProfileLoading(false);
    }
  };

  const saveMyProfile = async (e) => {
    e?.preventDefault?.();
    setProfileSaving(true);
    try {
      const payload = {
        specialization: profile.specialization,
        experience_years: profile.experience_years === '' ? null : Number(profile.experience_years),
        phone: profile.phone,
        city: profile.city,
      };
      const res = await fetch('/buildhub/backend/api/architect/update_my_profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const json = await res.json();
      if (json.success) {
        setSuccess('Profile updated');
        fetchMyProfile();
      } else {
        setError(json.message || 'Failed to update profile');
      }
    } catch (e) {
      setError('Error updating profile');
    } finally {
      setProfileSaving(false);
    }
  };

  const fetchMyLibrary = async () => {
    try {
      const res = await fetch('/buildhub/backend/api/architect/get_my_layouts.php');
      const json = await res.json();
      if (json.success) setLibraryLayouts(json.layouts || []);
    } catch (e) { console.error('Error fetching my layouts', e); }
  };

  const fetchContractorHousePlans = async () => {
    setContractorPlansLoading(true);
    try {
      const response = await fetch('/buildhub/backend/api/architect/get_contractor_house_plans.php');
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const result = await response.json();
      
      if (result.success) {
        setContractorHousePlans(result.house_plans || []);
        setContractorPlansSummary(result.summary || {
          total_plans: 0,
          total_contractors: 0,
          active_estimates: 0,
          completed_estimates: 0
        });
        
        if (result.message) {
          console.log('Contractor house plans info:', result.message);
        }
      } else {
        console.error('Failed to fetch contractor house plans:', result.message);
        if (result.debug) {
          console.error('Debug info:', result.debug);
        }
        
        // Set empty state but don't show error to user unless it's critical
        setContractorHousePlans([]);
        setContractorPlansSummary({
          total_plans: 0,
          total_contractors: 0,
          active_estimates: 0,
          completed_estimates: 0
        });
        
        // Only show error toast for critical errors
        if (result.message && !result.message.includes('No house plans found')) {
          toast.error('Failed to load contractor house plans: ' + result.message);
        }
      }
    } catch (error) {
      console.error('Error fetching contractor house plans:', error);
      
      // Set empty state
      setContractorHousePlans([]);
      setContractorPlansSummary({
        total_plans: 0,
        total_contractors: 0,
        active_estimates: 0,
        completed_estimates: 0
      });
      
      // Show user-friendly error message
      toast.error('Unable to load contractor work data. Please try refreshing.');
    } finally {
      setContractorPlansLoading(false);
    }
  };

  const submitNewLibraryItem = async (e) => {
    if (e) e.preventDefault();
    const fd = new FormData();
    Object.entries(libraryForm).forEach(([k,v]) => {
      if (v !== null && v !== '') {
        fd.append(k, v);
      }
    });
    try {
      const res = await fetch('/buildhub/backend/api/architect/create_layout_library_item.php', {
        method: 'POST',
        body: fd
      });
      const json = await res.json();
      if (json.success) {
        setSuccess('Layout added to library');
        setShowLibraryForm(false);
        setLibraryFormStep(0);
        setLibraryForm({ title:'', layout_type:'', bedrooms:'', bathrooms:'', area:'', price_range:'', description:'', image:null, design_file:null, technical_details: {} });
        fetchMyLibrary();
      } else {
        setError(json.message || 'Failed to add layout');
      }
    } catch (e) {
      setError('Error adding layout');
    }
  };

  const [editLayout, setEditLayout] = useState(null);

  // Library form navigation
  const nextLibraryStep = () => setLibraryFormStep(s => Math.min(s + 1, 1));
  const prevLibraryStep = () => setLibraryFormStep(s => Math.max(s - 1, 0));
  

  const openEditLayout = (item) => {
    setEditLayout({ ...item, image: null, design_file: null });
  };

  const closeEditLayout = () => setEditLayout(null);

  const saveEditLayout = async (e) => {
    e.preventDefault();
    if (!editLayout) return;
    const fd = new FormData();
    fd.append('id', editLayout.id);
    ['title','layout_type','bedrooms','bathrooms','area','price_range','description','status','view_price'].forEach(k=>{
      if (editLayout[k] !== undefined && editLayout[k] !== null && editLayout[k] !== '') fd.append(k, editLayout[k]);
    });
    if (editLayout.image) fd.append('image', editLayout.image);
    if (editLayout.design_file) fd.append('design_file', editLayout.design_file);
    try {
      const res = await fetch('/buildhub/backend/api/architect/update_layout_library_item.php', { method: 'POST', body: fd });
      const json = await res.json();
      if (json.success) {
        setSuccess('Layout updated');
        setEditLayout(null);
        fetchMyLibrary();
        setTimeout(() => setSuccess(''), 3000);
      } else {
        setError(json.message || 'Failed to update layout');
        setTimeout(() => setError(''), 3000);
      }
    } catch (e) {
      setError('Error updating layout');
      setTimeout(() => setError(''), 3000);
    }
  };

  const toggleLayoutStatus = async (item) => {
    console.log('=== TOGGLE LAYOUT STATUS DEBUG ===');
    console.log('Item:', item);
    console.log('Item ID:', item?.id, 'Type:', typeof item?.id);
    
    if (!item) {
      setError('Invalid layout item');
      setTimeout(() => setError(''), 3000);
      return;
    }
    
    const layoutId = item.id;
    console.log('Layout ID:', layoutId);
    
    if (layoutId === undefined || layoutId === null) {
      console.error('Layout ID is missing or invalid');
      setError('Layout ID is missing');
      setTimeout(() => setError(''), 3000);
      return;
    }
    
    const target = item.status === 'active' ? 'inactive' : 'active';
    const payload = { id: Number(layoutId), status: target };
    
    console.log('Payload:', payload);
    
    try {
      const res = await fetch('/buildhub/backend/api/architect/update_layout_library_item.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      console.log('Response:', json);
      if (json.success) {
        setLibraryLayouts(prev => prev.map(x => x.id === layoutId ? { ...x, status: target } : x));
        setSuccess(`Layout ${target === 'active' ? 'activated' : 'deactivated'} successfully`);
        setTimeout(() => setSuccess(''), 3000);
      } else {
        setError(json.message || 'Failed to change status');
        setTimeout(() => setError(''), 3000);
      }
    } catch (e) {
      setError('Error changing status');
      setTimeout(() => setError(''), 3000);
    }
  };

  // Handle assignment response (accept/decline)
  const handleAssignmentResponse = async (assignmentId, action) => {
    try {
      setLoading(true);
      const response = await fetch('/buildhub/backend/api/architect/respond_assignment.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          assignment_id: assignmentId,
          action: action
        })
      });

      const result = await response.json();
      if (result.success) {
        toast.success(`Assignment ${action}ed successfully`);
        // Refresh the requests to show updated status
        fetchLayoutRequests();
      } else {
        toast.error(result.message || `Failed to ${action} assignment`);
      }
    } catch (error) {
      console.error('Error responding to assignment:', error);
      toast.error('Network error occurred');
    } finally {
      setLoading(false);
    }
  };

  // Handle technical details submission for upload design
  // Handle technical details submission for upload design
  const handleTechnicalDetailsSubmit = async (technicalDetails, uploadFilesFunction) => {
    if (!selectedRequestForUpload) return;
    
    setTechnicalSubmissionLoading(true);
    
    try {
      // Create a plan data object for the submission
      const planData = {
        plan_name: `${selectedRequestForUpload.homeowner_name || 'Client'} House Plan`,
        plot_width: parseFloat(selectedRequestForUpload.plot_size) || 100,
        plot_height: parseFloat(selectedRequestForUpload.plot_size) || 100,
        rooms: [], // Empty rooms array for upload design
        scale_ratio: 1.2,
        total_layout_area: 0,
        total_construction_area: 0,
        floors: {
          total_floors: selectedRequestForUpload.floors || 1,
          current_floor: 1,
          floor_names: { 1: 'Ground Floor' }
        }
      };

      console.log('Creating house plan for upload design:', {
        layout_request_id: selectedRequestForUpload.id,
        plan_data: planData
      });

      // Step 1: Create the house plan first
      const createPlanPayload = {
        layout_request_id: selectedRequestForUpload.id,
        plan_name: planData.plan_name,
        plot_width: planData.plot_width,
        plot_height: planData.plot_height,
        plan_data: planData,
        notes: 'Upload Design with Technical Details'
      };

      const createResponse = await fetch('/buildhub/backend/api/architect/create_house_plan.php', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify(createPlanPayload)
      });

      if (!createResponse.ok) {
        throw new Error(`Failed to create house plan: HTTP ${createResponse.status}`);
      }

      const createResult = await createResponse.json();
      
      if (!createResult.success) {
        throw new Error(createResult.message || 'Failed to create house plan');
      }

      const planId = createResult.plan_id;
      console.log('House plan created successfully with ID:', planId);

      // Step 2: Upload files if there are any and get updated technical details
      let updatedTechnicalDetails = technicalDetails;
      if (uploadFilesFunction) {
        console.log('Uploading files for plan ID:', planId);
        updatedTechnicalDetails = await uploadFilesFunction(planId);
      }

      // Step 3: Submit the plan with updated technical details (after file upload)
      const submissionPayload = {
        plan_id: planId, // Use the created plan ID
        plan_data: planData,
        technical_details: updatedTechnicalDetails // Use updated technical details with file info
      };

      console.log('Submitting plan with technical details:', submissionPayload);

      const submitResponse = await fetch('/buildhub/backend/api/architect/submit_house_plan_with_details.php', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify(submissionPayload)
      });

      if (!submitResponse.ok) {
        throw new Error(`Failed to submit plan: HTTP ${submitResponse.status}`);
      }

      const submitResult = await submitResponse.json();
      
      if (submitResult.success) {
        setShowTechnicalModal(false);
        setSelectedRequestForUpload(null);
        toast.success(
          'Design Uploaded Successfully!', 
          `Your design with technical details has been submitted to the homeowner for review.`
        );
        
        // Refresh the requests to show updated status
        fetchLayoutRequests();
      } else {
        throw new Error(submitResult.message || 'Upload failed');
      }
    } catch (error) {
      console.error('Error uploading design:', error);
      toast.error(
        'Upload Failed', 
        `Failed to upload design: ${error.message}. Please try again.`
      );
    } finally {
      setTechnicalSubmissionLoading(false);
    }
  };

  // Preview modal for clear viewing of image and layout file
  const [previewItem, setPreviewItem] = useState(null);
  const openPreview = (item) => setPreviewItem(item);
  const closePreview = () => setPreviewItem(null);

  const isImageUrl = (url) => /\.(png|jpe?g|gif|webp|bmp)$/i.test(url || '');
  const isPdfUrl = (url) => /\.(pdf)$/i.test(url || '');

  const renderPreviewModal = () => {
    if (!previewItem) return null;
    const imgUrl = previewItem.image_url || null;
    const fileUrl = previewItem.design_file_url || null;
    const architectName = (user?.first_name || '') + ' ' + (user?.last_name || '');
    const architectEmail = user?.email || '';

    const canEmbed = fileUrl && (isImageUrl(fileUrl) || isPdfUrl(fileUrl));

    return (
      <div className="form-modal" onClick={closePreview}>
        <div className="form-content" style={{maxWidth:'90vw', width:'1100px'}} onClick={(e)=>e.stopPropagation()}>
          <div className="form-header" style={{display:'flex', justifyContent:'space-between', alignItems:'center'}}>
            <div>
              <h3 style={{margin:0}}>{previewItem.title || 'Preview'}</h3>
              {previewItem.layout_type && <p style={{margin:'4px 0 0 0', color:'#64748b'}}>{previewItem.layout_type}</p>}
            </div>
            <button className="btn btn-secondary" onClick={closePreview}>Close</button>
          </div>

          <div style={{display:'grid', gridTemplateColumns:'1.2fr .8fr', gap:'16px'}}>
            <div style={{border:'1px solid #e5e7eb', borderRadius:10, overflow:'hidden', background:'#000'}}>
              {canEmbed ? (
                isImageUrl(fileUrl) ? (
                  <img src={fileUrl} alt="Layout" style={{width:'100%', height:'80vh', objectFit:'contain', background:'#000'}}/>
                ) : (
                  <iframe src={fileUrl} title="Layout PDF" style={{width:'100%', height:'80vh', border:'none', background:'#fff'}}/>
                )
              ) : imgUrl ? (
                <img src={imgUrl} alt="Preview" style={{width:'100%', height:'80vh', objectFit:'contain'}}/>
              ) : (
                <div style={{padding:24}}>No preview available. You can download the layout file from the card.</div>
              )}
            </div>

            <div>
              {imgUrl && (
                <div style={{border:'1px solid #e5e7eb', borderRadius:10, overflow:'hidden', background:'#fff'}}>
                  <img src={imgUrl} alt="Preview" style={{width:'100%', height:260, objectFit:'cover'}}/>
                </div>
              )}
              <div className="drawer-section" style={{marginTop:16}}>
                <h4>Details</h4>
                <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:8}}>
                  <div><strong>Bedrooms:</strong> {previewItem.bedrooms ?? '-'}</div>
                  <div><strong>Bathrooms:</strong> {previewItem.bathrooms ?? '-'}</div>
                  <div><strong>Area:</strong> {previewItem.area ? `${previewItem.area} sq ft` : '-'}</div>
                  <div><strong>Price:</strong> {previewItem.price_range ?? '-'}</div>
                  <div><strong>Architect:</strong> {architectName.trim() || 'You'}</div>
                  <div><strong>Email:</strong> {architectEmail || '-'}</div>
                </div>
                 {previewItem.description && <p style={{marginTop:10, whiteSpace:'pre-wrap'}}>{previewItem.description}</p>}
                 
                 {/* Technical Details in Preview */}
                 {previewItem.technical_details && (
                   <div style={{marginTop: '16px', padding: '12px', background: '#f8f9fa', borderRadius: '6px', border: '1px solid #e9ecef'}}>
                     <h5 style={{margin: '0 0 8px 0', fontSize: '0.9rem', color: '#495057'}}>Technical Specifications</h5>
                     <TechnicalDetailsDisplay 
                       technicalDetails={previewItem.technical_details} 
                       compact={true}
                     />
                   </div>
                 )}
                 
                 {fileUrl && !canEmbed && (
                   <a className="btn btn-primary" href={fileUrl} target="_blank" rel="noreferrer" style={{marginTop:10, display:'inline-block'}}>Download Layout</a>
                 )}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  };

  // Handle concept preview generation
  const handleConceptPreviewGeneration = async () => {
    if (!conceptPreviewText.trim()) {
      toast.error('Please enter a concept description');
      return;
    }

    if (!selectedProjectForConcept) {
      toast.error('Please select a project');
      return;
    }

    setConceptGenerationLoading(true);

    try {
      const payload = {
        layout_request_id: selectedProjectForConcept,
        concept_description: conceptPreviewText.trim()
      };

      const response = await fetch('/buildhub/backend/api/architect/generate_concept_preview.php', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify(payload)
      });

      // Debug: Check what we're actually getting back
      const responseText = await response.text();
      console.log('Raw response:', responseText);
      console.log('Response length:', responseText.length);
      console.log('Response at position 78:', responseText.charAt(78));
      
      let result;
      try {
        result = JSON.parse(responseText);
      } catch (parseError) {
        console.error('JSON parse error:', parseError);
        console.error('Response text:', responseText);
        throw new Error(`Invalid JSON response: ${responseText.substring(0, 200)}...`);
      }
      
      if (result.success) {
        toast.success('Concept Preview Generation Started', 'Your concept preview is being generated. You can monitor progress below.');
        setConceptPreviewText('');
        setSelectedProjectForConcept('');
        fetchConceptPreviews(); // Refresh the list
      } else {
        throw new Error(result.message || 'Failed to start concept generation');
      }
    } catch (error) {
      console.error('Error generating concept preview:', error);
      toast.error('Generation Failed', error.message);
    } finally {
      setConceptGenerationLoading(false);
    }
  };

  // Handle concept preview regeneration
  const handleConceptRegeneration = async (previewId) => {
    try {
      const response = await fetch('/buildhub/backend/api/architect/regenerate_concept_preview.php', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify({ preview_id: previewId })
      });

      const result = await response.json();
      
      if (result.success) {
        toast.success('Concept Preview Regeneration Started', 'Your concept preview is being regenerated.');
        fetchConceptPreviews(); // Refresh the list
      } else {
        throw new Error(result.message || 'Failed to regenerate concept');
      }
    } catch (error) {
      console.error('Error regenerating concept preview:', error);
      toast.error('Regeneration Failed', error.message);
    }
  };

  // Handle concept preview download
  const handleDownloadConcept = async (preview) => {
    try {
      if (!preview.image_url) {
        toast.error('Download Failed', 'No image available for download');
        return;
      }

      // Use the download API endpoint for better filename and security
      const downloadUrl = `/buildhub/backend/api/architect/download_concept_preview.php?preview_id=${preview.id}`;
      
      // Create a temporary link to trigger download
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.target = '_blank';
      
      // Trigger download
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      toast.success('Download Started', 'Concept preview image is being downloaded.');
    } catch (error) {
      console.error('Error downloading concept preview:', error);
      toast.error('Download Failed', 'Failed to download the concept preview image.');
    }
  };

  // Handle concept preview deletion
  const handleDeleteConcept = async (previewId) => {
    // Show confirmation dialog
    const confirmed = window.confirm(
      'Are you sure you want to delete this concept preview? This action cannot be undone.'
    );
    
    if (!confirmed) {
      return;
    }

    try {
      const response = await fetch('/buildhub/backend/api/architect/delete_concept_preview.php', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify({ preview_id: previewId })
      });

      const result = await response.json();
      
      if (result.success) {
        toast.success('Concept Deleted', 'The concept preview has been deleted successfully.');
        fetchConceptPreviews(); // Refresh the list
      } else {
        throw new Error(result.message || 'Failed to delete concept');
      }
    } catch (error) {
      console.error('Error deleting concept preview:', error);
      toast.error('Deletion Failed', error.message);
    }
  };

  // Render the preview modal at the root of component output

  const handleLogout = async () => {
    try { await fetch('/buildhub/backend/api/logout.php', { method: 'POST', credentials: 'include' }); } catch {}
    localStorage.removeItem('bh_user');
    sessionStorage.removeItem('user');
    navigate('/login', { replace: true });
  };

  const handleUploadSubmit = async (e) => {
    e.preventDefault();
    // Only require files; backend will auto-route to the appropriate homeowner/request and default title
    if (uploadData.files.length === 0) {
      setError('Please select at least one file');
      return;
    }

    try {
      setLoading(true);
      const formData = new FormData();
      if (uploadData.request_id) formData.append('request_id', uploadData.request_id);
      if (uploadData.homeowner_id) formData.append('homeowner_id', uploadData.homeowner_id);
      if (uploadData.design_title) formData.append('design_title', uploadData.design_title);
      if (uploadData.description) formData.append('description', uploadData.description);
      if (uploadData.technical_details && Object.keys(uploadData.technical_details).length > 0) {
        formData.append('technical_details', JSON.stringify(uploadData.technical_details));
      }
      // Include architect-set view price if entered in technical details flow
      if (typeof uploadData.view_price !== 'undefined' && uploadData.view_price !== null && String(uploadData.view_price).trim() !== '') {
        formData.append('view_price', String(uploadData.view_price));
      }
      
      // Handle multiple files
      for (let i = 0; i < uploadData.files.length; i++) {
        formData.append('design_files[]', uploadData.files[i]);
      }

      const response = await fetch('/buildhub/backend/api/architect/upload_design.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();
      if (result.success) {
        setSuccess('Design uploaded successfully!');
        setShowUploadForm(false);
        setUploadData({
          request_id: '',
          homeowner_id: '',
          design_title: '',
          description: '',
          technical_details: {},
          files: []
        });
        setUploadFormStep(0);
        fetchMyDesigns();
      } else {
        setError('Failed to upload design: ' + result.message);
      }
    } catch (error) {
      setError('Error uploading design');
    } finally {
      setLoading(false);
    }
  };

  const renderDashboard = () => (
    <div>
      {/* Main Header */}
      <div className="main-header">
        <div className="header-content">
          <div>
            <h1>Dashboard</h1>
            <p>Manage your architectural designs and connect with clients</p>
          </div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
            <button 
              className="btn btn-secondary" 
              onClick={refreshDashboard}
              disabled={loading}
              style={{ display: 'flex', alignItems: 'center', gap: '6px' }}
            >
              {loading ? 'Refreshing...' : '🔄 Refresh Dashboard'}
            </button>
          <div className="header-profile">
            <ArchitectProfileButton 
              user={user}
              position="bottom-right"
              onProfileClick={() => setActiveTab('profile')}
              onLogout={handleLogout}
            />
            </div>
          </div>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="stats-grid">
        <div className="stat-card w-purple">
          <div className="stat-content">
            <div className="stat-icon requests">📋</div>
            <div className="stat-info">
              <h3>{layoutRequests.length}</h3>
              <p>Available Requests</p>
            </div>
          </div>
        </div>
        <div className="stat-card w-blue">
          <div className="stat-content">
            <div className="stat-icon designs">🎨</div>
            <div className="stat-info">
              <h3>{myDesigns.length}</h3>
              <p>Designs Created</p>
            </div>
          </div>
        </div>
        <div className="stat-card w-green">
          <div className="stat-content">
            <div className="stat-icon approved">✅</div>
            <div className="stat-info">
              <h3>{myDesigns.filter(d => d.status === 'approved').length}</h3>
              <p>Approved Designs</p>
            </div>
          </div>
        </div>
        <div className="stat-card w-orange">
          <div className="stat-content">
            <div className="stat-icon progress">⏳</div>
            <div className="stat-info">
              <h3>{myDesigns.filter(d => d.status === 'in-progress').length}</h3>
              <p>In Progress</p>
            </div>
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="section-card">
        <div className="section-header">
          <h2>Quick Actions</h2>
          <p>Get started with your architectural projects</p>
        </div>
        <div className="section-content">
          <div className="quick-actions float-grid stagger-children">
            <button 
              className="float-rect w-blue"
              onClick={() => setActiveTab('requests')}
            >
              <div className="fr-icon">📐</div>
              <div className="fr-title">Upload New Design</div>
              <div className="fr-sub">Create and submit architectural designs for client requests</div>
            </button>
            <button 
              className="float-rect w-orange"
              onClick={() => setActiveTab('requests')}
            >
              <div className="fr-icon">👁️</div>
              <div className="fr-title">Browse Requests</div>
              <div className="fr-sub">View available client requests waiting for designs</div>
            </button>
            <button 
              className="float-rect w-purple"
              onClick={() => setActiveTab('designs')}
            >
              <div className="fr-icon">🎨</div>
              <div className="fr-title">My Portfolio</div>
              <div className="fr-sub">Manage your submitted designs and track their status</div>
            </button>
          </div>
        </div>
      </div>

      {/* Recent Activity */}
      <div className="section-card">
        <div className="section-header">
          <h2>Recent Activity</h2>
          <p>Latest updates on your designs and client requests</p>
        </div>
        <div className="section-content">
          <div className="item-list">
            {myDesigns.slice(0, 5).map(design => (
              <div key={design.id} className="list-item">
                <div className="item-icon">
                  {design.status === 'approved' ? '✅' : 
                   design.status === 'rejected' ? '❌' : '🎨'}
                </div>
                <div className="item-content">
                  <h4 className="item-title">{design.design_title}</h4>
                  <p className="item-subtitle">Client: {design.client_name}</p>
                  <p className="item-meta">Submitted: {new Date(design.created_at).toLocaleDateString()}</p>
                </div>
                <div className="item-actions" style={{display:'flex', alignItems:'center', gap:8}}>
                  <span className={`status-badge ${badgeClass(design.status)}`}>
                    {formatStatus(design.status)}
                  </span>
                  {design.status !== 'finalized' && (
                    <button className="btn btn-secondary" onClick={() => finalizeDesign(design.id)}>
                      Finalize
                    </button>
                  )}
                </div>
              </div>
            ))}
            {myDesigns.length === 0 && (
              <div className="empty-state">
                <div className="empty-icon">🎨</div>
                <h3>No Designs Yet</h3>
                <p>Start by creating your first architectural design!</p>
                <button 
                  className="btn btn-primary"
                  onClick={() => setShowUploadForm(true)}
                >
                  Upload Design
                </button>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );

  const renderRequests = () => (
    <div>
      <div className="main-header">
        <div className="header-content">
          <div>
            <h1>Project Assignments</h1>
            <p>Homeowner requests assigned specifically to you</p>
          </div>
          <button 
            className="btn btn-primary"
            onClick={() => navigate('/architect/upload')}
          >
            + Upload Design
          </button>
        </div>
      </div>

      {/* Assigned to me */}
      <div className="section-card">
      <div className="section-header">
          <div>
        <h2>Your Assigned Projects</h2>
        <p>Accept or decline project assignments from homeowners</p>
      </div>
          <button 
            className="btn btn-secondary" 
            onClick={refreshDashboard}
            disabled={loading}
            style={{ marginLeft: 'auto' }}
          >
            {loading ? 'Refreshing...' : '🔄 Refresh'}
          </button>
        </div>
        <div className="section-content">
          {loading ? (
            <div className="loading">Loading assignments...</div>
          ) : layoutRequests.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">📭</div>
              <h3>No Accepted Projects</h3>
              <p>You haven't accepted any project assignments yet. Check available requests to accept new projects.</p>
            </div>
          ) : (
            <div className="item-list">
              {layoutRequests.map(request => (
                <div key={request.id} className="list-item">
                  <div className="item-image">
                    {request.site_images && Array.isArray(request.site_images) && request.site_images.length > 0 ? (
                      <img 
                        src={typeof request.site_images[0] === 'string' ? request.site_images[0] : request.site_images[0]?.url} 
                        alt="Site" 
                        style={{width:48, height:48, objectFit:'cover', borderRadius:6}} 
                      />
                    ) : request.reference_images && Array.isArray(request.reference_images) && request.reference_images.length > 0 ? (
                      <img 
                        src={typeof request.reference_images[0] === 'string' ? request.reference_images[0] : request.reference_images[0]?.url} 
                        alt="Reference" 
                        style={{width:48, height:48, objectFit:'cover', borderRadius:6}} 
                      />
                    ) : (
                      '🏠'
                    )}
                  </div>
                  <div className="item-content">
                    <h4 className="item-title">{request.homeowner_name}'s Project</h4>
                    <p className="item-subtitle">{request.plot_size} • Budget: {request.budget_range}</p>
                    <div className="item-details">
                      <p><strong>Location:</strong> {request.location || 'Not specified'}</p>
                      <p><strong>Timeline:</strong> {request.timeline || 'Not specified'}</p>
                      <p><strong>Floors:</strong> {request.num_floors || 'Not specified'}</p>
                      <p><strong>Style:</strong> {request.preferred_style || 'Not specified'}</p>
                      {request.assignment_message && (
                        <p><strong>Message:</strong> {request.assignment_message}</p>
                      )}
                      {request.requirements_parsed && (
                        <div className="requirements-summary">
                          <p><strong>Requirements:</strong></p>
                          <RequirementsDisplay 
                            requirements={request.requirements_parsed} 
                            compact={true}
                          />
                        </div>
                      )}
                    </div>
                    <p className="item-meta">
                      Assigned: {new Date(request.assigned_at).toLocaleDateString()} • 
                      Contact: {request.homeowner_email}
                    </p>
                  </div>
                  <div className="item-actions">
                    <span className={`status-badge accepted`}>
                      Accepted Project
                    </span>
                      <div className="action-buttons">
                      <button 
                        className="btn btn-primary"
                        onClick={() => {
                          setSelectedRequestForPlan(request.id);
                          setActiveTab('house-plans');
                          setShowHousePlanManager(true);
                        }}
                      >
                        Create Design
                      </button>
                      <button 
                        className="btn btn-success"
                        onClick={() => {
                          setSelectedRequestForUpload(request);
                          setShowTechnicalModal(true);
                        }}
                        title="Upload design with technical details"
                      >
                        📤 Upload Design
                      </button>
                      <button 
                        className="btn btn-danger btn-sm"
                        onClick={() => handleAssignmentResponse(request.assignment_id, 'reject')}
                        disabled={loading}
                        title="Cancel this assignment"
                      >
                        Remove
                      </button>
                    </div>
                    <button 
                      className="btn btn-secondary btn-sm"
                      onClick={() => setExpandedAssignments(prev => ({
                        ...prev, 
                        [request.id]: !prev[request.id]
                      }))}
                    >
                      {expandedAssignments[request.id] ? 'Hide Details' : 'View Images & Details'}
                    </button>
                    <button 
                      className="btn btn-success btn-sm"
                      onClick={() => downloadProjectPDF(request)}
                      title="Download project details as PDF"
                      style={{ display: 'flex', alignItems: 'center', gap: '4px' }}
                    >
                      📥 Download PDF
                    </button>
                  </div>
                  {expandedAssignments[request.id] && (
                    <div className="expanded-details" style={{
                      marginTop: '20px', 
                      padding: '24px', 
                      background: 'linear-gradient(135deg, #f8fafc 0%, #ffffff 100%)', 
                      borderRadius: '12px',
                      border: '1px solid #e2e8f0',
                      boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)',
                      position: 'relative',
                      overflow: 'visible',
                      width: '100%',
                      minHeight: 'auto',
                      zIndex: 1
                    }}>
                      
                      {/* Compact Project Overview */}
                      <div className="project-overview" style={{marginBottom: '24px'}}>
                        <div style={{
                          display: 'flex', 
                          alignItems: 'center', 
                          justifyContent: 'space-between',
                          marginBottom: '16px',
                          padding: '0 0 12px 0',
                          borderBottom: '1px solid #e2e8f0'
                        }}>
                          <div style={{display: 'flex', alignItems: 'center'}}>
                            <span style={{fontSize: '20px', marginRight: '8px'}}>📋</span>
                            <h4 style={{margin: 0, color: '#1f2937', fontSize: '18px', fontWeight: '600'}}>Project Overview</h4>
                        </div>
                          <div style={{
                            display: 'flex',
                            gap: '8px',
                            fontSize: '12px',
                            color: '#6b7280'
                          }}>
                            <span style={{
                              background: '#e0f2fe',
                              color: '#0369a1',
                              padding: '2px 8px',
                            borderRadius: '12px', 
                              fontWeight: '500'
                            }}>
                              {request.num_floors || 'N/A'} Floors
                            </span>
                            <span style={{
                              background: '#f0fdf4',
                              color: '#166534',
                              padding: '2px 8px',
                              borderRadius: '12px',
                              fontWeight: '500'
                            }}>
                              {request.preferred_style || 'Any Style'}
                            </span>
                          </div>
                        </div>
                        
                        {/* Compact Info Grid */}
                            <div style={{
                          display: 'grid', 
                          gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', 
                          gap: '16px',
                              marginBottom: '20px'
                            }}>
                          <div style={{
                            background: 'white', 
                            padding: '16px 20px', 
                            borderRadius: '10px', 
                            border: '1px solid #e5e7eb',
                            boxShadow: '0 2px 6px rgba(0, 0, 0, 0.08)',
                            transition: 'all 0.2s ease',
                            minHeight: '80px',
                            display: 'flex',
                            flexDirection: 'column',
                            justifyContent: 'space-between'
                          }}>
                            <div style={{display: 'flex', alignItems: 'center', marginBottom: '8px'}}>
                              <span style={{fontSize: '16px', marginRight: '8px', color: '#3b82f6'}}>📐</span>
                              <span style={{fontSize: '14px', fontWeight: '600', color: '#374151'}}>Plot Size</span>
                            </div>
                            <div style={{fontSize: '16px', fontWeight: '500', color: '#1f2937'}}>
                              {request.plot_size || 'Not specified'}
                                </div>
                                </div>
                          
                          <div style={{
                            background: 'white', 
                            padding: '16px 20px', 
                            borderRadius: '10px', 
                            border: '1px solid #e5e7eb',
                            boxShadow: '0 2px 6px rgba(0, 0, 0, 0.08)',
                            transition: 'all 0.2s ease',
                            minHeight: '80px',
                            display: 'flex',
                            flexDirection: 'column',
                            justifyContent: 'space-between'
                          }}>
                            <div style={{display: 'flex', alignItems: 'center', marginBottom: '8px'}}>
                              <span style={{fontSize: '16px', marginRight: '8px', color: '#10b981'}}>💰</span>
                              <span style={{fontSize: '14px', fontWeight: '600', color: '#374151'}}>Budget Range</span>
                                </div>
                            <div style={{fontSize: '16px', fontWeight: '500', color: '#1f2937'}}>
                              {request.budget_range || 'Not specified'}
                                </div>
                                </div>
                          
                          <div style={{
                            background: 'white', 
                            padding: '16px 20px', 
                            borderRadius: '10px', 
                            border: '1px solid #e5e7eb',
                            boxShadow: '0 2px 6px rgba(0, 0, 0, 0.08)',
                            transition: 'all 0.2s ease',
                            minHeight: '80px',
                            display: 'flex',
                            flexDirection: 'column',
                            justifyContent: 'space-between'
                          }}>
                            <div style={{display: 'flex', alignItems: 'center', marginBottom: '8px'}}>
                              <span style={{fontSize: '16px', marginRight: '8px', color: '#f59e0b'}}>⏱️</span>
                              <span style={{fontSize: '14px', fontWeight: '600', color: '#374151'}}>Timeline</span>
                                </div>
                            <div style={{fontSize: '16px', fontWeight: '500', color: '#1f2937'}}>
                              {request.timeline || 'Not specified'}
                            </div>
                          </div>
                          
                            <div style={{
                              background: 'white', 
                            padding: '16px 20px', 
                            borderRadius: '10px', 
                              border: '1px solid #e5e7eb',
                            boxShadow: '0 2px 6px rgba(0, 0, 0, 0.08)',
                            transition: 'all 0.2s ease',
                            minHeight: '80px',
                            display: 'flex',
                            flexDirection: 'column',
                            justifyContent: 'space-between'
                          }}>
                            <div style={{display: 'flex', alignItems: 'center', marginBottom: '8px'}}>
                              <span style={{fontSize: '16px', marginRight: '8px', color: '#8b5cf6'}}>📍</span>
                              <span style={{fontSize: '14px', fontWeight: '600', color: '#374151'}}>Location</span>
                            </div>
                            <div style={{fontSize: '16px', fontWeight: '500', color: '#1f2937'}}>
                              {request.location || 'Not specified'}
                            </div>
                          </div>
                        </div>

                        {/* Additional Info Row */}
                              <div style={{
                          display: 'flex',
                          gap: '12px',
                          flexWrap: 'wrap',
                          alignItems: 'center'
                        }}>
                          {request.orientation && (
                            <div style={{
                              background: 'linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%)',
                              padding: '8px 12px',
                              borderRadius: '20px',
                              border: '1px solid #bae6fd',
                                display: 'flex',
                                alignItems: 'center',
                              gap: '6px',
                              fontSize: '14px',
                              fontWeight: '500',
                              color: '#0369a1'
                            }}>
                              <span>🧭</span>
                              <span>Orientation: {request.orientation}</span>
                            </div>
                          )}
                          
                          {request.budget_allocation && (
                            <div style={{
                              background: 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)',
                              padding: '8px 12px',
                              borderRadius: '20px',
                              border: '1px solid #bbf7d0',
                                display: 'flex',
                                alignItems: 'center',
                              gap: '6px',
                              fontSize: '14px',
                              fontWeight: '500',
                              color: '#166534'
                            }}>
                              <span>💰</span>
                              <span>Allocation: {request.budget_allocation}</span>
                            </div>
                          )}
                        </div>
                      </div>

                      {/* Material Preferences - Compact */}
                      {request.material_preferences && request.material_preferences.length > 0 && (
                        <div className="material-preferences" style={{marginBottom: '20px'}}>
                          <div style={{
                            display: 'flex', 
                            alignItems: 'center', 
                            marginBottom: '12px'
                          }}>
                            <span style={{fontSize: '16px', marginRight: '8px', color: '#3b82f6'}}>🏗️</span>
                            <h4 style={{margin: 0, color: '#1f2937', fontSize: '16px', fontWeight: '600'}}>Material Preferences</h4>
                            <span style={{
                              marginLeft: '8px',
                              background: '#e0f2fe',
                              color: '#0369a1',
                              padding: '4px 10px',
                              borderRadius: '12px',
                              fontSize: '13px',
                              fontWeight: '500'
                            }}>
                              {request.material_preferences.length} materials
                            </span>
                          </div>
                          <div style={{
                            background: 'white', 
                            padding: '12px 16px', 
                            borderRadius: '8px', 
                            border: '1px solid #e5e7eb',
                            boxShadow: '0 1px 3px rgba(0, 0, 0, 0.05)'
                          }}>
                            <div style={{display: 'flex', flexWrap: 'wrap', gap: '8px'}}>
                              {request.material_preferences.map((material, idx) => (
                                <span key={idx} style={{
                                  background: 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)', 
                                  color: 'white', 
                                  padding: '6px 14px', 
                                  borderRadius: '16px', 
                                  fontSize: '14px',
                                  fontWeight: '500',
                                  boxShadow: '0 1px 3px rgba(59, 130, 246, 0.3)',
                                  transition: 'all 0.2s ease',
                                  cursor: 'default'
                                }}>
                                  {material}
                                </span>
                              ))}
                            </div>
                          </div>
                        </div>
                      )}

                      {/* Requirements Display */}
                      {(request.requirements || request.requirements_parsed) && (
                        <div style={{marginBottom: '20px'}}>
                          <RequirementsDisplay 
                            requirements={request.requirements || request.requirements_parsed} 
                            compact={false}
                          />
                        </div>
                      )}

                      {/* Site Considerations - Compact */}
                      {request.site_considerations && (
                        <div className="site-considerations" style={{marginBottom: '20px'}}>
                          <div style={{
                            display: 'flex', 
                            alignItems: 'center', 
                            marginBottom: '12px'
                          }}>
                            <span style={{fontSize: '16px', marginRight: '8px', color: '#059669'}}>🌍</span>
                            <h4 style={{margin: 0, color: '#1f2937', fontSize: '16px', fontWeight: '600'}}>Site Considerations</h4>
                          </div>
                          <div style={{
                            background: 'white', 
                            padding: '12px 16px', 
                            borderRadius: '8px', 
                            border: '1px solid #e5e7eb',
                            boxShadow: '0 1px 3px rgba(0, 0, 0, 0.05)'
                          }}>
                            <p style={{
                              fontSize: '15px', 
                              margin: 0, 
                              lineHeight: '1.6', 
                              color: '#4b5563',
                              whiteSpace: 'pre-wrap'
                            }}>
                              {request.site_considerations}
                            </p>
                          </div>
                        </div>
                      )}

                      {/* Images Gallery - Compact */}
                      <div className="images-section" style={{marginBottom: '24px'}}>
                        <div style={{
                          display: 'flex', 
                          alignItems: 'center', 
                          justifyContent: 'space-between',
                          marginBottom: '16px',
                          padding: '0 0 12px 0',
                          borderBottom: '1px solid #e2e8f0'
                        }}>
                          <div style={{display: 'flex', alignItems: 'center'}}>
                            <span style={{fontSize: '18px', marginRight: '8px', color: '#3b82f6'}}>📸</span>
                            <h4 style={{margin: 0, color: '#1f2937', fontSize: '16px', fontWeight: '600'}}>Project Images</h4>
                          </div>
                          <div style={{
                            display: 'flex',
                            gap: '8px',
                            fontSize: '11px',
                            color: '#6b7280'
                          }}>
                            {request.site_images && request.site_images.length > 0 && (
                              <span style={{
                                background: '#e0f2fe',
                                color: '#0369a1',
                                padding: '4px 10px',
                                borderRadius: '12px',
                                fontSize: '13px',
                                fontWeight: '500'
                              }}>
                                {request.site_images.length} site
                              </span>
                            )}
                            {request.room_images && Object.keys(request.room_images).length > 0 && (
                              <span style={{
                                background: '#f0fdf4',
                                color: '#166534',
                                padding: '4px 10px',
                                borderRadius: '12px',
                                fontSize: '13px',
                                fontWeight: '500'
                              }}>
                                {Object.values(request.room_images).flat().length} room
                              </span>
                            )}
                          </div>
                        </div>
                        
                          {/* Site Images - Compact */}
                        {request.site_images && request.site_images.length > 0 && (
                          <div style={{marginBottom: '20px'}}>
                            <div style={{
                              display: 'flex',
                              alignItems: 'center',
                              marginBottom: '12px'
                            }}>
                              <span style={{fontSize: '14px', marginRight: '6px', color: '#059669'}}>🏞️</span>
                              <h5 style={{margin: 0, color: '#374151', fontWeight: '600', fontSize: '16px'}}>Site Images</h5>
                              <span style={{
                                marginLeft: '8px',
                                background: '#e0f2fe',
                                color: '#0369a1',
                                padding: '4px 8px',
                                borderRadius: '10px',
                                fontSize: '12px',
                                fontWeight: '500'
                              }}>
                                {request.site_images.length}
                              </span>
                            </div>
                            <div style={{
                              display: 'grid', 
                              gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))', 
                              gap: '12px'
                            }}>
                              {request.site_images.map((img, idx) => (
                                <div key={`site-${idx}`} 
                                     className="image-card" 
                                     style={{
                                       position: 'relative', 
                                       borderRadius: '8px', 
                                       overflow: 'hidden',
                                       boxShadow: '0 1px 4px rgba(0, 0, 0, 0.1)',
                                       transition: 'all 0.2s ease',
                                       cursor: 'pointer',
                                       border: '1px solid #e5e7eb'
                                     }}
                                     onMouseEnter={(e) => {
                                       const card = e.currentTarget;
                                       card.style.transform = 'scale(1.02)';
                                       card.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.15)';
                                       const overlay = card.querySelector('.hover-overlay');
                                       if (overlay) overlay.style.display = 'flex';
                                     }}
                                     onMouseLeave={(e) => {
                                       const card = e.currentTarget;
                                       card.style.transform = 'scale(1)';
                                       card.style.boxShadow = '0 1px 4px rgba(0, 0, 0, 0.1)';
                                       const overlay = card.querySelector('.hover-overlay');
                                       if (overlay) overlay.style.display = 'none';
                                     }}
                                >
                                  <img 
                                    src={typeof img === 'string' ? img : img.url} 
                                alt={`Site ${idx + 1}`} 
                                    style={{width: '100%', height: '120px', objectFit: 'cover'}}
                                onError={(e) => {e.target.style.display = 'none'}}
                              />
                                  <div style={{
                                    position: 'absolute', 
                                    top: '6px', 
                                    left: '6px', 
                                    background: 'rgba(0,0,0,0.7)', 
                                    color: 'white', 
                                    padding: '4px 10px', 
                                    borderRadius: '6px', 
                                    fontSize: '12px', 
                                    fontWeight: '600',
                                    backdropFilter: 'blur(4px)'
                                  }}>
                                    Site {idx + 1}
                                  </div>
                                  {/* Action buttons overlay - shown on hover */}
                                  <div className="hover-overlay" style={{
                                    position: 'absolute',
                                    top: 0,
                                    left: 0,
                                    right: 0,
                                    bottom: 0,
                                    background: 'rgba(0,0,0,0.6)',
                                    display: 'none',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    gap: '8px',
                                    transition: 'opacity 0.2s ease'
                                  }}>
                                    <button
                                      onClick={(e) => {
                                        e.stopPropagation();
                                        window.open(typeof img === 'string' ? img : img.url, '_blank');
                                      }}
                                      style={{
                                        padding: '8px 14px',
                                        background: 'rgba(255,255,255,0.95)',
                                        border: 'none',
                                        borderRadius: '4px',
                                        fontSize: '13px',
                                        fontWeight: '600',
                                        color: '#374151',
                                        cursor: 'pointer',
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '4px',
                                        backdropFilter: 'blur(4px)',
                                        boxShadow: '0 1px 4px rgba(0,0,0,0.2)'
                                      }}
                                    >
                                      👁️ View
                                    </button>
                                    <a
                                      href={typeof img === 'string' ? img : img.url}
                                      download={`site-image-${idx + 1}`}
                                      onClick={(e) => e.stopPropagation()}
                                      style={{
                                          padding: '8px 14px',
                                          background: 'rgba(59, 130, 246, 0.95)',
                                        border: 'none',
                                          borderRadius: '4px',
                                        fontSize: '13px',
                                        fontWeight: '600',
                                        color: 'white',
                                        textDecoration: 'none',
                                        cursor: 'pointer',
                                        display: 'flex',
                                        alignItems: 'center',
                                          gap: '4px',
                                        backdropFilter: 'blur(4px)',
                                          boxShadow: '0 1px 4px rgba(0,0,0,0.2)'
                                      }}
                                    >
                                      💾 Download
                                    </a>
                                  </div>
                            </div>
                          ))}
                            </div>
                          </div>
                        )}

                          {/* Reference Images */}
                        {request.reference_images && request.reference_images.length > 0 && (
                          <div style={{marginBottom: '20px'}}>
                            <h5 style={{margin: '0 0 12px 0', color: '#374151', fontWeight: '600'}}>Reference Images</h5>
                            <div style={{display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))', gap: '12px'}}>
                              {request.reference_images.map((img, idx) => (
                                <div key={`ref-${idx}`} className="image-card" style={{position: 'relative', borderRadius: '8px', overflow: 'hidden'}}>
                                  <img 
                                    src={typeof img === 'string' ? img : img.url} 
                                alt={`Reference ${idx + 1}`} 
                                    style={{width: '100%', height: '150px', objectFit: 'cover'}}
                                onError={(e) => {e.target.style.display = 'none'}}
                              />
                                  <div style={{position: 'absolute', top: '8px', left: '8px', background: 'rgba(0,0,0,0.7)', color: 'white', padding: '4px 8px', borderRadius: '4px', fontSize: '12px', fontWeight: '500'}}>
                                    Reference {idx + 1}
                              </div>
                            </div>
                          ))}
                            </div>
                          </div>
                        )}

                          {/* Room Images */}
                        {request.room_images && Object.keys(request.room_images).length > 0 && (
                          <div>
                            <h5 style={{margin: '0 0 12px 0', color: '#374151', fontWeight: '600'}}>Room-Specific Images</h5>
                            {Object.entries(request.room_images).map(([floorKey, floorRooms]) => (
                              <div key={floorKey} style={{marginBottom: '16px'}}>
                                <h6 style={{margin: '0 0 8px 0', color: '#6b7280', fontWeight: '500', textTransform: 'capitalize'}}>
                                  {floorKey.replace('floor', 'Floor ')}
                                </h6>
                                {Object.entries(floorRooms || {}).map(([roomType, images]) => (
                                  images && images.length > 0 && (
                                    <div key={roomType} style={{marginBottom: '12px'}}>
                                      <div style={{fontSize: '13px', fontWeight: '500', color: '#6b7280', marginBottom: '6px', textTransform: 'capitalize'}}>
                                        {roomType.replace('_', ' ')}
                                      </div>
                                      <div style={{display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(150px, 1fr))', gap: '8px'}}>
                                        {images.map((img, idx) => (
                                          <div key={`${roomType}-${idx}`} 
                                               className="image-card" 
                                               style={{
                                                 position: 'relative', 
                                                 borderRadius: '6px', 
                                                 overflow: 'hidden',
                                                 cursor: 'pointer',
                                                 transition: 'transform 0.2s ease'
                                               }}
                                               onMouseEnter={(e) => {
                                                 const card = e.currentTarget;
                                                 card.style.transform = 'scale(1.05)';
                                                 const overlay = card.querySelector('.room-hover-overlay');
                                                 if (overlay) overlay.style.display = 'flex';
                                               }}
                                               onMouseLeave={(e) => {
                                                 const card = e.currentTarget;
                                                 card.style.transform = 'scale(1)';
                                                 const overlay = card.querySelector('.room-hover-overlay');
                                                 if (overlay) overlay.style.display = 'none';
                                               }}
                                          >
                                            <img 
                                              src={typeof img === 'string' ? img : img.url} 
                                              alt={`${roomType} ${idx + 1}`} 
                                              style={{width: '100%', height: '100px', objectFit: 'cover'}}
                                              onError={(e) => {e.target.style.display = 'none'}}
                                            />
                                            <div style={{position: 'absolute', top: '4px', left: '4px', background: 'rgba(0,0,0,0.7)', color: 'white', padding: '2px 6px', borderRadius: '3px', fontSize: '10px'}}>
                                              {roomType}
                                            </div>
                                            
                                            {/* Room image action buttons overlay */}
                                            <div className="room-hover-overlay" style={{
                                              position: 'absolute',
                                              top: 0,
                                              left: 0,
                                              right: 0,
                                              bottom: 0,
                                              background: 'rgba(0,0,0,0.5)',
                                              display: 'none',
                                              alignItems: 'center',
                                              justifyContent: 'center',
                                              gap: '8px',
                                              transition: 'opacity 0.3s ease'
                                            }}>
                                              <button
                                                onClick={(e) => {
                                                  e.stopPropagation();
                                                  window.open(typeof img === 'string' ? img : img.url, '_blank');
                                                }}
                                                style={{
                                                  padding: '6px 12px',
                                                  background: 'rgba(255,255,255,0.9)',
                                                  border: 'none',
                                                  borderRadius: '4px',
                                                  fontSize: '11px',
                                                  fontWeight: '600',
                                                  color: '#374151',
                                                  cursor: 'pointer',
                                                  display: 'flex',
                                                  alignItems: 'center',
                                                  gap: '4px',
                                                  backdropFilter: 'blur(4px)'
                                                }}
                                              >
                                                👁️ View
                                              </button>
                                              <a
                                                href={typeof img === 'string' ? img : img.url}
                                                download={`${roomType}-image-${idx + 1}`}
                                                onClick={(e) => e.stopPropagation()}
                                                style={{
                                                  padding: '6px 12px',
                                                  background: 'rgba(59, 130, 246, 0.9)',
                                                  border: 'none',
                                                  borderRadius: '4px',
                                                  fontSize: '11px',
                                                  fontWeight: '600',
                                                  color: 'white',
                                                  textDecoration: 'none',
                                                  cursor: 'pointer',
                                                  display: 'flex',
                                                  alignItems: 'center',
                                                  gap: '4px',
                                                  backdropFilter: 'blur(4px)'
                                                }}
                                              >
                                                💾 Download
                                              </a>
                                            </div>
                                          </div>
                                        ))}
                        </div>
                                    </div>
                                  )
                                ))}
                              </div>
                            ))}
                          </div>
                        )}
                      </div>

                      {/* Detailed Requirements - Compact */}
                      {request.requirements_parsed && (
                        <div className="detailed-requirements">
                          <div style={{
                            display: 'flex', 
                            alignItems: 'center', 
                            justifyContent: 'space-between',
                            marginBottom: '16px',
                            padding: '0 0 12px 0',
                            borderBottom: '1px solid #e2e8f0'
                          }}>
                            <div style={{display: 'flex', alignItems: 'center'}}>
                              <span style={{fontSize: '18px', marginRight: '8px', color: '#f59e0b'}}>📝</span>
                              <h4 style={{margin: 0, color: '#1f2937', fontSize: '16px', fontWeight: '600'}}>Detailed Requirements</h4>
                            </div>
                            <span style={{
                              background: '#fef3c7',
                              color: '#d97706',
                              padding: '4px 10px',
                              borderRadius: '12px',
                              fontSize: '13px',
                              fontWeight: '500'
                            }}>
                              {Object.keys(request.requirements_parsed).filter(key => key !== 'notes' && request.requirements_parsed[key]).length} items
                            </span>
                          </div>
                          <div style={{
                            display: 'grid', 
                            gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', 
                            gap: '16px'
                          }}>
                            {Object.entries(request.requirements_parsed).map(([key, value]) => {
                              if (!value || key === 'notes') return null;
                              
                              // Format complex values
                              const formatValue = (val) => {
                                if (!val) return '-';
                                
                                // Try to parse string values that might be JSON
                                let parsedVal = val;
                                if (typeof val === 'string') {
                                  try {
                                    parsedVal = JSON.parse(val);
                                  } catch (e) {
                                    // If not JSON, keep as string
                                    parsedVal = val;
                                  }
                                }
                                
                                // Handle arrays
                                if (Array.isArray(parsedVal)) {
                                  // Special handling for rooms array - show counts
                                  if (key === 'rooms') {
                                    // Count occurrences of each room type
                                    const roomCounts = {};
                                    parsedVal.forEach(room => {
                                      roomCounts[room] = (roomCounts[room] || 0) + 1;
                                    });
                                    
                                    return (
                                      <div style={{ marginTop: '4px' }}>
                                        {Object.entries(roomCounts).map(([roomType, count]) => (
                                          <span key={roomType} style={{ 
                                            display: 'inline-block',
                                            margin: '2px 4px 2px 0',
                                            padding: '4px 8px',
                                            backgroundColor: '#dbeafe',
                                            color: '#1e40af',
                                            borderRadius: '12px',
                                            fontSize: '12px',
                                            fontWeight: '500'
                                          }}>
                                            {roomType.replace(/_/g, ' ')}: {count}
                                          </span>
                                        ))}
                                      </div>
                                    );
                                  }
                                  return parsedVal.join(', ');
                                }
                                
                                // Handle string values that might be comma-separated rooms
                                if (typeof parsedVal === 'string' && key === 'rooms') {
                                  // Split comma-separated rooms and count them
                                  const roomsList = parsedVal.split(',').map(room => room.trim()).filter(room => room);
                                  const roomCounts = {};
                                  roomsList.forEach(room => {
                                    roomCounts[room] = (roomCounts[room] || 0) + 1;
                                  });
                                  
                                  return (
                                    <div style={{ marginTop: '4px' }}>
                                      {Object.entries(roomCounts).map(([roomType, count]) => (
                                        <span key={roomType} style={{ 
                                          display: 'inline-block',
                                          margin: '2px 4px 2px 0',
                                          padding: '4px 8px',
                                          backgroundColor: '#dbeafe',
                                          color: '#1e40af',
                                          borderRadius: '12px',
                                          fontSize: '12px',
                                          fontWeight: '500'
                                        }}>
                                          {roomType.replace(/_/g, ' ')}: {count}
                                        </span>
                                      ))}
                                    </div>
                                  );
                                }
                                
                                // Handle objects (like floor_rooms, site_images, etc.)
                                if (typeof parsedVal === 'object' && parsedVal !== null) {
                                  // Special handling for floor_rooms - return structured JSX
                                  if (key === 'floor_rooms') {
                                    return (
                                      <div style={{ marginTop: '4px' }}>
                                        {Object.entries(parsedVal).map(([floor, rooms]) => (
                                          <div key={floor} style={{ 
                                            marginBottom: '4px', 
                                            padding: '6px', 
                                            background: '#f8fafc', 
                                            borderRadius: '4px',
                                            border: '1px solid #e5e7eb'
                                          }}>
                                            <div style={{ 
                                              fontSize: '11px', 
                                              fontWeight: '600', 
                                              color: '#374151', 
                                              marginBottom: '3px',
                                              textTransform: 'capitalize'
                                            }}>
                                              {floor.replace(/_/g, ' ')}
                                            </div>
                                            <div style={{ fontSize: '11px', color: '#6b7280', lineHeight: '1.2' }}>
                                              {Object.entries(rooms).map(([room, count]) => (
                                                <span key={room} style={{ 
                                                  display: 'inline-block',
                                                  margin: '1px 3px 1px 0',
                                                  padding: '1px 4px',
                                                  background: '#dbeafe',
                                                  color: '#1e40af',
                                                  borderRadius: '8px',
                                                  fontSize: '10px',
                                                  fontWeight: '500'
                                                }}>
                                                  {room.replace(/_/g, ' ')}: {count}
                                                </span>
                                              ))}
                                            </div>
                                          </div>
                                        ))}
                                      </div>
                                    );
                                  }
                                  
                                  // Special handling for site_images, room_images - return JSX with buttons
                                  if (key.includes('images')) {
                                    const getImageList = () => {
                                      if (Array.isArray(parsedVal)) return parsedVal;
                                      
                                      // For room_images object structure
                                      const allImages = [];
                                      Object.entries(parsedVal).forEach(([floor, floorData]) => {
                                        if (Array.isArray(floorData)) {
                                          allImages.push(...floorData);
                                        } else if (typeof floorData === 'object') {
                                          Object.entries(floorData).forEach(([room, roomImages]) => {
                                            if (Array.isArray(roomImages)) {
                                              allImages.push(...roomImages.map(img => ({ ...img, floor, room })));
                                            }
                                          });
                                        }
                                      });
                                      return allImages;
                                    };
                                    
                                    const imageList = getImageList();
                                    const totalImages = imageList.length;
                                    
                                    if (totalImages === 0) return 'No images';
                                    
                                    return (
                                      <div style={{ marginTop: '4px' }}>
                                        <div style={{ 
                                          display: 'flex', 
                                          alignItems: 'center', 
                                          justifyContent: 'space-between',
                                          marginBottom: '4px'
                                        }}>
                                          <span style={{ fontSize: '11px', color: '#6b7280' }}>
                                            {totalImages} image{totalImages !== 1 ? 's' : ''}
                                          </span>
                                          <button
                                            onClick={() => {
                                              // Open image gallery modal
                                              const gallery = imageList.map((img, idx) => ({
                                                id: idx,
                                                url: img.path || `/buildhub/backend/uploads/${key.includes('site') ? 'site_images' : 'room_images'}/${img.filename || img}`,
                                                title: img.floor && img.room ? `${img.floor} - ${img.room}` : `Image ${idx + 1}`,
                                                type: key.replace('_', ' ')
                                              }));
                                              // You can implement a modal here or use existing image viewer
                                              console.log('Open gallery:', gallery);
                                            }}
                                            style={{
                                              padding: '2px 6px',
                                              fontSize: '10px',
                                              background: '#3b82f6',
                                              color: 'white',
                                              border: 'none',
                                              borderRadius: '3px',
                                              cursor: 'pointer'
                                            }}
                                          >
                                            📁 View All
                                          </button>
                                        </div>
                                        <div style={{ 
                                          display: 'grid', 
                                          gridTemplateColumns: 'repeat(auto-fill, minmax(40px, 1fr))', 
                                          gap: '2px',
                                          maxHeight: '80px',
                                          overflowY: 'auto'
                                        }}>
                                          {imageList.slice(0, 6).map((img, idx) => {
                                            const imgUrl = img.path || `/buildhub/backend/uploads/${key.includes('site') ? 'site_images' : 'room_images'}/${img.filename || img}`;
                                            return (
                                              <div key={idx} style={{ 
                                                position: 'relative',
                                                cursor: 'pointer',
                                                borderRadius: '3px',
                                                overflow: 'hidden',
                                                aspectRatio: '1',
                                                height: '35px'
                                              }}>
                                                <img 
                                                  src={imgUrl}
                                                  alt={`${key} ${idx + 1}`}
                                                  style={{ 
                                                    width: '100%', 
                                                    height: '100%', 
                                                    objectFit: 'cover',
                                                    transition: 'transform 0.2s'
                                                  }}
                                                  onMouseOver={(e) => e.target.style.transform = 'scale(1.05)'}
                                                  onMouseOut={(e) => e.target.style.transform = 'scale(1)'}
                                                  onClick={() => window.open(imgUrl, '_blank')}
                                                />
                                                <div style={{
                                                  position: 'absolute',
                                                  bottom: '1px',
                                                  right: '1px',
                                                  background: 'rgba(0,0,0,0.7)',
                                                  color: 'white',
                                                  borderRadius: '2px',
                                                  padding: '0px 2px',
                                                  fontSize: '7px',
                                                  lineHeight: '1.2'
                                                }}>
                                                  {idx + 1}
                                                </div>
                                              </div>
                                            );
                                          })}
                                          {totalImages > 6 && (
                                            <div style={{
                                              display: 'flex',
                                              alignItems: 'center',
                                              justifyContent: 'center',
                                              background: '#f3f4f6',
                                              color: '#6b7280',
                                              fontSize: '8px',
                                              fontWeight: '600',
                                              borderRadius: '3px',
                                              height: '35px'
                                            }}>
                                              +{totalImages - 6}
                                            </div>
                                          )}
                                        </div>
                                      </div>
                                    );
                                  }
                                  
                                  // Generic object handling - show key-value pairs
                                  return Object.entries(parsedVal)
                                    .map(([k, v]) => `${k.replace(/_/g, ' ')}: ${v}`)
                                    .join(', ');
                                }
                                
                                return String(parsedVal);
                              };
                              
                              const formattedValue = formatValue(value);
                              
                              return (
                                <div key={key} style={{
                                  background: 'white', 
                                  padding: '16px 20px', 
                                  borderRadius: '12px', 
                                  border: '1px solid #e5e7eb',
                                  boxShadow: '0 2px 8px rgba(0,0,0,0.08)',
                                  transition: 'all 0.2s ease',
                                  position: 'relative',
                                  overflow: 'visible'
                                }}>
                                  <div style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    marginBottom: '12px'
                                  }}>
                                    <div style={{
                                      width: '28px',
                                      height: '28px',
                                      borderRadius: '8px',
                                      background: 'linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%)',
                                      display: 'flex',
                                      alignItems: 'center',
                                      justifyContent: 'center',
                                      marginRight: '12px',
                                      fontSize: '14px'
                                  }}>
                                    {key.includes('image') && '📷'}
                                    {key === 'floor_rooms' && '🏠'}
                                    {key.includes('material') && '🧱'}
                                    {key.includes('budget') && '💰'}
                                      {key.includes('family') && '👨‍👩‍👧‍👦'}
                                      {key.includes('plot') && '📐'}
                                      {key.includes('topography') && '🏔️'}
                                      {key.includes('aesthetic') && '🎨'}
                                      {key.includes('orientation') && '🧭'}
                                      {!key.includes('image') && !key.includes('floor') && !key.includes('material') && !key.includes('budget') && !key.includes('family') && !key.includes('plot') && !key.includes('topography') && !key.includes('aesthetic') && !key.includes('orientation') && '📋'}
                                    </div>
                                    <div style={{
                                      fontWeight: '600', 
                                      textTransform: 'capitalize', 
                                      color: '#1f2937',
                                      fontSize: '16px',
                                      flex: 1
                                    }}>
                                    {key.replace(/_/g, ' ')}
                                  </div>
                                  </div>
                                  <div style={{
                                    fontSize: '15px', 
                                    color: '#374151', 
                                    lineHeight: '1.6',
                                    wordWrap: 'break-word',
                                    overflowWrap: 'break-word'
                                  }}>
                                    {typeof formattedValue === 'string' ? formattedValue : formattedValue}
                                  </div>
                                </div>
                              );
                            })}
                          </div>
                          {request.requirements_parsed.notes && (
                            <div style={{
                              marginTop: '16px', 
                              background: 'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)', 
                              padding: '12px 16px', 
                              borderRadius: '8px', 
                              border: '1px solid #f59e0b',
                              boxShadow: '0 1px 3px rgba(245, 158, 11, 0.1)'
                            }}>
                              <div style={{
                                display: 'flex',
                                alignItems: 'center',
                                marginBottom: '6px'
                              }}>
                                <span style={{fontSize: '14px', marginRight: '6px', color: '#d97706'}}>📝</span>
                                <div style={{fontWeight: '600', color: '#92400e', fontSize: '15px'}}>Additional Notes</div>
                              </div>
                              <div style={{
                                fontSize: '14px', 
                                color: '#92400e', 
                                lineHeight: '1.5',
                                whiteSpace: 'pre-wrap'
                              }}>
                                {request.requirements_parsed.notes}
                              </div>
                            </div>
                          )}
                        </div>
                      )}
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Pending Assignments */}
      <div className="section-card">
        <div className="section-header">
          <div>
          <h2>Pending Assignments</h2>
          <p>Homeowner requests waiting for your response</p>
          </div>
          <button 
            className="btn btn-secondary" 
            onClick={refreshDashboard}
            disabled={loading}
            style={{ marginLeft: 'auto' }}
          >
            {loading ? 'Refreshing...' : '🔄 Refresh'}
          </button>
        </div>
        <div className="section-content">
          <AssignedRequests 
            onCreateFromAssigned={(requestId) => {
              setUploadData({...uploadData, request_id: requestId});
                    setShowUploadForm(true);
                  }}
            expandedAssignments={expandedAssignments}
            setExpandedAssignments={setExpandedAssignments}
            toast={toast}
                />
            </div>
        </div>

    </div>
  );

  const renderDesigns = () => (
    <div>
      <div className="main-header">
        <div className="header-content">
          <div>
            <h1>My Designs</h1>
            <p>Track your submitted architectural designs and their progress</p>
          </div>
          <button 
            className="btn btn-primary"
            onClick={() => setShowUploadForm(true)}
          >
            + New Design
          </button>
        </div>
      </div>

      <div className="section-card">
        <div className="section-header">
          <h2>Design Portfolio</h2>
          <p>All your submitted architectural designs</p>
        </div>
        <div className="section-content">
          {myDesigns.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">🎨</div>
              <h3>No Designs Yet</h3>
              <p>Upload your first architectural design to get started!</p>
              <button 
                className="btn btn-primary"
                onClick={() => setShowUploadForm(true)}
              >
                Upload Design
              </button>
            </div>
          ) : (
            <div className="item-grid">
              {myDesigns.map(design => (
                <div key={design.id} className="layout-card">
                  <div className="layout-card-content">
                    <div className="layout-header">
                      <h4 className="layout-title">{design.design_title || 'Untitled Design'}</h4>
                      <span className={`badge ${badgeClass(design.status || 'proposed')}`}>{formatStatus(design.status || 'proposed')}</span>
                    </div>
                    {design.description && (<p className="layout-description">{design.description}</p>)}

                    <div className="homeowner-details">
                      <h4>Homeowner Details:</h4>
                      <p><strong>Name:</strong> {design.client_name || 'Not specified'}</p>
                      <p><strong>Email:</strong> {design.client_email || 'Not specified'}</p>
                    </div>

                    <div className="request-details">
                      <h4>Request Details:</h4>
                      <p><strong>Plot Size:</strong> {design.plot_size || '-'}</p>
                      <p><strong>Budget:</strong> {design.budget_range || '-'}</p>
                      {design.requirements && (() => {
                        const req = String(design.requirements || '').trim();
                        const looksJson = req.startsWith('{') && req.endsWith('}');
                        if (looksJson) {
                          return (
                            <div className="requirements-section">
                              <strong>Requirements:</strong>
                              <div style={{ marginTop: 6 }}>
                                <NeatJsonCard raw={req} title="Requirements" />
                              </div>
                            </div>
                          );
                        }
                        return (
                          <div className="requirements-section">
                            <strong>Requirements:</strong>
                            <div style={{whiteSpace:'pre-wrap'}}>{design.requirements}</div>
                          </div>
                        );
                      })()}
                    </div>

                    {design.technical_details && (
                      <div className="technical-details-section">
                        <TechnicalDetailsDisplay technicalDetails={design.technical_details} />
                      </div>
                    )}

                    <div className="file-links">
                      {Array.isArray(design.files) && design.files.length > 0 ? (
                        design.files.slice(0,3).map((f, idx) => (
                          (isImageUrl(f.path) || isPdfUrl(f.path)) ? (
                            <a key={idx} className="btn" href={f.path} target="_blank" rel="noreferrer">View File {idx+1}</a>
                          ) : (
                            <a key={idx} className="btn btn-link" href={f.path} target="_blank" rel="noreferrer">Download File {idx+1}</a>
                          )
                        ))
                      ) : (
                        <span className="muted">No files attached</span>
                      )}
                    </div>

                    <div className="review-section">
                      <h4>Review</h4>
                      {Array.isArray(archReviews) && archReviews.filter(rv => rv.design_id === design.id).length > 0 ? (
                        archReviews.filter(rv => rv.design_id === design.id).slice(0,1).map(rv => (
                          <div key={rv.id} className="review-item" style={{borderTop:'1px solid #eee', padding:'10px 0'}}>
                            <div style={{display:'flex', justifyContent:'space-between', alignItems:'center'}}>
                              <div style={{fontWeight:600}}>{rv.author || 'Homeowner'}</div>
                              <div style={{color:'#f5a623'}}>{'★'.repeat(rv.rating)}{'☆'.repeat(5 - rv.rating)}</div>
                            </div>
                            <div className="muted" style={{fontSize:12, color:'#667085', marginTop:4}}>{new Date(rv.created_at).toLocaleString()}</div>
                            {rv.comment && <div style={{marginTop:6, whiteSpace:'pre-wrap'}}>{rv.comment}</div>}
                          </div>
                        ))
                      ) : (
                        <div className="muted">No review yet</div>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const renderProfile = () => (
    <div style={{ padding: '20px' }}>
      {profileLoading ? (
        <div className="loading" style={{ textAlign: 'center', padding: '40px' }}>
          Loading profile…
        </div>
      ) : (
        <StylishProfile
          user={user}
          profile={profile}
          setProfile={setProfile}
          onSave={saveMyProfile}
          onReset={fetchMyProfile}
          loading={profileLoading}
          saving={profileSaving}
          reviews={archReviews}
          reviewCount={profile.review_count || 0}
          avgRating={profile.avg_rating || 0}
          onUserUpdate={(updatedUser) => setUser(updatedUser)}
        />
      )}
    </div>
  );

  const renderLibrary = () => (
    <div style={{
      height: '100vh', 
      overflowY: 'auto', 
      paddingRight: '8px',
      scrollbarWidth: 'thin',
      scrollbarColor: '#cbd5e0 #f7fafc'
    }}>
      <div className="main-header">
        <div className="header-content">
          <div>
            <h1>My Layout Library</h1>
            <p>Create layouts with preview images for homeowners to browse</p>
          </div>
          <button className="btn btn-primary" onClick={() => setShowLibraryForm(true)}>+ Add Layout</button>
        </div>
      </div>

      <div className="section-card">
        <div className="section-header">
          <h2>Library Items</h2>
          <p>Your published layouts</p>
        </div>
        <div className="section-content">
          {libraryLayouts.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">📚</div>
              <h3>No Layouts Yet</h3>
              <p>Add your first layout to the library so homeowners can request customizations</p>
              <button className="btn btn-primary" onClick={() => setShowLibraryForm(true)}>Add Layout</button>
            </div>
          ) : (
            <div className="item-grid">
              {libraryLayouts.map(item => (
                <div key={item.id} className="layout-card">
                  <button className="layout-image-container" onClick={()=>openPreview(item)} style={{cursor:'zoom-in'}}>
                    <img src={item.image_url || '/images/default-layout.jpg'} alt={item.title} className="layout-card-image"/>
                  </button>
                  <div className="layout-card-content">
                    <h4 className="layout-title">{item.title}</h4>
                    <p className="layout-type">{item.layout_type}</p>
                    <div className="layout-specs">
                      <span className="spec">🛏️ {item.bedrooms} BR</span>
                      <span className="spec">🚿 {item.bathrooms} BA</span>
                      <span className="spec">📐 {item.area} sq ft</span>
                    </div>
                     {item.description && (<p className="layout-description">{item.description}</p>)}
                     {item.price_range && (<div className="layout-price"><span className="price-range">₹{item.price_range}</span></div>)}
                     
                     {/* Technical Details Display */}
                     {item.technical_details && (
                       <div className="technical-details-preview" style={{marginTop: '12px', padding: '12px', background: '#f8f9fa', borderRadius: '6px', border: '1px solid #e9ecef'}}>
                         <h5 style={{margin: '0 0 8px 0', fontSize: '0.9rem', color: '#495057'}}>Technical Specifications</h5>
                         <TechnicalDetailsDisplay 
                           technicalDetails={item.technical_details} 
                           compact={true}
                         />
                       </div>
                     )}
                    <div style={{display:'flex', gap:8, flexWrap:'wrap', margin:'6px 0'}}>
                      {item.image_url && (
                        <button type="button" className="btn btn-secondary" onClick={()=>openPreview(item)}>View Preview</button>
                      )}
                      {item.design_file_url && (
                        isImageUrl(item.design_file_url) || isPdfUrl(item.design_file_url) ? (
                          <button type="button" className="btn" onClick={()=>openPreview(item)}>View Layout</button>
                        ) : (
                          <a className="btn btn-link" href={item.design_file_url} target="_blank" rel="noreferrer">Download Layout</a>
                        )
                      )}
                    </div>
                    <div style={{display:'flex', gap:8, flexWrap:'wrap', margin:'6px 0'}}>
                      <button type="button" className="btn btn-secondary" onClick={()=>openEditLayout(item)}>Edit</button>
                      <button
                        type="button"
                        className={`btn ${item.status === 'active' ? 'btn-danger' : 'btn-success'}`}
                        onClick={()=>toggleLayoutStatus(item)}
                      >
                        {item.status === 'active' ? 'Deactivate' : 'Activate'}
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {showLibraryForm && (
        <div className="form-modal">
          <div className="form-content" style={{
            maxWidth:'920px',
            width: '100%',
            maxHeight: '90vh',
            height: 'auto',
            display:'flex',
            flexDirection:'column',
            position: 'relative',
            overflow: 'hidden',
            borderRadius: '12px',
            boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1)'
          }}>
            <div className="form-header" style={{
              flexShrink: 0, 
              padding: '24px 28px', 
              borderBottom: '1px solid #e5e7eb',
              background: 'linear-gradient(to right, #f8fafc, #ffffff)',
              position: 'relative'
            }}>
              <div style={{display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '16px'}}>
                <div style={{flex: 1}}>
                  <div style={{display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '8px'}}>
                    <div style={{
                      width: '44px',
                      height: '44px',
                      borderRadius: '12px',
                      background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      boxShadow: '0 4px 12px rgba(16, 185, 129, 0.3)'
                    }}>
                      <span style={{fontSize: '20px'}}>➕</span>
                    </div>
                    <div>
                      <h3 style={{
                        margin: '0 0 2px 0', 
                        fontSize: '1.5rem', 
                        fontWeight: '700',
                        color: '#111827',
                        letterSpacing: '-0.01em'
                      }}>Add Layout</h3>
                      <p style={{
                        margin: 0, 
                        fontSize: '0.875rem', 
                        color: '#6b7280',
                        fontWeight: '500'
                      }}>Publish a new layout to the library</p>
                    </div>
                  </div>
                  <div className="step-indicator" style={{marginTop: '12px', display: 'flex', gap: '8px'}}>
                    <span className={`step active`} style={{
                      background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                      color: 'white',
                      padding: '6px 16px',
                      borderRadius: '20px',
                      fontSize: '0.875rem',
                      fontWeight: '600',
                      boxShadow: '0 2px 8px rgba(16, 185, 129, 0.3)'
                    }}>Basic Info & Files</span>
                  </div>
                </div>
                <button 
                  type="button" 
                  className="modal-close" 
                  onClick={() => setShowLibraryForm(false)} 
                  style={{
                    background: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                    color: 'white',
                    border: 'none',
                    borderRadius: '50%',
                    width: '40px',
                    height: '40px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    fontSize: '22px',
                    lineHeight: 1,
                    flexShrink: 0,
                    boxShadow: '0 4px 12px rgba(239, 68, 68, 0.3)',
                    transition: 'all 0.2s ease',
                    fontWeight: '600'
                  }}
                  onMouseEnter={(e) => {
                    e.target.style.transform = 'scale(1.1)';
                    e.target.style.boxShadow = '0 6px 16px rgba(239, 68, 68, 0.4)';
                  }}
                  onMouseLeave={(e) => {
                    e.target.style.transform = 'scale(1)';
                    e.target.style.boxShadow = '0 4px 12px rgba(239, 68, 68, 0.3)';
                  }}
                >×</button>
              </div>
            </div>
            <form onSubmit={submitNewLibraryItem} style={{display:'flex', flexDirection:'column', flex:1, minHeight: 0, overflow: 'hidden'}}>
              <div
                className="scrollable-form-content"
                style={{
                  flex:1,
                  overflowY:'auto',
                  overflowX:'hidden',
                  padding: '24px',
                  paddingRight:'32px',
                  maxHeight: 'calc(90vh - 200px)',
                  minHeight: '300px',
                  scrollbarWidth:'thin',
                  scrollbarColor:'#cbd5e1 #f1f5f9'
                }}
              >
              <div className="form-row">
                <div className="form-group">
                  <label>Title</label>
                  <input type="text" value={libraryForm.title} onChange={(e)=>setLibraryForm({...libraryForm, title:e.target.value})} placeholder="e.g., Modern 3BHK House" required/>
                </div>
                <div className="form-group">
                  <label>Layout Type</label>
                  <select value={libraryForm.layout_type} onChange={(e)=>setLibraryForm({...libraryForm, layout_type:e.target.value})} required>
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
                  <input type="number" value={libraryForm.bedrooms} onChange={(e)=>setLibraryForm({...libraryForm, bedrooms:e.target.value})} min="1" required/>
                </div>
                <div className="form-group">
                  <label>Bathrooms</label>
                  <input type="number" value={libraryForm.bathrooms} onChange={(e)=>setLibraryForm({...libraryForm, bathrooms:e.target.value})} min="1" required/>
                </div>
                <div className="form-group">
                  <label>
                    Area (sq ft)
                    <InfoPopup 
                      content={
                        <div>
                          <strong>Typical House Areas:</strong><br/>
                          • 1BHK: 400-600 sq ft<br/>
                          • 2BHK: 600-900 sq ft<br/>
                          • 3BHK: 900-1200 sq ft<br/>
                          • 4BHK: 1200-1500 sq ft<br/>
                          • Villa: 1500+ sq ft
                        </div>
                      }
                      position="top"
                    >
                      <span style={{ marginLeft: '8px', cursor: 'pointer', color: '#6b7280' }}>ℹ️</span>
                    </InfoPopup>
                  </label>
                  <input type="number" value={libraryForm.area} onChange={(e)=>setLibraryForm({...libraryForm, area:e.target.value})} min="100" required/>
                </div>
              </div>
              <div className="form-row">
                <div className="form-group">
                  <label>Price Range</label>
                  <input type="text" value={libraryForm.price_range} onChange={(e)=>setLibraryForm({...libraryForm, price_range:e.target.value})} placeholder="e.g., 20-30 Lakhs"/>
                </div>
                <div className="form-group">
                  <label>Price to View (₹)</label>
                  <input type="number" value={libraryForm.view_price || 0} onChange={(e)=>setLibraryForm({...libraryForm, view_price:e.target.value})} placeholder="e.g., 100" min="0" step="0.01"/>
                  <small style={{color:'#666'}}>Amount homeowners must pay to view this layout</small>
                </div>
              </div>
              
              {/* File Upload Section */}
              <div className="form-section" style={{marginTop: '20px', padding: '16px', border: '1px solid #e5e7eb', borderRadius: '8px', background: '#f9fafb'}}>
                <h4 style={{margin: '0 0 16px 0', color: '#374151'}}>Files & Media</h4>
                
                <div className="form-row">
                  <div className="form-group">
                    <label>Preview Image *</label>
                    <input 
                      type="file" 
                      accept="image/*" 
                      onChange={(e)=>setLibraryForm({...libraryForm, image:e.target.files?.[0] || null})}
                      required
                    />
                    <p className="form-help" style={{margin: '4px 0 0 0', fontSize: '0.8rem', color: '#6b7280'}}>Upload a preview image (JPG, PNG, GIF, WebP)</p>
                  </div>
                  <div className="form-group">
                    <label>Layout Design File *</label>
                    <input 
                      type="file" 
                      accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.svg,.dwg,.dxf,.ifc,.rvt,.skp,.3dm,.obj,.stl"
                      onChange={(e)=>setLibraryForm({...libraryForm, design_file:e.target.files?.[0] || null})}
                      required
                    />
                    <p className="form-help" style={{margin: '4px 0 0 0', fontSize: '0.8rem', color: '#6b7280'}}>Upload layout file (PDF, Images, CAD files, 3D models)</p>
                  </div>
                </div>
                
                {/* File Previews */}
                <div className="form-row">
                  <div className="form-group">
                    {libraryForm.image && (
                      <div style={{border:'1px solid #ddd', padding:12, borderRadius:8, background:'#fff'}}>
                        <p style={{margin:'0 0 8px', fontWeight:'500', color:'#374151'}}>📷 Preview Image</p>
                        <img src={URL.createObjectURL(libraryForm.image)} alt="Preview" style={{maxWidth:'100%', maxHeight:'200px', borderRadius:6, objectFit:'cover'}}/>
                        <p style={{margin:'8px 0 0', fontSize:'0.8rem', color:'#6b7280'}}>{libraryForm.image.name}</p>
                      </div>
                    )}
                  </div>
                  <div className="form-group">
                    {libraryForm.design_file && (
                      <div style={{border:'1px solid #ddd', padding:12, borderRadius:8, background:'#fff'}}>
                        <p style={{margin:'0 0 8px', fontWeight:'500', color:'#374151'}}>📄 Layout File</p>
                        <div style={{display:'flex', alignItems:'center', gap:8, padding:'8px 12px', background:'#f3f4f6', borderRadius:6}}>
                          <span style={{fontSize:'1.5rem'}}>
                            {libraryForm.design_file.name.toLowerCase().endsWith('.pdf') ? '📄' :
                             libraryForm.design_file.name.toLowerCase().match(/\.(jpg|jpeg|png|gif|webp)$/) ? '🖼️' :
                             libraryForm.design_file.name.toLowerCase().match(/\.(dwg|dxf)$/) ? '📐' :
                             libraryForm.design_file.name.toLowerCase().match(/\.(skp|3dm|obj|stl)$/) ? '🏗️' : '📎'}
                          </span>
                          <div>
                            <p style={{margin:0, fontWeight:'500'}}>{libraryForm.design_file.name}</p>
                            <p style={{margin:0, fontSize:'0.8rem', color:'#6b7280'}}>
                              {(libraryForm.design_file.size / 1024 / 1024).toFixed(2)} MB
                            </p>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </div>
              <div className="form-group">
                <label>Description</label>
                <textarea 
                  rows="6" 
                  value={libraryForm.description} 
                  onChange={(e)=>setLibraryForm({...libraryForm, description:e.target.value})} 
                  placeholder="Describe the layout features and design highlights..."
                  style={{minHeight: '120px'}}
                />
              </div>
              </div>
              <div className="form-actions" style={{
                padding: '16px 24px',
                borderTop: '1px solid #e5e7eb',
                background: 'white',
                flexShrink: 0,
                display: 'flex',
                gap: '12px',
                justifyContent: 'flex-end'
              }}>
                <button type="button" className="btn btn-secondary" onClick={()=>setShowLibraryForm(false)}>Cancel</button>
                <button type="submit" className="btn btn-primary">Add Layout</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {editLayout && (
        <div className="form-modal">
          <div className="form-content" style={{
            maxWidth:'920px',
            width: '100%',
            maxHeight: '90vh',
            height: 'auto',
            display:'flex',
            flexDirection:'column',
            position: 'relative',
            overflow: 'hidden',
            borderRadius: '12px',
            boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1)'
          }}>
            <div className="form-header" style={{
              flexShrink: 0, 
              padding: '24px 28px', 
              borderBottom: '1px solid #e5e7eb',
              background: 'linear-gradient(to right, #f8fafc, #ffffff)',
              position: 'relative'
            }}>
              <div style={{display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '16px'}}>
                <div style={{flex: 1}}>
                  <div style={{display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '8px'}}>
                    <div style={{
                      width: '44px',
                      height: '44px',
                      borderRadius: '12px',
                      background: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      boxShadow: '0 4px 12px rgba(59, 130, 246, 0.3)'
                    }}>
                      <span style={{fontSize: '20px'}}>📝</span>
                    </div>
                    <div>
                      <h3 style={{
                        margin: '0 0 2px 0', 
                        fontSize: '1.5rem', 
                        fontWeight: '700',
                        color: '#111827',
                        letterSpacing: '-0.01em'
                      }}>Edit Layout</h3>
                      <p style={{
                        margin: 0, 
                        fontSize: '0.875rem', 
                        color: '#6b7280',
                        fontWeight: '500'
                      }}>Update your library item</p>
                    </div>
                  </div>
                  <div className="step-indicator" style={{marginTop: '12px', display: 'flex', gap: '8px'}}>
                    <span className={`step active`} style={{
                      background: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
                      color: 'white',
                      padding: '6px 16px',
                      borderRadius: '20px',
                      fontSize: '0.875rem',
                      fontWeight: '600',
                      boxShadow: '0 2px 8px rgba(59, 130, 246, 0.3)'
                    }}>Basic Info</span>
                  </div>
                </div>
                <button 
                  type="button" 
                  className="modal-close" 
                  onClick={closeEditLayout} 
                  style={{
                    background: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
                    color: 'white',
                    border: 'none',
                    borderRadius: '50%',
                    width: '40px',
                    height: '40px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    fontSize: '22px',
                    lineHeight: 1,
                    flexShrink: 0,
                    boxShadow: '0 4px 12px rgba(239, 68, 68, 0.3)',
                    transition: 'all 0.2s ease',
                    fontWeight: '600'
                  }}
                  onMouseEnter={(e) => {
                    e.target.style.transform = 'scale(1.1)';
                    e.target.style.boxShadow = '0 6px 16px rgba(239, 68, 68, 0.4)';
                  }}
                  onMouseLeave={(e) => {
                    e.target.style.transform = 'scale(1)';
                    e.target.style.boxShadow = '0 4px 12px rgba(239, 68, 68, 0.3)';
                  }}
                >×</button>
              </div>
            </div>
            <div className="scrollable-form-content" style={{
              flex:1,
              overflowY:'auto',
              overflowX:'hidden',
              padding: '24px',
              paddingRight:'32px',
              maxHeight: 'calc(90vh - 200px)',
              minHeight: '300px',
              scrollbarWidth:'thin',
              scrollbarColor:'#cbd5e1 #f1f5f9'
            }}>
              <form onSubmit={(e) => e.preventDefault()} style={{paddingBottom:'16px'}}>
              <div className="form-row">
                <div className="form-group">
                  <label>Title *</label>
                  <input type="text" value={editLayout.title} onChange={(e)=>setEditLayout({...editLayout, title:e.target.value})} required/>
                </div>
                <div className="form-group">
                  <label>Type *</label>
                  <input type="text" value={editLayout.layout_type} onChange={(e)=>setEditLayout({...editLayout, layout_type:e.target.value})} required/>
                </div>
              </div>
              <div className="form-row">
                <div className="form-group">
                  <label>Bedrooms *</label>
                  <input type="number" value={editLayout.bedrooms} onChange={(e)=>setEditLayout({...editLayout, bedrooms:e.target.value})} required/>
                </div>
                <div className="form-group">
                  <label>Bathrooms *</label>
                  <input type="number" value={editLayout.bathrooms} onChange={(e)=>setEditLayout({...editLayout, bathrooms:e.target.value})} required/>
                </div>
                <div className="form-group">
                  <label>
                    Area (sq ft) *
                    <InfoPopup 
                      content={
                        <div>
                          <strong>Typical House Areas:</strong><br/>
                          • 1BHK: 400-600 sq ft<br/>
                          • 2BHK: 600-900 sq ft<br/>
                          • 3BHK: 900-1200 sq ft<br/>
                          • 4BHK: 1200-1500 sq ft<br/>
                          • Villa: 1500+ sq ft
                        </div>
                      }
                      position="top"
                    >
                      <span style={{ marginLeft: '8px', cursor: 'pointer', color: '#6b7280' }}>ℹ️</span>
                    </InfoPopup>
                  </label>
                  <input type="number" value={editLayout.area} onChange={(e)=>setEditLayout({...editLayout, area:e.target.value})} required/>
                </div>
              </div>
               <div className="form-row">
                 <div className="form-group">
                   <label>Price Range</label>
                   <input type="text" value={editLayout.price_range || ''} onChange={(e)=>setEditLayout({...editLayout, price_range:e.target.value})} placeholder="e.g., 20-30 Lakhs"/>
                 </div>
                 <div className="form-group">
                   <label>Price to View (₹)</label>
                   <input type="number" value={editLayout.view_price || 0} onChange={(e)=>setEditLayout({...editLayout, view_price:e.target.value})} placeholder="e.g., 100" min="0" step="0.01"/>
                   <small style={{color:'#666'}}>Amount homeowners must pay to view this layout</small>
                 </div>
               </div>
               
               {/* File Upload Section for Edit */}
               <div className="form-section" style={{marginTop: '20px', padding: '16px', border: '1px solid #e5e7eb', borderRadius: '8px', background: '#f9fafb'}}>
                 <h4 style={{margin: '0 0 16px 0', color: '#374151'}}>Files & Media</h4>
                 <p style={{margin: '0 0 16px 0', color: '#6b7280', fontSize: '0.9rem'}}>Replace existing files or keep current ones</p>
                 
                 <div className="form-row">
                   <div className="form-group">
                     <label>Replace Preview Image</label>
                     <input 
                       type="file" 
                       accept="image/*" 
                       onChange={(e)=>setEditLayout({...editLayout, image:e.target.files?.[0] || null})}
                     />
                     <p className="form-help" style={{margin: '4px 0 0 0', fontSize: '0.8rem', color: '#6b7280'}}>Upload a new preview image (JPG, PNG, GIF, WebP)</p>
                   </div>
                   <div className="form-group">
                     <label>Replace Layout Design File</label>
                     <input 
                       type="file" 
                       accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.svg,.dwg,.dxf,.ifc,.rvt,.skp,.3dm,.obj,.stl"
                       onChange={(e)=>setEditLayout({...editLayout, design_file:e.target.files?.[0] || null})}
                     />
                     <p className="form-help" style={{margin: '4px 0 0 0', fontSize: '0.8rem', color: '#6b7280'}}>Upload new layout file (PDF, Images, CAD files, 3D models)</p>
                   </div>
                 </div>
                 
                 {/* Current Files Display */}
                 <div className="form-row">
                   <div className="form-group">
                     <label>Current Preview Image</label>
                     {editLayout.image_url ? (
                       <div style={{border:'1px solid #ddd', padding:12, borderRadius:8, background:'#fff'}}>
                         <p style={{margin:'0 0 8px', fontWeight:'500', color:'#374151'}}>📷 Current Image</p>
                         <img src={editLayout.image_url} alt="Current Preview" style={{maxWidth:'100%', maxHeight:'200px', borderRadius:6, objectFit:'cover'}}/>
                       </div>
                     ) : (
                       <p style={{color:'#6b7280', fontStyle:'italic'}}>No current image</p>
                     )}
                   </div>
                   <div className="form-group">
                     <label>Current Layout File</label>
                     {editLayout.design_file_url ? (
                       <div style={{border:'1px solid #ddd', padding:12, borderRadius:8, background:'#fff'}}>
                         <p style={{margin:'0 0 8px', fontWeight:'500', color:'#374151'}}>📄 Current Layout File</p>
                         <div style={{display:'flex', alignItems:'center', gap:8, padding:'8px 12px', background:'#f3f4f6', borderRadius:6}}>
                           <span style={{fontSize:'1.5rem'}}>
                             {editLayout.design_file_url.toLowerCase().endsWith('.pdf') ? '📄' :
                              editLayout.design_file_url.toLowerCase().match(/\.(jpg|jpeg|png|gif|webp)$/) ? '🖼️' :
                              editLayout.design_file_url.toLowerCase().match(/\.(dwg|dxf)$/) ? '📐' :
                              editLayout.design_file_url.toLowerCase().match(/\.(skp|3dm|obj|stl)$/) ? '🏗️' : '📎'}
                           </span>
                           <div>
                             <p style={{margin:0, fontWeight:'500'}}>{editLayout.design_file_url.split('/').pop()}</p>
                             <a href={editLayout.design_file_url} target="_blank" rel="noreferrer" style={{fontSize:'0.8rem', color:'#3b82f6'}}>View File</a>
                           </div>
                         </div>
                       </div>
                     ) : (
                       <p style={{color:'#6b7280', fontStyle:'italic'}}>No current layout file</p>
                     )}
                   </div>
                 </div>
                 
                 {/* New File Previews */}
                 {(editLayout.image || editLayout.design_file) && (
                   <div className="form-row">
                     <div className="form-group">
                       {editLayout.image && (
                         <div style={{border:'1px solid #4ade80', padding:12, borderRadius:8, background:'#f0fdf4'}}>
                           <p style={{margin:'0 0 8px', fontWeight:'500', color:'#166534'}}>🆕 New Preview Image</p>
                           <img src={URL.createObjectURL(editLayout.image)} alt="New Preview" style={{maxWidth:'100%', maxHeight:'200px', borderRadius:6, objectFit:'cover'}}/>
                           <p style={{margin:'8px 0 0', fontSize:'0.8rem', color:'#166534'}}>{editLayout.image.name}</p>
                         </div>
                       )}
                     </div>
                     <div className="form-group">
                       {editLayout.design_file && (
                         <div style={{border:'1px solid #4ade80', padding:12, borderRadius:8, background:'#f0fdf4'}}>
                           <p style={{margin:'0 0 8px', fontWeight:'500', color:'#166534'}}>🆕 New Layout File</p>
                           <div style={{display:'flex', alignItems:'center', gap:8, padding:'8px 12px', background:'#dcfce7', borderRadius:6}}>
                             <span style={{fontSize:'1.5rem'}}>
                               {editLayout.design_file.name.toLowerCase().endsWith('.pdf') ? '📄' :
                                editLayout.design_file.name.toLowerCase().match(/\.(jpg|jpeg|png|gif|webp)$/) ? '🖼️' :
                                editLayout.design_file.name.toLowerCase().match(/\.(dwg|dxf)$/) ? '📐' :
                                editLayout.design_file.name.toLowerCase().match(/\.(skp|3dm|obj|stl)$/) ? '🏗️' : '📎'}
                             </span>
                             <div>
                               <p style={{margin:0, fontWeight:'500'}}>{editLayout.design_file.name}</p>
                               <p style={{margin:0, fontSize:'0.8rem', color:'#166534'}}>
                                 {(editLayout.design_file.size / 1024 / 1024).toFixed(2)} MB
                               </p>
                             </div>
                           </div>
                         </div>
                       )}
                     </div>
                   </div>
                 )}
               </div>
               <div className="form-group">
                 <label>Description</label>
                 <textarea rows="4" value={editLayout.description || ''} onChange={(e)=>setEditLayout({...editLayout, description:e.target.value})}></textarea>
               </div>
               
               
               <div className="form-row">
                 <div className="form-group">
                   <label>Status</label>
                   <select value={editLayout.status} onChange={(e)=>setEditLayout({...editLayout, status: e.target.value})}>
                     <option value="active">Active</option>
                     <option value="inactive">Inactive</option>
                   </select>
                 </div>
               </div>
              </form>
            </div>
            <div className="form-actions" style={{
              padding: '16px 24px',
              borderTop: '1px solid #e5e7eb',
              background: 'white',
              flexShrink: 0,
              display: 'flex',
              gap: '12px',
              justifyContent: 'flex-end'
            }}>
              <button type="button" className="btn btn-secondary" onClick={closeEditLayout}>Cancel</button>
              <button type="button" className="btn btn-primary" onClick={saveEditLayout}>Save Changes</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );

  const renderHousePlans = () => {
    if (showHousePlanManager) {
      return (
        <HousePlanManager
          layoutRequestId={selectedRequestForPlan}
          onClose={() => {
            setShowHousePlanManager(false);
            setSelectedRequestForPlan(null);
          }}
        />
      );
    }

    return (
      <div style={{
        height: '100vh', 
        overflowY: 'auto', 
        paddingRight: '8px',
        scrollbarWidth: 'thin',
        scrollbarColor: '#cbd5e0 #f7fafc'
      }}>
        <div className="main-header">
          <div className="header-content">
            <div>
              <h1>House Plans</h1>
              <p>Create custom house plans by drawing room layouts for your clients</p>
            </div>
            <button 
              className="btn btn-primary" 
              onClick={() => {
                setSelectedRequestForPlan(null);
                setShowHousePlanManager(true);
              }}
            >
              + Create New Plan
            </button>
          </div>
        </div>

        <div className="section-card">
          <div className="section-header">
            <h2>Your Assigned Projects</h2>
            <p>Create custom house plans for these client requests</p>
          </div>
          <div className="section-content">
            {layoutRequests.length === 0 ? (
              <div className="empty-state">
                <div className="empty-icon">🏠</div>
                <h3>No Assigned Projects</h3>
                <p>You don't have any assigned layout requests yet. Check the Layout Requests tab to see available projects.</p>
                <button 
                  className="btn btn-secondary" 
                  onClick={() => setActiveTab('requests')}
                >
                  View Layout Requests
                </button>
              </div>
            ) : (
              <div className="request-grid">
                {layoutRequests.map(request => (
                  <div key={request.id} className="request-card">
                    <div className="request-header">
                      <h4>{request.homeowner_name}</h4>
                      <span className="request-budget">{request.budget_range}</span>
                    </div>
                    
                    <div className="request-details">
                      <div className="detail-item">
                        <span className="detail-label">Plot Size:</span>
                        <span className="detail-value">{request.plot_size}</span>
                      </div>
                      <div className="detail-item">
                        <span className="detail-label">Location:</span>
                        <span className="detail-value">{request.location || 'Not specified'}</span>
                      </div>
                      <div className="detail-item">
                        <span className="detail-label">Style:</span>
                        <span className="detail-value">{request.preferred_style || 'Any'}</span>
                      </div>
                    </div>

                    <RequirementsDisplay 
                      requirements={request.requirements} 
                      compact={true}
                    />

                    <div className="request-actions">
                      <button 
                        className="btn btn-primary"
                        onClick={() => {
                          setSelectedRequestForPlan(request.id);
                          setActiveTab('house-plans');
                          setShowHousePlanManager(true);
                        }}
                      >
                        Create House Plan
                      </button>
                      <button 
                        className="btn btn-secondary"
                        onClick={() => downloadProjectPDF(request)}
                      >
                        Download Details
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        <div className="section-card">
          <div className="section-header">
            <h2>House Plans in Construction</h2>
            <p>Your house plans being worked on by contractors</p>
          </div>
          <div className="section-content">
            {contractorPlansLoading ? (
              <div className="loading-state">
                <div className="loading-spinner"></div>
                <p>Loading contractor work...</p>
              </div>
            ) : contractorHousePlans.length === 0 ? (
              <div className="empty-state">
                <div className="empty-icon">🏗️</div>
                <h3>No Active Construction</h3>
                <p>None of your house plans are currently being worked on by contractors.</p>
                <div className="empty-actions">
                  <button 
                    className="btn btn-outline"
                    onClick={() => {
                      setSelectedRequestForPlan(null);
                      setShowHousePlanManager(true);
                    }}
                  >
                    Create General Plan
                  </button>
                  <button 
                    className="btn btn-outline"
                    onClick={fetchContractorHousePlans}
                    disabled={contractorPlansLoading}
                  >
                    🔄 Refresh Data
                  </button>
                </div>
                <div className="debug-info">
                  <details>
                    <summary>Debug Information</summary>
                    <p>If you expect to see contractor work here, check:</p>
                    <ul>
                      <li>House plans have been sent to contractors</li>
                      <li>Contractor engagements exist in the system</li>
                      <li>Database tables are properly configured</li>
                    </ul>
                    <p>You can run the debug script at: <code>/buildhub/backend/debug_contractor_house_plans.php</code></p>
                  </details>
                </div>
              </div>
            ) : (
              <>
                {/* Summary Stats */}
                <div className="contractor-plans-summary">
                  <div className="summary-stats">
                    <div className="stat-item">
                      <span className="stat-number">{contractorPlansSummary.total_plans}</span>
                      <span className="stat-label">Plans in Work</span>
                    </div>
                    <div className="stat-item">
                      <span className="stat-number">{contractorPlansSummary.total_contractors}</span>
                      <span className="stat-label">Active Contractors</span>
                    </div>
                    <div className="stat-item">
                      <span className="stat-number">{contractorPlansSummary.active_estimates}</span>
                      <span className="stat-label">Pending Estimates</span>
                    </div>
                    <div className="stat-item">
                      <span className="stat-number">{contractorPlansSummary.completed_estimates}</span>
                      <span className="stat-label">Completed Estimates</span>
                    </div>
                  </div>
                  <button 
                    className="btn btn-outline refresh-contractor-plans"
                    onClick={fetchContractorHousePlans}
                    disabled={contractorPlansLoading}
                  >
                    🔄 Refresh
                  </button>
                </div>

                {/* House Plans List */}
                <div className="contractor-house-plans-list">
                  {contractorHousePlans.map(plan => (
                    <div key={plan.house_plan_id} className="contractor-plan-card">
                      <div className="plan-header">
                        <div className="plan-info">
                          <h4>{plan.plan_name}</h4>
                          <div className="plan-details">
                            <span className="plan-size">{plan.plot_width}' × {plan.plot_height}'</span>
                            <span className="plan-area">{plan.total_area} sq ft</span>
                            <span className={`plan-status status-${plan.plan_status}`}>
                              {plan.plan_status.charAt(0).toUpperCase() + plan.plan_status.slice(1)}
                            </span>
                          </div>
                        </div>
                        <div className="plan-actions">
                          <button 
                            className="btn btn-sm btn-outline"
                            onClick={() => {
                              // Open plan for editing
                              setSelectedRequestForPlan({ id: plan.layout_request?.id });
                              setShowHousePlanManager(true);
                            }}
                            title="View/Edit Plan"
                          >
                            📐 View Plan
                          </button>
                        </div>
                      </div>

                      {/* Homeowner Information */}
                      {plan.homeowner && (
                        <div className="homeowner-info">
                          <div className="info-section">
                            <h5>👤 Client Information</h5>
                            <div className="client-details">
                              <div className="client-item">
                                <span className="label">Name:</span>
                                <span className="value">{plan.homeowner.name || 'N/A'}</span>
                              </div>
                              <div className="client-item">
                                <span className="label">Email:</span>
                                <span className="value">{plan.homeowner.email || 'N/A'}</span>
                              </div>
                              {plan.homeowner.phone && (
                                <div className="client-item">
                                  <span className="label">Phone:</span>
                                  <span className="value">{plan.homeowner.phone}</span>
                                </div>
                              )}
                            </div>
                          </div>

                          {/* Project Details */}
                          {plan.layout_request && (
                            <div className="project-details">
                              <h5>📋 Project Details</h5>
                              <div className="project-info">
                                {plan.layout_request.budget_range && (
                                  <div className="project-item">
                                    <span className="label">Budget:</span>
                                    <span className="value">{plan.layout_request.budget_range}</span>
                                  </div>
                                )}
                                {plan.layout_request.location && (
                                  <div className="project-item">
                                    <span className="label">Location:</span>
                                    <span className="value">{plan.layout_request.location}</span>
                                  </div>
                                )}
                                {plan.layout_request.timeline && (
                                  <div className="project-item">
                                    <span className="label">Timeline:</span>
                                    <span className="value">{plan.layout_request.timeline}</span>
                                  </div>
                                )}
                              </div>
                            </div>
                          )}
                        </div>
                      )}

                      {/* Contractor Work Information */}
                      <div className="contractor-work-section">
                        <h5>🏗️ Contractor Activity ({plan.contractor_work.length})</h5>
                        {plan.contractor_work.length === 0 ? (
                          <p className="no-contractors">No contractors currently working on this plan.</p>
                        ) : (
                          <div className="contractors-list">
                            {plan.contractor_work.map((work, index) => (
                              <div key={index} className="contractor-work-item">
                                <div className="contractor-header">
                                  <div className="contractor-info">
                                    <strong>{work.contractor.name}</strong>
                                    <span className="contractor-contact">{work.contractor.email}</span>
                                  </div>
                                  <div className="work-status">
                                    {work.estimate ? (
                                      <span className={`estimate-status status-${work.estimate.status}`}>
                                        {work.estimate.status === 'accepted' ? '✅ Estimate Accepted' : 
                                         work.estimate.status === 'pending' ? '⏳ Estimate Pending' :
                                         work.estimate.status === 'rejected' ? '❌ Estimate Rejected' :
                                         '📋 Estimate Submitted'}
                                      </span>
                                    ) : work.send_details?.acknowledged_at ? (
                                      <span className="work-status acknowledged">✅ Acknowledged</span>
                                    ) : (
                                      <span className="work-status pending">⏳ Sent to Contractor</span>
                                    )}
                                  </div>
                                </div>

                                <div className="work-details">
                                  {work.send_details && (
                                    <div className="send-info">
                                      <small>
                                        Sent: {new Date(work.send_details.sent_at).toLocaleDateString()}
                                        {work.send_details.acknowledged_at && (
                                          <> • Acknowledged: {new Date(work.send_details.acknowledged_at).toLocaleDateString()}</>
                                        )}
                                        {work.send_details.due_date && (
                                          <> • Due: {new Date(work.send_details.due_date).toLocaleDateString()}</>
                                        )}
                                      </small>
                                    </div>
                                  )}

                                  {work.estimate && (
                                    <div className="estimate-info">
                                      <div className="estimate-details">
                                        {work.estimate.amount && (
                                          <span className="estimate-amount">
                                            Amount: ₹{parseFloat(work.estimate.amount).toLocaleString()}
                                          </span>
                                        )}
                                        <span className="estimate-date">
                                          Submitted: {new Date(work.estimate.created_at).toLocaleDateString()}
                                        </span>
                                        {work.estimate.accepted_at && (
                                          <span className="accepted-date">
                                            Accepted: {new Date(work.estimate.accepted_at).toLocaleDateString()}
                                          </span>
                                        )}
                                      </div>
                                    </div>
                                  )}

                                  {(work.engagement?.message || work.send_details?.message) && (
                                    <div className="work-message">
                                      <small>"{work.engagement?.message || work.send_details?.message}"</small>
                                    </div>
                                  )}
                                </div>
                              </div>
                            ))}
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>

                {/* Create New Plan Button */}
                <div className="create-plan-section">
                  <button 
                    className="btn btn-primary"
                    onClick={() => {
                      setSelectedRequestForPlan(null);
                      setShowHousePlanManager(true);
                    }}
                  >
                    ➕ Create New General Plan
                  </button>
                </div>
              </>
            )}
          </div>
        </div>
      </div>
    );
  };

  const renderConceptPreview = () => (
    <div>
      <div className="main-header">
        <h2>Concept Preview Generation</h2>
        <p>Generate exterior architectural concept previews for early-stage client discussions</p>
      </div>

      <div className="dashboard-content">
        {/* Generation Form */}
        <div className="card" style={{ marginBottom: '24px' }}>
          <div className="card-header">
            <h3>Generate New Concept Preview</h3>
            <p>Describe your architectural concept in natural language. The system will create a refined prompt and generate a photorealistic exterior preview image.</p>
          </div>
          <div className="card-body">
            <div className="form-group">
              <label>Select Project</label>
              <select
                value={selectedProjectForConcept}
                onChange={(e) => setSelectedProjectForConcept(e.target.value)}
                disabled={conceptGenerationLoading}
              >
                <option value="">Choose a project...</option>
                {layoutRequests.map(request => (
                  <option key={request.id} value={request.id}>
                    {request.homeowner_name} - {request.plot_size} sq ft ({request.budget_range})
                  </option>
                ))}
              </select>
            </div>
            
            <div className="form-group">
              <label>Architectural Concept Description</label>
              <textarea
                value={conceptPreviewText}
                onChange={(e) => setConceptPreviewText(e.target.value)}
                placeholder="Describe your architectural concept in natural language. For example: 'A modern two-story villa with clean lines, large windows, flat roof, white exterior walls, and a contemporary entrance with glass doors. The design should emphasize minimalism and natural light.'"
                rows="4"
                disabled={conceptGenerationLoading}
                style={{ resize: 'vertical' }}
              />
              <small style={{ color: '#666', fontSize: '0.85rem' }}>
                Focus on exterior elements only. Avoid interior details, room layouts, or construction specifications.
              </small>
            </div>
            
            <button
              onClick={handleConceptPreviewGeneration}
              disabled={conceptGenerationLoading || !conceptPreviewText.trim() || !selectedProjectForConcept}
              className="btn btn-primary"
            >
              {conceptGenerationLoading ? 'Generating Preview...' : 'Generate Preview Image'}
            </button>
          </div>
        </div>

        {/* Generated Previews */}
        <div className="card">
          <div className="card-header">
            <h3>Generated Concept Previews</h3>
            <p>Your generated concept previews will appear here. These are for discussion purposes only and not final plans.</p>
          </div>
          <div className="card-body">
            {conceptPreviews.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '40px', color: '#666' }}>
                <div style={{ fontSize: '48px', marginBottom: '16px' }}>🏗️</div>
                <p>No concept previews generated yet.</p>
                <p>Create your first concept preview using the form above.</p>
              </div>
            ) : (
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(350px, 1fr))', gap: '20px' }}>
                {conceptPreviews.map(preview => (
                  <div key={preview.id} className="concept-preview-card" style={{
                    border: '1px solid #e5e7eb',
                    borderRadius: '12px',
                    overflow: 'hidden',
                    background: '#fff',
                    boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                  }}>
                    {/* Preview Image */}
                    <div style={{ 
                      height: '200px', 
                      background: preview.status === 'completed' && preview.image_url ? `url(${preview.image_url})` : '#f3f4f6',
                      backgroundSize: 'cover',
                      backgroundPosition: 'center',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      position: 'relative'
                    }}>
                      {preview.status === 'processing' || preview.status === 'generating' ? (
                        <div style={{ textAlign: 'center', color: '#666' }}>
                          <div className="loading-spinner" style={{ margin: '0 auto 8px' }}></div>
                          <p>Generating...</p>
                        </div>
                      ) : preview.status === 'failed' ? (
                        <div style={{ textAlign: 'center', color: '#dc2626' }}>
                          <div style={{ fontSize: '24px', marginBottom: '8px' }}>⚠️</div>
                          <p>Generation Failed</p>
                        </div>
                      ) : !preview.image_url ? (
                        <div style={{ textAlign: 'center', color: '#666' }}>
                          <div style={{ fontSize: '24px', marginBottom: '8px' }}>🖼️</div>
                          <p>No Image</p>
                        </div>
                      ) : null}
                      
                      {/* Status Badge */}
                      <div style={{
                        position: 'absolute',
                        top: '8px',
                        right: '8px',
                        padding: '4px 8px',
                        borderRadius: '12px',
                        fontSize: '0.75rem',
                        fontWeight: 'bold',
                        background: preview.status === 'completed' ? '#10b981' : 
                                   preview.status === 'failed' ? '#dc2626' : '#f59e0b',
                        color: 'white'
                      }}>
                        {preview.status === 'completed' ? 'Ready' : 
                         preview.status === 'failed' ? 'Failed' : 'Processing'}
                      </div>
                    </div>
                    
                    {/* Preview Details */}
                    <div style={{ padding: '16px' }}>
                      <h4 style={{ margin: '0 0 8px 0', fontSize: '1rem' }}>
                        {preview.homeowner_name || 'Client'} - Concept Preview
                      </h4>
                      <p style={{ 
                        margin: '0 0 12px 0', 
                        fontSize: '0.85rem', 
                        color: '#666',
                        display: '-webkit-box',
                        WebkitLineClamp: 2,
                        WebkitBoxOrient: 'vertical',
                        overflow: 'hidden'
                      }}>
                        {preview.original_description}
                      </p>
                      
                      {preview.refined_prompt && (
                        <details style={{ marginBottom: '12px' }}>
                          <summary style={{ fontSize: '0.8rem', color: '#666', cursor: 'pointer' }}>
                            View Refined Prompt
                          </summary>
                          <p style={{ 
                            fontSize: '0.75rem', 
                            color: '#555', 
                            marginTop: '8px',
                            padding: '8px',
                            background: '#f9fafb',
                            borderRadius: '4px'
                          }}>
                            {preview.refined_prompt}
                          </p>
                        </details>
                      )}
                      
                      <div style={{ 
                        display: 'flex', 
                        justifyContent: 'space-between', 
                        alignItems: 'center',
                        fontSize: '0.75rem',
                        color: '#666',
                        marginBottom: '12px'
                      }}>
                        <span>Created: {new Date(preview.created_at).toLocaleDateString()}</span>
                        {preview.updated_at !== preview.created_at && (
                          <span>Updated: {new Date(preview.updated_at).toLocaleDateString()}</span>
                        )}
                      </div>
                      
                      {/* Action Buttons */}
                      <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
                        {preview.status === 'completed' && preview.image_url && (
                          <>
                            <button
                              onClick={() => handleDownloadConcept(preview)}
                              className="btn btn-sm btn-success"
                              style={{ flex: '1 1 auto', minWidth: '80px' }}
                              title="Download image"
                            >
                              📥 Download
                            </button>
                            <a
                              href={preview.image_url}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="btn btn-sm btn-primary"
                              style={{ flex: '1 1 auto', minWidth: '80px', textAlign: 'center', textDecoration: 'none' }}
                              title="View full size"
                            >
                              🔍 View
                            </a>
                          </>
                        )}
                        
                        {(preview.status === 'completed' || preview.status === 'failed') && (
                          <button
                            onClick={() => handleConceptRegeneration(preview.id)}
                            className="btn btn-sm btn-secondary"
                            style={{ flex: '1 1 auto', minWidth: '80px' }}
                            title="Regenerate concept"
                          >
                            🔄 Regenerate
                          </button>
                        )}
                        
                        <button
                          onClick={() => handleDeleteConcept(preview.id)}
                          className="btn btn-sm btn-danger"
                          style={{ flex: '1 1 auto', minWidth: '80px' }}
                          title="Delete concept"
                        >
                          🗑️ Delete
                        </button>
                      </div>
                      
                      {preview.error_message && (
                        <div style={{
                          marginTop: '12px',
                          padding: '8px',
                          background: '#fef2f2',
                          border: '1px solid #fecaca',
                          borderRadius: '4px',
                          fontSize: '0.8rem',
                          color: '#dc2626'
                        }}>
                          Error: {preview.error_message}
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );

  // Render functions for different tabs


  return (
    <div className="dashboard-container">
      <style>{`
        .scrollable-form-content::-webkit-scrollbar {
          width: 8px;
        }
        .scrollable-form-content::-webkit-scrollbar-track {
          background: #f1f5f9;
          border-radius: 4px;
        }
        .scrollable-form-content::-webkit-scrollbar-thumb {
          background: #cbd5e1;
          border-radius: 4px;
        }
        .scrollable-form-content::-webkit-scrollbar-thumb:hover {
          background: #94a3b8;
        }
        
        /* Enhanced scrollbar for form content */
        .form-content::-webkit-scrollbar {
          width: 10px;
        }
        .form-content::-webkit-scrollbar-track {
          background: #f1f5f9;
          border-radius: 5px;
          margin: 5px;
        }
        .form-content::-webkit-scrollbar-thumb {
          background: #64748b;
          border-radius: 5px;
          border: 2px solid #f1f5f9;
        }
        .form-content::-webkit-scrollbar-thumb:hover {
          background: #475569;
        }

        /* Image Modal Animations */
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }
        
        @keyframes slideIn {
          from { 
            opacity: 0; 
            transform: translateY(-20px) scale(0.95); 
          }
          to { 
            opacity: 1; 
            transform: translateY(0) scale(1); 
          }
        }

        /* Enhanced hover effects */
        .image-card:hover {
          transform: scale(1.02) !important;
          box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
          border-color: #3b82f6 !important;
        }
        
        /* Force scrollbar to always show */
        .form-content {
          scrollbar-gutter: stable;
        }
        .form-steps {
          display: flex;
          gap: 16px;
          margin-top: 12px;
        }
        .step {
          display: flex;
          align-items: center;
          gap: 8px;
          padding: 8px 12px;
          border-radius: 6px;
          background: #f3f4f6;
          color: #6b7280;
          font-size: 0.875rem;
          font-weight: 500;
        }
        .step.active {
          background: #3b82f6;
          color: white;
        }
        .step.completed {
          background: #10b981;
          color: white;
        }
        .step-number {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 20px;
          height: 20px;
          border-radius: 50%;
          background: rgba(255, 255, 255, 0.2);
          font-size: 0.75rem;
          font-weight: 600;
        }
        .step.active .step-number,
        .step.completed .step-number {
          background: rgba(255, 255, 255, 0.3);
        }
      `}</style>
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
            onClick={(e) => { e.preventDefault(); setActiveTab('dashboard'); }}
          >
            <span className="sb-label">Dashboard</span>
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'requests' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('requests'); }}
          >
            <span className="sb-label">Layout Requests</span>
            {(pendingRequestsCount > 0 || acceptedRequestsCount > 0) && (
              <div style={{ display:'inline-flex', gap:6, marginLeft:'auto' }}>
                {pendingRequestsCount > 0 && (
                  <span className="nav-badge pending pulse" title="Pending requests">{pendingRequestsCount}</span>
                )}
                {acceptedRequestsCount > 0 && (
                  <span className="nav-badge accepted" title="Accepted requests">{acceptedRequestsCount}</span>
                )}
              </div>
            )}
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'designs' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('designs'); }}
          >
            <span className="sb-label">My Designs</span>
            {designsCount > 0 && (<span className="nav-badge pulse" style={{ marginLeft:'auto' }}>{designsCount}</span>)}
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'library' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('library'); }}
          >
            <span className="sb-label">My Layout Library</span>
            {libraryCount > 0 && (<span className="nav-badge pulse" style={{ marginLeft:'auto' }}>{libraryCount}</span>)}
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'house-plans' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('house-plans'); }}
          >
            <span className="sb-label">House Plans</span>
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'concept-preview' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('concept-preview'); }}
          >
            <span className="sb-label">Concept Preview Generation</span>
          </a>

        </nav>

        <div className="sidebar-footer sb-footer" style={{padding:'12px'}}>
          <div style={{position:'relative'}}>

            {showSidebarProfileMenu && (
              <div className="profile-dropdown open" style={{position:'absolute', bottom:'52px', left:0, right:0, zIndex: 1000}}>
                <div className="dropdown-header">My Account</div>
                <button type="button" className="dropdown-item" onClick={handleLogout}>Logout</button>
              </div>
            )}
          </div>

          <div className="sidebar-version" style={{marginTop:10}}>v1.0.0</div>
        </div>
      </div>

      {/* Main Content */}
      <div className="dashboard-main soft-main shifted">
        {error && (
          <div className="alert alert-error">
            {error}
            <button onClick={() => setError('')} className="alert-close">×</button>
          </div>
        )}
        
        {success && (
          <div className="alert alert-success">
            {success}
            <button onClick={() => setSuccess('')} className="alert-close">×</button>
          </div>
        )}

        {activeTab === 'dashboard' && renderDashboard()}
        {activeTab === 'requests' && renderRequests()}
        {activeTab === 'designs' && renderDesigns()}
        {activeTab === 'library' && renderLibrary()}
        {activeTab === 'house-plans' && renderHousePlans()}
        {activeTab === 'concept-preview' && renderConceptPreview()}
        {activeTab === 'profile' && renderProfile()}

        {/* Upload Form Modal */}
        {showUploadForm && (
          <div className="form-modal">
            <div className="form-content">
              <div className="form-header">
                <h3>Upload Design</h3>
                <p>Submit your architectural design for a client request</p>
              </div>
            </div>
          </div>
        )}

        {/* Preview Modal */}
        {renderPreviewModal()}

        {/* Upload Form Modal */}
        {showUploadForm && (
          <div className="form-modal">
            <div className="form-content" style={{maxWidth: '1200px', width: '90vw'}}>
              <div className="form-header">
                <h3>Create Design</h3>
                <p>Submit your architectural design with comprehensive technical details</p>
                <div className="upload-steps">
                  <div className={`step ${uploadFormStep >= 0 ? 'active' : ''}`}>1. Basic Info</div>
                  <div className={`step ${uploadFormStep >= 1 ? 'active' : ''}`}>2. Technical Details</div>
                  <div className={`step ${uploadFormStep >= 2 ? 'active' : ''}`}>3. Files & Submit</div>
                </div>
              </div>
              
              {/* Step 1: Basic Information */}
              {uploadFormStep === 0 && (
                <div className="upload-step">
                  <div className="form-row">
                    <div className="form-group">
                      <label>Send To</label>
                      <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:'12px'}}>
                        <div>
                          <label style={{fontSize:'0.85rem'}}>By Request (optional)</label>
                          <select
                            value={uploadData.request_id}
                            onChange={(e) => setUploadData({...uploadData, request_id: e.target.value})}
                          >
                            <option value="">Choose a client request</option>
                            {layoutRequests.map(request => (
                              <option key={request.id} value={request.id}>
                                {request.client_name} - {request.plot_size} sq ft ({request.budget_range})
                              </option>
                            ))}
                          </select>
                        </div>
                        <div>
                          <label style={{fontSize:'0.85rem'}}>Direct Homeowner ID (optional)</label>
                          <input
                            type="number"
                            value={uploadData.homeowner_id}
                            onChange={(e) => setUploadData({...uploadData, homeowner_id: e.target.value})}
                            placeholder="e.g., 123"
                          />
                        </div>
                      </div>
                    </div>
                    <div className="form-group">
                      <label>Design Title *</label>
                      <input
                        type="text"
                        value={uploadData.design_title}
                        onChange={(e) => setUploadData({...uploadData, design_title: e.target.value})}
                        placeholder="e.g., Modern 3BHK Villa Design"
                        required
                      />
                    </div>
                  </div>

                  <div className="form-group">
                    <label>Description</label>
                    <textarea
                      value={uploadData.description}
                      onChange={(e) => setUploadData({...uploadData, description: e.target.value})}
                      placeholder="Describe your design features, materials, and special considerations..."
                      rows="4"
                    />
                  </div>

                  <div className="form-actions">
                    <button 
                      type="button" 
                      onClick={() => setShowUploadForm(false)}
                      className="btn btn-secondary"
                    >
                      Cancel
                    </button>
                    <button 
                      type="button" 
                      onClick={() => setUploadFormStep(1)}
                      className="btn btn-primary"
                      disabled={!uploadData.design_title}
                    >
                      Next: Technical Details
                    </button>
                  </div>
                </div>
              )}

              {/* Step 2: Technical Details */}
              {uploadFormStep === 1 && (
                <div className="upload-step">
                  <TechnicalDetailsForm 
                    data={uploadData} 
                    setData={setUploadData} 
                    onNext={() => setUploadFormStep(2)} 
                    onPrev={() => setUploadFormStep(0)} 
                  />
                </div>
              )}

              {/* Step 3: Files and Submit */}
              {uploadFormStep === 2 && (
                <div className="upload-step">
                  <div className="form-group">
                    <label>Design Files *</label>
                    <input
                      type="file"
                      multiple
                      accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.svg,.heic,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.rtf,.dwg,.dxf,.ifc,.rvt,.skp,.3dm,.obj,.stl,.zip,.rar,.7z,.mp4,.mov,.avi,.m4v"
                      onChange={(e) => setUploadData({...uploadData, files: Array.from(e.target.files)})}
                      required
                    />
                    <p className="form-help">Select multiple files (images, PDFs, docs, CAD/3D, archives, videos)</p>
                  </div>

                  {/* Review Section */}
                  <div className="review-section">
                    <h4>Review Your Submission</h4>
                    <div className="review-grid">
                      <div className="review-item">
                        <div className="review-label">Design Title</div>
                        <div className="review-value">{uploadData.design_title || 'Not provided'}</div>
                      </div>
                      <div className="review-item">
                        <div className="review-label">Description</div>
                        <div className="review-value">{uploadData.description || 'No description'}</div>
                      </div>
                      {uploadData.technical_details?.floor_plans?.layout_description && (
                        <div className="review-item">
                          <div className="review-label">Floor Plan Layout</div>
                          <div className="review-value">{uploadData.technical_details.floor_plans.layout_description}</div>
                        </div>
                      )}
                      <div className="review-item">
                        <div className="review-label">Files</div>
                        <div className="review-value">{uploadData.files.length} file(s) selected</div>
                      </div>
                    </div>
                  </div>

                  <div className="form-actions">
                    <button 
                      type="button" 
                      onClick={() => setUploadFormStep(1)}
                      className="btn btn-secondary"
                    >
                      Back
                    </button>
                    <button 
                      type="button" 
                      onClick={handleUploadSubmit}
                      disabled={loading || uploadData.files.length === 0}
                      className="btn btn-primary"
                    >
                      {loading ? 'Uploading...' : 'Upload Design'}
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        )}
      </div>

      {/* Technical Details Modal for Upload Design */}
      <TechnicalDetailsModal
        isOpen={showTechnicalModal}
        onClose={() => {
          setShowTechnicalModal(false);
          setSelectedRequestForUpload(null);
        }}
        onSubmit={handleTechnicalDetailsSubmit}
        planData={{
          plan_name: selectedRequestForUpload ? `${selectedRequestForUpload.homeowner_name || 'Client'} House Plan` : '',
          rooms: [],
          scale_ratio: 1.2
        }}
        loading={technicalSubmissionLoading}
      />
    </div>
  );
};

// Assigned Requests Component
const AssignedRequests = ({ onCreateFromAssigned, expandedAssignments, setExpandedAssignments, toast }) => {
  const [items, setItems] = React.useState([]);
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState('');

  // Helpers: parse/normalize requirements into structured fields
  const normalizeRequirements = (reqText, reqParsed) => {
    // Prefer parsed JSON if valid
    const src = (reqParsed && typeof reqParsed === 'object') ? reqParsed : {};
    // Try to detect key info from text as best-effort
    const text = (reqText || '').toString();
    const pick = (k) => src[k] ?? null;
    const extract = (label) => {
      const m = text.match(new RegExp(label + ":\\s*([^\\n]+)", 'i'));
      return m ? m[1].trim() : null;
    };
    return {
      rooms: pick('rooms') ?? extract('rooms') ?? extract('bedrooms') ?? null,
      family_needs: pick('family_needs') ?? extract('family needs') ?? null,
      style: pick('preferred_style') ?? pick('style') ?? pick('aesthetic') ?? extract('style') ?? null,
      plot_shape: pick('plot_shape') ?? extract('plot shape') ?? null,
      topography: pick('topography') ?? extract('topography') ?? null,
      development_laws: pick('development_laws') ?? extract('development laws') ?? null,
      notes: pick('notes') ?? null,
      raw: text.trim()
    };
  };

  const toClipboard = async (str) => {
    try { await navigator.clipboard.writeText(str); } catch {}
  };

  const downloadText = (filename, content) => {
    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename; a.click();
    URL.revokeObjectURL(url);
  };

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const res = await fetch('/buildhub/backend/api/architect/get_assigned_requests.php');
      const data = await res.json();
      if (data.success) {
        // Show only assignments waiting for architect response (avoid duplicates with Available Requests)
        const filteredAssignments = (data.assignments || []).filter(assignment => 
          assignment.assignment_status === 'sent'
        );
        setItems(filteredAssignments);
      } else {
        setError(data.message || 'Failed to load assigned requests');
      }
    } catch (e) {
      setError('Error loading assigned requests');
    } finally {
      setLoading(false);
    }
  };

  React.useEffect(() => { load(); }, []);

  const respond = async (assignment_id, action) => {
    try {
      const res = await fetch('/buildhub/backend/api/architect/respond_assignment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ assignment_id, action })
      });
      const data = await res.json().catch(() => ({}));
      
      if (data && data.success) {
        // Show success message
        if (action === 'accept') {
          toast.success('Assignment accepted! The request is now available in your Available Requests section.');
        } else if (action === 'decline') {
          toast.success('Assignment declined.');
        }
        
        // Refresh both assigned requests and available requests
      await load();
        // Trigger a page refresh to update the Available Requests section
        window.location.reload();
      } else {
        toast.error('Failed to respond to assignment: ' + (data.message || 'Unknown error'));
      }
      
      return data && data.success ? (data.status || null) : null;
    } catch (error) {
      console.error('Error responding to assignment:', error);
      toast.error('Network error occurred. Please try again.');
      return null;
    }
  };

  return (
    <div className="section-content">
      {loading ? (
        <div className="loading">Loading assigned requests...</div>
      ) : items.length === 0 ? (
        <div className="empty-state">
          <div className="empty-icon">📬</div>
          <h3>No Assigned Requests</h3>
          <p>Homeowners haven’t assigned requests to you yet.</p>
          <button className="btn btn-secondary" onClick={load}>Refresh</button>
        </div>
      ) : (
        <div className="item-list">
          {items.map(a => (
            <div key={a.assignment_id} className="list-item">
              <div className="item-icon">📌</div>
              <div className="item-content">
                <h4 className="item-title">Request #{a.layout_request.id} • {a.layout_request.plot_size} sq ft</h4>
                <p className="item-subtitle">Budget: {a.layout_request.budget_range} • From: {a.homeowner.name}</p>
                <p className="item-meta">Assigned: {new Date(a.assigned_at).toLocaleDateString()} • Status: {a.assignment_status}</p>
                {a.message && <p className="item-description">Message: {a.message}</p>}

                {/* Interactive requirement details */}
                {expandedAssignments[a.assignment_id] && (() => {
                  const R = normalizeRequirements(a.layout_request.requirements, a.layout_request.requirements_parsed);
                  const chips = [
                    R.rooms ? { label: 'Rooms', value: R.rooms } : null,
                    R.family_needs ? { label: 'Family needs', value: R.family_needs } : null,
                    (a.layout_request.preferred_style || R.style) ? { label: 'Style', value: a.layout_request.preferred_style || R.style } : null,
                  ].filter(Boolean);
                  return (
                    <div className="details-card">
                      <div className="details-header">
                        <strong>Requirements</strong>
                      </div>
                      <div className="details-grid">
                        <div>
                          <h5>Site & Budget</h5>
                          <div className="chips">
                            <span className="chip"><strong>Plot:</strong> {a.layout_request.plot_size || '—'}</span>
                            <span className="chip"><strong>Budget:</strong> {a.layout_request.budget_range || '—'}</span>
                            <span className="chip"><strong>Location:</strong> {a.layout_request.location || '—'}</span>
                            <span className="chip"><strong>Timeline:</strong> {a.layout_request.timeline || '—'}</span>
                            {R.plot_shape && <span className="chip"><strong>Plot shape:</strong> {R.plot_shape}</span>}
                            {R.topography && <span className="chip"><strong>Topography:</strong> {R.topography}</span>}
                            {R.development_laws && <span className="chip"><strong>Dev. laws:</strong> {R.development_laws}</span>}
                          </div>
                        </div>
                        <div>
                          <h5>Preferences</h5>
                          <div className="chips">
                            {(a.layout_request.layout_type || 'custom') && (
                              <span className="chip"><strong>Type:</strong> {a.layout_request.layout_type || 'custom'}</span>
                            )}
                            {chips.map((c, idx) => (
                              <span key={idx} className="chip"><strong>{c.label}:</strong> {c.value}</span>
                            ))}
                            {R.style && (
                              <span className="chip"><strong>Style:</strong> {R.style}</span>
                            )}
                            {a.layout_request.library?.title && (
                              <span className="chip"><strong>Library:</strong> {a.layout_request.library.title}</span>
                            )}
                          </div>
                        </div>
                        <div className="span-2">
                          <h5>Notes</h5>
                          <p className="item-description">{R.notes || (R.raw ? null : a.layout_request.requirements) || '—'}</p>
                          {!R.notes && R.raw && (
                            <div className="muted" style={{fontSize:12}}>Original notes available in raw request.</div>
                          )}
                          {a.layout_request.library?.image_url && (
                            <div className="preview-row">
                              <img src={a.layout_request.library.image_url} alt="Selected layout" className="preview-image" />
                            </div>
                          )}
                          {/* Show file link for the selected library layout so architect can view/download */}
                          {a.layout_request.library?.file_url && (
                            <div className="preview-row" style={{marginTop:8, display:'flex', gap:8}}>
                              <a className="btn btn-secondary" href={a.layout_request.library.file_url} target="_blank" rel="noopener noreferrer">
                                View Layout File
                              </a>
                              <a className="btn" href={a.layout_request.library.file_url} download>
                                Download
                              </a>
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                  );
                })()}
              </div>
              <div className="item-actions">
                <button className="btn btn-secondary" onClick={load}>Refresh</button>
                <button
                  className="btn"
                  onClick={() => setExpandedAssignments(s => ({...s, [a.assignment_id]: !s[a.assignment_id]}))}
                >
                  {expandedAssignments[a.assignment_id] ? 'Hide Details' : 'Details'}
                </button>
                {a.assignment_status === 'sent' && (
                  <>
                    <button className="btn btn-success" onClick={() => respond(a.assignment_id, 'accept')}>Accept</button>
                    <button className="btn btn-danger" onClick={() => respond(a.assignment_id, 'reject')}>Reject</button>
                  </>
                )}
                <div style={{display:'flex', gap:8}}>
                  <button
                    className="btn btn-primary"
                    disabled={a.assignment_status !== 'accepted'}
                    title={a.assignment_status !== 'accepted' ? 'Accept the request to create a design' : undefined}
                    onClick={() => onCreateFromAssigned?.(a.layout_request.id)}
                  >
                    Create Design
                  </button>
                  <button className="btn btn-secondary" onClick={() => {
                    respond(a.assignment_id, 'reject');
                  }}>
                    Remove
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

// Request Item Component
const RequestItem = ({ request, onCreateDesign }) => {
  const [showDetails, setShowDetails] = useState(false);
  const [showImageModal, setShowImageModal] = useState(false);
  const [selectedImage, setSelectedImage] = useState(null);
  const [imageGallery, setImageGallery] = useState([]);
  
  // Parse requirements if it's a JSON string
  const requirements = typeof request.requirements === 'string' 
    ? JSON.parse(request.requirements || '{}') 
    : request.requirements || {};
  
  // Handle both JSON strings and arrays (for backward compatibility)
  const siteImages = request.site_images ? (
    Array.isArray(request.site_images) 
      ? request.site_images 
      : (() => {
    try { return JSON.parse(request.site_images); } 
    catch (e) { return []; }
        })()
  ) : [];
  
  const referenceImages = request.reference_images ? (
    Array.isArray(request.reference_images) 
      ? request.reference_images 
      : (() => {
    try { return JSON.parse(request.reference_images); } 
    catch (e) { return []; }
        })()
  ) : [];
  
  const roomImages = request.room_images ? (
    typeof request.room_images === 'object' && !Array.isArray(request.room_images)
      ? request.room_images 
      : (() => {
    try { return JSON.parse(request.room_images); } 
    catch (e) { return {}; }
        })()
  ) : {};
  
  const floorRooms = request.floor_rooms ? (
    Array.isArray(request.floor_rooms)
      ? request.floor_rooms 
      : (() => {
    try { return JSON.parse(request.floor_rooms); } 
          catch (e) { return []; }
        })()
  ) : [];
  
  // Count total images
  const totalRoomImages = Object.values(roomImages || {}).reduce((acc, floorData) => {
    return acc + Object.values(floorData || {}).reduce((floorAcc, roomTypeImages) => {
      return floorAcc + (Array.isArray(roomTypeImages) ? roomTypeImages.length : 0);
    }, 0);
  }, 0);
  
  const totalImages = (siteImages?.length || 0) + (referenceImages?.length || 0) + totalRoomImages;
  

  // Function to open image gallery
  const openImageGallery = () => {
    const allImages = [];
    
    // Add site images
    if (siteImages && siteImages.length > 0) {
      siteImages.forEach(img => {
        allImages.push({...img, category: 'Site Images'});
      });
    }
    
    // Add reference images
    if (referenceImages && referenceImages.length > 0) {
      referenceImages.forEach(img => {
        allImages.push({...img, category: 'Reference Images'});
      });
    }
    
    // Add room images (nested structure: floor -> room_type -> images)
    if (roomImages && Object.keys(roomImages).length > 0) {
      Object.entries(roomImages).forEach(([floorKey, floorData]) => {
        Object.entries(floorData || {}).forEach(([roomType, images]) => {
          if (Array.isArray(images)) {
            images.forEach(img => {
              allImages.push({...img, category: `${roomType.replace('_', ' ').toUpperCase()} Room (${floorKey.replace('floor', 'Floor ')})`});
            });
          }
        });
      });
    }
    
    setImageGallery(allImages);
    if (allImages.length > 0) {
      setSelectedImage(allImages[0]);
      setShowImageModal(true);
    }
  };
  
  return (
    <>
    <div className="list-item" style={{ marginBottom: '20px' }}>
    <div className="item-icon">📋</div>
      <div className="item-content" style={{ flex: 1 }}>
      <h4 className="item-title">{request.client_name} - {request.plot_size} sq ft</h4>
      <p className="item-subtitle">Budget: {request.budget_range}</p>
      <p className="item-meta">
        Location: {request.location || 'Not specified'} • 
        Submitted: {new Date(request.created_at).toLocaleString()}
        {totalImages > 0 && (
          <span style={{ marginLeft: '10px', color: '#3b82f6', fontWeight: '600' }}>
            📷 {totalImages} image{totalImages !== 1 ? 's' : ''}
          </span>
        )}
      </p>
        {showDetails && (
          <>
            {/* Neat Details Card with chips */}
            <div className="details-card" style={{ marginTop: '12px' }}>
              <div className="details-header"><strong>Requirements</strong></div>
              <div className="details-grid">
                <div>
                  <h5>Site & Budget</h5>
                  <div className="chips">
                    <span className="chip"><strong>Plot:</strong> {request.plot_size || '—'}</span>
                    <span className="chip"><strong>Budget:</strong> {request.budget_range || '—'}</span>
                    <span className="chip"><strong>Location:</strong> {request.location || '—'}</span>
                    <span className="chip"><strong>Timeline:</strong> {request.timeline || '—'}</span>
                    {(requirements.plot_shape || request.plot_shape) && (
                      <span className="chip"><strong>Plot shape:</strong> {requirements.plot_shape || request.plot_shape}</span>
                    )}
                    {(requirements.topography || request.topography) && (
                      <span className="chip"><strong>Topography:</strong> {requirements.topography || request.topography}</span>
                    )}
                  </div>
                </div>
                <div>
                  <h5>Preferences</h5>
                  <div className="chips">
                    <span className="chip"><strong>Type:</strong> {request.layout_type || 'custom'}</span>
                    {(request.preferred_style || requirements.aesthetic) && (
                      <span className="chip"><strong>Style:</strong> {request.preferred_style || requirements.aesthetic}</span>
                    )}
                    {request.orientation && (
                      <span className="chip"><strong>Orientation:</strong> {request.orientation}</span>
                    )}
                    {request.num_floors && (
                      <span className="chip"><strong>Floors:</strong> {request.num_floors}</span>
                    )}
                    {requirements.family_needs && (
                      <span className="chip"><strong>Family needs:</strong> {Array.isArray(requirements.family_needs) ? requirements.family_needs.join(', ') : requirements.family_needs}</span>
                    )}
                  </div>
                </div>
                <div className="span-2">
                  <h5>Notes</h5>
                  <p className="item-description">{requirements.notes || '—'}</p>
                  {!requirements.notes && (requirements.raw || request.requirements) && (
                    <div className="muted" style={{ fontSize: 12 }}>Original notes available in raw request.</div>
                  )}
                </div>
              </div>
            </div>

            {/* Basic Details */}
            <div style={{ marginTop: '12px', padding: '12px', background: '#f8fafc', borderRadius: '8px', border: '1px solid #e5e7eb' }}>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '8px', marginBottom: '12px' }}>
                <div><strong>Plot Shape:</strong> {requirements.plot_shape || 'Not specified'}</div>
                <div><strong>Topography:</strong> {requirements.topography || 'Not specified'}</div>
                <div><strong>Floors:</strong> {request.num_floors || requirements.num_floors || 'Not specified'}</div>
                <div><strong>Style:</strong> {request.preferred_style || requirements.aesthetic || 'Not specified'}</div>
                <div><strong>Timeline:</strong> {request.timeline || 'Not specified'}</div>
                <div><strong>Orientation:</strong> {request.orientation || 'Not specified'}</div>
              </div>
              {requirements.family_needs && (
                <div style={{ marginBottom: '8px' }}>
                  <strong>Family Needs:</strong> {Array.isArray(requirements.family_needs) ? requirements.family_needs.join(', ') : requirements.family_needs}
                </div>
              )}
              {requirements.rooms && (
                <div style={{ marginBottom: '8px' }}>
                  <strong>Room Requirements:</strong> {Array.isArray(requirements.rooms) 
                    ? (() => {
                        // Count occurrences of each room type
                        const roomCounts = {};
                        requirements.rooms.forEach(room => {
                          roomCounts[room] = (roomCounts[room] || 0) + 1;
                        });
                        return Object.entries(roomCounts)
                          .map(([room, count]) => `${room.replace(/_/g, ' ')}: ${count}`)
                          .join(', ');
                      })()
                    : requirements.rooms}
                </div>
              )}
              {request.site_considerations && (
                <div style={{ marginBottom: '8px' }}>
                  <strong>Site Considerations:</strong> {request.site_considerations}
                </div>
              )}
              {request.material_preferences && (
                <div style={{ marginBottom: '8px' }}>
                  <strong>Material Preferences:</strong> {request.material_preferences}
                </div>
              )}
              {request.budget_allocation && (
                <div style={{ marginBottom: '8px' }}>
                  <strong>Budget Allocation:</strong> {request.budget_allocation}
                </div>
              )}
              {requirements.notes && (
                <div style={{ marginBottom: '8px' }}>
                  <strong>Additional Notes:</strong> {requirements.notes}
                </div>
              )}
            </div>

            {/* Site Images */}
            {siteImages.length > 0 && (
              <div style={{ marginTop: '12px' }}>
                <h5 style={{ margin: '0 0 8px 0', fontSize: '14px', fontWeight: '600' }}>
                  Site Images: 
                  <button 
                    onClick={openImageGallery}
                    style={{ 
                      marginLeft: '10px', 
                      padding: '4px 8px', 
                      fontSize: '12px', 
                      backgroundColor: '#3b82f6', 
                      color: 'white', 
                      border: 'none', 
                      borderRadius: '4px', 
                      cursor: 'pointer' 
                    }}
                  >
                    View All
                  </button>
                </h5>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(120px, 1fr))', gap: '8px' }}>
                  {siteImages.slice(0, 3).map((image, index) => (
                    <div 
                      key={index} 
                      style={{ textAlign: 'center', cursor: 'pointer' }}
                      onClick={() => {
                        setSelectedImage({...image, category: 'Site Images'});
                        setImageGallery([{...image, category: 'Site Images'}]);
                        setShowImageModal(true);
                      }}
                    >
                      <img 
                        src={image.url} 
                        alt={image.name}
                        onError={(e) => {
                          e.target.style.backgroundColor = '#f3f4f6';
                          e.target.style.display = 'flex';
                          e.target.style.alignItems = 'center';
                          e.target.style.justifyContent = 'center';
                          e.target.innerHTML = '❌';
                          e.target.title = 'Failed to load image';
                        }}
                        onLoad={() => {/* Image loaded successfully */}}
                        style={{ 
                          width: '100%', 
                          height: '80px', 
                          objectFit: 'cover', 
                          borderRadius: '6px',
                          border: '1px solid #e5e7eb'
                        }}
                      />
                      <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '4px' }}>
                        {image.name.length > 15 ? image.name.substring(0, 15) + '...' : image.name}
                      </div>
                    </div>
                  ))}
                  {siteImages.length > 3 && (
                    <div 
                      style={{ 
                        display: 'flex', 
                        alignItems: 'center', 
                        justifyContent: 'center', 
                        height: '80px', 
                        backgroundColor: '#f3f4f6', 
                        borderRadius: '6px', 
                        cursor: 'pointer',
                        border: '1px solid #e5e7eb'
                      }}
                      onClick={openImageGallery}
                    >
                      <div style={{ textAlign: 'center', color: '#666' }}>
                        <div style={{ fontSize: '24px' }}>+</div>
                        <div style={{ fontSize: '12px' }}>{siteImages.length - 3} more</div>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* Reference Images */}
            {referenceImages.length > 0 && (
              <div style={{ marginTop: '12px' }}>
                <h5 style={{ margin: '0 0 8px 0', fontSize: '14px', fontWeight: '600' }}>Reference Images:</h5>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(120px, 1fr))', gap: '8px' }}>
                  {referenceImages.map((image, index) => (
                    <div key={index} style={{ textAlign: 'center' }}>
                      <img 
                        src={image.url} 
                        alt={image.name}
                        onError={(e) => {
                          e.target.style.backgroundColor = '#f3f4f6';
                          e.target.style.display = 'flex';
                          e.target.style.alignItems = 'center';
                          e.target.style.justifyContent = 'center';
                          e.target.innerHTML = '❌';
                          e.target.title = 'Failed to load image';
                        }}
                        onLoad={() => {/* Image loaded successfully */}}
                        style={{ 
                          width: '100%', 
                          height: '80px', 
                          objectFit: 'cover', 
                          borderRadius: '6px',
                          border: '1px solid #e5e7eb'
                        }}
                      />
                      <div style={{ fontSize: '12px', color: '#6b7280', marginTop: '4px' }}>
                        {image.name.length > 15 ? image.name.substring(0, 15) + '...' : image.name}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Floor-wise Room Planning */}
            {Object.keys(floorRooms).length > 0 && (
              <div style={{ marginTop: '12px' }}>
                <h5 style={{ margin: '0 0 8px 0', fontSize: '14px', fontWeight: '600' }}>Floor-wise Room Planning:</h5>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '8px' }}>
                  {Object.entries(floorRooms).map(([floor, rooms]) => (
                    <div key={floor} style={{ padding: '8px', background: '#f1f5f9', borderRadius: '6px', border: '1px solid #e2e8f0' }}>
                      <strong>{floor.replace('floor', 'Floor ')}:</strong>
                      <div style={{ fontSize: '12px', marginTop: '4px' }}>
                        {Object.entries(rooms).map(([roomType, count]) => (
                          <div key={roomType}>{roomType}: {count}</div>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}


            {/* Room-specific Images */}
            {Object.keys(roomImages).length > 0 && (
              <div style={{ marginTop: '12px' }}>
                <h5 style={{ margin: '0 0 8px 0', fontSize: '14px', fontWeight: '600' }}>
                  Room-specific Images:
                  <button 
                    onClick={openImageGallery}
                    style={{ 
                      marginLeft: '10px', 
                      padding: '4px 8px', 
                      fontSize: '12px', 
                      backgroundColor: '#3b82f6', 
                      color: 'white', 
                      border: 'none', 
                      borderRadius: '4px', 
                      cursor: 'pointer' 
                    }}
                  >
                    View All
                  </button>
                </h5>
                {Object.entries(roomImages).map(([floorKey, floorData]) => (
                  <div key={floorKey} style={{ marginBottom: '16px' }}>
                    <div style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px', color: '#374151' }}>
                      🏢 {floorKey.replace('floor', 'Floor ')}
                    </div>
                    {Object.entries(floorData || {}).map(([roomType, images]) => (
                      <div key={`${floorKey}-${roomType}`} style={{ marginBottom: '12px', marginLeft: '16px' }}>
                        <strong style={{ fontSize: '13px' }}>{roomType.replace('_', ' ').toUpperCase()}:</strong>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(100px, 1fr))', gap: '6px', marginTop: '4px' }}>
                          {Array.isArray(images) && images.slice(0, 3).map((image, index) => (
                        <div 
                          key={index} 
                          style={{ textAlign: 'center', cursor: 'pointer' }}
                          onClick={() => {
                            setSelectedImage({...image, category: `${roomType.replace('_', ' ').toUpperCase()} Room (${floorKey.replace('floor', 'Floor ')})`});
                            const roomImagesList = [];
                            Object.entries(roomImages).forEach(([fk, fd]) => {
                              Object.entries(fd || {}).forEach(([rt, imgs]) => {
                                if (Array.isArray(imgs)) {
                                  imgs.forEach(img => {
                                    roomImagesList.push({...img, category: `${rt.replace('_', ' ').toUpperCase()} Room (${fk.replace('floor', 'Floor ')})`});
                                  });
                                }
                              });
                            });
                            setImageGallery(roomImagesList);
                            setShowImageModal(true);
                          }}
                        >
                          <img 
                            src={image.url} 
                            alt={image.name}
                            onError={(e) => {
                              e.target.style.backgroundColor = '#f3f4f6';
                              e.target.style.display = 'flex';
                              e.target.style.alignItems = 'center';
                              e.target.style.justifyContent = 'center';
                              e.target.innerHTML = '❌';
                              e.target.title = 'Failed to load image';
                            }}
                            onLoad={() => {/* Image loaded successfully */}}
                            style={{ 
                              width: '100%', 
                              height: '60px', 
                              objectFit: 'cover', 
                              borderRadius: '4px',
                              border: '1px solid #e5e7eb'
                            }}
                          />
                          <div style={{ fontSize: '11px', color: '#6b7280', marginTop: '2px' }}>
                            {image.name.length > 12 ? image.name.substring(0, 12) + '...' : image.name}
                          </div>
                        </div>
                      ))}
                      {Array.isArray(images) && images.length > 3 && (
                        <div 
                          style={{ 
                            display: 'flex', 
                            alignItems: 'center', 
                            justifyContent: 'center', 
                            height: '60px', 
                            backgroundColor: '#f3f4f6', 
                            borderRadius: '4px', 
                            cursor: 'pointer',
                            border: '1px solid #e5e7eb'
                          }}
                          onClick={openImageGallery}
                        >
                          <div style={{ textAlign: 'center', color: '#666' }}>
                            <div style={{ fontSize: '20px' }}>+</div>
                            <div style={{ fontSize: '10px' }}>{images.length - 3} more</div>
                          </div>
                        </div>
                      )}
                        </div>
                      </div>
                    ))}
                  </div>
                ))}
              </div>
            )}

            {/* Development Laws */}
            {requirements.development_laws && (
              <div style={{ marginTop: '12px', padding: '8px', background: '#fef3c7', borderRadius: '6px', border: '1px solid #f59e0b' }}>
                <strong>Development Laws/Restrictions:</strong> {requirements.development_laws}
              </div>
            )}

            {/* Contact Information */}
            <div style={{ marginTop: '12px', padding: '8px', background: '#ecfdf5', borderRadius: '6px', border: '1px solid #10b981' }}>
              <strong>Client Contact:</strong>
              <div style={{ fontSize: '13px', marginTop: '4px' }}>
                <div>Name: {request.first_name} {request.last_name}</div>
                <div>Email: {request.email}</div>
                {request.phone && <div>Phone: {request.phone}</div>}
                {request.address && <div>Address: {request.address}</div>}
                {request.city && <div>City: {request.city}, {request.state}</div>}
              </div>
            </div>
          </>
        )}
    </div>
    <div className="item-actions">
        <button className="btn" onClick={() => setShowDetails(s => !s)}>
          {showDetails ? 'Hide Details' : 'Details'}
        </button>
        {totalImages > 0 && (
          <button className="btn btn-secondary" onClick={openImageGallery} style={{ marginRight: '8px' }}>
            📷 View Images ({totalImages})
          </button>
        )}
      <button className="btn btn-primary" onClick={onCreateDesign}>
        Create Design
      </button>
    </div>
  </div>

  {/* Image Modal */}
  {showImageModal && (
    <div style={{
      position: 'fixed',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      backgroundColor: 'rgba(0, 0, 0, 0.9)',
      zIndex: 1000,
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center'
    }}>
      <div style={{
        backgroundColor: 'white',
        borderRadius: '12px',
        padding: '20px',
        maxWidth: '90vw',
        maxHeight: '90vh',
        overflow: 'auto',
        position: 'relative'
      }}>
        {/* Close button */}
        <button
          onClick={() => setShowImageModal(false)}
          style={{
            position: 'absolute',
            top: '10px',
            right: '15px',
            background: 'none',
            border: 'none',
            fontSize: '24px',
            cursor: 'pointer',
            color: '#666',
            zIndex: 1001
          }}
        >
          ×
        </button>

        {/* Modal header */}
        <div style={{ marginBottom: '20px', paddingRight: '30px' }}>
          <h3 style={{ margin: '0 0 10px 0', fontSize: '18px', fontWeight: '600' }}>
            Project Images - {request.client_name}
          </h3>
          <p style={{ margin: 0, color: '#666', fontSize: '14px' }}>
            {imageGallery.length} image{imageGallery.length !== 1 ? 's' : ''} available
          </p>
        </div>

        {/* Image gallery */}
        <div style={{ display: 'flex', gap: '20px' }}>
          {/* Thumbnail sidebar */}
          <div style={{
            width: '200px',
            maxHeight: '500px',
            overflowY: 'auto',
            borderRight: '1px solid #e5e7eb',
            paddingRight: '15px'
          }}>
            <h4 style={{ fontSize: '14px', fontWeight: '600', marginBottom: '10px' }}>All Images</h4>
            {imageGallery.map((image, index) => (
              <div
                key={index}
                onClick={() => setSelectedImage(image)}
                style={{
                  cursor: 'pointer',
                  marginBottom: '10px',
                  padding: '8px',
                  borderRadius: '6px',
                  border: selectedImage === image ? '2px solid #3b82f6' : '1px solid #e5e7eb',
                  backgroundColor: selectedImage === image ? '#eff6ff' : 'white'
                }}
              >
                <img
                  src={image.url}
                  alt={image.name}
                  style={{
                    width: '100%',
                    height: '80px',
                    objectFit: 'cover',
                    borderRadius: '4px',
                    marginBottom: '4px'
                  }}
                  onError={(e) => {
                    e.target.style.backgroundColor = '#f3f4f6';
                    e.target.style.display = 'flex';
                    e.target.style.alignItems = 'center';
                    e.target.style.justifyContent = 'center';
                    e.target.innerHTML = '❌';
                  }}
                />
                <div style={{ fontSize: '11px', fontWeight: '600', color: '#3b82f6' }}>
                  {image.category}
                </div>
                <div style={{ fontSize: '10px', color: '#666' }}>
                  {image.name.length > 20 ? image.name.substring(0, 20) + '...' : image.name}
                </div>
              </div>
            ))}
          </div>

          {/* Main image display */}
          <div style={{ flex: 1, minWidth: '400px' }}>
            {selectedImage && (
              <>
                <div style={{ marginBottom: '15px' }}>
                  <h4 style={{ margin: '0 0 5px 0', fontSize: '16px', fontWeight: '600' }}>
                    {selectedImage.category}
                  </h4>
                  <p style={{ margin: 0, fontSize: '14px', color: '#666' }}>
                    {selectedImage.name}
                  </p>
                </div>
                <div style={{ textAlign: 'center' }}>
                  <img
                    src={selectedImage.url}
                    alt={selectedImage.name}
                    style={{
                      maxWidth: '100%',
                      maxHeight: '400px',
                      objectFit: 'contain',
                      borderRadius: '8px',
                      border: '1px solid #e5e7eb'
                    }}
                    onError={(e) => {
                      e.target.style.backgroundColor = '#f3f4f6';
                      e.target.style.height = '200px';
                      e.target.style.display = 'flex';
                      e.target.style.alignItems = 'center';
                      e.target.style.justifyContent = 'center';
                      e.target.innerHTML = '<div style="text-align: center; color: #666;"><div style="font-size: 48px;">❌</div><div>Image failed to load</div><div style="font-size: 12px; margin-top: 5px;">URL: ' + selectedImage.url + '</div></div>';
                    }}
                  />
                </div>
                <div style={{ marginTop: '15px', textAlign: 'center' }}>
                  <a
                    href={selectedImage.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    style={{
                      display: 'inline-block',
                      padding: '8px 16px',
                      backgroundColor: '#3b82f6',
                      color: 'white',
                      textDecoration: 'none',
                      borderRadius: '6px',
                      fontSize: '14px'
                    }}
                  >
                    Open in New Tab
                  </a>
                </div>
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  )}

  {/* Image Modal */}
  {imageModal.open && (
    <div style={{
      position: 'fixed',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      backgroundColor: 'rgba(0, 0, 0, 0.9)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 9999,
      animation: 'fadeIn 0.3s ease-out'
    }}
    onClick={() => setImageModal({ open: false, image: null, title: '' })}
    >
      <div style={{
        position: 'relative',
        maxWidth: '90vw',
        maxHeight: '90vh',
        backgroundColor: 'white',
        borderRadius: '16px',
        overflow: 'hidden',
        boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
        animation: 'slideIn 0.3s ease-out'
      }}
      onClick={(e) => e.stopPropagation()}
      >
        {/* Modal Header */}
        <div style={{
          padding: '20px',
          backgroundColor: '#f8fafc',
          borderBottom: '1px solid #e2e8f0',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between'
        }}>
          <h3 style={{
            margin: 0,
            color: '#1f2937',
            fontSize: '18px',
            fontWeight: '600'
          }}>
            {imageModal.title}
          </h3>
          <button
            onClick={() => setImageModal({ open: false, image: null, title: '' })}
            style={{
              background: 'none',
              border: 'none',
              fontSize: '24px',
              cursor: 'pointer',
              color: '#6b7280',
              padding: '4px',
              borderRadius: '6px',
              transition: 'all 0.2s ease'
            }}
            onMouseEnter={(e) => {
              e.target.style.backgroundColor = '#e5e7eb';
              e.target.style.color = '#374151';
            }}
            onMouseLeave={(e) => {
              e.target.style.backgroundColor = 'transparent';
              e.target.style.color = '#6b7280';
            }}
          >
            ✕
          </button>
        </div>

        {/* Modal Body with Image */}
        <div style={{
          padding: '20px',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          backgroundColor: '#f9fafb'
        }}>
          <img
            src={imageModal.image}
            alt={imageModal.title}
            style={{
              maxWidth: '100%',
              maxHeight: '70vh',
              objectFit: 'contain',
              borderRadius: '8px',
              boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
              cursor: 'zoom-in'
            }}
            onClick={(e) => {
              if (e.target.style.transform === 'scale(2)') {
                e.target.style.transform = 'scale(1)';
                e.target.style.cursor = 'zoom-in';
              } else {
                e.target.style.transform = 'scale(2)';
                e.target.style.cursor = 'zoom-out';
              }
            }}
            onError={(e) => {
              e.target.style.display = 'none';
              // Add error message
              const errorDiv = document.createElement('div');
              errorDiv.style.cssText = 'text-align: center; color: #ef4444; padding: 40px; font-size: 16px;';
              errorDiv.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 16px;">❌</div>
                <div>Failed to load image</div>
                <div style="font-size: 12px; margin-top: 8px; color: #6b7280;">${imageModal.image}</div>
              `;
              e.target.parentNode.appendChild(errorDiv);
            }}
          />
        </div>

        {/* Modal Footer */}
        <div style={{
          padding: '16px 20px',
          backgroundColor: '#f8fafc',
          borderTop: '1px solid #e2e8f0',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between'
        }}>
          <div style={{
            fontSize: '14px',
            color: '#6b7280'
          }}>
            Click image to zoom • Press ESC or click outside to close
          </div>
          <a
            href={imageModal.image}
            target="_blank"
            rel="noopener noreferrer"
            style={{
              padding: '8px 16px',
              backgroundColor: '#3b82f6',
              color: 'white',
              textDecoration: 'none',
              borderRadius: '8px',
              fontSize: '14px',
              fontWeight: '500',
              transition: 'all 0.2s ease'
            }}
            onMouseEnter={(e) => {
              e.target.style.backgroundColor = '#2563eb';
            }}
            onMouseLeave={(e) => {
              e.target.style.backgroundColor = '#3b82f6';
            }}
          >
            Open in New Tab
          </a>
        </div>
      </div>
    </div>
  )}
  </>
);
};

// Design Item Component
const DesignItem = ({ design, user }) => (
  <div className="list-item">
    <div className="item-icon">
      {design.status === 'approved' ? '✅' : 
       design.status === 'rejected' ? '❌' : '🎨'}
    </div>
    <div className="item-content">
      <h4 className="item-title">{design.design_title}</h4>
      <p className="item-subtitle">Client: {design.client_name}</p>
      <p className="item-meta">
        Plot Size: {design.plot_size} sq ft • Budget: {design.budget_range}
      </p>
      <p className="item-meta">
        Submitted: {new Date(design.created_at).toLocaleDateString()}
      </p>
      <div className="details-panel" style={{ marginTop:8, padding:10, border:'1px solid #e5e7eb', borderRadius:8, background:'#fafafa' }}>
        <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:8}}>
          <div><strong>Architect:</strong> {(user?.first_name || '') + ' ' + (user?.last_name || '')}</div>
          <div><strong>Email:</strong> {user?.email || '-'}</div>
          {design.layout_request_id ? (
            <div><strong>Request ID:</strong> {design.layout_request_id}</div>
          ) : (
            <div><strong>Request:</strong> Direct upload</div>
          )}
          <div><strong>Status:</strong> {design.status}</div>
        </div>
      </div>
      {design.description && (
        <div style={{marginTop:8}}>
          <NeatJsonCard raw={design.description} title="Description" />
        </div>
      )}
    </div>
    <div className="item-actions" style={{display:'flex', alignItems:'center', gap:8}}>
      <span className={`status-badge ${badgeClass(design.status)}`}>
        {formatStatus(design.status)}
      </span>
      {design.status !== 'finalized' && (
        <button className="btn btn-secondary" onClick={() => finalizeDesign(design.id)}>
          Finalize
        </button>
      )}
      <button className="btn btn-secondary">
        View Files
      </button>
    </div>
  </div>
);

export default ArchitectDashboard;