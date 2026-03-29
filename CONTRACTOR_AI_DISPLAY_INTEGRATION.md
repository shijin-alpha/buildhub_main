# Contractor Dashboard - AI Prediction Display Integration

## Problem

The contractor dashboard doesn't show AI predictions even though they exist in the database. Contractors need to see the risk assessment that was generated for each homeowner request.

## Solution

Add AI prediction display to the contractor's inbox items so they can see:
- Cost overrun risk level (Low/Medium/High)
- Time delay risk level (Low/Medium/High)
- Risk probabilities
- Top risk factors

## Where to Add

**File**: `frontend/src/components/ContractorDashboard.jsx`

**Function**: `renderInboxItem(item)`

**Location**: After the homeowner message section, before the estimate details

## Code to Add

Add this section in the `renderInboxItem` function, right after the homeowner message display:

```jsx
{/* AI Risk Assessment - NEW SECTION */}
{(payload.ai_predictions || item.ai_predictions) && (
  <div style={{
    marginBottom: '20px',
    padding: '16px',
    background: 'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)',
    borderRadius: '10px',
    border: '2px solid #f59e0b',
    boxShadow: '0 2px 4px rgba(0,0,0,0.05)'
  }}>
    <div style={{display: 'flex', alignItems: 'center', marginBottom: '12px'}}>
      <span style={{fontSize: '24px', marginRight: '10px'}}>🤖</span>
      <h4 style={{margin: 0, fontSize: '15px', fontWeight: '700', color: '#92400e'}}>
        AI Risk Assessment
      </h4>
    </div>
    
    {(() => {
      const predictions = payload.ai_predictions || item.ai_predictions || {};
      const costRisk = predictions.cost_risk_level || predictions.predicted_cost_risk_level;
      const costProb = predictions.cost_probability || predictions.predicted_cost_probability;
      const timeRisk = predictions.time_risk_level || predictions.predicted_time_risk_level;
      const timeProb = predictions.time_probability || predictions.predicted_time_probability;
      
      const getRiskColor = (level) => {
        if (level === 'High') return '#ef4444';
        if (level === 'Medium') return '#f59e0b';
        return '#10b981';
      };
      
      const getRiskIcon = (level) => {
        if (level === 'High') return '🔴';
        if (level === 'Medium') return '🟡';
        return '🟢';
      };
      
      return (
        <div style={{paddingLeft: '34px'}}>
          <div style={{display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px', marginBottom: '12px'}}>
            {/* Cost Risk */}
            {costRisk && (
              <div style={{
                padding: '12px',
                background: 'white',
                borderRadius: '8px',
                border: `2px solid ${getRiskColor(costRisk)}`
              }}>
                <div style={{fontSize: '12px', color: '#6b7280', marginBottom: '4px'}}>
                  💰 Cost Overrun Risk
                </div>
                <div style={{
                  fontSize: '18px',
                  fontWeight: '700',
                  color: getRiskColor(costRisk),
                  marginBottom: '4px'
                }}>
                  {getRiskIcon(costRisk)} {costRisk}
                </div>
                {costProb && (
                  <div style={{fontSize: '13px', color: '#6b7280'}}>
                    Probability: {(costProb * 100).toFixed(1)}%
                  </div>
                )}
              </div>
            )}
            
            {/* Time Risk */}
            {timeRisk && (
              <div style={{
                padding: '12px',
                background: 'white',
                borderRadius: '8px',
                border: `2px solid ${getRiskColor(timeRisk)}`
              }}>
                <div style={{fontSize: '12px', color: '#6b7280', marginBottom: '4px'}}>
                  ⏰ Time Delay Risk
                </div>
                <div style={{
                  fontSize: '18px',
                  fontWeight: '700',
                  color: getRiskColor(timeRisk),
                  marginBottom: '4px'
                }}>
                  {getRiskIcon(timeRisk)} {timeRisk}
                </div>
                {timeProb && (
                  <div style={{fontSize: '13px', color: '#6b7280'}}>
                    Probability: {(timeProb * 100).toFixed(1)}%
                  </div>
                )}
              </div>
            )}
          </div>
          
          {/* Risk Factors */}
          {predictions.explanation && (
            <div style={{
              padding: '10px',
              background: 'white',
              borderRadius: '6px',
              fontSize: '13px',
              color: '#374151'
            }}>
              <strong style={{color: '#92400e'}}>Key Risk Factors:</strong>
              <ul style={{margin: '8px 0 0 0', paddingLeft: '20px'}}>
                {(predictions.explanation.cost_factors || []).slice(0, 3).map((factor, idx) => (
                  <li key={idx} style={{marginBottom: '4px'}}>{factor}</li>
                ))}
              </ul>
            </div>
          )}
          
          <div style={{
            marginTop: '10px',
            padding: '8px',
            background: 'rgba(255,255,255,0.5)',
            borderRadius: '4px',
            fontSize: '12px',
            color: '#92400e',
            fontStyle: 'italic'
          }}>
            ℹ️ This AI assessment was generated based on project specifications and historical data.
            Use it as guidance when preparing your estimate.
          </div>
        </div>
      );
    })()}
  </div>
)}
```

