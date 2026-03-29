import React, { useState, useEffect } from 'react';
import { useToast } from './ToastProvider.jsx';
import '../styles/RiskAssessmentPreview.css';
import ProjectHealthPanel from './ProjectHealthPanel.jsx';

const RiskAssessmentPreview = ({ 
  formData, 
  onProceed, 
  onRevise, 
  isVisible = false,
  onSavePrediction,   // optional: parent passes this to trigger save after estimate_id is known
}) => {
  const toast = useToast();
  const [riskAssessment, setRiskAssessment] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [animationStep, setAnimationStep] = useState(0);

  // Perform risk assessment when component becomes visible
  useEffect(() => {
    if (isVisible && formData) {
      setAnimationStep(0);
      performRiskAssessment();
    }
  }, [isVisible, formData]);

  // Expose savePredictionToDatabase to parent via callback prop
  useEffect(() => {
    if (onSavePrediction && riskAssessment) {
      onSavePrediction((estimateId) => savePredictionToDatabase(estimateId, riskAssessment));
    }
  }, [riskAssessment]);

  // Animation sequence
  useEffect(() => {
    if (riskAssessment && animationStep < 3) {
      const timer = setTimeout(() => {
        setAnimationStep(prev => prev + 1);
      }, 300);
      return () => clearTimeout(timer);
    }
  }, [riskAssessment, animationStep]);

  const performRiskAssessment = async () => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await fetch('/buildhub/backend/api/ml/predict_construction_risks.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify(formData)
      });
      
      const result = await response.json();
      
      if (result.success) {
        setRiskAssessment(result.data);
        // Prediction saving is deferred until the estimate is created (estimate_id comes from onProceed)
      } else {
        throw new Error(result.error || 'Risk assessment failed');
      }
    } catch (err) {
      console.error('Risk assessment error:', err);
      setError(err.message);
      toast.error('Failed to perform risk assessment: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  /**
   * Save prediction to database.
   * Must only be called AFTER the estimate has been created and estimate_id is known.
   * Call this from the parent after onProceed resolves with a valid estimate_id.
   */
  const savePredictionToDatabase = async (estimateId, predictions) => {
    if (!estimateId) {
      console.warn('⚠️ savePredictionToDatabase called without estimate_id — skipping');
      return;
    }

    // Derive probability from the probabilities map using the predicted label
    const costLabel       = predictions.cost_overrun_risk?.risk_level || predictions.cost_overrun_risk?.label;
    const costProbability = predictions.cost_overrun_risk?.probabilities?.[costLabel] ?? 0.5;
    const timeLabel       = predictions.time_delay_risk?.risk_level || predictions.time_delay_risk?.label;
    const timeProbability = predictions.time_delay_risk?.probabilities?.[timeLabel] ?? 0.5;

    try {
      const response = await fetch('/buildhub/backend/api/ml/save_estimate_prediction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          estimate_id:      estimateId,
          cost_risk_level:  costLabel  || 'Medium',
          cost_probability: costProbability,
          time_risk_level:  timeLabel  || 'Medium',
          time_probability: timeProbability,
          model_version:    predictions.model_version || 'v1.0.0',
        })
      });

      const result = await response.json();
      if (result.status === 'success') {
        console.log('✅ Prediction saved to database:', result.data);
      } else {
        console.warn('⚠️ Failed to save prediction:', result.message);
      }
    } catch (err) {
      console.error('❌ Error saving prediction:', err);
    }
  };

  const getRiskColor = (riskLevel) => {
    switch (riskLevel?.toLowerCase()) {
      case 'low': return '#10b981';
      case 'medium': return '#f59e0b';
      case 'high': return '#ef4444';
      default: return '#6b7280';
    }
  };

  const getRiskIcon = (riskLevel) => {
    switch (riskLevel?.toLowerCase()) {
      case 'low': return '🟢';
      case 'medium': return '🟡';
      case 'high': return '🔴';
      default: return '⚪';
    }
  };

  // Convert technical explanations to simple language
  const simplifyExplanation = (explanation) => {
    if (!explanation) return '';
    
    // Replace technical terms with simple language
    return explanation
      .replace(/Design complexity score of (\d+)/i, 'Your design has $1 special features')
      .replace(/Budget per sq\.ft of ₹(\d+)/i, 'Your budget of ₹$1 per square foot')
      .replace(/is a key factor in cost overrun risk/i, 'may increase costs')
      .replace(/significantly influences cost overrun risk/i, 'could lead to higher expenses')
      .replace(/influences cost overrun risk/i, 'affects your budget')
      .replace(/Budget Amount \(value: ([\d.]+)\)/i, 'Your total budget of ₹$1')
      .replace(/contributes to time delay risk/i, 'may take more time to build')
      .replace(/impacts time delay risk/i, 'could delay completion')
      .replace(/affects time delay probability/i, 'influences project timeline')
      .replace(/Site difficulty score of (\d+)/i, 'Your site has some challenges (level $1)')
      .replace(/Planned duration of (\d+) months/i, '$1 months construction time');
  };

  // Get user-friendly risk message
  const getRiskMessage = (riskLevel, type) => {
    const messages = {
      cost: {
        low: "Great news! Your project budget looks realistic and achievable.",
        medium: "Your budget is reasonable, but keep some extra funds ready for unexpected costs.",
        high: "Important: Your project may cost more than planned. Consider increasing your budget by 15-20%."
      },
      time: {
        low: "Excellent! Your project should complete on time as planned.",
        medium: "Your timeline is achievable, but expect minor delays of 1-2 months.",
        high: "Note: Construction may take longer than expected. Plan for 3-6 months extra time."
      }
    };
    
    return messages[type]?.[riskLevel?.toLowerCase()] || '';
  };

  // Check if project is too risky to proceed (both cost and time are high)
  const isProjectTooRisky = () => {
    if (!riskAssessment) return false;
    
    const costRisk = (riskAssessment.cost_overrun_risk?.risk_level || riskAssessment.cost_overrun_risk?.label || '').toLowerCase();
    const timeRisk = (riskAssessment.time_delay_risk?.risk_level || riskAssessment.time_delay_risk?.label || '').toLowerCase();
    
    // Block submission if BOTH risks are high
    return costRisk === 'high' && timeRisk === 'high';
  };

  // Get blocking message
  const getBlockingMessage = () => {
    return {
      title: "⚠️ Project Cannot Be Submitted",
      message: "Based on our AI analysis, this project has extremely high risks in both budget and timeline. This suggests the project requirements may be unrealistic or need significant revision.",
      suggestions: [
        "Reduce the design complexity or special features",
        "Increase your budget to match the project scope",
        "Extend the planned construction timeline",
        "Simplify the architectural requirements",
        "Consider building in phases instead of all at once"
      ]
    };
  };

  if (!isVisible) return null;

  return (
    <div style={{
      position: 'fixed',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      backgroundColor: 'rgba(0, 0, 0, 0.5)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      zIndex: 1000,
      padding: '20px'
    }}>
      <div style={{
        backgroundColor: 'white',
        borderRadius: '12px',
        padding: '24px',
        maxWidth: '800px',
        width: '100%',
        maxHeight: '90vh',
        overflowY: 'auto',
        boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
      }}>
        {/* Header */}
        <div style={{
          textAlign: 'center',
          marginBottom: '24px',
          borderBottom: '2px solid #e5e7eb',
          paddingBottom: '16px'
        }}>
          <h2 style={{
            fontSize: '24px',
            fontWeight: 'bold',
            color: '#1f2937',
            margin: '0 0 8px 0'
          }}>
            🎯 Your Project Risk Report
          </h2>
          <p style={{
            color: '#6b7280',
            fontSize: '14px',
            margin: 0
          }}>
            Simple insights about your construction project
          </p>
        </div>

        {loading && (
          <div style={{
            textAlign: 'center',
            padding: '40px',
            color: '#6b7280'
          }}>
            <div style={{ fontSize: '48px', marginBottom: '16px' }}>🔄</div>
            <p>Analyzing your project...</p>
          </div>
        )}

        {error && (
          <div style={{
            backgroundColor: '#fef2f2',
            border: '1px solid #fecaca',
            borderRadius: '8px',
            padding: '16px',
            marginBottom: '24px'
          }}>
            <div style={{ color: '#dc2626', fontWeight: '600', marginBottom: '8px' }}>
              ❌ Unable to analyze risks
            </div>
            <p style={{ color: '#7f1d1d', margin: 0 }}>{error}</p>
          </div>
        )}

        {riskAssessment && (
          <div>
            {/* Unified Project Health Panel */}
            <div style={{ marginBottom: '24px' }}>
              <ProjectHealthPanel
                costRisk={riskAssessment.cost_overrun_risk?.risk_level || riskAssessment.cost_overrun_risk?.label}
                timeRisk={riskAssessment.time_delay_risk?.risk_level || riskAssessment.time_delay_risk?.label}
                costProbability={(() => {
                  const r = riskAssessment.cost_overrun_risk;
                  const lvl = r?.risk_level || r?.label;
                  return r?.probabilities?.[lvl];
                })()}
                timeProbability={(() => {
                  const r = riskAssessment.time_delay_risk;
                  const lvl = r?.risk_level || r?.label;
                  return r?.probabilities?.[lvl];
                })()}
                costFactors={(riskAssessment.cost_overrun_risk?.explanation || []).map(simplifyExplanation)}
                timeFactors={(riskAssessment.time_delay_risk?.explanation || []).map(simplifyExplanation)}
                budget={formData?.budget_amount ? parseFloat(formData.budget_amount) : null}
                role="homeowner"
                riskReductionSuggestions={riskAssessment.risk_reduction_suggestions}
                aiDerivedMetrics={riskAssessment.derived_metrics || null}
                shapExplanation={riskAssessment.shap_explanation || null}
              />
            </div>

            {/* Blocking Warning for Critical projects */}
            {isProjectTooRisky() && (
              <div style={{
                backgroundColor: '#fef2f2',
                border: '3px solid #ef4444',
                borderRadius: '12px',
                padding: '20px',
                marginBottom: '24px'
              }}>
                <h4 style={{ fontSize: '17px', fontWeight: '700', color: '#dc2626', marginBottom: '12px', display: 'flex', alignItems: 'center' }}>
                  <span style={{ fontSize: '22px', marginRight: '8px' }}>🚫</span>
                  {getBlockingMessage().title}
                </h4>
                <p style={{ fontSize: '14px', color: '#7f1d1d', margin: '0 0 12px 0' }}>
                  {getBlockingMessage().message}
                </p>
                <ul style={{ margin: '0', paddingLeft: '22px', fontSize: '14px', color: '#7f1d1d' }}>
                  {getBlockingMessage().suggestions.map((s, i) => <li key={i} style={{ marginBottom: '6px' }}>{s}</li>)}
                </ul>
                <div style={{ marginTop: '14px', background: '#fee2e2', borderRadius: '8px', padding: '10px', fontSize: '13px', color: '#991b1b', fontWeight: '600', textAlign: 'center' }}>
                  ⚠️ You must revise your project details before submission
                </div>
              </div>
            )}
          </div>
        )}

        {/* Action Buttons */}
        <div style={{
          display: 'flex',
          justifyContent: 'space-between',
          gap: '12px',
          marginTop: '24px'
        }}>
          <button
            onClick={onRevise}
            style={{
              flex: 1,
              padding: '14px 24px',
              backgroundColor: isProjectTooRisky() ? '#ef4444' : '#f3f4f6',
              color: isProjectTooRisky() ? 'white' : '#374151',
              border: isProjectTooRisky() ? 'none' : '1px solid #d1d5db',
              borderRadius: '8px',
              fontSize: '15px',
              fontWeight: '600',
              cursor: 'pointer',
              transition: 'all 0.2s'
            }}
            onMouseOver={(e) => {
              e.target.style.backgroundColor = isProjectTooRisky() ? '#dc2626' : '#e5e7eb';
            }}
            onMouseOut={(e) => {
              e.target.style.backgroundColor = isProjectTooRisky() ? '#ef4444' : '#f3f4f6';
            }}
          >
            {isProjectTooRisky() ? '⚠️ Revise Project Details (Required)' : '← Change Project Details'}
          </button>
          
          {!isProjectTooRisky() && (
            <button
              onClick={() => onProceed(riskAssessment)}
              disabled={loading || error}
              style={{
                flex: 1,
                padding: '14px 24px',
                backgroundColor: loading || error ? '#9ca3af' : '#3b82f6',
                color: 'white',
                border: 'none',
                borderRadius: '8px',
                fontSize: '15px',
                fontWeight: '600',
                cursor: loading || error ? 'not-allowed' : 'pointer',
                transition: 'all 0.2s'
              }}
              onMouseOver={(e) => {
                if (!loading && !error) {
                  e.target.style.backgroundColor = '#2563eb';
                }
              }}
              onMouseOut={(e) => {
                if (!loading && !error) {
                  e.target.style.backgroundColor = '#3b82f6';
                }
              }}
            >
              Continue with My Request →
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default RiskAssessmentPreview;
