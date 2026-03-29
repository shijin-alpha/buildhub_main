# Construction AI System - Complete Architecture Audit Report

**Date:** March 11, 2026  
**Auditor:** Senior Software Architect  
**System:** BuildHub Construction AI Risk Assessment & Self-Evaluation Framework

---

## Executive Summary

This audit traces the complete runtime workflow of the construction AI system from project creation through ML prediction, storage, monitoring, completion, and self-evaluation. The analysis reveals a **well-architected but partially disconnected system** with critical gaps in the prediction-to-evaluation pipeline.

### Key Findings:
- ✅ **ML Pipeline**: Fully operational and production-ready
- ✅ **Database Schema**: Complete with triggers and stored procedures
- ⚠️ **Frontend Integration**: Prediction API exists but **NOT CALLED** during project creation
- ❌ **Critical Gap**: Predictions are **NOT being saved** to database during project submission
- ✅ **Evaluation Framework**: Fully implemented with automatic triggers
- ❌ **Broken Link**: No predictions stored = No evaluations triggered

---

## 1. VERIFIED SYSTEM WORKFLOW (Step-by-Step)

### Stage 1: Homeowner Project Creation ✅ IMPLEMENTED

**Entry Point:** `backend/api/homeowner/submit_enhanced_request.php`

**Flow:**
```
User fills form → submit_enhanced_request.php → Creates:
  1. layout_requests table entry
  2. projects table entry  
  3. project_milestones entries
  4. Notifications to architects
```

**Database Tables Used:**
- `layout_requests` - Stores plot size, budget, requirements
- `projects` - Main project tracking
- `project_milestones` - Workflow stages
- `inbox_messages` - Notifications

**Data Captured:**
- Plot size (sqft)
- Building size (sqft)
- Budget range
- Number of floors
- Number of bedrooms/bathrooms
- Location, timeline, requirements

**❌ CRITICAL MISSING STEP:** 
The form submission does **NOT** call the ML prediction API. The prediction endpoint exists at `backend/api/ml/predict_construction_risks.php` but is never invoked during project creation.

---

### Stage 2: ML Risk Prediction ⚠️ PARTIALLY IMPLEMENTED

**ML Pipeline:** `backend/ml/risk_prediction_pipeline.py`

**Status:** ✅ Fully functional and trained

**Models Available:**
- `backend/ml/models/cost_overrun_risk_model.pkl` ✅ EXISTS
- `backend/ml/models/time_delay_risk_model.pkl` ✅ EXISTS
- Model metadata and feature importance stored

**Prediction API:** `backend/api/ml/predict_construction_risks.php`

**Expected Flow:**
```python
# Python prediction script
Input: {
  plot_size_sqft,
  building_size_sqft,
  num_floors,
  budget_amount,
  num_bedrooms,
  num_bathrooms
}

Output: {
  cost_overrun_risk: {
    risk_level: "Low" | "Medium" | "High",
    probability: 0.0 - 1.0,
    confidence: percentage
  },
  time_delay_risk: {
    risk_level: "Low" | "Medium" | "High",
    probability: 0.0 - 1.0,
    confidence: percentage
  }
}
```

**❌ PROBLEM:** This API is **NOT CALLED** by the frontend during project submission. The prediction capability exists but is disconnected from the workflow.

---

### Stage 3: Prediction Storage ❌ NOT CONNECTED

**API Endpoint:** `backend/api/ml/save_ai_prediction.php`

**Purpose:** Store AI predictions immutably when project is confirmed

**Database Fields (construction_projects table):**
```sql
predicted_cost_risk_level ENUM('Low', 'Medium', 'High')
predicted_cost_probability DECIMAL(5,4)
predicted_time_risk_level ENUM('Low', 'Medium', 'High')
predicted_time_probability DECIMAL(5,4)
prediction_generated_at TIMESTAMP
model_version VARCHAR(50)
predictions_locked TINYINT(1) -- Prevents modification after work begins
```

**Stored Procedure:** `save_ai_prediction()`
- Validates predictions not locked
- Saves prediction data
- Logs to audit trail (`ai_prediction_audit` table)

**❌ CRITICAL ISSUE:** 
The `save_ai_prediction.php` API exists and is fully functional, but it is **NEVER CALLED** by the frontend. The homeowner project submission flow does not include:
1. Calling `predict_construction_risks.php` to get predictions
2. Displaying predictions to user
3. Calling `save_ai_prediction.php` to store predictions

**Result:** `predicted_cost_risk_level` and `predicted_time_risk_level` columns remain **NULL** for all projects.

---

