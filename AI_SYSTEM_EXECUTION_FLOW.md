# Construction AI System - Complete Execution Flow

## Visual Workflow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          STAGE 1: PROJECT REQUEST                            │
│                                                                               │
│  Homeowner → submit_request.php → layout_requests (INSERT)                  │
│                                                                               │
│  Status: pending                                                             │
│  Predictions: NONE YET                                                       │
└───────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                     STAGE 2: AI RISK PREDICTION                              │
│                                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Frontend: RiskAssessmentPreview.jsx                                  │   │
│  │ - Displays when homeowner reviews estimate                           │   │
│  │ - Shows risk levels with user-friendly explanations                  │   │
│  │ - BLOCKS if both cost AND time are HIGH                              │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                    │                                          │
│                                    ↓                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ API: predict_construction_risks.php                                  │   │
│  │ - Receives: plot_size, budget, floors, bedrooms, bathrooms          │   │
│  │ - Creates temp JSON file                                             │   │
│  │ - Executes: python predict_risks_api.py temp_file.json              │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                    │                                          │
│                                    ↓                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ Python ML: predict_risks_api.py                                      │   │
│  │ - Loads: cost_overrun_risk_model.pkl                                │   │
│  │ - Loads: time_delay_risk_model.pkl                                  │   │
│  │ - Feature engineering                                                │   │
│  │ - Returns: {cost_risk, cost_prob, time_risk, time_prob}             │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                    │                                          │
│                                    ↓                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ API: save_estimate_prediction.php                                    │   │
│  │ - Stores predictions in contractor_send_estimates                    │   │
│  │ - Fields: predicted_cost_risk_level, predicted_cost_probability     │   │
│  │          predicted_time_risk_level, predicted_time_probability      │   │
│  │          prediction_generated_at, model_version                      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  Database: contractor_send_estimates (UPDATE with predictions)               │
└───────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ↓ Homeowner Accepts Estimate
┌─────────────────────────────────────────────────────────────────────────────┐
│                    STAGE 3: PROJECT CREATION                                 │
│                                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ API: create_project_from_estimate.php                                │   │
│  │ - Validates estimate is accepted                                     │   │
│  │ - Creates project with all details                                   │   │
│  │ - INSERT INTO construction_projects                                  │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                    │                                          │
│                                    ↓ AUTOMATIC TRIGGER                        │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ TRIGGER: copy_predictions_to_project                                 │   │
│  │ - Fires: AFTER INSERT ON construction_projects                       │   │
│  │ - Logic:                                                             │   │
│  │   1. IF estimate_id exists                                           │   │
│  │   2. SELECT predictions FROM contractor_send_estimates               │   │
│  │   3. UPDATE construction_projects WITH predictions                   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  Database: construction_projects (predictions copied from estimate)          │
│  Status: created                                                             │
│  Predictions: STORED AND LINKED                                              │
└───────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ↓ Work Begins (actual_start_date set)
┌─────────────────────────────────────────────────────────────────────────────┐
│                    STAGE 4: PREDICTION LOCKING                               │
│                                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ TRIGGER: lock_predictions_on_start                                   │   │
│  │ - Fires: BEFORE UPDATE ON construction_projects                      │   │
│  │ - Logic:                                                             │   │
│  │   1. IF actual_start_date changes from NULL to date                  │   │
│  │   2. SET predictions_locked = 1                                      │   │
│  │   3. PREVENT any modification to prediction fields                   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  Database: construction_projects.predictions_locked = 1                      │
│  Result: PREDICTIONS NOW IMMUTABLE                                           │
└───────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ↓ Construction in Progress
┌─────────────────────────────────────────────────────────────────────────────┐
│                    STAGE 5: PROJECT MONITORING                               │
│                                                                               │
│  ┌──────────────────────────────┐  ┌──────────────────────────────────┐    │
│  │ COST TRACKING                │  │ TIME TRACKING                     │    │
│  │                              │  │                                   │    │
│  │ - Stage payments recorded    │  │ - actual_start_date recorded     │    │
│  │ - Custom payments recorded   │  │ - Progress updates tracked       │    │
│  │ - Total actual cost summed   │  │ - actual_end_date recorded       │    │
│  │                              │  │                                   │    │
│  │ Tables:                      │  │ Tables:                           │    │
│  │ - stage_payment_requests     │  │ - daily_progress_updates         │    │
│  │ - custom_payment_requests    │  │ - construction_projects          │    │
│  └──────────────────────────────┘  └──────────────────────────────────┘    │
│                                    │                                          │
│                                    ↓                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ OVERRUN CALCULATION (Automatic)                                      │   │
│  │                                                                       │   │
│  │ Cost Overrun % = ((actual_cost - estimated_cost) / estimated_cost)   │   │
│  │                  × 100                                                │   │
│  │                                                                       │   │
│  │ Time Overrun % = ((actual_days - planned_days) / planned_days)       │   │
│  │                  × 100                                                │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  Database: construction_projects                                             │
│  - actual_cost_overrun_percentage (calculated)                               │
│  - actual_time_overrun_percentage (calculated)                               │
└───────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ↓ Status Changed to 'completed'
┌─────────────────────────────────────────────────────────────────────────────┐
│                    STAGE 6: AUTO-EVALUATION                                  │
│                                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ TRIGGER: auto_evaluate_on_completion                                 │   │
│  │ - Fires: AFTER UPDATE ON construction_projects                       │   │
│  │ - Condition: IF status changes to 'completed'                        │   │
│  │ - Action: CALL evaluate_project_predictions(project_id)              │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                    │                                          │
│                                    ↓                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ PROCEDURE: evaluate_project_predictions                              │   │
│  │                                                                       │   │
│  │ Step 1: calculate_actual_cost_overrun                                │   │
│  │   - Sum all stage payments                                           │   │
│  │   - Sum all custom payments                                          │   │
│  │   - Calculate overrun percentage                                     │   │
│  │   - UPDATE actual_cost_overrun_percentage                            │   │
│  │                                                                       │   │
│  │ Step 2: determine_ground_truth_labels                                │   │
│  │   - Get thresholds (default: 5%)                                     │   │
│  │   - IF cost_overrun >= 5% → cost_ground_truth = 'High'              │   │
│  │   - IF cost_overrun < 5% → cost_ground_truth = 'Low'                │   │
│  │   - IF time_overrun >= 5% → time_ground_truth = 'High'              │   │
│  │   - IF time_overrun < 5% → time_ground_truth = 'Low'                │   │
│  │   - UPDATE ground_truth_labels                                       │   │
│  │                                                                       │   │
│  │ Step 3: classify_predictions (Confusion Matrix)                      │   │
│  │   - Convert Medium → High for binary classification                  │   │
│  │   - Compare predicted vs actual:                                     │   │
│  │     • Predicted High + Actual High = TP (True Positive) ✅          │   │
│  │     • Predicted High + Actual Low = FP (False Positive) ❌          │   │
│  │     • Predicted Low + Actual Low = TN (True Negative) ✅            │   │
│  │     • Predicted Low + Actual High = FN (False Negative) ❌          │   │
│  │   - UPDATE classification fields                                     │   │
│  │   - SET correctness flags                                            │   │
│  │   - SET evaluation_completed_at                                      │   │
│  │                                                                       │   │
│  │ Step 4: update_aggregated_metrics                                    │   │
│  │   - Count all TP, FP, TN, FN across all projects                    │   │
│  │   - Calculate:                                                       │   │
│  │     • Accuracy = (TP + TN) / Total                                   │   │
│  │     • Precision = TP / (TP + FP)                                     │   │
│  │     • Recall = TP / (TP + FN)                                        │   │
│  │     • F1 Score = 2 × (Precision × Recall) / (Precision + Recall)    │   │
│  │     • Specificity = TN / (TN + FP)                                   │   │
│  │     • FPR = FP / (FP + TN)                                           │   │
│  │   - INSERT/UPDATE ai_evaluation_metrics                              │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  Database Updates:                                                           │
│  - construction_projects (evaluation fields populated)                       │
│  - ai_evaluation_metrics (performance metrics updated)                       │
│  - ai_prediction_audit (audit log created)                                   │
│                                                                               │
│  Result: EVALUATION COMPLETE, METRICS UPDATED                                │
└───────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                    STAGE 7: METRICS RETRIEVAL                                │
│                                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ API: get_evaluation_metrics.php                                      │   │
│  │                                                                       │   │
│  │ Query Types:                                                         │   │
│  │ 1. ?type=latest                                                      │   │
│  │    - View: v_latest_ai_metrics                                       │   │
│  │    - Returns: Current accuracy, precision, recall, F1                │   │
│  │                                                                       │   │
│  │ 2. ?type=history&days=30                                             │   │
│  │    - Returns: Performance trends over time                           │   │
│  │                                                                       │   │
│  │ 3. ?type=project&project_id=123                                      │   │
│  │    - View: v_project_evaluation_summary                              │   │
│  │    - Returns: Prediction vs actual for specific project              │   │
│  │                                                                       │   │
│  │ 4. ?type=config                                                      │   │
│  │    - Returns: Current thresholds and settings                        │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ API: trigger_evaluation.php (Manual Trigger)                         │   │
│  │ - Admin can manually trigger evaluation                              │   │
│  │ - Can re-evaluate projects (force mode)                              │   │
│  │ - Batch evaluation of all eligible projects                          │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  Result: METRICS AVAILABLE FOR ANALYSIS                                      │
└───────────────────────────────────┬─────────────────────────────────────────┘
                                    │
                                    ↓
