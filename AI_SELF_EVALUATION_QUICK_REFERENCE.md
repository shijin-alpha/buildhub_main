# AI Self-Evaluation Framework - Quick Reference

## 🚀 Installation (3 Steps)

```bash
# 1. Backup database
mysqldump -u root -p buildhub > backup.sql

# 2. Run migration
php apply_ai_self_evaluation_migration.php

# 3. Test installation
php test_ai_self_evaluation.php
```

## 📊 Key Concepts

### Confusion Matrix Classification

```
Predicted High/Medium + Actual Overrun = TP (True Positive) ✅
Predicted High/Medium + No Overrun = FP (False Positive) ❌
Predicted Low + No Overrun = TN (True Negative) ✅
Predicted Low + Actual Overrun = FN (False Negative) ❌
```

### Metrics Formulas

```
Accuracy = (TP + TN) / Total
Precision = TP / (TP + FP)
Recall = TP / (TP + FN)
F1 Score = 2 × (Precision × Recall) / (Precision + Recall)
```

## 🔌 API Quick Reference

### Save Prediction
```bash
POST /backend/api/ml/save_ai_prediction.php
{
  "project_id": 1,
  "cost_risk_level": "High",
  "cost_probability": 0.85,
  "time_risk_level": "Medium",
  "time_probability": 0.62
}
```

### Get Latest Metrics
```bash
GET /backend/api/ml/get_evaluation_metrics.php?action=latest
```

### Get Confusion Matrix
```bash
GET /backend/api/ml/get_evaluation_metrics.php?action=confusion_matrix
```

### Calculate Metrics
```bash
GET /backend/api/ml/get_evaluation_metrics.php?action=calculate
```

### Get Project Performance
```bash
GET /backend/api/ml/get_evaluation_metrics.php?action=project_performance&project_id=1
```

## 🗄️ SQL Quick Reference

### View Latest Metrics
```sql
SELECT * FROM v_latest_evaluation_metrics;
```

### View Confusion Matrix
```sql
SELECT * FROM v_confusion_matrix_summary;
```

### View Project Performance
```sql
SELECT * FROM v_ai_prediction_performance
WHERE project_id = 1;
```

### Calculate Metrics
```sql
CALL calculate_aggregate_metrics();
```

### Evaluate Specific Project
```sql
CALL evaluate_project(1);
```

### Check Configuration
```sql
SELECT * FROM ai_evaluation_config;
```

### Update Threshold
```sql
UPDATE ai_evaluation_config
SET config_value = '10.0'
WHERE config_key = 'cost_overrun_threshold';
```

## 🔄 Workflow Summary

```
1. Save Prediction (project confirmation)
   ↓
2. Lock Prediction (work begins)
   ↓
3. Execute Project (track actuals)
   ↓
4. Complete Project (auto-evaluate)
   ↓
5. Calculate Metrics (aggregate)
```

## 📋 Database Fields

### Prediction Storage
- `predicted_cost_risk_level` - Low/Medium/High
- `predicted_cost_probability` - 0-1
- `predicted_time_risk_level` - Low/Medium/High
- `predicted_time_probability` - 0-1
- `prediction_generated_at` - Timestamp
- `model_version` - Version string

### Evaluation Results
- `actual_cost_overrun_percentage` - Calculated %
- `actual_time_overrun_percentage` - Calculated %
- `cost_ground_truth_label` - Overrun/No_Overrun
- `time_ground_truth_label` - Overrun/No_Overrun
- `cost_prediction_classification` - TP/FP/TN/FN
- `time_prediction_classification` - TP/FP/TN/FN
- `cost_prediction_correct` - 1/0
- `time_prediction_correct` - 1/0
- `evaluation_completed_at` - Timestamp
- `predictions_locked` - 1/0

## ⚙️ Configuration Keys

- `cost_overrun_threshold` - Default: 5.0 (%)
- `time_overrun_threshold` - Default: 5.0 (%)
- `high_risk_threshold` - Default: 0.70 (70%)
- `medium_risk_threshold` - Default: 0.40 (40%)
- `current_model_version` - Default: v1.0.0
- `auto_evaluation_enabled` - Default: 1 (yes)

