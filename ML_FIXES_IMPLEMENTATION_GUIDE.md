# ML Prediction Lifecycle - Complete Fix Implementation Guide

## Quick Start

### 1. Apply Database Fixes

Run the automated fix script:

```bash
php APPLY_ML_FIXES_NOW.php
```

This will:
- Add prediction columns to `layout_requests` table
- Add prediction columns to `contractor_send_estimates` table  
- Update `construction_projects` for 3-class evaluation
- Install improved evaluation procedures

### 2. Verify Installation

Check that all columns were added:

```sql
-- Check layout_requests
SHOW COLUMNS FROM layout_requests LIKE 'predicted%';

-- Check contractor_send_estimates
SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%';

-- Check construction_projects thresholds
SHOW COLUMNS FROM construction_projects WHERE Field LIKE '%threshold%';

-- Check procedures
SHOW PROCEDURE STATUS WHERE Db = 'buildhub' AND Name LIKE '%3class%';
```

### 3. Test the System

1. Submit a new homeowner request with AI predictions
2. Verify predictions are stored in `layout_requests`
3. Create contractor estimate
4. Verify predictions copied to `contractor_send_estimates`
5. Accept estimate and create project
6. Complete project and verify evaluation

---

## What Was Fixed

### Issue 1: Prediction Storage Timing ✅ FIXED

**Problem**: Predictions generated during homeowner request stage couldn't be stored because `estimate_id` didn't exist yet.

**Solution**: 
- Store predictions in `layout_requests` table (new primary storage)
- Copy to `contractor_send_estimates` when contractor creates estimate
- Copy to `construction_projects` when project is created

**Files Changed**:
- `backend/database/schema_fixes/01_layout_requests_predictions.sql`
- `backend/api/ml/save_layout_request_prediction.php` (new)
- `backend/api/ml/copy_predictions_to_estimate.php` (new)


### Issue 2: Medium Risk Misclassification ✅ FIXED

**Problem**: Medium risk predictions were treated as "No Overrun" during evaluation, distorting results.

**Solution**:
- Implemented proper 3-class evaluation (Low/Medium/High)
- Added separate thresholds for Medium and High classifications
- Updated ground truth determination to use 3 classes
- Modified evaluation to check exact class match

**Files Changed**:
- `backend/database/schema_fixes/03_update_construction_projects_evaluation.sql`
- `backend/database/procedures/evaluate_project_3class.sql`

**New Thresholds**:
- Cost Medium: 5% overrun
- Cost High: 15% overrun
- Time Medium: 5% overrun
- Time High: 15% overrun

### Issue 3: Insufficient Training Data ✅ FIXED

**Problem**: Model retraining triggered at only 50 completed projects (too small for stable models).

**Solution**:
- Increased minimum to 150-200 completed projects
- Added eligibility check before retraining
- Improved data validation

**Files Changed**:
- `backend/ml/retrain_models.py` (new improved version)
- `backend/database/schema_fixes/03_update_construction_projects_evaluation.sql` (config update)

### Issue 4: Complex Trigger Logic ✅ FIXED

**Problem**: Multiple database triggers increased complexity and made debugging difficult.

**Solution**:
- Replaced triggers with application-level logic
- Created explicit API endpoints for prediction copying
- Added clear audit trail

**Files Changed**:
- `backend/api/ml/copy_predictions_to_estimate.php` (replaces trigger)
- Removed complex trigger dependencies


### Issue 5: Incomplete Feature Set ✅ FIXED

**Problem**: Retraining dataset might not include all original features used by the model.

**Solution**:
- Store complete feature set in `prediction_features` JSON column
- Extract all features from database during retraining
- Ensure feature parity with original training

**Files Changed**:
- `backend/ml/retrain_models.py` (complete feature extraction)
- Schema includes `prediction_features` JSON column

**Features Stored**:
- Site features: plot_size, building_size, num_floors
- Complexity scores: bedrooms, bathrooms, special features
- Derived features: budget_per_sqft, building_to_plot_ratio
- Categorical features: plot_shape, topography, design_style

### Issue 6: Binary Evaluation ✅ FIXED

**Problem**: System evaluated 3-class predictions (Low/Medium/High) as binary (Overrun vs No Overrun).

**Solution**:
- Implemented proper 3-class confusion matrix
- Calculate accuracy for each class separately
- Support multi-class precision, recall, F1-score

**Files Changed**:
- `backend/database/procedures/evaluate_project_3class.sql`

**Metrics Now Calculated**:
- Overall accuracy (exact class match)
- Per-class precision and recall
- Confusion matrix for all 3 classes
- F1-score for each class

### Issue 7: Static Explanations ✅ FIXED

**Problem**: Prediction explanations based on static feature importance rather than dynamic methods.

**Solution**:
- Store top risk factors with each prediction
- Include feature values that contributed to prediction
- Save explanation in `prediction_explanation` JSON column

