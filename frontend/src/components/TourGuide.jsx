import React, { useState, useEffect } from 'react';
import '../styles/TourGuide.css';

const TourGuide = ({ isOpen, onClose, currentStep, totalSteps, onNext, onPrev, onSkip }) => {
  const [stepContent, setStepContent] = useState({});

  useEffect(() => {
    // Define tour content for each step
    const tourSteps = {
      0: {
        title: "Welcome to Custom Design Request",
        content: "This wizard will guide you through creating a detailed request for your custom home design. We'll collect information about your plot, family needs, budget, and preferences to help architects create the perfect design for you.",
        tips: [
          "💡 Take your time with each step - detailed information helps architects understand your vision",
          "📸 You can upload reference images and site photos to help architects visualize your requirements",
          "🏠 Think about your family's current and future needs when planning rooms"
        ]
      },
      1: {
        title: "Preliminary Information",
        content: "Let's start with the basics! Tell us about your plot size, building requirements, and what you're looking for in your new home.",
        tips: [
          "📏 Plot size helps architects understand the space available for design",
          "🏗️ Building size indicates how much of your plot you want to use",
          "📝 Be specific about your requirements - the more detail, the better the design"
        ]
      },
      2: {
        title: "Site Details",
        content: "Now let's understand your plot better. The shape, topography, and local development laws all influence the design possibilities.",
        tips: [
          "📐 Plot shape affects how rooms can be arranged",
          "🏔️ Topography (flat, sloping, etc.) impacts foundation and design options",
          "⚖️ Development laws ensure your design complies with local regulations"
        ]
      },
      3: {
        title: "Family Planning",
        content: "This is where you plan your home around your family's needs. Think about who will live in the house and what rooms you'll need.",
        tips: [
          "👨‍👩‍👧‍👦 Consider your family size and future plans",
          "🛏️ Plan bedrooms for current and future family members",
          "🚿 Bathrooms should be convenient for all family members",
          "📚 Think about work-from-home spaces and study areas"
        ]
      },
      4: {
        title: "Budget Planning",
        content: "Your budget helps architects understand what's possible within your financial constraints. Be realistic about your budget range.",
        tips: [
          "💰 Be honest about your budget - it helps architects design within your means",
          "🏠 Consider both construction and finishing costs",
          "📊 Budget allocation helps prioritize different aspects of your home"
        ]
      },
      5: {
        title: "Site Orientation",
        content: "The direction your plot faces affects natural light, ventilation, and energy efficiency. This information helps architects optimize your design.",
        tips: [
          "☀️ North-facing plots get good natural light",
          "🌬️ Consider prevailing winds for natural ventilation",
          "🌳 Think about existing trees and their impact on light"
        ]
      },
      6: {
        title: "Material Preferences",
        content: "Choose materials that match your style, budget, and maintenance preferences. This helps architects select appropriate materials for your design.",
        tips: [
          "🏗️ Consider durability vs. cost when choosing materials",
          "🎨 Think about the aesthetic you want to achieve",
          "🔧 Consider maintenance requirements of different materials"
        ]
      },
      7: {
        title: "Style Preferences",
        content: "Tell us about your aesthetic preferences. This helps architects understand your taste and create designs that match your vision.",
        tips: [
          "🎨 Browse reference images to find styles you like",
          "🏠 Consider your neighborhood's architectural style",
          "💡 Think about both interior and exterior aesthetics"
        ]
      },
      8: {
        title: "Review Your Request",
        content: "Review all the information you've provided. Make sure everything is accurate and complete before submitting.",
        tips: [
          "✅ Double-check all information for accuracy",
          "📝 Add any additional notes or special requirements",
          "📸 Make sure all images are uploaded correctly"
        ]
      },
      9: {
        title: "Choose Your Architect",
        content: "Select from our network of qualified architects. You can choose multiple architects to get different design perspectives.",
        tips: [
          "👨‍💼 Review architect portfolios and specializations",
          "⭐ Check ratings and reviews from previous clients",
          "🎯 Choose architects whose style matches your preferences",
          "💬 You can select multiple architects for variety"
        ]
      },
      10: {
        title: "Submit Your Request",
        content: "You're almost done! Review your final request and submit it to your selected architects.",
        tips: [
          "📋 Final review of all information",
          "📤 Your request will be sent to selected architects",
          "⏰ Architects typically respond within 24-48 hours",
          "💬 You'll be notified when architects submit their designs"
        ]
      }
    };

    setStepContent(tourSteps[currentStep] || {});
  }, [currentStep]);

  if (!isOpen) return null;

  return (
    <div className="tour-guide-overlay">
      <div className="tour-guide-modal">
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
          </div>
          
          <div className="tour-guide-actions">
            <button className="tour-guide-btn tour-guide-btn-secondary" onClick={onSkip}>
              Skip Tour
            </button>
            {currentStep > 0 && (
              <button className="tour-guide-btn tour-guide-btn-secondary" onClick={onPrev}>
                Previous
              </button>
            )}
            {currentStep < totalSteps - 1 ? (
              <button className="tour-guide-btn tour-guide-btn-primary" onClick={onNext}>
                Next
              </button>
            ) : (
              <button className="tour-guide-btn tour-guide-btn-primary" onClick={onClose}>
                Start Designing!
            </button>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default TourGuide;