# ML Prediction System - Corrected Workflow Diagram

## Complete System Lifecycle

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    STAGE 1: HOMEOWNER REQUEST                           │
│                    (PRIMARY PREDICTION STORAGE)                         │
└─────────────────────────────────────────────────────────────────────────┘

    Homeowner fills custom request form
           │
           ├─ plot_size, building_size, num_floors
           ├─ budget_range, num_bedrooms, num_bathrooms
           ├─ plot_shape, topography, design_style
           └─ special features, requirements
           │
           ▼
    Frontend calls ML Service API
    POST /backend/api/ml/predict_construction_risks.php
           │
           ▼
    ML Service (FastAPI) generates predictions
    - Loads pre-trained models (in memory)
    - Extracts features from form data
    - Predicts cost_risk_level & time_risk_level
    - Calculates probabilities
    - Generates explanations
           │
           ▼
    Predictions displayed in Risk Assessment Modal
    🟢 Low Risk | 🟡 Medium Risk | 🔴 High Risk
           │
           ▼
    Frontend calls Save Prediction API
    POST /backend/api/ml/save_layout_request_prediction.php
           │
           ▼
    ✅ STORED IN layout_requests TABLE
    - predicted_cost_risk_level
    - predicted_cost_probability
    - predicted_time_risk_level
    - predicted_time_probability
    - prediction_generated_at
    - model_version
    - prediction_features (JSON)
    - prediction_explanation (JSON)


┌─────────────────────────────────────────────────────────────────────────┐
│                    STAGE 2: CONTRACTOR ESTIMATE                         │
│                    (PREDICTION COPY)                                    │
└─────────────────────────────────────────────────────────────────────────┘

    Contractor receives layout_request
           │
           ▼
    Contractor creates estimate
    - materials, cost_breakdown, total_cost
    - timeline, notes
           │
           ▼
    Backend calls Copy Predictions API
    POST /backend/api/ml/copy_predictions_to_estimate.php
    {
      "estimate_id": 456,
      "layout_request_id": 123
    }
           │
           ▼
    ✅ COPIED TO contractor_send_estimates TABLE
    - All prediction fields copied
    - layout_request_id linked (FK)
    - estimate_id now exists


┌─────────────────────────────────────────────────────────────────────────┐
│                    STAGE 3: PROJECT CREATION                            │
│                    (EXISTING LOGIC)                                     │
└─────────────────────────────────────────────────────────────────────────┘

    Homeowner accepts estimate
           │
           ▼
    Project created in construction_projects
    - estimate_id linked
    - project_name, homeowner_id, contractor_id
    - estimated_cost, estimated_timeline
           │
           ▼
    Existing trigger copies predictions
    FROM contractor_send_estimates
    TO construction_projects
           │
           ▼
    ✅ PREDICTIONS IN construction_projects TABLE
    - All prediction fields copied
    - predictions_locked = 0 (initially)
           │
           ▼
    Work begins (actual_start_date set)
           │
           ▼
    Trigger locks predictions
    predictions_locked = 1 (IMMUTABLE)


