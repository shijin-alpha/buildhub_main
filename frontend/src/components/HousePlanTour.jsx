import React, { useState, useEffect } from 'react';
import '../styles/HousePlanTour.css';

const HousePlanTour = ({ isOpen, onClose, onComplete }) => {
  const [currentStep, setCurrentStep] = useState(0);
  const [isVisible, setIsVisible] = useState(false);

  const tourSteps = [
    {
      id: 'welcome',
      title: 'Welcome to House Plan Designer',
      content: 'This professional tool helps architects create detailed house plans with precise measurements and room layouts. Let\'s take a quick tour to get you started.',
      target: null,
      position: 'center'
    },
    {
      id: 'header',
      title: 'Control Panel',
      content: 'The header contains all essential controls: Undo/Redo for changes, Delete for removing rooms, Save for local storage, and Submit to send plans to homeowners.',
      target: '.drawer-header',
      position: 'bottom'
    },
    {
      id: 'plan-details',
      title: 'Plan Information',
      content: 'Start by entering your plan name, plot dimensions, and any notes. This information will be included in the final plan documentation.',
      target: '.plan-details-section',
      position: 'right'
    },
    {
      id: 'statistics',
      title: 'Real-time Statistics',
      content: 'Monitor your plan\'s key metrics: total area, room count, and plot coverage percentage. These update automatically as you design.',
      target: '.plan-stats',
      position: 'right'
    },
    {
      id: 'measurements',
      title: 'Measurement System',
      content: 'Choose between Layout (visual design) and Construction (actual building) dimensions. The scale ratio converts between them for accurate planning.',
      target: '.measurement-controls',
      position: 'right'
    },
    {
      id: 'room-templates',
      title: 'Room Templates',
      content: 'Click any room template to add it to your plan. Each template comes with standard dimensions and can be customized after placement.',
      target: '.room-templates',
      position: 'right'
    },
    {
      id: 'canvas',
      title: 'Design Canvas',
      content: 'This is your main workspace. Click rooms to select them, drag to move, and use the blue handles to resize. The grid helps with precise alignment.',
      target: '.canvas-container',
      position: 'left'
    },
    {
      id: 'room-properties',
      title: 'Room Customization',
      content: 'When a room is selected, customize its dimensions, materials, and construction specifications in this panel.',
      target: '.room-properties',
      position: 'right',
      condition: () => document.querySelector('.room-properties')
    },
    {
      id: 'keyboard-shortcuts',
      title: 'Keyboard Shortcuts',
      content: 'Use Ctrl+Z to undo, Ctrl+Y to redo, and Delete key to remove selected rooms. These shortcuts speed up your workflow significantly.',
      target: null,
      position: 'center'
    },
    {
      id: 'completion',
      title: 'You\'re Ready!',
      content: 'You now know the basics of the House Plan Designer. Start by adding rooms, customize them, and save or submit your plans when ready.',
      target: null,
      position: 'center'
    }
  ];

  useEffect(() => {
    if (isOpen) {
      setIsVisible(true);
      setCurrentStep(0);
    }
  }, [isOpen]);

  const nextStep = () => {
    if (currentStep < tourSteps.length - 1) {
      setCurrentStep(currentStep + 1);
    } else {
      completeTour();
    }
  };

  const prevStep = () => {
    if (currentStep > 0) {
      setCurrentStep(currentStep - 1);
    }
  };

  const skipTour = () => {
    completeTour();
  };

  const completeTour = () => {
    setIsVisible(false);
    setTimeout(() => {
      onComplete();
      onClose();
    }, 300);
  };

  const getCurrentStepData = () => {
    const step = tourSteps[currentStep];
    if (step.condition && !step.condition()) {
      // Skip this step if condition is not met
      if (currentStep < tourSteps.length - 1) {
        setCurrentStep(currentStep + 1);
        return null;
      }
    }
    return step;
  };

  if (!isOpen || !isVisible) return null;

  const currentStepData = getCurrentStepData();
  if (!currentStepData) return null;

  const getTooltipPosition = () => {
    if (!currentStepData.target) return { position: 'center' };
    
    const element = document.querySelector(currentStepData.target);
    if (!element) return { position: 'center' };
    
    const rect = element.getBoundingClientRect();
    const position = currentStepData.position || 'bottom';
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;
    
    // Scroll element into view if needed
    if (rect.bottom > viewportHeight || rect.top < 0) {
      element.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'center',
        inline: 'center'
      });
      
      // Wait for scroll to complete, then recalculate position
      setTimeout(() => {
        const newRect = element.getBoundingClientRect();
        updateTooltipPosition(newRect, position);
      }, 300);
    }
    
    return calculateTooltipPosition(rect, position, viewportHeight, viewportWidth);
  };

  const calculateTooltipPosition = (rect, position, viewportHeight, viewportWidth) => {
    const tooltipWidth = 400;
    const tooltipHeight = 200;
    const margin = 20;
    
    let top, left, transform;
    
    switch (position) {
      case 'top':
        top = Math.max(margin, rect.top - tooltipHeight - margin);
        left = Math.min(viewportWidth - tooltipWidth - margin, Math.max(margin, rect.left + rect.width / 2 - tooltipWidth / 2));
        transform = 'translate(0, 0)';
        break;
      case 'bottom':
        top = Math.min(viewportHeight - tooltipHeight - margin, rect.bottom + margin);
        left = Math.min(viewportWidth - tooltipWidth - margin, Math.max(margin, rect.left + rect.width / 2 - tooltipWidth / 2));
        transform = 'translate(0, 0)';
        break;
      case 'left':
        top = Math.min(viewportHeight - tooltipHeight - margin, Math.max(margin, rect.top + rect.height / 2 - tooltipHeight / 2));
        left = Math.max(margin, rect.left - tooltipWidth - margin);
        transform = 'translate(0, 0)';
        break;
      case 'right':
        top = Math.min(viewportHeight - tooltipHeight - margin, Math.max(margin, rect.top + rect.height / 2 - tooltipHeight / 2));
        left = Math.min(viewportWidth - tooltipWidth - margin, rect.right + margin);
        transform = 'translate(0, 0)';
        break;
      default:
        return { position: 'center' };
    }
    
    return { top, left, transform };
  };

  const updateTooltipPosition = (rect, position) => {
    const tooltip = document.querySelector('.tour-tooltip');
    if (tooltip && !tooltip.classList.contains('tour-tooltip-center')) {
      const newPosition = calculateTooltipPosition(rect, position, window.innerHeight, window.innerWidth);
      Object.assign(tooltip.style, newPosition);
    }
  };

  const tooltipStyle = currentStepData.position === 'center' 
    ? {} 
    : getTooltipPosition();

  return (
    <>
      {/* Overlay */}
      <div className="tour-overlay" onClick={skipTour} />
      
      {/* Highlight target element */}
      {currentStepData.target && (
        <div className="tour-highlight" data-target={currentStepData.target} />
      )}
      
      {/* Tour tooltip */}
      <div 
        className={`tour-tooltip ${currentStepData.position === 'center' ? 'tour-tooltip-center' : ''}`}
        style={tooltipStyle}
      >
        <div className="tour-tooltip-header">
          <h3>{currentStepData.title}</h3>
          <div className="tour-step-indicator">
            {currentStep + 1} of {tourSteps.length}
          </div>
        </div>
        
        <div className="tour-tooltip-content">
          <p>{currentStepData.content}</p>
        </div>
        
        <div className="tour-tooltip-actions">
          <button 
            onClick={skipTour}
            className="tour-btn tour-btn-skip"
          >
            Skip Tour
          </button>
          
          <div className="tour-navigation">
            {currentStep > 0 && (
              <button 
                onClick={prevStep}
                className="tour-btn tour-btn-prev"
              >
                Previous
              </button>
            )}
            
            <button 
              onClick={nextStep}
              className="tour-btn tour-btn-next"
            >
              {currentStep === tourSteps.length - 1 ? 'Finish' : 'Next'}
            </button>
          </div>
        </div>
        
        {/* Progress bar */}
        <div className="tour-progress">
          <div 
            className="tour-progress-bar"
            style={{ width: `${((currentStep + 1) / tourSteps.length) * 100}%` }}
          />
        </div>
      </div>
    </>
  );
};

export default HousePlanTour;