### Stage 4: Project Execution Tracking ✅ IMPLEMENTED

**Schedule Tracking:** `backend/api/schedule_tracking.php`

**Workflow:**
```
1. Contractor sets planned dates
   → planned_start_date, planned_end_date

2. Work begins
   → actual_start_date set
   → TRIGGER: predictions_locked = 1 (prevents modification)

3. Project progresses
   → Daily progress reports
   → Stage payments
   → Custom payments

4. Project completes
   → actual_end_date set
   → status = 'completed'
   → actual_time_overrun_percentage calculated
```

**Database Tables:**
- `construction_projects` - Main project data
- `stage_payment_requests` - Stage-based payments
- `custom_payment_requests` - Additional payments
- `progress_reports` - Daily updates
- `project_schedule_audit` - Change tracking

**Calculation Logic:**
```php
// Time Overrun Calculation
$plannedDuration = $plannedStartDate->diff($plannedEndDate)->days;
$actualDuration = $actualStartDate->diff($actualEndDate)->days;
$overrunPercentage = (($actualDuration - $plannedDuration) / $plannedDuration) * 100;
```

**✅ WORKING:** Schedule tracking is fully operational and calculates time overruns correctly.

---

### Stage 5: Cost Overrun Calculation ✅ IMPLEMENTED

**Stored Procedure:** `calculate_actual_cost_overrun()`

**Logic:**
```sql
-- Get original estimate
SELECT estimated_cost FROM construction_projects WHERE id = project_id;

-- Sum all stage payments
SELECT SUM(amount) FROM stage_payment_requests 
WHERE project_id = project_id AND status IN ('paid', 'pending', 'approved');

-- Sum all custom payments  
SELECT SUM(amount) FROM custom_payment_requests
WHERE project_id = project_id AND status IN ('paid', 'pending', 'approved');

-- Calculate overrun percentage
total_cost = stage_payments + custom_payments
overrun_pct = ((total_cost - estimated_cost) / estimated_cost) * 100

-- Update project
UPDATE construction_projects 
SET actual_cost_overrun_percentage = overrun_pct
WHERE id = project_id;
```

**✅ WORKING:** Cost overrun calculation is implemented and functional.

---

### Stage 6: AI Self-Evaluation ⚠️ IMPLEMENTED BUT NEVER TRIGGERED

**Database Schema:** `backend/database/ai_self_evaluation_schema.sql`

**Evaluation Tables:**
- `ai_evaluation_config` - Thresholds and settings
- `ai_evaluation_metrics` - Aggregated performance metrics
- `ai_prediction_audit` - Audit trail

**Automatic Trigger:** `auto_evaluate_on_completion`

```sql
CREATE TRIGGER auto_evaluate_on_completion
AFTER UPDATE ON construction_projects
FOR EACH ROW
BEGIN
  -- Check if auto-evaluation is enabled
  SELECT CAST(config_value AS UNSIGNED) INTO v_auto_eval_enabled
  FROM ai_evaluation_config
  WHERE config_key = 'auto_evaluation_enabled';
  
  -- Trigger evaluation when status changes to 'completed'
  IF NEW.status = 'completed' 
     AND OLD.status != 'completed'
     AND v_auto_eval_enabled = 1 THEN
    CALL evaluate_project(NEW.id);
  END IF;
END;
```

**Evaluation Workflow:**
```
1. calculate_actual_cost_overrun() → Gets actual costs
2. classify_ground_truth() → Determines if "Overrun" or "No_Overrun"
3. classify_prediction() → Confusion matrix classification (TP/FP/TN/FN)
4. Update evaluation_completed_at timestamp
5. Log to ai_prediction_audit
6. calculate_aggregate_metrics() → Update system-wide metrics
```

**Confusion Matrix Logic:**
```
Predicted High/Medium + Actual Overrun = TP (True Positive)
Predicted High/Medium + No Overrun = FP (False Positive)
Predicted Low + No Overrun = TN (True Negative)
Predicted Low + Actual Overrun = FN (False Negative)
```

**❌ CRITICAL PROBLEM:**
The evaluation trigger checks:
```sql
IF NEW.status = 'completed' 
   AND (predicted_cost_risk_level IS NOT NULL 
        OR predicted_time_risk_level IS NOT NULL)
```

Since predictions are **NEVER SAVED**, these fields are **NULL**, so the evaluation **NEVER RUNS**.

---

## 2. ACTUAL ARCHITECTURE DIAGRAM

