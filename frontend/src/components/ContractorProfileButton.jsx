import React, { useState, useRef, useEffect } from 'react';
import './ProfileButton.css';
import './ProfilePopup.css';

const ContractorProfileButton = ({ 
  user, 
  onLogout,
  onProfileClick,
  className = '', 
  showRole = true, 
  size = 'medium',
  position = 'bottom-right' 
}) => {
  const [isPopupOpen, setIsPopupOpen] = useState(false);
  const buttonRef = useRef(null);

  // Close popup when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (buttonRef.current && !buttonRef.current.contains(event.target)) {
        setIsPopupOpen(false);
      }
    };

    if (isPopupOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isPopupOpen]);

  const handleButtonClick = () => {
    setIsPopupOpen(!isPopupOpen);
  };

  const handlePopupClose = () => {
    setIsPopupOpen(false);
  };

  const handleProfileSetup = () => {
    setIsPopupOpen(false);
    if (onProfileClick) {
      onProfileClick();
    }
  };

  const handleLogout = () => {
    setIsPopupOpen(false);
    if (onLogout) {
      onLogout();
    }
  };

  const getInitials = () => {
    if (user?.first_name && user?.last_name) {
      return `${user.first_name.charAt(0)}${user.last_name.charAt(0)}`.toUpperCase();
    }
    if (user?.name) {
      const parts = user.name.split(' ');
      return `${parts[0]?.[0] || ''}${parts[1]?.[0] || ''}`.toUpperCase();
    }
    if (user?.email) {
      return user.email.charAt(0).toUpperCase();
    }
    return 'C';
  };

  const getRoleDisplay = () => {
    if (!showRole) return null;
    const role = user?.role || 'Contractor';
    return role.charAt(0).toUpperCase() + role.slice(1);
  };

  return (
    <div ref={buttonRef} className={`profile-button-container ${className}`}>
      <button 
        className={`profile-button ${size}`}
        onClick={handleButtonClick}
        aria-haspopup="menu"
        aria-expanded={isPopupOpen}
      >
        <div className="profile-avatar">
          {user?.avatar_url ? (
            <img src={user.avatar_url} alt="Profile" style={{width: '100%', height: '100%', borderRadius: '50%', objectFit: 'cover'}} />
          ) : (
            getInitials()
          )}
        </div>
        <div className="profile-info">
          {showRole && (
            <div className="profile-role">
              {getRoleDisplay()}
            </div>
          )}
        </div>
        <div className={`profile-arrow ${isPopupOpen ? 'open' : ''}`}>
          ▼
        </div>
      </button>

      <ContractorProfilePopup
        isOpen={isPopupOpen}
        onClose={handlePopupClose}
        user={user}
        position={position}
        onProfileSetup={handleProfileSetup}
        onLogout={handleLogout}
      />
    </div>
  );
};

// Custom ProfilePopup for Contractor Dashboard
const ContractorProfilePopup = ({ isOpen, onClose, user, position, onProfileSetup, onLogout }) => {
  const popupRef = useRef(null);

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
            {user?.avatar_url ? (
              <img src={user.avatar_url} alt="Profile" style={{width: '100%', height: '100%', borderRadius: '50%', objectFit: 'cover'}} />
            ) : (
              user?.first_name?.charAt(0) + user?.last_name?.charAt(0)
            )}
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
            onClick={onProfileSetup}
          >
            <span className="profile-popup-icon">⚙️</span>
            <span>Profile Settings</span>
          </button>
          
          <button 
            className="profile-popup-item logout"
            onClick={onLogout}
          >
            <span className="profile-popup-icon">🚪</span>
            <span>Logout</span>
          </button>
        </div>
      </div>
    </div>
  );
};

export default ContractorProfileButton;