┌─────────────────────────────────────────────────────────────────────────────┐
│                    STAGE 8: CONTINUOUS IMPROVEMENT                           │
│                                                                               │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │ FEEDBACK LOOP (Future Enhancement)                                   │   │
│  │                                                                       │   │
│  │ 1. Export evaluation data                                            │   │
│  │ 2. Analyze prediction errors                                         │   │
│  │ 3. Retrain models with new data                                      │   │
│  │ 4. Deploy improved models                                            │   │
│  │ 5. Update model_version                                              │   │
│  │ 6. Monitor performance improvement                                   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  Status: NOT YET IMPLEMENTED (Manual retraining currently)                   │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Key Integration Points

### 1. Frontend → Backend
- **Component:** RiskAssessmentPreview.jsx
- **API:** predict_construction_risks.php
- **Method:** POST with JSON body
- **Response:** Risk levels and probabilities

### 2. Backend → Python ML
- **Executor:** shell_exec() in PHP
- **Communication:** Temporary JSON file
- **Script:** predict_risks_api.py
- **Models:** .pkl files loaded via joblib

### 3. Backend → Database
- **Connection:** PDO/MySQLi
- **Triggers:** Automatic on INSERT/UPDATE
- **Procedures:** Called from triggers
- **Views:** Queried by APIs