## Backend API Update

The contractor inbox API needs to include AI predictions. Update the API to fetch predictions from `layout_requests` table.

**File**: `backend/api/contractor/get_inbox.php`

Add this to the query:

```php
// In the SELECT statement, add:
lr.predicted_cost_risk_level,
lr.predicted_cost_probability,
lr.predicted_time_risk_level,
lr.predicted_time_probability,
lr.prediction_explanation,
lr.model_version

// In the JOIN section, ensure layout_requests is joined:
LEFT JOIN layout_requests lr ON cls.layout_id = lr.id
```

Then in the response building, add:

```php
$item['ai_predictions'] = null;
if ($row['predicted_cost_risk_level'] || $row['predicted_time_risk_level']) {
    $item['ai_predictions'] = [
        'cost_risk_level' => $row['predicted_cost_risk_level'],
        'cost_probability' => floatval($row['predicted_cost_probability']),
        'time_risk_level' => $row['predicted_time_risk_level'],
        'time_probability' => floatval($row['predicted_time_probability']),
        'model_version' => $row['model_version'],
        'explanation' => json_decode($row['prediction_explanation'], true)
    ];
}
```

## Testing

1. Apply the ML fixes first: `php APPLY_ML_FIXES_NOW.php`
2. Submit a homeowner request with AI predictions
3. Send the request to a contractor
4. Login as contractor
5. Check inbox - you should see the AI Risk Assessment section

## Visual Example

The display will look like this:

```
┌─────────────────────────────────────────────────────────┐
│ 🤖 AI Risk Assessment                                   │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────────────┐  ┌──────────────────┐           │
│  │ 💰 Cost Overrun  │  │ ⏰ Time Delay    │           │
│  │ 🔴 High          │  │ 🟢 Low           │           │
│  │ Probability: 95.5%│  │ Probability: 15.2%│          │
│  └──────────────────┘  └──────────────────┘           │
│                                                          │
│  Key Risk Factors:                                      │
│  • Complex design with multiple floors                  │
│  • High budget per square foot                          │
│  • Special features increase complexity                 │
│                                                          │
│  ℹ️ This AI assessment was generated based on project  │
│     specifications and historical data.                 │
└─────────────────────────────────────────────────────────┘
```

## Benefits

1. **Informed Estimates**: Contractors see risk assessment before creating estimates
2. **Better Planning**: Can adjust pricing and timeline based on AI insights
3. **Transparency**: Both homeowner and contractor see the same risk assessment
4. **Data-Driven**: Decisions based on ML analysis of historical projects

## Next Steps

1. Update `ContractorDashboard.jsx` with the new code
2. Update `backend/api/contractor/get_inbox.php` to include predictions
3. Test the display
4. Consider adding similar display in "My Estimates" section
5. Add prediction display in construction project details

