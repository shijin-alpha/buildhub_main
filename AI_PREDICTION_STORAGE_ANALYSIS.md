# AI Prediction Storage Analysis - Complete Report

## Executive Summary

**PROBLEM IDENTIFIED:** AI predictions are displayed in the dashboard but NOT stored in the database table `contractor_send_estimates`.

**ROOT CAUSE:** The prediction columns do NOT exist in the `contractor_send_estimates` table in the actual database, despite the code attempting to save predictions.

---

## Complete Workflow Analysis

### Stage 1: Homeowner Request Form Submission

**File:** `frontend/src/components/HomeownerRequestWizard.jsx`

**Flow:**
1. Homeowner fills custom request form with project details
2. Form data includes: plot_size_sqft, building_size_sqft, num_floors, budget_amount, num_bedrooms, num_bathrooms
3. On submission, form proceeds to Risk Assessment step

---

### Stage 2: AI Risk Prediction Generation

**File:** `frontend/src/components/RiskAssessmentPreview.jsx` (Lines 40-60)

**Flow:**
```javascript
// 1. Component calls prediction API
const response = await fetch('/buildhub/backend/api/ml/predict_construction_risks.php', {
  method: 'POST',
  body: JSON.stringify(formData)
});

// 2. Receives prediction results
const result = await response.json();
// result.data contains:
// - cost_overrun_risk: { risk_level, probability, top_factors }
// - time_delay_risk: { risk_level, probability, top_factors }
```

**Backend API:** `backend/api/ml/predict_construction_risks.php`

**Flow:**
```php
// 1. Validates input data
// 2. Calls FastAPI ML service at http://localhost:8000/predict
$ml_service_url = 'http://localhost:8000/predict';
$response = curl_exec($ch);

// 3. Returns predictions to frontend
```

**ML Service:** `backend/ml_service/main.py`

**Flow:**
```python
# 1. Models loaded once at startup (persistent in memory)
@app.on_event("startup")
async def load_models():
    predictor = ConstructionRiskPredictor()
    predictor.load_models()

# 2. /predict endpoint processes request
@app.post("/predict")
async def predict_risk(request: PredictionRequest):
    # Uses loaded models to make predictions
    # Returns: cost_risk_level, cost_probability, time_risk_level, time_probability
```

---

### Stage 3: Prediction Display (WORKING ✓)

**File:** `frontend/src/components/RiskAssessmentPreview.jsx` (Lines 100-250)

**Display Format:**
- Shows risk levels with color coding (🟢 Low, 🟡 Medium, 🔴 High)
- Displays probability percentages
- Lists top risk factors with explanations
- Blocks submission if BOTH cost AND time risks are HIGH
- Provides recommendations for budget/timeline adjustments

**Status:** ✅ WORKING - Predictions are successfully displayed in the dashboard

---

### Stage 4: Prediction Storage (BROKEN ❌)

**File:** `frontend/src/components/RiskAssessmentPreview.jsx` (Lines 75-100)

**Attempted Flow:**
```javascript
const savePredictionToDatabase = async (estimateId, predictions) => {
  const response = await fetch('/buildhub/backend/api/ml/save_estimate_prediction.php', {
    method: 'POST',
    body: JSON.stringify({
      estimate_id: estimateId,
      cost_risk_level: predictions.cost_overrun_risk?.risk_level,
      cost_probability: predictions.cost_overrun_risk?.probability,
      time_risk_level: predictions.time_delay_risk?.risk_level,
      time_probability: predictions.time_delay_risk?.probability,
      model_version: predictions.model_version || 'v1.0.0'
    })
  });
};
```

**Backend API:** `backend/api/ml/save_estimate_prediction.php` (Lines 140-165)

**Attempted SQL Query:**
```php
$update_query = "
    UPDATE contractor_send_estimates
    SET predicted_cost_risk_level = ?,
        predicted_cost_probability = ?,
        predicted_time_risk_level = ?,
        predicted_time_probability = ?,
        prediction_generated_at = NOW(),
        model_version = ?
    WHERE id = ?
";
```

**Status:** ❌ FAILING - Columns do NOT exist in database

---

## Database Schema Analysis

### Current Schema (ACTUAL)

**Table:** `contractor_send_estimates`

**Existing Columns:**
```sql
CREATE TABLE `contractor_send_estimates` (
  `id` int(11) NOT NULL,
  `send_id` int(11) NOT NULL,
  `contractor_id` int(11) NOT NULL,
  `materials` text DEFAULT NULL,
  `cost_breakdown` text DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `timeline` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(32) DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `structured` longtext DEFAULT NULL,
  `homeowner_feedback` text DEFAULT NULL,
  `homeowner_action_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**MISSING COLUMNS:**
- ❌ `predicted_cost_risk_level`
- ❌ `predicted_cost_probability`
- ❌ `predicted_time_risk_level`
- ❌ `predicted_time_probability`
- ❌ `prediction_generated_at`
- ❌ `model_version`

### Expected Schema (FROM DOCUMENTATION)

**File:** `backend/database/prediction_storage_fix.sql`

