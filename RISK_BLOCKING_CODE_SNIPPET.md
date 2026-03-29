# Risk Blocking Validation - Code Snippets

## 🔍 Key Code Changes

### 1. Blocking Detection Function

```javascript
// Check if project is too risky to proceed (both cost and time are high)
const isProjectTooRisky = () => {
  if (!riskAssessment) return false;
  
  const costRisk = riskAssessment.cost_overrun_risk?.risk_level?.toLowerCase();
  const timeRisk = riskAssessment.time_delay_risk?.risk_level?.toLowerCase();
  
  // Block submission if BOTH risks are high
  return costRisk === 'high' && timeRisk === 'high';
};
```

**Logic:**
- Returns `true` only when BOTH risks are HIGH
- Returns `false` for all other combinations
- Safe null checking with optional chaining

---

### 2. Blocking Message Generator

```javascript
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
```

**Purpose:**
- Provides clear explanation of why project is blocked
- Offers 5 specific, actionable suggestions
- User-friendly language

---

### 3. Conditional Blocking Warning UI

```javascript
{/* Blocking Warning for High Risk Projects */}
{isProjectTooRisky() && (
  <div style={{
    backgroundColor: '#fef2f2',
    border: '3px solid #ef4444',
    borderRadius: '12px',
    padding: '24px',
    marginBottom: '24px'
  }}>
    <h4 style={{
      fontSize: '18px',
      fontWeight: '700',
      color: '#dc2626',
      marginBottom: '16px',
      display: 'flex',
      alignItems: 'center'
    }}>
      <span style={{ fontSize: '24px', marginRight: '8px' }}>🚫</span>
      {getBlockingMessage().title}
    </h4>
    
    <div style={{ fontSize: '14px', color: '#7f1d1d', lineHeight: '1.6', marginBottom: '16px' }}>
      <p style={{ margin: '0 0 16px 0', fontWeight: '600' }}>
        {getBlockingMessage().message}
      </p>
      
      <p style={{ margin: '0 0 12px 0', fontWeight: '600' }}>
        Please revise your project by:
      </p>
      <ul style={{ margin: '0', paddingLeft: '24px' }}>
        {getBlockingMessage().suggestions.map((suggestion, idx) => (
          <li key={idx} style={{ marginBottom: '8px' }}>
            {suggestion}
          </li>
        ))}
      </ul>
    </div>

    <div style={{
      backgroundColor: '#fee2e2',
      borderRadius: '8px',
      padding: '12px',
      fontSize: '13px',
      color: '#991b1b',
      fontWeight: '600',
      textAlign: 'center'
    }}>
      ⚠️ You must revise your project details before submission
    </div>
  </div>
)}
```

**Features:**
- Only renders when `isProjectTooRisky()` returns true
- Red theme for urgency
- Clear title and message
- Bulleted list of suggestions
- Bottom warning banner

---

### 4. Conditional Success Tips UI

```javascript
{/* What This Means Section - Only show if not blocking */}
{!isProjectTooRisky() && (
  <div style={{
    backgroundColor: '#f0f9ff',
    border: '2px solid #bae6fd',
    borderRadius: '12px',
    padding: '20px',
    marginBottom: '24px'
  }}>
    <h4 style={{
      fontSize: '16px',
      fontWeight: '600',
      color: '#1f2937',
      marginBottom: '12px',
      display: 'flex',
      alignItems: 'center'
    }}>
      <span style={{ fontSize: '20px', marginRight: '8px' }}>💡</span>
      What should you do?
    </h4>
    
    <div style={{ fontSize: '14px', color: '#374151', lineHeight: '1.6' }}>
      {/* Risk-specific recommendations */}
      {riskAssessment.cost_overrun_risk?.risk_level?.toLowerCase() === 'high' && (
        <p style={{ margin: '0 0 12px 0' }}>
          <strong>Budget:</strong> Add 15-20% extra to your budget as a safety cushion.
        </p>
      )}
      {riskAssessment.time_delay_risk?.risk_level?.toLowerCase() === 'high' && (
        <p style={{ margin: '0 0 12px 0' }}>
          <strong>Timeline:</strong> Plan for 3-6 months extra time.
        </p>
      )}
      <p style={{ margin: 0 }}>
        <strong>Remember:</strong> These are predictions based on similar projects.
      </p>
    </div>
  </div>
)}
```

**Features:**
- Only renders when project is NOT blocked
- Blue theme for informational content
- Conditional recommendations based on risk levels
- Helpful tips for managing risks

---

### 5. Conditional Button Rendering

