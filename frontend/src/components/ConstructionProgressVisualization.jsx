import React, { useState, useEffect } from 'react';
import '../styles/ConstructionProgressVisualization.css';

const ConstructionProgressVisualization = ({ projectId, className = '' }) => {
  const [progressData, setProgressData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [hoveredLayer, setHoveredLayer] = useState(null);

  useEffect(() => {
    if (projectId) {
      fetchProgressData();
      
      // Set up polling for progress updates
      const interval = setInterval(() => {
        const lastUpdate = localStorage.getItem(`progress_update_${projectId}`);
        if (lastUpdate) {
          const updateTime = parseInt(lastUpdate);
          const now = Date.now();
          // If update was within last 30 seconds, refresh the data
          if (now - updateTime < 30000) {
            fetchProgressData();
            localStorage.removeItem(`progress_update_${projectId}`);
          }
        }
      }, 5000); // Check every 5 seconds
      
      return () => clearInterval(interval);
    }
  }, [projectId]);

  const fetchProgressData = async () => {
    try {
      setLoading(true);
      const response = await fetch(
        `http://localhost/buildhub/backend/api/homeowner/get_project_progress.php?project_id=${projectId}`,
        {
          method: 'GET',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
          },
        }
      );

      const result = await response.json();
      
      if (result.success) {
        setProgressData(result.data);
        setError(null);
      } else {
        setError(result.message || 'Failed to load progress data');
      }
    } catch (err) {
      setError('Network error loading progress data');
      console.error('Progress fetch error:', err);
    } finally {
      setLoading(false);
    }
  };

  const getLayerOpacity = (layerName) => {
    if (!progressData) return 0.1;
    
    // Use percentage-based opacity with minimum visibility
    const percentage = progressData.visual_layers[layerName] || 0;
    const minOpacity = 0.15;
    const maxOpacity = 1.0;
    
    // Convert percentage (0-1) to opacity range
    return minOpacity + (percentage * (maxOpacity - minOpacity));
  };

  const getLayerClass = (layerName) => {
    if (!progressData) return 'layer-incomplete';
    
    const percentage = progressData.visual_layers[layerName] || 0;
    if (percentage >= 1.0) return 'layer-complete';
    if (percentage > 0) return 'layer-in-progress';
    return 'layer-incomplete';
  };

  const getLayerPercentage = (layerName) => {
    if (!progressData || !progressData.layer_percentages) return 0;
    return Math.round(progressData.layer_percentages[layerName] || 0);
  };

  const handleLayerHover = (layerName, stageName) => {
    const percentage = getLayerPercentage(layerName);
    setHoveredLayer({ 
      layer: layerName, 
      stage: stageName, 
      percentage: percentage 
    });
  };

  const handleLayerLeave = () => {
    setHoveredLayer(null);
  };

  if (loading) {
    return (
      <div className={`progress-visualization loading ${className}`}>
        <div className="loading-spinner"></div>
        <p>Loading construction progress...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className={`progress-visualization error ${className}`}>
        <div className="error-icon">⚠️</div>
        <p>{error}</p>
      </div>
    );
  }

  if (!progressData) {
    return null;
  }

  const { progress, visual_layers } = progressData;

  return (
    <div className={`progress-visualization ${className}`}>
      <div className="progress-header">
        <h3>Construction Progress</h3>
        <div className="progress-summary">
          <div className="progress-percentage">
            {progress.overall_percentage}%
          </div>
          <div className="progress-stage">
            {progress.current_stage}
          </div>
        </div>
      </div>

      <div className="house-container">
        <svg 
          viewBox="0 0 400 300" 
          className="house-svg"
          xmlns="http://www.w3.org/2000/svg"
        >
          {/* Foundation Layer */}
          <g 
            className={`house-layer foundation ${getLayerClass('foundation')}`}
            opacity={getLayerOpacity('foundation')}
            onMouseEnter={() => handleLayerHover('foundation', 'Foundation')}
            onMouseLeave={handleLayerLeave}
          >
            <rect x="50" y="220" width="300" height="40" fill="#8B4513" stroke="#654321" strokeWidth="2"/>
            <rect x="40" y="250" width="320" height="20" fill="#A0522D" stroke="#654321" strokeWidth="1"/>
            <text x="200" y="245" textAnchor="middle" className="layer-label">Foundation</text>
          </g>

          {/* Structure Layer */}
          <g 
            className={`house-layer structure ${getLayerClass('structure')}`}
            opacity={getLayerOpacity('structure')}
            onMouseEnter={() => handleLayerHover('structure', 'Structure')}
            onMouseLeave={handleLayerLeave}
          >
            {/* Main frame pillars */}
            <rect x="70" y="120" width="8" height="100" fill="#D2691E" stroke="#8B4513" strokeWidth="1"/>
            <rect x="120" y="120" width="8" height="100" fill="#D2691E" stroke="#8B4513" strokeWidth="1"/>
            <rect x="170" y="120" width="8" height="100" fill="#D2691E" stroke="#8B4513" strokeWidth="1"/>
            <rect x="220" y="120" width="8" height="100" fill="#D2691E" stroke="#8B4513" strokeWidth="1"/>
            <rect x="270" y="120" width="8" height="100" fill="#D2691E" stroke="#8B4513" strokeWidth="1"/>
            <rect x="320" y="120" width="8" height="100" fill="#D2691E" stroke="#8B4513" strokeWidth="1"/>
            
            {/* Horizontal beams */}
            <rect x="70" y="120" width="258" height="6" fill="#D2691E" stroke="#8B4513" strokeWidth="1"/>
            <rect x="70" y="160" width="258" height="6" fill="#D2691E" stroke="#8B4513" strokeWidth="1"/>
            <rect x="70" y="200" width="258" height="6" fill="#D2691E" stroke="#8B4513" strokeWidth="1"/>
            
            <text x="200" y="190" textAnchor="middle" className="layer-label">Structure</text>
          </g>

          {/* Walls Layer */}
          <g 
            className={`house-layer walls ${getLayerClass('walls')}`}
            opacity={getLayerOpacity('walls')}
            onMouseEnter={() => handleLayerHover('walls', 'Walls')}
            onMouseLeave={handleLayerLeave}
          >
            {/* Main walls */}
            <rect x="70" y="120" width="260" height="100" fill="#F5DEB3" stroke="#D2B48C" strokeWidth="2"/>
            
            {/* Door */}
            <rect x="180" y="180" width="40" height="40" fill="#8B4513" stroke="#654321" strokeWidth="2"/>
            <circle cx="210" cy="200" r="2" fill="#FFD700"/>
            
            {/* Window frames */}
            <rect x="100" y="140" width="40" height="30" fill="#87CEEB" stroke="#4682B4" strokeWidth="2"/>
            <rect x="260" y="140" width="40" height="30" fill="#87CEEB" stroke="#4682B4" strokeWidth="2"/>
            
            <text x="200" y="155" textAnchor="middle" className="layer-label">Walls</text>
          </g>

          {/* Roofing Layer */}
          <g 
            className={`house-layer roofing ${getLayerClass('roofing')}`}
            opacity={getLayerOpacity('roofing')}
            onMouseEnter={() => handleLayerHover('roofing', 'Roofing')}
            onMouseLeave={handleLayerLeave}
          >
            {/* Roof structure */}
            <polygon points="50,120 200,40 350,120" fill="#8B0000" stroke="#654321" strokeWidth="2"/>
            <polygon points="60,115 200,50 340,115" fill="#DC143C" stroke="#8B0000" strokeWidth="1"/>
            
            {/* Roof tiles pattern */}
            <g className="roof-tiles">
              <path d="M 80,110 Q 90,105 100,110 Q 110,105 120,110" stroke="#654321" strokeWidth="1" fill="none"/>
              <path d="M 120,110 Q 130,105 140,110 Q 150,105 160,110" stroke="#654321" strokeWidth="1" fill="none"/>
              <path d="M 160,110 Q 170,105 180,110 Q 190,105 200,110" stroke="#654321" strokeWidth="1" fill="none"/>
              <path d="M 200,110 Q 210,105 220,110 Q 230,105 240,110" stroke="#654321" strokeWidth="1" fill="none"/>
              <path d="M 240,110 Q 250,105 260,110 Q 270,105 280,110" stroke="#654321" strokeWidth="1" fill="none"/>
              <path d="M 280,110 Q 290,105 300,110 Q 310,105 320,110" stroke="#654321" strokeWidth="1" fill="none"/>
            </g>
            
            <text x="200" y="85" textAnchor="middle" className="layer-label">Roofing</text>
          </g>

          {/* Finishing Layer */}
          <g 
            className={`house-layer finishing ${getLayerClass('finishing')}`}
            opacity={getLayerOpacity('finishing')}
            onMouseEnter={() => handleLayerHover('finishing', 'Finishing')}
            onMouseLeave={handleLayerLeave}
          >
            {/* Window glass and details */}
            <rect x="102" y="142" width="36" height="26" fill="#E0F6FF" stroke="#4682B4" strokeWidth="1"/>
            <rect x="262" y="142" width="36" height="26" fill="#E0F6FF" stroke="#4682B4" strokeWidth="1"/>
            
            {/* Window cross frames */}
            <line x1="120" y1="142" x2="120" y2="168" stroke="#4682B4" strokeWidth="1"/>
            <line x1="102" y1="155" x2="138" y2="155" stroke="#4682B4" strokeWidth="1"/>
            <line x1="280" y1="142" x2="280" y2="168" stroke="#4682B4" strokeWidth="1"/>
            <line x1="262" y1="155" x2="298" y2="155" stroke="#4682B4" strokeWidth="1"/>
            
            {/* Door details */}
            <rect x="182" y="182" width="36" height="36" fill="#A0522D" stroke="#654321" strokeWidth="1"/>
            <rect x="185" y="185" width="30" height="30" fill="#D2691E"/>
            <circle cx="210" cy="200" r="3" fill="#FFD700" stroke="#DAA520" strokeWidth="1"/>
            
            {/* Decorative elements */}
            <rect x="190" y="125" width="20" height="15" fill="#228B22" stroke="#006400" strokeWidth="1"/>
            <text x="200" y="135" textAnchor="middle" fontSize="8" fill="#FFFFFF">🏠</text>
            
            <text x="200" y="210" textAnchor="middle" className="layer-label">Finishing</text>
          </g>

          {/* Completion celebration */}
          {progress.overall_percentage === 100 && (
            <g className="completion-celebration">
              <text x="200" y="30" textAnchor="middle" fontSize="16" fill="#FFD700">🎉 Complete! 🎉</text>
            </g>
          )}
        </svg>

        {/* Tooltip */}
        {hoveredLayer && (
          <div className="progress-tooltip">
            <div className="tooltip-stage">{hoveredLayer.stage}</div>
            <div className="tooltip-percentage">{hoveredLayer.percentage}% Complete</div>
            <div className="tooltip-status">
              {hoveredLayer.percentage >= 100 ? 'Completed' : 
               hoveredLayer.percentage > 0 ? 'In Progress' : 'Not Started'}
            </div>
          </div>
        )}
      </div>

      <div className="progress-details">
        <div className="stage-progress">
          <span className="completed-stages">
            {progress.completed_count} of {Object.keys(progressData.stage_details || {}).length} stages completed
          </span>
        </div>
        
        <div className="progress-bar">
          <div 
            className="progress-fill" 
            style={{ width: `${progress.overall_percentage}%` }}
          ></div>
        </div>
        
        {/* Stage breakdown */}
        <div className="stage-breakdown">
          {progressData.layer_percentages && Object.entries(progressData.layer_percentages).map(([layer, percentage]) => (
            <div key={layer} className="stage-item">
              <span className="stage-name">{layer.charAt(0).toUpperCase() + layer.slice(1)}</span>
              <span className="stage-percent">{Math.round(percentage)}%</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default ConstructionProgressVisualization;