# AI Self-Evaluation Framework - Complete Documentation

## 📋 Table of Contents
1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Database Schema](#database-schema)
4. [Workflow](#workflow)
5. [API Reference](#api-reference)
6. [Installation Guide](#installation-guide)
7. [Testing](#testing)
8. [Configuration](#configuration)
9. [Metrics Explained](#metrics-explained)
10. [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

### What Is This?

The AI Self-Evaluation Framework is an **additive, backward-compatible enhancement** that converts the existing cost and time overrun prediction system into a **self-evaluating AI system**. It automatically measures real-world prediction accuracy using confusion matrix classification and standard ML metrics.

### Key Features

✅ **100% Backward Compatible**
- All new fields are nullable
- Existing projects work without changes
- No breaking changes to any existing functionality

✅ **Immutable Predictions**
- Predictions are saved permanently when project is confirmed
- Automatically locked when work begins
- Cannot be modified after execution starts

✅ **Automatic Evaluation**
- Evaluates predictions when project completes
- Calculates ground truth based on configurable thresholds
- Classifies predictions using confusion matrix (TP, FP, TN, FN)

✅ **Real-World Metrics**
- Accuracy, Precision, Recall, F1 Score
- Calculated from actual completed projects
- Historical tracking over time

✅ **Complete Audit Trail**
- All prediction saves logged
- Lock events recorded
- Evaluation results tracked

---

## 🏗️ System Architecture

### High-Level Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    1. PREDICTION PHASE                      │
│  Homeowner confirms project → AI prediction saved           │
│  • Cost risk level + probability                            │
│  • Time risk level + probability                            │
│  • Model version recorded                                   │
│  • Timestamp captured                                       │
└────────────────────────┬────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│                    2. LOCK PHASE                            │
│  Work begins → Predictions locked automatically             │
│  • actual_start_date set                                    │
│  • predictions_locked = 1                                   │
│  • Cannot modify predictions anymore                        │
│  • Lock event logged in audit                               │
└────────────────────────┬────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│                    3. EXECUTION PHASE                       │
│  Project progresses → Actual data collected                 │
│  • Stage payments tracked                                   │
│  • Custom payments tracked                                  │
│  • Schedule dates recorded                                  │
│  • Progress monitored                                       │
└────────────────────────┬────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│                    4. EVALUATION PHASE                      │
│  Project completes → Automatic evaluation triggered         │
│  • Calculate actual cost overrun %                          │
│  • Calculate actual time overrun %                          │
│  • Determine ground truth (Overrun / No_Overrun)           │
│  • Classify prediction (TP / FP / TN / FN)                 │
│  • Mark correctness (1 = correct, 0 = wrong)               │
│  • Log evaluation event                                     │
└────────────────────────┬────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────┐
│                    5. AGGREGATION PHASE                     │
│  Calculate metrics across all completed projects            │
│  • Count TP, FP, TN, FN                                     │
│  • Calculate Accuracy, Precision, Recall, F1                │
│  • Store in metrics table                                   │
│  • Available via API                                        │
└─────────────────────────────────────────────────────────────┘
```

### Components

1. **Database Schema** (`ai_self_evaluation_schema.sql`)
   - New fields in `construction_projects` table
   - Configuration table for thresholds
   - Metrics tracking table
   - Audit log table

2. **Stored Procedures** (`ai_evaluation_procedures.sql`)
   - `save_ai_prediction()` - Save predictions
   - `calculate_actual_cost_overrun()` - Calculate cost overrun
   - `classify_ground_truth()` - Determine actual outcome
   - `classify_prediction()` - Confusion matrix classification
   - `evaluate_project()` - Complete evaluation workflow
   - `calculate_aggregate_metrics()` - Calculate system-wide metrics

3. **Triggers**
   - `lock_predictions_on_work_start` - Auto-lock when work begins
   - `auto_evaluate_on_completion` - Auto-evaluate when completed

4. **API Endpoints**
   - `save_ai_prediction.php` - Save prediction results
   - `get_evaluation_metrics.php` - Retrieve metrics and performance data

5. **Views**
   - `v_ai_prediction_performance` - Individual project performance
   - `v_latest_evaluation_metrics` - Latest system metrics
   - `v_confusion_matrix_summary` - Confusion matrix counts

---

## 💾 Database Schema

### New Fields in `construction_projects`

```sql
-- Prediction Storage (saved at project confirmation)
predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL
predicted_cost_probability DECIMAL(5,4) NULL
predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL
predicted_time_probability DECIMAL(5,4) NULL
prediction_generated_at TIMESTAMP NULL
model_version VARCHAR(50) NULL

-- Actual Outcomes (calculated at completion)
actual_cost_overrun_percentage DECIMAL(10,2) NULL
-- actual_time_overrun_percentage already exists

-- Ground Truth Classification
cost_ground_truth_label ENUM('Overrun', 'No_Overrun') NULL
time_ground_truth_label ENUM('Overrun', 'No_Overrun') NULL

-- Confusion Matrix Classification
cost_prediction_classification ENUM('TP', 'FP', 'TN', 'FN') NULL
time_prediction_classification ENUM('TP', 'FP', 'TN', 'FN') NULL

-- Correctness Flags
cost_prediction_correct TINYINT(1) NULL  -- 1=correct, 0=wrong
time_prediction_correct TINYINT(1) NULL  -- 1=correct, 0=wrong

-- Metadata
evaluation_completed_at TIMESTAMP NULL
predictions_locked TINYINT(1) DEFAULT 0
```

### New Tables

#### `ai_evaluation_config`
```sql
CREATE TABLE ai_evaluation_config (
  id INT PRIMARY KEY AUTO_INCREMENT,
  config_key VARCHAR(100) UNIQUE NOT NULL,
  config_value VARCHAR(255) NOT NULL,
  description TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Default Configuration:**
- `cost_overrun_threshold`: 5.0 (%)
- `time_overrun_threshold`: 5.0 (%)
- `high_risk_threshold`: 0.70 (70%)
- `medium_risk_threshold`: 0.40 (40%)
- `current_model_version`: v1.0.0
- `auto_evaluation_enabled`: 1 (yes)

#### `ai_evaluation_metrics`
```sql
CREATE TABLE ai_evaluation_metrics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  evaluation_date DATE NOT NULL,
  metric_type ENUM('cost', 'time') NOT NULL,
  
  -- Confusion Matrix
  true_positives INT DEFAULT 0,
  false_positives INT DEFAULT 0,
  true_negatives INT DEFAULT 0,
  false_negatives INT DEFAULT 0,
  
  -- Calculated Metrics
  accuracy DECIMAL(5,4) NULL,
  precision_score DECIMAL(5,4) NULL,
  recall_score DECIMAL(5,4) NULL,
  f1_score DECIMAL(5,4) NULL,
  
  -- Sample Size
  total_projects INT DEFAULT 0,
  evaluated_projects INT DEFAULT 0,
  
  -- Metadata
  model_version VARCHAR(50),
  threshold_used DECIMAL(5,2),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### `ai_prediction_audit`
```sql
CREATE TABLE ai_prediction_audit (
  id INT PRIMARY KEY AUTO_INCREMENT,
  project_id INT NOT NULL,
  event_type ENUM('prediction_saved', 'prediction_locked', 'evaluation_completed'),
  event_data JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔄 Workflow

### 1. Save Prediction (Project Confirmation)

**When:** Homeowner confirms project submission after viewing AI risk assessment

**API Call:**
```javascript
POST /backend/api/ml/save_ai_prediction.php
{
  "project_id": 1,
  "cost_risk_level": "High",
  "cost_probability": 0.8500,
  "time_risk_level": "Medium",
  "time_probability": 0.6200,
  "model_version": "v1.0.0"
}
```

**What Happens:**
1. Validates project exists and predictions not locked
2. Calls `save_ai_prediction()` stored procedure
3. Saves prediction data to `construction_projects`
4. Logs event to `ai_prediction_audit`
5. Returns success confirmation

**Database State:**
```sql
predicted_cost_risk_level = 'High'
predicted_cost_probability = 0.8500
predicted_time_risk_level = 'Medium'
predicted_time_probability = 0.6200
prediction_generated_at = NOW()
model_version = 'v1.0.0'
predictions_locked = 0  -- Not locked yet
```

### 2. Lock Predictions (Work Begins)

**When:** Contractor records `actual_start_date`

**Trigger:** `lock_predictions_on_work_start`

**What Happens:**
1. Detects `actual_start_date` being set for first time
2. Sets `predictions_locked = 1`
3. Prevents any future modification of prediction fields
4. Logs lock event to audit table

**Database State:**
```sql
actual_start_date = '2026-02-05'
predictions_locked = 1  -- LOCKED
-- All prediction fields now immutable
```

**Important:** Once locked, predictions cannot be changed by anyone, including admins.

### 3. Project Completion & Evaluation

**When:** Project status changes to 'completed'

**Trigger:** `auto_evaluate_on_completion`

**What Happens:**

#### Step 1: Calculate Actual Cost Overrun
```sql
CALL calculate_actual_cost_overrun(project_id);
```
- Sums all stage payments
- Sums all custom payments
- Calculates: `((total - estimate) / estimate) * 100`
- Stores in `actual_cost_overrun_percentage`

#### Step 2: Classify Ground Truth
```sql
CALL classify_ground_truth(project_id);
```
- Compares actual overrun to threshold (default 5%)
- Cost: `actual_cost_overrun_percentage > 5%` → 'Overrun', else 'No_Overrun'
- Time: `actual_time_overrun_percentage > 5%` → 'Overrun', else 'No_Overrun'

#### Step 3: Classify Prediction (Confusion Matrix)
```sql
CALL classify_prediction(project_id);
```

**Classification Logic:**

For Cost Predictions:
```
IF predicted_cost_risk_level IN ('High', 'Medium'):
  Prediction = POSITIVE
ELSE (predicted_cost_risk_level = 'Low'):
  Prediction = NEGATIVE

IF cost_ground_truth_label = 'Overrun':
  Actual = POSITIVE
ELSE (cost_ground_truth_label = 'No_Overrun'):
  Actual = NEGATIVE

Classification:
  Predicted POSITIVE + Actual POSITIVE = TP (True Positive)
  Predicted POSITIVE + Actual NEGATIVE = FP (False Positive)
  Predicted NEGATIVE + Actual NEGATIVE = TN (True Negative)
  Predicted NEGATIVE + Actual POSITIVE = FN (False Negative)
```

Same logic applies for Time Predictions.

#### Step 4: Mark Evaluation Complete
```sql
evaluation_completed_at = NOW()
```

**Final Database State:**
```sql
-- Predictions (immutable)
predicted_cost_risk_level = 'High'
predicted_time_risk_level = 'Medium'

-- Actuals (calculated)
actual_cost_overrun_percentage = 8.0
actual_time_overrun_percentage = 11.11

-- Ground Truth (classified)
cost_ground_truth_label = 'Overrun'  -- 8% > 5% threshold
time_ground_truth_label = 'Overrun'  -- 11.11% > 5% threshold

-- Confusion Matrix Classification
cost_prediction_classification = 'TP'  -- Predicted High, Actual Overrun
time_prediction_classification = 'TP'  -- Predicted Medium, Actual Overrun

-- Correctness
cost_prediction_correct = 1  -- TP = correct
time_prediction_correct = 1  -- TP = correct

-- Metadata
evaluation_completed_at = '2026-05-15 14:30:00'
predictions_locked = 1
```

### 4. Calculate Aggregate Metrics

**When:** Called manually or scheduled (e.g., daily)

**API Call:**
```javascript
GET /backend/api/ml/get_evaluation_metrics.php?action=calculate
```

**What Happens:**
```sql
CALL calculate_aggregate_metrics();
```

1. Counts all TP, FP, TN, FN for cost predictions
2. Counts all TP, FP, TN, FN for time predictions
3. Calculates metrics:
   - **Accuracy** = (TP + TN) / (TP + FP + TN + FN)
   - **Precision** = TP / (TP + FP)
   - **Recall** = TP / (TP + FN)
   - **F1 Score** = 2 × (Precision × Recall) / (Precision + Recall)
4. Stores results in `ai_evaluation_metrics` table
5. Returns calculated metrics

**Example Output:**
```json
{
  "metric_type": "cost",
  "TP": 45,
  "FP": 8,
  "TN": 32,
  "FN": 5,
  "accuracy": 0.8556,  // 85.56%
  "precision": 0.8491,  // 84.91%
  "recall": 0.9000,     // 90.00%
  "f1_score": 0.8739    // 87.39%
}
```

---

### New Tables

#### 1. `ai_evaluation_config`
Stores system-wide configuration including thresholds:

```sql
CREATE TABLE ai_evaluation_config (
  id INT PRIMARY KEY AUTO_INCREMENT,
  config_key VARCHAR(100) UNIQUE NOT NULL,
  config_value VARCHAR(255) NOT NULL,
  description TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default values:
cost_overrun_threshold = 5.0%
time_overrun_threshold = 5.0%
model_version = v1.0.0
evaluation_enabled = 1
```

#### 2. `ai_evaluation_metrics`
Stores aggregated performance metrics:

```sql
CREATE TABLE ai_evaluation_metrics (
  id INT PRIMARY KEY AUTO_INCREMENT,
  metric_type ENUM('cost', 'time') NOT NULL,
  evaluation_date DATE NOT NULL,
  total_projects INT NOT NULL,
  
  -- Confusion Matrix
  true_positives INT NOT NULL,
  false_positives INT NOT NULL,
  true_negatives INT NOT NULL,
  false_negatives INT NOT NULL,
  
  -- Performance Metrics
  accuracy DECIMAL(5,2) NULL,
  precision_score DECIMAL(5,2) NULL,
  recall_score DECIMAL(5,2) NULL,
  f1_score DECIMAL(5,2) NULL,
  specificity DECIMAL(5,2) NULL,
  false_positive_rate DECIMAL(5,2) NULL
);
```

#### 3. `ai_prediction_audit`
Complete audit trail of all prediction activities:

```sql
CREATE TABLE ai_prediction_audit (
  id INT PRIMARY KEY AUTO_INCREMENT,
  project_id INT NOT NULL,
  action_type ENUM('prediction_saved', 'prediction_locked', 'evaluation_completed'),
  cost_risk_level VARCHAR(20),
  cost_probability DECIMAL(5,2),
  time_risk_level VARCHAR(20),
  time_probability DECIMAL(5,2),
  cost_classification VARCHAR(10),
  time_classification VARCHAR(10),
  model_version VARCHAR(50),
  performed_by INT,
  performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🔄 Workflow Details

### Phase 1: Prediction Storage (Project Confirmation)

**When:** After homeowner submits custom request and AI generates predictions

**API Endpoint:** `POST /backend/api/ml/save_ai_predictions.php`

**Request:**
```json
{
  "project_id": 123,
  "cost_risk_level": "High",
  "cost_probability": 95.5,
  "time_risk_level": "Low",
  "time_probability": 15.2
}
```

**What Happens:**
1. Validates project exists and user has permission
2. Checks if predictions are already locked
3. Retrieves current model version
4. Saves predictions to database
5. Logs to audit trail
6. Returns success confirmation

**Important:** Predictions can be updated ONLY before project starts

---

### Phase 2: Prediction Locking (Project Start)

**When:** Contractor records `actual_start_date`

**Trigger:** `lock_predictions_on_start` (automatic)

**What Happens:**
```sql
-- Automatically executed by database trigger
IF actual_start_date IS SET FOR FIRST TIME THEN
  SET predictions_locked = 1
  PREVENT any changes to prediction fields
END IF
```

**Result:** Predictions become **immutable** - cannot be modified

**Audit Log:** Action type = 'prediction_locked'

---

### Phase 3: Automatic Evaluation (Project Completion)

**When:** Project status changes to 'completed'

**Trigger:** `auto_evaluate_on_completion` (automatic)

**Stored Procedure:** `evaluate_project_predictions(project_id)`

**Evaluation Steps:**

#### Step 1: Calculate Actual Cost Overrun
```sql
CALL calculate_actual_cost_overrun(project_id)

-- Formula:
actual_cost_overrun_percentage = 
  ((total_stage_payments + total_custom_payments - original_estimate) 
   / original_estimate) × 100
```

#### Step 2: Determine Ground Truth Labels
```sql
CALL determine_ground_truth_labels(project_id)

-- Logic:
IF actual_cost_overrun_percentage >= cost_threshold (5%) THEN
  cost_ground_truth_label = 'High'
ELSE
  cost_ground_truth_label = 'Low'
END IF

IF actual_time_overrun_percentage >= time_threshold (5%) THEN
  time_ground_truth_label = 'High'
ELSE
  time_ground_truth_label = 'Low'
END IF
```

#### Step 3: Classify Predictions (Confusion Matrix)
```sql
CALL classify_predictions(project_id)

-- Cost Classification Logic:
predicted_risk = predicted_cost_risk_level (Medium → High for binary)
actual_risk = cost_ground_truth_label

IF predicted_risk = 'High' AND actual_risk = 'High' THEN
  classification = 'TP' (True Positive)
  correct = 1
ELSEIF predicted_risk = 'High' AND actual_risk = 'Low' THEN
  classification = 'FP' (False Positive)
  correct = 0
ELSEIF predicted_risk = 'Low' AND actual_risk = 'Low' THEN
  classification = 'TN' (True Negative)
  correct = 1
ELSEIF predicted_risk = 'Low' AND actual_risk = 'High' THEN
  classification = 'FN' (False Negative)
  correct = 0
END IF

-- Same logic applies for time predictions
```

#### Step 4: Update Aggregated Metrics
```sql
CALL update_aggregated_metrics()

-- Calculates for all completed projects:
Accuracy = (TP + TN) / Total
Precision = TP / (TP + FP)
Recall = TP / (TP + FN)
F1 Score = 2 × (Precision × Recall) / (Precision + Recall)
Specificity = TN / (TN + FP)
False Positive Rate = FP / (FP + TN)
```

---

## 📈 Performance Metrics Explained

### Confusion Matrix

```
                    Actual Outcome
                 Low          High
Predicted  Low   TN           FN
           High  FP           TP

TN = True Negative  - Correctly predicted Low risk
TP = True Positive  - Correctly predicted High risk
FP = False Positive - Predicted High but was Low (over-cautious)
FN = False Negative - Predicted Low but was High (missed risk)
```

### Metric Definitions

**Accuracy**
- Formula: `(TP + TN) / Total`
- Meaning: Overall percentage of correct predictions
- Good: >80%

**Precision**
- Formula: `TP / (TP + FP)`
- Meaning: Of all High risk predictions, how many were actually High
- Good: >70%
- Low precision = Too many false alarms

**Recall (Sensitivity)**
- Formula: `TP / (TP + FN)`
- Meaning: Of all actual High risk projects, how many were predicted as High
- Good: >70%
- Low recall = Missing risky projects

**F1 Score**
- Formula: `2 × (Precision × Recall) / (Precision + Recall)`
- Meaning: Balanced measure of precision and recall
- Excellent: >90%, Good: >80%, Fair: >70%

**Specificity**
- Formula: `TN / (TN + FP)`
- Meaning: Of all actual Low risk projects, how many were predicted as Low
- Good: >80%

**False Positive Rate**
- Formula: `FP / (FP + TN)`
- Meaning: Percentage of Low risk projects incorrectly flagged as High
- Good: <20%

---

## 🔌 API Reference

### 1. Save AI Predictions

**Endpoint:** `POST /backend/api/ml/save_ai_predictions.php`

**Authentication:** Required (homeowner or admin)

**Request:**
```json
{
  "project_id": 123,
  "cost_risk_level": "High",
  "cost_probability": 95.5,
  "time_risk_level": "Low",
  "time_probability": 15.2
}
```

**Response:**
```json
{
  "success": true,
  "message": "AI predictions saved successfully",
  "data": {
    "project_id": 123,
    "cost_risk_level": "High",
    "cost_probability": 95.5,
    "time_risk_level": "Low",
    "time_probability": 15.2,
    "model_version": "v1.0.0",
    "prediction_generated_at": "2026-02-16 10:30:00",
    "predictions_locked": false
  }
}
```

**Error Cases:**
- 401: Not authenticated
- 403: No permission for this project
- 400: Predictions already locked
- 400: Invalid risk level


## 🔌 API Reference

### 1. Save AI Prediction

**Endpoint:** `POST /backend/api/ml/save_ai_prediction.php`

**Purpose:** Store AI prediction results when homeowner confirms project

**Request Body:**
```json
{
  "project_id": 1,
  "cost_risk_level": "High",
  "cost_probability": 0.8500,
  "time_risk_level": "Medium",
  "time_probability": 0.6200,
  "model_version": "v1.0.0"
}
```

**Validation:**
- `project_id`: Must be valid project ID
- `cost_risk_level`: Must be 'Low', 'Medium', or 'High'
- `cost_probability`: Must be between 0 and 1
- `time_risk_level`: Must be 'Low', 'Medium', or 'High'
- `time_probability`: Must be between 0 and 1
- `model_version`: Optional, defaults to 'v1.0.0'

**Success Response (200):**
```json
{
  "success": true,
  "message": "AI prediction saved successfully",
  "data": {
    "project_id": 1,
    "cost_risk_level": "High",
    "cost_probability": 0.85,
    "time_risk_level": "Medium",
    "time_probability": 0.62,
    "model_version": "v1.0.0",
    "saved_at": "2026-02-16 10:30:00",
    "predictions_locked": false
  }
}
```

**Error Responses:**

400 - Missing Field:
```json
{
  "success": false,
  "error": "Missing required field: cost_risk_level"
}
```

403 - Predictions Locked:
```json
{
  "success": false,
  "error": "Predictions are locked and cannot be modified. Work has already begun on this project."
}
```

404 - Project Not Found:
```json
{
  "success": false,
  "error": "Project not found"
}
```

---

### 2. Get Evaluation Metrics

**Endpoint:** `GET /backend/api/ml/get_evaluation_metrics.php`

**Purpose:** Retrieve AI evaluation metrics and performance data

#### Action: latest

Get latest evaluation metrics for cost and time predictions.

**Request:**
```
GET /backend/api/ml/get_evaluation_metrics.php?action=latest
```

**Optional Parameters:**
- `metric_type`: 'cost', 'time', or 'both' (default: 'both')

**Response:**
```json
{
  "success": true,
  "data": {
    "metrics": [
      {
        "metric_type": "cost",
        "TP": 45,
        "FP": 8,
        "TN": 32,
        "FN": 5,
        "accuracy_pct": 85.56,
        "precision_pct": 84.91,
        "recall_pct": 90.00,
        "f1_score_pct": 87.39,
        "total_projects": 100,
        "evaluated_projects": 90,
        "model_version": "v1.0.0",
        "threshold_used": 5.0,
        "evaluation_date": "2026-02-16"
      },
      {
        "metric_type": "time",
        "TP": 52,
        "FP": 5,
        "TN": 28,
        "FN": 5,
        "accuracy_pct": 88.89,
        "precision_pct": 91.23,
        "recall_pct": 91.23,
        "f1_score_pct": 91.23,
        "total_projects": 100,
        "evaluated_projects": 90,
        "model_version": "v1.0.0",
        "threshold_used": 5.0,
        "evaluation_date": "2026-02-16"
      }
    ]
  }
}
```

#### Action: confusion_matrix

Get confusion matrix summary for both cost and time predictions.

**Request:**
```
GET /backend/api/ml/get_evaluation_metrics.php?action=confusion_matrix
```

**Response:**
```json
{
  "success": true,
  "data": {
    "confusion_matrices": [
      {
        "prediction_type": "Cost Predictions",
        "true_positives": 45,
        "false_positives": 8,
        "true_negatives": 32,
        "false_negatives": 5,
        "total_evaluated": 90
      },
      {
        "prediction_type": "Time Predictions",
        "true_positives": 52,
        "false_positives": 5,
        "true_negatives": 28,
        "false_negatives": 5,
        "total_evaluated": 90
      }
    ]
  }
}
```

#### Action: calculate

Trigger calculation of aggregate metrics across all completed projects.

**Request:**
```
GET /backend/api/ml/get_evaluation_metrics.php?action=calculate
```

**Response:**
```json
{
  "success": true,
  "message": "Metrics calculated successfully",
  "data": {
    "calculated_metrics": [
      {
        "metric_type": "cost",
        "TP": 45,
        "FP": 8,
        "TN": 32,
        "FN": 5,
        "accuracy": 0.8556,
        "precision_val": 0.8491,
        "recall_val": 0.9000,
        "f1_score": 0.8739
      },
      {
        "metric_type": "time",
        "TP": 52,
        "FP": 5,
        "TN": 28,
        "FN": 5,
        "accuracy": 0.8889,
        "precision_val": 0.9123,
        "recall_val": 0.9123,
        "f1_score": 0.9123
      }
    ]
  }
}
```

#### Action: history

Get historical metrics over time.

**Request:**
```
GET /backend/api/ml/get_evaluation_metrics.php?action=history&start_date=2026-01-01&end_date=2026-02-16
```

**Optional Parameters:**
- `metric_type`: 'cost', 'time', or 'both'
- `start_date`: Start date (YYYY-MM-DD)
- `end_date`: End date (YYYY-MM-DD)

**Response:**
```json
{
  "success": true,
  "data": {
    "history": [
      {
        "evaluation_date": "2026-02-16",
        "metric_type": "cost",
        "true_positives": 45,
        "false_positives": 8,
        "true_negatives": 32,
        "false_negatives": 5,
        "accuracy_pct": 85.56,
        "precision_pct": 84.91,
        "recall_pct": 90.00,
        "f1_score_pct": 87.39,
        "total_projects": 100,
        "evaluated_projects": 90,
        "model_version": "v1.0.0",
        "threshold_used": 5.0
      }
      // ... more historical records
    ]
  }
}
```

#### Action: project_performance

Get individual project performance data.

**Request:**
```
GET /backend/api/ml/get_evaluation_metrics.php?action=project_performance&project_id=1
```

**Optional Parameters:**
- `project_id`: Specific project ID
- `status`: Filter by status ('completed', 'in_progress', etc.)
- `correct_only`: 'true' to show only correct predictions
- `incorrect_only`: 'true' to show only incorrect predictions

**Response:**
```json
{
  "success": true,
  "data": {
    "projects": [
      {
        "project_id": 1,
        "project_name": "Modern Villa",
        "status": "completed",
        "predicted_cost_risk_level": "High",
        "predicted_cost_probability": 0.8500,
        "predicted_time_risk_level": "Medium",
        "predicted_time_probability": 0.6200,
        "prediction_generated_at": "2026-01-15 10:30:00",
        "model_version": "v1.0.0",
        "actual_cost_overrun_percentage": 8.00,
        "actual_time_overrun_percentage": 11.11,
        "cost_ground_truth_label": "Overrun",
        "time_ground_truth_label": "Overrun",
        "cost_prediction_classification": "TP",
        "time_prediction_classification": "TP",
        "cost_prediction_correct": 1,
        "time_prediction_correct": 1,
        "evaluation_completed_at": "2026-05-15 14:30:00",
        "predictions_locked": 1,
        "cost_prediction_status": "Correct",
        "time_prediction_status": "Correct"
      }
    ],
    "count": 1
  }
}
```

#### Action: config

Get current system configuration.

**Request:**
```
GET /backend/api/ml/get_evaluation_metrics.php?action=config
```

**Response:**
```json
{
  "success": true,
  "data": {
    "config": {
      "cost_overrun_threshold": {
        "value": "5.0",
        "description": "Threshold percentage to classify cost overrun (default: 5%)",
        "updated_at": "2026-02-16 10:00:00"
      },
      "time_overrun_threshold": {
        "value": "5.0",
        "description": "Threshold percentage to classify time overrun (default: 5%)",
        "updated_at": "2026-02-16 10:00:00"
      },
      "current_model_version": {
        "value": "v1.0.0",
        "description": "Current ML model version identifier",
        "updated_at": "2026-02-16 10:00:00"
      },
      "auto_evaluation_enabled": {
        "value": "1",
        "description": "Enable automatic evaluation on project completion (1=yes, 0=no)",
        "updated_at": "2026-02-16 10:00:00"
      }
    }
  }
}
```

---

## 📥 Installation Guide

### Prerequisites

- MySQL 5.7+ or MariaDB 10.2+
- PHP 7.4+
- Existing BuildHub system with cost/time overrun tracking

### Step 1: Backup Database

```bash
mysqldump -u root -p buildhub > buildhub_backup_$(date +%Y%m%d).sql
```

### Step 2: Run Migration

```bash
php apply_ai_self_evaluation_migration.php
```

**Expected Output:**
```
=================================================================
AI Self-Evaluation Framework Migration
=================================================================

✓ Database connection established

Step 1: Applying schema changes...
  ✓ Executed 25 statements, skipped 0 existing items

Step 2: Installing stored procedures and triggers...
  ✓ Stored procedures and triggers installed

Step 3: Verifying installation...
  ✓ Column 'predicted_cost_risk_level' exists
  ✓ Column 'predicted_time_risk_level' exists
  ✓ Column 'cost_ground_truth_label' exists
  ✓ Column 'cost_prediction_classification' exists
  ✓ Column 'evaluation_completed_at' exists
  ✓ Table 'ai_evaluation_config' exists
  ✓ Table 'ai_evaluation_metrics' exists
  ✓ Table 'ai_prediction_audit' exists
  ✓ Procedure 'save_ai_prediction' exists
  ✓ Procedure 'evaluate_project' exists
  ✓ Procedure 'calculate_aggregate_metrics' exists
  ✓ View 'v_ai_prediction_performance' exists
  ✓ View 'v_latest_evaluation_metrics' exists
  ✓ View 'v_confusion_matrix_summary' exists

Step 4: Current configuration:
  • cost_overrun_threshold: 5.0
    (Threshold percentage to classify cost overrun (default: 5%))
  • time_overrun_threshold: 5.0
    (Threshold percentage to classify time overrun (default: 5%))
  • current_model_version: v1.0.0
    (Current ML model version identifier)
  • auto_evaluation_enabled: 1
    (Enable automatic evaluation on project completion (1=yes, 0=no))

=================================================================
✓ MIGRATION COMPLETED SUCCESSFULLY
=================================================================
```

### Step 3: Test Installation

```bash
php test_ai_self_evaluation.php
```

### Step 4: Update Frontend

Modify the risk assessment component to save predictions:

```javascript
// In RiskAssessmentPreview.jsx or similar component

async function handleProjectConfirmation() {
  // After user confirms project submission
  
  // Save AI prediction
  const predictionData = {
    project_id: projectId,
    cost_risk_level: costRisk.risk_level,
    cost_probability: costRisk.probabilities.High,
    time_risk_level: timeRisk.risk_level,
    time_probability: timeRisk.probabilities.High,
    model_version: 'v1.0.0'
  };
  
  try {
    const response = await fetch('/backend/api/ml/save_ai_prediction.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(predictionData)
    });
    
    const result = await response.json();
    
    if (result.success) {
      console.log('Prediction saved successfully');
      // Continue with project submission
    }
  } catch (error) {
    console.error('Error saving prediction:', error);
    // Still allow project submission even if prediction save fails
  }
}
```

### Step 5: Verify Automatic Evaluation

1. Complete an existing project
2. Check if evaluation was triggered:

```sql
SELECT 
  id,
  project_name,
  evaluation_completed_at,
  cost_prediction_classification,
  time_prediction_classification
FROM construction_projects
WHERE status = 'completed'
AND evaluation_completed_at IS NOT NULL
ORDER BY evaluation_completed_at DESC
LIMIT 5;
```

---

## 🧪 Testing

### Manual Testing

1. **Open Test Interface:**
   ```
   http://localhost/buildhub/test_ai_self_evaluation_system.html
   ```

2. **Test Prediction Saving:**
   - Enter project ID
   - Select risk levels
   - Enter probabilities
   - Click "Save Prediction"
   - Verify success response

3. **Test Metrics Retrieval:**
   - Select "Latest Metrics" action
   - Click "Get Metrics"
   - Verify metrics display

4. **Test Confusion Matrix:**
   - Click "Load Confusion Matrix"
   - Verify TP, FP, TN, FN counts

### Automated Testing

```bash
php test_ai_self_evaluation.php
```

**Tests Performed:**
1. Create test project
2. Save AI prediction
3. Modify prediction (before lock)
4. Start work (lock predictions)
5. Attempt to modify locked prediction (should fail)
6. Add payment data
7. Add schedule data
8. Complete project (trigger evaluation)
9. Calculate aggregate metrics
10. View prediction performance
11. Check audit log
12. Cleanup

### SQL Testing Queries

```sql
-- Test 1: Check if predictions are being saved
SELECT 
  id,
  project_name,
  predicted_cost_risk_level,
  predicted_time_risk_level,
  predictions_locked
FROM construction_projects
WHERE predicted_cost_risk_level IS NOT NULL
LIMIT 10;

-- Test 2: Check if evaluations are running
SELECT 
  id,
  project_name,
  evaluation_completed_at,
  cost_prediction_correct,
  time_prediction_correct
FROM construction_projects
WHERE evaluation_completed_at IS NOT NULL
LIMIT 10;

-- Test 3: View confusion matrix
SELECT * FROM v_confusion_matrix_summary;

-- Test 4: View latest metrics
SELECT * FROM v_latest_evaluation_metrics;

-- Test 5: Check audit log
SELECT 
  project_id,
  event_type,
  created_at
FROM ai_prediction_audit
ORDER BY created_at DESC
LIMIT 20;
```

---

## ⚙️ Configuration

### Modify Thresholds

```sql
-- Change cost overrun threshold to 10%
UPDATE ai_evaluation_config
SET config_value = '10.0'
WHERE config_key = 'cost_overrun_threshold';

-- Change time overrun threshold to 8%
UPDATE ai_evaluation_config
SET config_value = '8.0'
WHERE config_key = 'time_overrun_threshold';

-- Recalculate metrics with new thresholds
CALL calculate_aggregate_metrics();
```

### Disable Auto-Evaluation

```sql
UPDATE ai_evaluation_config
SET config_value = '0'
WHERE config_key = 'auto_evaluation_enabled';
```

### Update Model Version

```sql
UPDATE ai_evaluation_config
SET config_value = 'v2.0.0'
WHERE config_key = 'current_model_version';
```

### Manual Evaluation

If auto-evaluation is disabled, evaluate projects manually:

```sql
-- Evaluate specific project
CALL evaluate_project(1);

-- Evaluate all completed projects without evaluation
SELECT id FROM construction_projects
WHERE status = 'completed'
AND evaluation_completed_at IS NULL
AND predicted_cost_risk_level IS NOT NULL;

-- Then call evaluate_project for each
```

---

---

### 2. Get AI Evaluation Metrics

**Endpoint:** `GET /backend/api/ml/get_ai_evaluation_metrics.php`

**Authentication:** Required (admin or contractor)

**Query Parameters:**
- `metric_type`: 'cost', 'time', or 'both' (default: 'both')
- `date_from`: Start date (optional)
- `date_to`: End date (optional)
- `include_breakdown`: true/false (default: true)

**Example Request:**
```http
GET /backend/api/ml/get_ai_evaluation_metrics.php?metric_type=both&include_breakdown=true
```

**Response:**
```json
{
  "success": true,
  "data": {
    "metrics": [
      {
        "metric_type": "cost",
        "evaluation_date": "2026-02-16",
        "total_projects": 50,
        "confusion_matrix": {
          "true_positives": 30,
          "false_positives": 5,
          "true_negatives": 12,
          "false_negatives": 3
        },
        "performance_metrics": {
          "accuracy": 84.0,
          "precision": 85.7,
          "recall": 90.9,
          "f1_score": 88.2,
          "specificity": 70.6,
          "false_positive_rate": 29.4
        }
      },
      {
        "metric_type": "time",
        "evaluation_date": "2026-02-16",
        "total_projects": 50,
        "confusion_matrix": {
          "true_positives": 35,
          "false_positives": 3,
          "true_negatives": 10,
          "false_negatives": 2
        },
        "performance_metrics": {
          "accuracy": 90.0,
          "precision": 92.1,
          "recall": 94.6,
          "f1_score": 93.3,
          "specificity": 76.9,
          "false_positive_rate": 23.1
        }
      }
    ],
    "breakdown": {
      "cost": [
        {"classification": "TP", "count": 30, "percentage": 60.0},
        {"classification": "FP", "count": 5, "percentage": 10.0},
        {"classification": "TN", "count": 12, "percentage": 24.0},
        {"classification": "FN", "count": 3, "percentage": 6.0}
      ],
      "time": [
        {"classification": "TP", "count": 35, "percentage": 70.0},
        {"classification": "FP", "count": 3, "percentage": 6.0},
        {"classification": "TN", "count": 10, "percentage": 20.0},
        {"classification": "FN", "count": 2, "percentage": 4.0}
      ]
    },
    "configuration": {
      "cost_overrun_threshold": {
        "value": "5.0",
        "description": "Threshold percentage to classify cost overrun as High"
      },
      "time_overrun_threshold": {
        "value": "5.0",
        "description": "Threshold percentage to classify time overrun as High"
      },
      "model_version": {
        "value": "v1.0.0",
        "description": "Current ML model version identifier"
      }
    },
    "summary": {
      "total_evaluated_projects": 50,
      "cost_predictions_correct": 42,
      "time_predictions_correct": 45,
      "both_predictions_correct": 40,
      "model_versions_used": 1,
      "first_evaluation_date": "2026-01-15 08:30:00",
      "latest_evaluation_date": "2026-02-16 14:20:00"
    },
    "recent_evaluations": [
      {
        "project_id": 150,
        "project_name": "Modern Villa",
        "cost": {
          "predicted": "High",
          "actual": "High",
          "classification": "TP"
        },
        "time": {
          "predicted": "Low",
          "actual": "Low",
          "classification": "TN"
        },
        "evaluated_at": "2026-02-16 14:20:00"
      }
    ],
    "interpretation": {
      "cost": {
        "overall_quality": "Good",
        "f1_score": 88.2,
        "interpretation": {
          "accuracy": "84.0% of predictions were correct overall",
          "precision": "85.7% of High risk predictions were actually High",
          "recall": "90.9% of actual High risk projects were predicted as High",
          "f1_score": "Balanced score of 88.2% (Good)"
        },
        "recommendations": [
          "Model performance is acceptable - continue monitoring"
        ]
      },
      "time": {
        "overall_quality": "Excellent",
        "f1_score": 93.3,
        "interpretation": {
          "accuracy": "90.0% of predictions were correct overall",
          "precision": "92.1% of High risk predictions were actually High",
          "recall": "94.6% of actual High risk projects were predicted as High",
          "f1_score": "Balanced score of 93.3% (Excellent)"
        },
        "recommendations": [
          "Model performance is excellent - continue monitoring"
        ]
      }
    }
  },
  "metadata": {
    "generated_at": "2026-02-16 15:00:00",
    "metric_type": "both"
  }
}
```

---

### 3. Trigger Manual Evaluation

**Endpoint:** `POST /backend/api/ml/trigger_evaluation.php`

**Authentication:** Required (admin only)

**Request:**
```json
{
  "project_id": 123,  // Optional, omit to evaluate all eligible
  "force": false      // Set true to re-evaluate already evaluated projects
}
```

**Response:**
```json
{
  "success": true,
  "message": "Evaluation process completed",
  "data": {
    "evaluated_count": 1,
    "skipped_count": 0,
    "error_count": 0,
    "evaluated_projects": [
      {
        "project_id": 123,
        "project_name": "Modern Villa",
        "cost_evaluation": {
          "predicted": "High",
          "actual": "High",
          "classification": "TP",
          "correct": true,
          "actual_overrun_pct": 8.5
        },
        "time_evaluation": {
          "predicted": "Low",
          "actual": "Low",
          "classification": "TN",
          "correct": true,
          "actual_overrun_pct": 2.3
        },
        "evaluated_at": "2026-02-16 15:30:00"
      }
    ]
  },
  "metadata": {
    "triggered_by": 1,
    "triggered_at": "2026-02-16 15:30:00",
    "force_mode": false
  }
}
```

---

## 🔍 Database Views

### 1. `v_latest_ai_metrics`
Latest performance metrics for each metric type:

```sql
SELECT * FROM v_latest_ai_metrics;
```

### 2. `v_project_evaluation_summary`
Complete evaluation summary for all projects with predictions:

```sql
SELECT * FROM v_project_evaluation_summary WHERE project_id = 123;
```

### 3. `v_confusion_matrix_breakdown`
Confusion matrix distribution:

```sql
SELECT * FROM v_confusion_matrix_breakdown;
```

---

## 🧪 Testing & Verification

### Installation

```bash
# Apply database schema
mysql -u root -p buildhub < backend/database/ai_self_evaluation_schema.sql
```

### Verification Queries

#### 1. Check Schema Changes
```sql
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'buildhub' 
  AND TABLE_NAME = 'construction_projects'
  AND (COLUMN_NAME LIKE '%predict%' 
       OR COLUMN_NAME LIKE '%ground_truth%' 
       OR COLUMN_NAME LIKE '%evaluation%')
ORDER BY ORDINAL_POSITION;
```

#### 2. Check Configuration
```sql
SELECT * FROM ai_evaluation_config;
```

#### 3. View Current Metrics
```sql
SELECT * FROM v_latest_ai_metrics;
```

#### 4. Check Recent Evaluations
```sql
SELECT 
  project_id,
  project_name,
  predicted_cost_risk_level,
  cost_ground_truth_label,
  cost_prediction_classification,
  cost_prediction_correct,
  evaluation_completed_at
FROM construction_projects
WHERE evaluation_completed_at IS NOT NULL
ORDER BY evaluation_completed_at DESC
LIMIT 10;
```

#### 5. Verify Audit Trail
```sql
SELECT * FROM ai_prediction_audit
ORDER BY performed_at DESC
LIMIT 20;
```

---

## 🎯 Integration with Existing System

### Frontend Integration

#### 1. Save Predictions After AI Analysis

```javascript
// In HomeownerRequestWizard.jsx or similar component

async function handleProjectSubmission(projectData, aiPredictions) {
  // Step 1: Submit project (existing flow)
  const projectResponse = await submitProject(projectData);
  const projectId = projectResponse.project_id;
  
  // Step 2: Save AI predictions (NEW)
  if (aiPredictions) {
    await saveAIPredictions(projectId, aiPredictions);
  }
  
  // Continue with existing flow...
}

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

#### 2. Display Metrics Dashboard

```javascript
// New component: AIMetricsDashboard.jsx

import React, { useState, useEffect } from 'react';

function AIMetricsDashboard() {
  const [metrics, setMetrics] = useState(null);
  
  useEffect(() => {
    fetchMetrics();
  }, []);
  
  async function fetchMetrics() {
    const response = await fetch(
      '/backend/api/ml/get_ai_evaluation_metrics.php?metric_type=both'
    );
    const data = await response.json();
    setMetrics(data.data);
  }
  
  if (!metrics) return <div>Loading...</div>;
  
  return (
    <div className="ai-metrics-dashboard">
      <h2>AI Performance Metrics</h2>
      
      {metrics.metrics.map(metric => (
        <div key={metric.metric_type} className="metric-card">
          <h3>{metric.metric_type.toUpperCase()} Predictions</h3>
          
          <div className="confusion-matrix">
            <h4>Confusion Matrix</h4>
            <table>
              <tr>
                <td>TN: {metric.confusion_matrix.true_negatives}</td>
                <td>FP: {metric.confusion_matrix.false_positives}</td>
              </tr>
              <tr>
                <td>FN: {metric.confusion_matrix.false_negatives}</td>
                <td>TP: {metric.confusion_matrix.true_positives}</td>
              </tr>
            </table>
          </div>
          
          <div className="performance-metrics">
            <h4>Performance</h4>
            <p>Accuracy: {metric.performance_metrics.accuracy}%</p>
            <p>Precision: {metric.performance_metrics.precision}%</p>
            <p>Recall: {metric.performance_metrics.recall}%</p>
            <p>F1 Score: {metric.performance_metrics.f1_score}%</p>
          </div>
        </div>
      ))}
    </div>
  );
}

export default AIMetricsDashboard;
```

---

## ⚙️ Configuration

### Adjust Overrun Thresholds

```sql
-- Change cost overrun threshold to 10%
UPDATE ai_evaluation_config
SET config_value = '10.0'
WHERE config_key = 'cost_overrun_threshold';

-- Change time overrun threshold to 8%
UPDATE ai_evaluation_config
SET config_value = '8.0'
WHERE config_key = 'time_overrun_threshold';
```

### Enable/Disable Automatic Evaluation

```sql
-- Disable automatic evaluation
UPDATE ai_evaluation_config
SET config_value = '0'
WHERE config_key = 'evaluation_enabled';

-- Enable automatic evaluation
UPDATE ai_evaluation_config
SET config_value = '1'
WHERE config_key = 'evaluation_enabled';
```

### Update Model Version

```sql
-- Update model version identifier
UPDATE ai_evaluation_config
SET config_value = 'v2.0.0'
WHERE config_key = 'model_version';
```

---

## 🔒 Security & Data Integrity

### Immutability Guarantees

1. **Prediction Locking**
   - Predictions automatically lock when `actual_start_date` is set
   - Database trigger prevents any modification of prediction fields
   - Audit trail logs locking event

2. **Evaluation Integrity**
   - Ground truth calculated from actual data only
   - Classification logic is deterministic
   - All calculations logged in audit trail

3. **Access Control**
   - Only homeowners and admins can save predictions
   - Only admins can manually trigger evaluations
   - Only admins and contractors can view metrics

### Audit Trail

Every action is logged:
- Prediction saved
- Prediction locked
- Evaluation completed
- Configuration changes

Query audit trail:
```sql
SELECT 
  apa.*,
  u.first_name,
  u.last_name,
  cp.project_name
FROM ai_prediction_audit apa
JOIN users u ON apa.performed_by = u.id
JOIN construction_projects cp ON apa.project_id = cp.id
ORDER BY apa.performed_at DESC;
```

---

## 📊 Example Scenarios

### Scenario 1: Perfect Prediction (True Positive)

```
Prediction:
- Cost Risk: High (95% probability)
- Time Risk: High (90% probability)

Actual Outcome:
- Cost Overrun: 12.5% (> 5% threshold)
- Time Overrun: 15.0% (> 5% threshold)

Ground Truth:
- Cost: High
- Time: High

Classification:
- Cost: TP (True Positive) ✅
- Time: TP (True Positive) ✅

Result: Both predictions correct!
```

### Scenario 2: False Alarm (False Positive)

```
Prediction:
- Cost Risk: High (85% probability)

Actual Outcome:
- Cost Overrun: 2.5% (< 5% threshold)

Ground Truth:
- Cost: Low

Classification:
- Cost: FP (False Positive) ❌

Result: Predicted High but was actually Low
Interpretation: Model was too conservative
```

### Scenario 3: Missed Risk (False Negative)

```
Prediction:
- Time Risk: Low (10% probability)

Actual Outcome:
- Time Overrun: 18.0% (> 5% threshold)

Ground Truth:
- Time: High

Classification:
- Time: FN (False Negative) ❌

Result: Predicted Low but was actually High
Interpretation: Model missed a risky project
```

---

## 🎓 Best Practices

### For Developers

1. **Always Save Predictions**
   - Call `save_ai_predictions.php` immediately after AI analysis
   - Don't wait until project approval

2. **Handle Locked Predictions**
   - Check `predictions_locked` status before attempting updates
   - Show appropriate error messages to users

3. **Monitor Metrics Regularly**
   - Set up dashboard to display latest metrics
   - Alert if F1 score drops below 80%

4. **Respect Immutability**
   - Never manually update prediction fields after locking
   - Use audit trail to track all changes

### For Admins

1. **Review Metrics Monthly**
   - Check accuracy, precision, recall trends
   - Identify patterns in false positives/negatives

2. **Adjust Thresholds Carefully**
   - Changing thresholds affects ground truth classification
   - Document reasons for threshold changes

3. **Retrain Models When Needed**
   - If F1 score < 70%, consider retraining
   - Use actual project data for retraining

4. **Investigate Misclassifications**
   - Review FP and FN cases
   - Identify common factors in incorrect predictions

---

## 🚀 Future Enhancements

### Phase 1: Advanced Analytics
- Trend analysis over time
- Model performance by project type
- Feature importance validation

### Phase 2: Automated Retraining
- Trigger retraining when metrics degrade
- A/B testing of model versions
- Continuous learning pipeline

### Phase 3: Predictive Insights
- Identify which features cause misclassifications
- Recommend model improvements
- Automated threshold optimization

---

## 📝 Summary

### What This Framework Provides

✅ **Self-Evaluation** - AI automatically measures its own performance  
✅ **Real-World Metrics** - Accuracy, Precision, Recall, F1 Score  
✅ **Confusion Matrix** - TP, FP, TN, FN classification  
✅ **Immutable Predictions** - Cannot be changed after project starts  
✅ **Automatic Evaluation** - Triggers on project completion  
✅ **Complete Audit Trail** - All actions logged  
✅ **Backward Compatible** - No breaking changes  
✅ **Configurable Thresholds** - Adjust ground truth criteria  

### Key Benefits

- **Transparency**: Know exactly how well AI predictions perform
- **Accountability**: Immutable predictions prevent manipulation
- **Continuous Improvement**: Identify weaknesses and retrain
- **Trust**: Demonstrate AI reliability with real metrics
- **Compliance**: Complete audit trail for regulatory requirements

---

**Version:** 1.0  
**Date:** February 16, 2026  
**Status:** Production Ready ✅  
**Compatibility:** 100% Backward Compatible ✅

## 📊 Metrics Explained

### Confusion Matrix

A confusion matrix classifies predictions into four categories:

```
                    ACTUAL OUTCOME
                 Overrun    No Overrun
PREDICTED  High/Med   TP         FP
           Low         FN         TN
```

**True Positive (TP):**
- Predicted: High or Medium risk
- Actual: Overrun occurred (>5% threshold)
- Interpretation: Correctly predicted an overrun
- Example: Predicted High cost risk, actual cost overrun was 8%

**False Positive (FP):**
- Predicted: High or Medium risk
- Actual: No overrun (≤5% threshold)
- Interpretation: Incorrectly predicted an overrun
- Example: Predicted High cost risk, actual cost overrun was only 2%

**True Negative (TN):**
- Predicted: Low risk
- Actual: No overrun (≤5% threshold)
- Interpretation: Correctly predicted no overrun
- Example: Predicted Low cost risk, actual cost overrun was 1%

**False Negative (FN):**
- Predicted: Low risk
- Actual: Overrun occurred (>5% threshold)
- Interpretation: Incorrectly predicted no overrun (missed it)
- Example: Predicted Low cost risk, actual cost overrun was 12%

### Performance Metrics

#### Accuracy
```
Accuracy = (TP + TN) / (TP + FP + TN + FN)
```
- **Meaning:** Overall correctness of predictions
- **Range:** 0-100%
- **Good Value:** >80%
- **Example:** 85% accuracy means 85 out of 100 predictions were correct

#### Precision
```
Precision = TP / (TP + FP)
```
- **Meaning:** When we predict an overrun, how often are we correct?
- **Range:** 0-100%
- **Good Value:** >80%
- **Example:** 90% precision means when we predict overrun, we're right 90% of the time
- **Use Case:** Important when false alarms are costly

#### Recall (Sensitivity)
```
Recall = TP / (TP + FN)
```
- **Meaning:** Of all actual overruns, how many did we catch?
- **Range:** 0-100%
- **Good Value:** >85%
- **Example:** 95% recall means we caught 95% of all overruns
- **Use Case:** Important when missing overruns is costly

#### F1 Score
```
F1 = 2 × (Precision × Recall) / (Precision + Recall)
```
- **Meaning:** Harmonic mean of precision and recall
- **Range:** 0-100%
- **Good Value:** >85%
- **Example:** 88% F1 means balanced performance between precision and recall
- **Use Case:** Best overall metric when you need balance

### Interpretation Guide

**Scenario 1: High Precision, Low Recall**
```
Precision: 95%
Recall: 60%
```
- We rarely cry wolf (few false alarms)
- But we miss many actual overruns
- **Action:** Model is too conservative, increase sensitivity

**Scenario 2: Low Precision, High Recall**
```
Precision: 65%
Recall: 95%
```
- We catch almost all overruns
- But we have many false alarms
- **Action:** Model is too aggressive, increase specificity

**Scenario 3: Balanced Performance**
```
Precision: 88%
Recall: 90%
F1 Score: 89%
```
- Good balance between catching overruns and avoiding false alarms
- **Action:** Model is performing well

### Real-World Example

**Project Data:**
- Total completed projects: 100
- Projects with predictions: 90

**Cost Predictions:**
- TP: 45 (Predicted overrun, actually overran)
- FP: 8 (Predicted overrun, didn't overrun)
- TN: 32 (Predicted no overrun, didn't overrun)
- FN: 5 (Predicted no overrun, actually overran)

**Calculated Metrics:**
```
Accuracy = (45 + 32) / (45 + 8 + 32 + 5) = 77/90 = 85.56%
Precision = 45 / (45 + 8) = 45/53 = 84.91%
Recall = 45 / (45 + 5) = 45/50 = 90.00%
F1 Score = 2 × (0.8491 × 0.9000) / (0.8491 + 0.9000) = 87.39%
```

**Interpretation:**
- 85.56% of all predictions were correct
- When we predict overrun, we're right 84.91% of the time
- We catch 90% of all actual overruns
- Overall balanced performance: 87.39% F1 score

---

## 🔧 Troubleshooting

### Issue 1: Predictions Not Being Saved

**Symptoms:**
- API returns success but predictions are NULL in database
- No audit log entries

**Solutions:**
1. Check if stored procedure exists:
   ```sql
   SHOW PROCEDURE STATUS WHERE Name = 'save_ai_prediction';
   ```

2. Test procedure directly:
   ```sql
   CALL save_ai_prediction(1, 'High', 0.85, 'Medium', 0.62, 'v1.0.0');
   SELECT predicted_cost_risk_level FROM construction_projects WHERE id = 1;
   ```

3. Check for SQL errors in PHP error log

4. Verify database connection in `backend/config/database.php`

### Issue 2: Predictions Not Locking

**Symptoms:**
- `predictions_locked` remains 0 after work starts
- Can still modify predictions after `actual_start_date` is set

**Solutions:**
1. Check if trigger exists:
   ```sql
   SHOW TRIGGERS WHERE `Trigger` = 'lock_predictions_on_work_start';
   ```

2. Test trigger manually:
   ```sql
   UPDATE construction_projects
   SET actual_start_date = CURDATE()
   WHERE id = 1;
   
   SELECT predictions_locked FROM construction_projects WHERE id = 1;
   ```

3. Recreate trigger if missing:
   ```bash
   mysql -u root -p buildhub < backend/database/ai_evaluation_procedures.sql
   ```

### Issue 3: Evaluation Not Running on Completion

**Symptoms:**
- `evaluation_completed_at` is NULL for completed projects
- No confusion matrix classifications

**Solutions:**
1. Check if auto-evaluation is enabled:
   ```sql
   SELECT config_value FROM ai_evaluation_config
   WHERE config_key = 'auto_evaluation_enabled';
   ```

2. Check if trigger exists:
   ```sql
   SHOW TRIGGERS WHERE `Trigger` = 'auto_evaluate_on_completion';
   ```

3. Run evaluation manually:
   ```sql
   CALL evaluate_project(1);
   ```

4. Check if project has predictions:
   ```sql
   SELECT 
     id,
     predicted_cost_risk_level,
     predicted_time_risk_level
   FROM construction_projects
   WHERE id = 1;
   ```

### Issue 4: Metrics Showing NULL or 0

**Symptoms:**
- All metrics are NULL or 0
- Confusion matrix is empty

**Solutions:**
1. Check if any projects have been evaluated:
   ```sql
   SELECT COUNT(*) FROM construction_projects
   WHERE evaluation_completed_at IS NOT NULL;
   ```

2. Manually calculate metrics:
   ```sql
   CALL calculate_aggregate_metrics();
   ```

3. Check if completed projects have predictions:
   ```sql
   SELECT 
     COUNT(*) as total,
     SUM(CASE WHEN predicted_cost_risk_level IS NOT NULL THEN 1 ELSE 0 END) as with_predictions
   FROM construction_projects
   WHERE status = 'completed';
   ```

4. Verify threshold configuration:
   ```sql
   SELECT * FROM ai_evaluation_config
   WHERE config_key LIKE '%threshold%';
   ```

### Issue 5: API Returns 500 Error

**Symptoms:**
- API calls fail with 500 Internal Server Error
- No response data

**Solutions:**
1. Check PHP error log:
   ```bash
   tail -f /var/log/php/error.log
   ```

2. Test database connection:
   ```php
   <?php
   require_once 'backend/config/database.php';
   $db = new Database();
   $conn = $db->getConnection();
   echo $conn ? "Connected" : "Failed";
   ?>
   ```

3. Test API directly with curl:
   ```bash
   curl -X POST http://localhost/buildhub/backend/api/ml/save_ai_prediction.php \
     -H "Content-Type: application/json" \
     -d '{"project_id":1,"cost_risk_level":"High","cost_probability":0.85,"time_risk_level":"Medium","time_probability":0.62}'
   ```

4. Check file permissions:
   ```bash
   ls -la backend/api/ml/
   ```

### Issue 6: Incorrect Classifications

**Symptoms:**
- TP/FP/TN/FN counts seem wrong
- Predictions marked as incorrect when they should be correct

**Solutions:**
1. Check threshold values:
   ```sql
   SELECT * FROM ai_evaluation_config
   WHERE config_key IN ('cost_overrun_threshold', 'time_overrun_threshold');
   ```

2. Verify ground truth classification:
   ```sql
   SELECT 
     id,
     actual_cost_overrun_percentage,
     cost_ground_truth_label,
     actual_time_overrun_percentage,
     time_ground_truth_label
   FROM construction_projects
   WHERE evaluation_completed_at IS NOT NULL
   LIMIT 10;
   ```

3. Check classification logic:
   ```sql
   SELECT 
     id,
     predicted_cost_risk_level,
     cost_ground_truth_label,
     cost_prediction_classification,
     CASE
       WHEN predicted_cost_risk_level IN ('High', 'Medium') 
            AND cost_ground_truth_label = 'Overrun' THEN 'TP'
       WHEN predicted_cost_risk_level IN ('High', 'Medium') 
            AND cost_ground_truth_label = 'No_Overrun' THEN 'FP'
       WHEN predicted_cost_risk_level = 'Low' 
            AND cost_ground_truth_label = 'No_Overrun' THEN 'TN'
       WHEN predicted_cost_risk_level = 'Low' 
            AND cost_ground_truth_label = 'Overrun' THEN 'FN'
     END as expected_classification
   FROM construction_projects
   WHERE evaluation_completed_at IS NOT NULL
   LIMIT 10;
   ```

4. Re-evaluate if needed:
   ```sql
   UPDATE construction_projects
   SET evaluation_completed_at = NULL
   WHERE id = 1;
   
   CALL evaluate_project(1);
   ```

---

## 📈 Best Practices

### 1. Regular Metric Calculation

Schedule daily metric calculation:

```bash
# Add to crontab
0 2 * * * php /path/to/buildhub/calculate_daily_metrics.php
```

Create `calculate_daily_metrics.php`:
```php
<?php
require_once __DIR__ . '/backend/config/database.php';

$database = new Database();
$conn = $database->getConnection();

$conn->query("CALL calculate_aggregate_metrics()");

echo "Metrics calculated: " . date('Y-m-d H:i:s') . "\n";

$conn->close();
?>
```

### 2. Monitor Prediction Accuracy

Set up alerts for declining accuracy:

```sql
-- Check if accuracy dropped below 80%
SELECT 
  metric_type,
  accuracy,
  evaluation_date
FROM ai_evaluation_metrics
WHERE evaluation_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
AND accuracy < 0.80
ORDER BY evaluation_date DESC;
```

### 3. Analyze Misclassifications

Identify patterns in incorrect predictions:

```sql
-- Find projects with incorrect cost predictions
SELECT 
  id,
  project_name,
  predicted_cost_risk_level,
  actual_cost_overrun_percentage,
  cost_ground_truth_label,
  cost_prediction_classification
FROM construction_projects
WHERE cost_prediction_correct = 0
AND evaluation_completed_at IS NOT NULL
ORDER BY actual_cost_overrun_percentage DESC
LIMIT 20;
```

### 4. Version Control for Models

When updating ML models:

```sql
-- Update model version
UPDATE ai_evaluation_config
SET config_value = 'v2.0.0'
WHERE config_key = 'current_model_version';

-- All new predictions will use v2.0.0
-- Old predictions retain their original version
```

### 5. Backup Before Changes

Before modifying thresholds or configuration:

```bash
mysqldump -u root -p buildhub ai_evaluation_config ai_evaluation_metrics > backup_evaluation_$(date +%Y%m%d).sql
```

---

## 🎯 Summary

### What You Get

✅ **Self-Evaluating AI System**
- Automatically measures prediction accuracy
- Uses real-world project outcomes
- Provides standard ML metrics

✅ **100% Backward Compatible**
- All changes are additive
- Existing projects unaffected
- No breaking changes

✅ **Immutable Predictions**
- Predictions locked when work begins
- Prevents manipulation
- Ensures data integrity

✅ **Automatic Evaluation**
- Triggers on project completion
- Calculates confusion matrix
- Stores results permanently

✅ **Comprehensive Metrics**
- Accuracy, Precision, Recall, F1
- Historical tracking
- Per-project and aggregate views

### Key Files

- `backend/database/ai_self_evaluation_schema.sql` - Database schema
- `backend/database/ai_evaluation_procedures.sql` - Stored procedures & triggers
- `backend/api/ml/save_ai_prediction.php` - Save predictions API
- `backend/api/ml/get_evaluation_metrics.php` - Retrieve metrics API
- `apply_ai_self_evaluation_migration.php` - Migration script
- `test_ai_self_evaluation.php` - Automated tests
- `test_ai_self_evaluation_system.html` - Test interface

### Next Steps

1. Run migration: `php apply_ai_self_evaluation_migration.php`
2. Test system: `php test_ai_self_evaluation.php`
3. Update frontend to save predictions
4. Monitor metrics via API
5. Adjust thresholds as needed

---

**Document Version:** 1.0  
**Last Updated:** February 16, 2026  
**System Status:** Production Ready ✅

---

*This framework enables continuous improvement of AI predictions through real-world validation and performance tracking.*
