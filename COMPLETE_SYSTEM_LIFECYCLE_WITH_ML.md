# Complete System Lifecycle with ML Integration

## Overview

This document shows the complete workflow from when a homeowner submits a project request through AI prediction, project execution, completion, and final evaluation.

---

## Phase 1: PROJECT REQUEST & AI PREDICTION

### Step 1.1: Homeowner Submits Custom Request

**Location:** Homeowner Dashboard → Custom Request Form

**User Actions:**
1. Homeowner logs in
2. Clicks "Custom Request" button
3. Fills multi-step form:
   - Site Details (plot size, topography, shape)
   - Building Details (size, floors, rooms)
   - Budget & Timeline
   - Design Preferences
   - Special Requirements

**Data Collected:**
```javascript
{
  plot_size_sqft: 2500,
  building_size_sqft: 2000,
  num_floors: 2,
  budget_amount: 2500000,
  num_bedrooms: 3,
  num_bathrooms: 2,
  plot_shape: "rectangular",
  topography: "flat",
  design_style: "modern",
  basement: false,
  terrace: true,
  parking: true
}
```

**Database:** Data saved to `layout_requests` table with status='pending'

---

### Step 1.2: AI Risk Assessment (AUTOMATIC)

**Trigger:** When user clicks "Review & Submit"

**Component:** `RiskAssessmentPreview.jsx` modal opens

**ML Prediction Process:**

1. **Frontend calls PHP API:**
   ```javascript
   fetch('/buildhub/backend/api/ml/predict_construction_risks.php', {
     method: 'POST',
     body: JSON.stringify(formData)
   })
   ```

2. **PHP forwards to FastAPI:**
   ```php
   curl_post('http://localhost:8000/predict', $formData)
   ```

3. **FastAPI processes request:**
   - Validates input (Pydantic)
   - Calls `predictor.predict_risks(formData)`

4. **ML Engine generates predictions:**
   - Converts form data to 14 cost features + 9 time features
   - Loads Gradient Boosting model (cost)
   - Loads Random Forest model (time)
   - Predicts risk levels and probabilities
   - Generates explanations

5. **Response returned:**
   ```json
   {
     "cost_overrun_risk": {
       "risk_level": "High",
       "probability": 0.955,
       "explanation": ["Design complexity...", "Budget per sqft..."]
     },
     "time_delay_risk": {
       "risk_level": "Low", 
       "probability": 0.152,
       "explanation": ["Number of floors...", "Site difficulty..."]
     }
   }
   ```

6. **Frontend displays risk assessment:**
   - 💰 Budget Risk: 🔴 HIGH (95.5%)
   - ⏰ Timeline Risk: 🟢 LOW (15.2%)
   - User-friendly explanations
   - Recommendations

**Decision Point:**
- If BOTH risks HIGH → ❌ Submission BLOCKED (user must revise)
- Otherwise → ✅ User can proceed or revise

---

### Step 1.3: Prediction Storage

**When:** User proceeds with submission

**API Call:** `save_estimate_prediction.php`

**Database Update:**
```sql
-- Predictions saved to layout_requests or contractor_send_estimates
UPDATE contractor_send_estimates
SET predicted_cost_risk_level = 'High',
    predicted_cost_probability = 0.955,
    predicted_time_risk_level = 'Low',
    predicted_time_probability = 0.152,
    prediction_generated_at = NOW(),
    model_version = 'v1.0.0'
WHERE id = estimate_id;
```

**Result:** Predictions permanently stored for future evaluation

---

## Phase 2: CONTRACTOR SELECTION & ESTIMATION

### Step 2.1: Request Sent to Contractors

**System Action:** Request visible to contractors in their dashboard

**Contractors Can:**
- View project requirements
- View AI risk assessment (if shared)
- Submit detailed estimates

---

### Step 2.2: Contractor Submits Estimate

**Contractor Actions:**
1. Reviews project requirements
2. Creates detailed estimate with:
   - Materials breakdown
   - Labor costs
   - Timeline estimate
   - Total cost

**Database:** Estimate saved to `contractor_send_estimates` table

**Status:** estimate.status = 'submitted'

---

### Step 2.3: Homeowner Reviews Estimates

**Homeowner Dashboard:** Views all submitted estimates

