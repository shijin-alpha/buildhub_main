# 🎨 AI SYSTEM COMPLETE WORKFLOW - VISUAL GUIDE

**Date:** March 11, 2026  
**Status:** ✅ FULLY OPERATIONAL (after applying fix)

---

## 🌊 DATA FLOW DIAGRAM

```
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    HOMEOWNER INTERFACE                            ┃
┃  📱 HomeownerRequestWizard.jsx                                    ┃
┃  User fills: plot size, budget, rooms, floors, preferences        ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ Form Data
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    RISK ASSESSMENT PREVIEW                        ┃
┃  🎯 RiskAssessmentPreview.jsx                                     ┃
┃  - Displays risk assessment modal                                 ┃
┃  - Shows cost and time risk predictions                           ┃
┃  - Blocks high-risk projects                                      ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ POST Request
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    PREDICTION API                                 ┃
┃  🤖 predict_construction_risks.php                                ┃
┃  - Receives project parameters                                    ┃
┃  - Calls Python ML script                                         ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ shell_exec()
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    ML PREDICTION ENGINE                           ┃
┃  🧠 predict_risks_api.py                                          ┃
┃  - Loads trained models (.pkl files)                              ┃
┃  - Processes features                                             ┃
┃  - Generates predictions                                          ┃
┃                                                                   ┃
┃  Models:                                                          ┃
┃  📊 cost_overrun_risk_model.pkl (94.7% accuracy)                  ┃
┃  📊 time_delay_risk_model.pkl (98.9% accuracy)                    ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ JSON Response
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    PREDICTION RESULTS                             ┃
┃  {                                                                ┃
┃    "cost_overrun_risk": {                                         ┃
┃      "risk_level": "Medium",                                      ┃
┃      "probability": 0.65,                                         ┃
┃      "explanation": [...]                                         ┃
┃    },                                                             ┃
┃    "time_delay_risk": {                                           ┃
┃      "risk_level": "Low",                                         ┃
┃      "probability": 0.25,                                         ┃
┃      "explanation": [...]                                         ┃
┃    },                                                             ┃
┃    "model_version": "v1.0.0"                                      ┃
┃  }                                                                ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ Display to User
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    USER DECISION POINT                            ┃
┃  ⚠️  High Risk? → Must Revise                                     ┃
┃  ✅  Low/Medium Risk? → Can Proceed                               ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ If Proceeding
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    SAVE PREDICTION TO ESTIMATE ✅ NEW             ┃
┃  💾 save_estimate_prediction.php                                  ┃
┃  - Validates prediction data                                      ┃
┃  - Saves to contractor_send_estimates table                       ┃
┃  - Returns success confirmation                                   ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ INSERT
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    ESTIMATES TABLE                                ┃
┃  📋 contractor_send_estimates                                     ┃
┃  ┌────────────────────────────────────────────────────────────┐  ┃
┃  │ id: 123                                                    │  ┃
┃  │ homeowner_id: 45                                           │  ┃
┃  │ predicted_cost_risk_level: "Medium"                        │  ┃
┃  │ predicted_cost_probability: 0.65                           │  ┃
┃  │ predicted_time_risk_level: "Low"                           │  ┃
┃  │ predicted_time_probability: 0.25                           │  ┃
┃  │ prediction_generated_at: "2026-03-11 14:30:00"            │  ┃
┃  │ model_version: "v1.0.0"                                    │  ┃
┃  └────────────────────────────────────────────────────────────┘  ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ User Submits Request
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    PROJECT CREATION                               ┃
┃  🏗️ submit_request.php                                            ┃
┃  - Creates construction_projects record                           ┃
┃  - Links to estimate via estimate_id                              ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ INSERT triggers...
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    AUTOMATIC PREDICTION COPY ✅ NEW               ┃
┃  ⚡ copy_predictions_to_project TRIGGER                           ┃
┃  - Fires automatically on project INSERT                          ┃
┃  - Reads predictions from estimate                                ┃
┃  - Copies to construction_projects                                ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ UPDATE
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    PROJECTS TABLE                                 ┃
┃  🏗️ construction_projects                                         ┃
┃  ┌────────────────────────────────────────────────────────────┐  ┃
┃  │ id: 456                                                    │  ┃
┃  │ estimate_id: 123                                           │  ┃
┃  │ predicted_cost_risk_level: "Medium" ← COPIED               │  ┃
┃  │ predicted_cost_probability: 0.65 ← COPIED                  │  ┃
┃  │ predicted_time_risk_level: "Low" ← COPIED                  │  ┃
┃  │ predicted_time_probability: 0.25 ← COPIED                  │  ┃
┃  │ prediction_generated_at: "2026-03-11 14:30:00" ← COPIED   │  ┃
┃  │ model_version: "v1.0.0" ← COPIED                           │  ┃
┃  │ predictions_locked: 0                                      │  ┃
┃  │ status: "pending"                                          │  ┃
┃  └────────────────────────────────────────────────────────────┘  ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ Project Execution...
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    PROJECT TRACKING                               ┃
┃  📊 Multiple Systems Running in Parallel                          ┃
┃                                                                   ┃
┃  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┃
┃  │ Schedule        │  │ Progress        │  │ Budget          │  ┃
┃  │ Tracking        │  │ Monitoring      │  │ Tracking        │  ┃
┃  │                 │  │                 │  │                 │  ┃
┃  │ • Planned dates │  │ • Daily updates │  │ • Stage payments│  ┃
┃  │ • Actual dates  │  │ • Completion %  │  │ • Custom costs  │  ┃
┃  │ • Time overrun  │  │ • Work done     │  │ • Total spent   │  ┃
┃  └─────────────────┘  └─────────────────┘  └─────────────────┘  ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ Work Begins → predictions_locked = 1
                              │ Project Completes → status = 'completed'
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    AUTOMATIC EVALUATION TRIGGER                   ┃
┃  ⚡ auto_evaluate_on_completion TRIGGER                           ┃
┃  - Fires when status changes to 'completed'                       ┃
┃  - Calls evaluate_project_predictions()                           ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ CALL
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    AI SELF-EVALUATION PIPELINE                    ┃
┃  🧮 evaluate_project_predictions(project_id)                      ┃
┃                                                                   ┃
┃  Step 1: Calculate Actual Cost Overrun                           ┃
┃  ┌────────────────────────────────────────────────────────────┐  ┃
┃  │ calculate_actual_cost_overrun()                            │  ┃
┃  │ • Sum all stage payments                                   │  ┃
┃  │ • Sum all custom payments                                  │  ┃
┃  │ • Compare to estimated_cost                                │  ┃
┃  │ • Calculate percentage overrun                             │  ┃
┃  │ → actual_cost_overrun_percentage                           │  ┃
┃  └────────────────────────────────────────────────────────────┘  ┃
┃                                                                   ┃
┃  Step 2: Determine Ground Truth Labels                           ┃
┃  ┌────────────────────────────────────────────────────────────┐  ┃
┃  │ determine_ground_truth_labels()                            │  ┃
┃  │ • Compare actual overrun to threshold (5%)                 │  ┃
┃  │ • If overrun >= 5% → "High"                                │  ┃
┃  │ • If overrun < 5% → "Low"                                  │  ┃
┃  │ → cost_ground_truth_label                                  │  ┃
┃  │ → time_ground_truth_label                                  │  ┃
┃  └────────────────────────────────────────────────────────────┘  ┃
┃                                                                   ┃
┃  Step 3: Classify Predictions (Confusion Matrix)                 ┃
┃  ┌────────────────────────────────────────────────────────────┐  ┃
┃  │ classify_predictions()                                     │  ┃
┃  │                                                            │  ┃
┃  │ Predicted High + Actual High = TP (True Positive)         │  ┃
┃  │ Predicted High + Actual Low  = FP (False Positive)        │  ┃
┃  │ Predicted Low  + Actual Low  = TN (True Negative)         │  ┃
┃  │ Predicted Low  + Actual High = FN (False Negative)        │  ┃
┃  │                                                            │  ┃
┃  │ → cost_prediction_classification                          │  ┃
┃  │ → time_prediction_classification                          │  ┃
┃  │ → cost_prediction_correct (1/0)                           │  ┃
┃  │ → time_prediction_correct (1/0)                           │  ┃
┃  └────────────────────────────────────────────────────────────┘  ┃
┃                                                                   ┃
┃  Step 4: Update Aggregated Metrics                               ┃
┃  ┌────────────────────────────────────────────────────────────┐  ┃
┃  │ update_aggregated_metrics()                                │  ┃
┃  │ • Count TP, FP, TN, FN across all projects                │  ┃
┃  │ • Calculate accuracy = (TP + TN) / Total                   │  ┃
┃  │ • Calculate precision = TP / (TP + FP)                     │  ┃
┃  │ • Calculate recall = TP / (TP + FN)                        │  ┃
┃  │ • Calculate F1-score = 2 * (P * R) / (P + R)              │  ┃
┃  │ • Calculate specificity = TN / (TN + FP)                   │  ┃
┃  │ → ai_evaluation_metrics table                              │  ┃
┃  └────────────────────────────────────────────────────────────┘  ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ INSERT/UPDATE
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    EVALUATION METRICS TABLE                       ┃
┃  📈 ai_evaluation_metrics                                         ┃
┃  ┌────────────────────────────────────────────────────────────┐  ┃
┃  │ metric_type: "cost_overrun"                                │  ┃
┃  │ evaluation_date: "2026-03-11"                              │  ┃
┃  │ total_projects: 25                                         │  ┃
┃  │ true_positives: 8                                          │  ┃
┃  │ false_positives: 2                                         │  ┃
┃  │ true_negatives: 12                                         │  ┃
┃  │ false_negatives: 3                                         │  ┃
┃  │ accuracy: 0.80 (80%)                                       │  ┃
┃  │ precision_score: 0.80                                      │  ┃
┃  │ recall_score: 0.73                                         │  ┃
┃  │ f1_score: 0.76                                             │  ┃
┃  │ specificity: 0.86                                          │  ┃
┃  └────────────────────────────────────────────────────────────┘  ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
                              │
                              │ Query for Dashboard
                              ▼
┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃                    ADMIN DASHBOARD                                ┃
┃  📊 ML Analytics Dashboard                                        ┃
┃  - View AI performance metrics                                    ┃
┃  - See prediction accuracy trends                                 ┃
┃  - Monitor model performance                                      ┃
┃  - Identify areas for improvement                                 ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛
```