## 🧪 Testing

### Test Interface
```
http://localhost/buildhub/test_ai_self_evaluation_system.html
```

### Automated Tests
```bash
php test_ai_self_evaluation.php
```

### Manual SQL Tests
```sql
-- Test prediction saving
SELECT id, predicted_cost_risk_level, predictions_locked
FROM construction_projects
WHERE predicted_cost_risk_level IS NOT NULL
LIMIT 5;

-- Test evaluation
SELECT id, evaluation_completed_at, cost_prediction_correct
FROM construction_projects
WHERE evaluation_completed_at IS NOT NULL
LIMIT 5;

-- Test audit log
SELECT project_id, event_type, created_at
FROM ai_prediction_audit
ORDER BY created_at DESC
LIMIT 10;
```

## 🔧 Common Tasks

### Disable Auto-Evaluation
```sql
UPDATE ai_evaluation_config
SET config_value = '0'
WHERE config_key = 'auto_evaluation_enabled';
```

### Re-evaluate All Projects
```sql
UPDATE construction_projects
SET evaluation_completed_at = NULL
WHERE status = 'completed';

-- Then trigger completion again or call manually
CALL evaluate_project(project_id);
```

### View Incorrect Predictions
```sql
SELECT id, project_name, 
       predicted_cost_risk_level, actual_cost_overrun_percentage,
       cost_prediction_classification
FROM construction_projects
WHERE cost_prediction_correct = 0
AND evaluation_completed_at IS NOT NULL;
```

### Export Metrics to CSV
```sql
SELECT 
  evaluation_date,
  metric_type,
  true_positives as TP,
  false_positives as FP,
  true_negatives as TN,
  false_negatives as FN,
  ROUND(accuracy * 100, 2) as accuracy_pct,
  ROUND(precision_score * 100, 2) as precision_pct,
  ROUND(recall_score * 100, 2) as recall_pct,
  ROUND(f1_score * 100, 2) as f1_pct
FROM ai_evaluation_metrics
ORDER BY evaluation_date DESC, metric_type
INTO OUTFILE '/tmp/ai_metrics.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

## 🚨 Troubleshooting

### Predictions Not Saving
```sql
-- Check if procedure exists
SHOW PROCEDURE STATUS WHERE Name = 'save_ai_prediction';

-- Test directly
CALL save_ai_prediction(1, 'High', 0.85, 'Medium', 0.62, 'v1.0.0');
```

### Predictions Not Locking
```sql
-- Check if trigger exists
SHOW TRIGGERS WHERE `Trigger` = 'lock_predictions_on_work_start';

-- Test manually
UPDATE construction_projects
SET actual_start_date = CURDATE()
WHERE id = 1;
```

### Evaluation Not Running
```sql
-- Check if enabled
SELECT config_value FROM ai_evaluation_config
WHERE config_key = 'auto_evaluation_enabled';

-- Run manually
CALL evaluate_project(1);
```

### Metrics Are NULL
```sql
-- Check evaluated projects
SELECT COUNT(*) FROM construction_projects
WHERE evaluation_completed_at IS NOT NULL;

-- Calculate manually
CALL calculate_aggregate_metrics();
```

## 📚 Documentation Files

- `AI_SELF_EVALUATION_FRAMEWORK.md` - Complete documentation
- `AI_SELF_EVALUATION_QUICK_REFERENCE.md` - This file
- `backend/database/ai_self_evaluation_schema.sql` - Schema
- `backend/database/ai_evaluation_procedures.sql` - Procedures
- `test_ai_self_evaluation.php` - Automated tests
- `test_ai_self_evaluation_system.html` - Test interface

## ✅ Checklist

- [ ] Database backed up
- [ ] Migration completed successfully
- [ ] Tests passed
- [ ] Frontend updated to save predictions
- [ ] First prediction saved and verified
- [ ] First project evaluated successfully
- [ ] Metrics calculated and displayed
- [ ] Audit log working
- [ ] Predictions locking correctly

---

**Quick Reference Version:** 1.0  
**Last Updated:** February 16, 2026
