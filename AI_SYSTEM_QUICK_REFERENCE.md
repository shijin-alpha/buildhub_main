# Construction AI System - Quick Reference Guide

## System Overview

**Type:** Closed-Loop AI System with Self-Learning  
**Purpose:** Predict construction cost/time overruns and evaluate accuracy  
**Status:** ✅ Fully Operational

---

## 7-Stage Workflow

```
1. Request → 2. Predict → 3. Create → 4. Lock → 5. Monitor → 6. Evaluate → 7. Analyze
```

### Stage 1: Project Request
- **File:** `backend/api/homeowner/submit_request.php`
- **Table:** `layout_requests`
- **Status:** pending

### Stage 2: AI Prediction
- **Frontend:** `frontend/src/components/RiskAssessmentPreview.jsx`
- **API:** `backend/api/ml/predict_construction_risks.php`
- **ML:** `backend/ml/predict_risks_api.py`
- **Storage:** `backend/api/ml/save_estimate_prediction.php`
- **Table:** `contractor_send_estimates` (predictions stored)
- **Blocking:** Prevents submission if BOTH risks are HIGH

### Stage 3: Project Creation
- **API:** `backend/api/contractor/create_project_from_estimate.php`
- **Trigger:** `copy_predictions_to_project` (automatic)
- **Table:** `construction_projects` (predictions copied)

### Stage 4: Prediction Locking
- **Trigger:** `lock_predictions_on_start` (automatic)
- **When:** actual_start_date set
- **Result:** predictions_locked = 1 (immutable)

### Stage 5: Project Monitoring
- **Cost:** `stage_payment_requests`, `custom_payment_requests`
- **Time:** `daily_progress_updates`, actual dates
- **Calculation:** Overrun percentages computed

### Stage 6: Auto-Evaluation
- **Trigger:** `auto_evaluate_on_completion` (automatic)
- **When:** status = 'completed'
- **Procedure:** `evaluate_project_predictions`
- **Steps:**
  1. Calculate actual cost overrun
  2. Determine ground truth labels (High/Low)
  3. Classify predictions (TP/FP/TN/FN)
  4. Update aggregated metrics
- **Table:** `ai_evaluation_metrics` (performance stored)

### Stage 7: Metrics Retrieval
- **API:** `backend/api/ml/get_evaluation_metrics.php`
- **Views:** `v_latest_ai_metrics`, `v_project_evaluation_summary`
- **Manual:** `backend/api/ml/trigger_evaluation.php` (admin only)

---

## Key Files

### Frontend
- `frontend/src/components/RiskAssessmentPreview.jsx` - Risk display & blocking

### Backend APIs
- `backend/api/ml/predict_construction_risks.php` - Prediction endpoint
- `backend/api/ml/save_estimate_prediction.php` - Store with estimate
- `backend/api/ml/save_ai_prediction.php` - Store with project (legacy)
- `backend/api/ml/get_evaluation_metrics.php` - Retrieve metrics
- `backend/api/ml/trigger_evaluation.php` - Manual evaluation
- `backend/api/contractor/create_project_from_estimate.php` - Project creation

### Python ML
- `backend/ml/predict_risks_api.py` - ML prediction script
- `backend/ml/models/cost_overrun_risk_model.pkl` - Cost model
- `backend/ml/models/time_delay_risk_model.pkl` - Time model

### Database Schema
- `backend/database/ai_self_evaluation_schema.sql` - Main schema
- `backend/database/prediction_storage_fix.sql` - Estimate predictions

---

## Database Tables

### Core Tables
1. **contractor_send_estimates** - Stores predictions with estimates
2. **construction_projects** - Stores predictions, actuals, evaluations
3. **ai_evaluation_config** - System configuration (thresholds)
4. **ai_evaluation_metrics** - Performance metrics (accuracy, precision, etc.)
5. **ai_prediction_audit** - Audit trail

### Supporting Tables
- `stage_payment_requests` - Stage payments
- `custom_payment_requests` - Custom payments
- `daily_progress_updates` - Progress tracking

---

## Database Triggers

### 1. copy_predictions_to_project
- **When:** AFTER INSERT ON construction_projects
- **Action:** Copy predictions from estimate to project
- **File:** `backend/database/prediction_storage_fix.sql`

