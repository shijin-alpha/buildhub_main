import React, { useState, useEffect } from 'react';
import '../styles/NotificationToast.css';

const NotificationToast = ({ notifications = [], onRemove }) => {
  // Ensure notifications is always an array
  const safeNotifications = Array.isArray(notifications) ? notifications : [];
  
  return (
    <div className="notification-container">
      {safeNotifications.map((notification) => (
        <div
          key={notification.id}
          className={`notification-toast ${notification.type}`}
          onClick={() => onRemove(notification.id)}
        >
          <div className="notification-icon">
            {notification.type === 'success' && '✅'}
            {notification.type === 'error' && '❌'}
            {notification.type === 'warning' && '⚠️'}
            {notification.type === 'info' && 'ℹ️'}
          </div>
          <div className="notification-content">
            <div className="notification-title">{notification.title}</div>
            {notification.message && (
              <div className="notification-message">{notification.message}</div>
            )}
          </div>
          <div className="notification-close">×</div>
        </div>
      ))}
    </div>
  );
};

export default NotificationToast;