**Homeowner Actions:**
- Compare estimates
- Review contractor profiles
- Check AI risk assessment
- Accept one estimate

**Database Update:**
```sql
UPDATE contractor_send_estimates
SET status = 'accepted',
    homeowner_action_at = NOW()
WHERE id = selected_estimate_id;
```

---

## Phase 3: PROJECT CREATION

### Step 3.1: Project Created from Accepted Estimate

**API:** `create_project_from_estimate.php`

**Database INSERT:**
```sql
INSERT INTO construction_projects (
  homeowner_id,
  contractor_id,
  estimate_id,
  project_name,
  estimated_cost,
  planned_duration_months,
  status,
  created_at
) VALUES (...);
```

**Status:** project.status = 'created'

---

### Step 3.2: AI Predictions Copied (AUTOMATIC)

**Trigger:** Database trigger `copy_predictions_to_project`

**Fires:** AFTER INSERT on construction_projects

**Logic:**
```sql
-- Automatically copies predictions from estimate to project
IF NEW.estimate_id IS NOT NULL THEN
  SELECT predictions FROM contractor_send_estimates
  WHERE id = NEW.estimate_id;
  
  UPDATE construction_projects
  SET predicted_cost_risk_level = ...,
      predicted_cost_probability = ...,
      predicted_time_risk_level = ...,
      predicted_time_probability = ...
  WHERE id = NEW.id;
END IF;
```

**Result:** Predictions now linked to project for future evaluation

---

## Phase 4: PROJECT EXECUTION & MONITORING

### Step 4.1: Construction Begins

**Contractor Action:** Sets actual_start_date

**Database UPDATE:**
```sql
UPDATE construction_projects
SET actual_start_date = CURDATE(),
    status = 'in_progress'
WHERE id = project_id;
```

---

### Step 4.2: Predictions Locked (AUTOMATIC)

**Trigger:** `lock_predictions_on_start`

**Fires:** BEFORE UPDATE on construction_projects

**Logic:**
```sql
-- When actual_start_date changes from NULL to date
IF OLD.actual_start_date IS NULL AND NEW.actual_start_date IS NOT NULL THEN
  SET NEW.predictions_locked = 1;
END IF;
```

**Result:** Predictions now IMMUTABLE (cannot be changed)

---

### Step 4.3: Real-Time Monitoring

**A. Budget Tracking:**
- Stage payments recorded in `stage_payment_requests`
- Custom payments in `custom_payment_requests`
- Total actual cost calculated automatically

**B. Timeline Tracking:**
- Daily progress updates in `daily_progress_updates`
- Photos uploaded and stored
- Completion percentage tracked

**C. Overrun Calculation (AUTOMATIC):**
```sql
-- Cost Overrun %
actual_cost_overrun_percentage = 
  ((actual_cost - estimated_cost) / estimated_cost) * 100

-- Time Overrun %
actual_time_overrun_percentage = 
  ((actual_days - planned_days) / planned_days) * 100
```

---

## Phase 5: PROJECT COMPLETION

### Step 5.1: Contractor Marks Complete

**Contractor Action:** Changes status to 'completed'

**Database UPDATE:**
```sql
UPDATE construction_projects
SET status = 'completed',
    actual_end_date = CURDATE(),
    completion_percentage = 100
WHERE id = project_id;
```

---

### Step 5.2: Auto-Evaluation Triggered (AUTOMATIC)

**Trigger:** `auto_evaluate_on_completion`

**Fires:** AFTER UPDATE on construction_projects

**Condition:** IF status changes to 'completed'

**Action:** Calls stored procedure `evaluate_project_predictions(project_id)`

---

### Step 5.3: AI Prediction Evaluation

**Stored Procedure:** `evaluate_project_predictions`

**Process:**

**A. Calculate Actual Overruns:**
```sql
-- Sum all payments
actual_cost = SUM(stage_payments) + SUM(custom_payments)

-- Calculate overrun percentages
cost_overrun_pct = ((actual_cost - estimated_cost) / estimated_cost) * 100
time_overrun_pct = ((actual_days - planned_days) / planned_days) * 100
```

