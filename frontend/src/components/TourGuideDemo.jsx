import React, { useState } from 'react';
import TourGuide from './TourGuide';
import '../styles/TourGuide.css';

const TourGuideDemo = () => {
  const [showTour, setShowTour] = useState(false);
  const [currentStep, setCurrentStep] = useState(0);
  const totalSteps = 10;

  const handleNext = () => {
    if (currentStep < totalSteps - 1) {
      setCurrentStep(currentStep + 1);
    } else {
      setShowTour(false);
    }
  };

  const handlePrev = () => {
    if (currentStep > 0) {
      setCurrentStep(currentStep - 1);
    }
  };

  const handleSkip = () => {
    setShowTour(false);
  };

  const handleClose = () => {
    setShowTour(false);
  };

  const resetTour = () => {
    localStorage.removeItem('buildhub_tour_completed');
    alert('Tour reset! Refresh the page to see the tour guide again.');
  };

  return (
    <div style={{ padding: '20px', maxWidth: '800px', margin: '0 auto' }}>
      <h1>Tour Guide Demo</h1>
      <p>This demonstrates the tour guide functionality for the Request Custom Design page.</p>
      
      <div style={{ marginBottom: '20px' }}>
        <button 
          onClick={() => setShowTour(true)}
          style={{
            padding: '10px 20px',
            backgroundColor: '#3b82f6',
            color: 'white',
            border: 'none',
            borderRadius: '6px',
            cursor: 'pointer',
            marginRight: '10px'
          }}
        >
          Start Tour Guide
        </button>
        
        <button 
          onClick={resetTour}
          style={{
            padding: '10px 20px',
            backgroundColor: '#6b7280',
            color: 'white',
            border: 'none',
            borderRadius: '6px',
            cursor: 'pointer'
          }}
        >
          Reset Tour (for testing)
        </button>
      </div>

      <div style={{ 
        background: '#f8fafc', 
        padding: '20px', 
        borderRadius: '8px',
        border: '1px solid #e2e8f0'
      }}>
        <h3>Tour Guide Features:</h3>
        <ul>
          <li>✅ Step-by-step guidance through the design request process</li>
          <li>✅ Helpful tips and explanations for each step</li>
          <li>✅ Responsive design that works on all devices</li>
          <li>✅ Keyboard shortcuts (Ctrl + H to open help)</li>
          <li>✅ Persistent tour completion tracking</li>
          <li>✅ Contextual help button on each step</li>
        </ul>
        
        <h3>How to Test:</h3>
        <ol>
          <li>Click "Start Tour Guide" to see the tour in action</li>
          <li>Navigate through the steps using Next/Previous buttons</li>
          <li>Try the "Skip Tour" option</li>
          <li>Click "Reset Tour" and refresh the page to see the auto-start feature</li>
          <li>In the actual Request Custom Design page, use Ctrl + H to open help anytime</li>
        </ol>
      </div>

      <TourGuide
        isOpen={showTour}
        onClose={handleClose}
        currentStep={currentStep}
        totalSteps={totalSteps}
        onNext={handleNext}
        onPrev={handlePrev}
        onSkip={handleSkip}
      />
    </div>
  );
};

export default TourGuideDemo;