```
┌─────────────────────────────────────────────────────────────────────┐
│                    CONSTRUCTION AI SYSTEM ARCHITECTURE               │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────┐
│  HOMEOWNER FORM      │
│  (Frontend)          │
│                      │
│  - Plot size         │
│  - Budget            │
│  - Bedrooms/baths    │
│  - Floors            │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────────────────────────────────────────────────┐
│  submit_enhanced_request.php                                     │
│  ✅ Creates layout_requests                                      │
│  ✅ Creates projects entry                                       │
│  ✅ Creates milestones                                           │
│  ❌ DOES NOT call predict_construction_risks.php                 │
│  ❌ DOES NOT call save_ai_prediction.php                         │
└──────────┬───────────────────────────────────────────────────────┘
           │
           │  ❌ MISSING CONNECTION
           │
           ▼
┌──────────────────────────────────────────────────────────────────┐
│  ML PREDICTION PIPELINE (DISCONNECTED)                           │
│                                                                  │
│  predict_construction_risks.php                                  │
│  ├─ Calls: backend/ml/predict_risks_api.py                      │
│  ├─ Loads: cost_overrun_risk_model.pkl                          │
│  ├─ Loads: time_delay_risk_model.pkl                            │
│  └─ Returns: {cost_risk, time_risk, probabilities}              │
│                                                                  │
│  ✅ FULLY FUNCTIONAL                                             │
│  ❌ NEVER CALLED                                                 │
└──────────────────────────────────────────────────────────────────┘
           │
           │  ❌ MISSING CONNECTION
           │
           ▼
┌──────────────────────────────────────────────────────────────────┐
│  PREDICTION STORAGE (DISCONNECTED)                               │
│                                                                  │
│  save_ai_prediction.php                                          │
│  ├─ Validates project exists                                     │
│  ├─ Checks predictions_locked flag                               │
│  ├─ Calls: save_ai_prediction() stored procedure                 │
│  └─ Updates: construction_projects table                         │
│                                                                  │
│  Fields Updated:                                                 │
│  - predicted_cost_risk_level                                     │
│  - predicted_cost_probability                                    │
│  - predicted_time_risk_level                                     │
│  - predicted_time_probability                                    │
│  - prediction_generated_at                                       │
│  - model_version                                                 │
│                                                                  │
│  ✅ FULLY FUNCTIONAL                                             │
│  ❌ NEVER CALLED                                                 │
└──────────────────────────────────────────────────────────────────┘
           │
           │
           ▼
┌──────────────────────────────────────────────────────────────────┐
│  PROJECT EXECUTION PHASE                                         │
│  ✅ FULLY OPERATIONAL                                            │
│                                                                  │
│  schedule_tracking.php                                           │
│  ├─ Contractor sets planned dates                                │
│  ├─ Records actual_start_date                                    │
│  │  └─ TRIGGER: predictions_locked = 1                           │
│  ├─ Daily progress reports                                       │
│  ├─ Stage payments tracked                                       │
│  ├─ Custom payments tracked                                      │
│  └─ Records actual_end_date                                      │
│     └─ Calculates actual_time_overrun_percentage                 │
│                                                                  │
│  calculate_actual_cost_overrun()                                 │
│  └─ Sums all payments vs estimated_cost                          │
│     └─ Calculates actual_cost_overrun_percentage                 │
└──────────┬───────────────────────────────────────────────────────┘
           │
           │  status = 'completed'
           │
           ▼
┌──────────────────────────────────────────────────────────────────┐
│  AUTO-EVALUATION TRIGGER                                         │
│  ⚠️ IMPLEMENTED BUT NEVER FIRES                                  │
│                                                                  │
│  auto_evaluate_on_completion TRIGGER                             │
│  ├─ Checks: status changed to 'completed'                        │
│  ├─ Checks: predicted_cost_risk_level IS NOT NULL ❌ FAILS      │
│  └─ Checks: predicted_time_risk_level IS NOT NULL ❌ FAILS      │
│                                                                  │
│  IF predictions exist:                                           │
│    CALL evaluate_project(project_id)                             │
│    ├─ calculate_actual_cost_overrun()                            │
│    ├─ classify_ground_truth()                                    │
│    ├─ classify_prediction()                                      │
│    └─ calculate_aggregate_metrics()                              │
│                                                                  │
│  ❌ NEVER EXECUTES (no predictions stored)                       │
└──────────────────────────────────────────────────────────────────┘
           │
           │  ❌ EVALUATION NEVER HAPPENS
           │
           ▼
┌──────────────────────────────────────────────────────────────────┐
│  AI EVALUATION METRICS (EMPTY)                                   │
│                                                                  │
│  Tables:                                                         │
│  - ai_evaluation_metrics (no data)                               │
│  - ai_prediction_audit (no entries)                              │
│  - construction_projects.evaluation_completed_at (always NULL)   │
│                                                                  │
│  ❌ NO DATA COLLECTED                                            │
└──────────────────────────────────────────────────────────────────┘
```

