# 🎨 Construction AI System - Visual Workflow Guide

**Purpose:** Easy-to-understand visual representation of the complete AI system  
**Audience:** All stakeholders (developers, managers, users)  
**Date:** March 11, 2026

---

## 🌊 Complete Data Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                         USER INTERACTION                             │
│                                                                      │
│  👤 Homeowner fills project form                                    │
│  📝 Plot size, building size, floors, bedrooms, budget, etc.        │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      FRONTEND PROCESSING                             │
│                                                                      │
│  ⚛️  RiskAssessmentPreview.jsx                                      │
│  📤 POST /api/ml/predict_construction_risks.php                     │
│  📦 Sends: formData (all project details)                           │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      BACKEND API LAYER                               │
│                                                                      │
│  🔧 predict_construction_risks.php                                  │
│  🔄 Forwards request to Python ML service                           │
│  🌐 HTTP POST to localhost:5000/predict                             │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      ML PREDICTION SERVICE                           │
│                                                                      │
│  🐍 Python Flask API (predict_risks_api.py)                         │
│  📊 Loads trained models:                                           │
│     • cost_overrun_risk_model.pkl                                   │
│     • time_delay_risk_model.pkl                                     │
│  🧮 Feature engineering and preprocessing                           │
│  🤖 Model inference                                                 │
│  📈 Risk classification (Low/Medium/High)                           │
│  📤 Returns: {cost_risk, time_risk, probabilities, explanations}    │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      PREDICTION DISPLAY                              │
│                                                                      │
│  🎨 RiskAssessmentPreview.jsx shows results                         │
│  💰 Cost Risk: High/Medium/Low with probability                     │
│  ⏰ Time Risk: High/Medium/Low with probability                     │
│  💡 User-friendly explanations                                      │
│  ⚠️  Blocking logic if both risks are High                          │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   AUTOMATIC PREDICTION STORAGE                       │
│                                                                      │
│  💾 savePredictionToDatabase() function                             │
│  📤 POST /api/ml/save_estimate_prediction.php                       │
│  📦 Sends: {estimate_id, cost_risk, time_risk, probabilities}       │
│                                                                      │
│  🗄️  Database: contractor_send_estimates                            │
│  ✅ Predictions stored BEFORE project creation                      │
│  🔑 Key: estimate_id (not project_id)                               │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      USER DECISION POINT                             │
│                                                                      │
│  ✅ User clicks "Continue" → Proceed with project                   │
│  ❌ User clicks "Revise" → Go back and modify details               │
│  🚫 If both risks High → MUST revise (blocked)                      │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      PROJECT CREATION                                │
│                                                                      │
│  📝 Contractor accepts estimate                                     │
│  🆕 New row in construction_projects table                          │
│  🔗 estimate_id foreign key links to estimate                       │
│                                                                      │
│  ⚡ TRIGGER: copy_predictions_to_project                            │
│  📋 Automatically copies predictions from estimate to project       │
│  ✅ Predictions now in construction_projects table                  │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      PROJECT EXECUTION PHASE                         │
│                                                                      │
│  🏗️  Construction begins                                            │
│  📅 actual_start_date set                                           │
│                                                                      │
│  ⚡ TRIGGER: lock_predictions_on_start                              │
│  🔒 predictions_locked = 1 (immutable)                              │
│                                                                      │
│  📊 Three parallel monitoring streams:                              │
│                                                                      │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐ │
│  │ 📅 SCHEDULE      │  │ 📈 PROGRESS      │  │ 💰 BUDGET        │ │
│  │                  │  │                  │  │                  │ │
│  │ • Planned dates  │  │ • Daily updates  │  │ • Stage payments │ │
│  │ • Actual dates   │  │ • Completion %   │  │ • Custom pays    │ │
│  │ • Time overrun   │  │ • Photos         │  │ • Total cost     │ │
│  │                  │  │ • Work done      │  │ • Overrun calc   │ │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘ │
│                                                                      │
│  🔌 APIs:                                                            │
│  • schedule_tracking.php                                            │
│  • daily_progress_updates (table)                                   │
│  • budget_tracking.php (NEW)                                        │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      PROJECT COMPLETION                              │
│                                                                      │
│  ✅ Contractor/Admin sets status = 'completed'                      │
│  📅 actual_end_date set                                             │
│                                                                      │
│  ⚡ TRIGGER: auto_evaluate_on_completion                            │
│  🚀 Automatically fires evaluation pipeline                         │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   AI SELF-EVALUATION PIPELINE                        │
│                                                                      │
│  🔧 Stored Procedure: evaluate_project_predictions(project_id)      │
│                                                                      │
│  Step 1: Calculate Actual Overruns                                  │
│  ├─ 💰 Cost: Sum all payments vs estimate                           │
│  │   actual_cost_overrun_pct = (total - estimate) / estimate * 100 │
│  └─ ⏰ Time: Compare actual vs planned dates                         │
│      actual_time_overrun_pct = (actual - planned) / planned * 100  │
│                                                                      │
│  Step 2: Determine Ground Truth                                     │
│  ├─ 💰 Cost: If overrun > 5% → "High", else → "Low"                │
│  └─ ⏰ Time: If overrun > 10% → "High", else → "Low"                │
│                                                                      │
│  Step 3: Classify Predictions (Confusion Matrix)                    │
│  ┌─────────────────────────────────────────────────────┐           │
│  │              ACTUAL                                 │           │
│  │           High      Low                             │           │
│  │  PRED  ┌────────┬────────┐                          │           │
│  │  High  │   TP   │   FP   │  TP = Correct High      │           │
│  │        │        │        │  FP = False Alarm       │           │
│  │  ├─────┼────────┼────────┤                          │           │
│  │  Low   │   FN   │   TN   │  FN = Missed High       │           │
│  │        │        │        │  TN = Correct Low       │           │
│  │        └────────┴────────┘                          │           │
│  └─────────────────────────────────────────────────────┘           │
│                                                                      │
│  Step 4: Update Aggregated Metrics                                  │
│  ├─ Accuracy = (TP + TN) / Total                                    │
│  ├─ Precision = TP / (TP + FP)                                      │
│  ├─ Recall = TP / (TP + FN)                                         │
│  ├─ F1-Score = 2 * (Precision * Recall) / (P + R)                  │
│  ├─ Specificity = TN / (TN + FP)                                    │
│  └─ False Positive Rate = FP / (FP + TN)                            │
│                                                                      │
│  💾 Store in: ai_evaluation_metrics table                           │
│  ✅ evaluation_completed_at timestamp set                           │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      METRICS STORAGE                                 │
│                                                                      │
│  🗄️  Tables:                                                         │
│  • construction_projects (per-project evaluation)                   │
│  • ai_evaluation_metrics (aggregated metrics)                       │
│                                                                      │
│  📊 Views:                                                           │
│  • v_latest_ai_metrics (current performance)                        │
│  • v_project_evaluation_summary (project details)                   │
│  • v_confusion_matrix_breakdown (TP/FP/TN/FN counts)                │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      METRICS ACCESS                                  │
│                                                                      │
│  🔌 API: get_evaluation_metrics.php                                 │
│                                                                      │
│  Query Types:                                                        │
│  • ?type=latest → Current performance metrics                       │
│  • ?type=history&days=30 → Historical trends                        │
│  • ?type=project&project_id=X → Individual evaluation               │
│  • ?type=config → Current thresholds                                │
│                                                                      │
│  📱 Frontend Dashboard:                                              │
│  • Display accuracy, precision, recall, F1-score                    │
│  • Show confusion matrix visualization                              │
│  • Historical trend charts                                          │
│  • Per-project evaluation details                                   │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      CLOSED-LOOP LEARNING                            │
│                                                                      │
│  📊 Analyze Performance:                                             │
│  • Identify prediction errors (FP, FN)                              │
│  • Analyze feature importance                                       │
│  • Detect patterns in misclassifications                            │
│                                                                      │
│  🔄 Model Improvement:                                               │
│  • Collect new training data from completed projects                │
│  • Retrain models with updated dataset                              │
│  • Validate improved accuracy                                       │
│  • Deploy new model version                                         │
│                                                                      │
│  🚀 Continuous Improvement:                                          │
│  • Monitor metrics over time                                        │
│  • Adjust thresholds based on business needs                        │
│  • Refine feature engineering                                       │
│  • Update model architecture if needed                              │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🔄 State Transitions