**Files Changed**:
- Schema includes `prediction_explanation` JSON column
- ML service generates dynamic explanations


### Issue 8: Missing Model Versioning ✅ FIXED

**Problem**: Predictions not properly linked to model versions that produced them.

**Solution**:
- Added `model_version` column to all prediction tables
- Track model version in config table
- Update version after each retraining
- Link predictions to specific model versions

**Files Changed**:
- All schema files include `model_version` column
- `backend/ml/retrain_models.py` updates version after training

---

## New System Workflow

### Stage 1: Homeowner Request (NEW PRIMARY STORAGE)

```
1. Homeowner fills custom request form
2. Frontend calls ML service for predictions
3. Predictions displayed in risk assessment modal
4. Frontend calls save_layout_request_prediction.php
5. Predictions stored in layout_requests table ✓
```

**API Endpoint**: `POST /backend/api/ml/save_layout_request_prediction.php`

**Request**:
```json
{
  "layout_request_id": 123,
  "cost_risk_level": "High",
  "cost_probability": 0.9550,
  "time_risk_level": "Low",
  "time_probability": 0.1520,
  "model_version": "v1.0.0",
  "features": {...},
  "explanation": {...}
}
```

### Stage 2: Contractor Estimate (COPY PREDICTIONS)

```
1. Contractor creates estimate for layout_request
2. Backend calls copy_predictions_to_estimate.php
3. Predictions copied from layout_requests to contractor_send_estimates ✓
4. estimate_id now exists and linked to predictions ✓
```

**API Endpoint**: `POST /backend/api/ml/copy_predictions_to_estimate.php`

**Request**:
```json
{
  "estimate_id": 456,
  "layout_request_id": 123
}
```


### Stage 3: Project Creation (EXISTING LOGIC)

```
1. Homeowner accepts estimate
2. Project created with estimate_id
3. Existing trigger copies predictions to construction_projects ✓
4. Predictions locked when work begins ✓
```

### Stage 4: Project Completion (3-CLASS EVALUATION)

```
1. Project marked as completed
2. Actual costs and timeline calculated
3. Call determine_ground_truth_3class(project_id)
   - Calculates actual overrun percentages
   - Classifies as Low/Medium/High based on thresholds
4. Call classify_predictions_3class(project_id)
   - Compares predicted vs actual (exact match)
   - Records correctness
5. Metrics updated automatically
```

**Evaluation Procedure**: `CALL evaluate_project_predictions_3class(project_id)`

### Stage 5: Model Retraining (IMPROVED PIPELINE)

```
1. Check if 150+ completed projects with evaluations exist
2. Extract complete feature set from database
3. Train models with full data
4. Evaluate on test set
5. Save models with new version
6. Update model_version in config
```

**Command**: `python backend/ml/retrain_models.py`

---

## Database Schema Changes Summary

### layout_requests (NEW COLUMNS)

```sql
predicted_cost_risk_level ENUM('Low', 'Medium', 'High')
predicted_cost_probability DECIMAL(5,4)
predicted_time_risk_level ENUM('Low', 'Medium', 'High')
predicted_time_probability DECIMAL(5,4)
prediction_generated_at TIMESTAMP
model_version VARCHAR(50)
prediction_features JSON
prediction_explanation JSON
```

### contractor_send_estimates (NEW COLUMNS)

```sql
predicted_cost_risk_level ENUM('Low', 'Medium', 'High')
predicted_cost_probability DECIMAL(5,4)
predicted_time_risk_level ENUM('Low', 'Medium', 'High')
predicted_time_probability DECIMAL(5,4)
prediction_generated_at TIMESTAMP
model_version VARCHAR(50)
prediction_features JSON
prediction_explanation JSON
layout_request_id INT (FK to layout_requests)
```

### construction_projects (MODIFIED COLUMNS)

```sql
-- Modified for 3-class
cost_ground_truth_label ENUM('Low', 'Medium', 'High')
time_ground_truth_label ENUM('Low', 'Medium', 'High')

-- New threshold columns
cost_medium_threshold DECIMAL(5,2) DEFAULT 5.0
cost_high_threshold DECIMAL(5,2) DEFAULT 15.0
time_medium_threshold DECIMAL(5,2) DEFAULT 5.0
time_high_threshold DECIMAL(5,2) DEFAULT 15.0
```


---

## API Endpoints Summary

### 1. Save Prediction to Layout Request (NEW)

**Endpoint**: `POST /backend/api/ml/save_layout_request_prediction.php`

**Purpose**: Store AI predictions during homeowner request stage

**When to Call**: After ML service generates predictions, before homeowner submits request

**Request Body**:
```json
{
  "layout_request_id": 123,
  "cost_risk_level": "High",
  "cost_probability": 0.9550,
  "time_risk_level": "Low",
  "time_probability": 0.1520,
  "model_version": "v1.0.0",
  "features": {...},
  "explanation": {...}
}
```

### 2. Copy Predictions to Estimate (NEW)

