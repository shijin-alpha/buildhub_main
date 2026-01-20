import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import AdminPaymentVerification from './AdminPaymentVerification.jsx';
import '../styles/AdminDashboard.css';
import '../styles/BlueGlassTheme.css';
import '../styles/SoftSidebar.css';
import '../styles/AdminSupport.css';
import './WidgetColors.css';
import OCRVerificationModal from './OCRVerificationModal';

// Lightweight inline icon component (no external deps)
const Icon = ({ name, size = 20, stroke = 1.8, color = 'currentColor' }) => {
  const common = { width: size, height: size, viewBox: '0 0 24 24', fill: 'none', stroke: color, strokeWidth: stroke, strokeLinecap: 'round', strokeLinejoin: 'round' };
  switch (name) {
    case 'home':
      return (<svg {...common}><path d="M3 10.5L12 3l9 7.5"/><path d="M5 10v10h14V10"/></svg>);
    case 'users':
      return (<svg {...common}><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>);
    case 'hourglass':
      return (<svg {...common}><path d="M6 2h12"/><path d="M6 22h12"/><path d="M8 2v6l4 4 4-4V2"/><path d="M16 22v-6l-4-4-4 4v6"/></svg>);
    case 'cube':
      return (<svg {...common}><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96L12 12l8.73-5.04"/></svg>);
    case 'project':
      return (<svg {...common}><rect x="3" y="3" width="18" height="14" rx="2"/><path d="M7 21h10"/><path d="M12 17v4"/></svg>);
    case 'search':
      return (<svg {...common}><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>);
    case 'bell':
      return (<svg {...common}><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"/><path d="M9 21h6"/></svg>);
    case 'settings':
      return (<svg {...common}><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V22a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H2a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H8a1.65 1.65 0 0 0 1-1.51V2a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.09a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V8c0 .66.26 1.3.73 1.77.47.47 1.11.73 1.77.73H22a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>);
    case 'logout':
      return (<svg {...common}><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>);
    case 'file':
      return (<svg {...common}><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>);
    case 'eye':
      return (<svg {...common}><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>);
    case 'download':
      return (<svg {...common}><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>);
    case 'check':
      return (<svg {...common}><path d="M20 6L9 17l-5-5"/></svg>);
    case 'x':
      return (<svg {...common}><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>);
    default:
      return null;
  }
};

const AdminDashboard = () => {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('dashboard');
  const [pendingUsers, setPendingUsers] = useState([]);
  const [allUsers, setAllUsers] = useState([]);
  const [materials, setMaterials] = useState([]);
  const [systemStats, setSystemStats] = useState({});
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [materialError, setMaterialError] = useState('');
  const [materialSuccess, setMaterialSuccess] = useState('');
  const [materialToDelete, setMaterialToDelete] = useState(null);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  // OCR Verification Modal state
  const [ocrModalOpen, setOcrModalOpen] = useState(false);
  const [selectedUserForOCR, setSelectedUserForOCR] = useState(null);
  // Support issues state
  const [supportIssues, setSupportIssues] = useState([]);
  const [supportLoading, setSupportLoading] = useState(false);
  const [selectedIssue, setSelectedIssue] = useState(null);
  const [issueReplies, setIssueReplies] = useState([]);
  const [adminReply, setAdminReply] = useState('');
  const adminName = (typeof window !== 'undefined' && (localStorage.getItem('admin_username') || 'Administrator')) || 'Administrator';

  // User management filters
  const [userFilters, setUserFilters] = useState({
    role: 'all',
    status: 'all',
    search: '',
    sortBy: 'created_at',
    sortOrder: 'desc'
  });

  // Material form state
  const [materialForm, setMaterialForm] = useState({
    name: '',
    category: '',
    unit: '',
    price: '',
    description: ''
  });

  useEffect(() => {
    // Strict: prevent cached back navigation
    import('../utils/session').then(({ preventCache }) => preventCache());

    fetchSystemStats();
    if (activeTab === 'pending') {
      fetchPendingUsers();
    } else if (activeTab === 'users') {
      fetchAllUsers();
    } else if (activeTab === 'materials') {
      fetchMaterials();
    } else if (activeTab === 'reports') {
      loadSupportIssues();
    }
  }, [activeTab]);

  // Auto-refresh reports list and open thread while on Reports tab
  useEffect(() => {
    if (activeTab !== 'reports') return;
    const listInterval = setInterval(() => {
      loadSupportIssues();
    }, 30000); // every 30s
    const threadInterval = setInterval(() => {
      if (selectedIssue) openSupportThread(selectedIssue.id);
    }, 15000); // thread updates every 15s if open
    return () => { clearInterval(listInterval); clearInterval(threadInterval); };
  }, [activeTab, selectedIssue]);

  useEffect(() => {
    if (activeTab === 'users') {
      fetchAllUsers();
    }
  }, [userFilters]);

  // Clear messages after 5 seconds
  useEffect(() => {
    if (error || success) {
      const timer = setTimeout(() => {
        setError('');
        setSuccess('');
      }, 5000);
      return () => clearTimeout(timer);
    }
  }, [error, success]);

  // Clear material messages after 5 seconds
  useEffect(() => {
    if (materialError || materialSuccess) {
      const timer = setTimeout(() => {
        setMaterialError('');
        setMaterialSuccess('');
      }, 5000);
      return () => clearTimeout(timer);
    }
  }, [materialError, materialSuccess]);

  const fetchSystemStats = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/admin/get_stats.php');
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const result = await response.json();
      if (result.success) {
        setSystemStats(result.stats || {});
      } else {
        console.error('Stats error:', result.message);
        setSystemStats({});
      }
    } catch (error) {
      console.error('Error fetching system stats:', error);
    }
  };

  const fetchPendingUsers = async () => {
    setLoading(true);
    try {
      const response = await fetch('/buildhub/backend/api/admin/get_pending_users.php');
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const result = await response.json();
      
      if (result.success) {
        setPendingUsers(result.users || []);
        setError('');
      } else {
        setError(result.message || 'Failed to fetch pending users');
        setPendingUsers([]);
      }
    } catch (error) {
      console.error('Fetch pending users error:', error);
      setError(`Network error: ${error.message}`);
      setPendingUsers([]);
    } finally {
      setLoading(false);
    }
  };

  const fetchAllUsers = async () => {
    setLoading(true);
    try {
      const queryParams = new URLSearchParams({
        role: userFilters.role,
        status: userFilters.status,
        search: userFilters.search,
        sortBy: userFilters.sortBy,
        sortOrder: userFilters.sortOrder
      });

      const response = await fetch(`/buildhub/backend/api/admin/get_all_users.php?${queryParams}`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const result = await response.json();
      
      if (result.success) {
        setAllUsers(result.users || []);
        setError('');
      } else {
        setError(result.message || 'Failed to fetch users');
        setAllUsers([]);
      }
    } catch (error) {
      console.error('Fetch all users error:', error);
      setError(`Network error: ${error.message}`);
      setAllUsers([]);
    } finally {
      setLoading(false);
    }
  };

  const fetchMaterials = async () => {
    setLoading(true);
    try {
      const response = await fetch('/buildhub/backend/api/admin/get_materials.php');
      const result = await response.json();
      if (result.success) {
        setMaterials(result.materials);
      } else {
        setError(result.message || 'Failed to fetch materials');
      }
    } catch (error) {
      setError('Network error occurred');
    } finally {
      setLoading(false);
    }
  };

  const handleUserAction = async (userId, action) => {
    setLoading(true);
    setError('');
    setSuccess('');
    
    try {
      const response = await fetch('/buildhub/backend/api/admin/user_action.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: userId,
          action: action
        })
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const result = await response.json();
      
      if (result.success) {
        setSuccess(result.message);
        // Refresh both pending users and all users lists
        setTimeout(() => {
          fetchPendingUsers();
          fetchAllUsers();
        }, 500);
      } else {
        setError(result.message || 'Action failed');
      }
    } catch (error) {
      console.error('User action error:', error);
      setError(`Network error: ${error.message}`);
    } finally {
      setLoading(false);
    }
  };

  const downloadDocument = async (userId, docType) => {
    try {
      const response = await fetch(`/buildhub/backend/api/admin/download_document.php?user_id=${userId}&doc_type=${docType}`);
      
      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `${docType}_${userId}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
      } else {
        setError('Failed to download document');
      }
    } catch (error) {
      setError('Download failed');
    }
  };

  // OCR Verification Functions
  const openOCRModal = (user) => {
    setSelectedUserForOCR(user);
    setOcrModalOpen(true);
  };

  const closeOCRModal = () => {
    setOcrModalOpen(false);
    setSelectedUserForOCR(null);
  };

  const handleOCRVerificationComplete = (message) => {
    setSuccess(message);
    // Refresh the pending users list
    setTimeout(() => {
      fetchPendingUsers();
      fetchAllUsers();
    }, 500);
  };

  const triggerOCRProcessing = async (userId) => {
    setLoading(true);
    setError('');
    
    try {
      const response = await fetch('/buildhub/backend/api/admin/trigger_ocr.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: userId
        })
      });
      
      const result = await response.json();
      
      if (result.success) {
        setSuccess('OCR processing started successfully');
        // Refresh the users list after a delay
        setTimeout(() => {
          fetchPendingUsers();
          fetchAllUsers();
        }, 2000);
      } else {
        setError(result.message || 'OCR processing failed');
      }
    } catch (error) {
      console.error('OCR trigger error:', error);
      setError('Network error occurred');
    } finally {
      setLoading(false);
    }
  };

  const viewDocument = (userId, docType) => {
    const viewUrl = `/buildhub/backend/api/admin/view_document.php?user_id=${userId}&doc_type=${docType}`;
    window.open(viewUrl, '_blank');
  };

  const triggerOCR = async (userId) => {
    setLoading(true);
    setError('');
    setSuccess('');
    
    try {
      const response = await fetch('/buildhub/backend/api/admin/trigger_ocr.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ user_id: userId })
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const result = await response.json();
      
      if (result.success) {
        setSuccess('OCR processing triggered. Results will appear shortly.');
        // Refresh pending users after a short delay
        setTimeout(() => {
          fetchPendingUsers();
        }, 2000);
      } else {
        setError(result.message || 'Failed to trigger OCR');
      }
    } catch (error) {
      console.error('Trigger OCR error:', error);
      setError(`Network error: ${error.message}`);
    } finally {
      setLoading(false);
    }
  };

  const handleMaterialSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMaterialError('');
    setMaterialSuccess('');
    
    try {
      const response = await fetch('/buildhub/backend/api/admin/add_material.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(materialForm)
      });
      
      const result = await response.json();
      if (result.success) {
        setMaterialSuccess('Material added successfully');
        setMaterialForm({ name: '', category: '', unit: '', price: '', description: '' });
        fetchMaterials();
      } else {
        setMaterialError(result.message || 'Failed to add material');
      }
    } catch (error) {
      setMaterialError('Network error occurred');
    } finally {
      setLoading(false);
    }
  };

  const confirmDeleteMaterial = (materialId) => {
    const material = materials.find(m => m.id === materialId);
    setMaterialToDelete(material);
    deleteMaterial();
  };

  const deleteMaterial = async () => {
    if (!materialToDelete) return;
    
    setLoading(true);
    setMaterialError('');
    setMaterialSuccess('');
    setShowDeleteConfirm(false);
    
    try {
      const response = await fetch('/buildhub/backend/api/admin/delete_material.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ material_id: materialToDelete.id })
      });
      
      const result = await response.json();
      if (result.success) {
        setMaterialSuccess('Material deleted successfully');
        fetchMaterials();
      } else {
        setMaterialError(result.message || 'Failed to delete material');
      }
    } catch (error) {
      setMaterialError('Network error occurred');
    } finally {
      setLoading(false);
      setMaterialToDelete(null);
    }
  };

  // Support helpers (admin)
  const loadSupportIssues = async () => {
    setSupportLoading(true);
    try {
      console.log('Loading support issues...');
      console.log('API URL: /buildhub/backend/api/admin/get_support_issues.php');
      
      const res = await fetch('/buildhub/backend/api/admin/get_support_issues.php', { 
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        }
      });
      
      console.log('Response status:', res.status);
      console.log('Response headers:', res.headers);
      
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }
      
      const json = await res.json();
      console.log('Response data:', json);
      
      if (json.success) {
        console.log('Setting support issues:', json.issues?.length || 0, 'issues');
        setSupportIssues(json.issues || []);
        setError(''); // Clear any previous errors
      } else {
        console.log('Admin API failed, trying fallback. Message:', json.message);
        setError(`Admin API failed: ${json.message}`);
        
        // Fallback: show current user's reports if admin session not detected
        try {
          console.log('Trying fallback API...');
          const resUser = await fetch('/buildhub/backend/api/support/get_issues.php', { credentials: 'include' });
          const jsUser = await resUser.json();
          console.log('Fallback response:', jsUser);
          if (jsUser.success) {
            setSupportIssues(jsUser.issues || []);
            setError('Showing your own issues (admin access may be limited)');
          } else {
            setSupportIssues([]);
            setError('No issues found and admin access failed');
          }
        } catch (fallbackError) {
          console.error('Fallback also failed:', fallbackError);
          setSupportIssues([]);
          setError('Both admin and user APIs failed');
        }
      }
    } catch (e) {
      console.error('Error loading support issues:', e);
      setError(`Failed to load reports: ${e.message}`);
      setSupportIssues([]);
    } finally {
      setSupportLoading(false);
    }
  };

  const openSupportThread = async (issueId) => {
    setSupportLoading(true);
    try {
      const res = await fetch(`/buildhub/backend/api/admin/get_support_thread.php?issue_id=${issueId}`, { credentials: 'include' });
      const json = await res.json();
      if (json.success) {
        setSelectedIssue(json.issue);
        setIssueReplies(json.replies || []);
      } else {
        setError(json.message || 'Failed to load thread');
      }
    } catch (e) {
      setError('Failed to load thread');
    } finally {
      setSupportLoading(false);
    }
  };

  const sendAdminReply = async () => {
    if (!selectedIssue || !adminReply.trim()) return;
    try {
      const res = await fetch('/buildhub/backend/api/support/admin_reply.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ issue_id: selectedIssue.id, message: adminReply })
      });
      const json = await res.json();
      if (json.success) {
        setAdminReply('');
        openSupportThread(selectedIssue.id);
        loadSupportIssues();
        setSuccess('Reply sent');
      } else {
        setError(json.message || 'Failed to send reply');
      }
    } catch (e) {
      setError('Network error sending reply');
    }
  };

  const handleUserStatusChange = async (userId, newStatus) => {
    setLoading(true);
    setError('');
    setSuccess('');
    
    try {
      const response = await fetch('/buildhub/backend/api/admin/update_user_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: userId,
          status: newStatus
        })
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const result = await response.json();
      
      if (result.success) {
        setSuccess(result.message);
        // Refresh both all users and pending users lists
        fetchAllUsers();
        fetchPendingUsers();
        if (selectedUser && selectedUser.id === userId) {
          setSelectedUser({...selectedUser, status: newStatus});
        }
      } else {
        setError(result.message || 'Status update failed');
      }
    } catch (error) {
      console.error('User status update error:', error);
      setError(`Network error: ${error.message}`);
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteUser = async (userId) => {
    setLoading(true);
    setError('');
    setSuccess('');
    try {
      const response = await fetch('/buildhub/backend/api/admin/delete_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId })
      });
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const result = await response.json();
      if (result.success) {
        setSuccess(result.message || 'User deleted');
        fetchAllUsers();
        fetchPendingUsers();
      } else {
        setError(result.message || 'Failed to delete user');
      }
    } catch (e) {
      setError(`Network error: ${e.message}`);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (filterType, value) => {
    setUserFilters(prev => ({
      ...prev,
      [filterType]: value
    }));
  };

  const clearFilters = () => {
    setUserFilters({
      role: 'all',
      status: 'all',
      search: '',
      sortBy: 'created_at',
      sortOrder: 'desc'
    });
  };

  const viewUserDetails = (user) => {
    toast.info(`User Details:\nName: ${user.name}\nEmail: ${user.email}\nStatus: ${user.status}\nRole: ${user.role}\nCreated: ${new Date(user.created_at).toLocaleDateString()}`);
  };

  const handleLogout = async () => {
    try { await fetch('/buildhub/backend/api/logout.php', { method: 'POST', credentials: 'include' }); } catch {}
    localStorage.removeItem('admin_logged_in');
    localStorage.removeItem('admin_username');
    navigate('/login', { replace: true });
  };

  const renderDashboard = () => (
    <div>
      {/* Main Header */}
      <div className="main-header">
        <h1>Overview</h1>
        <p>Manage users, materials, and monitor system activity</p>
      </div>

      {/* Stats Grid */}
      <div className="stats-grid">
        <div className="stat-card w-blue">
          <div className="stat-content">
            <div className="stat-icon users"><Icon name="users" /></div>
            <div className="stat-info">
              <h3>{systemStats.totalUsers || 0}</h3>
              <p>Total Users</p>
            </div>
          </div>
        </div>
        <div className="stat-card w-orange">
          <div className="stat-content">
            <div className="stat-icon pending"><Icon name="hourglass" /></div>
            <div className="stat-info">
              <h3>{pendingUsers.length}</h3>
              <p>Pending Approvals</p>
            </div>
          </div>
        </div>
        <div className="stat-card w-purple">
          <div className="stat-content">
            <div className="stat-icon materials"><Icon name="cube" /></div>
            <div className="stat-info">
              <h3>{materials.length}</h3>
              <p>Materials Listed</p>
            </div>
          </div>
        </div>
        <div className="stat-card w-green">
          <div className="stat-content">
            <div className="stat-icon projects"><Icon name="project" /></div>
            <div className="stat-info">
              <h3>{systemStats.activeProjects || 0}</h3>
              <p>Active Projects</p>
            </div>
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="section-card">
        <div className="section-header">
          <h2>Quick Actions</h2>
          <p>Common administrative tasks</p>
        </div>
        <div className="section-content">
          <div className="quick-actions">
            <button 
              className="action-card"
              onClick={() => setActiveTab('users')}
            >
              <div className="action-icon"><Icon name="users" /></div>
              <h3>Manage All Users</h3>
              <p>View, filter, and manage all system users</p>
            </button>
            <button 
              className="action-card"
              onClick={() => setActiveTab('pending')}
            >
              <div className="action-icon"><Icon name="hourglass" /></div>
              <h3>Review Pending Users</h3>
              <p>Approve or reject user registration requests</p>
            </button>
            <button 
              className="action-card"
              onClick={() => setActiveTab('materials')}
            >
              <div className="action-icon"><Icon name="cube" /></div>
              <h3>Manage Materials</h3>
              <p>Add, edit, or remove construction materials</p>
            </button>
          </div>
        </div>
      </div>

      {/* Recent Activity */}
      <div className="section-card">
        <div className="section-header">
          <h2>Recent Activity</h2>
          <p>Latest system activities and user actions</p>
        </div>
        <div className="section-content">
          <div className="item-list">
            {pendingUsers.slice(0, 5).map(user => (
              <div key={user.id} className="list-item">
                <div className="item-icon"><Icon name="users" /></div>
                <div className="item-content">
                  <h4 className="item-title">{user.first_name} {user.last_name}</h4>
                  <p className="item-subtitle">Role: {user.role} • Email: {user.email}</p>
                  <p className="item-meta">Registered: {new Date(user.created_at).toLocaleDateString()}</p>
                </div>
                <div className="item-actions">
                  <span className="status-badge pending">Pending Review</span>
                </div>
              </div>
            ))}
            {pendingUsers.length === 0 && (
              <div className="empty-state">
                <div className="empty-icon">✅</div>
                <h3>All Caught Up!</h3>
                <p>No pending user approvals at the moment.</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );

  const renderUsers = () => (
    <div>
      <div className="main-header">
        <div className="header-content">
          <div>
            <h1>User Management</h1>
            <p>View and manage all users in the system</p>
          </div>
        </div>
      </div>

      {/* Filters Section */}
      <div className="section-card">
        <div className="section-header">
          <h2>Filter & Search Users</h2>
          <p>Use filters to find specific users efficiently</p>
        </div>
        <div className="section-content">
          <div className="filters-container">
            <div className="filter-row">
              <div className="filter-group">
                <label>Search Users</label>
                <input
                  type="text"
                  placeholder="Search by name or email..."
                  value={userFilters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  className="filter-input"
                />
              </div>
              <div className="filter-group">
                <label>User Role</label>
                <select
                  value={userFilters.role}
                  onChange={(e) => handleFilterChange('role', e.target.value)}
                  className="filter-select"
                >
                  <option value="all">All Roles</option>
                  <option value="homeowner">Homeowners</option>
                  <option value="contractor">Contractors</option>
                  <option value="architect">Architects</option>
                </select>
              </div>
              <div className="filter-group">
                <label>Status</label>
                <select
                  value={userFilters.status}
                  onChange={(e) => handleFilterChange('status', e.target.value)}
                  className="filter-select"
                >
                  <option value="all">All Status</option>
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="rejected">Rejected</option>
                  <option value="suspended">Suspended</option>
                </select>
              </div>
            </div>
            <div className="filter-row">
              <div className="filter-group">
                <label>Sort By</label>
                <select
                  value={userFilters.sortBy}
                  onChange={(e) => handleFilterChange('sortBy', e.target.value)}
                  className="filter-select"
                >
                  <option value="created_at">Registration Date</option>
                  <option value="first_name">First Name</option>
                  <option value="last_name">Last Name</option>
                  <option value="email">Email</option>
                  <option value="role">Role</option>
                  <option value="status">Status</option>
                </select>
              </div>
              <div className="filter-group">
                <label>Order</label>
                <select
                  value={userFilters.sortOrder}
                  onChange={(e) => handleFilterChange('sortOrder', e.target.value)}
                  className="filter-select"
                >
                  <option value="desc">Newest First</option>
                  <option value="asc">Oldest First</option>
                </select>
              </div>
              <div className="filter-actions">
                <button className="btn btn-secondary" onClick={clearFilters}>
                  Clear Filters
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Users List */}
      <div className="section-card">
        <div className="section-header">
          <h2>Users ({allUsers.length})</h2>
          <p>All registered users in the system</p>
        </div>
        <div className="section-content">
          {loading ? (
            <div className="loading">Loading users...</div>
          ) : allUsers.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">👥</div>
              <h3>No Users Found</h3>
              <p>No users match your current filters.</p>
            </div>
          ) : (
            <div className="users-table-container">
              <div className="users-table">
                <div className="table-header">
                  <div className="table-cell">User</div>
                  <div className="table-cell">Role</div>
                  <div className="table-cell">Status</div>
                  <div className="table-cell">Registered</div>
                  <div className="table-cell">Actions</div>
                </div>
                {allUsers.map(user => (
                  <UserTableRow 
                    key={user.id} 
                    user={user} 
                    onViewDetails={() => viewUserDetails(user)}
                    onStatusChange={(newStatus) => handleUserStatusChange(user.id, newStatus)}
                    onViewDocument={viewDocument}
                    onDownloadDocument={downloadDocument}
                    onDeleteUser={handleDeleteUser}
                  />
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const renderPendingUsers = () => (
    <div>
      <div className="main-header">
        <div className="header-content">
          <div>
            <h1>Pending User Approvals</h1>
            <p>Review and approve user registration requests</p>
          </div>
        </div>
      </div>

      <div className="section-card">
        <div className="section-header">
          <div>
            <h2>Users Awaiting Approval</h2>
            <p>Review user documents and approve or reject registrations</p>
            <p style={{ fontSize: '12px', color: '#666', marginTop: '5px' }}>
              📄 OCR verification results are shown below each user card
            </p>
          </div>
        </div>
        <div className="section-content">
          {loading ? (
            <div className="loading">Loading users...</div>
          ) : pendingUsers.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">✅</div>
              <h3>No Pending Approvals</h3>
              <p>All user registrations have been processed!</p>
            </div>
          ) : (
            <div className="users-grid">
              {pendingUsers.map(user => (
                <UserCard 
                  key={user.id} 
                  user={user} 
                  onApprove={() => handleUserAction(user.id, 'approve')}
                  onReject={() => handleUserAction(user.id, 'reject')}
                  onViewDocument={viewDocument}
                  onDownloadDocument={downloadDocument}
                  onTriggerOCR={openOCRModal}
                  loading={loading}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );

  const renderMaterials = () => (
    <div style={{marginRight: '20px'}}>
      <div className="main-header">
        <div className="header-content">
          <div>
            <h1>Materials Management</h1>
            <p>Manage construction materials and pricing</p>
          </div>
        </div>
      </div>

      {/* Material-specific alerts */}
      {materialError && (
        <div className="alert alert-error" style={{marginBottom: '20px'}}>
          {materialError}
          <button onClick={() => setMaterialError('')} className="alert-close">×</button>
        </div>
      )}
      {materialSuccess && (
        <div className="alert alert-success" style={{marginBottom: '20px'}}>
          {materialSuccess}
          <button onClick={() => setMaterialSuccess('')} className="alert-close">×</button>
        </div>
      )}

      {/* Add Material Form */}
      <div className="section-card" style={{marginBottom: '24px'}}>
        <div className="section-header">
          <h2>Add New Material</h2>
          <p>Add construction materials to the system catalog</p>
        </div>
        <div className="section-content">
          <form onSubmit={handleMaterialSubmit} className="material-form">
            <div className="form-row">
              <div className="form-group">
                <label>Material Name *</label>
                <input
                  type="text"
                  value={materialForm.name}
                  onChange={(e) => setMaterialForm({...materialForm, name: e.target.value})}
                  placeholder="e.g., Portland Cement"
                  required
                />
              </div>
              <div className="form-group">
                <label>Category *</label>
                <select
                  value={materialForm.category}
                  onChange={(e) => setMaterialForm({...materialForm, category: e.target.value})}
                  required
                >
                  <option value="">Select Category</option>
                  <option value="cement">Cement</option>
                  <option value="steel">Steel</option>
                  <option value="bricks">Bricks</option>
                  <option value="sand">Sand</option>
                  <option value="gravel">Gravel</option>
                  <option value="wood">Wood</option>
                  <option value="tiles">Tiles</option>
                  <option value="paint">Paint</option>
                  <option value="electrical">Electrical</option>
                  <option value="plumbing">Plumbing</option>
                  <option value="other">Other</option>
                </select>
              </div>
            </div>
            <div className="form-row">
              <div className="form-group">
                <label>Unit *</label>
                <input
                  type="text"
                  value={materialForm.unit}
                  onChange={(e) => setMaterialForm({...materialForm, unit: e.target.value})}
                  placeholder="kg, m³, pieces, etc."
                  required
                />
              </div>
              <div className="form-group">
                <label>Price per Unit *</label>
                <input
                  type="number"
                  step="0.01"
                  value={materialForm.price}
                  onChange={(e) => setMaterialForm({...materialForm, price: e.target.value})}
                  placeholder="0.00"
                  required
                />
              </div>
            </div>
            <div className="form-group">
              <label>Description</label>
              <textarea
                value={materialForm.description}
                onChange={(e) => setMaterialForm({...materialForm, description: e.target.value})}
                placeholder="Optional description of the material..."
                rows="3"
              />
            </div>
            <div className="form-actions">
              <button type="submit" disabled={loading} className="btn btn-primary">
                {loading ? 'Adding...' : 'Add Material'}
              </button>
            </div>
          </form>
        </div>
      </div>

      {/* Materials List */}
      <div className="section-card">
        <div className="section-header">
          <h2>Current Materials</h2>
          <p>All materials currently available in the system</p>
        </div>
        <div className="section-content">
          {loading ? (
            <div className="loading">Loading materials...</div>
          ) : materials.length === 0 ? (
            <div className="empty-state">
              <div className="empty-icon">🧱</div>
              <h3>No Materials Added</h3>
              <p>Start by adding your first construction material!</p>
            </div>
          ) : (
            <div className="materials-grid">
              {materials.map(material => (
                <MaterialCard 
                  key={material.id} 
                  material={material} 
                  onDelete={() => confirmDeleteMaterial(material.id)}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );

  return (
    <div className="dashboard-container admin-modern">
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
            <span className="logo-text sb-title">Admin</span>
          </a>
        </div>

        <nav className="sidebar-nav sb-nav">
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'dashboard' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('dashboard'); }}
          >
            <span className="nav-icon sb-icon"><Icon name="home" /></span>
            Overview
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'users' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('users'); }}
          >
            <span className="nav-icon sb-icon"><Icon name="users" /></span>
            All Users
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'pending' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('pending'); }}
          >
            <span className="nav-icon sb-icon"><Icon name="hourglass" /></span>
            Pending Approvals
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'materials' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('materials'); }}
          >
            <span className="nav-icon sb-icon"><Icon name="cube" /></span>
            Materials
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'payments' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('payments'); }}
          >
            <span className="nav-icon sb-icon"><Icon name="check" /></span>
            Payment Verification
          </a>
          <a 
            href="#" 
            className={`nav-item sb-item ${activeTab === 'reports' ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setActiveTab('reports'); }}
          >
            <span className="nav-icon sb-icon"><Icon name="file" /></span>
            Customer Support
          </a>
          {/* 3D view moved to Homeowner Dashboard */}
        </nav>

      </div>

      {/* Main Content with right profile rail */}
      <div className="dashboard-main soft-main shifted">
        {/* Top bar: search + controls */}
        <div className="admin-topbar">
          <div className="search-box">
            <Icon name="search" size={20} />
            <input type="text" placeholder="Search" aria-label="Search" />
          </div>
          <div className="topbar-actions">
            <button className="icon-btn" aria-label="Change view"><Icon name="project" /></button>
            <button className="icon-btn" aria-label="Notifications"><Icon name="bell" /></button>
            <button className="icon-btn" aria-label="Settings"><Icon name="settings" /></button>
          </div>
        </div>

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

        <div className="admin-layout">
          <div className="admin-center" style={{paddingRight: '12px'}}>
            {activeTab === 'dashboard' && renderDashboard()}
            {activeTab === 'users' && renderUsers()}
            {activeTab === 'pending' && renderPendingUsers()}
            {activeTab === 'materials' && renderMaterials()}
            {activeTab === 'payments' && <AdminPaymentVerification />}
            {activeTab === 'reports' && (
              <div className="admin-support-container">
                <div className="admin-support-header">
                  <h1 className="admin-support-title">
                    🎧 Customer Support Issues
                  </h1>
                  <p className="admin-support-subtitle">
                    View and respond to customer support requests and issues
                  </p>
                  <div className="admin-support-stats">
                    <div className="admin-support-stat">
                      <div className="admin-support-stat-number">{supportIssues.length}</div>
                      <div className="admin-support-stat-label">Total Issues</div>
                    </div>
                    <div className="admin-support-stat">
                      <div className="admin-support-stat-number">
                        {supportIssues.filter(iss => iss.status === 'open').length}
                      </div>
                      <div className="admin-support-stat-label">Open Issues</div>
                    </div>
                    <div className="admin-support-stat">
                      <div className="admin-support-stat-number">
                        {supportIssues.filter(iss => iss.status === 'replied').length}
                      </div>
                      <div className="admin-support-stat-label">Replied</div>
                    </div>
                    <div className="admin-support-stat">
                      <div className="admin-support-stat-number">
                        {supportIssues.filter(iss => iss.reply_count > 0).length}
                      </div>
                      <div className="admin-support-stat-label">With Replies</div>
                    </div>
                  </div>
                </div>
                
                <div className="admin-support-content">
                  {/* Issues List Panel */}
                  <div className="admin-issues-panel">
                    <div className="admin-issues-header">
                      <h2 className="admin-issues-title">
                        📋 Customer Issues
                        <span className="admin-issues-count">{supportIssues.length}</span>
                      </h2>
                      <button className="admin-refresh-btn" onClick={loadSupportIssues}>
                        🔄 Refresh
                      </button>
                      <button 
                        className="admin-refresh-btn" 
                        onClick={async () => {
                          try {
                            const res = await fetch('/buildhub/backend/api/test_proxy.php');
                            const data = await res.json();
                            console.log('Proxy test result:', data);
                            alert('Proxy test: ' + (data.success ? 'SUCCESS' : 'FAILED'));
                          } catch (e) {
                            console.error('Proxy test failed:', e);
                            alert('Proxy test FAILED: ' + e.message);
                          }
                        }}
                        style={{ marginLeft: '8px' }}
                      >
                        🧪 Test API
                      </button>
                    </div>
                    
                    <div className="admin-issues-list">
                      {error && (
                        <div style={{ 
                          padding: '16px', 
                          background: '#fee2e2', 
                          border: '1px solid #fca5a5', 
                          borderRadius: '8px', 
                          margin: '16px',
                          color: '#dc2626'
                        }}>
                          <strong>Error:</strong> {error}
                        </div>
                      )}
                      
                      {supportLoading ? (
                        <div className="admin-loading">Loading customer issues...</div>
                      ) : supportIssues.length === 0 ? (
                        <div className="admin-empty-state">
                          <div className="admin-empty-icon">📝</div>
                          <h3 className="admin-empty-title">No Customer Issues</h3>
                          <p className="admin-empty-text">
                            {error ? 'There was an error loading issues. Check the console for details.' : 'Customer support requests will appear here when submitted.'}
                          </p>
                        </div>
                      ) : (
                        supportIssues.map(iss => (
                          <div 
                            key={iss.id} 
                            className={`admin-issue-item ${selectedIssue?.id === iss.id ? 'selected' : ''}`}
                            onClick={() => openSupportThread(iss.id)}
                          >
                            <div className="admin-issue-header">
                              <div className="admin-issue-priority">
                                {iss.category === 'bug' ? '🐛' : 
                                 iss.category === 'billing' ? '💳' : 
                                 iss.category === 'account' ? '👤' : 
                                 iss.category === 'feature' ? '✨' : '💬'}
                              </div>
                              <div className="admin-issue-content">
                                <h4 className="admin-issue-title">{iss.subject}</h4>
                                <p className="admin-issue-meta">
                                  <span className="admin-issue-customer">
                                    {iss.first_name || 'Unknown'} {iss.last_name || 'User'}
                                  </span>
                                  {iss.email && ` (${iss.email})`} • #{iss.id} • {iss.category}
                                  {iss.reply_count > 0 && ` • ${iss.reply_count} replies`}
                                </p>
                                <p className="admin-issue-time">
                                  {new Date(iss.created_at).toLocaleDateString()} at {new Date(iss.created_at).toLocaleTimeString()}
                                </p>
                              </div>
                            </div>
                            <div className="admin-issue-status">
                              <span className={`admin-status-badge ${iss.status}`}>
                                {iss.status}
                              </span>
                            </div>
                          </div>
                        ))
                      )}
                    </div>
                  </div>

                  {/* Thread Panel */}
                  <div className="admin-thread-panel">
                    {!selectedIssue ? (
                      <div className="admin-empty-state">
                        <div className="admin-empty-icon">💬</div>
                        <h3 className="admin-empty-title">Select a Customer Issue</h3>
                        <p className="admin-empty-text">
                          Choose an issue from the list to view the conversation and send replies.
                        </p>
                      </div>
                    ) : (
                      <>
                        <div className="admin-thread-header">
                          <h3 className="admin-thread-title">{selectedIssue.subject}</h3>
                          <div className="admin-thread-meta">
                            <div className="admin-thread-meta-item">
                              <span className="admin-thread-meta-label">Customer</span>
                              <span className="admin-thread-meta-value">
                                {selectedIssue.first_name || 'Unknown'} {selectedIssue.last_name || 'User'}
                              </span>
                            </div>
                            <div className="admin-thread-meta-item">
                              <span className="admin-thread-meta-label">Email</span>
                              <span className="admin-thread-meta-value">{selectedIssue.email || 'Not provided'}</span>
                            </div>
                            <div className="admin-thread-meta-item">
                              <span className="admin-thread-meta-label">Category</span>
                              <span className="admin-thread-meta-value">{selectedIssue.category}</span>
                            </div>
                            <div className="admin-thread-meta-item">
                              <span className="admin-thread-meta-label">Status</span>
                              <span className="admin-thread-meta-value">
                                <span className={`admin-status-badge ${selectedIssue.status}`}>
                                  {selectedIssue.status}
                                </span>
                              </span>
                            </div>
                          </div>
                        </div>
                        
                        <div className="admin-thread-messages">
                          <div className="admin-message customer">
                            <div className="admin-message-header">Customer Issue</div>
                            <div className="admin-message-body">{selectedIssue.message}</div>
                            <div className="admin-message-time">
                              {new Date(selectedIssue.created_at).toLocaleDateString()} at {new Date(selectedIssue.created_at).toLocaleTimeString()}
                            </div>
                          </div>
                          
                          {issueReplies.map(r => (
                            <div key={r.id} className={`admin-message ${r.sender === 'admin' ? 'admin' : 'customer'}`}>
                              <div className="admin-message-header">
                                {r.sender === 'admin' ? 'Admin Support' : 'Customer'}
                              </div>
                              <div className="admin-message-body">{r.message}</div>
                              <div className="admin-message-time">
                                {new Date(r.created_at).toLocaleDateString()} at {new Date(r.created_at).toLocaleTimeString()}
                              </div>
                            </div>
                          ))}
                          
                          {issueReplies.length === 0 && selectedIssue.status === 'open' && (
                            <div className="admin-empty-state">
                              <div className="admin-empty-icon">⏳</div>
                              <h3 className="admin-empty-title">No Replies Yet</h3>
                              <p className="admin-empty-text">
                                This customer issue is waiting for your response. Use the reply section below to help the customer.
                              </p>
                            </div>
                          )}
                        </div>
                        
                        <div className="admin-reply-section">
                          <label className="admin-reply-label">Reply to Customer</label>
                          <textarea 
                            className="admin-reply-textarea"
                            rows="4" 
                            value={adminReply} 
                            onChange={(e) => setAdminReply(e.target.value)} 
                            placeholder="Type your response to help the customer resolve their issue..."
                          />
                          <div className="admin-reply-actions">
                            <button 
                              className="admin-reply-btn" 
                              onClick={sendAdminReply}
                              disabled={!adminReply.trim() || supportLoading}
                            >
                              {supportLoading ? '📤 Sending...' : '📤 Send Reply'}
                            </button>
                            <span style={{ fontSize: '14px', color: '#64748b' }}>
                              Customer will be notified of your response
                            </span>
                          </div>
                        </div>
                      </>
                    )}
                  </div>
                </div>
              </div>
            )}
            {/* 3D view moved to Homeowner Dashboard */}
          </div>
          <aside className="admin-right-rail">
            <div className="profile-card">
              <div className="profile-header">
                <img className="profile-avatar" alt="avatar" src={`https://ui-avatars.com/api/?name=${encodeURIComponent(adminName)}&background=7c3aed&color=fff`} />
                <button className="icon-btn close-btn" aria-label="Close">×</button>
              </div>
              <h3 className="profile-name">{adminName}</h3>
              <p className="profile-sub">Financial analytics</p>
              <div className="profit-card">
                <div className="mini-chart" aria-hidden="true"></div>
                <div className="profit-values">
                  <span className="label">Total Users</span>
                  <span className="value">{systemStats.totalUsers || 0}</span>
                </div>
              </div>
              <div className="profit-card" style={{marginTop:'10px'}}>
                <div className="mini-chart" aria-hidden="true"></div>
                <div className="profit-values">
                  <span className="label">Active Projects</span>
                  <span className="value">{systemStats.activeProjects || 0}</span>
                </div>
              </div>
              <div className="recent-activities">
                <h4>Recent activities</h4>
                <ul>
                  {pendingUsers.slice(0,3).map(u => (
                    <li key={u.id}><span className="act-icon up"/> {u.first_name} {u.last_name} <span className="amount">pending</span></li>
                  ))}
                  {pendingUsers.length === 0 && (
                    <li><span className="act-icon down"/> No pending users <span className="amount">—</span></li>
                  )}
                </ul>
              </div>
              
              {/* Logout Button */}
              <div className="profile-actions" style={{marginTop: '20px', paddingTop: '15px', borderTop: '1px solid #e5e7eb'}}>
                <button 
                  onClick={handleLogout}
                  className="logout-btn"
                  style={{
                    width: '100%',
                    padding: '10px 16px',
                    backgroundColor: '#dc2626',
                    color: 'white',
                    border: 'none',
                    borderRadius: '8px',
                    fontSize: '14px',
                    fontWeight: '500',
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '8px',
                    transition: 'all 0.2s ease'
                  }}
                  onMouseOver={(e) => {
                    e.currentTarget.style.backgroundColor = '#b91c1c';
                    e.currentTarget.style.transform = 'translateY(-1px)';
                  }}
                  onMouseOut={(e) => {
                    e.currentTarget.style.backgroundColor = '#dc2626';
                    e.currentTarget.style.transform = 'translateY(0)';
                  }}
                >
                  <Icon name="logout" />
                  Logout
                </button>
              </div>
            </div>
          </aside>
        </div>

        {/* OCR Verification Modal */}
        <OCRVerificationModal
          user={selectedUserForOCR}
          isOpen={ocrModalOpen}
          onClose={closeOCRModal}
          onVerificationComplete={handleOCRVerificationComplete}
        />
      </div>
    </div>
  );
};

