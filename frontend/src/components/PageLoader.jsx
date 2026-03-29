import React from 'react';
import './PageLoader.css';

const PageLoader = ({ isLoading }) => {
  if (!isLoading) return null;

  return (
    <div className="page-loader-overlay">
      <div className="page-loader-container">
        <div className="page-loader-spinner">
          <div className="spinner-ring"></div>
          <div className="spinner-ring"></div>
          <div className="spinner-ring"></div>
        </div>
        <div className="page-loader-text">
          <h3>Loading...</h3>
          <p>Please wait while we prepare your dashboard</p>
        </div>
      </div>
    </div>
  );
};

export default PageLoader;

