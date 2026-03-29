# ML Prediction Lifecycle - Quick Reference Card

## 🚀 Quick Start

```bash
# 1. Apply all fixes
php APPLY_ML_FIXES_NOW.php

# 2. Verify installation
mysql -u root -p buildhub -e "SHOW COLUMNS FROM layout_requests LIKE 'predicted%';"

# 3. Test the system (submit homeowner request with AI predictions)
```

## 📊 System Overview

| Stage | Storage Table | API Endpoint |
|-------|--------------|--------------|
| Homeowner Request | `layout_requests` | `save_layout_request_prediction.php` |
| Contractor Estimate | `contractor_send_estimates` | `copy_predictions_to_estimate.php` |
| Project Creation | `construction_projects` | (existing trigger) |
| Project Completion | `construction_projects` | `evaluate_project_predictions_3class()` |
| Model Retraining | ML models | `python retrain_models.py` |

## 🔧 Key Fixes Applied

| # | Issue | Solution |
|---|-------|----------|
| 1 | Storage timing | Store in `layout_requests` first |
| 2 | Medium risk | 3-class evaluation (Low/Med/High) |
| 3 | Training data | Minimum 150-200 projects |
| 4 | Triggers | Application-level APIs |
| 5 | Features | Complete feature extraction |
| 6 | Binary eval | Multi-class confusion matrix |
| 7 | Explanations | Dynamic explanations stored |
| 8 | Versioning | Model version tracking |

## 📁 Files Created

### Database Schema (3 files)
```
backend/database/schema_fixes/
├── 01_layout_requests_predictions.sql
├── 02_contractor_estimates_predictions.sql
└── 03_update_construction_projects_evaluation.sql
```

### Stored Procedures (1 file)
```
backend/database/procedures/
└── evaluate_project_3class.sql
```

### Backend APIs (2 files)
```
backend/api/ml/
├── save_layout_request_prediction.php
└── copy_predictions_to_estimate.php
```

### ML Pipeline (1 file)
```
backend/ml/
└── retrain_models.py
```

### Automation (1 file)
```
APPLY_ML_FIXES_NOW.php
```

## 🔍 Verification Queries

### Check layout_requests columns
```sql
SHOW COLUMNS FROM layout_requests LIKE 'predicted%';
-- Expected: 8 columns
```

### Check contractor_send_estimates columns
```sql
SHOW COLUMNS FROM contractor_send_estimates LIKE 'predicted%';
-- Expected: 8 columns
```

### Check evaluation procedures
```sql
SHOW PROCEDURE STATUS WHERE Name LIKE '%3class%';
-- Expected: 3 procedures
```

### Check thresholds
```sql
SELECT * FROM ai_evaluation_config WHERE config_key LIKE '%threshold%';
-- Expected: 4 threshold configs
```

## 📝 API Usage

### Save Prediction to Layout Request
```javascript
POST /backend/api/ml/save_layout_request_prediction.php
{
  "layout_request_id": 123,
  "cost_risk_level": "High",
  "cost_probability": 0.9550,
  "time_risk_level": "Low",
  "time_probability": 0.1520,
  "model_version": "v1.0.0"
}
```

### Copy Predictions to Estimate
```javascript
POST /backend/api/ml/copy_predictions_to_estimate.php
{
  "estimate_id": 456,
  "layout_request_id": 123
}
```

### Evaluate Project
```sql
CALL evaluate_project_predictions_3class(789);
```

## 🎯 3-Class Thresholds

| Class | Cost Overrun | Time Overrun |
|-------|--------------|--------------|
| Low | < 5% | < 5% |
| Medium | 5-15% | 5-15% |
| High | > 15% | > 15% |

## 🔄 Retraining Workflow

```bash
# Check eligibility (150+ projects required)
python backend/ml/retrain_models.py

# Output shows:
# - Projects found
# - Feature extraction
# - Model training
# - Accuracy metrics
# - Version update
```

## 🧪 Testing Checklist

- [ ] Predictions save to layout_requests
- [ ] Predictions copy to contractor_send_estimates
- [ ] Predictions copy to construction_projects
- [ ] Predictions lock when work begins
- [ ] 3-class evaluation works
- [ ] Metrics calculate correctly
- [ ] Model version tracks properly
- [ ] Retraining pipeline works

## 🐛 Troubleshooting

### Predictions not saving
```sql
-- Check if columns exist
SHOW COLUMNS FROM layout_requests LIKE 'predicted%';

-- If missing, run:
php APPLY_ML_FIXES_NOW.php
```

### Evaluation not working
```sql
-- Check procedures
SHOW PROCEDURE STATUS WHERE Name LIKE '%3class%';

-- Check thresholds
SELECT * FROM ai_evaluation_config WHERE config_key LIKE '%threshold%';
```

### Retraining fails
```bash
# Check project count
mysql -u root -p buildhub -e "
SELECT COUNT(*) FROM construction_projects 
WHERE status='completed' AND evaluation_completed_at IS NOT NULL;"

# Need 150+ projects
```

## 📊 Monitoring Queries

### Check prediction storage rate
```sql
SELECT 
  COUNT(*) as total_requests,
  SUM(CASE WHEN predicted_cost_risk_level IS NOT NULL THEN 1 ELSE 0 END) as with_predictions,
  ROUND(SUM(CASE WHEN predicted_cost_risk_level IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as percentage
FROM layout_requests
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Check evaluation completion rate
```sql
SELECT 
  COUNT(*) as completed_projects,
  SUM(CASE WHEN evaluation_completed_at IS NOT NULL THEN 1 ELSE 0 END) as evaluated,
  ROUND(SUM(CASE WHEN evaluation_completed_at IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as percentage
FROM construction_projects
WHERE status = 'completed';
```

### Check model accuracy
```sql
SELECT 
  'Cost' as metric_type,
  ROUND(AVG(cost_prediction_correct) * 100, 2) as accuracy_pct
FROM construction_projects
WHERE evaluation_completed_at IS NOT NULL
UNION ALL
SELECT 
  'Time' as metric_type,
  ROUND(AVG(time_prediction_correct) * 100, 2) as accuracy_pct
FROM construction_projects
WHERE evaluation_completed_at IS NOT NULL;
```

## 🔗 Related Documents

- `ML_FIXES_IMPLEMENTATION_GUIDE.md` - Complete implementation guide
- `ML_FIXES_EXECUTIVE_SUMMARY.md` - Executive summary
- `ML_SYSTEM_CORRECTED_WORKFLOW.md` - Visual workflow diagram
- `ML_PREDICTION_LIFECYCLE_COMPLETE_FIX.md` - Technical details

## 📞 Support

1. Check this quick reference
2. Review implementation guide
3. Verify database schema
4. Check error logs
5. Test each stage independently

---

**Version**: 1.0  
**Last Updated**: 2026-03-12  
**Status**: Production Ready