// User Card Component
const UserCard = ({ user, onApprove, onReject, onViewDocument, onDownloadDocument, onTriggerOCR, loading }) => (
  <div className="user-card">
    <div className="user-header">
      <div className="user-avatar">
        {user.profile_image ? (
          <img 
            src={user.profile_image} 
            alt={`${user.first_name} ${user.last_name}`}
            style={{width: '100%', height: '100%', objectFit: 'cover', borderRadius: '50%'}}
          />
        ) : (
          <div style={{width: '100%', height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: '600', fontSize: '16px', color: '#fff'}}>
            {user.first_name?.charAt(0)}{user.last_name?.charAt(0)}
          </div>
        )}
      </div>
      <div className="user-basic-info">
        <h4>{user.first_name} {user.last_name}</h4>
        <p className="user-role">{user.role}</p>
      </div>
    </div>
    
    <div className="user-details">
      <div className="detail-item">
        <span className="detail-label">Email:</span>
        <span className="detail-value">{user.email}</span>
      </div>
      <div className="detail-item">
        <span className="detail-label">Registered:</span>
        <span className="detail-value">{new Date(user.created_at).toLocaleDateString()}</span>
      </div>
    </div>
    
    <div className="user-documents">
      <h5>Documents:</h5>
      {user.role === 'contractor' && user.license && (
        <div className="document-item">
          <span className="doc-label"><Icon name="file" /> License</span>
          <div className="document-actions">
            <button 
              className="btn btn-secondary btn-sm"
              onClick={() => onViewDocument(user.id, 'license')}
            >
              <Icon name="eye" /> View
            </button>
            <button 
              className="btn btn-secondary btn-sm"
              onClick={() => onDownloadDocument(user.id, 'license')}
            >
              <Icon name="download" /> Download
            </button>
          </div>
        </div>
      )}
      {user.role === 'architect' && user.portfolio && (
        <div className="document-item">
          <span className="doc-label"><Icon name="file" /> Portfolio</span>
          <div className="document-actions">
            <button 
              className="btn btn-secondary btn-sm"
              onClick={() => onViewDocument(user.id, 'portfolio')}
            >
              <Icon name="eye" /> View
            </button>
            <button 
              className="btn btn-secondary btn-sm"
              onClick={() => onDownloadDocument(user.id, 'portfolio')}
            >
              <Icon name="download" /> Download
            </button>
          </div>
        </div>
      )}
      {!user.license && !user.portfolio && (
        <p className="no-documents">No documents uploaded</p>
      )}
    </div>
    
    {/* OCR Results Section */}
    {user.has_ocr && user.ocr_id && (
      <div className="ocr-results" style={{
        marginTop: '15px',
        padding: '12px',
        backgroundColor: '#f8f9fa',
        borderRadius: '8px',
        border: '1px solid #e0e0e0'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' }}>
          <h5 style={{ fontSize: '14px', fontWeight: '600', color: '#333', margin: 0 }}>
            📄 OCR Verification Results
          </h5>
          <div>
            <button
              className="btn btn-primary btn-sm"
              onClick={() => {
                const pdfUrl = `/buildhub/backend/api/admin/download_ocr_results_pdf.php?user_id=${user.id}`;
                window.open(pdfUrl, '_blank');
              }}
              style={{ fontSize: '11px', padding: '5px 10px', fontWeight: '600' }}
              title="Download as PDF"
            >
              📄 Download PDF
            </button>
          </div>
        </div>
        
        {/* Confidence Level */}
        <div style={{ marginBottom: '10px' }}>
          <span style={{ fontSize: '12px', color: '#666', fontWeight: '500' }}>Confidence: </span>
          <span style={{
            padding: '4px 8px',
            borderRadius: '4px',
            fontSize: '11px',
            fontWeight: '600',
            backgroundColor: user.confidence_level === 'HIGH' ? '#d4edda' : 
                           user.confidence_level === 'MEDIUM' ? '#fff3cd' : '#f8d7da',
            color: user.confidence_level === 'HIGH' ? '#155724' : 
                   user.confidence_level === 'MEDIUM' ? '#856404' : '#721c24'
          }}>
            {user.confidence_level || 'LOW'}
          </span>
        </div>
        
        {/* Extracted Fields */}
        {user.extracted_fields && Object.keys(user.extracted_fields).length > 0 && (
          <div style={{ marginBottom: '10px' }}>
            <div style={{ fontSize: '12px', color: '#666', fontWeight: '500', marginBottom: '6px' }}>
              Extracted Information:
            </div>
            <div style={{ fontSize: '11px', color: '#555' }}>
              {Object.entries(user.extracted_fields).map(([key, value]) => (
                <div key={key} style={{ marginBottom: '4px', paddingLeft: '8px' }}>
                  <strong style={{ textTransform: 'capitalize' }}>{key.replace('_', ' ')}:</strong> {value}
                </div>
              ))}
            </div>
          </div>
        )}
        
        {(!user.extracted_fields || Object.keys(user.extracted_fields).length === 0) && (
          <div style={{ fontSize: '11px', color: '#999', fontStyle: 'italic' }}>
            No fields extracted (document may be unclear or unreadable)
          </div>
        )}
        
        {user.ocr_processed_at && (
          <div style={{ fontSize: '10px', color: '#999', marginTop: '8px' }}>
            Processed: {new Date(user.ocr_processed_at).toLocaleString()}
          </div>
        )}
      </div>
    )}
    
    {!user.has_ocr && (user.license || user.portfolio) && (
      <div style={{
        marginTop: '10px',
        padding: '8px',
        backgroundColor: '#fff3cd',
        borderRadius: '6px',
        fontSize: '11px',
        color: '#856404',
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center'
      }}>
        <span>⏳ OCR processing pending or not available</span>
        <button 
          className="btn btn-secondary btn-sm"
          onClick={() => {
            if (window.confirm('Trigger OCR processing for this document?')) {
              // This will be passed from parent
              if (onTriggerOCR) onTriggerOCR(user.id);
            }
          }}
          style={{ fontSize: '10px', padding: '4px 8px' }}
        >
          🔄 Process OCR
        </button>
      </div>
    )}
    
    <div className="user-actions">
      {user.has_ocr && user.ocr_id && (
        <button 
          className="btn btn-info"
          onClick={() => onTriggerOCR && onTriggerOCR(user)}
          disabled={loading}
          style={{ marginBottom: '8px', width: '100%' }}
        >
          🔍 Verify OCR Data
        </button>
      )}
      <button 
        className="btn btn-success"
        onClick={onApprove}
        disabled={loading}
      >
        <Icon name="check" /> Approve
      </button>
      <button 
        className="btn btn-danger"
        onClick={onReject}
        disabled={loading}
      >
        <Icon name="x" /> Reject
      </button>
    </div>
  </div>
);

// User Table Row Component
const UserTableRow = ({ user, onViewDetails, onStatusChange, onViewDocument, onDownloadDocument, onDeleteUser }) => (
  <div className="table-row">
    <div className="table-cell user-info-cell">
      <div className="user-avatar-small">
        {user.profile_image ? (
          <img 
            src={user.profile_image} 
            alt={`${user.first_name} ${user.last_name}`}
            style={{width: '100%', height: '100%', objectFit: 'cover', borderRadius: '50%'}}
          />
        ) : (
          <div style={{width: '100%', height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: '600', fontSize: '12px', color: '#fff'}}>
            {user.first_name?.charAt(0)}{user.last_name?.charAt(0)}
          </div>
        )}
      </div>
      <div className="user-basic-info">
        <h5>{user.first_name} {user.last_name}</h5>
        <p>{user.email}</p>
        {user.phone && <p>{user.phone}</p>}
      </div>
    </div>
    <div className="table-cell">
      <span className={`role-badge ${user.role}`}>
        {user.role}
      </span>
    </div>
    <div className="table-cell">
      <span className={`status-badge ${user.status}`}>
        {user.status || (user.is_verified ? 'approved' : 'pending')}
      </span>
    </div>
    <div className="table-cell">
      {new Date(user.created_at).toLocaleDateString()}
    </div>
    <div className="table-cell actions-cell">
      <div className="action-buttons">
        <button 
          className="btn btn-secondary btn-sm"
          onClick={onViewDetails}
        >
          👁️ View
        </button>
        {/* Suspend/Unsuspend toggle */}
        {user.status === 'suspended' ? (
          <button 
            className="btn btn-success btn-sm"
            onClick={() => onStatusChange('approved')}
          >
            ✅ Unsuspend
          </button>
        ) : (
          <button 
            className="btn btn-danger btn-sm"
            onClick={() => onStatusChange('suspended')}
          >
            🚫 Suspend
          </button>
        )}
        <button 
          className="btn btn-danger btn-sm"
          onClick={() => onDeleteUser(user.id)}
          style={{backgroundColor:'#991b1b'}}
        >
          🗑️ Delete
        </button>
      </div>
    </div>
  </div>
);

// User Details Modal Component
const UserDetailsModal = ({ user, onStatusChange, onViewDocument, onDownloadDocument, onDeleteUser, onClose }) => (
  <div className="user-details-modal">
    <div className="user-profile-section">
      <div className="user-avatar-large">
        {user.profile_image ? (
          <img 
            src={user.profile_image} 
            alt={`${user.first_name} ${user.last_name}`}
            style={{width: '100%', height: '100%', objectFit: 'cover', borderRadius: '50%'}}
          />
        ) : (
          <div style={{width: '100%', height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: '700', fontSize: '32px', color: '#fff'}}>
            {user.first_name?.charAt(0)}{user.last_name?.charAt(0)}
          </div>
        )}
      </div>
      <div className="user-profile-info">
        <h2>{user.first_name} {user.last_name}</h2>
        <p className="user-role-large">{user.role}</p>
        <span className={`status-badge-large ${user.status || (user.is_verified ? 'approved' : 'pending')}`}>
          {user.status || (user.is_verified ? 'approved' : 'pending')}
        </span>
      </div>
    </div>

    <div className="user-details-grid">
      <div className="detail-section">
        <h4>Contact Information</h4>
        <div className="detail-item">
          <span className="detail-label">Email:</span>
          <span className="detail-value">{user.email}</span>
        </div>
        {user.phone && (
          <div className="detail-item">
            <span className="detail-label">Phone:</span>
            <span className="detail-value">{user.phone}</span>
          </div>
        )}
        {user.address && (
          <div className="detail-item">
            <span className="detail-label">Address:</span>
            <span className="detail-value">{user.address}</span>
          </div>
        )}
      </div>

      <div className="detail-section">
        <h4>Account Information</h4>
        <div className="detail-item">
          <span className="detail-label">User ID:</span>
          <span className="detail-value">#{user.id}</span>
        </div>
        <div className="detail-item">
          <span className="detail-label">Registered:</span>
          <span className="detail-value">{new Date(user.created_at).toLocaleDateString()}</span>
        </div>
        <div className="detail-item">
          <span className="detail-label">Last Updated:</span>
          <span className="detail-value">{new Date(user.updated_at).toLocaleDateString()}</span>
        </div>
      </div>

      {(user.role === 'contractor' || user.role === 'architect') && (
        <div className="detail-section">
          <h4>Professional Information</h4>
          {user.company_name && (
            <div className="detail-item">
              <span className="detail-label">Company:</span>
              <span className="detail-value">{user.company_name}</span>
            </div>
          )}
          {user.experience_years && (
            <div className="detail-item">
              <span className="detail-label">Experience:</span>
              <span className="detail-value">{user.experience_years} years</span>
            </div>
          )}
          {user.specialization && (
            <div className="detail-item">
              <span className="detail-label">Specialization:</span>
              <span className="detail-value">{user.specialization}</span>
            </div>
          )}
        </div>
      )}

      <div className="detail-section">
        <h4>Documents</h4>
        {user.role === 'contractor' && user.license && (
          <div className="document-item">
            <span>📄 License Document</span>
            <div className="document-actions">
              <button 
                className="btn btn-secondary btn-sm"
                onClick={() => onViewDocument(user.id, 'license')}
              >
                👁️ View
              </button>
              <button 
                className="btn btn-secondary btn-sm"
                onClick={() => onDownloadDocument(user.id, 'license')}
              >
                📥 Download
              </button>
            </div>
          </div>
        )}
        {user.role === 'architect' && user.portfolio && (
          <div className="document-item">
            <span>📁 Portfolio Document</span>
            <div className="document-actions">
              <button 
                className="btn btn-secondary btn-sm"
                onClick={() => onViewDocument(user.id, 'portfolio')}
              >
                👁️ View
              </button>
              <button 
                className="btn btn-secondary btn-sm"
                onClick={() => onDownloadDocument(user.id, 'portfolio')}
              >
                📥 Download
              </button>
            </div>
          </div>
        )}
        {!user.license && !user.portfolio && (
          <p className="no-documents">No documents uploaded</p>
        )}
      </div>
    </div>

    <div className="modal-actions">
      {user.status === 'pending' && (
        <>
          <button 
            className="btn btn-success"
            onClick={() => onStatusChange('approved')}
          >
            ✅ Approve User
          </button>
          <button 
            className="btn btn-danger"
            onClick={() => onStatusChange('rejected')}
          >
            ❌ Reject User
          </button>
        </>
      )}
      {user.status === 'approved' && (
        <button 
          className="btn btn-danger"
          onClick={() => onStatusChange('suspended')}
        >
          🚫 Suspend User
        </button>
      )}
      {user.status === 'suspended' && (
        <button 
          className="btn btn-success"
          onClick={() => onStatusChange('approved')}
        >
          ✅ Unsuspend User
        </button>
      )}
      <button 
        className="btn btn-danger"
        onClick={onDeleteUser}
        style={{backgroundColor:'#991b1b'}}
      >
        🗑️ Delete User
      </button>
      <button 
        className="btn btn-secondary"
        onClick={onClose}
      >
        Close
      </button>
    </div>
  </div>
);

// Material Card Component
const MaterialCard = ({ material, onDelete }) => (
  <div className="material-card">
    <div className="material-header">
      <h4>{material.name}</h4>
      <span className="material-category">{material.category}</span>
    </div>
    <div className="material-details">
      <div className="detail-item">
        <span className="detail-label">Unit:</span>
        <span className="detail-value">{material.unit}</span>
      </div>
      <div className="detail-item">
        <span className="detail-label">Price:</span>
        <span className="detail-value">₹{material.price}</span>
      </div>
      {material.description && (
        <div className="material-description">
          <p>{material.description}</p>
        </div>
      )}
    </div>
    <div className="material-actions">
      <button 
        className="btn btn-danger btn-sm"
        onClick={onDelete}
      >
        🗑️ Delete
      </button>
    </div>
  </div>
);

export default AdminDashboard;