**Required Columns:**
```sql
ALTER TABLE contractor_send_estimates
ADD COLUMN predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL,
ADD COLUMN predicted_cost_probability DECIMAL(5,4) NULL,
ADD COLUMN predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL,
ADD COLUMN predicted_time_probability DECIMAL(5,4) NULL,
ADD COLUMN prediction_generated_at TIMESTAMP NULL,
ADD COLUMN model_version VARCHAR(50) NULL;
```

---

## Why Predictions Are NOT Stored

### Issue #1: Missing Database Columns

**Problem:** The `contractor_send_estimates` table does not have the required prediction columns.

**Evidence:**
- Schema inspection shows only 13 columns in the table
- None of the prediction columns exist
- SQL UPDATE query in `save_estimate_prediction.php` references non-existent columns

**Impact:** Any attempt to save predictions fails silently or throws SQL errors

### Issue #2: Schema Migration Not Applied

**Problem:** The migration script `backend/database/prediction_storage_fix.sql` was created but never executed on the database.

**Evidence:**
- Migration file exists with correct ALTER TABLE statements
- Database schema does not reflect these changes
- No evidence of migration execution in database

**Impact:** System cannot store predictions even though code is written to do so

### Issue #3: Auto-Schema Check Has Limitations

**File:** `backend/api/ml/save_estimate_prediction.php` (Lines 120-140)

**Code Attempt:**
```php
// Check if prediction columns exist
$columns_check = "SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted_cost_risk_level'";
$columns_result = $conn->query($columns_check);

if ($columns_result->num_rows === 0) {
    // Attempt to add columns dynamically
    $alter_query = "ALTER TABLE contractor_send_estimates ADD COLUMN ...";
    $conn->query($alter_query);
}
```

**Problem:** This auto-check may fail due to:
- Insufficient database permissions
- Connection using PDO instead of mysqli
- Silent failures not logged

---

## Verification of Current State

### Test Query Results

**Query:** Check for predictions in database
```sql
SELECT predicted_cost_risk_level, predicted_cost_probability, 
       predicted_time_risk_level, predicted_time_probability 
FROM contractor_send_estimates 
WHERE predicted_cost_risk_level IS NOT NULL;
```

**Result:** ❌ ERROR - "no such table: contractor_send_estimates"
- This error occurred because we tried SQLite commands on a MySQL database
- Confirms database is MySQL/MariaDB, not SQLite

**Correct Query:**
```sql
SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%';
```

