import React, { useState, useEffect } from 'react';
import '../styles/TourGuide.css';

const HomeownerDashboardTour = ({ isOpen, onClose, currentStep, totalSteps, onNext, onPrev, onSkip }) => {
  const [stepContent, setStepContent] = useState({});
  const [highlightedElement, setHighlightedElement] = useState(null);

  useEffect(() => {
    // Define tour content for each step of the homeowner dashboard
    const tourSteps = {
      0: {
        title: "🏠 Welcome to Your Homeowner Dashboard!",
        content: "This is your central hub for managing all your construction projects. Here you can create requests, track designs, manage payments, and communicate with architects and contractors.",
        tips: [
          "💡 Use the navigation tabs to switch between different sections",
          "📊 The dashboard shows an overview of all your projects",
          "🎯 Each section has specific tools for different tasks"
        ],
        highlight: null,
        position: "center"
      },
      1: {
        title: "📋 Dashboard Overview",
        content: "Your dashboard shows key statistics about your projects. You can see how many requests you've made, active projects, available layouts, and more.",
        tips: [
          "📈 These numbers update in real-time as you create projects",
          "🎨 Each stat card shows different aspects of your account",
          "🔄 Refresh the page to see the latest updates"
        ],
        highlight: ".stats-grid",
        position: "top"
      },
      2: {
        title: "🎯 Quick Actions",
        content: "The quick action buttons let you perform common tasks quickly. You can start a new request, browse layouts, or take this tour anytime.",
        tips: [
          "➕ 'New Request' starts the project creation wizard",
          "📚 'Browse Layouts' shows pre-designed templates",
          "🎯 'Take Tour Guide' brings you back to this tour"
        ],
        highlight: ".quick-actions",
        position: "top"
      },
      3: {
        title: "📝 Layout Requests Tab",
        content: "This tab shows all your submitted requests to architects. You can see the status, track progress, and manage your requests.",
        tips: [
          "📊 View all your requests in one place",
          "🔄 Check status updates from architects",
          "💬 Communicate directly with assigned architects"
        ],
        highlight: ".nav-tabs",
        position: "top"
      },
      4: {
        title: "🎨 Received Designs Tab",
        content: "When architects submit designs for your requests, they appear here. You can review, comment, rate, and approve designs.",
        tips: [
          "👀 Preview designs before downloading",
          "⭐ Rate designs to help other homeowners",
          "💬 Leave feedback for architects",
          "💰 Pay to unlock full design files"
        ],
        highlight: ".nav-tabs",
        position: "top"
      },
      5: {
        title: "🏗️ Contractor Requests Tab",
        content: "This section is for construction estimates. You can request quotes from contractors and compare different estimates.",
        tips: [
          "📊 Compare multiple contractor estimates",
          "💰 See detailed cost breakdowns",
          "💬 Message contractors directly",
          "📋 Track estimate status"
        ],
        highlight: ".nav-tabs",
        position: "top"
      },
      6: {
        title: "📚 Layout Library",
        content: "Browse pre-designed layouts created by architects. You can customize these templates or use them as inspiration for your custom requests.",
        tips: [
          "🔍 Filter layouts by type, size, and style",
          "👀 Preview layouts before requesting customization",
          "💡 Use layouts as inspiration for custom designs",
          "📋 Request customizations based on templates"
        ],
        highlight: ".nav-tabs",
        position: "top"
      },
      7: {
        title: "👤 Profile & Settings",
        content: "Manage your account information, view your project history, and adjust your preferences.",
        tips: [
          "✏️ Update your personal information",
          "📊 View your project history and statistics",
          "⚙️ Adjust notification preferences",
          "🔒 Manage account security settings"
        ],
        highlight: ".nav-tabs",
        position: "top"
      },
      8: {
        title: "❓ Help & Support",
        content: "Need help? Click the help button to report issues, ask questions, or get support from our team.",
        tips: [
          "📝 Report bugs or issues you encounter",
          "❓ Ask questions about using the platform",
          "💬 Get help from our support team",
          "📋 View your previous support requests"
        ],
        highlight: ".icon-btn",
        position: "top"
      },
      9: {
        title: "🎉 You're All Set!",
        content: "You now know your way around the homeowner dashboard! Start by creating your first project request or browse the layout library for inspiration.",
        tips: [
          "🚀 Create your first project request",
          "📚 Browse the layout library for ideas",
          "💡 Use the help button if you need assistance",
          "🔄 Come back anytime to manage your projects"
        ],
        highlight: null,
        position: "center"
      }
    };

    setStepContent(tourSteps[currentStep] || {});
    
    // Highlight the element if specified
    if (tourSteps[currentStep]?.highlight) {
      setHighlightedElement(tourSteps[currentStep].highlight);
    } else {
      setHighlightedElement(null);
    }
  }, [currentStep]);

  // Add highlight effect to elements
  useEffect(() => {
    if (highlightedElement) {
      const element = document.querySelector(highlightedElement);
      if (element) {
        element.style.position = 'relative';
        element.style.zIndex = '1000';
        element.style.boxShadow = '0 0 0 4px rgba(59, 130, 246, 0.5)';
        element.style.borderRadius = '8px';
        element.style.transition = 'all 0.3s ease';
      }
    }

    // Cleanup function to remove highlights
    return () => {
      if (highlightedElement) {
        const element = document.querySelector(highlightedElement);
        if (element) {
          element.style.position = '';
          element.style.zIndex = '';
          element.style.boxShadow = '';
          element.style.borderRadius = '';
          element.style.transition = '';
        }
      }
    };
  }, [highlightedElement]);

  if (!isOpen) return null;

  return (
    <div className="tour-guide-overlay">
      <div className={`tour-guide-modal ${stepContent.position === 'center' ? 'tour-guide-center' : 'tour-guide-positioned'}`}>
        <div className="tour-guide-header">
          <h2>{stepContent.title}</h2>
          <button className="tour-guide-close" onClick={onClose}>×</button>
        </div>

        <div className="tour-guide-content">
          <p className="tour-guide-description">{stepContent.content}</p>
          
          {stepContent.tips && stepContent.tips.length > 0 && (
            <div className="tour-guide-tips">
              <h4>💡 Helpful Tips:</h4>
              <ul>
                {stepContent.tips.map((tip, index) => (
                  <li key={index}>{tip}</li>
                ))}
              </ul>
            </div>
          )}
        </div>

        <div className="tour-guide-footer">
          <div className="tour-guide-progress">
            Step {currentStep + 1} of {totalSteps}
            <div className="tour-guide-progress-bar">
              <div 
                className="tour-guide-progress-fill" 
                style={{ width: `${((currentStep + 1) / totalSteps) * 100}%` }}
              ></div>
            </div>
          </div>
          
          <div className="tour-guide-actions">
            <button className="tour-guide-btn tour-guide-btn-secondary" onClick={onSkip}>
              Skip Tour
            </button>
            {currentStep > 0 && (
              <button className="tour-guide-btn tour-guide-btn-secondary" onClick={onPrev}>
                ← Previous
              </button>
            )}
            {currentStep < totalSteps - 1 ? (
              <button className="tour-guide-btn tour-guide-btn-primary" onClick={onNext}>
                Next →
              </button>
            ) : (
              <button className="tour-guide-btn tour-guide-btn-primary" onClick={onClose}>
                🎉 Start Using Dashboard!
              </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default HomeownerDashboardTour;