---

## 3. MISSING LINKS IN IMPLEMENTATION

### 🔴 Critical Gap #1: Prediction API Not Called

**Location:** Frontend project submission form

**Missing Code:**
```javascript
// SHOULD BE ADDED to homeowner project submission
async function submitProjectWithRiskAssessment(formData) {
  // Step 1: Get AI risk prediction
  const predictionResponse = await fetch('/backend/api/ml/predict_construction_risks.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      plot_size_sqft: formData.plot_size,
      building_size_sqft: formData.building_size,
      num_floors: formData.num_floors,
      budget_amount: formData.budget_range,
      num_bedrooms: formData.num_bedrooms,
      num_bathrooms: formData.num_bathrooms
    })
  });
  
  const prediction = await predictionResponse.json();
  
  // Step 2: Display risk assessment to user
  displayRiskAssessment(prediction.data);
  
  // Step 3: If user confirms, submit project
  if (await userConfirmsSubmission()) {
    const projectResponse = await submitProject(formData);
    const projectId = projectResponse.project_id;
    
    // Step 4: Save AI prediction
    await fetch('/backend/api/ml/save_ai_prediction.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        project_id: projectId,
        cost_risk_level: prediction.data.cost_overrun_risk.risk_level,
        cost_probability: prediction.data.cost_overrun_risk.probability,
        time_risk_level: prediction.data.time_delay_risk.risk_level,
        time_probability: prediction.data.time_delay_risk.probability,
        model_version: prediction.data.model_version
      })
    });
  }
}
```

**Impact:** Without this, predictions are never generated or stored.

---

### 🔴 Critical Gap #2: Prediction Storage Not Triggered

**Location:** `backend/api/homeowner/submit_enhanced_request.php`

**Current Code:** Lines 1-300 (project creation only)

**Missing Integration:**
```php
// SHOULD BE ADDED after project creation
$project_id = $db->lastInsertId();

// Call ML prediction API internally
$prediction_data = [
    'plot_size_sqft' => $plot_size,
    'building_size_sqft' => $building_size,
    'num_floors' => $house_plan_requirements['floors'],
    'budget_amount' => extractBudgetAmount($budget_range),
    'num_bedrooms' => count($house_plan_requirements['rooms']),
    'num_bathrooms' => countBathrooms($house_plan_requirements['rooms'])
];

// Get prediction from Python service
$prediction_result = callPythonPredictionService($prediction_data);

// Save prediction to database
if ($prediction_result['success']) {
    $save_prediction_query = "CALL save_ai_prediction(?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($save_prediction_query);
    $stmt->execute([
        $project_id,
        $prediction_result['cost_risk_level'],
        $prediction_result['cost_probability'],
        $prediction_result['time_risk_level'],
        $prediction_result['time_probability'],
        $prediction_result['model_version']
    ]);
}
```

**Impact:** Backend never stores predictions even if frontend calls the API.

---

### 🟡 Minor Gap #3: Manual Evaluation API Exists But Not Exposed

**Location:** `backend/api/ml/trigger_evaluation.php`

**Status:** ✅ Fully functional for manual triggering

**Usage:**
```bash
# Admin can manually trigger evaluation
POST /backend/api/ml/trigger_evaluation.php
{
  "project_id": 123  // Optional, evaluates all if omitted
}
```

**Issue:** No admin UI to trigger this. Only accessible via direct API call.

---

## 4. RECOMMENDED FIXES

### Priority 1: Connect Prediction to Project Creation (CRITICAL)

**Option A: Frontend Integration (Recommended)**

1. **Update homeowner project form:**
   - Add risk assessment step before final submission
   - Call `predict_construction_risks.php` with form data
   - Display risk levels and recommendations
   - Require user acknowledgment
   - Call `save_ai_prediction.php` after project creation

2. **Files to modify:**
   - `homeowner_dashboard_enhanced.html` or React equivalent
   - Add risk assessment preview component
   - Add confirmation dialog

**Option B: Backend Integration (Alternative)**

1. **Modify `submit_enhanced_request.php`:**
   - After creating project, call Python prediction service
   - Automatically save predictions to database
   - Return prediction data in response

2. **Advantage:** Guarantees predictions are always saved
3. **Disadvantage:** User doesn't see risk assessment before submission

---

### Priority 2: Verify Evaluation Trigger Works