### Project Lifecycle States

```
┌──────────┐
│ ESTIMATE │ ← Predictions stored here FIRST
└────┬─────┘
     │
     │ Contractor accepts
     ▼
┌──────────┐
│ PLANNING │ ← Predictions auto-copied via trigger
└────┬─────┘   predictions_locked = 0
     │
     │ Work begins (actual_start_date set)
     ▼
┌──────────┐
│ ACTIVE   │ ← Predictions LOCKED (immutable)
└────┬─────┘   predictions_locked = 1
     │          Monitoring: schedule + progress + budget
     │
     │ Work finishes (status = 'completed')
     ▼
┌──────────┐
│COMPLETED │ ← Evaluation triggered automatically
└────┬─────┘   evaluation_completed_at set
     │
     │ Metrics calculated
     ▼
┌──────────┐
│EVALUATED │ ← Performance metrics stored
└──────────┘   Ready for learning cycle
```

---

## 🎯 Key Decision Points

### 1. Risk Assessment Display

```
User submits form
       │
       ▼
   Generate prediction
       │
       ├─ Both risks HIGH? ──YES──> BLOCK submission
       │                            Show revision message
       │                            User MUST revise
       │
       └─ At least one LOW/MEDIUM? ──> ALLOW submission
                                       Show risk report
                                       User can proceed or revise
```

