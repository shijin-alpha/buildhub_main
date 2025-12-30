import React, { useState, useEffect, useCallback } from 'react';
import './ArchitectSelection.css';

const ArchitectSelection = ({
  selectedArchitectIds = [],
  onSelectionChange,
  layoutRequestId = null,
  showAIRecommendations = false,
  stylePreferences = {}
}) => {
  // State management
  const [architects, setArchitects] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  const [specialization, setSpecialization] = useState('');
  const [minExperience, setMinExperience] = useState('');
  const [sortBy, setSortBy] = useState('best');
  const [showFilters, setShowFilters] = useState(false);
  const [expandedArchitect, setExpandedArchitect] = useState(null);
  const [reviews, setReviews] = useState({});
  const [loadingReviews, setLoadingReviews] = useState({});
  const [aiRecommendations, setAiRecommendations] = useState([]);
  const [showRecommendations, setShowRecommendations] = useState(false);

  // Debounced search effect
  useEffect(() => {
    const timeoutId = setTimeout(() => {
      fetchArchitects();
    }, 300);
    return () => clearTimeout(timeoutId);
  }, [searchTerm, specialization, minExperience]);

  // Fetch architects with filters
  const fetchArchitects = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const params = new URLSearchParams();
      if (searchTerm.trim()) params.append('search', searchTerm.trim());
      if (specialization.trim()) params.append('specialization', specialization.trim());
      if (minExperience) params.append('min_experience', minExperience);
      if (layoutRequestId) params.append('layout_request_id', layoutRequestId);

      const queryString = params.toString();
      const url = `/buildhub/backend/api/homeowner/get_architects.php${queryString ? `?${queryString}` : ''}`;

      const response = await fetch(url, { credentials: 'include' });
      const result = await response.json();

      if (result.success) {
        setArchitects(result.architects || []);
      } else {
        setError(result.message || 'Failed to load architects');
      }
    } catch (err) {
      setError('Error loading architects: ' + err.message);
    } finally {
      setLoading(false);
    }
  }, [searchTerm, specialization, minExperience, layoutRequestId]);

  // Load initial data
  useEffect(() => {
    fetchArchitects();
  }, []);

  // Generate AI recommendations based on style preferences
  useEffect(() => {
    if (showAIRecommendations && Object.keys(stylePreferences).length > 0 && architects.length > 0) {
      // Debounce AI recommendations to prevent infinite loops
      const timeoutId = setTimeout(() => {
        generateAIRecommendations();
      }, 300);

      return () => clearTimeout(timeoutId);
    }
  }, [architects, stylePreferences, showAIRecommendations]);

  // Generate AI recommendations using KNN engine
  const generateAIRecommendations = async () => {
    if (!showAIRecommendations || Object.keys(stylePreferences).length === 0) {
      return;
    }

    try {
      setLoading(true);

      // Prepare project data for KNN recommendation
      const projectData = {
        budget_range: stylePreferences.budget_range || '',
        plot_size: stylePreferences.plot_size || '',
        plot_unit: stylePreferences.plot_unit || 'cents',
        building_size: stylePreferences.building_size || '',
        num_floors: stylePreferences.num_floors || '1',
        aesthetic: stylePreferences.aesthetic || '',
        rooms: stylePreferences.rooms || [],
        location: stylePreferences.location || '',
        num_recommendations: 5
      };

      // Let the API handle defaults like ml_simple.py does
      // The KNN API will provide sensible defaults for missing fields

      console.log('🤖 Sending project data to KNN engine:', projectData);

      // Call KNN recommendation API
      const response = await fetch('http://localhost:5001/api/architect-recommendations', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(projectData)
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (result.success && result.recommendations) {
        console.log('🎯 KNN recommendations received:', result.recommendations);

        // Map KNN recommendations to architect format
        const mappedRecommendations = result.recommendations.map((rec, index) => ({
          id: rec.architect_id,
          first_name: rec.name.split(' ')[0] || '',
          last_name: rec.name.split(' ').slice(1).join(' ') || '',
          email: rec.email,
          specialization: rec.specialty,
          experience_years: rec.experience_years,
          avg_rating: rec.rating,
          review_count: rec.num_reviews,
          location: rec.location,
          price_range_min: rec.price_range_min,
          price_range_max: rec.price_range_max,
          specializations: rec.specializations,
          portfolio_count: rec.portfolio_count,
          success_rate: rec.success_rate,
          response_time_hours: rec.response_time_hours,
          // KNN specific fields
          aiScore: Math.round(rec.similarity_score * 100), // Convert to percentage
          aiReasons: [rec.match_reason],
          knnSimilarity: rec.similarity_score,
          knnRank: index + 1
        }));

        setAiRecommendations(mappedRecommendations);
        console.log('✅ AI recommendations updated:', mappedRecommendations);
      } else {
        console.warn('⚠️ KNN API returned no recommendations:', result);
        setAiRecommendations([]);
      }
    } catch (error) {
      console.error('❌ Error fetching KNN recommendations:', error);

      // Check if it's a connection error (API not running)
      if (error.message.includes('Failed to fetch') || error.message.includes('ERR_CONNECTION_REFUSED')) {
        console.log('⚠️ KNN API server not running, using fallback recommendations');
      } else if (error.message.includes('404')) {
        console.log('⚠️ No architects found in database, using fallback recommendations');
      }

      // Fallback to simple recommendations if KNN fails
      const fallbackRecommendations = architects.map(architect => {
        let score = 0;
        let reasons = [];

        // Score based on specialization match
        if (stylePreferences.aesthetic) {
          const aesthetic = stylePreferences.aesthetic.toLowerCase();
          const specialization = (architect.specialization || '').toLowerCase();

          if (specialization.includes(aesthetic) || aesthetic.includes(specialization)) {
            score += 30;
            reasons.push('Specialization matches your style preference');
          }
        }

        // Score based on experience
        if (architect.experience_years >= 5) {
          score += 20;
          reasons.push('Experienced professional');
        } else if (architect.experience_years >= 3) {
          score += 15;
          reasons.push('Good experience level');
        }

        // Score based on rating
        if (architect.avg_rating >= 4.5) {
          score += 25;
          reasons.push('Highly rated by clients');
        } else if (architect.avg_rating >= 4.0) {
          score += 20;
          reasons.push('Well-rated professional');
        } else if (architect.avg_rating >= 3.5) {
          score += 15;
          reasons.push('Good client feedback');
        }

        // Score based on review count
        if (architect.review_count >= 10) {
          score += 15;
          reasons.push('Many client reviews');
        } else if (architect.review_count >= 5) {
          score += 10;
          reasons.push('Several client reviews');
        }

        // Bonus for verified architects
        if (architect.is_verified) {
          score += 10;
          reasons.push('Verified professional');
        }

        return {
          ...architect,
          aiScore: score,
          aiReasons: reasons,
          knnSimilarity: null,
          knnRank: null
        };
      }).filter(architect => architect.aiScore > 0)
        .sort((a, b) => b.aiScore - a.aiScore)
        .slice(0, 3);

      setAiRecommendations(fallbackRecommendations);
      console.log('🔄 Using fallback recommendations:', fallbackRecommendations);
    } finally {
      setLoading(false);
    }
  };

  // Toggle architect selection
  const toggleArchitect = (architectId) => {
    const newSelection = selectedArchitectIds.includes(architectId)
      ? selectedArchitectIds.filter(id => id !== architectId)
      : [...selectedArchitectIds, architectId];

    // Get the architect objects for the selected IDs
    const selectedArchitectObjects = architects.filter(architect =>
      newSelection.includes(architect.id)
    );

    onSelectionChange(newSelection, selectedArchitectObjects);
  };

  // Toggle architect details
  const toggleArchitectDetails = async (architectId) => {
    if (expandedArchitect === architectId) {
      setExpandedArchitect(null);
      return;
    }

    setExpandedArchitect(architectId);

    // Load reviews if not already loaded
    if (!reviews[architectId] && !loadingReviews[architectId]) {
      setLoadingReviews(prev => ({ ...prev, [architectId]: true }));

      try {
        const response = await fetch(`/buildhub/backend/api/reviews/get_reviews.php?architect_id=${architectId}`);
        const result = await response.json();

        if (result.success) {
          setReviews(prev => ({ ...prev, [architectId]: result.reviews || [] }));
        }
      } catch (err) {
        console.error('Error loading reviews:', err);
      } finally {
        setLoadingReviews(prev => ({ ...prev, [architectId]: false }));
      }
    }
  };

  // Clear all filters
  const clearFilters = () => {
    setSearchTerm('');
    setSpecialization('');
    setMinExperience('');
    setSortBy('best');
  };

  // Sort architects
  const sortedArchitects = [...architects].sort((a, b) => {
    switch (sortBy) {
      case 'experience':
        return (b.experience_years || 0) - (a.experience_years || 0);
      case 'rating':
        return (b.avg_rating || 0) - (a.avg_rating || 0);
      case 'recent':
        return new Date(b.created_at || 0) - new Date(a.created_at || 0);
      default:
        return 0; // Best match - keep original order
    }
  });

  // Render star rating
  const renderStars = (rating) => {
    const filled = '★'.repeat(Math.floor(rating || 0));
    const empty = '☆'.repeat(5 - Math.floor(rating || 0));
    return filled + empty;
  };

  // Get architect initials
  const getInitials = (firstName, lastName) => {
    const first = (firstName || '').charAt(0).toUpperCase();
    const last = (lastName || '').charAt(0).toUpperCase();
    return first + last;
  };

  return (
    <div className="architect-selection-container">
      {/* Header */}
      <div className="architect-selection-header">
        <h3 className="architect-selection-title">Choose Your Architect</h3>
        <p className="architect-selection-subtitle">
          Select one or more architects to work on your project
          {showAIRecommendations && Object.keys(stylePreferences).length > 0 && (
            <span className="ai-recommendation-badge">
              🤖 AI recommendations based on your style preferences
            </span>
          )}
        </p>
      </div>

      {/* Search and Filters */}
      <div className="architect-search-section">
        <div className="search-bar-container">
          <div className="search-input-group">
            <i className="fas fa-search search-icon"></i>
            <input
              type="text"
              placeholder="Search by name, company, or email..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="search-input"
            />
          </div>
          <button
            className="filter-toggle-btn"
            onClick={() => setShowFilters(!showFilters)}
          >
            <i className="fas fa-filter"></i>
            Filters
            {(specialization || minExperience) && <span className="filter-indicator"></span>}
          </button>
        </div>

        {/* Advanced Filters */}
        {showFilters && (
          <div className="advanced-filters">
            <div className="filter-row">
              <div className="filter-group">
                <label>Specialization</label>
                <input
                  type="text"
                  placeholder="e.g., Residential, Commercial, Interior"
                  value={specialization}
                  onChange={(e) => setSpecialization(e.target.value)}
                />
              </div>
              <div className="filter-group">
                <label>Min Experience (years)</label>
                <input
                  type="number"
                  min="0"
                  placeholder="0"
                  value={minExperience}
                  onChange={(e) => setMinExperience(e.target.value)}
                />
              </div>
              <div className="filter-group">
                <label>Sort by</label>
                <select value={sortBy} onChange={(e) => setSortBy(e.target.value)}>
                  <option value="best">Best Match</option>
                  <option value="rating">Highest Rating</option>
                  <option value="experience">Most Experience</option>
                  <option value="recent">Recently Joined</option>
                </select>
              </div>
            </div>
            <div className="filter-actions">
              <button className="clear-filters-btn" onClick={clearFilters}>
                <i className="fas fa-times"></i>
                Clear Filters
              </button>
              <div className="selected-count">
                {selectedArchitectIds.length} selected
              </div>
            </div>
          </div>
        )}
      </div>

      {/* AI Recommendations */}
      {showAIRecommendations && aiRecommendations.length > 0 && (
        <div className="ai-recommendations-section">
          <div className="recommendations-header">
            <h4 className="recommendations-title">
              <i className="fas fa-robot"></i>
              AI Recommendations
            </h4>
            <button
              className="toggle-recommendations-btn"
              onClick={() => setShowRecommendations(!showRecommendations)}
            >
              {showRecommendations ? 'Hide' : 'Show'} Recommendations
              <i className={`fas fa-chevron-${showRecommendations ? 'up' : 'down'}`}></i>
            </button>
          </div>

          {showRecommendations && (
            <div className="recommendations-grid">
              {aiRecommendations.map((architect, index) => (
                <div
                  key={architect.id}
                  className={`recommendation-card ${selectedArchitectIds.includes(architect.id) ? 'selected' : ''}`}
                  onClick={() => toggleArchitect(architect.id)}
                >
                  <div className="recommendation-badge">
                    <span className="badge-rank">#{architect.knnRank || index + 1}</span>
                    <span className="badge-score">{architect.aiScore}% Match</span>
                    {architect.knnSimilarity && (
                      <span className="badge-knn">🤖 KNN</span>
                    )}
                  </div>

                  <div className="recommendation-content">
                    <div className="architect-info">
                      <div className="architect-avatar">
                        {getInitials(architect.first_name, architect.last_name)}
                      </div>
                      <div className="architect-details">
                        <h5 className="architect-name">
                          {`${architect.first_name || ''} ${architect.last_name || ''}`.trim() || `Architect #${architect.id}`}
                        </h5>
                        <p className="architect-email">{architect.email}</p>
                      </div>
                    </div>

                    <div className="recommendation-reasons">
                      <h6>Why we recommend:</h6>
                      <ul>
                        {architect.aiReasons.map((reason, reasonIndex) => (
                          <li key={reasonIndex}>{reason}</li>
                        ))}
                      </ul>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Error State */}
      {error && (
        <div className="error-message">
          <i className="fas fa-exclamation-triangle"></i>
          {error}
        </div>
      )}

      {/* Loading State */}
      {loading && (
        <div className="loading-state">
          <div className="loading-spinner"></div>
          <span>Loading architects...</span>
        </div>
      )}

      {/* Architect Grid */}
      {!loading && (
        <div className="architect-grid">
          {sortedArchitects.map(architect => (
            <div
              key={architect.id}
              className={`architect-card ${selectedArchitectIds.includes(architect.id) ? 'selected' : ''} ${architect.already_assigned ? 'disabled' : ''}`}
              onClick={() => !architect.already_assigned && toggleArchitect(architect.id)}
            >
              {/* Selection Indicator */}
              <div className="selection-indicator">
                {selectedArchitectIds.includes(architect.id) && <i className="fas fa-check"></i>}
              </div>

              {/* Status Badge */}
              {architect.already_assigned && (
                <div className="status-badge assigned">
                  <i className="fas fa-user-check"></i>
                  Already Assigned
                </div>
              )}

              {/* Card Header */}
              <div className="architect-card-header">
                <div className="architect-info">
                  <div className="architect-avatar">
                    {getInitials(architect.first_name, architect.last_name)}
                  </div>
                  <div className="architect-details">
                    <h4 className="architect-name">
                      {`${architect.first_name || ''} ${architect.last_name || ''}`.trim() || `Architect #${architect.id}`}
                    </h4>
                    <p className="architect-email">{architect.email}</p>
                    {architect.company_name && (
                      <p className="architect-company">{architect.company_name}</p>
                    )}
                  </div>
                </div>
              </div>

              {/* Rating */}
              <div className="architect-rating">
                <div className="rating-stars">
                  {renderStars(architect.avg_rating)}
                </div>
                <span className="rating-text">
                  {typeof architect.avg_rating === 'number' ? `${architect.avg_rating.toFixed(1)}/5` : 'No rating'}
                </span>
                <span className="rating-count">
                  ({architect.review_count || 0} reviews)
                </span>
              </div>

              {/* Tags */}
              <div className="architect-tags">
                {architect.specialization && (
                  <span className="architect-tag specialization">
                    <i className="fas fa-briefcase"></i>
                    {architect.specialization}
                  </span>
                )}
                {typeof architect.experience_years === 'number' && (
                  <span className="architect-tag experience">
                    <i className="fas fa-calendar-alt"></i>
                    {architect.experience_years} years
                  </span>
                )}
              </div>

              {/* Action Button */}
              <button
                className="architect-action-btn"
                onClick={(e) => {
                  e.stopPropagation();
                  toggleArchitectDetails(architect.id);
                }}
              >
                <i className="fas fa-eye"></i>
                {expandedArchitect === architect.id ? 'Hide Details' : 'View Details'}
              </button>

              {/* Expanded Details */}
              {expandedArchitect === architect.id && (
                <div className="architect-details-expanded">
                  <div className="details-section">
                    <h5>Reviews</h5>
                    {loadingReviews[architect.id] ? (
                      <div className="loading-reviews">Loading reviews...</div>
                    ) : reviews[architect.id]?.length > 0 ? (
                      <div className="reviews-list">
                        {reviews[architect.id].slice(0, 3).map(review => (
                          <div key={review.id} className="review-item">
                            <div className="review-header">
                              <span className="review-author">{review.author || 'Homeowner'}</span>
                              <span className="review-rating">{renderStars(review.rating)}</span>
                            </div>
                            <p className="review-comment">{review.comment}</p>
                            <span className="review-date">
                              {new Date(review.created_at).toLocaleDateString()}
                            </span>
                          </div>
                        ))}
                        {reviews[architect.id].length > 3 && (
                          <p className="more-reviews">+{reviews[architect.id].length - 3} more reviews</p>
                        )}
                      </div>
                    ) : (
                      <p className="no-reviews">No reviews yet</p>
                    )}
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {/* Empty State */}
      {!loading && sortedArchitects.length === 0 && (
        <div className="empty-state">
          <div className="empty-icon">🧑‍🎨</div>
          <h3>No architects found</h3>
          <p>Try adjusting your search criteria or filters</p>
          <button className="retry-btn" onClick={fetchArchitects}>
            <i className="fas fa-refresh"></i>
            Try Again
          </button>
        </div>
      )}
    </div>
  );
};

export default ArchitectSelection;
