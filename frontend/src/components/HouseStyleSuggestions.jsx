import React, { useState, useEffect } from 'react';
import './HouseStyleSuggestions.css';

const HouseStyleSuggestions = ({ 
  formData = {}, 
  onStyleChange,
  showSuggestions = true,
  autoSelect = false
}) => {
  const [suggestions, setSuggestions] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [selectedStyle, setSelectedStyle] = useState('');

  // Generate style suggestions based on form data
  useEffect(() => {
    if (showSuggestions && Object.keys(formData).length > 0) {
      generateSuggestions();
    }
  }, [formData, showSuggestions]);

  const generateSuggestions = async () => {
    setLoading(true);
    setError('');

    try {
      const plotSize = parseFloat(formData.plot_size || 0);
      const buildingSize = parseFloat(formData.building_size || 0);
      const budgetRange = formData.budget_range || '';
      const numFloors = parseInt(formData.num_floors || 1);
      const rooms = formData.rooms || [];
      const location = formData.location || '';

      // Parse budget range to get numeric value
      let budgetValue = 0;
      if (budgetRange.includes('5-10')) budgetValue = 7500000; // 7.5 lakhs
      else if (budgetRange.includes('10-20')) budgetValue = 15000000; // 15 lakhs
      else if (budgetRange.includes('20-30')) budgetValue = 25000000; // 25 lakhs
      else if (budgetRange.includes('30-50')) budgetValue = 40000000; // 40 lakhs
      else if (budgetRange.includes('50-75')) budgetValue = 62500000; // 62.5 lakhs
      else if (budgetRange.includes('75 Lakhs - 1 Crore')) budgetValue = 87500000; // 87.5 lakhs
      else if (budgetRange.includes('1-2 Crores')) budgetValue = 150000000; // 1.5 crores
      else if (budgetRange.includes('2-5 Crores')) budgetValue = 350000000; // 3.5 crores
      else if (budgetRange.includes('5+ Crores')) budgetValue = 750000000; // 7.5 crores

      // Convert plot size to square feet for consistent calculations
      let plotSizeSqft = plotSize;
      if (formData.plot_unit === 'cents') {
        plotSizeSqft = plotSize * 435.6; // 1 cent = 435.6 sq ft
      } else if (formData.plot_unit === 'acres') {
        plotSizeSqft = plotSize * 43560; // 1 acre = 43560 sq ft
      }

      const styleSuggestions = [];

      // High-end luxury styles (1+ crores)
      if (budgetValue >= 100000000) {
        styleSuggestions.push({
          name: 'Modern Luxury',
          description: 'Ultra-modern design with premium materials and smart home integration',
          features: ['Glass facades', 'Smart home automation', 'Premium marble', 'Landscaped gardens', 'Swimming pool'],
          budgetRange: '1+ Crores',
          suitability: 'Very High',
          image: '🏢',
          reason: 'Perfect for luxury budget with premium features'
        });

        styleSuggestions.push({
          name: 'Mediterranean Villa',
          description: 'Elegant Mediterranean-inspired design with spacious layouts',
          features: ['Terracotta roofs', 'Arched windows', 'Open courtyards', 'Natural stone', 'Private gardens'],
          budgetRange: '1+ Crores',
          suitability: 'Very High',
          image: '🏛️',
          reason: 'Ideal for large plots with luxury budget'
        });

        styleSuggestions.push({
          name: 'Contemporary Mansion',
          description: 'Sophisticated contemporary design with high-end finishes',
          features: ['Modern architecture', 'Premium materials', 'Smart lighting', 'Home theater', 'Wine cellar'],
          budgetRange: '1+ Crores',
          suitability: 'Very High',
          image: '🏰',
          reason: 'Perfect for high-end contemporary living'
        });
      }

      // Premium styles (50 lakhs - 1 crore)
      if (budgetValue >= 5000000 && budgetValue < 100000000) {
        styleSuggestions.push({
          name: 'Modern Contemporary',
          description: 'Clean lines, open spaces, and modern materials',
          features: ['Minimalist design', 'Large windows', 'Open floor plans', 'Modern fixtures', 'Energy efficient'],
          budgetRange: '50 Lakhs - 1 Crore',
          suitability: 'Very High',
          image: '🏠',
          reason: 'Excellent balance of modern design and functionality'
        });

        styleSuggestions.push({
          name: 'Scandinavian',
          description: 'Nordic-inspired design emphasizing simplicity and functionality',
          features: ['Light wood finishes', 'Natural lighting', 'Minimalist decor', 'Cozy interiors', 'Eco-friendly'],
          budgetRange: '50 Lakhs - 1 Crore',
          suitability: 'High',
          image: '❄️',
          reason: 'Perfect for modern families seeking comfort and style'
        });

        styleSuggestions.push({
          name: 'Industrial Modern',
          description: 'Urban-inspired design with exposed materials and modern elements',
          features: ['Exposed brick', 'Steel beams', 'Concrete finishes', 'High ceilings', 'Modern fixtures'],
          budgetRange: '50 Lakhs - 1 Crore',
          suitability: 'High',
          image: '🏭',
          reason: 'Great for urban settings with modern aesthetic'
        });
      }

      // Mid-range styles (20-50 lakhs)
      if (budgetValue >= 2000000 && budgetValue < 5000000) {
        styleSuggestions.push({
          name: 'Traditional Kerala',
          description: 'Classic Kerala architecture with modern amenities',
          features: ['Sloped roofs', 'Wooden elements', 'Courtyard design', 'Natural ventilation', 'Local materials'],
          budgetRange: '20-50 Lakhs',
          suitability: 'Very High',
          image: '🏘️',
          reason: 'Perfect for Kerala climate and cultural preferences'
        });

        styleSuggestions.push({
          name: 'Modern Minimalist',
          description: 'Simple, functional design focusing on essentials',
          features: ['Clean lines', 'Neutral colors', 'Functional spaces', 'Cost-effective', 'Easy maintenance'],
          budgetRange: '20-50 Lakhs',
          suitability: 'Very High',
          image: '📐',
          reason: 'Ideal for budget-conscious families seeking modern design'
        });

        styleSuggestions.push({
          name: 'Contemporary Indian',
          description: 'Modern Indian design blending traditional and contemporary elements',
          features: ['Indian motifs', 'Modern layout', 'Local materials', 'Cultural elements', 'Functional design'],
          budgetRange: '20-50 Lakhs',
          suitability: 'High',
          image: '🏛️',
          reason: 'Perfect blend of tradition and modernity'
        });
      }

      // Budget-friendly styles (5-20 lakhs)
      if (budgetValue >= 500000 && budgetValue < 2000000) {
        styleSuggestions.push({
          name: 'Affordable Modern',
          description: 'Modern design optimized for budget constraints',
          features: ['Simple design', 'Cost-effective materials', 'Functional layout', 'Energy efficient', 'Easy construction'],
          budgetRange: '5-20 Lakhs',
          suitability: 'Very High',
          image: '🏠',
          reason: 'Best value for money with modern appeal'
        });

        styleSuggestions.push({
          name: 'Traditional Simple',
          description: 'Simple traditional design with essential features',
          features: ['Traditional layout', 'Local materials', 'Simple construction', 'Cost-effective', 'Cultural appeal'],
          budgetRange: '5-20 Lakhs',
          suitability: 'High',
          image: '🏘️',
          reason: 'Perfect for traditional families on a budget'
        });
      }

      // Plot size based suggestions
      if (plotSizeSqft >= 5000) { // Large plots (5000+ sq ft)
        styleSuggestions.push({
          name: 'Farmhouse Style',
          description: 'Spacious single-story design with large outdoor areas',
          features: ['Single story', 'Large verandas', 'Garden spaces', 'Natural materials', 'Outdoor living'],
          budgetRange: 'Any',
          suitability: 'High',
          image: '🌾',
          reason: 'Perfect for large plots with outdoor living focus'
        });

        styleSuggestions.push({
          name: 'Ranch Style',
          description: 'Single-story design with horizontal emphasis',
          features: ['Single story', 'Wide layout', 'Large windows', 'Outdoor spaces', 'Easy access'],
          budgetRange: 'Any',
          suitability: 'High',
          image: '🤠',
          reason: 'Ideal for large plots with accessibility needs'
        });
      }

      // Small plot suggestions (less than 2000 sq ft)
      if (plotSizeSqft < 2000) {
        styleSuggestions.push({
          name: 'Compact Modern',
          description: 'Space-efficient modern design for small plots',
          features: ['Space optimization', 'Vertical design', 'Multi-functional spaces', 'Smart storage', 'Modern fixtures'],
          budgetRange: 'Any',
          suitability: 'Very High',
          image: '📐',
          reason: 'Maximizes space efficiency for small plots'
        });

        styleSuggestions.push({
          name: 'Tiny House Style',
          description: 'Minimalist design maximizing every square foot',
          features: ['Minimal design', 'Multi-purpose furniture', 'Vertical storage', 'Natural light', 'Eco-friendly'],
          budgetRange: 'Any',
          suitability: 'High',
          image: '🏠',
          reason: 'Perfect for small plots with minimalist lifestyle'
        });
      }

      // Floor-based suggestions
      if (numFloors >= 3) {
        styleSuggestions.push({
          name: 'Multi-story Modern',
          description: 'Vertical design optimized for multi-story construction',
          features: ['Vertical gardens', 'Rooftop spaces', 'Elevator provision', 'Stacked design', 'Modern facade'],
          budgetRange: '30+ Lakhs',
          suitability: 'High',
          image: '🏗️',
          reason: 'Optimized for multi-story construction'
        });
      }

      // Room-based suggestions
      if (rooms.includes('garage')) {
        styleSuggestions.push({
          name: 'Contemporary with Garage',
          description: 'Modern design with integrated garage space',
          features: ['Integrated garage', 'Modern facade', 'Functional layout', 'Car-friendly design', 'Modern materials'],
          budgetRange: '20+ Lakhs',
          suitability: 'High',
          image: '🚗',
          reason: 'Perfect for families needing garage space'
        });
      }

      if (rooms.includes('terrace') || rooms.includes('balcony')) {
        styleSuggestions.push({
          name: 'Mediterranean with Terraces',
          description: 'Mediterranean-inspired design with outdoor spaces',
          features: ['Terrace spaces', 'Outdoor living', 'Natural materials', 'Mediterranean elements', 'Garden integration'],
          budgetRange: '30+ Lakhs',
          suitability: 'High',
          image: '🌊',
          reason: 'Ideal for outdoor living enthusiasts'
        });
      }

      // Location-based suggestions
      if (location.toLowerCase().includes('kerala')) {
        styleSuggestions.push({
          name: 'Kerala Traditional',
          description: 'Authentic Kerala architecture with local materials',
          features: ['Tiled roofs', 'Wooden pillars', 'Nalukettu design', 'Local materials', 'Climate adaptation'],
          budgetRange: '15+ Lakhs',
          suitability: 'Very High',
          image: '🏘️',
          reason: 'Perfect for Kerala climate and cultural preferences'
        });
      }

      // Eco-friendly option for all budgets
      if (budgetValue >= 2000000) {
        styleSuggestions.push({
          name: 'Eco-Friendly Modern',
          description: 'Sustainable design with green building practices',
          features: ['Solar panels', 'Rainwater harvesting', 'Natural lighting', 'Sustainable materials', 'Energy efficient'],
          budgetRange: '20+ Lakhs',
          suitability: 'High',
          image: '🌱',
          reason: 'Perfect for environmentally conscious families'
        });
      }

      // Sort by suitability and remove duplicates
      const uniqueSuggestions = styleSuggestions.filter((style, index, self) => 
        index === self.findIndex(s => s.name === style.name)
      ).sort((a, b) => {
        const suitabilityOrder = { 'Very High': 4, 'High': 3, 'Medium': 2, 'Low': 1 };
        return suitabilityOrder[b.suitability] - suitabilityOrder[a.suitability];
      });

      setSuggestions(uniqueSuggestions);

      // Auto-select the first (best) suggestion if autoSelect is enabled
      if (autoSelect && uniqueSuggestions.length > 0 && !selectedStyle) {
        const bestStyle = uniqueSuggestions[0];
        setSelectedStyle(bestStyle.name);
        if (onStyleChange) {
          onStyleChange(bestStyle);
        }
        console.log('🎨 Auto-selected house style:', bestStyle.name);
      }

    } catch (err) {
      setError('Error generating suggestions: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleStyleSelect = (style) => {
    setSelectedStyle(style.name);
    if (onStyleChange) {
      onStyleChange(style);
    }
  };

  const getSuitabilityColor = (suitability) => {
    switch (suitability) {
      case 'Very High': return '#28a745';
      case 'High': return '#17a2b8';
      case 'Medium': return '#ffc107';
      case 'Low': return '#dc3545';
      default: return '#6c757d';
    }
  };

  const getSuitabilityIcon = (suitability) => {
    switch (suitability) {
      case 'Very High': return '⭐⭐⭐⭐⭐';
      case 'High': return '⭐⭐⭐⭐';
      case 'Medium': return '⭐⭐⭐';
      case 'Low': return '⭐⭐';
      default: return '⭐';
    }
  };

  if (loading) {
    return (
      <div className="house-style-suggestions-container">
        <div className="style-loading">
          <div className="loading-spinner"></div>
          <span>Generating style suggestions...</span>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="house-style-suggestions-container">
        <div className="style-error">
          <i className="fas fa-exclamation-triangle"></i>
          {error}
        </div>
      </div>
    );
  }

  if (!showSuggestions || suggestions.length === 0) {
    return null;
  }

  return (
    <div className="house-style-suggestions-container">
      <div className="style-header">
        <h4 className="style-title">
          <i className="fas fa-palette"></i>
          House Style Suggestions
        </h4>
        <p className="style-subtitle">
          AI-powered recommendations based on your project details
        </p>
      </div>

      <div className="style-suggestions-grid">
        {suggestions.map((style, index) => (
          <div 
            key={index}
            className={`style-card ${selectedStyle === style.name ? 'selected' : ''}`}
            onClick={() => handleStyleSelect(style)}
          >
            <div className="style-card-header">
              <div className="style-icon">{style.image}</div>
              <div className="style-info">
                <h5 className="style-name">{style.name}</h5>
                <div className="style-budget">{style.budgetRange}</div>
              </div>
              <div className="style-suitability">
                <span 
                  className="suitability-badge"
                  style={{ color: getSuitabilityColor(style.suitability) }}
                >
                  {getSuitabilityIcon(style.suitability)} {style.suitability}
                </span>
              </div>
            </div>

            <div className="style-description">
              {style.description}
            </div>

            <div className="style-features">
              <h6 className="features-title">Key Features:</h6>
              <ul className="features-list">
                {style.features.map((feature, featureIndex) => (
                  <li key={featureIndex} className="feature-item">
                    <i className="fas fa-check"></i>
                    {feature}
                  </li>
                ))}
              </ul>
            </div>

            <div className="style-actions">
              <button 
                className={`select-style-btn ${selectedStyle === style.name ? 'selected' : ''}`}
                onClick={(e) => {
                  e.stopPropagation();
                  handleStyleSelect(style);
                }}
              >
                {selectedStyle === style.name ? (
                  <>
                    <i className="fas fa-check"></i>
                    Selected
                  </>
                ) : (
                  <>
                    <i className="fas fa-plus"></i>
                    Select Style
                  </>
                )}
              </button>
            </div>
          </div>
        ))}
      </div>

      {selectedStyle && (
        <div className="selected-style-info">
          <div className="selected-style-header">
            <i className="fas fa-check-circle"></i>
            <span>Selected: {selectedStyle}</span>
          </div>
          <p className="selected-style-note">
            This style will be used for architect matching and design recommendations.
          </p>
        </div>
      )}
    </div>
  );
};

export default HouseStyleSuggestions;