**B. Classify Ground Truth:**
```sql
-- Based on 5% threshold
cost_ground_truth = CASE
  WHEN cost_overrun_pct >= 5 THEN 'Overrun'
  ELSE 'No_Overrun'
END

time_ground_truth = CASE
  WHEN time_overrun_pct >= 5 THEN 'Overrun'
  ELSE 'No_Overrun'
END
```

**C. Convert Predictions to Binary:**
```sql
-- High risk = predicted overrun
cost_predicted = CASE
  WHEN predicted_cost_risk_level = 'High' THEN 'Overrun'
  ELSE 'No_Overrun'
END

time_predicted = CASE
  WHEN predicted_time_risk_level = 'High' THEN 'Overrun'
  ELSE 'No_Overrun'
END
```

**D. Confusion Matrix Classification:**
```sql
cost_classification = CASE
  WHEN predicted='Overrun' AND actual='Overrun' THEN 'TP'  -- True Positive
  WHEN predicted='Overrun' AND actual='No_Overrun' THEN 'FP'  -- False Positive
  WHEN predicted='No_Overrun' AND actual='No_Overrun' THEN 'TN'  -- True Negative
  WHEN predicted='No_Overrun' AND actual='Overrun' THEN 'FN'  -- False Negative
END
```

**E. Mark Correctness:**
```sql
cost_prediction_correct = CASE
  WHEN classification IN ('TP', 'TN') THEN 1
  ELSE 0
END
```

**F. Update Project Record:**
```sql
UPDATE construction_projects
SET actual_cost_overrun_percentage = cost_overrun_pct,
    actual_time_overrun_percentage = time_overrun_pct,
    cost_ground_truth_label = cost_ground_truth,
    time_ground_truth_label = time_ground_truth,
    cost_prediction_classification = cost_classification,
    time_prediction_classification = time_classification,
    cost_prediction_correct = cost_correct,
    time_prediction_correct = time_correct,
    evaluation_completed_at = NOW()
WHERE id = project_id;
```

---

## Phase 6: ANALYTICS & MODEL IMPROVEMENT

### Step 6.1: Aggregate Metrics Calculation

**Trigger:** After multiple projects complete

**Stored Procedure:** `calculate_aggregate_metrics`

**Process:**

**A. Count Confusion Matrix:**
```sql
SELECT 
  COUNT(CASE WHEN classification = 'TP' THEN 1 END) as true_positives,
  COUNT(CASE WHEN classification = 'FP' THEN 1 END) as false_positives,
  COUNT(CASE WHEN classification = 'TN' THEN 1 END) as true_negatives,
  COUNT(CASE WHEN classification = 'FN' THEN 1 END) as false_negatives
FROM construction_projects
WHERE evaluation_completed_at IS NOT NULL;
```

**B. Calculate Performance Metrics:**
```sql
accuracy = (TP + TN) / (TP + TN + FP + FN)
precision = TP / (TP + FP)
recall = TP / (TP + FN)
f1_score = 2 * (precision * recall) / (precision + recall)
```

**C. Store in Metrics Table:**
```sql
INSERT INTO ai_evaluation_metrics (
  evaluation_date,
  metric_type,
  true_positives,
  false_positives,
  true_negatives,
  false_negatives,
  accuracy,
  precision_score,
  recall_score,
  f1_score,
  total_projects,
  model_version
) VALUES (...);
```

---

### Step 6.2: Model Retraining (When Needed)

**Trigger:** Manual or scheduled (after 50+ completed projects)

**Process:**

1. **Export Real Data:**
   ```sql
   SELECT 
     plot_size_sqft,
     building_size_sqft,
     num_floors,
     budget_amount,
     actual_cost_overrun_percentage > 5 as cost_overrun_occurred,
     actual_time_overrun_percentage > 5 as time_overrun_occurred
   FROM construction_projects
   WHERE evaluation_completed_at IS NOT NULL;
   ```

2. **Retrain Models:**
   ```bash
   cd backend/ml
   python run_training.py
   ```

3. **New Models Generated:**
   - `cost_overrun_risk_model_v2.pkl`
   - `time_delay_risk_model_v2.pkl`
   - Updated `model_metadata.json`

4. **Deploy New Models:**
   - Update `current_model.json`
   - Restart FastAPI service
   - New predictions use improved models

---

## Complete Workflow Summary