---

## 🔄 SELF-LEARNING LOOP

```
    ┌─────────────────────────────────────────────────────────┐
    │                                                         │
    │  🔄 CONTINUOUS IMPROVEMENT CYCLE                        │
    │                                                         │
    │  ┌──────────────┐                                       │
    │  │ 1. PREDICT   │                                       │
    │  │ ML generates │                                       │
    │  │ risk levels  │                                       │
    │  └──────┬───────┘                                       │
    │         │                                               │
    │         ▼                                               │
    │  ┌──────────────┐                                       │
    │  │ 2. SAVE      │                                       │
    │  │ Store with   │                                       │
    │  │ estimate     │                                       │
    │  └──────┬───────┘                                       │
    │         │                                               │
    │         ▼                                               │
    │  ┌──────────────┐                                       │
    │  │ 3. EXECUTE   │                                       │
    │  │ Track actual │                                       │
    │  │ outcomes     │                                       │
    │  └──────┬───────┘                                       │
    │         │                                               │
    │         ▼                                               │
    │  ┌──────────────┐                                       │
    │  │ 4. EVALUATE  │                                       │
    │  │ Compare      │                                       │
    │  │ predicted vs │                                       │
    │  │ actual       │                                       │
    │  └──────┬───────┘                                       │
    │         │                                               │
    │         ▼                                               │
    │  ┌──────────────┐                                       │
    │  │ 5. LEARN     │                                       │
    │  │ Update       │                                       │
    │  │ metrics and  │                                       │
    │  │ retrain      │                                       │
    │  └──────┬───────┘                                       │
    │         │                                               │
    │         └───────────────────────────────────────────────┘
    │                 (Loop back to step 1)
    └─────────────────────────────────────────────────────────┘
```