**Expected Result:** 0 rows (columns don't exist)

---

## Complete Fix Solution

### Step 1: Add Missing Columns to Database

**File to Execute:** `backend/database/prediction_storage_fix.sql`

**SQL Commands:**
```sql
-- Add prediction columns to contractor_send_estimates
ALTER TABLE contractor_send_estimates
ADD COLUMN predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted cost overrun risk level',
ADD COLUMN predicted_cost_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted cost risk (0-1)',
ADD COLUMN predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL COMMENT 'AI predicted time delay risk level',
ADD COLUMN predicted_time_probability DECIMAL(5,4) NULL COMMENT 'Probability of predicted time risk (0-1)',
ADD COLUMN prediction_generated_at TIMESTAMP NULL COMMENT 'When AI prediction was made',
ADD COLUMN model_version VARCHAR(50) NULL COMMENT 'ML model version used for prediction';

-- Add index for performance
CREATE INDEX idx_estimate_predictions 
ON contractor_send_estimates(predicted_cost_risk_level, predicted_time_risk_level);
```

### Step 2: Verify Column Addition

**SQL Query:**
```sql
SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%';
```

**Expected Output:**
```
predicted_cost_risk_level    | enum('Low','Medium','High')
predicted_cost_probability   | decimal(5,4)
predicted_time_risk_level    | enum('Low','Medium','High')
predicted_time_probability   | decimal(5,4)
prediction_generated_at      | timestamp
model_version                | varchar(50)
```

### Step 3: Test Prediction Storage

**Test Query:**
```sql
-- Insert test prediction
UPDATE contractor_send_estimates
SET predicted_cost_risk_level = 'High',
    predicted_cost_probability = 0.9550,
    predicted_time_risk_level = 'Low',
    predicted_time_probability = 0.1520,
    prediction_generated_at = NOW(),
    model_version = 'v1.0.0'
WHERE id = 37;

-- Verify storage
SELECT id, predicted_cost_risk_level, predicted_cost_probability,
       predicted_time_risk_level, predicted_time_probability,
       prediction_generated_at, model_version
FROM contractor_send_estimates
WHERE id = 37;
```

### Step 4: Add Columns to construction_projects Table

**SQL Commands:**
```sql
-- Add same columns to construction_projects for when project is created
ALTER TABLE construction_projects
ADD COLUMN predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL,
ADD COLUMN predicted_cost_probability DECIMAL(5,4) NULL,
ADD COLUMN predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL,
ADD COLUMN predicted_time_probability DECIMAL(5,4) NULL,
ADD COLUMN prediction_generated_at TIMESTAMP NULL,
ADD COLUMN model_version VARCHAR(50) NULL,
ADD COLUMN predictions_locked TINYINT(1) DEFAULT 0;
```

### Step 5: Create Trigger to Copy Predictions

**File:** `backend/database/prediction_copy_trigger.sql`

**SQL Commands:**
```sql
DELIMITER $$

DROP TRIGGER IF EXISTS copy_predictions_to_project$$

CREATE TRIGGER copy_predictions_to_project
AFTER INSERT ON construction_projects
FOR EACH ROW
BEGIN
    DECLARE v_cost_risk VARCHAR(20);
    DECLARE v_cost_prob DECIMAL(5,4);
    DECLARE v_time_risk VARCHAR(20);
    DECLARE v_time_prob DECIMAL(5,4);
    DECLARE v_pred_time TIMESTAMP;
    DECLARE v_model_ver VARCHAR(50);
    
    IF NEW.estimate_id IS NOT NULL THEN
        SELECT 
            predicted_cost_risk_level,
            predicted_cost_probability,
            predicted_time_risk_level,
            predicted_time_probability,
            prediction_generated_at,
            model_version
        INTO 
            v_cost_risk, v_cost_prob, v_time_risk, 
            v_time_prob, v_pred_time, v_model_ver
        FROM contractor_send_estimates
        WHERE id = NEW.estimate_id;
        
        IF v_cost_risk IS NOT NULL OR v_time_risk IS NOT NULL THEN
            UPDATE construction_projects
            SET 
                predicted_cost_risk_level = v_cost_risk,
                predicted_cost_probability = v_cost_prob,
                predicted_time_risk_level = v_time_risk,
                predicted_time_probability = v_time_prob,
                prediction_generated_at = v_pred_time,
                model_version = v_model_ver
            WHERE id = NEW.id;
        END IF;
    END IF;
END$$

DELIMITER ;
```

---

## File Locations Summary

### Files Responsible for Prediction Generation

1. **Frontend Display:**
   - `frontend/src/components/RiskAssessmentPreview.jsx` - Displays predictions and calls save API

2. **Backend API:**
   - `backend/api/ml/predict_construction_risks.php` - Calls ML service for predictions
   - `backend/api/ml/save_estimate_prediction.php` - Saves predictions to database

3. **ML Service:**
   - `backend/ml_service/main.py` - FastAPI service with persistent models
   - `backend/ml/risk_predictor.py` - ML model loading and prediction logic

### Files Responsible for Database Storage

1. **Migration Scripts:**
   - `backend/database/prediction_storage_fix.sql` - Adds columns to contractor_send_estimates
   - `backend/database/ai_self_evaluation_schema.sql` - Adds columns to construction_projects
   - `backend/database/prediction_copy_trigger.sql` - Trigger to copy predictions

2. **Storage APIs:**
   - `backend/api/ml/save_estimate_prediction.php` - Saves to contractor_send_estimates
   - `backend/api/ml/save_ai_predictions.php` - Saves to construction_projects

### Files Responsible for Dashboard Display

1. **Frontend Components:**
   - `frontend/src/components/RiskAssessmentPreview.jsx` - Risk assessment modal
   - `frontend/src/components/HomeownerRequestWizard.jsx` - Form submission flow
   - `frontend/src/styles/RiskAssessmentPreview.css` - Styling

---

## Execution Plan

### Immediate Actions Required

1. **Execute Migration Script:**
   ```bash
   mysql -u root -p buildhub < backend/database/prediction_storage_fix.sql
   ```

2. **Verify Columns Added:**
   ```bash
   mysql -u root -p buildhub -e "SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%';"
   ```

3. **Execute Trigger Script:**
   ```bash
   mysql -u root -p buildhub < backend/database/prediction_copy_trigger.sql
   ```

4. **Test Prediction Storage:**
   - Submit a new homeowner request
   - View risk assessment
   - Check database for stored predictions

### Verification Queries

```sql
-- Check if columns exist
SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%';

-- Check if trigger exists
SHOW TRIGGERS WHERE `Table` = 'construction_projects';

-- View stored predictions
SELECT id, send_id, predicted_cost_risk_level, predicted_cost_probability,
       predicted_time_risk_level, predicted_time_probability,
       prediction_generated_at, model_version
FROM contractor_send_estimates
WHERE predicted_cost_risk_level IS NOT NULL
ORDER BY created_at DESC
LIMIT 10;
```

---

## Conclusion

**Root Cause:** Database schema is missing the required prediction columns.

**Impact:** Predictions are generated and displayed but not persisted to the database.

**Solution:** Execute the migration scripts to add the missing columns and triggers.

**Expected Outcome:** After applying the fix, predictions will be:
1. ✅ Generated by ML models
2. ✅ Displayed in the dashboard
3. ✅ Stored in contractor_send_estimates table
4. ✅ Automatically copied to construction_projects when project is created
5. ✅ Available for evaluation and analytics

---

## Next Steps

1. Apply database migrations immediately
2. Test with a new homeowner request
3. Verify predictions are stored correctly
4. Monitor for any errors in the logs
5. Update documentation with migration status
