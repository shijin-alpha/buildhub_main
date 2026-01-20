import React, { useState, useEffect } from 'react';
import '../styles/MessageCenter.css';

const MessageCenter = ({ isOpen, onClose, userId }) => {
  const [activeTab, setActiveTab] = useState('notifications');
  const [notifications, setNotifications] = useState([]);
  const [messages, setMessages] = useState([]);
  const [threads, setThreads] = useState([]);
  const [selectedThread, setSelectedThread] = useState(null);
  const [loading, setLoading] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);

  console.log('MessageCenter render - isOpen:', isOpen, 'userId:', userId);

  useEffect(() => {
    if (isOpen && userId) {
      console.log('MessageCenter opened, loading data...');
      loadNotifications();
      loadMessages();
    }
  }, [isOpen, userId]);

  const loadNotifications = async () => {
    setLoading(true);
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/get_notifications.php', {
        credentials: 'include'
      });
      const data = await response.json();
      if (data.success) {
        setNotifications(data.notifications || []);
        setUnreadCount(data.unread_count || 0);
      }
    } catch (error) {
      console.error('Error loading notifications:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadMessages = async () => {
    try {
      const response = await fetch('/buildhub/backend/api/homeowner/get_messages.php', {
        credentials: 'include'
      });
      const data = await response.json();
      if (data.success) {
        setMessages(data.messages || []);
        setThreads(data.threads || []);
      }
    } catch (error) {
      console.error('Error loading messages:', error);
    }
  };

  const markAsRead = async (notificationIds, sources = null) => {
    try {
      const payload = { notification_ids: notificationIds };
      if (sources) {
        payload.source = sources;
      }
      
      await fetch('/buildhub/backend/api/homeowner/mark_notifications_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(payload)
      });
      loadNotifications();
    } catch (error) {
      console.error('Error marking as read:', error);
    }
  };

  const getNotificationIcon = (type) => {
    switch (type) {
      case 'acknowledgment': return '✅';
      case 'estimate_received': return '💰';
      case 'layout_approved': return '✅';
      case 'construction_started': return '🏗️';
      case 'message_sent': return '📤';
      case 'message_received': return '📥';
      case 'payment_required': return '💳';
      case 'project_completed': return '🎉';
      case 'contractor_acknowledgment': return '🤝';
      default: return '📢';
    }
  };

  const formatTimeAgo = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;
    return date.toLocaleDateString();
  };

  if (!isOpen) return null;

  return (
    <div className="message-center-overlay" onClick={onClose}>
      <div className="message-center" onClick={(e) => e.stopPropagation()}>
        <div className="message-center-header">
          <h2>
            {activeTab === 'notifications' ? '🔔 Notifications' : '💬 Messages'}
            {unreadCount > 0 && (
              <span className="unread-badge">{unreadCount}</span>
            )}
          </h2>
          <button className="close-btn" onClick={onClose}>×</button>
        </div>

        <div className="message-center-tabs">
          <button 
            className={`tab-btn ${activeTab === 'notifications' ? 'active' : ''}`}
            onClick={() => setActiveTab('notifications')}
          >
            🔔 Notifications
            {unreadCount > 0 && <span className="tab-badge">{unreadCount}</span>}
          </button>
          <button 
            className={`tab-btn ${activeTab === 'messages' ? 'active' : ''}`}
            onClick={() => setActiveTab('messages')}
          >
            💬 Messages
            {threads.reduce((sum, thread) => sum + (thread.unread_count || 0), 0) > 0 && (
              <span className="tab-badge">
                {threads.reduce((sum, thread) => sum + (thread.unread_count || 0), 0)}
              </span>
            )}
          </button>
        </div>

        <div className="message-center-content">
          {loading ? (
            <div className="loading-state">
              <div className="spinner"></div>
              <p>Loading...</p>
            </div>
          ) : activeTab === 'notifications' ? (
            <div className="notifications-list">
              {notifications.length === 0 ? (
                <div className="empty-state">
                  <div className="empty-icon">🔔</div>
                  <h3>No Notifications</h3>
                  <p>You're all caught up! New notifications will appear here.</p>
                </div>
              ) : (
                notifications.map((notification) => (
                  <div 
                    key={notification.id} 
                    className={`notification-item ${!notification.is_read ? 'unread' : ''}`}
                    onClick={() => {
                      if (!notification.is_read) {
                        markAsRead([notification.id], [notification.source]);
                      }
                    }}
                  >
                    <div className="notification-icon">
                      {getNotificationIcon(notification.type)}
                    </div>
                    <div className="notification-content">
                      <h4>{notification.title}</h4>
                      <p>{notification.message}</p>
                      <span className="notification-time">
                        {formatTimeAgo(notification.created_at)}
                      </span>
                    </div>
                    {!notification.is_read && (
                      <div className="unread-indicator"></div>
                    )}
                  </div>
                ))
              )}
            </div>
          ) : (
            <div className="messages-section">
              {!selectedThread ? (
                <div className="threads-list">
                  {threads.length === 0 ? (
                    <div className="empty-state">
                      <div className="empty-icon">💬</div>
                      <h3>No Messages</h3>
                      <p>Your conversations with contractors and architects will appear here.</p>
                    </div>
                  ) : (
                    threads.map((thread) => (
                      <div 
                        key={thread.other_user_id} 
                        className={`thread-item ${thread.unread_count > 0 ? 'unread' : ''}`}
                        onClick={() => setSelectedThread(thread)}
                      >
                        <div className="thread-avatar">
                          {thread.first_name?.charAt(0)}{thread.last_name?.charAt(0)}
                        </div>
                        <div className="thread-content">
                          <h4>{thread.first_name} {thread.last_name}</h4>
                          <p className="thread-role">{thread.role}</p>
                          <span className="thread-time">
                            {formatTimeAgo(thread.last_message_time)}
                          </span>
                        </div>
                        {thread.unread_count > 0 && (
                          <div className="thread-unread-count">
                            {thread.unread_count}
                          </div>
                        )}
                      </div>
                    ))
                  )}
                </div>
              ) : (
                <div className="thread-view">
                  <div className="thread-header">
                    <button 
                      className="back-btn" 
                      onClick={() => setSelectedThread(null)}
                    >
                      ← Back
                    </button>
                    <h3>{selectedThread.first_name} {selectedThread.last_name}</h3>
                    <span className="thread-role-badge">{selectedThread.role}</span>
                  </div>
                  <div className="thread-messages">
                    {messages
                      .filter(msg => 
                        (msg.from_user_id === selectedThread.other_user_id && msg.to_user_id === userId) ||
                        (msg.from_user_id === userId && msg.to_user_id === selectedThread.other_user_id)
                      )
                      .map((message) => (
                        <div 
                          key={message.id} 
                          className={`message-item ${message.from_user_id === userId ? 'sent' : 'received'}`}
                        >
                          <div className="message-content">
                            <h5>{message.subject}</h5>
                            <p>{message.message}</p>
                            <span className="message-time">
                              {formatTimeAgo(message.created_at)}
                            </span>
                          </div>
                        </div>
                      ))
                    }
                  </div>
                </div>
              )}
            </div>
          )}
        </div>

        {activeTab === 'notifications' && notifications.some(n => !n.is_read) && (
          <div className="message-center-footer">
            <button 
              className="mark-all-read-btn"
              onClick={() => {
                const unreadNotifications = notifications.filter(n => !n.is_read);
                const unreadIds = unreadNotifications.map(n => n.id);
                const unreadSources = unreadNotifications.map(n => n.source);
                markAsRead(unreadIds, unreadSources);
              }}
            >
              Mark All as Read
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default MessageCenter;