┌─────────────────────────────────────────────────────────────────────────┐
│                    STAGE 4: PROJECT COMPLETION                          │
│                    (3-CLASS EVALUATION)                                 │
└─────────────────────────────────────────────────────────────────────────┘

    Project marked as completed
    status = 'completed'
           │
           ▼
    Calculate actual costs
    - Sum of stage_payment_requests
    - Sum of custom_payment_requests
    - actual_cost_overrun_percentage calculated
           │
           ▼
    Calculate actual timeline
    - actual_start_date to actual_end_date
    - actual_time_overrun_percentage calculated
           │
           ▼
    CALL determine_ground_truth_3class(project_id)
    
    Cost Classification:
    - < 5% overrun → Low
    - 5-15% overrun → Medium
    - > 15% overrun → High
    
    Time Classification:
    - < 5% overrun → Low
    - 5-15% overrun → Medium
    - > 15% overrun → High
           │
           ▼
    ✅ GROUND TRUTH LABELS SET
    - cost_ground_truth_label (Low/Medium/High)
    - time_ground_truth_label (Low/Medium/High)
           │
           ▼
    CALL classify_predictions_3class(project_id)
    
    Evaluation Logic:
    IF predicted_cost_risk_level == cost_ground_truth_label
        THEN cost_prediction_correct = 1
        ELSE cost_prediction_correct = 0
    
    IF predicted_time_risk_level == time_ground_truth_label
        THEN time_prediction_correct = 1
        ELSE time_prediction_correct = 0
           │
           ▼
    ✅ EVALUATION COMPLETED
    - cost_prediction_correct (0 or 1)
    - time_prediction_correct (0 or 1)
    - evaluation_completed_at (timestamp)
           │
           ▼
    Audit log created
    - event_type: 'evaluation_completed'
    - event_data: predictions vs actuals


┌─────────────────────────────────────────────────────────────────────────┐
│                    STAGE 5: MODEL RETRAINING                            │
│                    (IMPROVED PIPELINE)                                  │
└─────────────────────────────────────────────────────────────────────────┘

    Check retraining eligibility
    python backend/ml/retrain_models.py
           │
           ▼
    Count completed projects with evaluations
    SELECT COUNT(*) FROM construction_projects
    WHERE status = 'completed'
      AND evaluation_completed_at IS NOT NULL
      AND predicted_cost_risk_level IS NOT NULL
      AND cost_ground_truth_label IS NOT NULL
           │
           ▼
    IF count >= 150 THEN proceed
    ELSE skip retraining
           │
           ▼
    Extract complete feature set from database
    - Join construction_projects
    - Join contractor_send_estimates
    - Join layout_requests
    - Parse requirements JSON
    - Extract all original features
           │
           ▼
    Engineer derived features
    - complexity_score
    - budget_per_sqft
    - building_to_plot_ratio
    - One-hot encode categoricals
           │
           ▼
    Train Cost Overrun Model
    - RandomForestClassifier (200 trees)
    - 3-class output (Low/Medium/High)
    - Cross-validation
    - Calculate accuracy, precision, recall, F1
           │
           ▼
    Train Time Delay Model
    - RandomForestClassifier (200 trees)
    - 3-class output (Low/Medium/High)
    - Cross-validation
    - Calculate accuracy, precision, recall, F1
           │
           ▼
    Save models with version
    - cost_overrun_risk_model_v20260312_143022.pkl
    - time_delay_risk_model_v20260312_143022.pkl
    - Also save as current models (no version suffix)
           │
           ▼
    Update model version in database
    UPDATE ai_evaluation_config
    SET config_value = 'v20260312_143022'
    WHERE config_key = 'current_model_version'
           │
           ▼
    Log retraining event
    INSERT INTO ai_prediction_audit
    - event_type: 'model_retrained'
    - event_data: version, accuracies, project_count
           │
           ▼
    ✅ RETRAINING COMPLETED
    - New models deployed
    - Version tracked
    - Metrics logged


┌─────────────────────────────────────────────────────────────────────────┐
│                    EVALUATION METRICS DASHBOARD                         │
└─────────────────────────────────────────────────────────────────────────┘

    3-Class Confusion Matrix (Cost):
    
                    Predicted
                Low   Med   High
    Actual Low   TP    FP    FP
           Med   FN    TP    FP
           High  FN    FN    TP
    
    Metrics Calculated:
    - Overall Accuracy = (TP_low + TP_med + TP_high) / Total
    - Per-class Precision = TP / (TP + FP)
    - Per-class Recall = TP / (TP + FN)
    - Per-class F1-Score = 2 * (Precision * Recall) / (Precision + Recall)
    
    Same for Time predictions


┌─────────────────────────────────────────────────────────────────────────┐
│                    KEY IMPROVEMENTS SUMMARY                             │
└─────────────────────────────────────────────────────────────────────────┘

