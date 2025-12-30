import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
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
import '../styles/TechnicalDetailsForm.css';
import InfoPopup from './InfoPopup';
import ProjectDetailsCard from './ProjectDetailsCard';


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
    title: '', layout_type: '', bedrooms: '', bathrooms: '', area: '', price_range: '', description: '', image: null, design_file: null, technical_details: {}
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
      })();
    });
  }, []);

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
        fetchMyProfile()
      ]);
      toast.success('Dashboard refreshed successfully');
    } catch (error) {
      console.error('Error refreshing dashboard:', error);
      toast.error('Failed to refresh dashboard');
    } finally {
      setLoading(false);
    }
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
    ['title','layout_type','bedrooms','bathrooms','area','price_range','description','status'].forEach(k=>{
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
      } else {
        setError(json.message || 'Failed to update layout');
      }
    } catch (e) {
      setError('Error updating layout');
    }
  };

  const toggleLayoutStatus = async (item) => {
    const target = item.status === 'active' ? 'inactive' : 'active';
    try {
      const res = await fetch('/buildhub/backend/api/architect/update_layout_library_item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: item.id, status: target })
      });
      const json = await res.json();
      if (json.success) {
        setLibraryLayouts(prev => prev.map(x => x.id === item.id ? { ...x, status: target } : x));
      } else {
        setError(json.message || 'Failed to change status');
      }
    } catch (e) {
      setError('Error changing status');
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
                          <div style={{fontSize:'0.9rem', color:'#666', marginLeft:'10px'}}>
                            {request.requirements_parsed.family_needs && (
                              <p>• Family Needs: {Array.isArray(request.requirements_parsed.family_needs) 
                                ? request.requirements_parsed.family_needs.join(', ') 
                                : request.requirements_parsed.family_needs}</p>
                            )}
                            {request.requirements_parsed.rooms && (
                              <p>• Rooms: {Array.isArray(request.requirements_parsed.rooms) 
                                ? request.requirements_parsed.rooms.join(', ') 
                                : request.requirements_parsed.rooms}</p>
                            )}
                            {request.requirements_parsed.plot_shape && (
                              <p>• Plot Shape: {request.requirements_parsed.plot_shape}</p>
                            )}
                            {request.requirements_parsed.topography && (
                              <p>• Topography: {request.requirements_parsed.topography}</p>
                            )}
                            {request.requirements_parsed.notes && (
                              <p>• Notes: {request.requirements_parsed.notes}</p>
                            )}
                          </div>
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
                          setUploadData({...uploadData, request_id: request.id});
                          setShowUploadForm(true);
                        }}
                      >
                        Upload Design
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
                  </div>
                  {expandedAssignments[request.id] && (
                    <ProjectDetailsCard 
                      request={request}
                      onUploadDesign={() => {
                        // Handle upload design
                        console.log('Upload design for request:', request.id);
                      }}
                      onRemoveAssignment={() => {
                        // Handle remove assignment
                        console.log('Remove assignment for request:', request.id);
                      }}
                    />
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
          {pendingRequests.length > 0 ? (
            <div className="request-list">
              {pendingRequests.map((request) => (
                <div key={request.id} className="list-item">
                  <div className="item-image">
                    {request.site_images && request.site_images.length > 0 ? (
                      <img 
                        src={request.site_images[0].url || request.site_images[0]} 
                        alt="Site" 
                        style={{width: 48, height: 48, objectFit: 'cover', borderRadius: 6}}
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
                          <div style={{fontSize:'0.9rem', color:'#666', marginLeft:'10px'}}>
                            {request.requirements_parsed.family_needs && (
                              <p>• Family Needs: {Array.isArray(request.requirements_parsed.family_needs) 
                                ? request.requirements_parsed.family_needs.join(', ') 
                                : request.requirements_parsed.family_needs}</p>
                            )}
                            {request.requirements_parsed.rooms && (
                              <p>• Rooms: {Array.isArray(request.requirements_parsed.rooms) 
                                ? request.requirements_parsed.rooms.join(', ') 
                                : request.requirements_parsed.rooms}</p>
                            )}
                            {request.requirements_parsed.plot_shape && (
                              <p>• Plot Shape: {request.requirements_parsed.plot_shape}</p>
                            )}
                            {request.requirements_parsed.topography && (
                              <p>• Topography: {request.requirements_parsed.topography}</p>
                            )}
                            {request.requirements_parsed.notes && (
                              <p>• Notes: {request.requirements_parsed.notes}</p>
                            )}
                          </div>
                        </div>
                      )}
                    </div>
                    <p className="item-meta">
                      Assigned: {new Date(request.assigned_at).toLocaleDateString()} • 
                      Contact: {request.homeowner_email}
                    </p>
                  </div>
                  <div className="item-actions">
                    <span className={`status-badge ${request.status}`}>
                      {formatStatus(request.status)}
                    </span>
                    <div className="action-buttons">
                      <button 
                        className="btn btn-primary"
                        onClick={() => {
                          // Handle accept assignment
                          console.log('Accept assignment:', request.id);
                        }}
                      >
                        Accept
                      </button>
                      <button 
                        className="btn btn-danger btn-sm"
                        onClick={() => {
                          // Handle reject assignment
                          console.log('Reject assignment:', request.id);
                        }}
                        title="Reject this assignment"
                      >
                        Reject
                      </button>
                    </div>
                    <button 
                      className="btn btn-secondary btn-sm"
                      onClick={() => {
                        setExpandedAssignments(prev => ({
                          ...prev,
                          [request.id]: !prev[request.id]
                        }));
                      }}
                    >
                      {expandedAssignments[request.id] ? 'Hide Details' : 'View Images & Details'}
                    </button>
                  </div>
                  {expandedAssignments[request.id] && (
                    <ProjectDetailsCard 
                      request={request}
                      onUploadDesign={() => {
                        // Handle upload design
                        console.log('Upload design for request:', request.id);
                      }}
                      onRemoveAssignment={() => {
                        // Handle remove assignment
                        console.log('Remove assignment for request:', request.id);
                      }}
                    />
                  )}
                </div>
              ))}
            </div>
          ) : (
            <div className="empty-state">
              <p>No pending assignments at the moment.</p>
              <button 
                className="btn btn-primary"
                onClick={refreshDashboard}
              >
                Refresh
              </button>
            </div>
          )}
        </div>
      </div>

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
            Upload New Design
          </button>
        </div>
      </div>
      <div className="section-card">
        <div className="section-content">
          {myDesigns.length > 0 ? (
            <div className="designs-grid">
              {myDesigns.map((design) => (
                <div key={design.id} className="design-card">
                  <div className="design-image">
                    {design.image_url ? (
                      <img 
                        src={design.image_url} 
                        alt={design.title}
                        onClick={() => openPreview(design)}
                      />
                    ) : (
                      <div className="no-image">No Preview</div>
                    )}
                  </div>
                  <div className="design-content">
                    <h3>{design.title}</h3>
                    <p className="design-description">{design.description}</p>
                    <div className="design-meta">
                      <span className={`status-badge ${design.status}`}>
                        {formatStatus(design.status)}
                      </span>
                      <span className="design-date">
                        {new Date(design.created_at).toLocaleDateString()}
                      </span>
                    </div>
                    {design.technical_details && (
                      <div className="technical-details-section" style={{marginTop:16}}>
                        <TechnicalDetailsDisplay technicalDetails={design.technical_details} />
                      </div>
                    )}

                    <div style={{display:'flex', gap:8, flexWrap:'wrap', margin:'6px 0'}}>
                      {Array.isArray(design.files) && design.files.length > 0 ? (
                        design.files.map((file, index) => (
                          <a key={index} className="btn btn-secondary" href={file.url} target="_blank" rel="noreferrer">
                            📎 {file.name || `File ${index + 1}`}
                          </a>
                        ))
                      ) : (
                        <span className="muted">No files attached</span>
                      )}
                    </div>

                    <div className="review-section" style={{marginTop:8}}>
                      <strong>Reviews:</strong>
                      {design.reviews && design.reviews.length > 0 ? (
                        design.reviews.map((review, index) => (
                          <div key={index} className="review-item" style={{marginTop:4, padding:8, background:'#f8f9fa', borderRadius:4}}>
                            <div style={{fontWeight:'bold'}}>{review.homeowner_name}</div>
                            <div style={{fontSize:'0.9em', color:'#666'}}>{review.comment}</div>
                            <div style={{fontSize:'0.8em', color:'#999'}}>Rating: {review.rating}/5</div>
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
          ) : (
            <div className="empty-state">
              <p>No designs uploaded yet.</p>
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
  );

  const renderLibrary = () => (
    <div style={{
      height: '100vh', 
      overflowY: 'auto', 
      paddingRight: '8px',
      scrollbarWidth: 'thin',
      scrollbarColor: '#cbd5e0 transparent'
    }}>
      <div className="main-header">
        <div className="header-content">
          <div>
            <h1>My Library</h1>
            <p>Manage your design templates and materials</p>
          </div>
          <button 
            className="btn btn-primary"
            onClick={() => setShowLibraryForm(true)}
          >
            Add to Library
          </button>
        </div>
      </div>
      <div className="section-card">
        <div className="section-content">
          {myLibrary.length > 0 ? (
            <div className="library-grid">
              {myLibrary.map((item) => (
                <div key={item.id} className="library-item">
                  <div className="item-image">
                    {item.image_url ? (
                      <img 
                        src={item.image_url} 
                        alt={item.title}
                        onClick={() => openPreview(item)}
                      />
                    ) : (
                      <div className="no-image">No Preview</div>
                    )}
                  </div>
                  <div className="item-content">
                    <h3>{item.title}</h3>
                    <p className="item-description">{item.description}</p>
                    <div className="item-meta">
                      <span className="item-type">{item.type}</span>
                      <span className="item-date">
                        {new Date(item.created_at).toLocaleDateString()}
                      </span>
                    </div>
                    {item.technical_details && (
                      <div className="technical-details-section" style={{marginTop:16}}>
                        <TechnicalDetailsDisplay 
                          technicalDetails={item.technical_details} 
                          compact={true}
                        />
                      </div>
                    )}
                    <div style={{display:'flex', gap:8, flexWrap:'wrap', margin:'6px 0'}}>
                      {item.image_url && (
                        <button className="btn btn-secondary" onClick={()=>openPreview(item)}>View Preview</button>
                      )}
                      {item.design_file_url && (
                        isImageUrl(item.design_file_url) || isPdfUrl(item.design_file_url) ? (
                          <button className="btn" onClick={()=>openPreview(item)}>View Layout</button>
                        ) : (
                          <a className="btn btn-link" href={item.design_file_url} target="_blank" rel="noreferrer">Download Layout</a>
                        )
                      )}
                    </div>
                    <div style={{display:'flex', gap:8, flexWrap:'wrap', margin:'6px 0'}}>
                      <button className="btn btn-secondary" onClick={()=>openEditLayout(item)}>Edit</button>
                      <button className="btn btn-danger btn-sm" onClick={()=>deleteLibraryItem(item.id)}>Delete</button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="empty-state">
              <p>No items in your library yet.</p>
              <button 
                className="btn btn-primary"
                onClick={() => setShowLibraryForm(true)}
              >
                Add to Library
              </button>
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
          reviewsError={archReviewsError}
        />
      )}
    </div>
  );

  return (
    <div className="dashboard-container">
      <style>{`
        .scrollable-form-content::-webkit-scrollbar {
          width: 8px;
        }
        .scrollable-form-content::-webkit-scrollbar-track {
          background: #f1f1f1;
          border-radius: 4px;
        }
        .scrollable-form-content::-webkit-scrollbar-thumb {
          background: #c1c1c1;
          border-radius: 4px;
        }
        .scrollable-form-content::-webkit-scrollbar-thumb:hover {
          background: #a8a8a8;
        }
      `}</style>
      
      {/* Sidebar */}
      <div className={`sidebar ${sidebarOpen ? 'open' : ''}`}>
        <div className="sidebar-header">
          <div className="sidebar-logo">
            <span className="logo-icon">🏗️</span>
            <span className="logo-text">ArchitectHub</span>
          </div>
          <button 
            className="sidebar-toggle"
            onClick={() => setSidebarOpen(!sidebarOpen)}
          >
            {sidebarOpen ? '✕' : '☰'}
          </button>
        </div>
        
        <nav className="sidebar-nav">
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'dashboard' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('dashboard'); }}
          >
            <span className="sb-icon">📊</span>
            <span className="sb-label">Dashboard</span>
            {pendingRequestsCount > 0 && (
              <span className="nav-badge pending pulse" title="Pending requests">{pendingRequestsCount}</span>
            )}
            {acceptedRequestsCount > 0 && (
              <span className="nav-badge accepted" title="Accepted requests">{acceptedRequestsCount}</span>
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
            <span className="sb-label">My Library</span>
            {libraryCount > 0 && (<span className="nav-badge pulse" style={{ marginLeft:'auto' }}>{libraryCount}</span>)}
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'profile' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('profile'); }}
          >
            <span className="sb-label">Profile</span>
          </a>
        </nav>
        
        <div className="sidebar-footer sb-footer">
          <div style={{position:'relative'}}>

            {showSidebarProfileMenu && (
              <div className="profile-dropdown open" style={{position:'absolute', bottom:'52px', left:0, right:0, zIndex: 1000}}>
                <div className="dropdown-header">My Account</div>
                <button className="dropdown-item" onClick={() => { setShowSidebarProfileMenu(false); setActiveTab('profile'); }}>Profile</button>
                <button className="dropdown-item" onClick={() => { setShowSidebarProfileMenu(false); }}>Settings</button>
                <button className="dropdown-item" onClick={() => { setShowSidebarProfileMenu(false); }}>Billing</button>
                <div className="dropdown-divider"></div>
                <button className="dropdown-item" onClick={handleLogout}>Sign Out</button>
              </div>
            )}
            
            <button 
              type="button" 
              className="profile-button"
              onClick={() => setShowSidebarProfileMenu(!showSidebarProfileMenu)}
              style={{
                width: '100%',
                background: showSidebarProfileMenu 
                  ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
                  : 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)',
                border: 'none',
                padding: '10px 12px',
                borderRadius: '10px',
                cursor: 'pointer',
                transition: 'all 0.3s ease',
                boxShadow: showSidebarProfileMenu 
                  ? '0 4px 12px rgba(102, 126, 234, 0.3)'
                  : '0 2px 4px rgba(0, 0, 0, 0.1)',
                transform: showSidebarProfileMenu ? 'translateY(-1px)' : 'translateY(0)',
                display: 'flex',
                alignItems: 'center'
              }}
              onMouseEnter={(e) => {
                if (!showSidebarProfileMenu) {
                  e.target.style.background = 'linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%)';
                  e.target.style.transform = 'translateY(-1px)';
                }
              }}
              onMouseLeave={(e) => {
                if (!showSidebarProfileMenu) {
                  e.target.style.background = 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)';
                  e.target.style.transform = 'translateY(0)';
                }
              }}
            >
              <div style={{
                display: 'flex',
                alignItems: 'center',
                gap: '10px',
                width: '100%',
                minWidth: 0
              }}>
                <div style={{
                  width: 36,
                  height: 36,
                  borderRadius: '50%',
                  background: showSidebarProfileMenu 
                    ? 'linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%)'
                    : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                  border: showSidebarProfileMenu ? '2px solid rgba(255, 255, 255, 0.3)' : '2px solid #e2e8f0',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  overflow: 'hidden',
                  boxShadow: showSidebarProfileMenu 
                    ? '0 2px 8px rgba(0, 0, 0, 0.1)'
                    : '0 1px 3px rgba(0, 0, 0, 0.1)',
                  flexShrink: 0
                }}>
                  <span style={{
                    fontWeight: 700,
                    fontSize: 14,
                    color: showSidebarProfileMenu ? '#667eea' : 'white',
                    textShadow: showSidebarProfileMenu ? 'none' : '0 1px 2px rgba(0, 0, 0, 0.1)'
                  }}>
                    {user?.first_name?.[0]}{user?.last_name?.[0]}
                  </span>
                </div>
                <div style={{
                  flex: 1,
                  minWidth: 0
                }}>
                  <div style={{
                    fontWeight: 600,
                    fontSize: 13,
                    color: showSidebarProfileMenu ? 'white' : '#374151',
                    whiteSpace: 'nowrap',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                    textShadow: showSidebarProfileMenu ? '0 1px 2px rgba(0, 0, 0, 0.1)' : 'none'
                  }}>
                    {user?.first_name} {user?.last_name}
                  </div>
                  <div style={{
                    fontSize: 11,
                    color: showSidebarProfileMenu ? 'rgba(255, 255, 255, 0.8)' : '#6b7280',
                    display: 'flex',
                    alignItems: 'center',
                    gap: 4,
                    lineHeight: '1.2',
                    marginTop: '2px'
                  }}>
                    <span style={{
                      display: 'inline-flex',
                      width: 5,
                      height: 5,
                      borderRadius: 5,
                      background: showSidebarProfileMenu ? 'rgba(255, 255, 255, 0.8)' : '#10b981',
                      boxShadow: showSidebarProfileMenu ? '0 0 4px rgba(255, 255, 255, 0.3)' : '0 0 4px rgba(16, 185, 129, 0.3)',
                      flexShrink: 0
                    }}></span>
                    <span style={{
                      whiteSpace: 'nowrap',
                      overflow: 'hidden',
                      textOverflow: 'ellipsis'
                    }}>Homeowner</span>
                  </div>
                </div>
                <span style={{
                  fontSize: 12,
                  color: showSidebarProfileMenu ? 'white' : '#6b7280',
                  textShadow: showSidebarProfileMenu ? '0 1px 2px rgba(0, 0, 0, 0.1)' : 'none',
                  flexShrink: 0,
                  marginLeft: '4px',
                  transform: showSidebarProfileMenu ? 'rotate(180deg)' : 'rotate(0deg)',
                  transition: 'transform 0.2s ease'
                }}>
                  ▾
                </span>
              </div>
            </button>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="main-content">
        {/* Header */}
        <div className="main-header">
          <div className="header-content">
            <div>
              <h1>Architect Dashboard</h1>
              <p>Manage your projects and designs</p>
            </div>
            <div className="header-actions">
              <button 
                className="btn btn-secondary" 
                onClick={refreshDashboard}
                disabled={loading}
              >
                {loading ? 'Refreshing...' : '🔄 Refresh'}
              </button>
            </div>
          </div>
        </div>

        {/* Error/Success Messages */}
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
        {activeTab === 'designs' && renderDesigns()}
        {activeTab === 'library' && renderLibrary()}
        {activeTab === 'profile' && renderProfile()}

        {/* Preview Modal */}
        {previewItem && (
          <div className="form-modal" onClick={closePreview}>
            <div className="form-content" style={{maxWidth:'90vw', width:'1100px'}} onClick={(e)=>e.stopPropagation()}>
              <div className="form-header" style={{display:'flex', justifyContent:'space-between', alignItems:'center'}}>
                <div>
                  <h3 style={{margin:0}}>{previewItem.title || 'Preview'}</h3>
                  <p style={{margin:'4px 0 0', color:'#666', fontSize:'0.9em'}}>
                    {previewItem.type && `Type: ${previewItem.type}`}
                  </p>
                </div>
                <button className="btn btn-secondary" onClick={closePreview}>✕</button>
              </div>
              <div className="scrollable-form-content" style={{maxHeight:'80vh', overflowY:'auto'}}>
                <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:20, marginBottom:20}}>
                  <div>
                    <h4>Preview</h4>
                    {previewItem.image_url ? (
                      <img 
                        src={previewItem.image_url} 
                        alt="Preview" 
                        style={{width:'100%', height:'80vh', objectFit:'contain'}}
                      />
                    ) : imgUrl ? (
                      <img src={imgUrl} alt="Preview" style={{width:'100%', height:'80vh', objectFit:'contain'}}/>
                    ) : (
                      <div style={{padding:24}}>No preview available. You can download the layout file from the card.</div>
                    )}
                  </div>
                  <div>
                    <h4>Details</h4>
                    <div style={{display:'grid', gridTemplateColumns:'1fr 1fr', gap:8}}>
                      <div><strong>Bedrooms:</strong> {previewItem.bedrooms ?? '-'}</div>
                      <div><strong>Bathrooms:</strong> {previewItem.bathrooms ?? '-'}</div>
                      <div><strong>Floors:</strong> {previewItem.floors ?? '-'}</div>
                      <div><strong>Area:</strong> {previewItem.area ?? '-'}</div>
                      <div><strong>Style:</strong> {previewItem.style ?? '-'}</div>
                      <div><strong>Type:</strong> {previewItem.type ?? '-'}</div>
                    </div>
                    {previewItem.technical_details && (
                      <div className="technical-details-section" style={{marginTop:16}}>
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
        )}

        {/* Upload Form Modal */}
        {showUploadForm && (
          <div className="form-modal" onClick={() => setShowUploadForm(false)}>
            <div className="form-content" onClick={(e) => e.stopPropagation()}>
              <div className="form-header">
                <h3>Upload New Design</h3>
                <button className="btn btn-secondary" onClick={() => setShowUploadForm(false)}>✕</button>
              </div>
              <form onSubmit={handleUploadSubmit}>
                <div className="form-group">
                  <label>Title *</label>
                  <input 
                    type="text" 
                    value={uploadForm.title}
                    onChange={(e) => setUploadForm({...uploadForm, title: e.target.value})}
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Description</label>
                  <textarea 
                    value={uploadForm.description}
                    onChange={(e) => setUploadForm({...uploadForm, description: e.target.value})}
                    rows={3}
                  />
                </div>
                <div className="form-group">
                  <label>Type *</label>
                  <select 
                    value={uploadForm.type}
                    onChange={(e) => setUploadForm({...uploadForm, type: e.target.value})}
                    required
                  >
                    <option value="">Select Type</option>
                    <option value="residential">Residential</option>
                    <option value="commercial">Commercial</option>
                    <option value="template">Template</option>
                  </select>
                </div>
                <div className="form-group">
                  <label>Image *</label>
                  <input 
                    type="file" 
                    accept="image/*"
                    onChange={handleImageChange}
                    required
                  />
                  {uploadForm.image && (
                    <div style={{marginTop: 8}}>
                      <img 
                        src={URL.createObjectURL(uploadForm.image)} 
                        alt="Preview" 
                        style={{width: 100, height: 100, objectFit: 'cover', borderRadius: 4}}
                      />
                      <p style={{margin:'8px 0 0', fontSize:'0.8rem', color:'#166534'}}>{uploadForm.image.name}</p>
                    </div>
                  )}
                </div>
                <div className="form-group">
                  <label>Design File</label>
                  <input 
                    type="file" 
                    accept=".pdf,.dwg,.skp"
                    onChange={handleFileChange}
                  />
                  {uploadForm.design_file && (
                    <div style={{marginTop: 8}}>
                      <p style={{margin:'8px 0 0', fontSize:'0.8rem', color:'#166534'}}>{uploadForm.design_file.name}</p>
                    </div>
                  )}
                </div>
                <div className="form-actions">
                  <button type="button" className="btn btn-secondary" onClick={() => setShowUploadForm(false)}>
                    Cancel
                  </button>
                  <button type="submit" className="btn btn-primary" disabled={uploading}>
                    {uploading ? 'Uploading...' : 'Upload Design'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Library Form Modal */}
        {showLibraryForm && (
          <div className="form-modal" onClick={() => setShowLibraryForm(false)}>
            <div className="form-content" onClick={(e) => e.stopPropagation()}>
              <div className="form-header">
                <h3>Add to Library</h3>
                <button className="btn btn-secondary" onClick={() => setShowLibraryForm(false)}>✕</button>
              </div>
              <form onSubmit={handleLibrarySubmit}>
                <div className="form-group">
                  <label>Title *</label>
                  <input 
                    type="text" 
                    value={libraryForm.title}
                    onChange={(e) => setLibraryForm({...libraryForm, title: e.target.value})}
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Description</label>
                  <textarea 
                    value={libraryForm.description}
                    onChange={(e) => setLibraryForm({...libraryForm, description: e.target.value})}
                    rows={3}
                  />
                </div>
                <div className="form-group">
                  <label>Type *</label>
                  <select 
                    value={libraryForm.type}
                    onChange={(e) => setLibraryForm({...libraryForm, type: e.target.value})}
                    required
                  >
                    <option value="">Select Type</option>
                    <option value="template">Template</option>
                    <option value="material">Material</option>
                    <option value="reference">Reference</option>
                  </select>
                </div>
                <div className="form-group">
                  <label>Image *</label>
                  <input 
                    type="file" 
                    accept="image/*"
                    onChange={handleLibraryImageChange}
                    required
                  />
                  {libraryForm.image && (
                    <div style={{marginTop: 8}}>
                      <img 
                        src={URL.createObjectURL(libraryForm.image)} 
                        alt="Preview" 
                        style={{width: 100, height: 100, objectFit: 'cover', borderRadius: 4}}
                      />
                      <p style={{margin:'8px 0 0', fontSize:'0.8rem', color:'#166534'}}>{libraryForm.image.name}</p>
                    </div>
                  )}
                </div>
                <div className="form-group">
                  <label>Design File</label>
                  <input 
                    type="file" 
                    accept=".pdf,.dwg,.skp"
                    onChange={handleLibraryFileChange}
                  />
                  {libraryForm.design_file && (
                    <div style={{marginTop: 8}}>
                      <p style={{margin:'8px 0 0', fontSize:'0.8rem', color:'#166534'}}>{libraryForm.design_file.name}</p>
                    </div>
                  )}
                </div>
                <div className="form-actions">
                  <button type="button" className="btn btn-secondary" onClick={() => setShowLibraryForm(false)}>
                    Cancel
                  </button>
                  <button type="submit" className="btn btn-primary" disabled={uploading}>
                    {uploading ? 'Adding...' : 'Add to Library'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Edit Layout Modal */}
        {editLayout && (
          <div className="form-modal" onClick={() => setEditLayout(null)}>
            <div className="form-content" onClick={(e) => e.stopPropagation()}>
              <div className="form-header">
                <h3>Edit Library Item</h3>
                <button className="btn btn-secondary" onClick={() => setEditLayout(null)}>✕</button>
              </div>
              <form onSubmit={handleEditLayoutSubmit}>
                <div className="form-group">
                  <label>Title *</label>
                  <input 
                    type="text" 
                    value={editLayout.title}
                    onChange={(e) => setEditLayout({...editLayout, title: e.target.value})}
                    required
                  />
                </div>
                <div className="form-group">
                  <label>Description</label>
                  <textarea 
                    value={editLayout.description}
                    onChange={(e) => setEditLayout({...editLayout, description: e.target.value})}
                    rows={3}
                  />
                </div>
                <div className="form-group">
                  <label>Type *</label>
                  <select 
                    value={editLayout.type}
                    onChange={(e) => setEditLayout({...editLayout, type: e.target.value})}
                    required
                  >
                    <option value="template">Template</option>
                    <option value="material">Material</option>
                    <option value="reference">Reference</option>
                  </select>
                </div>
                <div className="form-group">
                  <label>Current Image</label>
                  {editLayout.image_url ? (
                    <div>
                      <img 
                        src={editLayout.image_url} 
                        alt="Current" 
                        style={{width: 100, height: 100, objectFit: 'cover', borderRadius: 4}}
                      />
                      <p style={{margin:'8px 0 0', fontSize:'0.8rem', color:'#166534'}}>Current image</p>
                    </div>
                  ) : (
                    <p style={{color:'#6b7280', fontStyle:'italic'}}>No current image</p>
                  )}
                </div>
                <div className="form-group">
                  <label>New Image</label>
                  <input 
                    type="file" 
                    accept="image/*"
                    onChange={handleEditImageChange}
                  />
                  {editLayout.image && (
                    <div style={{marginTop: 8}}>
                      <img 
                        src={URL.createObjectURL(editLayout.image)} 
                        alt="Preview" 
                        style={{width: 100, height: 100, objectFit: 'cover', borderRadius: 4}}
                      />
                      <p style={{margin:'8px 0 0', fontSize:'0.8rem', color:'#166534'}}>{editLayout.image.name}</p>
                    </div>
                  )}
                </div>
                <div className="form-group">
                  <label>Current Layout File</label>
                  {editLayout.design_file_url ? (
                    <div>
                      <a href={editLayout.design_file_url} target="_blank" rel="noreferrer" className="btn btn-link">
                        View Current File
                      </a>
                    </div>
                  ) : (
                    <p style={{color:'#6b7280', fontStyle:'italic'}}>No current layout file</p>
                  )}
                </div>
                <div className="form-group">
                  <label>New Layout File</label>
                  <input 
                    type="file" 
                    accept=".pdf,.dwg,.skp"
                    onChange={handleEditFileChange}
                  />
                  {editLayout.design_file && (
                    <div style={{marginTop: 8}}>
                      <p style={{margin:'8px 0 0', fontSize:'0.8rem', color:'#166534'}}>{editLayout.design_file.name}</p>
                    </div>
                  )}
                </div>
                <div className="form-actions">
                  <button type="button" className="btn btn-secondary" onClick={() => setEditLayout(null)}>
                    Cancel
                  </button>
                  <button type="submit" className="btn btn-primary" disabled={uploading}>
                    {uploading ? 'Updating...' : 'Update Item'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default ArchitectDashboard;
