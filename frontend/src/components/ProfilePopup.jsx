import React, { useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import './ProfilePopup.css';

const ProfilePopup = ({ isOpen, onClose, user, position = 'bottom-right' }) => {
  const popupRef = useRef(null);
  const navigate = useNavigate();

  // Close popup when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (popupRef.current && !popupRef.current.contains(event.target)) {
        onClose();
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isOpen, onClose]);

  // Handle profile setup navigation
  const handleProfileSetup = () => {
    onClose();
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

  // Handle logout
  const handleLogout = async () => {
    try {
      await fetch('/buildhub/backend/api/logout.php', { 
        method: 'POST', 
        credentials: 'include' 
      });
    } catch (error) {
      console.error('Logout error:', error);
    }
    
    localStorage.removeItem('bh_user');
    sessionStorage.removeItem('user');
    onClose();
    navigate('/login', { replace: true });
  };

  if (!isOpen) return null;

  return (
    <div 
      ref={popupRef}
      className={`profile-popup ${position}`}
    >
      <div className="profile-popup-content">
        {/* Profile Header */}
        <div className="profile-popup-header">
          <div className="profile-popup-avatar">
            {user?.first_name?.charAt(0)}{user?.last_name?.charAt(0)}
          </div>
          <div className="profile-popup-info">
            <div className="profile-popup-name">
              {user?.first_name} {user?.last_name}
            </div>
            <div className="profile-popup-role">
              {user?.role?.charAt(0).toUpperCase() + user?.role?.slice(1)}
            </div>
          </div>
        </div>

        {/* Divider */}
        <div className="profile-popup-divider"></div>

        {/* Menu Items */}
        <div className="profile-popup-menu">
          <button 
            className="profile-popup-item"
            onClick={handleProfileSetup}
          >
            <span className="profile-popup-icon">⚙️</span>
            <span>Profile Setup</span>
          </button>
          
          <button 
            className="profile-popup-item logout"
            onClick={handleLogout}
          >
            <span className="profile-popup-icon">🚪</span>
            <span>Logout</span>
          </button>
        </div>
      </div>
    </div>
  );
};

export default ProfilePopup;




