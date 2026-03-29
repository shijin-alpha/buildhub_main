import React, { useState } from 'react';
import './PaymentValidationDisplay.css';

/**
 * Payment Validation Display Component
 * 
 * Shows comprehensive validation results for payment requests
 * including errors, warnings, recommendations, and validation score
 */
const PaymentValidationDisplay = ({ 
    validationResult, 
    onRetrySubmission, 
    onProceedAnyway,
    showActions = true,
    isVisible = true 
}) => {
    const [showDetails, setShowDetails] = useState(false);
    const [showAnalysis, setShowAnalysis] = useState(false);

    if (!isVisible || !validationResult) {
        return null;
    }

    const {
        is_valid,
        errors = [],
        warnings = [],
        recommendations = [],
        validation_score = 0,
        validation_details = {}
    } = validationResult;

    const summary = validationResult.summary || getValidationSummary(validation_score, is_valid);

    const getValidationColor = () => {
        if (validation_score >= 90) return '#4CAF50'; // Green
        if (validation_score >= 80) return '#8BC34A'; // Light Green
        if (validation_score >= 70) return '#FF9800'; // Orange
        if (validation_score >= 60) return '#FF5722'; // Deep Orange
        return '#F44336'; // Red
    };

    const getValidationIcon = () => {
        if (validation_score >= 90) return '✅';
        if (validation_score >= 80) return '✔️';
        if (validation_score >= 70) return '⚠️';
        if (validation_score >= 60) return '❌';
        return '🚫';
    };

    function getValidationSummary(score, isValid) {
        if (score >= 90) return { grade: 'A', message: 'Excellent - Payment request meets all requirements' };
        if (score >= 80) return { grade: 'B', message: 'Good - Minor issues to address' };
        if (score >= 70) return { grade: 'C', message: 'Acceptable - Several improvements needed' };
        if (score >= 60) return { grade: 'D', message: 'Poor - Significant issues need attention' };
        return { grade: 'F', message: 'Failed - Critical errors must be fixed' };
    }

    const renderValidationHeader = () => (
        <div className="validation-header">
            <div className="validation-status">
                <div 
                    className="status-indicator"
                    style={{ backgroundColor: getValidationColor() }}
                >
                    <span className="status-icon">{getValidationIcon()}</span>
                </div>
                <div className="status-info">
                    <h4>Payment Request Validation</h4>
                    <div className="status-details">
                        <span className="validation-grade">Grade: {summary.grade}</span>
                        <span className="validation-score">Score: {validation_score}%</span>
                    </div>
                    <p className="status-message">{summary.message}</p>
                </div>
            </div>
            
            <div className="validation-controls">
                <button 
                    className="details-toggle"
                    onClick={() => setShowDetails(!showDetails)}
                >
                    {showDetails ? 'Hide Details' : 'Show Details'}
                </button>
            </div>
        </div>
    );

    const renderValidationIssues = () => (
        <div className="validation-issues">
            {errors.length > 0 && (
                <div className="issues-section errors-section">
                    <h5>❌ Critical Errors ({errors.length})</h5>
                    <ul className="issues-list">
                        {errors.map((error, index) => (
                            <li key={index} className="issue-item error-item">
                                <span className="issue-icon">🚫</span>
                                <span className="issue-text">{error}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {warnings.length > 0 && (
                <div className="issues-section warnings-section">
                    <h5>⚠️ Warnings ({warnings.length})</h5>
                    <ul className="issues-list">
                        {warnings.map((warning, index) => (
                            <li key={index} className="issue-item warning-item">
                                <span className="issue-icon">⚠️</span>
                                <span className="issue-text">{warning}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {recommendations.length > 0 && (
                <div className="issues-section recommendations-section">
                    <h5>💡 Recommendations ({recommendations.length})</h5>
                    <ul className="issues-list">
                        {recommendations.map((recommendation, index) => (
                            <li key={index} className="issue-item recommendation-item">
                                <span className="issue-icon">💡</span>
                                <span className="issue-text">{recommendation}</span>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );

    const renderValidationAnalysis = () => {
        const { amount_analysis, stage_analysis, breakdown_analysis, business_analysis } = validation_details;
        
        if (!amount_analysis && !stage_analysis && !breakdown_analysis && !business_analysis) {
            return null;
        }

        return (
            <div className="validation-analysis">
                <div className="analysis-header">
                    <h5>📊 Detailed Analysis</h5>
                    <button 
                        className="analysis-toggle"
                        onClick={() => setShowAnalysis(!showAnalysis)}
                    >
                        {showAnalysis ? 'Hide Analysis' : 'Show Analysis'}
                    </button>
                </div>

                {showAnalysis && (
                    <div className="analysis-content">
                        {amount_analysis && (
                            <div className="analysis-section">
                                <h6>💰 Amount Analysis</h6>
                                {amount_analysis.percentage_of_project && (
                                    <p>Percentage of project cost: {amount_analysis.percentage_of_project.toFixed(1)}%</p>
                                )}
                            </div>
                        )}

                        {stage_analysis && (
                            <div className="analysis-section">
                                <h6>🏗️ Stage Analysis</h6>
                                {stage_analysis.percentage_of_project && (
                                    <p>Stage percentage: {stage_analysis.percentage_of_project.toFixed(1)}%</p>
                                )}
                                {stage_analysis.typical_range && (
                                    <p>Typical range: {stage_analysis.typical_range[0]}-{stage_analysis.typical_range[1]}%</p>
                                )}
                            </div>
                        )}

                        {breakdown_analysis && (
                            <div className="analysis-section">
                                <h6>📋 Cost Breakdown Analysis</h6>
                                <div className="breakdown-grid">
                                    {breakdown_analysis.labor_percentage && (
                                        <div className="breakdown-item">
                                            <span>Labor:</span>
                                            <span>{breakdown_analysis.labor_percentage.toFixed(1)}%</span>
                                        </div>
                                    )}
                                    {breakdown_analysis.material_percentage && (
                                        <div className="breakdown-item">
                                            <span>Materials:</span>
                                            <span>{breakdown_analysis.material_percentage.toFixed(1)}%</span>
                                        </div>
                                    )}
                                    {breakdown_analysis.equipment_percentage && (
                                        <div className="breakdown-item">
                                            <span>Equipment:</span>
                                            <span>{breakdown_analysis.equipment_percentage.toFixed(1)}%</span>
                                        </div>
                                    )}
                                    {breakdown_analysis.other_percentage && (
                                        <div className="breakdown-item">
                                            <span>Other:</span>
                                            <span>{breakdown_analysis.other_percentage.toFixed(1)}%</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        );
    };

    const renderValidationActions = () => {
        if (!showActions) return null;

        return (
            <div className="validation-actions">
                {!is_valid && errors.length > 0 && (
                    <button 
                        className="retry-btn"
                        onClick={onRetrySubmission}
                    >
                        🔄 Fix Issues & Retry
                    </button>
                )}
                
                {is_valid || (warnings.length > 0 && errors.length === 0) ? (
                    <button 
                        className="proceed-btn"
                        onClick={onProceedAnyway}
                    >
                        ✅ Submit Payment Request
                    </button>
                ) : null}
                
                {!is_valid && errors.length > 0 && (
                    <button 
                        className="proceed-anyway-btn"
                        onClick={onProceedAnyway}
                    >
                        ⚠️ Submit Anyway
                    </button>
                )}
            </div>
        );
    };

    return (
        <div className="payment-validation-display">
            {renderValidationHeader()}
            
            {showDetails && (
                <div className="validation-details">
                    {renderValidationIssues()}
                    {renderValidationAnalysis()}
                </div>
            )}
            
            {renderValidationActions()}
            
            <div className="validation-disclaimer">
                <p>
                    <strong>Note:</strong> This validation system ensures payment requests 
                    meet business rules and logical requirements. High scores indicate 
                    well-structured requests that are likely to be approved quickly.
                </p>
            </div>
        </div>
    );
};

export default PaymentValidationDisplay;