import React, { useState } from 'react';

const InfoPopup = ({ content, children, position = 'top' }) => {
  const [isVisible, setIsVisible] = useState(false);

  const positionClasses = {
    top: 'info-popup-top',
    bottom: 'info-popup-bottom',
    left: 'info-popup-left',
    right: 'info-popup-right'
  };

  return (
    <div 
      className="info-popup-container"
      style={{ position: 'relative', display: 'inline-block' }}
      onMouseEnter={() => setIsVisible(true)}
      onMouseLeave={() => setIsVisible(false)}
    >
      {children}
      {isVisible && (
        <div 
          className={`info-popup ${positionClasses[position]}`}
          style={{
            position: 'absolute',
            zIndex: 1000,
            backgroundColor: '#1f2937',
            color: 'white',
            padding: '12px 16px',
            borderRadius: '8px',
            fontSize: '14px',
            lineHeight: '1.4',
            maxWidth: '300px',
            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            ...(position === 'top' && { bottom: '100%', left: '50%', transform: 'translateX(-50%)', marginBottom: '8px' }),
            ...(position === 'bottom' && { top: '100%', left: '50%', transform: 'translateX(-50%)', marginTop: '8px' }),
            ...(position === 'left' && { right: '100%', top: '50%', transform: 'translateY(-50%)', marginRight: '8px' }),
            ...(position === 'right' && { left: '100%', top: '50%', transform: 'translateY(-50%)', marginLeft: '8px' })
          }}
        >
          {content}
          <div 
            style={{
              position: 'absolute',
              width: 0,
              height: 0,
              ...(position === 'top' && { 
                top: '100%', 
                left: '50%', 
                transform: 'translateX(-50%)',
                borderLeft: '6px solid transparent',
                borderRight: '6px solid transparent',
                borderTop: '6px solid #1f2937'
              }),
              ...(position === 'bottom' && { 
                bottom: '100%', 
                left: '50%', 
                transform: 'translateX(-50%)',
                borderLeft: '6px solid transparent',
                borderRight: '6px solid transparent',
                borderBottom: '6px solid #1f2937'
              }),
              ...(position === 'left' && { 
                left: '100%', 
                top: '50%', 
                transform: 'translateY(-50%)',
                borderTop: '6px solid transparent',
                borderBottom: '6px solid transparent',
                borderLeft: '6px solid #1f2937'
              }),
              ...(position === 'right' && { 
                right: '100%', 
                top: '50%', 
                transform: 'translateY(-50%)',
                borderTop: '6px solid transparent',
                borderBottom: '6px solid transparent',
                borderRight: '6px solid #1f2937'
              })
            }}
          />
        </div>
      )}
    </div>
  );
};

export default InfoPopup;
















