# AI Self-Evaluation Framework - Quick Start Guide

## 🚀 Installation (3 Steps)

### Step 1: Run Installation Script
```bash
install_ai_self_evaluation.bat
```

### Step 2: Test Installation
Open `test_ai_self_evaluation.html` in your browser and test each section.

### Step 3: Integrate with Frontend
Add prediction saving to your project submission flow.

---

## 📋 Integration Checklist

### Backend Integration

✅ **Database Schema** - Installed via script  
✅ **API Endpoints** - Ready to use:
- `POST /backend/api/ml/save_ai_predictions.php`
- `GET /backend/api/ml/get_ai_evaluation_metrics.php`
- `POST /backend/api/ml/trigger_evaluation.php`

### Frontend Integration

Add this code after AI risk assessment:

```javascript
// After AI generates predictions
async function saveAIPredictions(projectId, predictions) {
  const response = await fetch('/backend/api/ml/save_ai_predictions.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      project_id: projectId,
      cost_risk_level: predictions.cost_overrun_risk.risk_level,
      cost_probability: predictions.cost_overrun_risk.probabilities.High,
      time_risk_level: predictions.time_delay_risk.risk_level,
      time_probability: predictions.time_delay_risk.probabilities.High
    })
  });
  
  return response.json();
}
```

---

## 🎯 How It Works

### 1. Save Predictions (Project Confirmation)
```
Homeowner submits → AI predicts → Save to database
```

### 2. Lock Predictions (Project Start)
```
Work begins → Predictions LOCKED (automatic)
```

### 3. Evaluate (Project Completion)
```
Status = 'completed' → Automatic evaluation (automatic)
```

### 4. View Metrics (Anytime)
```
Admin/Contractor → View dashboard → See performance
```

---

## 📊 Key Metrics Explained

### Confusion Matrix
```
              Actual
           Low    High
Pred Low   TN     FN
     High  FP     TP
```

- **TP (True Positive)**: Correctly predicted High risk
- **TN (True Negative)**: Correctly predicted Low risk
- **FP (False Positive)**: Predicted High but was Low
- **FN (False Negative)**: Predicted Low but was High

### Performance Metrics

**Accuracy** = (TP + TN) / Total  
→ Overall correctness

**Precision** = TP / (TP + FP)  
→ Of High predictions, how many were correct

**Recall** = TP / (TP + FN)  
→ Of actual High risks, how many were caught

**F1 Score** = 2 × (Precision × Recall) / (Precision + Recall)  
→ Balanced measure (>90% = Excellent)

---

## ⚙️ Configuration

### View Current Settings
```sql
SELECT * FROM ai_evaluation_config;
```

### Change Thresholds
```sql
-- Cost overrun threshold (default: 5%)
UPDATE ai_evaluation_config
SET config_value = '10.0'
WHERE config_key = 'cost_overrun_threshold';

-- Time overrun threshold (default: 5%)
UPDATE ai_evaluation_config
SET config_value = '8.0'
WHERE config_key = 'time_overrun_threshold';
```

### Enable/Disable Evaluation
```sql
-- Disable
UPDATE ai_evaluation_config
SET config_value = '0'
WHERE config_key = 'evaluation_enabled';

-- Enable
UPDATE ai_evaluation_config
SET config_value = '1'
WHERE config_key = 'evaluation_enabled';
```

---

## 🔍 Verification Queries

### Check Predictions Saved
```sql
SELECT 
  id,
  project_name,
  predicted_cost_risk_level,
  predicted_time_risk_level,
  prediction_generated_at,
  predictions_locked
FROM construction_projects
WHERE predicted_cost_risk_level IS NOT NULL
ORDER BY prediction_generated_at DESC
LIMIT 10;
```

### Check Evaluations Completed
```sql
SELECT 
  id,
  project_name,
  cost_prediction_classification,
  time_prediction_classification,
  cost_prediction_correct,
  time_prediction_correct,
  evaluation_completed_at
FROM construction_projects
WHERE evaluation_completed_at IS NOT NULL
ORDER BY evaluation_completed_at DESC
LIMIT 10;
```

### View Current Metrics
```sql
SELECT * FROM v_latest_ai_metrics;
```

### View Confusion Matrix
```sql
SELECT * FROM v_confusion_matrix_breakdown;
```

---

## 🎨 Example Workflow

### Complete Example: From Prediction to Evaluation

```javascript
// 1. Homeowner submits project
const projectData = {
  plot_size_sqft: 2500,
  building_size_sqft: 2000,
  num_floors: 2,
  budget_amount: 2500000,
  // ... other fields
};

// 2. AI generates predictions
const aiResponse = await fetch('/backend/api/ml/predict_construction_risks.php', {
  method: 'POST',
  body: JSON.stringify(projectData)
});
const predictions = await aiResponse.json();

// 3. Save predictions to database (NEW!)
const saveResponse = await fetch('/backend/api/ml/save_ai_predictions.php', {
  method: 'POST',
  body: JSON.stringify({
    project_id: 123,
    cost_risk_level: predictions.cost_overrun_risk.risk_level,
    cost_probability: predictions.cost_overrun_risk.probabilities.High,
    time_risk_level: predictions.time_delay_risk.risk_level,
    time_probability: predictions.time_delay_risk.probabilities.High
  })
});

// 4. Project proceeds...
// 5. When project completes, evaluation happens automatically!
// 6. View metrics anytime:

const metricsResponse = await fetch('/backend/api/ml/get_ai_evaluation_metrics.php?metric_type=both');
const metrics = await metricsResponse.json();
console.log('AI Performance:', metrics.data.metrics);
```

---

## 🔒 Important Rules

### Immutability
- ✅ Predictions can be saved/updated BEFORE project starts
- 🔒 Predictions LOCKED when `actual_start_date` is set
- ❌ Cannot modify predictions after locking

### Automatic Evaluation
- ✅ Triggers when status changes to 'completed'
- ✅ Calculates actual overruns
- ✅ Determines ground truth
- ✅ Classifies predictions (TP/FP/TN/FN)
- ✅ Updates aggregated metrics

### Backward Compatibility
- ✅ All new fields are nullable
- ✅ Legacy projects work without changes
- ✅ No impact on existing workflows
- ✅ Evaluation only runs if predictions exist

---

## 📞 Troubleshooting

### Issue: Predictions not saving
**Check:**
1. Project exists and user has permission
2. Predictions not already locked
3. Valid risk levels (Low/Medium/High)

### Issue: Evaluation not running
**Check:**
1. Project status is 'completed'
2. Predictions exist for the project
3. `evaluation_enabled` = 1 in config
4. Check trigger is active

### Issue: Metrics showing NULL
**Check:**
1. At least one project has been evaluated
2. Predictions and ground truth both exist
3. Run `CALL update_aggregated_metrics()` manually

---

## 📚 Documentation Files

- **AI_SELF_EVALUATION_FRAMEWORK.md** - Complete documentation
- **AI_SELF_EVALUATION_QUICK_START.md** - This file
- **test_ai_self_evaluation.html** - Test interface
- **backend/database/ai_self_evaluation_schema.sql** - Database schema

---

## ✅ Success Criteria

Your installation is successful if:

1. ✅ New columns exist in `construction_projects` table
2. ✅ Three new tables created (config, metrics, audit)
3. ✅ Stored procedures installed
4. ✅ Triggers active
5. ✅ Views accessible
6. ✅ Test interface works
7. ✅ Can save predictions
8. ✅ Can view metrics

---

**Version:** 1.0  
**Date:** February 16, 2026  
**Status:** Production Ready ✅