### 2. Prediction Storage Timing

```
BEFORE (Problem):
  Estimate → Project Created → Try to save prediction
                               ❌ No project_id yet!

AFTER (Solution):
  Estimate → Save prediction with estimate_id ✅
          → Project Created
          → Trigger auto-copies predictions ✅
```

### 3. Evaluation Trigger Logic

```
Project status changes
       │
       ├─ Status = 'completed'? ──NO──> Do nothing
       │
       └─ Status = 'completed'? ──YES──> Check conditions
                                          │
                                          ├─ Has predictions? ──NO──> Skip
                                          │
                                          └─ Has predictions? ──YES──> Run evaluation
                                                                       Calculate metrics
                                                                       Store results
```

---

## 📊 Data Flow Diagram

### Prediction Data Journey

```
┌─────────────────────────────────────────────────────────────┐
│                    DATA TRANSFORMATION                       │
└─────────────────────────────────────────────────────────────┘

INPUT (User Form):
{
  plot_size: 2000,
  building_size: 1500,
  floors: 2,
  bedrooms: 3,
  bathrooms: 2,
  budget: 5000000,
  ...
}
       │
       ▼ Feature Engineering
       │
FEATURES (ML Model):
{
  budget_per_sqft: 3333.33,
  design_complexity: 8,
  site_difficulty: 5,
  planned_duration: 12,
  ...
}
       │
       ▼ Model Inference
       │
PREDICTIONS (Raw):
{
  cost_overrun_probability: 0.8523,
  time_delay_probability: 0.6234
}
       │
       ▼ Risk Classification
       │
RISK LEVELS (Classified):
{
  cost_risk: "High" (prob > 0.7),
  time_risk: "Medium" (0.4 < prob < 0.7)
}
       │
       ▼ Storage
       │
DATABASE (contractor_send_estimates):
{
  estimate_id: 123,
  predicted_cost_risk_level: "High",
  predicted_cost_probability: 0.8523,
  predicted_time_risk_level: "Medium",
  predicted_time_probability: 0.6234,
  prediction_generated_at: "2026-03-11 10:30:00",
  model_version: "v1.0.0"
}
       │
       ▼ Project Creation
       │
DATABASE (construction_projects):
{
  project_id: 456,
  estimate_id: 123,
  predicted_cost_risk_level: "High",  ← Copied by trigger
  predicted_cost_probability: 0.8523,  ← Copied by trigger
  ...
}
       │
       ▼ Project Completion
       │
EVALUATION RESULTS:
{
  actual_cost_overrun_percentage: 12.5,
  cost_ground_truth_label: "High",
  cost_prediction_classification: "TP",
  cost_prediction_correct: 1
}
       │
       ▼ Aggregation
       │
METRICS (ai_evaluation_metrics):
{
  metric_type: "cost_overrun",
  accuracy: 0.85,
  precision: 0.82,
  recall: 0.88,
  f1_score: 0.85,
  true_positives: 45,
  false_positives: 10,
  ...
}
```

---