```
┌─────────────────────────────────────────────────────────────────┐
│ PHASE 1: REQUEST & PREDICTION                                    │
│ Homeowner Form → AI Prediction → Risk Assessment → Storage       │
│ Time: ~2 minutes                                                 │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│ PHASE 2: CONTRACTOR SELECTION                                    │
│ Contractors View → Submit Estimates → Homeowner Accepts          │
│ Time: 1-7 days                                                   │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│ PHASE 3: PROJECT CREATION                                        │
│ Create Project → Copy Predictions (trigger) → Ready to Start     │
│ Time: Instant                                                    │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│ PHASE 4: EXECUTION & MONITORING                                  │
│ Start Work → Lock Predictions → Track Budget & Timeline          │
│ Time: 3-12 months (construction duration)                        │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│ PHASE 5: COMPLETION & EVALUATION                                 │
│ Mark Complete → Auto-Evaluate (trigger) → Classify Accuracy      │
│ Time: Instant (automatic)                                        │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│ PHASE 6: ANALYTICS & IMPROVEMENT                                 │
│ Aggregate Metrics → Model Retraining → Deploy New Version        │
│ Time: Periodic (after 50+ projects)                             │
└─────────────────────────────────────────────────────────────────┘
```

---

## Key Database Tables & Their Roles

| Table | Phase | Purpose |
|-------|-------|---------|
| layout_requests | 1 | Initial project request |
| contractor_send_estimates | 1-2 | Estimates with AI predictions |
| construction_projects | 3-5 | Active projects with predictions |
| stage_payment_requests | 4 | Budget tracking |
| daily_progress_updates | 4 | Timeline tracking |
| ai_evaluation_metrics | 6 | Aggregated performance metrics |
| ai_prediction_audit | All | Audit trail of predictions |

---

## Automatic Triggers in the System

1. **copy_predictions_to_project** - Copies predictions when project created
2. **lock_predictions_on_start** - Locks predictions when work begins
3. **auto_evaluate_on_completion** - Evaluates predictions when project completes
4. **calculate_aggregate_metrics** - Updates system-wide metrics

---

## Example: Complete Lifecycle

**Day 1:** Homeowner submits request
- AI predicts: Cost Risk HIGH (95.5%), Time Risk LOW (15.2%)
- Predictions stored in database

**Day 3:** Contractor submits estimate
- Estimate: ₹28,00,000 (12% higher than homeowner budget)
- Timeline: 8 months

**Day 5:** Homeowner accepts estimate
- Project created automatically
- Predictions copied to project table

**Day 7:** Construction begins
- actual_start_date set
- Predictions locked (immutable)

**Month 1-8:** Construction in progress
- 8 stage payments recorded
- 240 daily progress updates
- Photos uploaded

**Month 9:** Project completes
- actual_end_date set
- Status changed to 'completed'
- **AUTO-EVALUATION TRIGGERED**

**Evaluation Results:**
- Actual cost: ₹29,50,000 (5.4% overrun) → Overrun occurred
- Actual time: 8.5 months (6.25% overrun) → Overrun occurred
- Cost prediction: HIGH → Predicted overrun → ✅ TRUE POSITIVE
- Time prediction: LOW → Predicted no overrun → ❌ FALSE NEGATIVE

**System Learning:**
- Cost model was correct (TP)
- Time model needs improvement (FN)
- Data added to retraining dataset

---

## Benefits of This Workflow

1. **Proactive Risk Management** - Homeowners know risks before committing
2. **Transparent Predictions** - All predictions stored and auditable
3. **Automatic Evaluation** - No manual work needed
4. **Continuous Improvement** - Models get better with real data
5. **Accountability** - Predictions locked, cannot be changed retroactively
6. **Data-Driven Decisions** - System learns from actual outcomes

---

## Monitoring & Dashboards

**Admin Dashboard Shows:**
- Total predictions made
- Prediction accuracy over time
- Confusion matrix visualization
- Model performance trends
- Projects by risk level

**Homeowner Dashboard Shows:**
- Their project's AI prediction
- Actual vs predicted progress
- Budget tracking
- Timeline tracking

**Contractor Dashboard Shows:**
- Project risk assessments
- Payment status
- Progress tracking

---

This complete lifecycle ensures that AI predictions are not just generated but also evaluated against real outcomes, enabling continuous improvement of the ML models!
