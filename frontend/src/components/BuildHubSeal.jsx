import React from 'react';
import '../styles/BuildHubSeal.css';

const BuildHubSeal = ({ size = 'medium', className = '' }) => {
  const sizeClasses = {
    small: 'seal-small',
    medium: 'seal-medium',
    large: 'seal-large'
  };

  return (
    <div className={`buildhub-seal ${sizeClasses[size]} ${className}`}>
      <div className="seal-container">
        <div className="seal-border">
          <div className="seal-inner">
            <div className="seal-text">BUILDHUB ESTIMATION</div>
            <div className="seal-icon">
              <div className="building-icon">
                <div className="building-pediment"></div>
                <div className="building-frieze"></div>
                <div className="building-columns">
                  <div className="column"></div>
                  <div className="column"></div>
                  <div className="column"></div>
                  <div className="column"></div>
                  <div className="column"></div>
                </div>
                <div className="building-base"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default BuildHubSeal;



