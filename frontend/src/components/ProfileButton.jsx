import React, { useState, useRef, useEffect } from 'react';
import ProfilePopup from './ProfilePopup';
import './ProfileButton.css';

const ProfileButton = ({ 
  user, 
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
    return 'U';
  };

  const getRoleDisplay = () => {
    if (!showRole) return null;
    const role = user?.role || 'User';
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

      <ProfilePopup
        isOpen={isPopupOpen}
        onClose={handlePopupClose}
        user={user}
        position={position}
      />
    </div>
  );
};

export default ProfileButton;
