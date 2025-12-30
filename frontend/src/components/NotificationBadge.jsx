import React from 'react';

const NotificationBadge = ({ count, type = 'default', size = 'small' }) => {
  if (!count || count === 0) return null;
  
  const getTypeClass = () => {
    switch (type) {
      case 'error': return 'bg-red-500';
      case 'warning': return 'bg-yellow-500';
      case 'success': return 'bg-green-500';
      case 'info': return 'bg-blue-500';
      case 'message': return 'bg-purple-500';
      default: return 'bg-red-500';
    }
  };
  
  const getSizeClass = () => {
    switch (size) {
      case 'large': return 'w-6 h-6 text-sm';
      case 'medium': return 'w-5 h-5 text-xs';
      case 'small': return 'w-4 h-4 text-xs';
      default: return 'w-4 h-4 text-xs';
    }
  };
  
  return (
    <span 
      className={`
        inline-flex items-center justify-center 
        ${getSizeClass()} 
        ${getTypeClass()} 
        text-white font-bold rounded-full 
        animate-pulse
      `}
      style={{
        minWidth: size === 'large' ? '24px' : size === 'medium' ? '20px' : '16px',
        fontSize: size === 'large' ? '12px' : '10px'
      }}
    >
      {count > 99 ? '99+' : count}
    </span>
  );
};

export default NotificationBadge;