## 🔧 Component Interaction Map

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND LAYER                            │
│  ┌──────────────────────────────────────────────────┐       │
│  │ RiskAssessmentPreview.jsx                        │       │
│  │ • Displays risk assessment                       │       │
│  │ • Calls prediction API                           │       │
│  │ • Calls storage API                              │       │
│  │ • Handles user decisions                         │       │
│  └──────────────────────────────────────────────────┘       │
└────────────────────┬─────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    BACKEND API LAYER                         │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │ predict_         │  │ save_estimate_   │                │
│  │ construction_    │  │ prediction.php   │                │
│  │ risks.php        │  │                  │                │
│  └────┬─────────────┘  └────┬─────────────┘                │
│       │                     │                               │
│       │                     │                               │
│  ┌────▼─────────────┐  ┌───▼──────────────┐               │
│  │ get_evaluation_  │  │ budget_          │               │
│  │ metrics.php      │  │ tracking.php     │               │
│  └──────────────────┘  └──────────────────┘               │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    ML SERVICE LAYER                          │
│  ┌──────────────────────────────────────────────────┐       │
│  │ predict_risks_api.py (Flask)                     │       │
│  │ • Loads ML models                                │       │
│  │ • Feature engineering                            │       │
│  │ • Model inference                                │       │
│  │ • Risk classification                            │       │
│  └──────────────────────────────────────────────────┘       │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    DATABASE LAYER                            │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │ Tables:          │  │ Triggers:        │                │
│  │ • estimates      │  │ • copy_pred...   │                │
│  │ • projects       │  │ • lock_pred...   │                │
│  │ • payments       │  │ • auto_eval...   │                │
│  │ • metrics        │  │                  │                │
│  └──────────────────┘  └──────────────────┘                │
│                                                              │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │ Stored Procs:    │  │ Views:           │                │
│  │ • evaluate_...   │  │ • v_latest_...   │                │
│  │ • calculate_...  │  │ • v_project_...  │                │
│  └──────────────────┘  └──────────────────┘                │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎨 User Experience Flow

### Homeowner Journey

```
1. 📝 Fill Project Form
   ├─ Plot size, building size
   ├─ Floors, bedrooms, bathrooms
   ├─ Budget, timeline
   └─ Special requirements

2. 🎯 View Risk Assessment
   ├─ See cost risk (High/Medium/Low)
   ├─ See time risk (High/Medium/Low)
   ├─ Read simple explanations
   └─ Understand what it means

3. 🤔 Make Decision
   ├─ Both risks HIGH? → Must revise
   ├─ Risks acceptable? → Can proceed
   └─ Want to adjust? → Can revise

4. ✅ Submit Project
   └─ Predictions saved automatically

5. 🏗️ Monitor Progress
   ├─ View schedule updates
   ├─ See daily progress
   └─ Track budget spending

6. 🎉 Project Completes
   └─ Evaluation happens automatically
```

### Admin/Analyst Journey

```
1. 📊 View Dashboard
   └─ See overall system performance

2. 📈 Check Metrics
   ├─ Accuracy: How often are we correct?
   ├─ Precision: Are high-risk predictions reliable?
   ├─ Recall: Are we catching all high-risk projects?
   └─ F1-Score: Overall balance

3. 🔍 Analyze Errors
   ├─ False Positives: Predicted high, actually low
   ├─ False Negatives: Predicted low, actually high
   └─ Identify patterns

4. 🔄 Improve Models
   ├─ Collect new training data
   ├─ Retrain models
   ├─ Deploy improved version
   └─ Monitor improvement
```

---

## 💡 Key Insights

### Why This Architecture Works

1. **Timing Solution:** Store predictions with estimates, not projects
2. **Automation:** Triggers handle data transfer automatically
3. **Immutability:** Predictions locked when work starts
4. **Transparency:** Complete evaluation with confusion matrix
5. **Accessibility:** REST APIs for easy metrics access
6. **Scalability:** Database views for efficient queries
7. **Maintainability:** Clear separation of concerns

### Critical Success Factors

- ✅ Predictions stored before project creation
- ✅ Automatic data transfer via triggers
- ✅ Locked predictions ensure integrity
- ✅ Automatic evaluation on completion
- ✅ Comprehensive performance metrics
- ✅ Easy access to evaluation data

---

**System Status:** PRODUCTION READY ✅  
**Visual Guide Version:** 1.0  
**Last Updated:** March 11, 2026

