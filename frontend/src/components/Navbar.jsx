import React, { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';

const Navbar = () => {
  const [open, setOpen] = useState(false);
  const menuRef = useRef(null);
  const navigate = useNavigate();

  const user = useMemo(() => {
    try {
      const raw = localStorage.getItem('bh_user');
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  }, []);

  // Close dropdown on outside click
  useEffect(() => {
    const onDocClick = (e) => {
      if (menuRef.current && !menuRef.current.contains(e.target)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', onDocClick);
    return () => document.removeEventListener('mousedown', onDocClick);
  }, []);

  const handleProfile = () => {
    setOpen(false);
    if (user?.role === 'homeowner') {
      navigate('/homeowner/profile');
    } else if (user?.role === 'contractor') {
      navigate('/contractor-dashboard');
    } else if (user?.role === 'architect') {
      navigate('/architect-dashboard');
    } else {
      navigate('/profile');
    }
  };

  const handleLogout = async () => {
    try { await fetch('/buildhub/backend/api/logout.php', { method: 'POST', credentials: 'include' }); } catch {}
    localStorage.removeItem('bh_user');
    sessionStorage.removeItem('user');
    setOpen(false);
    navigate('/login', { replace: true });
  };

  const getInitials = () => {
    const name = (user?.name || '').trim();
    if (name) {
      const parts = name.split(/\s+/).filter(Boolean);
      const first = parts[0]?.[0] || '';
      const last = parts[parts.length - 1]?.[0] || '';
      return (first + last).toUpperCase() || 'U';
    }
    const email = (user?.email || '').trim();
    if (email) return email[0].toUpperCase();
    return 'U';
  };

  return (
    <div ref={menuRef} style={{ position: 'fixed', top: 12, right: 12, zIndex: 1000 }}>
      <button
        aria-label="Open profile menu"
        onClick={() => setOpen((v) => !v)}
        style={{
          width: 36,
          height: 36,
          borderRadius: '50%',
          border: '1px solid #e5e7eb',
          background: '#fff',
          boxShadow: '0 1px 3px rgba(0,0,0,0.08)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          padding: 0,
          cursor: 'pointer'
        }}
      >
        <span style={{ fontWeight: 700, fontSize: 12, color: '#374151' }}>{getInitials()}</span>
      </button>

      {open && (
        <div
          role="menu"
          style={{
            position: 'absolute',
            top: 44,
            right: 0,
            width: 180,
            background: '#fff',
            border: '1px solid #e5e7eb',
            borderRadius: 8,
            boxShadow: '0 8px 24px rgba(0,0,0,0.12)',
            padding: 8
          }}
        >
          <div style={{ fontSize: 12, color: '#6b7280', padding: '6px 8px' }}>My Account</div>
          <button
            onClick={handleProfile}
            style={{
              width: '100%',
              display: 'flex',
              alignItems: 'center',
              gap: 8,
              background: 'transparent',
              border: 'none',
              textAlign: 'left',
              padding: '8px 10px',
              borderRadius: 6,
              cursor: 'pointer'
            }}
          >
            <span role="img" aria-label="profile">👤</span>
            <span>Profile</span>
          </button>
          <div style={{ height: 1, background: '#f1f5f9', margin: '4px 0' }}></div>
          <button
            onClick={handleLogout}
            style={{
              width: '100%',
              display: 'flex',
              alignItems: 'center',
              gap: 8,
              background: 'transparent',
              border: 'none',
              textAlign: 'left',
              padding: '8px 10px',
              borderRadius: 6,
              cursor: 'pointer',
              color: '#b91c1c'
            }}
          >
            <span role="img" aria-label="logout">🚪</span>
            <span>Logout</span>
          </button>
        </div>
      )}
    </div>
  );
};

export default Navbar;