---

## 🎯 KEY INTEGRATION POINTS

### ✅ FIXED: Prediction Storage
```
BEFORE:
RiskAssessmentPreview.jsx → predict_construction_risks.php
                          → Display only ❌ NOT SAVED

AFTER:
RiskAssessmentPreview.jsx → predict_construction_risks.php
                          → Display
                          → save_estimate_prediction.php ✅ SAVED
```

### ✅ FIXED: Prediction Copy
```
BEFORE:
Project created → No predictions ❌

AFTER:
Project created → copy_predictions_to_project trigger
               → Predictions copied from estimate ✅
```

### ✅ WORKING: Automatic Evaluation
```
Project completed → auto_evaluate_on_completion trigger
                 → evaluate_project_predictions()
                 → Metrics updated ✅
```

---

## 📊 CONFUSION MATRIX EXPLAINED

```
                    ACTUAL OUTCOME
                 Low Risk    High Risk
              ┌────────────┬────────────┐
PREDICTED     │            │            │
Low Risk      │     TN     │     FN     │
              │  (Correct) │  (Missed)  │
              ├────────────┼────────────┤
PREDICTED     │            │            │
High Risk     │     FP     │     TP     │
              │ (False     │  (Correct) │
              │  Alarm)    │            │
              └────────────┴────────────┘

TN (True Negative):  Predicted Low, Actually Low  ✅
TP (True Positive):  Predicted High, Actually High ✅
FP (False Positive): Predicted High, Actually Low  ❌
FN (False Negative): Predicted Low, Actually High  ❌
```