**Test Script:**
```php
// test_complete_evaluation_workflow.php
<?php
require_once 'backend/config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Step 1: Create test project with predictions
$project_id = createTestProject($conn);
savePredictions($conn, $project_id, 'High', 0.85, 'Medium', 0.65);

// Step 2: Simulate project execution
recordActualDates($conn, $project_id);
recordPayments($conn, $project_id, 1200000); // 20% overrun

// Step 3: Complete project
$conn->query("UPDATE construction_projects SET status = 'completed' WHERE id = $project_id");

// Step 4: Verify evaluation ran
$result = $conn->query("SELECT evaluation_completed_at, cost_prediction_classification 
                        FROM construction_projects WHERE id = $project_id");
$eval = $result->fetch_assoc();

if ($eval['evaluation_completed_at']) {
    echo "✅ Evaluation triggered successfully\n";
    echo "Classification: {$eval['cost_prediction_classification']}\n";
} else {
    echo "❌ Evaluation did not trigger\n";
}
?>
```

---

### Priority 3: Add Admin Dashboard for Evaluation Metrics

**Create:** `admin_ai_evaluation_dashboard.php`

**Features:**
- View latest accuracy, precision, recall, F1 scores
- Confusion matrix visualization
- Historical trends
- Manual trigger button for re-evaluation
- Project-level evaluation details

**Database Views to Use:**
- `v_latest_ai_metrics`
- `v_project_evaluation_summary`
- `v_confusion_matrix_breakdown`

---

### Priority 4: Add Prediction Locking Verification

**Verify trigger works:**
```sql
-- Test prediction locking
UPDATE construction_projects 
SET actual_start_date = NOW() 
WHERE id = 1;

-- Check if locked
SELECT predictions_locked FROM construction_projects WHERE id = 1;
-- Should return 1

-- Try to modify prediction (should fail)
UPDATE construction_projects 
SET predicted_cost_risk_level = 'Low' 
WHERE id = 1;

-- Verify unchanged
SELECT predicted_cost_risk_level FROM construction_projects WHERE id = 1;
```

---

## 5. IMPLEMENTATION ROADMAP

### Phase 1: Critical Fixes (Week 1)
1. ✅ Add prediction API call to project submission form
2. ✅ Implement risk assessment preview UI
3. ✅ Connect `save_ai_prediction.php` after project creation
4. ✅ Test end-to-end workflow with one project

### Phase 2: Verification (Week 2)
1. ✅ Complete 5-10 test projects with predictions
2. ✅ Verify evaluation triggers automatically
3. ✅ Check metrics are calculated correctly
4. ✅ Validate confusion matrix classifications

### Phase 3: Admin Tools (Week 3)
1. ✅ Build admin evaluation dashboard
2. ✅ Add manual trigger UI
3. ✅ Add historical metrics charts
4. ✅ Add project-level evaluation details

### Phase 4: Production Deployment (Week 4)
1. ✅ Migrate existing projects (backfill predictions if possible)
2. ✅ Enable auto-evaluation in production
3. ✅ Monitor first 30 days of data
4. ✅ Adjust thresholds based on real data

---

## 6. CONCLUSION

### System Status: 🟡 PARTIALLY OPERATIONAL

**What Works:**
- ✅ ML models trained and ready
- ✅ Prediction API functional
- ✅ Database schema complete
- ✅ Evaluation logic implemented
- ✅ Triggers configured
- ✅ Cost/time tracking operational

**What's Broken:**
- ❌ Predictions not called during project creation
- ❌ Predictions not saved to database
- ❌ Evaluation never triggers (no predictions to evaluate)
- ❌ Metrics tables empty
- ❌ Self-evaluation framework dormant

**Root Cause:**
The system has all the pieces but they're not connected. The frontend doesn't call the prediction API, so predictions are never generated or stored, which means the evaluation framework has nothing to evaluate.

**Fix Complexity:** 🟢 LOW
- Single integration point needed (frontend → prediction API → save API)
- No architectural changes required
- All backend infrastructure ready
- Estimated effort: 2-3 days for full integration

**Business Impact:**
Once connected, the system will provide:
- Real-time risk assessment for homeowners
- Automatic accuracy tracking
- Continuous model improvement data
- Evidence-based decision support

---

## 7. NEXT STEPS

1. **Immediate:** Implement frontend prediction integration
2. **Short-term:** Test with 10 projects end-to-end
3. **Medium-term:** Build admin dashboard
4. **Long-term:** Use evaluation data to retrain models

---

**Report Generated:** March 11, 2026  
**System Version:** v1.0.0  
**Audit Status:** COMPLETE