### 4. Database Triggers
- **copy_predictions_to_project:** Fires on project creation
- **lock_predictions_on_start:** Fires when work begins
- **auto_evaluate_on_completion:** Fires when project completes

## Data Flow Summary

```
User Input → AI Prediction → Estimate Storage → Project Creation → 
Prediction Copy → Prediction Lock → Project Monitoring → 
Actual Data Collection → Project Completion → Auto-Evaluation → 
Metrics Calculation → Metrics Storage → Metrics Retrieval → 
Analysis & Improvement
```

## Critical Success Factors

1. ✅ **Predictions stored BEFORE project creation** (with estimates)
2. ✅ **Predictions automatically copied** to projects (via trigger)
3. ✅ **Predictions locked** when work begins (immutable)
4. ✅ **Evaluation runs automatically** on completion (via trigger)
5. ✅ **Metrics calculated correctly** (confusion matrix logic)
6. ✅ **Complete audit trail** maintained (all events logged)

## System Health Indicators

### Green (Healthy)
- Predictions generated successfully
- Predictions copied to projects
- Predictions locked on time
- Evaluation completes automatically
- Metrics calculated correctly
- No data integrity issues

### Yellow (Warning)
- Prediction API slow response
- Python script errors
- Evaluation skipped (missing data)
- Metrics not updating

### Red (Critical)
- Predictions not being stored
- Trigger not firing
- Evaluation failing
- Data corruption detected

## Monitoring Queries

```sql
-- Check if predictions are being stored
SELECT COUNT(*) FROM contractor_send_estimates 
WHERE predicted_cost_risk_level IS NOT NULL;

-- Check if predictions are being copied
SELECT COUNT(*) FROM construction_projects 
WHERE predicted_cost_risk_level IS NOT NULL;

-- Check if evaluations are running
SELECT COUNT(*) FROM construction_projects 
WHERE status = 'completed' AND evaluation_completed_at IS NOT NULL;

-- Check latest metrics
SELECT * FROM v_latest_ai_metrics;

-- Check for locked predictions
SELECT COUNT(*) FROM construction_projects 
WHERE predictions_locked = 1;
```

## Troubleshooting Guide

### Issue: Predictions not showing in project
**Check:**
1. Estimate has predictions stored
2. Trigger `copy_predictions_to_project` exists
3. Project has valid estimate_id

### Issue: Evaluation not running
**Check:**
1. Project status is 'completed'
2. Trigger `auto_evaluate_on_completion` exists
3. Project has predictions
4. Config `auto_evaluation_enabled` = 1

### Issue: Metrics not updating
**Check:**
1. Evaluation completed successfully
2. Procedure `update_aggregated_metrics` exists
3. Table `ai_evaluation_metrics` exists
4. No SQL errors in logs

---

**Document Version:** 1.0  
**Last Updated:** March 11, 2026  
**Status:** System Fully Operational