### 2. lock_predictions_on_start
- **When:** BEFORE UPDATE ON construction_projects
- **Condition:** actual_start_date changes from NULL
- **Action:** Set predictions_locked = 1, prevent modification
- **File:** `backend/database/ai_self_evaluation_schema.sql`

### 3. auto_evaluate_on_completion
- **When:** AFTER UPDATE ON construction_projects
- **Condition:** status changes to 'completed'
- **Action:** Call evaluate_project_predictions()
- **File:** `backend/database/ai_self_evaluation_schema.sql`

---

## Stored Procedures

1. **evaluate_project_predictions(project_id)** - Master evaluation
2. **calculate_actual_cost_overrun(project_id)** - Cost calculation
3. **determine_ground_truth_labels(project_id)** - Classify actuals
4. **classify_predictions(project_id)** - Confusion matrix
5. **update_aggregated_metrics()** - System-wide metrics
6. **get_evaluation_thresholds()** - Get thresholds

---

## Confusion Matrix Logic

### Binary Classification
- **Positive Class:** High risk (overrun ≥ 5%)
- **Negative Class:** Low risk (overrun < 5%)
- **Medium → High** for binary classification

### Classifications
- **TP (True Positive):** Predicted High, Actual High ✅ Correct
- **FP (False Positive):** Predicted High, Actual Low ❌ Wrong
- **TN (True Negative):** Predicted Low, Actual Low ✅ Correct
- **FN (False Negative):** Predicted Low, Actual High ❌ Wrong

### Metrics
- **Accuracy** = (TP + TN) / Total
- **Precision** = TP / (TP + FP) - "When we predict High, how often correct?"
- **Recall** = TP / (TP + FN) - "Of all actual Highs, how many did we catch?"
- **F1 Score** = 2 × (Precision × Recall) / (Precision + Recall)
- **Specificity** = TN / (TN + FP) - "Of all actual Lows, how many did we correctly identify?"
- **FPR** = FP / (FP + TN) - "False alarm rate"

---

## API Endpoints

### Prediction
```bash
POST /buildhub/backend/api/ml/predict_construction_risks.php
Body: {
  "plot_size_sqft": 2000,
  "building_size_sqft": 1500,
  "num_floors": 2,
  "budget_amount": 5000000,
  "num_bedrooms": 3,
  "num_bathrooms": 2
}
Response: {
  "success": true,
  "data": {
    "cost_overrun_risk": {
      "risk_level": "Medium",
      "probability": 0.65,
      "explanation": [...]
    },
    "time_delay_risk": {
      "risk_level": "High",
      "probability": 0.82,
      "explanation": [...]
    }
  }
}
```

### Save Prediction (Estimate)
```bash
POST /buildhub/backend/api/ml/save_estimate_prediction.php
Body: {
  "estimate_id": 123,
  "cost_risk_level": "Medium",
  "cost_probability": 0.65,
  "time_risk_level": "High",
  "time_probability": 0.82,
  "model_version": "v1.0.0"
}
```

### Get Metrics
```bash
GET /buildhub/backend/api/ml/get_evaluation_metrics.php?type=latest
GET /buildhub/backend/api/ml/get_evaluation_metrics.php?type=history&days=30
GET /buildhub/backend/api/ml/get_evaluation_metrics.php?type=project&project_id=123
GET /buildhub/backend/api/ml/get_evaluation_metrics.php?type=config
```

### Manual Evaluation
```bash
POST /buildhub/backend/api/ml/trigger_evaluation.php
Body: {
  "project_id": 123,  // Optional, evaluates all if omitted
  "force": false      // Re-evaluate already evaluated projects
}
Auth: Admin role required
```

---

## Configuration

### Default Thresholds
```sql
SELECT * FROM ai_evaluation_config;

cost_overrun_threshold: 5.0%
time_overrun_threshold: 5.0%
high_risk_threshold: 0.70 (70%)
medium_risk_threshold: 0.40 (40%)
current_model_version: v1.0.0
auto_evaluation_enabled: 1
```

### Update Threshold
```sql
UPDATE ai_evaluation_config 
SET config_value = '10.0' 
WHERE config_key = 'cost_overrun_threshold';
```

---

## Monitoring Queries

### Check Prediction Storage
```sql
-- Estimates with predictions
SELECT COUNT(*) FROM contractor_send_estimates 
WHERE predicted_cost_risk_level IS NOT NULL;

-- Projects with predictions
SELECT COUNT(*) FROM construction_projects 
WHERE predicted_cost_risk_level IS NOT NULL;

-- Locked predictions
SELECT COUNT(*) FROM construction_projects 
WHERE predictions_locked = 1;
```

