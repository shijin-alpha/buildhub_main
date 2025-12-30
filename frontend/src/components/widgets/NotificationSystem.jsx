import React, { useState, useEffect } from 'react';
import { FaBell } from 'react-icons/fa';
import { MdAdminPanelSettings, MdDesignServices, MdConstruction, MdMessage, MdPayment, MdWarning, MdCheckCircle } from 'react-icons/md';
import MessageCenter from '../MessageCenter';
import '../../styles/Widgets.css';

const NotificationSystem = ({ userId }) => {
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [showNotifications, setShowNotifications] = useState(false);
  const [showMessageCenter, setShowMessageCenter] = useState(false);

  // Fetch notifications from API based on userId
  useEffect(() => {
    const fetchNotifications = async () => {
      if (!userId) {
        setNotifications([]);
        setUnreadCount(0);
        return;
      }
      
      try {
        // Use the real API endpoint
        const response = await fetch('/buildhub/backend/api/homeowner/get_notifications.php', {
          credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
          setNotifications(data.notifications || []);
          setUnreadCount(data.unread_count || 0);
        } else {
          console.error('Failed to fetch notifications:', data.message);
          setNotifications([]);
          setUnreadCount(0);
        }
      } catch (error) {
        console.error('Error fetching notifications:', error);
        setNotifications([]);
        setUnreadCount(0);
      }
    };

    fetchNotifications();
    
    // Set up polling for new notifications
    const intervalId = setInterval(() => {
      fetchNotifications();
    }, 30000); // Check every 30 seconds
    
    return () => clearInterval(intervalId);
  }, [userId]);

  // Mark a notification as read
  const markAsRead = async (id) => {
    try {
      await fetch('/buildhub/backend/api/homeowner/mark_notifications_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ notification_ids: [id] })
      });
      
      setNotifications(notifications.map(notification => 
        notification.id === id ? { ...notification, is_read: true } : notification
      ));
      setUnreadCount(prev => Math.max(0, prev - 1));
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  };

  // Mark all notifications as read
  const markAllAsRead = async () => {
    try {
      await fetch('/buildhub/backend/api/homeowner/mark_notifications_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({}) // Empty body marks all as read
      });
      
      setNotifications(notifications.map(notification => ({ ...notification, is_read: true })));
      setUnreadCount(0);
    } catch (error) {
      console.error('Error marking all notifications as read:', error);
    }
  };

  // Get icon based on notification type
  const getNotificationIcon = (type) => {
    switch(type) {
      case 'design': return <MdDesignServices />;
      case 'project': return <MdConstruction />;
      case 'request': return <MdMessage />;
      case 'message': return <MdMessage />;
      case 'admin': return <MdAdminPanelSettings />;
      case 'payment': return <MdPayment />;
      case 'alert': return <MdWarning />;
      case 'success': return <MdCheckCircle />;
      default: return <FaBell />;
    }
  };
  
  // Get color based on notification type
  const getNotificationColor = (type) => {
    switch(type) {
      case 'design': return '#4f46e5'; // indigo
      case 'project': return '#0891b2'; // cyan
      case 'request': return '#ca8a04'; // yellow
      case 'message': return '#16a34a'; // green
      case 'admin': return '#9333ea'; // purple
      case 'payment': return '#15803d'; // green
      case 'alert': return '#dc2626'; // red
      case 'success': return '#16a34a'; // green
      default: return '#6b7280'; // gray
    }
  };

  // Format timestamp to relative time
  const formatTimeAgo = (timestamp) => {
    const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000);
    
    let interval = Math.floor(seconds / 31536000);
    if (interval >= 1) return `${interval} year${interval === 1 ? '' : 's'} ago`;
    
    interval = Math.floor(seconds / 2592000);
    if (interval >= 1) return `${interval} month${interval === 1 ? '' : 's'} ago`;
    
    interval = Math.floor(seconds / 86400);
    if (interval >= 1) return `${interval} day${interval === 1 ? '' : 's'} ago`;
    
    interval = Math.floor(seconds / 3600);
    if (interval >= 1) return `${interval} hour${interval === 1 ? '' : 's'} ago`;
    
    interval = Math.floor(seconds / 60);
    if (interval >= 1) return `${interval} minute${interval === 1 ? '' : 's'} ago`;
    
    return `${Math.floor(seconds)} second${seconds === 1 ? '' : 's'} ago`;
  };

  // This function would be implemented when the API is ready
  // It would handle marking notifications as read and updating the UI

  // Request notification permission
  useEffect(() => {
    if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
      Notification.requestPermission();
    }
  }, []);

  return (
    <div className="notification-system">
      <div className="notification-bell" onClick={() => setShowMessageCenter(true)}>
        <span className="bell-icon"><FaBell /></span>
        {unreadCount > 0 && <span className="notification-badge">{unreadCount}</span>}
      </div>
      
      {/* Use the new MessageCenter component */}
      <MessageCenter
        isOpen={showMessageCenter}
        onClose={() => setShowMessageCenter(false)}
        userId={userId}
      />
    </div>
  );
};

export default NotificationSystem;