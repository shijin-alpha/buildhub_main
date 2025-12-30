import React, { useState, useEffect } from 'react';

const ArchitectRecommendationEngine = ({ 
  userPreferences, 
  onRecommendations, 
  onLoading, 
  showRecommendations = true 
}) => {
  const [recommendations, setRecommendations] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Style preference options for user selection
  const styleOptions = [
    { key: 'modern', label: 'Modern', icon: '🏢' },
    { key: 'contemporary', label: 'Contemporary', icon: '✨' },
    { key: 'minimalist', label: 'Minimalist', icon: '⚪' },
    { key: 'traditional', label: 'Traditional', icon: '🏛️' },
    { key: 'luxury', label: 'Luxury', icon: '💎' },
    { key: 'sustainable', label: 'Sustainable', icon: '🌱' },
    { key: 'eco_friendly', label: 'Eco-friendly', icon: '♻️' },
    { key: 'natural', label: 'Natural', icon: '🌿' },
    { key: 'aesthetic', label: 'Aesthetic', icon: '🎨' },
    { key: 'functional', label: 'Functional', icon: '⚙️' },
    { key: 'elegant', label: 'Elegant', icon: '👑' },
    { key: 'innovative', label: 'Innovative', icon: '💡' }
  ];

  const [selectedStyles, setSelectedStyles] = useState({});

  useEffect(() => {
    if (userPreferences && Object.keys(userPreferences).length > 0) {
      fetchRecommendations(userPreferences);
    }
  }, [userPreferences]);

  const fetchRecommendations = async (preferences) => {
    setLoading(true);
    setError('');
    
    if (onLoading) onLoading(true);

    try {
      const response = await fetch('/buildhub/backend/api/homeowner/recommend_architects.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          preferences: preferences,
          k: 5
        })
      });

      const result = await response.json();
      
      if (result.success) {
        setRecommendations(result.recommendations || []);
        if (onRecommendations) {
          onRecommendations(result.recommendations || []);
        }
      } else {
        setError(result.message || 'Failed to get recommendations');
      }
    } catch (err) {
      setError('Network error. Please try again.');
      console.error('Recommendation error:', err);
    } finally {
      setLoading(false);
      if (onLoading) onLoading(false);
    }
  };

  const handleStyleToggle = (styleKey) => {
    setSelectedStyles(prev => ({
      ...prev,
      [styleKey]: prev[styleKey] ? 0 : 1
    }));
  };

  const handleGetRecommendations = () => {
    const activeStyles = Object.entries(selectedStyles)
      .filter(([_, weight]) => weight > 0)
      .reduce((acc, [key, weight]) => ({ ...acc, [key]: weight }), {});
    
    if (Object.keys(activeStyles).length === 0) {
      setError('Please select at least one style preference');
      return;
    }

    fetchRecommendations(activeStyles);
  };

  const getScoreColor = (score) => {
    if (score >= 0.8) return '#10b981'; // Green
    if (score >= 0.6) return '#f59e0b'; // Yellow
    if (score >= 0.4) return '#f97316'; // Orange
    return '#ef4444'; // Red
  };

  const getScoreLabel = (score) => {
    if (score >= 0.8) return 'Excellent Match';
    if (score >= 0.6) return 'Good Match';
    if (score >= 0.4) return 'Fair Match';
    return 'Low Match';
  };

  if (!showRecommendations) {
    return null;
  }

  return (
    <div className="architect-recommendation-engine">
      <div className="recommendation-header">
        <h3>🎯 AI-Powered Architect Matching</h3>
        <p>Select your style preferences to find the best architects for your project</p>
      </div>

      {/* Style Selection */}
      <div className="style-selection">
        <h4>Select Your Style Preferences:</h4>
        <div className="style-grid">
          {styleOptions.map(option => (
            <button
              key={option.key}
              className={`style-option ${selectedStyles[option.key] ? 'selected' : ''}`}
              onClick={() => handleStyleToggle(option.key)}
            >
              <span className="style-icon">{option.icon}</span>
              <span className="style-label">{option.label}</span>
            </button>
          ))}
        </div>
        
        <button 
          className="btn btn-primary get-recommendations-btn"
          onClick={handleGetRecommendations}
          disabled={loading}
        >
          {loading ? 'Finding Best Matches...' : 'Find Recommended Architects'}
        </button>
      </div>

      {/* Error Display */}
      {error && (
        <div className="alert alert-error">
          {error}
        </div>
      )}

      {/* Recommendations Display */}
      {recommendations.length > 0 && (
        <div className="recommendations-section">
          <h4>🏆 Recommended Architects</h4>
          <div className="recommendations-grid">
            {recommendations.map((rec, index) => (
              <div key={rec.architect.id} className="recommendation-card">
                <div className="recommendation-header">
                  <div className="architect-info">
                    <h5>{rec.architect.first_name} {rec.architect.last_name}</h5>
                    <p className="specialization">{rec.architect.specialization}</p>
                    <div className="architect-stats">
                      <span className="rating">⭐ {rec.architect.avg_rating || 0}/5</span>
                      <span className="experience">{rec.architect.experience_years || 0} years</span>
                      <span className="reviews">{rec.architect.review_count || 0} reviews</span>
                    </div>
                  </div>
                  <div className="match-score">
                    <div 
                      className="score-circle"
                      style={{ 
                        backgroundColor: getScoreColor(rec.composite_score),
                        color: 'white'
                      }}
                    >
                      {Math.round(rec.composite_score * 100)}%
                    </div>
                    <span className="score-label">{getScoreLabel(rec.composite_score)}</span>
                  </div>
                </div>
                
                <div className="match-reasons">
                  <h6>Why this architect matches:</h6>
                  <ul>
                    {rec.match_reasons.map((reason, idx) => (
                      <li key={idx}>
                        <span className="reason-preference">{reason.preference}</span>
                        <span className="reason-strength">
                          {Math.round(reason.strength * 100)}% match
                        </span>
                      </li>
                    ))}
                  </ul>
                </div>
                
                <div className="recommendation-actions">
                  <button className="btn btn-primary btn-sm">
                    View Profile
                  </button>
                  <button className="btn btn-secondary btn-sm">
                    Contact
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      <style jsx>{`
        .architect-recommendation-engine {
          background: #f8fafc;
          border-radius: 12px;
          padding: 24px;
          margin: 20px 0;
          border: 1px solid #e2e8f0;
        }

        .recommendation-header {
          text-align: center;
          margin-bottom: 24px;
        }

        .recommendation-header h3 {
          color: #1e293b;
          margin-bottom: 8px;
          font-size: 1.5rem;
        }

        .recommendation-header p {
          color: #64748b;
          font-size: 0.95rem;
        }

        .style-selection h4 {
          color: #374151;
          margin-bottom: 16px;
          font-size: 1.1rem;
        }

        .style-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
          gap: 12px;
          margin-bottom: 20px;
        }

        .style-option {
          display: flex;
          flex-direction: column;
          align-items: center;
          padding: 12px 8px;
          border: 2px solid #e5e7eb;
          border-radius: 8px;
          background: white;
          cursor: pointer;
          transition: all 0.2s;
          font-size: 0.85rem;
        }

        .style-option:hover {
          border-color: #3b82f6;
          transform: translateY(-1px);
        }

        .style-option.selected {
          border-color: #3b82f6;
          background: #eff6ff;
          color: #1e40af;
        }

        .style-icon {
          font-size: 1.5rem;
          margin-bottom: 4px;
        }

        .style-label {
          font-weight: 500;
        }

        .get-recommendations-btn {
          width: 100%;
          padding: 12px;
          font-size: 1rem;
          font-weight: 600;
        }

        .recommendations-section h4 {
          color: #1e293b;
          margin: 24px 0 16px 0;
          font-size: 1.2rem;
        }

        .recommendations-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
          gap: 16px;
        }

        .recommendation-card {
          background: white;
          border: 1px solid #e5e7eb;
          border-radius: 12px;
          padding: 20px;
          box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .recommendation-header {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          margin-bottom: 16px;
        }

        .architect-info h5 {
          color: #1e293b;
          margin: 0 0 4px 0;
          font-size: 1.1rem;
        }

        .specialization {
          color: #3b82f6;
          font-weight: 600;
          margin: 0 0 8px 0;
          font-size: 0.9rem;
        }

        .architect-stats {
          display: flex;
          gap: 12px;
          font-size: 0.8rem;
          color: #64748b;
        }

        .match-score {
          text-align: center;
        }

        .score-circle {
          width: 50px;
          height: 50px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: 700;
          font-size: 0.9rem;
          margin-bottom: 4px;
        }

        .score-label {
          font-size: 0.75rem;
          color: #64748b;
          font-weight: 500;
        }

        .match-reasons h6 {
          color: #374151;
          margin: 0 0 8px 0;
          font-size: 0.9rem;
        }

        .match-reasons ul {
          list-style: none;
          padding: 0;
          margin: 0;
        }

        .match-reasons li {
          display: flex;
          justify-content: space-between;
          padding: 4px 0;
          font-size: 0.8rem;
        }

        .reason-preference {
          color: #374151;
        }

        .reason-strength {
          color: #3b82f6;
          font-weight: 600;
        }

        .recommendation-actions {
          display: flex;
          gap: 8px;
          margin-top: 16px;
        }

        .alert {
          padding: 12px 16px;
          border-radius: 8px;
          margin: 16px 0;
        }

        .alert-error {
          background: #fef2f2;
          border: 1px solid #fecaca;
          color: #dc2626;
        }
      `}</style>
    </div>
  );
};

export default ArchitectRecommendationEngine;








