import React, { useState, useEffect } from 'react';
import './ImageRelevanceValidator.css';

/**
 * Image Relevance Validator Component
 * 
 * Displays validation results for generated room improvement images
 * and provides options to regenerate if images are not relevant
 */
const ImageRelevanceValidator = ({ 
    validationResult, 
    onRegenerateImage, 
    onAcceptImage,
    imageUrl,
    roomType,
    isVisible = true 
}) => {
    const [showDetails, setShowDetails] = useState(false);
    const [userFeedback, setUserFeedback] = useState('');
    const [isSubmittingFeedback, setIsSubmittingFeedback] = useState(false);

    if (!isVisible || !validationResult) {
        return null;
    }

    const { 
        is_relevant, 
        confidence_score, 
        issues_found = [], 
        recommendations = [],
        validation_details = []
    } = validationResult;

    const getValidationStatusColor = () => {
        if (confidence_score >= 80) return '#4CAF50'; // Green
        if (confidence_score >= 60) return '#FF9800'; // Orange
        return '#F44336'; // Red
    };

    const getValidationStatusText = () => {
        if (confidence_score >= 80) return 'Highly Relevant';
        if (confidence_score >= 60) return 'Moderately Relevant';
        return 'Low Relevance';
    };

    const handleRegenerateClick = () => {
        if (onRegenerateImage) {
            onRegenerateImage({
                reason: 'relevance_validation_failed',
                confidence_score,
                issues: issues_found,
                user_feedback: userFeedback
            });
        }
    };

    const handleAcceptClick = () => {
        if (onAcceptImage) {
            onAcceptImage({
                validation_accepted: true,
                confidence_score,
                user_feedback: userFeedback
            });
        }
    };

    const submitUserFeedback = async () => {
        if (!userFeedback.trim()) return;
        
        setIsSubmittingFeedback(true);
        try {
            // Submit feedback to improve validation system
            await fetch('/buildhub/backend/api/homeowner/submit_validation_feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    validation_result: validationResult,
                    user_feedback: userFeedback,
                    room_type: roomType,
                    image_url: imageUrl
                })
            });
            
            setUserFeedback('');
            alert('Thank you for your feedback! This helps improve our image validation system.');
        } catch (error) {
            console.error('Error submitting feedback:', error);
        } finally {
            setIsSubmittingFeedback(false);
        }
    };

    return (
        <div className="image-relevance-validator">
            <div className="validation-header">
                <div className="validation-status">
                    <div 
                        className="status-indicator"
                        style={{ backgroundColor: getValidationStatusColor() }}
                    ></div>
                    <div className="status-info">
                        <h4>Image Relevance Check</h4>
                        <p className="status-text">
                            {getValidationStatusText()} ({confidence_score}% confidence)
                        </p>
                    </div>
                </div>
                
                <button 
                    className="details-toggle"
                    onClick={() => setShowDetails(!showDetails)}
                >
                    {showDetails ? 'Hide Details' : 'Show Details'}
                </button>
            </div>

            {!is_relevant && (
                <div className="validation-warning">
                    <div className="warning-icon">⚠️</div>
                    <div className="warning-content">
                        <h5>Image Relevance Issue Detected</h5>
                        <p>
                            The generated image may not be appropriate for a {roomType}. 
                            Consider regenerating for better results.
                        </p>
                    </div>
                </div>
            )}

            {showDetails && (
                <div className="validation-details">
                    {issues_found.length > 0 && (
                        <div className="issues-section">
                            <h5>Issues Found:</h5>
                            <ul className="issues-list">
                                {issues_found.map((issue, index) => (
                                    <li key={index} className="issue-item">
                                        <span className="issue-icon">❌</span>
                                        {issue}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {recommendations.length > 0 && (
                        <div className="recommendations-section">
                            <h5>Recommendations:</h5>
                            <ul className="recommendations-list">
                                {recommendations.map((recommendation, index) => (
                                    <li key={index} className="recommendation-item">
                                        <span className="recommendation-icon">💡</span>
                                        {recommendation}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {validation_details.length > 0 && (
                        <div className="validation-details-section">
                            <h5>Validation Details:</h5>
                            <ul className="details-list">
                                {validation_details.map((detail, index) => (
                                    <li key={index} className="detail-item">
                                        <span className="detail-icon">ℹ️</span>
                                        {detail}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
            )}

            <div className="user-feedback-section">
                <h5>Your Feedback (Optional):</h5>
                <textarea
                    className="feedback-textarea"
                    placeholder="Is this image appropriate for your room? Any specific concerns or suggestions?"
                    value={userFeedback}
                    onChange={(e) => setUserFeedback(e.target.value)}
                    rows={3}
                />
                {userFeedback.trim() && (
                    <button 
                        className="submit-feedback-btn"
                        onClick={submitUserFeedback}
                        disabled={isSubmittingFeedback}
                    >
                        {isSubmittingFeedback ? 'Submitting...' : 'Submit Feedback'}
                    </button>
                )}
            </div>

            <div className="validation-actions">
                {confidence_score < 70 && (
                    <button 
                        className="regenerate-btn"
                        onClick={handleRegenerateClick}
                    >
                        🔄 Regenerate Image
                    </button>
                )}
                
                <button 
                    className="accept-btn"
                    onClick={handleAcceptClick}
                >
                    ✅ Use This Image
                </button>
            </div>

            <div className="validation-disclaimer">
                <p>
                    <strong>Note:</strong> This validation system helps ensure generated images 
                    are appropriate for your selected room type. The AI analyzes objects, 
                    style, and context to provide relevance feedback.
                </p>
            </div>
        </div>
    );
};

export default ImageRelevanceValidator;