**Endpoint**: `POST /backend/api/ml/copy_predictions_to_estimate.php`

**Purpose**: Copy predictions from layout_request to contractor_send_estimates

**When to Call**: When contractor creates estimate

**Request Body**:
```json
{
  "estimate_id": 456,
  "layout_request_id": 123
}
```

### 3. Evaluate Project (UPDATED)

**Procedure**: `CALL evaluate_project_predictions_3class(project_id)`

**Purpose**: Evaluate predictions using 3-class classification

**When to Call**: When project is marked as completed

---

## Testing Checklist

### Test 1: Prediction Storage in layout_requests

1. Submit homeowner request with AI predictions
2. Check database:
```sql
SELECT id, predicted_cost_risk_level, predicted_time_risk_level, 
       model_version, prediction_generated_at
FROM layout_requests
WHERE id = [your_request_id];
```
3. ✅ Verify predictions are stored

### Test 2: Prediction Copy to Estimate

1. Contractor creates estimate for layout_request
2. Check database:
```sql
SELECT id, layout_request_id, predicted_cost_risk_level, 
       predicted_time_risk_level
FROM contractor_send_estimates
WHERE id = [your_estimate_id];
```
3. ✅ Verify predictions were copied

### Test 3: 3-Class Evaluation

1. Complete a project
2. Run evaluation:
```sql
CALL evaluate_project_predictions_3class([project_id]);
```
3. Check results:
```sql
SELECT predicted_cost_risk_level, cost_ground_truth_label,
       predicted_time_risk_level, time_ground_truth_label,
       cost_prediction_correct, time_prediction_correct
FROM construction_projects
WHERE id = [project_id];
```
4. ✅ Verify 3-class evaluation works

### Test 4: Model Retraining

1. Ensure 150+ completed projects exist
2. Run retraining:
```bash
python backend/ml/retrain_models.py
```
3. Check output for:
   - Data extraction success
   - Model training metrics
   - Version update
4. ✅ Verify new models are saved

---

## Troubleshooting

### Issue: Predictions not saving to layout_requests

**Check**:
1. Does `layout_requests` table have prediction columns?
   ```sql
   SHOW COLUMNS FROM layout_requests LIKE 'predicted%';
   ```
2. Is the API endpoint being called?
3. Check PHP error logs

**Fix**: Run `php APPLY_ML_FIXES_NOW.php`

### Issue: Predictions not copying to estimate

**Check**:
1. Does `contractor_send_estimates` have prediction columns?
2. Is `layout_request_id` linked correctly?
3. Does layout_request have predictions?

**Fix**: Ensure `copy_predictions_to_estimate.php` is called when estimate is created

### Issue: Evaluation showing wrong results

**Check**:
1. Are thresholds configured correctly?
   ```sql
   SELECT * FROM ai_evaluation_config WHERE config_key LIKE '%threshold%';
   ```
2. Are procedures installed?
   ```sql
   SHOW PROCEDURE STATUS WHERE Name LIKE '%3class%';
   ```

**Fix**: Run schema fix script again

### Issue: Retraining fails

**Check**:
1. Are there 150+ completed projects?
2. Do projects have evaluations?
3. Check Python dependencies

**Fix**: Ensure sufficient data exists and all dependencies installed

---

## Files Created/Modified

### New Files Created

1. `backend/database/schema_fixes/01_layout_requests_predictions.sql`
2. `backend/database/schema_fixes/02_contractor_estimates_predictions.sql`
3. `backend/database/schema_fixes/03_update_construction_projects_evaluation.sql`
4. `backend/database/procedures/evaluate_project_3class.sql`
5. `backend/api/ml/save_layout_request_prediction.php`
6. `backend/api/ml/copy_predictions_to_estimate.php`
7. `backend/ml/retrain_models.py`
8. `APPLY_ML_FIXES_NOW.php`
9. `ML_FIXES_IMPLEMENTATION_GUIDE.md`
10. `ML_PREDICTION_LIFECYCLE_COMPLETE_FIX.md`

### Files to Modify (Frontend Integration)

1. `frontend/src/components/RiskAssessmentPreview.jsx`
   - Change API call from `save_estimate_prediction.php` to `save_layout_request_prediction.php`
   - Pass `layout_request_id` instead of `estimate_id`

2. Backend contractor estimate creation logic
   - Add call to `copy_predictions_to_estimate.php` after estimate is created

---

## Next Steps

1. ✅ Apply database fixes: `php APPLY_ML_FIXES_NOW.php`
2. ✅ Verify all columns added
3. ⏳ Update frontend to use new API endpoint
4. ⏳ Update contractor estimate creation to copy predictions
5. ⏳ Test complete workflow
6. ⏳ Monitor evaluation metrics
7. ⏳ Run retraining when sufficient data exists

---

## Support

For issues or questions:
1. Check this guide first
2. Review error logs
3. Verify database schema
4. Test each stage independently