---

## 🎓 PERFORMANCE METRICS

### Accuracy
```
Accuracy = (TP + TN) / (TP + TN + FP + FN)
         = Correct Predictions / Total Predictions
         = How often is the model right?
```

### Precision
```
Precision = TP / (TP + FP)
          = True Positives / All Positive Predictions
          = When it predicts High, how often is it right?
```

### Recall (Sensitivity)
```
Recall = TP / (TP + FN)
       = True Positives / All Actual Positives
       = Of all High-risk projects, how many did we catch?
```

### F1-Score
```
F1 = 2 * (Precision * Recall) / (Precision + Recall)
   = Harmonic mean of Precision and Recall
   = Balanced measure of model performance
```

### Specificity
```
Specificity = TN / (TN + FP)
            = True Negatives / All Actual Negatives
            = Of all Low-risk projects, how many did we correctly identify?
```

---

## 🚀 DEPLOYMENT CHECKLIST

- [ ] Run `php apply_prediction_copy_trigger.php`
- [ ] Verify trigger created successfully
- [ ] Test with new project request
- [ ] Verify predictions saved to estimate
- [ ] Verify predictions copied to project
- [ ] Complete a test project
- [ ] Verify evaluation runs automatically
- [ ] Check metrics in ai_evaluation_metrics table
- [ ] View metrics in admin dashboard

---

## 📚 FILE REFERENCE

| Component | File Path |
|-----------|-----------|
| Frontend Risk Assessment | `frontend/src/components/RiskAssessmentPreview.jsx` |
| Prediction API | `backend/api/ml/predict_construction_risks.php` |
| ML Engine | `backend/ml/predict_risks_api.py` |
| Save to Estimate | `backend/api/ml/save_estimate_prediction.php` ✅ NEW |
| Save to Project | `backend/api/ml/save_ai_prediction.php` |
| Copy Trigger | `backend/database/prediction_copy_trigger.sql` ✅ NEW |
| Evaluation Trigger | `backend/database/ai_self_evaluation_schema.sql` |
| Evaluation Procedures | `backend/database/ai_evaluation_procedures.sql` |
| Setup Script | `apply_prediction_copy_trigger.php` ✅ NEW |

---

**Visual Guide Created:** March 11, 2026  
**Status:** ✅ COMPLETE AND READY FOR DEPLOYMENT
