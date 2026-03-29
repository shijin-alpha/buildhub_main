import React, { useState, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import { useToast } from './ToastProvider.jsx';
import ArchitectDetailsModal from './ArchitectDetailsModal.jsx';
import TourGuide from './TourGuide.jsx';
import HomeownerDashboardTour from './HomeownerDashboardTour.jsx';
import ArchitectSelection from './ArchitectSelection.jsx';
import GeoPhotoViewer from './GeoPhotoViewer.jsx';
import HouseStyleSuggestions from './HouseStyleSuggestions.jsx';
import { useNavigate } from 'react-router-dom';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';
import '../styles/HomeownerDashboard.css';
import '../styles/HomeownerProgressReports.css';
import '../styles/BlueGlassTheme.css';
import '../styles/SoftSidebar.css';
import '../styles/Widgets.css';
import '../styles/ReviewSection.css';
import '../styles/ArchitectRecommendation.css';
import './WidgetColors.css';
import SearchableDropdown from './SearchableDropdown';
import { indianCities } from '../data/indianCities';
import { stateDistricts, keralaPanchayatsMunicipalities } from '../data/locationData';
import { badgeClass, formatStatus } from '../utils/status';
import { ProjectProgressChart, ProjectTimeline, BudgetTracker } from './widgets/ProjectTrackingWidgets';
import NotificationSystem from './widgets/NotificationSystem';
import DesignGallery from './widgets/DesignGallery';
import NeatJsonCard from './NeatJsonCard';
import TechnicalDetailsDisplay from './TechnicalDetailsDisplay';
import HomeownerProfileButton from './HomeownerProfileButton';
import HomeownerProgressReports from './HomeownerProgressReports';
import ConfirmModal from './ConfirmModal';
import RoomImprovementAssistant from './RoomImprovementAssistant';
import InlineRoomImprovement from './InlineRoomImprovement';
import ConstructionTimeline from './ConstructionTimeline';
import ConstructionProgressVisualization from './ConstructionProgressVisualization';
import '../styles/SupportSystem.css';
import '../styles/RoomImprovementAssistant.css';
import '../styles/InlineRoomImprovement.css';
import '../styles/ConstructionTimeline.css';

// Enhanced Image Component with Error Handling
const SafeImage = ({ src, alt, style, onClick, fallbackText = 'Image not available', className = '' }) => {
  const [imageError, setImageError] = useState(false);
  const [imageLoaded, setImageLoaded] = useState(false);

  const handleImageError = () => {
    console.log('Image failed to load:', src);
    setImageError(true);
  };

  const handleImageLoad = () => {
    setImageLoaded(true);
    setImageError(false);
  };

  if (imageError) {
    return (
      <div 
        className={`image-error-placeholder ${className}`}
        style={{
          ...style,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          background: '#f5f5f7',
          borderRadius: '6px',
          flexDirection: 'column',
          color: '#6b7280',
          fontSize: '0.75rem',
          textAlign: 'center',
          padding: '1rem',
          border: '1px dashed #d1d5db',
          cursor: onClick ? 'pointer' : 'default'
        }}
        onClick={onClick}
      >
        <span style={{ fontSize: '2rem', marginBottom: '8px' }}>🖼️</span>
        <span>{fallbackText}</span>
      </div>
    );
  }

  return (
    <>
      {!imageLoaded && (
        <div 
          className={`image-loading-placeholder ${className}`}
          style={{
            ...style,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            background: '#f5f5f7',
            borderRadius: '6px',
            flexDirection: 'column',
            color: '#6b7280',
            fontSize: '0.75rem',
            textAlign: 'center',
            padding: '1rem',
            border: '1px solid #e5e7eb'
          }}
        >
          <div className="loading-spinner" style={{
            width: '24px',
            height: '24px',
            border: '2px solid #e5e7eb',
            borderTop: '2px solid #3b82f6',
            borderRadius: '50%',
            animation: 'spin 1s linear infinite',
            marginBottom: '8px'
          }}></div>
          <span>Loading...</span>
        </div>
      )}
      <img 
        src={src}
        alt={alt}
        className={className}
        style={{
          ...style,
          display: imageLoaded ? 'block' : 'none'
        }}
        onClick={onClick}
        onError={handleImageError}
        onLoad={handleImageLoad}
      />
    </>
  );
};

const HomeownerDashboard = () => {
  // ... (keep all existing state and functions)
  
  // Add CSS for loading spinner
  useEffect(() => {
    const style = document.createElement('style');
    style.textContent = `
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
      .image-error-placeholder:hover {
        background: #f0f0f0 !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      }
      .image-loading-placeholder {
        transition: all 0.3s ease;
      }
    `;
    document.head.appendChild(style);
    return () => document.head.removeChild(style);
  }, []);

  // ... (keep all existing component logic)
  
  return (
    <div className="dashboard-container">
      {/* Keep all existing JSX but replace img tags with SafeImage */}
      {/* This is a template - the actual implementation would replace all img usage */}
    </div>
  );
};

export default HomeownerDashboard;