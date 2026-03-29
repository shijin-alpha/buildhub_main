# ML Prediction Lifecycle - Complete Technical Review & Fix

## Executive Summary

This document provides a comprehensive fix for all identified issues in the BUILDHUB ML prediction lifecycle system.

### Critical Issues Identified

1. **Prediction Storage Timing Issue**: Predictions generated during homeowner request stage cannot be stored because `estimate_id` doesn't exist yet
2. **Wrong Storage Table**: System tries to store in `contractor_send_estimates` before estimate is created
3. **Medium Risk Misclassification**: Medium risk treated as "No Overrun" distorts evaluation
4. **Insufficient Training Data**: Retraining triggers at only 50 projects (too small)
5. **Complex Trigger Logic**: Multiple database triggers increase complexity
6. **Incomplete Feature Set**: Retraining may miss original features
7. **Binary Evaluation**: System evaluates 3-class predictions as binary
8. **Static Explanations**: Feature importance is static, not dynamic
9. **Missing Model Versioning**: Predictions not properly linked to model versions

### Solution Overview

- Store predictions in `layout_requests` table during homeowner request stage
- Copy predictions to `contractor_send_estimates` when contractor creates estimate
- Implement proper 3-class evaluation (Low/Medium/High)
- Increase retraining threshold to 150-200 completed projects
- Replace triggers with application-level logic
- Ensure complete feature set for retraining
- Add comprehensive evaluation metrics (confusion matrix, precision, recall, F1)
- Implement proper model version tracking

---

## Part 1: Corrected System Workflow

### Current Broken Workflow

```
Homeowner Request → AI Prediction → [FAILS: No estimate_id] → Contractor Estimate → Project
```

### Fixed Workflow

```
1. Homeowner Request Stage:
   - Homeowner fills custom request form
   - AI generates predictions
   - Predictions stored in layout_requests table ✓
   - Model version recorded ✓

2. Contractor Estimate Stage:
   - Contractor creates estimate for layout_request
   - Predictions copied from layout_requests to contractor_send_estimates ✓
   - Estimate_id now exists ✓

3. Project Creation Stage:
   - Homeowner accepts estimate
   - Project created with estimate_id
   - Predictions copied from contractor_send_estimates to construction_projects ✓
   - Predictions locked when work begins ✓

4. Project Completion Stage:
   - Project marked as completed
   - Actual costs and timeline calculated
   - Ground truth labels determined (3-class: Low/Medium/High) ✓
   - Predictions evaluated against actuals ✓
   - Metrics updated ✓

5. Model Retraining Stage:
   - Triggered when 150-200 completed projects with evaluations exist ✓
   - Full feature set extracted from completed projects ✓
   - Models retrained with complete data ✓
   - Model version incremented ✓
```

---


## Part 2: Database Schema Changes

### 2.1 Add Prediction Columns to layout_requests Table

This is the PRIMARY storage location for predictions during the homeowner request stage.

```sql
-- File: backend/database/schema_fixes/01_layout_requests_predictions.sql

ALTER TABLE layout_requests
ADD COLUMN predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL 
  COMMENT 'AI predicted cost overrun risk level',
ADD COLUMN predicted_cost_probability DECIMAL(5,4) NULL 
  COMMENT 'Probability of predicted cost risk (0-1)',
ADD COLUMN predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL 
  COMMENT 'AI predicted time delay risk level',
ADD COLUMN predicted_time_probability DECIMAL(5,4) NULL 
  COMMENT 'Probability of predicted time risk (0-1)',
ADD COLUMN prediction_generated_at TIMESTAMP NULL 
  COMMENT 'When AI prediction was made',
ADD COLUMN model_version VARCHAR(50) NULL 
  COMMENT 'ML model version used for prediction',
ADD COLUMN prediction_features JSON NULL 
  COMMENT 'Features used for prediction (for retraining)',
ADD COLUMN prediction_explanation JSON NULL 
  COMMENT 'Top risk factors and explanations';

-- Add index for querying predictions
CREATE INDEX idx_layout_predictions 
ON layout_requests(predicted_cost_risk_level, predicted_time_risk_level);
```

### 2.2 Add Prediction Columns to contractor_send_estimates Table

```sql
-- File: backend/database/schema_fixes/02_contractor_estimates_predictions.sql

ALTER TABLE contractor_send_estimates
ADD COLUMN predicted_cost_risk_level ENUM('Low', 'Medium', 'High') NULL,
ADD COLUMN predicted_cost_probability DECIMAL(5,4) NULL,
ADD COLUMN predicted_time_risk_level ENUM('Low', 'Medium', 'High') NULL,
ADD COLUMN predicted_time_probability DECIMAL(5,4) NULL,
ADD COLUMN prediction_generated_at TIMESTAMP NULL,
ADD COLUMN model_version VARCHAR(50) NULL,
ADD COLUMN prediction_features JSON NULL,
ADD COLUMN prediction_explanation JSON NULL,
ADD COLUMN layout_request_id INT NULL COMMENT 'Link to original layout request',
ADD FOREIGN KEY (layout_request_id) REFERENCES layout_requests(id) ON DELETE SET NULL;

CREATE INDEX idx_estimate_predictions 
ON contractor_send_estimates(predicted_cost_risk_level, predicted_time_risk_level);
```

