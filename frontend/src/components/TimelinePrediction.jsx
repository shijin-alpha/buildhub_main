import React, { useState, useEffect } from 'react';
import './TimelinePrediction.css';

const TimelinePrediction = ({ 
  formData = {}, 
  onTimelineChange,
  showPredictions = true 
}) => {
  const [predictedTimeline, setPredictedTimeline] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Predict timeline based on form data (only when there's meaningful data)
  useEffect(() => {
    if (showPredictions && hasMeaningfulData(formData)) {
      predictTimeline();
    } else if (!hasMeaningfulData(formData)) {
      // Clear timeline when no meaningful data
      setPredictedTimeline(null);
    }
  }, [formData, showPredictions]);

  // Check if form has meaningful data for timeline prediction
  const hasMeaningfulData = (data) => {
    const plotSize = parseFloat(data.plot_size || 0);
    const buildingSize = parseFloat(data.building_size || 0);
    const budget = parseFloat(data.budget || 0);
    
    // Only predict if at least plot size or building size is provided
    return plotSize > 0 || buildingSize > 0 || budget > 0;
  };

  const predictTimeline = async () => {
    setLoading(true);
    setError('');

    try {
  // Calculate timeline based on project complexity
      const plotSize = parseFloat(formData.plot_size || 0);
      const buildingSize = parseFloat(formData.building_size || 0);
      const numFloors = parseInt(formData.num_floors || 1);
      const budget = parseFloat(formData.budget || 0);
    
    // Base timeline calculation
    let baseMonths = 6; // Minimum 6 months
    
    // Adjust based on plot size
      if (plotSize >= 4000) baseMonths += 2; // Large plots need more time
      else if (plotSize >= 2000) baseMonths += 1;

      // Adjust based on building size
      if (buildingSize >= 3000) baseMonths += 2;
      else if (buildingSize >= 1500) baseMonths += 1;
    
    // Adjust based on floors
      if (numFloors >= 3) baseMonths += 2;
      else if (numFloors >= 2) baseMonths += 1;

      // Adjust based on budget (higher budget = more complex = longer time)
      if (budget >= 5000000) baseMonths += 2; // 50+ lakhs
      else if (budget >= 2000000) baseMonths += 1; // 20+ lakhs

      // Add complexity factors
      if (formData.topography === 'sloped') baseMonths += 1;
      if (formData.development_laws === 'strict') baseMonths += 1;
      if (formData.plot_shape === 'irregular') baseMonths += 1;

      // Cap at reasonable maximum
      const finalMonths = Math.min(baseMonths, 18);

      const prediction = {
        months: finalMonths,
        phases: [
          { name: 'Planning & Design', duration: Math.ceil(finalMonths * 0.3), status: 'upcoming' },
          { name: 'Permits & Approvals', duration: Math.ceil(finalMonths * 0.2), status: 'upcoming' },
          { name: 'Construction', duration: Math.ceil(finalMonths * 0.4), status: 'upcoming' },
          { name: 'Finishing & Handover', duration: Math.ceil(finalMonths * 0.1), status: 'upcoming' }
        ],
        confidence: calculateConfidence(formData),
        factors: getTimelineFactors(formData, finalMonths)
      };

      setPredictedTimeline(prediction);
      
      // Notify parent component
      if (onTimelineChange) {
        onTimelineChange(prediction);
      }

    } catch (err) {
      setError('Error predicting timeline: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const calculateConfidence = (data) => {
    let confidence = 50; // Base confidence

    // Increase confidence based on filled fields
    const keyFields = ['plot_size', 'building_size', 'num_floors', 'budget'];
    const filledFields = keyFields.filter(field => data[field] && data[field] !== '');
    confidence += filledFields.length * 10;

    // Adjust based on data quality
    if (data.plot_size && data.building_size) confidence += 10;
    if (data.budget && data.budget > 0) confidence += 10;

    return Math.min(confidence, 95);
  };

  const getTimelineFactors = (data, months) => {
    const factors = [];

    if (data.plot_size >= 4000) {
      factors.push({ type: 'positive', text: 'Large plot allows efficient construction' });
    }

    if (data.num_floors >= 3) {
      factors.push({ type: 'warning', text: 'Multi-story construction requires more time' });
    }

    if (data.topography === 'sloped') {
      factors.push({ type: 'warning', text: 'Sloped terrain may require additional foundation work' });
    }

    if (data.development_laws === 'strict') {
      factors.push({ type: 'warning', text: 'Strict regulations may extend approval time' });
    }

    if (data.budget >= 5000000) {
      factors.push({ type: 'positive', text: 'Higher budget allows for faster construction' });
    }

    return factors;
  };

  const formatTimeline = (months) => {
    if (months < 12) {
      return `${months} months`;
    } else {
      const years = Math.floor(months / 12);
      const remainingMonths = months % 12;
      if (remainingMonths === 0) {
        return `${years} year${years > 1 ? 's' : ''}`;
      } else {
        return `${years} year${years > 1 ? 's' : ''} ${remainingMonths} month${remainingMonths > 1 ? 's' : ''}`;
      }
    }
  };

  const getPhaseIcon = (phaseName) => {
    const icons = {
      'Planning & Design': '📐',
      'Permits & Approvals': '📋',
      'Construction': '🏗️',
      'Finishing & Handover': '🔑'
    };
    return icons[phaseName] || '📅';
  };

  if (loading) {
    return (
      <div className="timeline-prediction-container">
        <div className="timeline-loading">
          <div className="loading-spinner"></div>
          <span>Predicting timeline...</span>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="timeline-prediction-container">
        <div className="timeline-error">
          <i className="fas fa-exclamation-triangle"></i>
          {error}
        </div>
      </div>
    );
  }

  if (!predictedTimeline) {
    return null;
  }

  return (
    <div className="timeline-prediction-container">
      <div className="timeline-header">
        <h4 className="timeline-title">
          <i className="fas fa-clock"></i>
          Predicted Timeline
        </h4>
        <div className="timeline-confidence">
          <span className="confidence-label">Confidence:</span>
          <span className="confidence-value">{predictedTimeline.confidence}%</span>
        </div>
      </div>

        <div className="timeline-summary">
          <div className="timeline-duration">
          <span className="duration-label">Estimated Duration:</span>
          <span className="duration-value">{formatTimeline(predictedTimeline.months)}</span>
        </div>
      </div>

      <div className="timeline-phases">
        <h5 className="phases-title">Project Phases</h5>
        <div className="phases-timeline">
          {predictedTimeline.phases.map((phase, index) => (
            <div key={index} className="phase-item">
              <div className="phase-icon">{getPhaseIcon(phase.name)}</div>
              <div className="phase-content">
                <div className="phase-name">{phase.name}</div>
                <div className="phase-duration">{phase.duration} month{phase.duration > 1 ? 's' : ''}</div>
              </div>
            </div>
          ))}
            </div>
      </div>

      {predictedTimeline.factors.length > 0 && (
        <div className="timeline-factors">
          <h5 className="factors-title">Timeline Factors</h5>
          <div className="factors-list">
            {predictedTimeline.factors.map((factor, index) => (
              <div key={index} className={`factor-item ${factor.type}`}>
                <i className={`fas fa-${factor.type === 'positive' ? 'check-circle' : 'exclamation-triangle'}`}></i>
                <span>{factor.text}</span>
          </div>
        ))}
      </div>
        </div>
      )}

      <div className="timeline-note">
        <i className="fas fa-info-circle"></i>
        <span>Timeline is estimated based on your project details. Actual duration may vary.</span>
      </div>
    </div>
  );
};

export default TimelinePrediction;