### Check Evaluations
```sql
-- Completed projects
SELECT COUNT(*) FROM construction_projects 
WHERE status = 'completed';

-- Evaluated projects
SELECT COUNT(*) FROM construction_projects 
WHERE evaluation_completed_at IS NOT NULL;

-- Pending evaluation
SELECT id, project_name FROM construction_projects 
WHERE status = 'completed' 
  AND evaluation_completed_at IS NULL
  AND predicted_cost_risk_level IS NOT NULL;
```

### Check Metrics
```sql
-- Latest metrics
SELECT * FROM v_latest_ai_metrics;

-- Confusion matrix breakdown
SELECT * FROM v_confusion_matrix_breakdown;

-- Project evaluation summary
SELECT * FROM v_project_evaluation_summary 
WHERE project_id = 123;
```

---

## Troubleshooting

### Predictions not stored
1. Check API response for errors
2. Verify estimate_id exists
3. Check database connection

### Predictions not copied to project
1. Verify trigger exists: `SHOW TRIGGERS LIKE 'copy_predictions_to_project'`
2. Check estimate has predictions
3. Verify project has estimate_id

### Evaluation not running
1. Check trigger exists: `SHOW TRIGGERS LIKE 'auto_evaluate_on_completion'`
2. Verify project status = 'completed'
3. Check config: `auto_evaluation_enabled = 1`
4. Verify project has predictions

### Metrics not updating
1. Check procedure exists: `SHOW PROCEDURE STATUS WHERE Name = 'update_aggregated_metrics'`
2. Verify evaluations completed
3. Check for SQL errors in logs

---

## Testing Commands

### Test Prediction
```bash
curl -X POST http://localhost/buildhub/backend/api/ml/predict_construction_risks.php \
  -H "Content-Type: application/json" \
  -d '{
    "plot_size_sqft": 2000,
    "building_size_sqft": 1500,
    "num_floors": 2,
    "budget_amount": 5000000,
    "num_bedrooms": 3,
    "num_bathrooms": 2
  }'
```

### Test Evaluation
```sql
-- Manually trigger evaluation for project 123
CALL evaluate_project_predictions(123);

-- Check result
SELECT * FROM v_project_evaluation_summary WHERE project_id = 123;
```

### Test Metrics
```bash
curl http://localhost/buildhub/backend/api/ml/get_evaluation_metrics.php?type=latest
```

---

## System Health Checklist

- [ ] Predictions being generated (check API logs)
- [ ] Predictions stored in estimates
- [ ] Predictions copied to projects (check trigger)
- [ ] Predictions locked when work begins
- [ ] Actual data collected during construction
- [ ] Evaluation runs on completion (check trigger)
- [ ] Metrics calculated correctly
- [ ] No SQL errors in logs
- [ ] API endpoints responding
- [ ] Python ML script working

---

## Key Success Metrics

### System Performance
- **Prediction Success Rate:** % of predictions generated successfully
- **Evaluation Success Rate:** % of completed projects evaluated
- **API Response Time:** Average time for prediction API

### Model Performance
- **Accuracy:** Overall correctness (target: >70%)
- **Precision:** When we predict High, how often correct? (target: >60%)
- **Recall:** Of all actual Highs, how many caught? (target: >70%)
- **F1 Score:** Balance of precision and recall (target: >65%)

---

## Quick Commands

```bash
# Check system status
mysql -u root -p buildhub -e "SELECT * FROM v_latest_ai_metrics"

# List pending evaluations
mysql -u root -p buildhub -e "SELECT id, project_name FROM construction_projects WHERE status='completed' AND evaluation_completed_at IS NULL"

# Trigger evaluation for all
curl -X POST http://localhost/buildhub/backend/api/ml/trigger_evaluation.php \
  -H "Content-Type: application/json" \
  -d '{}' \
  --cookie "PHPSESSID=your_session_id"

# View recent audit logs
mysql -u root -p buildhub -e "SELECT * FROM ai_prediction_audit ORDER BY created_at DESC LIMIT 10"
```

---

**Version:** 1.0  
**Last Updated:** March 11, 2026  
**Status:** Production Ready ✅