```javascript
{/* Action Buttons */}
<div style={{
  display: 'flex',
  justifyContent: 'space-between',
  gap: '12px',
  marginTop: '24px'
}}>
  {/* Revise Button - Always visible, changes style when blocked */}
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
  
  {/* Continue Button - Only visible when NOT blocked */}
  {!isProjectTooRisky() && (
    <button
      onClick={onProceed}
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
```

**Features:**
- Revise button always visible
- Revise button changes to red when blocked
- Continue button hidden when blocked (not just disabled)
- Dynamic button text based on blocking state
- Smooth hover effects

---

## 🎯 Complete Logic Flow

```javascript
// Step 1: Get risk assessment from API
const riskAssessment = {
  cost_overrun_risk: { risk_level: 'High', probability: 0.999 },
  time_delay_risk: { risk_level: 'High', probability: 0.950 }
};

// Step 2: Check if project is too risky
const isBlocked = isProjectTooRisky(); // Returns true

// Step 3: Conditional rendering
if (isBlocked) {
  // Show blocking warning
  // Hide continue button
  // Make revise button red
} else {
  // Show success tips
  // Show both buttons
  // Normal button colors
}
```

---

## 🧪 Testing the Logic

```javascript
// Test Case 1: Both HIGH → BLOCKED
const test1 = {
  cost_overrun_risk: { risk_level: 'High' },
  time_delay_risk: { risk_level: 'High' }
};
console.log(isProjectTooRisky()); // true ✅

// Test Case 2: High + Low → ALLOWED
const test2 = {
  cost_overrun_risk: { risk_level: 'High' },
  time_delay_risk: { risk_level: 'Low' }
};
console.log(isProjectTooRisky()); // false ✅

// Test Case 3: Low + High → ALLOWED
const test3 = {
  cost_overrun_risk: { risk_level: 'Low' },
  time_delay_risk: { risk_level: 'High' }
};
console.log(isProjectTooRisky()); // false ✅

// Test Case 4: Both LOW → ALLOWED
const test4 = {
  cost_overrun_risk: { risk_level: 'Low' },
  time_delay_risk: { risk_level: 'Low' }
};
console.log(isProjectTooRisky()); // false ✅
```

---

## 📊 Decision Table

```javascript
const decisionTable = {
  'high-high': { blocked: true, message: 'Project cannot be submitted' },
  'high-medium': { blocked: false, message: 'Proceed with caution' },
  'high-low': { blocked: false, message: 'Proceed with caution' },
  'medium-high': { blocked: false, message: 'Proceed with caution' },
  'medium-medium': { blocked: false, message: 'Proceed normally' },
  'medium-low': { blocked: false, message: 'Proceed normally' },
  'low-high': { blocked: false, message: 'Proceed with caution' },
  'low-medium': { blocked: false, message: 'Proceed normally' },
  'low-low': { blocked: false, message: 'Proceed normally' }
};

// Usage
const key = `${costRisk}-${timeRisk}`;
const decision = decisionTable[key];
```

---

## 🎨 Color Scheme

```javascript
const colors = {
  // Risk levels
  low: '#10b981',      // Green
  medium: '#f59e0b',   // Yellow
  high: '#ef4444',     // Red
  
  // UI states
  blocked: {
    background: '#fef2f2',
    border: '#ef4444',
    text: '#dc2626',
    button: '#ef4444'
  },
  allowed: {
    background: '#f0f9ff',
    border: '#bae6fd',
    text: '#1f2937',
    button: '#3b82f6'
  }
};
```

---

## 🚀 Integration Points

### In HomeownerRequestWizard.jsx

```javascript
// Risk assessment modal
<RiskAssessmentPreview
  formData={riskAssessmentData}
  isVisible={showRiskAssessment}
  onProceed={handleRiskAssessmentProceed}  // Only called if not blocked
  onRevise={handleRiskAssessmentRevise}    // Always available
/>

// Handlers
const handleRiskAssessmentProceed = () => {
  setShowRiskAssessment(false);
  submit(); // Proceed with submission
};

const handleRiskAssessmentRevise = () => {
  setShowRiskAssessment(false);
  setStep(4); // Go back to review step
};
```

---

## 📝 Summary

**3 Key Functions:**
1. `isProjectTooRisky()` - Detects blocking condition
2. `getBlockingMessage()` - Provides user guidance
3. Conditional rendering - Shows appropriate UI

**Result:**
- Clean, maintainable code
- Clear user experience
- Effective risk prevention

---

*Code Reference: RiskAssessmentPreview.jsx*
*Implementation Date: February 16, 2026*