✅ Predictions stored at homeowner request stage (layout_requests)
✅ Predictions copied through workflow (estimate → project)
✅ 3-class evaluation (Low/Medium/High) with proper thresholds
✅ Minimum 150-200 projects for retraining
✅ Complete feature set extraction for retraining
✅ Model version tracking for all predictions
✅ Immutable predictions (locked when work begins)
✅ Full audit trail for all operations
✅ Application-level logic (no complex triggers)
✅ Proper confusion matrix and metrics


┌─────────────────────────────────────────────────────────────────────────┐
│                    DATA FLOW SUMMARY                                    │
└─────────────────────────────────────────────────────────────────────────┘

layout_requests
    ↓ (copy)
contractor_send_estimates
    ↓ (copy)
construction_projects
    ↓ (evaluate)
ai_evaluation_metrics
    ↓ (retrain)
New ML Models
    ↓ (predict)
layout_requests (cycle continues)
```

## Database Tables Involved

### 1. layout_requests (PRIMARY STORAGE)
- Stores predictions at homeowner request stage
- 8 new prediction columns
- Source of truth for initial predictions

### 2. contractor_send_estimates (ESTIMATE STORAGE)
- Receives predictions when contractor creates estimate
- 9 new prediction columns (includes layout_request_id FK)
- Links predictions to estimate

### 3. construction_projects (PROJECT STORAGE)
- Receives predictions when project is created
- Includes evaluation fields
- Predictions locked when work begins

### 4. ai_evaluation_config (CONFIGURATION)
- Stores thresholds for 3-class classification
- Stores current model version
- Stores retraining parameters

### 5. ai_evaluation_metrics (METRICS)
- Stores aggregated evaluation metrics
- Per-class accuracy, precision, recall, F1
- Historical performance tracking

### 6. ai_prediction_audit (AUDIT TRAIL)
- Logs all prediction events
- Logs evaluation events
- Logs retraining events

## API Endpoints

### 1. Generate Predictions
`POST /backend/api/ml/predict_construction_risks.php`
- Input: Form data
- Output: Predictions with probabilities

### 2. Save to Layout Request (NEW)
`POST /backend/api/ml/save_layout_request_prediction.php`
- Input: layout_request_id + predictions
- Output: Success confirmation

### 3. Copy to Estimate (NEW)
`POST /backend/api/ml/copy_predictions_to_estimate.php`
- Input: estimate_id + layout_request_id
- Output: Success confirmation

### 4. Evaluate Project
`CALL evaluate_project_predictions_3class(project_id)`
- Input: project_id
- Output: Evaluation results in database

## Stored Procedures

### 1. determine_ground_truth_3class
- Calculates actual overrun percentages
- Classifies as Low/Medium/High
- Updates ground truth labels

### 2. classify_predictions_3class
- Compares predicted vs actual
- Records correctness (exact match)
- Updates evaluation fields

### 3. evaluate_project_predictions_3class
- Master procedure
- Calls both above procedures
- Updates metrics

## Model Files

### Current Models
- `backend/ml/models/cost_overrun_risk_model.pkl`
- `backend/ml/models/time_delay_risk_model.pkl`

### Versioned Models
- `backend/ml/models/cost_overrun_risk_model_v20260312_143022.pkl`
- `backend/ml/models/time_delay_risk_model_v20260312_143022.pkl`

## Configuration

### Thresholds (Configurable)
```sql
cost_medium_threshold = 5.0%
cost_high_threshold = 15.0%
time_medium_threshold = 5.0%
time_high_threshold = 15.0%
min_projects_for_retraining = 150
```

### Model Version
```sql
current_model_version = 'v20260312_143022'
```

---

**This workflow ensures**:
- Predictions are stored at the correct stage
- Data flows correctly through the system
- Evaluations are accurate (3-class)
- Models improve over time
- Full traceability and auditability
